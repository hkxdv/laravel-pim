if (-not (Test-Path -Path ./database)) {
    New-Item -ItemType Directory -Path ./database | Out-Null 
}; 

if (-not (Test-Path -Path ./database/database.sqlite)) {
    New-Item -ItemType File -Path ./database/database.sqlite | Out-Null 
};

Write-Host 'SQLite DB file ensured at ./database/database.sqlite'
