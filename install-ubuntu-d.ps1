$ErrorActionPreference = 'Stop'
$base = 'D:\WSL'
$installDir = Join-Path $base 'Ubuntu'
$rootfs = Join-Path $base 'ubuntu-noble-wsl-amd64-24.04lts.rootfs.tar.gz'
$logPath = Join-Path $base 'ubuntu-install.log'
$url = 'https://cloud-images.ubuntu.com/wsl/releases/24.04/current/ubuntu-noble-wsl-amd64-24.04lts.rootfs.tar.gz'

New-Item -ItemType Directory -Path $base -Force | Out-Null
Start-Transcript -Path $logPath -Force
try {
    Write-Output "Downloading official Ubuntu rootfs: $url"
    if (-not (Test-Path -LiteralPath $rootfs)) {
        Invoke-WebRequest -Uri $url -OutFile $rootfs -UseBasicParsing
    }
    Write-Output "Rootfs size: $([math]::Round((Get-Item -LiteralPath $rootfs).Length / 1GB, 2)) GB"

    if (Test-Path -LiteralPath $installDir) {
        throw "Target directory already exists: $installDir"
    }

    Write-Output "Importing Ubuntu as WSL 2 distribution into $installDir"
    wsl.exe --import Ubuntu $installDir $rootfs --version 2
    if ($LASTEXITCODE -ne 0) {
        throw "wsl --import failed with exit code $LASTEXITCODE"
    }

    Write-Output 'Registered distributions:'
    wsl.exe --list --verbose
}
finally {
    Stop-Transcript
}
