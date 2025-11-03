Param()

$exampleFile = if (Test-Path ".env.local.example") { ".env.local.example" } elseif (Test-Path ".env.example") { ".env.example" } else { $null }

if (-not $exampleFile) {
  Write-Host "No example env file found." ; exit 0
}

$exKeys = Get-Content $exampleFile | Where-Object { 
  $_ -match '^[A-Z0-9_]+='
} | ForEach-Object { ($_ -split '=', 2)[0] }

$envFile = if (Test-Path ".env.local") { ".env.local" } else { $null }

$actKeys = if ($envFile) { 
  Get-Content $envFile | Where-Object { $_ -match '^[A-Z0-9_]+=' } 
  | ForEach-Object { ($_ -split '=', 2)[0] } 
}
else { @() }

$missing = $exKeys | Where-Object { $_ -notin $actKeys }

if ($missing.Count -gt 0) {
  Write-Host "Missing env keys:"
  $missing | ForEach-Object { Write-Host " - $_" }
  exit 1
}
else {
  Write-Host "Env looks good."
  exit 0
}