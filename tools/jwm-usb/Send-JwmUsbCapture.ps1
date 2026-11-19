#requires -Version 5.1
[CmdletBinding()]
param(
    [Parameter(Mandatory)] [string] $CaptureDirectory,
    [Parameter(Mandatory)] [string] $ReaderNumber,
    [Parameter(Mandatory)] [int] $PatrolSessionId,
    [Parameter(Mandatory)] [string] $UtcOffset,
    [Parameter(Mandatory)] [string] $FromUtc,
    [Parameter(Mandatory)] [string] $BeforeUtc,
    [uri] $ApiBaseUrl,
    [int] $DeviceId,
    [switch] $AllowLoopbackHttp,
    [switch] $Upload
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
Import-Module (Join-Path $PSScriptRoot 'JwmUsbUpload.psm1') -Force

$uploadAttempted = $false
try {
    $batch = ConvertFrom-JwmUsbCapture -CaptureDirectory $CaptureDirectory -ReaderNumber $ReaderNumber `
        -PatrolSessionId $PatrolSessionId -UtcOffset $UtcOffset -FromUtc $FromUtc -BeforeUtc $BeforeUtc
    Write-Output "Validated tag scans: $($batch.Events.Count). Rows outside selected UTC window: $($batch.ExcludedRows)."
    Write-Output "Blank separator lines ignored: $($batch.BlankLines)."
    Write-Output 'GPS, image and biometric data are not uploaded by this tag-only adapter.'
    if (-not $Upload) {
        Write-Output 'Preview only. No network request or device command was performed. Add -Upload with your HTTPS API URL and app device ID to send.'
        exit 0
    }
    if ($null -eq $ApiBaseUrl -or $DeviceId -le 0) { throw 'Upload requires -ApiBaseUrl and a positive application -DeviceId.' }
    Write-Output "Destination: $ApiBaseUrl; app device ID: $DeviceId; expected physical reader: $ReaderNumber."
    $token = Read-Host 'Device-bound bridge token (hidden; never use your admin token)' -AsSecureString
    try {
        $uploadAttempted = $true
        $result = Send-JwmUsbEvents -Batch $batch -ApiBaseUrl $ApiBaseUrl -DeviceId $DeviceId -Token $token `
            -AllowLoopbackHttp:$AllowLoopbackHttp
        Write-Output "Upload acknowledged. New events: $($result.Created). Replayed events: $($result.Replayed)."
    } finally { $token.Dispose() }
    exit 0
} catch {
    $failure = 'Capture validation failed. No upload was attempted. '
    if ($uploadAttempted) {
        $failure = 'Capture upload failed. Some rows may already be saved; retain the capture and retry with identical arguments after review. '
    }
    Write-Error -Message ($failure + $_.Exception.Message) -ErrorAction Continue
    exit 1
}
