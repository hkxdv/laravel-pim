Param(
  [Parameter(Mandatory = $false)] [string]$Model = 'App\Models\Product'
)

Write-Host "[Scout Reindex] Modelo: $Model" -ForegroundColor Cyan

Write-Host "[Scout Reindex] Levantando contenedores (dk:up)..." -ForegroundColor Yellow
try {
  bun run dk:up | Out-Host
}
catch {
  Write-Error "Fallo al iniciar contenedores via 'bun run dk:up'."; exit 1
}

Write-Host "[Scout Reindex] composer dump-autoload -o en backend..." -ForegroundColor Yellow
try {
  bun run dk exec backend composer dump-autoload -o | Out-Host
}
catch {
  Write-Error "Fallo ejecutando 'composer dump-autoload -o'."; exit 1
}

Write-Host "[Scout Reindex] Verificando autoload de $Model..." -ForegroundColor Yellow
$autoloadCheck = bun run dk exec backend php -r "chdir('/var/www/backend'); require 'vendor/autoload.php'; echo class_exists('$Model') ? 'OK' : 'NO';"
if ($autoloadCheck -notmatch 'OK') {
  Write-Error "class_exists('$Model') devolvió NO. Revisa autoload y namespaces."; exit 1
}

Write-Host "[Scout Reindex] scout:flush $Model" -ForegroundColor Yellow
try {
  bun run dk:artisan scout:flush "$Model" | Out-Host
}
catch {
  Write-Error "Fallo en scout:flush para $Model."; exit 1
}

Write-Host "[Scout Reindex] scout:import $Model" -ForegroundColor Yellow
try {
  bun run dk:artisan scout:import "$Model" | Out-Host
}
catch {
  Write-Error "Fallo en scout:import para $Model."; exit 1
}

Write-Host "[Scout Reindex] Verificando Typesense (health y colección products)..." -ForegroundColor Yellow
try {
  bun run dk exec backend sh -lc 'curl -s http://typesense:8108/health' | Out-Host

  $tsList = bun run dk exec backend php -r "chdir('/var/www/backend'); require 'vendor/autoload.php'; $c = new \Typesense\Client(['api_key' => getenv('TYPESENSE_API_KEY'), 'nodes' => [['host' => 'typesense', 'port' => 8108, 'protocol' => 'http']], 'connection_timeout_seconds' => 2]); echo json_encode($c->collections->retrieve(), JSON_PRETTY_PRINT);"
  
  $collections = $null
  try { $collections = $tsList | ConvertFrom-Json } catch {}
  if ($collections) {
    $prod = $collections | Where-Object { $_.name -eq 'products' }
    if ($prod) {
      Write-Host ("[Typesense] products: " + $prod.num_documents + " documentos") -ForegroundColor Green
    }
    else {
      Write-Warning "La colección 'products' no existe aún."
    }
  }
  else {
    Write-Warning "No se pudo parsear respuesta de colecciones Typesense."
  }
}
catch {
  Write-Warning "No se pudo verificar Typesense (health/colecciones)."
}

Write-Host "[Scout Reindex] Completado." -ForegroundColor Green