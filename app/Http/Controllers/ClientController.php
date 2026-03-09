<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Client;
use App\Models\Company;
use App\Models\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Nette\Utils\Arrays;
use Spatie\Permission\Traits\HasRoles;

use function PHPUnit\Framework\isNull;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($company = "0")
{
    $id_user = auth()->user()->id;

    // Obtener la empresa a la que pertenece el usuario
    $company_user = Company::join('permission_company', 'companies.id', '=', 'permission_company.company_id')
        ->where('permission_company.user_id', '=', $id_user)
        ->pluck('companies.id')
        ->first();

    $company_selected = ($company != "0") ? base64_decode($company) : $company_user;

    // Consultar el rol del usuario (asumiendo que el rol de admin tiene role_id = 1 y ventas tiene role_id = 3)
    $rolQuery = "SELECT a.role_id FROM model_has_roles a WHERE a.model_id = ?";
    $rolResult = DB::select($rolQuery, [$id_user]);
    $roleId = !empty($rolResult) ? $rolResult[0]->role_id : null;
    $isAdmin = $roleId == 1;
    $isVentas = $roleId == 3;
    // Los usuarios admin y de ventas pueden ver todos los clientes
    $canViewAllClients = $isAdmin || $isVentas;

    // Construcción de la consulta
    $clientsQuery = Client::join('addresses', 'clients.address_id', '=', 'addresses.id')
        ->join('countries', 'addresses.country_id', '=', 'countries.id')
        ->join('departments', 'addresses.department_id', '=', 'departments.id')
        ->join('municipalities', 'addresses.municipality_id', '=', 'municipalities.id')
        ->join('economicactivities', 'clients.economicactivity_id', '=', 'economicactivities.id')
        ->join('phones', 'clients.phone_id', '=', 'phones.id')
        ->select(
            'clients.*',
            'countries.name as pais',
            'departments.name as departamento',
            'municipalities.name as municipioname',
            'economicactivities.name as econo',
            'addresses.reference as address',
            'phones.phone',
            'phones.phone_fijo',
            'addresses.country_id as country',
            'addresses.department_id as departament',
            'addresses.municipality_id as municipio',
            'clients.economicactivity_id as acteconomica'
        )
        ->where('clients.company_id', $company_selected);

    // Si no es admin ni usuario de ventas, solo muestra los clientes ingresados por él
    if (!$canViewAllClients) {
        $clientsQuery->where('clients.user_id', $id_user);
    }

    // Obtener los clientes filtrados
    $clients = $clientsQuery->get();

    return view('client.index', [
        "clients" => $clients,
        "companyselected" => $company_selected
    ]);
}



    public function getclientbycompany($idcompany)
    {
        $query = "SELECT
        a.*,
        a.id as id,
        a.firstname,
        a.firstlastname,
        a.secondname,
        a.secondlastname,
        a.comercial_name,
        a.name_contribuyente,
        a.nit,
        a.ncr,
        a.tpersona,
        a.tipoContribuyente,
        a.email,
        p.phone,
        (CASE
            WHEN a.tpersona = 'N' THEN CONCAT(a.firstname, ' ', a.firstlastname)
            WHEN a.tpersona = 'J' THEN a.comercial_name
            ELSE 'SIN NOMBRE'
        END) AS name_format_label,
        (CASE
            WHEN a.tpersona = 'N' THEN CONCAT(
                COALESCE(a.firstname, ''), ' ',
                COALESCE(a.firstlastname, ''), ' | ',
                COALESCE(a.nit, 'SIN DUI/NIT'), ' | ',
                COALESCE(a.ncr, 'SIN NCR')
            )
            WHEN a.tpersona = 'J' THEN CONCAT(
                COALESCE(a.comercial_name, 'SIN NOMBRE'), ' | ',
                COALESCE(a.nit, 'SIN NIT'), ' | ',
                COALESCE(a.ncr, 'SIN NCR')
            )
            ELSE 'SIN DATOS'
        END) AS search_display_text
       FROM clients a
       LEFT JOIN phones p ON a.phone_id = p.id
       WHERE a.company_id=" .  base64_decode($idcompany) . "";

        $result = DB::select(DB::raw($query));
        return response()->json($result);
    }

    public function gettypecontri($client)
    {
        $contribuyente = Client::find(base64_decode($client));
        return response()->json($contribuyente);
    }

        public function keyclient($num, $tpersona, $campo = null, $clientId = null)
    {
        $tpersona = base64_decode($tpersona);
        $num = base64_decode($num);
        $campo = $campo ? base64_decode($campo) : null;
        $clientId = $clientId ? base64_decode($clientId) : null;

        if($tpersona == "E"){
            // Extranjero - validar por pasaporte
            $query = Client::where('pasaporte', $num);
            if ($clientId) {
                $query->where('id', '!=', $clientId);
            }
            $cliente = $query->first();
            if ($cliente) {
                return response()->json([
                    'val' => true,
                    'message' => 'Ya existe un cliente extranjero con este pasaporte: ' . $num,
                    'tipo' => 'extranjero',
                    'campo' => 'pasaporte'
                ]);
            }
                } else if ($tpersona == "N") {
            // Persona natural
            if ($campo === 'ncr') {
                // Natural contribuyente: validar NRC (comparar sin guiones pero mostrar con guiones)
                $query = Client::where('tpersona', 'N')->whereNotNull('ncr')->where('ncr', '!=', 'N/A');
                if ($clientId) {
                    $query->where('id', '!=', $clientId);
                }
                $clientes = $query->get();
                foreach ($clientes as $cliente) {
                    $cleanDbNcr = preg_replace('/[-\s]/', '', $cliente->ncr);
                    if ($cleanDbNcr === $num) {
                        return response()->json([
                            'val' => true,
                            'message' => 'Ya existe una persona natural contribuyente con este NRC: ' . $cliente->ncr,
                            'tipo' => 'natural',
                            'campo' => 'ncr'
                        ]);
                    }
                }
            } else {
                // Persona natural - validar por DUI/NIT (comparar sin guiones pero mostrar con guiones)
                $query = Client::where('tpersona', 'N')->whereNotNull('nit')->where('nit', '!=', 'N/A');
                if ($clientId) {
                    $query->where('id', '!=', $clientId);
                }
                $clientes = $query->get();
                foreach ($clientes as $cliente) {
                    $cleanDbNit = preg_replace('/[-\s]/', '', $cliente->nit);
                    if ($cleanDbNit === $num) {
                        return response()->json([
                            'val' => true,
                            'message' => 'Ya existe una persona natural con este DUI/NIT: ' . $cliente->nit,
                            'tipo' => 'natural',
                            'campo' => 'nit'
                        ]);
                    }
                }
            }
        } elseif ($tpersona == "J") {
            // Persona jurídica - validar por NRC o NIT según el campo
            if ($campo === 'nit') {
                $query = Client::where('tpersona', 'J')->whereNotNull('nit')->where('nit', '!=', 'N/A');
                if ($clientId) {
                    $query->where('id', '!=', $clientId);
                }
                $clientes = $query->get();
                foreach ($clientes as $cliente) {
                    $cleanDbNit = preg_replace('/[-\s]/', '', $cliente->nit);
                    if ($cleanDbNit === $num) {
                        return response()->json([
                            'val' => true,
                            'message' => 'Ya existe una persona jurídica con este NIT: ' . $cliente->nit,
                            'tipo' => 'juridico',
                            'campo' => 'nit'
                        ]);
                    }
                }
            } else {
                // Por defecto validar NCR
                $query = Client::where('tpersona', 'J')->whereNotNull('ncr')->where('ncr', '!=', 'N/A');
                if ($clientId) {
                    $query->where('id', '!=', $clientId);
                }
                $clientes = $query->get();
                foreach ($clientes as $cliente) {
                    $cleanDbNcr = preg_replace('/[-\s]/', '', $cliente->ncr);
                    if ($cleanDbNcr === $num) {
                        return response()->json([
                            'val' => true,
                            'message' => 'Ya existe una persona jurídica con este NRC: ' . $cliente->ncr,
                            'tipo' => 'juridico',
                            'campo' => 'ncr'
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'val' => false,
            'message' => 'El cliente no existe',
            'tipo' => $tpersona
        ]);
    }

    public function getClientid($id)
    {
        $Client = Client::join('addresses', 'clients.address_id', '=', 'addresses.id')
            ->join('countries', 'addresses.country_id', '=', 'countries.id')
            ->join('departments', 'addresses.department_id', '=', 'departments.id')
            ->join('municipalities', 'addresses.municipality_id', '=', 'municipalities.id')
            ->join('economicactivities', 'clients.economicactivity_id', '=', 'economicactivities.id')
            ->join('phones', 'clients.phone_id', '=', 'phones.id')
            ->select(
                'clients.*',
                'countries.name as pais',
                'departments.name as departamento',
                'municipalities.name as municipio',
                'economicactivities.name as econo',
                'addresses.reference as address',
                'phones.phone',
                'phones.phone_fijo',
                'addresses.country_id as country',
                'addresses.department_id as departament',
                'addresses.municipality_id as municipio',
                'clients.economicactivity_id as acteconomica'
            )
            ->where('clients.id', '=', base64_decode($id))
            ->get();
        return response()->json($Client);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('client.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validaciones previas para evitar duplicados
        $validationErrors = [];

        // Limpiar valores removiendo guiones y espacios
        $cleanNit = $request->nit ? preg_replace('/[-\s]/', '', $request->nit) : null;
        $cleanNcr = $request->ncr ? preg_replace('/[-\s]/', '', $request->ncr) : null;
        $cleanPasaporte = $request->pasaporte ? preg_replace('/[-\s]/', '', $request->pasaporte) : null;

        // Validar según el tipo de persona (comparar sin guiones)
        if ($request->extranjero == 'on') {
            // Extranjero - validar pasaporte
            if ($cleanPasaporte) {
                $clientes = Client::whereNotNull('pasaporte')->where('pasaporte', '!=', 'N/A')->get();
                foreach ($clientes as $cliente) {
                    $cleanDbPasaporte = preg_replace('/[-\s]/', '', $cliente->pasaporte);
                    if ($cleanDbPasaporte === $cleanPasaporte) {
                        $validationErrors[] = 'Ya existe un cliente extranjero con el pasaporte: ' . $cliente->pasaporte;
                        break;
                    }
                }
            }
        } else if ($request->tpersona == 'N') {
            // Persona natural - validar DUI/NIT
            if ($cleanNit) {
                $clientes = Client::where('tpersona', 'N')->whereNotNull('nit')->where('nit', '!=', 'N/A')->get();
                foreach ($clientes as $cliente) {
                    $cleanDbNit = preg_replace('/[-\s]/', '', $cliente->nit);
                    if ($cleanDbNit === $cleanNit) {
                        $validationErrors[] = 'Ya existe una persona natural con el DUI/NIT: ' . $cliente->nit;
                        break;
                    }
                }
            }
        } else if ($request->tpersona == 'J') {
            // Persona jurídica - validar NRC y NIT
            if ($cleanNcr) {
                $clientes = Client::where('tpersona', 'J')->whereNotNull('ncr')->where('ncr', '!=', 'N/A')->get();
                foreach ($clientes as $cliente) {
                    $cleanDbNcr = preg_replace('/[-\s]/', '', $cliente->ncr);
                    if ($cleanDbNcr === $cleanNcr) {
                        $validationErrors[] = 'Ya existe una persona jurídica con el NRC: ' . $cliente->ncr;
                        break;
                    }
                }
            }
            if ($cleanNit) {
                $clientes = Client::where('tpersona', 'J')->whereNotNull('nit')->where('nit', '!=', 'N/A')->get();
                foreach ($clientes as $cliente) {
                    $cleanDbNit = preg_replace('/[-\s]/', '', $cliente->nit);
                    if ($cleanDbNit === $cleanNit) {
                        $validationErrors[] = 'Ya existe una persona jurídica con el NIT: ' . $cliente->nit;
                        break;
                    }
                }
            }
        }

        // Si hay errores de validación, retornar error
        if (!empty($validationErrors)) {
            return response()->json([
                'error' => 'Error de validación',
                'messages' => $validationErrors
            ], 422);
        }

        DB::beginTransaction();
        try {
            $id_user = auth()->user()->id;
            $phone = new Phone();
            $phone->phone = $request->tel1;
            $phone->phone_fijo = $request->tel2;
            $phone->save();

            $address = new Address();
            $address->country_id = $request->country;
            $address->department_id = $request->departament;
            $address->municipality_id = $request->municipio;
            $address->reference = $request->address;
            $address->save();
            //dd($request);
            $client = new Client();
            $client->firstname = (is_null($request->firstname) ? 'N/A' : $request->firstname);
            $client->secondname = (is_null($request->firstname) ? 'N/A' : $request->secondname);
            $client->firstlastname = (is_null($request->firstlastname) ? 'N/A' : $request->firstlastname);
            $client->secondlastname = (is_null($request->secondlastname) ? 'N/A' : $request->secondlastname);
            $client->comercial_name = (is_null($request->comercial_name) ? 'N/A' : $request->comercial_name);
            $client->name_contribuyente = (is_null($request->name_contribuyente) ? 'N/A' : $request->name_contribuyente);
            $client->email = $request->email;
            if ($request->contribuyente == 'on') {
                $contri = '1';
            } else {
                $contri = '0';
            }
            if ($request->extranjero == 'on') {
                $extranjero = '1';
            } else {
                $extranjero = '0';
            }
            if ($request->agente_retencion == 'on') {
                $agente_retencion = '1';
            } else {
                $agente_retencion = '0';
            }
            $client->ncr = (is_null($request->ncr) ? 'N/A' : $request->ncr);
            $client->giro = (is_null($request->giro) ? 'N/A' : $request->giro);
            $client->nit = $request->nit;
            $client->legal = (is_null($request->legal) ? 'N/A' : $request->legal);
            $client->tpersona = $request->tpersona;
            $client->contribuyente = $contri;
            $client->extranjero = $extranjero;
            $client->agente_retencion = $agente_retencion;
            $client->pasaporte = $request->pasaporte;
            $client->tipoContribuyente = $request->tipocontribuyente;
            $client->economicactivity_id = $request->acteconomica;
            $client->birthday = date('Ymd', strtotime($request->birthday));
            $client->empresa = (is_null($request->empresa) ? 'N/A' : $request->empresa);
            $client->company_id = $request->companyselected;
            $client->address_id = $address['id'];
            $client->phone_id = $phone['id'];
            $client->user_id = $id_user;
            $client->save();
            $com = $request->companyselected;
            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cliente agregado correctamente.',
                    'redirect' => route('client.index', ['company' => base64_encode($com)])
                ]);
            }
            return redirect()->route('client.index', base64_encode($com));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudo guardar el cliente', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return view('Company.view', array(
            "company" => Client::join('addresses', 'companies.address_id', '=', 'addresses.id')
                ->join('countries', 'addresses.country_id', '=', 'countries.id')
                ->join('departments', 'addresses.department_id', '=', 'departments.id')
                ->join('municipalities', 'addresses.municipality_id', '=', 'municipalities.id')
                ->join('economicactivities', 'companies.economicactivity_id', '=', 'economicactivities.id')
                ->select('companies.*', 'countries.name as pais', 'departments.name as departamento', 'municipalities.name as municipio', 'economicactivities.name as econo', 'addresses.reference as address')
                ->where('companies.id', '=', $id)
                ->get()
        ));
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function edit(Client $client)
    {
        //return view('client.edit', compact('client'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Client $client)
    {
        // Validaciones previas para evitar duplicados (excluyendo el cliente actual)
        $validationErrors = [];
        $clientId = $request->idedit;

        // Obtener el cliente actual para comparar valores
        $clienteActual = Client::find($clientId);
        if (!$clienteActual) {
            return response()->json([
                'error' => 'Error',
                'messages' => ['Cliente no encontrado']
            ], 404);
        }

        // Limpiar valores removiendo guiones y espacios
        $cleanNit = $request->nitedit ? preg_replace('/[-\s]/', '', $request->nitedit) : null;
        $cleanNcr = $request->ncredit ? preg_replace('/[-\s]/', '', $request->ncredit) : null;
        $cleanPasaporte = $request->pasaporteedit ? preg_replace('/[-\s]/', '', $request->pasaporteedit) : null;

        // Limpiar valores actuales del cliente para comparar
        $cleanNitActual = $clienteActual->nit ? preg_replace('/[-\s]/', '', $clienteActual->nit) : null;
        $cleanNcrActual = $clienteActual->ncr ? preg_replace('/[-\s]/', '', $clienteActual->ncr) : null;
        $cleanPasaporteActual = $clienteActual->pasaporte ? preg_replace('/[-\s]/', '', $clienteActual->pasaporte) : null;

        // Validar según el tipo de persona (solo si los campos cambiaron)
        if ($request->extranjeroedit == '1') {
            // Extranjero - validar pasaporte solo si cambió
            if ($cleanPasaporte && $cleanPasaporte !== $cleanPasaporteActual) {
                $clientes = Client::whereNotNull('pasaporte')->where('pasaporte', '!=', 'N/A')
                    ->where('id', '!=', $clientId)->get();
                foreach ($clientes as $cliente) {
                    $cleanDbPasaporte = preg_replace('/[-\s]/', '', $cliente->pasaporte);
                    if ($cleanDbPasaporte === $cleanPasaporte) {
                        $validationErrors[] = 'Ya existe otro cliente extranjero con el pasaporte: ' . $cliente->pasaporte;
                        break;
                    }
                }
            }
        } else if ($request->tpersonaedit == 'N') {
            // Persona natural - validar DUI/NIT solo si cambió
            if ($cleanNit && $cleanNit !== $cleanNitActual) {
                $clientes = Client::where('tpersona', 'N')->whereNotNull('nit')->where('nit', '!=', 'N/A')
                    ->where('id', '!=', $clientId)->get();
                foreach ($clientes as $cliente) {
                    $cleanDbNit = preg_replace('/[-\s]/', '', $cliente->nit);
                    if ($cleanDbNit === $cleanNit) {
                        $validationErrors[] = 'Ya existe otra persona natural con el DUI/NIT: ' . $cliente->nit;
                        break;
                    }
                }
            }
            // Si es natural contribuyente, validar NRC solo si cambió
            if ($request->contribuyenteeditvalor == '1' && $cleanNcr && $cleanNcr !== $cleanNcrActual) {
                $clientes = Client::where('tpersona', 'N')->whereNotNull('ncr')->where('ncr', '!=', 'N/A')
                    ->where('id', '!=', $clientId)->get();
                foreach ($clientes as $cliente) {
                    $cleanDbNcr = preg_replace('/[-\s]/', '', $cliente->ncr);
                    if ($cleanDbNcr === $cleanNcr) {
                        $validationErrors[] = 'Ya existe otra persona natural contribuyente con el NRC: ' . $cliente->ncr;
                        break;
                    }
                }
            }
        } else if ($request->tpersonaedit == 'J') {
            // Persona jurídica - validar NRC solo si cambió
            if ($cleanNcr && $cleanNcr !== $cleanNcrActual) {
                $clientes = Client::where('tpersona', 'J')->whereNotNull('ncr')->where('ncr', '!=', 'N/A')
                    ->where('id', '!=', $clientId)->get();
                foreach ($clientes as $cliente) {
                    $cleanDbNcr = preg_replace('/[-\s]/', '', $cliente->ncr);
                    if ($cleanDbNcr === $cleanNcr) {
                        $validationErrors[] = 'Ya existe otra persona jurídica con el NRC: ' . $cliente->ncr;
                        break;
                    }
                }
            }
            // Persona jurídica - validar NIT solo si cambió
            if ($cleanNit && $cleanNit !== $cleanNitActual) {
                $clientes = Client::where('tpersona', 'J')->whereNotNull('nit')->where('nit', '!=', 'N/A')
                    ->where('id', '!=', $clientId)->get();
                foreach ($clientes as $cliente) {
                    $cleanDbNit = preg_replace('/[-\s]/', '', $cliente->nit);
                    if ($cleanDbNit === $cleanNit) {
                        $validationErrors[] = 'Ya existe otra persona jurídica con el NIT: ' . $cliente->nit;
                        break;
                    }
                }
            }
        }

        // Si hay errores de validación, retornar error
        if (!empty($validationErrors)) {
            return response()->json([
                'error' => 'Error de validación',
                'messages' => $validationErrors
            ], 422);
        }

        DB::beginTransaction();
        try {
            $id_user = auth()->user()->id;
        $phone = Phone::find($request->phoneeditid);
        $phone->phone = $request->tel1edit;
        $phone->phone_fijo = $request->tel2edit;
        $phone->save();

        $address = Address::find($request->addresseditid);
        $address->country_id = $request->countryedit;
        $address->department_id = $request->departamentedit;
        $address->municipality_id = $request->municipioedit;
        $address->reference = $request->addressedit;
        $address->save();
        //dd($request);
        $client = Client::find($request->idedit);
        $client->firstname = $request->firstnameedit;
        $client->secondname = $request->secondnameedit;
        $client->firstlastname = $request->firstlastnameedit;
        $client->secondlastname = $request->secondlastnameedit;
        $client->comercial_name = $request->comercial_nameedit;
        $client->name_contribuyente = $request->name_contribuyenteedit;
        $client->email = $request->emailedit;
        $client->ncr = $request->ncredit;
        $client->giro = $request->giroedit;
        $client->nit = $request->nitedit;
        $client->legal = $request->legaledit;
        $client->tpersona = $request->tpersonaedit;
        $client->contribuyente = $request->contribuyenteeditvalor;
        $client->extranjero = $request->extranjeroedit == 'on' ? '1' : '0';
        $client->pasaporte = $request->pasaporteedit;
        // Actualizar agente de retención
        $agente_retencion_value = $request->agente_retencionedit == 'on' ? '1' : ($request->agente_retencionedit_hidden == '1' ? '1' : '0');
        $client->agente_retencion = $agente_retencion_value;
        $client->tipoContribuyente = $request->tipocontribuyenteedit;
        $client->economicactivity_id = $request->acteconomicaedit;
        $client->birthday = date('Ymd', strtotime($request->birthdayedit));
        $client->empresa = $request->empresaedit;
        $client->address_id = $address['id'];
        $client->phone_id = $phone['id'];
        $client->phone_id = $phone['id'];
        $client->user_id_update = $id_user;
        $client->save();
        $com = $request->companyselectededit;

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Cliente actualizado exitosamente'
        ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudo actualizar el cliente', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $client = Client::find(base64_decode($id));

            if (!$client) {
                return response()->json([
                    "res" => "0",
                    "message" => "Cliente no encontrado"
                ], 404);
            }

            // Verificar si el cliente tiene ventas relacionadas
            $salesCount = $client->sales()->count();
            if ($salesCount > 0) {
                return response()->json([
                    "res" => "0",
                    "message" => "No se puede eliminar el cliente porque tiene {$salesCount} venta(s) relacionada(s). Por favor, elimine primero las ventas asociadas."
                ], 400);
            }

            // Verificar si el cliente tiene cotizaciones relacionadas
            $quotationsCount = $client->quotations()->count();
            if ($quotationsCount > 0) {
                return response()->json([
                    "res" => "0",
                    "message" => "No se puede eliminar el cliente porque tiene {$quotationsCount} cotización(es) relacionada(s). Por favor, elimine primero las cotizaciones asociadas."
                ], 400);
            }

            // Si no tiene relaciones, proceder con la eliminación
            $client->delete();

            return response()->json([
                "res" => "1",
                "message" => "Cliente eliminado exitosamente"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "res" => "0",
                "message" => "Error al eliminar el cliente: " . $e->getMessage()
            ], 500);
        }
    }
}
