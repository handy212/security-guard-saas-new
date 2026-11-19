#requires -Version 5.1
Set-StrictMode -Version Latest
Add-Type -AssemblyName System.Net.Http

function Read-JwmBoundedFile([string] $Path, [int] $MaximumBytes) {
    $stream = [System.IO.File]::Open($Path, [System.IO.FileMode]::Open, [System.IO.FileAccess]::Read, [System.IO.FileShare]::Read)
    try {
        if ($stream.Length -le 0 -or $stream.Length -gt $MaximumBytes) {
            throw "Input file must contain between 1 and $MaximumBytes bytes."
        }
        $bytes = [byte[]]::new([int] $stream.Length)
        $position = 0
        while ($position -lt $bytes.Length) {
            $read = $stream.Read($bytes, $position, $bytes.Length - $position)
            if ($read -eq 0) { throw 'Input file changed while being read.' }
            $position += $read
        }
        if ($stream.ReadByte() -ne -1) { throw 'Input file changed while being read.' }
        return ,$bytes
    } finally { $stream.Dispose() }
}

function Get-JwmSha256([byte[]] $Bytes) {
    $hasher = [System.Security.Cryptography.SHA256]::Create()
    try { [System.BitConverter]::ToString($hasher.ComputeHash($Bytes)).Replace('-', '').ToLowerInvariant() }
    finally { $hasher.Dispose() }
}

function ConvertTo-JwmUtc([string] $Value) {
    $instant = [datetimeoffset]::MinValue
    if ($Value -cnotmatch '^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z\z' -or
        -not [datetimeoffset]::TryParseExact($Value, "yyyy-MM-dd'T'HH:mm:ss'Z'",
            [cultureinfo]::InvariantCulture, [System.Globalization.DateTimeStyles]::AssumeUniversal, [ref] $instant)) {
        throw 'Use an explicit whole-second UTC window, for example 2026-11-16T20:00:00Z.'
    }
    $instant.ToUniversalTime()
}

function ConvertFrom-JwmUsbCapture {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory)] [string] $CaptureDirectory,
        [Parameter(Mandatory)] [ValidatePattern('^[0-9]+\z')] [string] $ReaderNumber,
        [Parameter(Mandatory)] [ValidateRange(1, 2147483647)] [int] $PatrolSessionId,
        [Parameter(Mandatory)] [string] $UtcOffset,
        [Parameter(Mandatory)] [string] $FromUtc,
        [Parameter(Mandatory)] [string] $BeforeUtc
    )

    $ErrorActionPreference = 'Stop'
    $from = ConvertTo-JwmUtc $FromUtc
    $before = ConvertTo-JwmUtc $BeforeUtc
    if ($from -ge $before) { throw 'The UTC window start must be before its end.' }
    if ($UtcOffset -cnotmatch '^[+-](0[0-9]|1[0-4]):[0-5][0-9]\z' -or
        ($UtcOffset.Substring(1, 2) -eq '14' -and $UtcOffset.Substring(4, 2) -ne '00')) {
        throw 'The reader offset must be explicit, between -14:00 and +14:00.'
    }
    $utf8 = [System.Text.UTF8Encoding]::new($false, $true)
    $manifestBytes = Read-JwmBoundedFile (Join-Path $CaptureDirectory 'manifest.json') 65536
    $manifest = $utf8.GetString($manifestBytes).TrimStart([char]0xfeff) | ConvertFrom-Json
    foreach ($field in @('schema_version', 'status', 'device_id', 'sdk_sha256', 'verification_value', 'export')) {
        if ($field -notin $manifest.PSObject.Properties.Name) { throw "Invalid capture manifest: missing $field." }
    }
    if ($manifest.schema_version -ne 1 -or $manifest.status -cne 'captured') {
        throw 'Only a completed captured manifest with schema version 1 can be uploaded.'
    }
    if ([string] $manifest.device_id -cne $ReaderNumber) { throw 'The capture reader does not match the expected reader number.' }
    if ($manifest.sdk_sha256 -notmatch '^[a-fA-F0-9]{64}\z' -or $manifest.verification_value -notin @(0, 1)) {
        throw 'Invalid SDK identity or verification mode in capture manifest.'
    }
    foreach ($field in @('path', 'bytes', 'sha256')) {
        if ($field -notin $manifest.export.PSObject.Properties.Name) { throw "Invalid export manifest: missing $field." }
    }
    if ($manifest.export.path -cne 'records.txt') { throw 'The export manifest must reference records.txt in the capture directory.' }
    $bytes = Read-JwmBoundedFile (Join-Path $CaptureDirectory 'records.txt') (10 * 1024 * 1024)
    if ($manifest.export.bytes -ne $bytes.Length -or $manifest.export.sha256 -ine (Get-JwmSha256 $bytes)) {
        throw 'Export integrity check failed. Preserve the original records and manifest together.'
    }
    $text = $utf8.GetString($bytes).TrimStart([char]0xfeff)
    if ($text -match '[^\x09\x0a\x0d\x20-\x7e]') { throw 'This tag-only adapter requires ASCII record fields; encoding/content needs review.' }
    $lines = [regex]::Split($text, '\r\n|\n|\r')
    $lineCount = $lines.Length
    if ($lines[$lineCount - 1] -ceq '') { $lineCount-- }
    $events = [System.Collections.Generic.List[object]]::new()
    $seen = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::Ordinal)
    $excluded = 0
    $blankLines = 0
    $previous = [datetimeoffset]::MinValue
    for ($index = 0; $index -lt $lineCount; $index++) {
        $lineNumber = $index + 1
        if ($lines[$index] -cmatch '^ *\z') { $blankLines++; continue }
        $fields = $lines[$index].Split([char]9)
        if ($fields.Length -ne 13) { throw "Source line ${lineNumber}: expected exactly 13 tab-separated fields." }
        $occurred = [datetimeoffset]::MinValue
        if ($fields[9] -cnotmatch '^[0-9]{12}\z' -or
            -not [datetimeoffset]::TryParseExact(('20' + $fields[9] + $UtcOffset), 'yyyyMMddHHmmsszzz',
                [cultureinfo]::InvariantCulture, [System.Globalization.DateTimeStyles]::None, [ref] $occurred)) {
            throw "Source line ${lineNumber}: invalid device timestamp."
        }
        $occurred = $occurred.ToUniversalTime()
        if ($occurred -lt $from -or $occurred -ge $before) { $excluded++; continue }
        if ($occurred -gt [datetimeoffset]::UtcNow) { throw "Source line ${lineNumber}: device timestamp is in the future." }
        if ($fields[0] -cne '2') { throw "Source line ${lineNumber}: unsupported record type; only tag scans (2) are enabled." }
        if ($fields[2] -cnotmatch '^[!-~]{1,100}\z' -or $fields[3] -cnotmatch '^[!-~]{1,100}\z') {
            throw "Source line ${lineNumber}: invalid guard or checkpoint identifier; whitespace is not trimmed."
        }
        if ($fields[1] -cnotmatch '^-?[0-9]+\z') { throw "Source line ${lineNumber}: invalid native line ID." }
        foreach ($column in @(4, 5, 6, 7)) {
            if ($fields[$column] -cnotmatch '^-?[0-9]+(\.[0-9]+)?\z') { throw "Source line ${lineNumber}: invalid numeric metadata." }
        }
        if ($fields[8] -cnotmatch '^[0-9]+\z' -or $fields[11] -cnotmatch '^[0-9]+\z') {
            throw "Source line ${lineNumber}: invalid satellite/step metadata."
        }
        if ($fields[10] -cne '0') { throw "Source line ${lineNumber}: image-linked records require a separate reviewed adapter." }
        if ($fields[12] -cne '()') { throw "Source line ${lineNumber}: nonempty event content requires a separate reviewed adapter." }
        # Native lineid may be -1. Ignore it, GPS metadata and upload/session context.
        $identity = @('jwm-usb-tag-v1', $fields[2], $fields[3], $fields[9]) -join "`t"
        $eventId = 'usb-tag-v1-' + (Get-JwmSha256 ($utf8.GetBytes($identity)))
        if (-not $seen.Add($eventId)) {
            throw "Source line ${lineNumber}: indistinguishable scans share guard, tag and device second; operator review is required."
        }
        if ($occurred -lt $previous) { throw "Source line ${lineNumber}: records are out of chronological order; operator review is required." }
        $previous = $occurred
        $events.Add([pscustomobject]@{
            SourceLine = $lineNumber
            Payload = [ordered]@{
                event_id = $eventId; type = 'checkpoint'
                occurred_at = $occurred.ToString("yyyy-MM-dd'T'HH:mm:ss'Z'", [cultureinfo]::InvariantCulture)
                patrol_session_id = $PatrolSessionId
                guard_external_id = $fields[2]; checkpoint_external_id = $fields[3]
            }
        })
    }
    if ($events.Count -eq 0) { throw 'No tag scans are selected by this UTC window. Nothing can be uploaded.' }
    [pscustomobject]@{ Events = $events.ToArray(); ExcludedRows = $excluded; BlankLines = $blankLines }
}

function Send-JwmUsbEvents {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory)] [pscustomobject] $Batch,
        [Parameter(Mandatory)] [uri] $ApiBaseUrl,
        [Parameter(Mandatory)] [ValidateRange(1, 2147483647)] [int] $DeviceId,
        [Parameter(Mandatory)] [System.Security.SecureString] $Token,
        [switch] $AllowLoopbackHttp,
        [Parameter(DontShow)] [System.Net.Http.HttpClient] $HttpClient
    )

    $ErrorActionPreference = 'Stop'
    $localHttp = $ApiBaseUrl.IsAbsoluteUri -and $AllowLoopbackHttp -and $ApiBaseUrl.Scheme -ceq 'http' -and
        $ApiBaseUrl.Host -in @('127.0.0.1', '[::1]')
    if (-not $ApiBaseUrl.IsAbsoluteUri -or ($ApiBaseUrl.Scheme -cne 'https' -and -not $localHttp) -or $ApiBaseUrl.UserInfo -or
        $ApiBaseUrl.Query -or $ApiBaseUrl.Fragment -or $ApiBaseUrl.AbsolutePath -cnotmatch '/api/v1/?\z') {
        throw 'Use your application HTTPS API base URL ending in /api/v1, without credentials, query or fragment. HTTP is allowed only with -AllowLoopbackHttp and a literal 127.0.0.1 or [::1] destination.'
    }
    if ($Token.Length -eq 0) { throw 'A nonempty device-bound bridge token is required.' }
    if ($Batch.Events.Count -eq 0) { throw 'No validated events are available to upload.' }
    $ownsClient = $null -eq $HttpClient
    if ($ownsClient) {
        $handler = [System.Net.Http.HttpClientHandler]::new()
        $handler.AllowAutoRedirect = $false
        $handler.UseCookies = $false
        $handler.UseDefaultCredentials = $false
        $HttpClient = [System.Net.Http.HttpClient]::new($handler)
        $HttpClient.Timeout = [timespan]::FromSeconds(30)
        $HttpClient.MaxResponseContentBufferSize = 1048576
    }
    $created = 0
    $replayed = 0
    try {
        $endpoint = $ApiBaseUrl.AbsoluteUri.TrimEnd('/') + "/jwm/devices/$DeviceId/events"
        foreach ($event in $Batch.Events) {
            $request = [System.Net.Http.HttpRequestMessage]::new([System.Net.Http.HttpMethod]::Post, $endpoint)
            $response = $null
            try {
                $request.Headers.Accept.ParseAdd('application/json')
                $request.Headers.Authorization = [System.Net.Http.Headers.AuthenticationHeaderValue]::new(
                    'Bearer', ([System.Net.NetworkCredential]::new('', $Token)).Password)
                $request.Content = [System.Net.Http.StringContent]::new(
                    ($event.Payload | ConvertTo-Json -Compress), [System.Text.Encoding]::UTF8, 'application/json')
                $response = $HttpClient.SendAsync($request).GetAwaiter().GetResult()
                $status = [int] $response.StatusCode
                if ($status -notin @(200, 201)) {
                    throw "Upload stopped at source line $($event.SourceLine) (HTTP $status). Previously accepted rows remain saved; review authorization, mappings and patrol eligibility before retrying identical arguments."
                }
                try {
                    $ack = $response.Content.ReadAsStringAsync().GetAwaiter().GetResult() | ConvertFrom-Json
                } catch [System.ArgumentException] {
                    throw "Invalid API acknowledgment at source line $($event.SourceLine). Response was not valid JSON; outcome is uncertain."
                }
                if ($null -eq $ack -or 'data' -notin $ack.PSObject.Properties.Name -or 'replayed' -notin $ack.PSObject.Properties.Name -or
                    $null -eq $ack.data -or 'event_id' -notin $ack.data.PSObject.Properties.Name -or
                    'jwm_device_id' -notin $ack.data.PSObject.Properties.Name -or
                    $ack.data.event_id -cne $event.Payload.event_id -or $ack.data.jwm_device_id -ne $DeviceId -or
                    $ack.replayed -isnot [bool] -or $ack.replayed -ne ($status -eq 200)) {
                    throw "Invalid API acknowledgment at source line $($event.SourceLine). Outcome is uncertain; retain capture and retry identical arguments only after review."
                }
                if ($ack.replayed) { $replayed++ } else { $created++ }
            } finally {
                if ($null -ne $response) { $response.Dispose() }
                $request.Dispose()
            }
            if ($created + $replayed -lt $Batch.Events.Count) { Start-Sleep -Milliseconds 1100 }
        }
        [pscustomobject]@{ Created = $created; Replayed = $replayed }
    } finally {
        if ($ownsClient) { $HttpClient.Dispose() }
    }
}

Export-ModuleMember -Function ConvertFrom-JwmUsbCapture, Send-JwmUsbEvents
