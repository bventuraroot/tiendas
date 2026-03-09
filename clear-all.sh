#!/bin/bash

echo "🧹 Limpiando todas las cachés de Laravel..."

php artisan optimize:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan cache:clear
php artisan event:clear

echo "✅ Todas las cachés limpiadas!"
echo "🌐 Ahora limpia el caché del navegador con Ctrl+Shift+R"
