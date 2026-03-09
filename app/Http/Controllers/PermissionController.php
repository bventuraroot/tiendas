<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.users.permissions.index');
    }

    public function getpermission()
    {
        $permission['data'] = Permission::all();
        foreach($permission['data'] as $index => $value){
                @$permission['data'][$index]['roles']=[];
                $idpermissiondata = @$value->id;
                $roles = "SELECT b.name AS rolename FROM role_has_permissions a
                INNER JOIN roles b ON a.role_id=b.id
                WHERE a.permission_id='$idpermissiondata'
                GROUP BY b.name";
                $result = DB::select(DB::raw($roles));
                foreach($result as $index2 => $value2){
                    @$permission['data'][$index]['roles']=$result;
                }
        }
        return response()->json($permission);
    }

    public function getmenujson(){
        $userId=auth()->user()->id;
        $rolhasuser = "SELECT
                a.model_id UserID,
                b.id Rolid,
                b.name Rol,
                d.id PermisoID,
                d.name Permiso
                FROM model_has_roles a
                INNER JOIN roles b ON a.role_id=b.id
                LEFT JOIN role_has_permissions c ON b.id=c.role_id
                LEFT JOIN permissions d ON c.permission_id=d.id
                WHERE model_id=$userId";
        $result = DB::select(DB::raw($rolhasuser));
        return response()->json($result);
    }

    public function store(Request $request)
    {
        // Implementación del método store si es necesario
        return response()->json(['message' => 'Método no implementado']);
    }

    public function update(Request $request)
    {
        // Implementación del método update si es necesario
        return response()->json(['message' => 'Método no implementado']);
    }

    public function destroy($id)
    {
        try {
            $permission = Permission::findOrFail($id);
            $permission->delete();
            return response()->json(['success' => true, 'message' => 'Permiso eliminado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al eliminar permiso: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Vista para sincronizar permisos desde el navegador
     */
    public function syncPermissionsView()
    {
        return view('admin.users.permissions.sync-permissions');
    }

    /**
     * Sincronizar todos los permisos desde las rutas del sistema
     */
    public function syncAllPermissionsFromRoutes()
    {
        try {
            $routes = \Illuminate\Support\Facades\Route::getRoutes();
            $permissions = [];
            $created = 0;
            $existing = 0;
            $skipped = 0;

            // Rutas a ignorar
            $ignoredRoutes = [
                'login', 'logout', 'register', 'password', 'verification',
                'sanctum', 'ignition', 'profile', 'dashboard',
                'api.', 'generated::', 'debugbar', 'horizon'
            ];

            foreach ($routes as $route) {
                $routeName = $route->getName();
                
                // Saltar si no tiene nombre o está en la lista de ignorados
                if (!$routeName || $this->shouldSkipRoute($routeName, $ignoredRoutes)) {
                    $skipped++;
                    continue;
                }

                // Verificar si el permiso ya existe
                $existingPermission = Permission::where('name', $routeName)->first();

                if (!$existingPermission) {
                    Permission::create([
                        'name' => $routeName,
                        'guard_name' => 'web'
                    ]);
                    $created++;
                    $parts = explode('.', $routeName);
                    $module = $parts[0];
                    if (!isset($permissions[$module])) {
                        $permissions[$module] = [];
                    }
                    $permissions[$module][] = $routeName;
                } else {
                    $existing++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Permisos sincronizados correctamente',
                'created' => $created,
                'existing' => $existing,
                'skipped' => $skipped,
                'permissions_by_module' => $permissions,
                'total' => $created + $existing + $skipped
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar permisos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determinar si una ruta debe ser ignorada
     */
    private function shouldSkipRoute($routeName, $ignoredRoutes)
    {
        foreach ($ignoredRoutes as $ignored) {
            if (str_starts_with($routeName, $ignored)) {
                return true;
            }
        }
        return false;
    }

    // Métodos adicionales que pueden existir en el controlador
    // Estos métodos se pueden agregar según sea necesario
    public function createCorrelativosPermissions()
    {
        // Implementación si existe
        return response()->json(['message' => 'Método no implementado']);
    }

    public function assignCorrelativosPermissions(Request $request)
    {
        // Implementación si existe
        return response()->json(['message' => 'Método no implementado']);
    }
}
