#requires -Version 5.1
[CmdletBinding()]
param(
    [ValidateNotNullOrEmpty()] [string] $OutputRoot = [System.IO.Path]::GetTempPath()
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
Import-Module (Join-Path $PSScriptRoot 'JwmUsb.psm1') -Force

class FakeJwmUsbDevice {
    [System.Collections.Generic.List[string]] $Calls = [System.Collections.Generic.List[string]]::new()
    [int] $OpenCode = 1
    [int] $IdCode = 123456
    [int] $VerifyCode = 0
    [int] $ExportCode = 0
    [int] $CloseCode = 0
    [bool] $MissingFile = $false
    [bool] $ThrowOnExport = $false
    [bool] $RemoveOnDispose = $false
    [bool] $ThrowOnClose = $false
    [bool] $ThrowOnDispose = $false
    [string] $ExportPath
    [byte[]] $Bytes = @(0, 9, 10, 13, 65, 255)

    [int] OpenDevice() {
        $this.Calls.Add('OpenDevice')
        return $this.OpenCode
    }
    [int] GetDeviceId() {
        $this.Calls.Add('GetDeviceId')
        return $this.IdCode
    }
    [int] Verify([int] $value) {
        $this.Calls.Add("Verify:$value")
        return $this.VerifyCode
    }
    [int] GetRecords([string] $path, [int] $encryption) {
        $this.Calls.Add("GetRecords:$encryption")
        $this.ExportPath = $path
        if (-not $this.MissingFile) {
            [System.IO.File]::WriteAllBytes($path, $this.Bytes)
        }
        if ($this.ThrowOnExport) {
            throw [System.IO.IOException]::new('Synthetic export failure.')
        }
        return $this.ExportCode
    }
    [int] CloseDevice() {
        $this.Calls.Add('CloseDevice')
        if ($this.ThrowOnClose) {
            throw [System.IO.IOException]::new('Synthetic close failure.')
        }
        return $this.CloseCode
    }
    [void] Dispose() {
        $this.Calls.Add('Dispose')
        if ($this.RemoveOnDispose) {
            [System.IO.File]::Delete($this.ExportPath)
        }
        if ($this.ThrowOnDispose) {
            throw [System.IO.IOException]::new('Synthetic disposal failure.')
        }
    }
}

function Assert-Equal($Actual, $Expected) {
    if ($Actual -cne $Expected) {
        throw "Expected [$Expected], got [$Actual]."
    }
}

function Assert-Fails([scriptblock] $Action, [string] $Pattern) {
    try {
        & $Action
    } catch {
        if ($_.Exception.Message -notmatch $Pattern) { throw }
        return
    }
    throw "Expected a failure matching [$Pattern]."
}

function Capture([FakeJwmUsbDevice] $Device, [string] $Root) {
    $factory = { $Device }.GetNewClosure()
    Invoke-JwmUsbCapture -DeviceFactory $factory -OutputRoot $Root
}

function Read-Manifest([string] $Root) {
    $path = Get-ChildItem -LiteralPath $Root -Filter 'manifest.json' -Recurse -File
    Get-Content -LiteralPath $path.FullName -Raw | ConvertFrom-Json
}

$testRoot = Join-Path ([System.IO.Path]::GetFullPath($OutputRoot)) ('jwm-usb-tests-' + [guid]::NewGuid().ToString('N'))
if ($testRoot -match '[^\x20-\x7e]') {
    throw 'Choose a private ASCII-only test directory with -OutputRoot.'
}
$null = New-Item -ItemType Directory -Path $testRoot
$owner = [guid]::NewGuid().ToString('N')
$marker = Join-Path $testRoot '.test-owner'
[System.IO.File]::WriteAllText($marker, $owner)

try {
    $parseErrors = @()
    foreach ($file in Get-ChildItem -LiteralPath $PSScriptRoot -File | Where-Object { $_.Extension -in '.ps1', '.psm1' }) {
        $tokens = $null
        $errors = $null
        $null = [System.Management.Automation.Language.Parser]::ParseFile($file.FullName, [ref] $tokens, [ref] $errors)
        $parseErrors += $errors
    }
    Assert-Equal $parseErrors.Count 0
    $nativeSource = Join-Path $PSScriptRoot 'NativeDevice.cs'
    if ($PSVersionTable.PSVersion.Major -ge 6) {
        Add-Type -Path $nativeSource -CompilerOptions '/langversion:5'
    } else {
        Add-Type -Path $nativeSource
    }
    Write-Output 'PASS: PowerShell syntax and C# 5-compatible interop compilation.'

    $device = [FakeJwmUsbDevice]::new()
    $root = Join-Path $testRoot 'success'
    $result = Capture $device $root
    Assert-Equal $result.Status 'captured'
    Assert-Equal ($device.Calls -join ',') 'OpenDevice,GetDeviceId,Verify:1,GetRecords:0,CloseDevice,Dispose'
    Assert-Equal (([System.IO.File]::ReadAllBytes($result.ExportPath)) -join ',') ($device.Bytes -join ',')
    $manifest = Read-Manifest $root
    Assert-Equal $manifest.device_id 123456
    Assert-Equal $manifest.export.bytes $device.Bytes.Length
    Assert-Equal $manifest.export.sha256 (Get-FileHash -LiteralPath $result.ExportPath -Algorithm SHA256).Hash
    $second = Capture ([FakeJwmUsbDevice]::new()) $root
    Assert-Equal ($second.ExportPath -ne $result.ExportPath) $true
    Assert-Equal ([System.IO.File]::Exists($result.ExportPath)) $true
    Write-Output 'PASS: Capture preserves exact bytes and repeated runs do not overwrite.'

    foreach ($operation in 'Open', 'Id', 'Verify', 'Export', 'Close') {
        $device = [FakeJwmUsbDevice]::new()
        $device.($operation + 'Code') = -4098
        $root = Join-Path $testRoot $operation
        Assert-Fails { Capture $device $root } 'failed'
        $manifest = Read-Manifest $root
        Assert-Equal $manifest.status 'failed'
        Assert-Equal $device.Calls.Contains('Dispose') $true
        Assert-Equal $device.Calls.Contains('CloseDevice') ($operation -ne 'Open')
        if ($operation -in 'Id', 'Verify') {
            Assert-Equal $device.Calls.Contains('GetRecords:0') $false
        }
        if ($operation -eq 'Export') {
            Assert-Equal $manifest.export.bytes $device.Bytes.Length
            Assert-Equal @(Get-ChildItem -LiteralPath $root -Filter 'records.txt' -Recurse -File).Count 1
        }
    }
    Write-Output 'PASS: Native errors fail explicitly, preserve partial export and close only opened devices.'

    $device = [FakeJwmUsbDevice]::new()
    $device.OpenCode = 0
    $root = Join-Path $testRoot 'zero-open-success'
    $result = Capture $device $root
    Assert-Equal $result.Status 'captured'
    Assert-Equal ($device.Calls -join ',') 'OpenDevice,GetDeviceId,Verify:1,GetRecords:0,CloseDevice,Dispose'
    Assert-Equal (Read-Manifest $root).native_codes.OpenDevice 0
    Write-Output 'PASS: Zero is a successful communication result, not a record count or open failure.'

    $device = [FakeJwmUsbDevice]::new()
    $root = Join-Path $testRoot 'explicit-verification'
    $factory = { $device }.GetNewClosure()
    $null = Invoke-JwmUsbCapture -DeviceFactory $factory -OutputRoot $root -VerificationValue 0
    Assert-Equal ($device.Calls -join ',') 'OpenDevice,GetDeviceId,Verify:0,GetRecords:0,CloseDevice,Dispose'
    Assert-Equal (Read-Manifest $root).verification_value 0
    $device.Calls.Clear()
    Assert-Fails {
        Invoke-JwmUsbCapture -DeviceFactory $factory -OutputRoot $root -VerificationValue 2
    } 'ValidateSet|validation|invalid|does not belong'
    Assert-Equal $device.Calls.Count 0
    $device.VerifyCode = -4118
    $root = Join-Path $testRoot 'explicit-verification-failure'
    Assert-Fails {
        Invoke-JwmUsbCapture -DeviceFactory $factory -OutputRoot $root -VerificationValue 0
    } 'Verify\(0\).*failed'
    Assert-Equal ($device.Calls -join ',') 'OpenDevice,GetDeviceId,Verify:0,CloseDevice,Dispose'
    Write-Output 'PASS: Verification mode is explicit, validated and recorded without automatic retries.'

    $sdkFixture = Join-Path $testRoot 'synthetic-sdk.bin'
    [System.IO.File]::WriteAllText($sdkFixture, 'SYNTHETIC SDK IDENTITY ONLY')
    $device = [FakeJwmUsbDevice]::new()
    $root = Join-Path $testRoot 'sdk-identity'
    $factory = { $device }.GetNewClosure()
    $null = Invoke-JwmUsbCapture -DeviceFactory $factory -OutputRoot $root -SdkDllPath $sdkFixture
    Assert-Equal (Read-Manifest $root).sdk_sha256 (Get-FileHash -LiteralPath $sdkFixture -Algorithm SHA256).Hash
    $device.Calls.Clear()
    $root = Join-Path $testRoot 'sdk-unreadable'
    Assert-Fails {
        Invoke-JwmUsbCapture -DeviceFactory $factory -OutputRoot $root -SdkDllPath (Join-Path $testRoot 'missing-sdk.bin')
    } 'not exist|Cannot find|Could not find'
    Assert-Equal $device.Calls.Count 0
    Assert-Equal (Read-Manifest $root).status 'failed'
    Write-Output 'PASS: SDK identity is recorded; unreadable SDK files fail before device access.'

    foreach ($mode in 'missing', 'empty', 'exception') {
        $device = [FakeJwmUsbDevice]::new()
        if ($mode -eq 'missing') { $device.MissingFile = $true }
        if ($mode -eq 'empty') { $device.Bytes = @() }
        if ($mode -eq 'exception') { $device.ThrowOnExport = $true }
        $root = Join-Path $testRoot $mode
        Assert-Fails { Capture $device $root } 'failed|Synthetic export failure'
        Assert-Equal (Read-Manifest $root).status 'failed'
        Assert-Equal $device.Calls.Contains('CloseDevice') $true
        Assert-Equal $device.Calls.Contains('Dispose') $true
    }
    Write-Output 'PASS: Missing, empty and exceptional exports cannot report success.'

    foreach ($mode in 'RemoveOnDispose', 'ThrowOnClose', 'ThrowOnDispose') {
        $device = [FakeJwmUsbDevice]::new()
        $device.$mode = $true
        $root = Join-Path $testRoot $mode
        Assert-Fails { Capture $device $root } 'failed|Synthetic .+ failure'
        Assert-Equal (Read-Manifest $root).status 'failed'
        Assert-Equal $device.Calls.Contains('Dispose') $true
    }
    Write-Output 'PASS: Cleanup exceptions and disappearing exports cannot leave successful manifests.'

    $device = [FakeJwmUsbDevice]::new()
    $device.ExportCode = -4098
    $device.CloseCode = -4099
    $root = Join-Path $testRoot 'multiple-errors'
    Assert-Fails { Capture $device $root } 'GetRecords.*-4098.*CloseDevice.*-4099'
    Assert-Equal (Read-Manifest $root).errors.Count 2
    Write-Output 'PASS: Cleanup failure does not hide an export return-code failure.'

    $device = [FakeJwmUsbDevice]::new()
    $invalidRoot = Join-Path $testRoot 'not-a-directory'
    [System.IO.File]::WriteAllText($invalidRoot, 'SYNTHETIC')
    Assert-Fails { Capture $device $invalidRoot } 'directory'
    $unicodeRoot = Join-Path $testRoot ([string][char]0x00e9)
    Assert-Fails { Capture $device $unicodeRoot } 'ASCII'
    Assert-Fails { Capture $device (Join-Path $testRoot ('x' * 270)) } 'too long'
    Assert-Equal $device.Calls.Count 0
    Write-Output 'PASS: Unsafe output paths fail before any device call.'

    $root = Join-Path $testRoot 'load-error'
    Assert-Fails {
        Invoke-JwmUsbCapture -OutputRoot $root -DeviceFactory {
            throw [System.ComponentModel.Win32Exception]::new(126, 'Synthetic loader failure.')
        }
    } 'Synthetic loader failure'
    Assert-Equal (Read-Manifest $root).status 'failed'
    Write-Output 'PASS: Loader errors are preserved and receive a failed diagnostic manifest.'
    Write-Output 'All USB proof tests passed. No vendor library was loaded or device accessed.'
} finally {
    if ([System.IO.File]::ReadAllText($marker) -cne $owner) {
        throw "Refusing cleanup of unowned test directory: $testRoot"
    }
    Remove-Item -LiteralPath $testRoot -Recurse -Force
}
