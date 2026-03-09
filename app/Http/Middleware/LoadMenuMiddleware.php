<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\PermissionController;

class LoadMenuMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Obtener permisos del usuario
            $userPermissions = [];
            $userRoles = $user->roles;
            $isAdmin = false;
            
            foreach ($userRoles as $role) {
                if ($role->id == 1) {
                    $isAdmin = true; // Usuario administrador
                }
                foreach ($role->permissions as $permission) {
                    $userPermissions[] = $permission->name;
                }
            }
            
            // Cargar el menú vertical desde el archivo JSON
            $verticalMenuJson = file_get_contents(base_path('resources/menu/verticalMenu.json'));
            // Decodificar como array para poder filtrarlo
            $verticalMenuArray = json_decode($verticalMenuJson, true);
            
            // Validar que el JSON se decodificó correctamente
            if (json_last_error() !== JSON_ERROR_NONE || !isset($verticalMenuArray['menu'])) {
                // Si hay error, crear un menú vacío
                $verticalMenuArray = ['menu' => []];
            }
            
            // Filtrar el menú según los permisos del usuario (si no es admin)
            if (!$isAdmin && isset($verticalMenuArray['menu'])) {
                $verticalMenuArray['menu'] = $this->filterMenuByPermissions($verticalMenuArray['menu'], $userPermissions);
            }
            
            // Convertir de vuelta a objeto para mantener compatibilidad
            $verticalMenuData = json_decode(json_encode($verticalMenuArray));
            
            // Asegurar que siempre sea un objeto válido
            if (!$verticalMenuData || !isset($verticalMenuData->menu)) {
                $verticalMenuData = (object)['menu' => []];
            }

            // Cargar el menú horizontal desde el archivo JSON
            $horizontalMenuJson = file_get_contents(base_path('resources/menu/horizontalMenu.json'));
            // Decodificar como objeto para mantener consistencia
            $horizontalMenuData = json_decode($horizontalMenuJson);
            
            // Asegurar que siempre sea un objeto válido
            if (!$horizontalMenuData) {
                $horizontalMenuData = (object)['menu' => []];
            }

            // Compartir los datos del menú con todas las vistas
            // $menuData[0] será el menú vertical, $menuData[1] será el horizontal
            \View::share('menuData', [$verticalMenuData, $horizontalMenuData]);
        } else {
            // Si el usuario no está autenticado, compartir menús vacíos
            \View::share('menuData', [
                (object)['menu' => []],
                (object)['menu' => []]
            ]);
        }

        return $next($request);
    }
    
    /**
     * Filtrar el menú según los permisos del usuario
     */
    private function filterMenuByPermissions($menuItems, $userPermissions)
    {
        $filteredMenu = [];
        
        foreach ($menuItems as $menuItem) {
            $shouldInclude = false;
            
            // Si el item tiene un slug, verificar permisos
            if (isset($menuItem['slug'])) {
                // Permitir siempre el dashboard general (centro de control)
                if ($menuItem['slug'] === 'dashboard') {
                    $shouldInclude = true;
                }
                // Si el slug coincide con algún permiso del usuario
                elseif (in_array($menuItem['slug'], $userPermissions)) {
                    $shouldInclude = true;
                }
                // Verificar si algún permiso del usuario coincide con el prefijo del slug
                else {
                    $slugPrefix = explode('.', $menuItem['slug'])[0];
                    foreach ($userPermissions as $perm) {
                        if (strpos($perm, $slugPrefix) === 0) {
                            $shouldInclude = true;
                            break;
                        }
                    }
                }
            } else {
                // Si no tiene slug, incluirlo siempre
                $shouldInclude = true;
            }
            
            // Si tiene submenú, filtrarlo también
            if (isset($menuItem['submenu']) && is_array($menuItem['submenu'])) {
                $filteredSubmenu = $this->filterMenuByPermissions($menuItem['submenu'], $userPermissions);
                if (!empty($filteredSubmenu)) {
                    $menuItem['submenu'] = $filteredSubmenu;
                    $shouldInclude = true; // Incluir si tiene submenús válidos
                } else {
                    $shouldInclude = false; // No incluir si no tiene submenús válidos
                }
            }
            
            // Si se debe incluir, agregarlo al menú filtrado
            if ($shouldInclude) {
                $filteredMenu[] = $menuItem;
            }
        }
        
        return $filteredMenu;
    }
}
