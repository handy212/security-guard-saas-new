#requires -Version 5.1
Set-StrictMode -Version Latest

function Invoke-JwmUsbCapture {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory)] [scriptblock] $DeviceFactory,
        [Parameter(Mandatory)] [ValidateNotNullOrEmpty()] [string] $OutputRoot,
        [ValidateSet(0, 1)] [int] $VerificationValue = 1,
        [string] $SdkDllPath
    )

    $ErrorActionPreference = 'Stop'
    $root = [System.IO.Path]::GetFullPath($OutputRoot)
    if ($root -match '[^\x20-\x7e]') {
        throw 'The legacy SDK needs an ASCII output path. Choose a private ASCII-only -OutputRoot.'
    }
    if ([System.IO.File]::Exists($root)) {
        throw 'OutputRoot must be a directory, not an existing file.'
    }
    $stamp = [datetime]::UtcNow.ToString('yyyyMMddTHHmmssfffZ', [System.Globalization.CultureInfo]::InvariantCulture)
    $directory = Join-Path $root ("capture-$stamp-" + [guid]::NewGuid().ToString('N'))
    $recordsPath = Join-Path $directory 'records.txt'
    $manifestPath = Join-Path $directory 'manifest.json'
    if ($recordsPath.Length -ge 260 -or $manifestPath.Length -ge 260) {
        throw 'Output path is too long for the legacy SDK. Choose a shorter -OutputRoot.'
    }
    $null = [System.IO.Directory]::CreateDirectory($root)
    $null = New-Item -ItemType Directory -Path $directory -ErrorAction Stop
    $encoding = [System.Text.UTF8Encoding]::new($false)
    $manifest = [ordered]@{
        schema_version = 1
        status = 'in_progress'
        captured_at_utc = [datetime]::UtcNow.ToString('o')
        device_id = $null
        verification_value = $VerificationValue
        sdk_sha256 = $null
        operation = 'load-sdk'
        native_codes = [ordered]@{
            OpenDevice = $null; GetDeviceId = $null; Verify = $null; GetRecords = $null; CloseDevice = $null
        }
        export = [ordered]@{ path = 'records.txt'; bytes = $null; sha256 = $null }
        errors = [System.Collections.Generic.List[string]]::new()
    }
    [System.IO.File]::WriteAllText($manifestPath, ($manifest | ConvertTo-Json -Depth 5), $encoding)
    $device = $null
    $opened = $false
    $closed = $false
    $disposed = $false
    $captured = $false

    try {
        if (-not [string]::IsNullOrWhiteSpace($SdkDllPath)) {
            $manifest.sdk_sha256 = (Get-FileHash -LiteralPath $SdkDllPath -Algorithm SHA256 -ErrorAction Stop).Hash
        }
        $device = & $DeviceFactory
        # Defer native status errors until cleanup so both export and close failures survive.
        do {
            $manifest.operation = 'OpenDevice'
            $code = $device.OpenDevice()
            $manifest.native_codes.OpenDevice = $code
            if ($code -lt 0) {
                $manifest.errors.Add("OpenDevice failed (code $code). Connect one reader, check its USB driver and close other reader software.")
                break
            }
            $opened = $true
            $manifest.operation = 'GetDeviceId'
            $code = $device.GetDeviceId()
            $manifest.native_codes.GetDeviceId = $code
            if ($code -lt 0) {
                $manifest.errors.Add("GetDeviceId failed (code $code).")
                break
            }
            $manifest.device_id = $code
            $manifest.operation = 'Verify'
            $code = $device.Verify($VerificationValue)
            $manifest.native_codes.Verify = $code
            if ($code -lt 0) {
                $manifest.errors.Add("Verify($VerificationValue) failed (code $code). The supplied workbooks disagree on this argument; confirm the correct value with the vendor.")
                break
            }
            $manifest.operation = 'GetRecords'
            $code = $device.GetRecords($recordsPath, 0)
            $manifest.native_codes.GetRecords = $code
            if ($code -lt 0) {
                $manifest.errors.Add("GetRecords failed (code $code). Preserve any partial export for local investigation.")
                break
            }
            $manifest.operation = 'verify-export'
            if (-not [System.IO.File]::Exists($recordsPath) -or (Get-Item -LiteralPath $recordsPath).Length -eq 0) {
                $manifest.errors.Add('GetRecords produced no populated export. No records may be present, or the SDK export may have failed.')
                break
            }
            $captured = $true
        } while ($false)
    } finally {
        try {
            if ($opened) {
                $code = $device.CloseDevice()
                $manifest.native_codes.CloseDevice = $code
                $closed = $code -ge 0
                if (-not $closed) {
                    $manifest.errors.Add("CloseDevice failed (code $code). Close other reader software and reconnect before retrying.")
                }
            }
        } finally {
            try {
                if ($null -ne $device) {
                    $device.Dispose()
                    $disposed = $true
                }
            } finally {
                $manifest.status = 'failed'
                try {
                    if ([System.IO.File]::Exists($recordsPath)) {
                        $manifest.export.bytes = (Get-Item -LiteralPath $recordsPath).Length
                        $manifest.export.sha256 = (Get-FileHash -LiteralPath $recordsPath -Algorithm SHA256 -ErrorAction Stop).Hash
                    }
                    if ($captured -and $closed -and $disposed -and $manifest.export.bytes -gt 0) {
                        $manifest.status = 'captured'
                    } elseif ($manifest.errors.Count -eq 0) {
                        $manifest.errors.Add("Capture interrupted during $($manifest.operation), cleanup or final export verification. See the command error; retain any partial export.")
                    }
                } finally {
                    [System.IO.File]::WriteAllText($manifestPath, ($manifest | ConvertTo-Json -Depth 5), $encoding)
                }
            }
        }
    }

    if ($manifest.status -ne 'captured') {
        throw "USB capture failed. $($manifest.errors -join ' ') Diagnostics: $manifestPath"
    }
    [pscustomobject]@{ Status = 'captured'; ExportPath = $recordsPath; ManifestPath = $manifestPath }
}

function Read-JwmUsbDevice {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory)] [ValidateNotNullOrEmpty()] [string] $SdkDllPath,
        [string] $OutputRoot,
        [ValidateSet(0, 1)] [int] $VerificationValue = 1
    )

    $ErrorActionPreference = 'Stop'
    if ([System.Environment]::OSVersion.Platform -ne [System.PlatformID]::Win32NT -or [System.IntPtr]::Size -ne 8) {
        throw 'Use 64-bit Windows PowerShell, not a Linux host or 32-bit process.'
    }
    if ($SdkDllPath -notmatch '^[a-zA-Z]:[\\/]' -or [System.IO.Path]::GetFileName($SdkDllPath) -ine 'libJComm.dll') {
        throw 'Supply an absolute local-drive path to your trusted x64 libJComm.dll, not a network share.'
    }
    $resolvedDll = (Get-Item -LiteralPath $SdkDllPath -ErrorAction Stop).FullName
    if (-not [System.IO.File]::Exists($resolvedDll)) {
        throw 'The SDK path must identify the trusted x64 libJComm.dll file.'
    }
    if ([string]::IsNullOrWhiteSpace($OutputRoot)) {
        $OutputRoot = Join-Path ([System.Environment]::GetFolderPath('LocalApplicationData')) 'GuardOps\JwmUsbExports'
    }
    $source = Join-Path $PSScriptRoot 'NativeDevice.cs'
    $factory = {
        if (-not ('GuardOps.JwmUsb.NativeDevice' -as [type])) {
            Add-Type -Path $source -ErrorAction Stop
        }
        New-Object GuardOps.JwmUsb.NativeDevice -ArgumentList $resolvedDll
    }.GetNewClosure()
    Invoke-JwmUsbCapture -DeviceFactory $factory -OutputRoot $OutputRoot -VerificationValue $VerificationValue -SdkDllPath $resolvedDll
}

Export-ModuleMember -Function Read-JwmUsbDevice, Invoke-JwmUsbCapture
