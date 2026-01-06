# Script para configurar timeout de FastCGI en IIS
# Ejecutar como Administrador

Import-Module WebAdministration

# Configurar timeout de FastCGI a 300 segundos (5 minutos)
Set-WebConfigurationProperty -pspath 'MACHINE/WEBROOT/APPHOST' -filter "system.webServer/fastCgi/application[@fullPath='C:\Program Files\PHP\php-cgi.exe']" -name "activityTimeout" -value "300"
Set-WebConfigurationProperty -pspath 'MACHINE/WEBROOT/APPHOST' -filter "system.webServer/fastCgi/application[@fullPath='C:\Program Files\PHP\php-cgi.exe']" -name "requestTimeout" -value "300"

# Si usas PHP instalado en otra ubicación, ajusta la ruta arriba

Write-Host "Configuración de timeout de FastCGI actualizada a 300 segundos" -ForegroundColor Green
Write-Host "Reiniciando IIS..." -ForegroundColor Yellow

# Reiniciar IIS para aplicar cambios
iisreset

Write-Host "Configuración completada" -ForegroundColor Green
