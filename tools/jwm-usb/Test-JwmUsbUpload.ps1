#requires -Version 5.1
[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
Import-Module (Join-Path $PSScriptRoot 'JwmUsbUpload.psm1') -Force
Add-Type -AssemblyName System.Net.Http

function Assert-Equal($Actual, $Expected) {
    if ($Actual -cne $Expected) { throw "Expected [$Expected], got [$Actual]." }
}

function Assert-Fails([scriptblock] $Action, [string] $Pattern) {
    try { & $Action } catch {
        if ($_.Exception.Message -notmatch $Pattern) { throw }
        return
    }
    throw "Expected a failure matching [$Pattern]."
}

$root = Join-Path ([System.IO.Path]::GetTempPath()) ('jwm-upload-tests-' + [guid]::NewGuid().ToString('N'))
$null = New-Item -ItemType Directory -Path $root
$marker = Join-Path $root '.test-owner'
$owner = [guid]::NewGuid().ToString('N')
[System.IO.File]::WriteAllText($marker, $owner)
$encoding = [System.Text.UTF8Encoding]::new($false)
$row = "2`t-1`t0000000042`tAB00112233`t0.000000`t0.000000`t0.00`t0.00`t0`t200116101024`t0`t0`t()"
$row2 = $row.Replace('AB00112233', 'CD00445566').Replace('200116101024', '200116101124')

function New-Capture([string] $Text) {
    $directory = Join-Path $root ([guid]::NewGuid().ToString('N'))
    $null = New-Item -ItemType Directory -Path $directory
    $records = Join-Path $directory 'records.txt'
    [System.IO.File]::WriteAllText($records, $Text, $encoding)
    $manifest = @{
        schema_version = 1; status = 'captured'; device_id = 123456
        verification_value = 1; sdk_sha256 = ('A' * 64)
        export = @{ path = 'records.txt'; bytes = (Get-Item -LiteralPath $records).Length; sha256 = (Get-FileHash -LiteralPath $records).Hash }
    }
    [System.IO.File]::WriteAllText((Join-Path $directory 'manifest.json'), ($manifest | ConvertTo-Json), $encoding)
    $directory
}

function Parse([string] $Directory) {
    ConvertFrom-JwmUsbCapture -CaptureDirectory $Directory -ReaderNumber '123456' -PatrolSessionId 7 `
        -UtcOffset '+00:00' -FromUtc '2020-01-16T10:00:00Z' -BeforeUtc '2020-01-16T11:00:00Z'
}

try {
    $directory = New-Capture "$row`r`n$row2`r`n"
    $batch = Parse $directory
    Assert-Equal $batch.Events.Count 2
    Assert-Equal $batch.Events[0].Payload.guard_external_id '0000000042'
    Assert-Equal $batch.Events[0].Payload.checkpoint_external_id 'AB00112233'
    Assert-Equal $batch.Events[0].Payload.occurred_at '2020-01-16T10:10:24Z'
    Assert-Equal $batch.Events[0].Payload.patrol_session_id 7
    Assert-Equal ($batch.Events[0].Payload.Contains('latitude')) $false
    Assert-Equal $batch.Events[0].Payload.type 'checkpoint'
    Assert-Equal ($batch.Events[0].Payload.event_id -match '^usb-tag-v1-[a-f0-9]{64}$') $true
    Assert-Equal ($batch.Events[0].Payload.event_id -ne $batch.Events[1].Payload.event_id) $true
    $other = Parse (New-Capture ($row.Replace("`t-1`t", "`t987`t") + "`n" + $row2))
    Assert-Equal $other.Events[0].Payload.event_id $batch.Events[0].Payload.event_id
    Write-Output 'PASS: Tag rows preserve exact identifiers, UTC time and stable identities without fake GPS.'

    $windowed = ConvertFrom-JwmUsbCapture -CaptureDirectory $directory -ReaderNumber '123456' -PatrolSessionId 7 `
        -UtcOffset '+00:00' -FromUtc '2020-01-16T10:11:24Z' -BeforeUtc '2020-01-16T10:11:25Z'
    Assert-Equal $windowed.Events.Count 1
    Assert-Equal $windowed.ExcludedRows 1
    Assert-Equal $windowed.Events[0].SourceLine 2
    $shifted = ConvertFrom-JwmUsbCapture -CaptureDirectory $directory -ReaderNumber '123456' -PatrolSessionId 7 `
        -UtcOffset '+01:00' -FromUtc '2020-01-16T09:00:00Z' -BeforeUtc '2020-01-16T10:00:00Z'
    Assert-Equal $shifted.Events[0].Payload.occurred_at '2020-01-16T09:10:24Z'
    Assert-Equal $shifted.Events[0].Payload.event_id $batch.Events[0].Payload.event_id
    Write-Output 'PASS: Explicit offset and start-inclusive/end-exclusive UTC window are honored.'

    foreach ($separator in @("`n`n", "`r`n`r`n", "`r`r`n")) {
        $withBlanks = New-Capture ($row + $separator + $row2)
        $hashBefore = (Get-FileHash -LiteralPath (Join-Path $withBlanks 'records.txt')).Hash
        $separated = Parse $withBlanks
        Assert-Equal $separated.Events.Count 2
        Assert-Equal $separated.BlankLines 1
        Assert-Equal $separated.ExcludedRows 0
        Assert-Equal $separated.Events[1].SourceLine 3
        Assert-Equal $separated.Events[1].Payload.event_id $batch.Events[1].Payload.event_id
        Assert-Equal (Get-FileHash -LiteralPath (Join-Path $withBlanks 'records.txt')).Hash $hashBefore
        Assert-Fails { Parse (New-Capture ($row + $separator + $row2 + "`tEXTRA")) } 'Source line 3: expected exactly 13'
    }
    $separated = Parse (New-Capture (" `r`n$row`r`n   `r`n$row2`r`n`r`n"))
    Assert-Equal $separated.BlankLines 3
    Assert-Equal $separated.Events[0].SourceLine 2
    Assert-Equal $separated.Events[1].SourceLine 4
    Assert-Fails { Parse (New-Capture "`r`n   `r`n") } 'No tag scans'
    Write-Output 'PASS: Blank separators are counted without changing source lines, replay identities or original bytes.'

    $shellPath = [System.Diagnostics.Process]::GetCurrentProcess().MainModule.FileName
    $preview = & $shellPath -NoLogo -NoProfile -File (Join-Path $PSScriptRoot 'Send-JwmUsbCapture.ps1') `
        -CaptureDirectory $withBlanks -ReaderNumber '123456' -PatrolSessionId 7 -UtcOffset '+00:00' `
        -FromUtc '2020-01-16T10:00:00Z' -BeforeUtc '2020-01-16T11:00:00Z'
    Assert-Equal $LASTEXITCODE 0
    Assert-Equal (($preview -join "`n") -match 'Preview only') $true
    Assert-Equal (($preview -join "`n") -match 'Validated tag scans: 2\.') $true
    Assert-Equal (($preview -join "`n") -match 'Blank separator lines ignored: 1\.') $true
    Assert-Equal (($preview -join "`n") -match '0000000042|AB00112233') $false
    Write-Output 'PASS: Default CLI preview completes without a token prompt, network request or printed patrol identifiers.'

    $invalidDirectory = New-Capture "$row`tEXTRA"
    $process = [System.Diagnostics.Process]::new()
    try {
        $process.StartInfo.FileName = $shellPath
        $script = Join-Path $PSScriptRoot 'Send-JwmUsbCapture.ps1'
        $process.StartInfo.Arguments = "-NoLogo -NoProfile -File `"$script`" -CaptureDirectory `"$invalidDirectory`" -ReaderNumber 123456 -PatrolSessionId 7 -UtcOffset +00:00 -FromUtc 2020-01-16T10:00:00Z -BeforeUtc 2020-01-16T11:00:00Z"
        $process.StartInfo.UseShellExecute = $false
        $process.StartInfo.RedirectStandardOutput = $true
        $process.StartInfo.RedirectStandardError = $true
        $null = $process.Start()
        $failureOutput = $process.StandardOutput.ReadToEnd() + $process.StandardError.ReadToEnd()
        $process.WaitForExit()
        Assert-Equal $process.ExitCode 1
        Assert-Equal ($failureOutput -match 'No upload was attempted') $true
        Assert-Equal ($failureOutput -match 'Some rows may already be saved') $false
    } finally { $process.Dispose() }
    Write-Output 'PASS: CLI preview validation failure never claims that rows may have been saved.'

    Assert-Fails { Parse (New-Capture "$row`n$($row.Replace("`t-1`t", "`t6`t"))") } 'indistinguishable'
    Assert-Fails { Parse (New-Capture "$row2`n$row") } 'order'
    Assert-Fails { Parse (New-Capture $row.Replace('200116101024', '200230101024')) } 'timestamp'
    $future = New-Capture $row.Replace('200116101024', '991116101024')
    Assert-Fails {
        ConvertFrom-JwmUsbCapture -CaptureDirectory $future -ReaderNumber '123456' -PatrolSessionId 7 `
            -UtcOffset '+00:00' -FromUtc '2099-11-16T10:00:00Z' -BeforeUtc '2099-11-16T11:00:00Z'
    } 'future'
    Assert-Fails { Parse (New-Capture $row.Replace("2`t-1", "3`t-1")) } 'type'
    Assert-Fails { Parse (New-Capture $row.Replace('()', '(personal event)')) } 'content'
    Assert-Fails { Parse (New-Capture $row.Replace('0000000042', ' 0000000042')) } 'identifier'
    Assert-Fails { Parse (New-Capture $row.Replace("`t0`t0`t()", "`t9`t0`t()")) } 'image'
    Assert-Fails { Parse (New-Capture "$row`tEXTRA") } '13'
    Assert-Fails { Parse (New-Capture "$row`n`t`n$row2") } '13'
    Assert-Fails { Parse (New-Capture ($row + "`n" + ("`t" * 12) + "`n" + $row2)) } 'timestamp'
    Write-Output 'PASS: Ambiguous duplicates, reordering and unsupported or malformed selected rows fail explicitly.'

    $corrupt = New-Capture $row
    [System.IO.File]::AppendAllText((Join-Path $corrupt 'records.txt'), 'tampered')
    Assert-Fails { Parse $corrupt } 'integrity'
    $changed = New-Capture $row
    [System.IO.File]::WriteAllText((Join-Path $changed 'records.txt'), $row.Replace('AB00112233', 'AB00112234'), $encoding)
    Assert-Fails { Parse $changed } 'integrity'
    $manifestPath = Join-Path $corrupt 'manifest.json'
    $manifest = Get-Content -LiteralPath $manifestPath -Raw | ConvertFrom-Json
    $manifest.status = 'failed'
    $manifest | ConvertTo-Json | Set-Content -LiteralPath $manifestPath
    Assert-Fails { Parse $corrupt } 'captured'
    Assert-Fails {
        ConvertFrom-JwmUsbCapture -CaptureDirectory $directory -ReaderNumber '654321' -PatrolSessionId 7 `
            -UtcOffset '+00:00' -FromUtc '2020-01-16T10:00:00Z' -BeforeUtc '2020-01-16T11:00:00Z'
    } 'reader'
    Assert-Fails {
        ConvertFrom-JwmUsbCapture -CaptureDirectory $directory -ReaderNumber '123456' -PatrolSessionId 7 `
            -UtcOffset '+14:01' -FromUtc '2020-01-16T10:00:00Z' -BeforeUtc '2020-01-16T11:00:00Z'
    } 'offset'
    Assert-Fails {
        ConvertFrom-JwmUsbCapture -CaptureDirectory $directory -ReaderNumber '123456' -PatrolSessionId 7 `
            -UtcOffset '+00:00' -FromUtc '2020-01-16T10:00:00' -BeforeUtc '2020-01-16T11:00:00Z'
    } 'UTC'
    Write-Output 'PASS: Failed/tampered captures, wrong readers and ambiguous clock inputs are rejected.'

    $handlerSource = @'
using System;
using System.Collections.Generic;
using System.Net;
using System.Net.Http;
using System.Threading;
using System.Threading.Tasks;
public sealed class JwmTestUploadHandler : HttpMessageHandler {
    public readonly List<string> Bodies = new List<string>();
    public readonly List<string> Targets = new List<string>();
    public readonly List<string> Authorizations = new List<string>();
    public readonly Queue<HttpResponseMessage> Responses = new Queue<HttpResponseMessage>();
    protected override Task<HttpResponseMessage> SendAsync(HttpRequestMessage request, CancellationToken token) {
        Bodies.Add(request.Content.ReadAsStringAsync().GetAwaiter().GetResult());
        Targets.Add(request.RequestUri.AbsoluteUri);
        Authorizations.Add(request.Headers.Authorization.ToString());
        return Task.FromResult(Responses.Dequeue());
    }
}
'@
    if ($PSVersionTable.PSVersion.Major -ge 6) {
        Add-Type -TypeDefinition $handlerSource -CompilerOptions '/langversion:5'
    } else {
        Add-Type -TypeDefinition $handlerSource -ReferencedAssemblies System.Net.Http
    }
    $token = ConvertTo-SecureString 'synthetic-only-token' -AsPlainText -Force
    $handler = New-Object JwmTestUploadHandler
    $client = [System.Net.Http.HttpClient]::new($handler)
    try {
        foreach ($index in 0..1) {
            $response = [System.Net.Http.HttpResponseMessage]::new([System.Net.HttpStatusCode] $(if ($index -eq 0) {201} else {200}))
            $body = @{ data = @{ event_id = $batch.Events[$index].Payload.event_id; jwm_device_id = 5 }; replayed = ($index -eq 1) }
            $response.Content = [System.Net.Http.StringContent]::new(($body | ConvertTo-Json))
            $handler.Responses.Enqueue($response)
        }
        $sent = Send-JwmUsbEvents -Batch $batch -ApiBaseUrl 'https://guardops.example/api/v1' -DeviceId 5 -Token $token -HttpClient $client
        Assert-Equal $sent.Created 1
        Assert-Equal $sent.Replayed 1
        Assert-Equal $handler.Bodies.Count 2
        $payload = $handler.Bodies[0] | ConvertFrom-Json
        Assert-Equal $payload.guard_external_id '0000000042'
        Assert-Equal $handler.Targets[0] 'https://guardops.example/api/v1/jwm/devices/5/events'
        Assert-Equal $handler.Authorizations[0] 'Bearer synthetic-only-token'
        Assert-Equal $client.DefaultRequestHeaders.Contains('Authorization') $false
        Assert-Fails {
            Send-JwmUsbEvents -Batch $batch -ApiBaseUrl 'http://guardops.example/api/v1' -DeviceId 5 -Token $token -HttpClient $client
        } 'HTTPS'
        Assert-Equal $handler.Bodies.Count 2
        Assert-Fails {
            Send-JwmUsbEvents -Batch $batch -ApiBaseUrl 'http://127.0.0.1:8080/api/v1' -DeviceId 5 -Token $token -HttpClient $client
        } 'HTTPS'
        Assert-Fails {
            Send-JwmUsbEvents -Batch $batch -ApiBaseUrl 'http://guardops.example/api/v1' -DeviceId 5 -Token $token -HttpClient $client -AllowLoopbackHttp
        } 'HTTPS'
        Write-Output 'PASS: Exact API payloads acknowledge created/replayed results; HTTP is refused before sending credentials.'

        foreach ($status in @(302, 401, 403, 409, 422, 429, 500)) {
            $handler.Responses.Enqueue([System.Net.Http.HttpResponseMessage]::new([System.Net.HttpStatusCode] $status))
            $before = $handler.Bodies.Count
            Assert-Fails {
                Send-JwmUsbEvents -Batch $batch -ApiBaseUrl 'https://guardops.example/api/v1' -DeviceId 5 -Token $token -HttpClient $client
            } "HTTP $status"
            Assert-Equal $handler.Bodies.Count ($before + 1)
        }
        $response = [System.Net.Http.HttpResponseMessage]::new([System.Net.HttpStatusCode]::OK)
        $response.Content = [System.Net.Http.StringContent]::new('{"data":{"event_id":"wrong","jwm_device_id":5},"replayed":false}')
        $handler.Responses.Enqueue($response)
        Assert-Fails {
            Send-JwmUsbEvents -Batch $batch -ApiBaseUrl 'https://guardops.example/api/v1' -DeviceId 5 -Token $token -HttpClient $client
        } 'acknowledg'
        foreach ($invalidBody in @('null', '{SYNTHETIC-PRIVATE-RESPONSE')) {
            $response = [System.Net.Http.HttpResponseMessage]::new([System.Net.HttpStatusCode]::Created)
            $response.Content = [System.Net.Http.StringContent]::new($invalidBody)
            $handler.Responses.Enqueue($response)
            Assert-Fails {
                Send-JwmUsbEvents -Batch $batch -ApiBaseUrl 'https://guardops.example/api/v1' -DeviceId 5 -Token $token -HttpClient $client
            } '^Invalid API acknowledgment'
        }
        $single = [pscustomobject]@{ Events = @($batch.Events[0]); ExcludedRows = 0 }
        $response = [System.Net.Http.HttpResponseMessage]::new([System.Net.HttpStatusCode]::Created)
        $response.Content = [System.Net.Http.StringContent]::new((@{
            data = @{ event_id = $single.Events[0].Payload.event_id; jwm_device_id = 5 }; replayed = $false
        } | ConvertTo-Json))
        $handler.Responses.Enqueue($response)
        $local = Send-JwmUsbEvents -Batch $single -ApiBaseUrl 'http://127.0.0.1:8080/api/v1' `
            -DeviceId 5 -Token $token -HttpClient $client -AllowLoopbackHttp
        Assert-Equal $local.Created 1
        Assert-Equal $handler.Targets[$handler.Targets.Count - 1] 'http://127.0.0.1:8080/api/v1/jwm/devices/5/events'
        $response = [System.Net.Http.HttpResponseMessage]::new([System.Net.HttpStatusCode]::Created)
        $response.Content = [System.Net.Http.StringContent]::new((@{
            data = @{ event_id = $batch.Events[0].Payload.event_id; jwm_device_id = 5 }; replayed = $false
        } | ConvertTo-Json))
        $handler.Responses.Enqueue($response)
        $handler.Responses.Enqueue([System.Net.Http.HttpResponseMessage]::new([System.Net.HttpStatusCode]::UnprocessableEntity))
        $before = $handler.Bodies.Count
        Assert-Fails {
            Send-JwmUsbEvents -Batch $batch -ApiBaseUrl 'https://guardops.example/api/v1' -DeviceId 5 -Token $token -HttpClient $client
        } 'source line 2 \(HTTP 422\)'
        Assert-Equal $handler.Bodies.Count ($before + 2)
        Write-Output 'PASS: Redirects, authorization/validation/rate-limit failures and invalid acknowledgments stop without retries.'
    } finally { $client.Dispose(); $token.Dispose() }
    Write-Output 'All USB upload tests passed. No network request, vendor DLL or physical reader was used.'
} finally {
    if ([System.IO.File]::ReadAllText($marker) -cne $owner) { throw "Refusing cleanup of unowned directory: $root" }
    Remove-Item -LiteralPath $root -Recurse -Force
}
