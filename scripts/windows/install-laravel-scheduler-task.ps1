param(
    [string]$TaskName = 'ColdKeyGMI Laravel Scheduler',
    [string]$RunnerPath = "$PSScriptRoot\run-laravel-scheduler.bat"
)

$runnerFullPath = [System.IO.Path]::GetFullPath($RunnerPath)

if (-not (Test-Path -LiteralPath $runnerFullPath)) {
    throw "Runner script not found: $runnerFullPath"
}

$taskQuery = schtasks.exe /Query /TN $TaskName 2>$null
if ($LASTEXITCODE -eq 0) {
    schtasks.exe /Delete /TN $TaskName /F | Out-Null
}

$startTime = (Get-Date).AddMinutes(1).ToString('HH:mm')
$taskCommand = '"' + $runnerFullPath + '"'

schtasks.exe /Create `
    /SC MINUTE `
    /MO 1 `
    /TN $TaskName `
    /TR $taskCommand `
    /ST $startTime `
    /F

if ($LASTEXITCODE -ne 0) {
    throw "Failed to create scheduled task [$TaskName]. Run PowerShell as Administrator if Windows blocks task creation."
}

Write-Host "Scheduled task created: $TaskName"
Write-Host "Runner: $runnerFullPath"
