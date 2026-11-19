#requires -Version 5.1
[CmdletBinding()]
param(
    [Parameter(Mandatory)] [ValidateNotNullOrEmpty()] [string] $SdkDllPath,
    [string] $OutputRoot,
    [ValidateSet(0, 1)] [int] $VerificationValue = 1
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
Import-Module (Join-Path $PSScriptRoot 'JwmUsb.psm1') -Force

try {
    $capture = Read-JwmUsbDevice -SdkDllPath $SdkDllPath -OutputRoot $OutputRoot -VerificationValue $VerificationValue
    Write-Output "USB export captured locally: $($capture.ExportPath)"
    Write-Output "Diagnostic manifest: $($capture.ManifestPath)"
    Write-Output 'Records are uninterpreted. No API upload, deletion or configuration command was performed.'
    exit 0
} catch {
    Write-Error -Message $_.Exception.Message -ErrorAction Continue
    exit 1
}
