$ErrorActionPreference = 'Stop'
$logPath = Join-Path $PSScriptRoot 'enable-wsl-features.log'
Start-Transcript -Path $logPath -Force
$failed = $false
try {
    Write-Output '=== Enable WSL ==='
    dism.exe /Online /Enable-Feature /FeatureName:Microsoft-Windows-Subsystem-Linux /All /NoRestart
    if ($LASTEXITCODE -ne 0) { $failed = $true }

    Write-Output '=== Enable Virtual Machine Platform ==='
    dism.exe /Online /Enable-Feature /FeatureName:VirtualMachinePlatform /All /NoRestart
    if ($LASTEXITCODE -ne 0) { $failed = $true }
}
catch {
    Write-Error $_
    $failed = $true
}
finally {
    Stop-Transcript
}
if ($failed) { exit 1 }
exit 0
