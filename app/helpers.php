<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\Product;
use PhpParser\Node\Stmt\Foreach_;

// Configurar zona horaria de El Salvador para todas las funciones date()
date_default_timezone_set('America/El_Salvador');

if (!function_exists('get_multi_result_set')) {
    function get_multi_result_set($conn, $statement)
    {
        $results = [];
        $pdo = DB::connection($conn)->getPdo();
        $result = $pdo->prepare($statement);
        $result->execute();
        do {
            $resultSet = [];
            foreach ($result->fetchall(PDO::FETCH_ASSOC) as $res) {
                array_push($resultSet, $res);
            }
            array_push($results, $resultSet);
        } while ($result->nextRowset());

        return $results;
    }
}

if (!function_exists('CerosIzquierda')) {
    function CerosIzquierda($cadena, $numero)
    {
        $aux = str_pad($cadena, $numero, "0", STR_PAD_LEFT);
        return $aux;
    }
}

if (!function_exists('codigoQR')) {
    function codigoQR($ambiente, $codigo, $fecha)
    {
        $url = 'https://webapp.dtes.mh.gob.sv/consultaPublica?ambiente=' . $ambiente . '&codGen=' . $codigo . '&fechaEmi=' . date('Y-m-d', strtotime($fecha));
        return (string)QrCode::size(100)->generate($url);

    }
}

if (!function_exists('urlCodigoQR')) {
    function urlCodigoQR($ambiente, $codigo, $fecha)
    {
        return 'https://webapp.dtes.mh.gob.sv/consultaPublica?ambiente=' . $ambiente . '&codGen=' . $codigo . '&fechaEmi=' . date('Y-m-d', strtotime($fecha));
    }
}

if (!function_exists('FEstatus')) {
    function FEstatus($estatus)
    {
        return ($estatus ==1)? 'Activo' : 'Inactivo';
    }
}

if (!function_exists('Frol')) {
    function Frol($rol)
    {
        switch ($rol) {
            case '1':
               $rol_name = "Caja";
                break;
            case '2':
                $rol_name = "Supervisor";
                break;
            case '3':
                $rol_name = "Administrador";
                break;
            default:
                # code...
                break;
        }
        return $rol_name;
    }
}

if (!function_exists('FNumero')) {
    function FNumero($numero)
    {
        return number_format($numero, 2, '.', ',');
    }
}

if (!function_exists('logo_pdf')) {
    function logo_pdf($ncr) {
        // Cache key para el logo
        $cacheKey = "logo_pdf_{$ncr}";

        // Intentar obtener del cache primero
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $company = Company::where('ncr', $ncr)->select('logo')->first();

        if (!$company || !$company->logo) {
            return null;
        }

        $logoPath = public_path('assets/img/logo/' . $company->logo);

        if (file_exists($logoPath)) {
            try {
                // Verificar el tamaño del archivo antes de procesarlo
                $fileSize = filesize($logoPath);
                if ($fileSize > 1024 * 1024) { // Si es mayor a 1MB, no procesar
                    return null;
                }

                // Reducir la calidad de la imagen para ahorrar memoria
                $logoData = file_get_contents($logoPath);
                if ($logoData !== false) {
                    // Crear una imagen más pequeña para reducir el uso de memoria
                    $image = imagecreatefromstring($logoData);
                    if ($image) {
                        // Redimensionar a un tamaño máximo de 200x200 píxeles
                        $width = imagesx($image);
                        $height = imagesy($image);

                        if ($width > 200 || $height > 200) {
                            $newWidth = min(200, $width);
                            $newHeight = min(200, $height);

                            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                            imagealphablending($resizedImage, false);
                            imagesavealpha($resizedImage, true);

                            imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                            // Convertir a PNG con compresión
                            ob_start();
                            imagepng($resizedImage, null, 6); // Nivel 6 de compresión
                            $resizedData = ob_get_contents();
                            ob_end_clean();

                            imagedestroy($resizedImage);
                            imagedestroy($image);

                            $base64 = 'data:image/png;base64,' . base64_encode($resizedData);
                        } else {
                            imagedestroy($image);
                            $base64 = 'data:image/png;base64,' . base64_encode($logoData);
                        }

                        // Guardar en cache por 24 horas
                        Cache::put($cacheKey, $base64, now()->addHours(24));

                        return $base64;
                    }
                }
            } catch (Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Obtener descripción completa del producto (nombre + marca) para PDF
     */
    if (!function_exists('product_description_pdf')) {
        function product_description_pdf($productId) {
            // Cache key para la descripción del producto
            $cacheKey = "product_desc_{$productId}";

            // Intentar obtener del cache primero
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            try {
                $product = \App\Models\Product::with('marca')
                    ->select('id', 'name', 'marca_id')
                    ->find($productId);

                if (!$product) {
                    return 'Producto no encontrado';
                }

                // Construir descripción: nombre + marca (como en el módulo de ventas)
                $description = $product->name;
                if ($product->marca && $product->marca->name) {
                    $description .= ' ' . $product->marca->name;
                }

                // Guardar en cache por 1 hora
                Cache::put($cacheKey, $description, now()->addHour());

                return $description;
            } catch (Exception $e) {
                return 'Error al cargar producto';
            }
        }
    }
}

if (!function_exists('get_name_departamento')) {
    function get_name_departamento($id) {
        // Usando find() para obtener el departamento por su ID
        $department = Department::find($id);
        // Verifica si el departamento fue encontrado
        if ($department) {
            return $department->name; // Devuelve el nombre del departamento
        }
        return null; // Si no se encuentra el departamento, devuelve null
    }
}
if (!function_exists('get_name_municipio')) {
    function get_name_municipio($id) {
        // Usando find() para obtener el municipio por su ID
        $municipio = Municipality::find($id);
        // Verifica si el municipio fue encontrado
        if ($municipio) {
            return $municipio->name; // Devuelve el nombre del municipio
        }
        return null; // Si no se encuentra el municipio, devuelve null
    }
}

if (!function_exists('numeroDTE')) {
    function numeroDTE($numero, $tipo, $establecimiento, $cod_establecimiento)
    {
        $numero_documento = CerosIzquierda($numero,15);
        $tipo_documento = $tipo;
        $caja = $cod_establecimiento;
        $tipo_establecimiento = CerosIzquierda($establecimiento, 4);
        return "DTE-" . $tipo_documento . "-" . $tipo_establecimiento . $caja . "-" . $numero_documento;
    }
}

if (!function_exists('Tipo_Establecimiento')) {
    function Tipo_Establecimiento($numero)
    {

        switch ($numero) {
            case '01':
                $tipo = 'Sucursal';
                break;
            case '02':
                $tipo = 'Casa matriz';
                break;
            case '04':
                $tipo = 'Bodega';
                break;
            case '07':
                $tipo = 'Predio y/o patio';
                break;
            case '20':
                $tipo = 'Otro';
                break;
            default:
                $tipo = 'Sucursal / Agencia';
                break;
        }
        return $tipo;
    }
}

if (!function_exists('Send_Mail')) {
    function Send_Mail($to_name, $to_email,$to_cc, $nombre, $data, $subject, $pdf)
    {
        try {
        //$data = array('name' => $nombre, "p" => $body);


        // Agregar variables necesarias para la plantilla
        $data['numeroFactura'] = $data['numeroFactura'] ?? $data['numero_control'] ?? '';
        $data['nombreEmpresa'] = $data['nombreEmpresa'] ?? config('app.name');

        $envio = Mail::send(['html' => 'emails.factura-offline'], $data, function ($message) use ($to_name, $to_email, $to_cc, $subject,$pdf) {
            $message->to($to_email, $to_name)
                ->cc(env('MAIL_CC', ''))
                ->subject($subject)
                ->attachData($pdf->output(), "comprobante.pdf");
            $message->from(env('MAIL_FROM_ADDRESS',''),env('MAIL_FROM_NAME', ''));
        });
        }
        catch(Exception $e) {
            echo 'Message: ' .$e->getMessage();
        }
        //dd($envio);
        return $envio;
    }
}

if (!function_exists('enviar_correo_prueba')) {
    function enviar_correo_prueba($to_name, $to_email,$to_cc, $data, $subject)
    {
        try {
        //$data = array('name' => $nombre, "p" => $body);
        //dd($data);

        $envio = Mail::send(['html' => 'emails.prueba_correo'], $data, function ($message) use ($to_name, $to_email, $to_cc, $subject) {
            $message->to($to_email, $to_name)
                ->cc($to_cc)
                ->subject($subject);
            $message->from(env('MAIL_FROM_ADDRESS',''),env('MAIL_FROM_NAME', ''));
        });
        }
        catch(Exception $e) {
            echo 'Message: ' .$e->getMessage();
            return ;
        }
        //dd($envio);
        return $envio;
    }
}




if (!function_exists('convertir_json')) {
    function convertir_json($compro_procesar, $codTransaccion="01")
    {
        //var_dump($compro_procesar);
        $compro = $compro_procesar;
        //dd(is_array($compro));
        //dd($compro["documento"][0]["tipodocumento"]);
        if ($codTransaccion=="02") {
            $tipo_comprobante = $compro["documento"][0]["tipodocumento"];
        } else if($codTransaccion=="05") {
            $tipo_comprobante = $compro["documento"][0]["tipodocumento"];
        } else {
            $tipo_comprobante = $compro["documento"][0]->tipodocumento;
        }
        //dd($tipo_comprobante);
        $retorno = [];
        //cmabio pendiente porque el uuid si es contingencia o si ya tiene un uuid no crear uno nuevo sino utilizar el que ya tiene autorizado
        $uuid_generado = strtoupper(Str::uuid()->toString());
        //$retorno=[$compro, $uuid_generado];
        switch ($tipo_comprobante) {
            case '03': //CRF
                $retorno = crf($compro, $uuid_generado);
                break;
            case '01': //FAC
                $retorno = fac($compro, $uuid_generado);
                break;
            case '05':  //NCR
                $retorno = ncr($compro, $uuid_generado);; // ncr($compro, $uuid_generado);
                break;
            case '06':  //NCR
                $retorno = ndb($compro, $uuid_generado);; // ncr($compro, $uuid_generado);
                break;
            case '08':  //CLQ
                $retorno = []; //clq($compro, $uuid_generado);
                break;
            case '11':  //FEX
                $retorno = fex($compro, $uuid_generado);
                break;
            case '14':  //FSE
                $retorno = fse($compro, $uuid_generado);
                break;
            case '99':
                $retorno = fan($compro, $uuid_generado);
                break;

            default:
                $retorno = [];
                break;
        }
        return $retorno;
    }
}


if (!function_exists('crf')) {
    function crf($comprobante_procesar, $uuid_generado)
    {
        $comprobante = [];
        $encabezado = $comprobante_procesar["documento"][0];
        $emisor_data = $comprobante_procesar["emisor"];
        $cliente = $comprobante_procesar["cliente"];
        $totales = $comprobante_procesar["totales"];
        $cuerpo =  $comprobante_procesar["detalle"];
        //dd($emisor_data);
        $uuid = $uuid_generado;
        $numero_documento = CerosIzquierda($encabezado->actual, 15);
        $tipo_documento = $encabezado->tipodocumento;
        $caja = "P001";
        //$tipo_establecimiento = CerosIzquierda($emisor_data[0]->tipoEstablecimiento, 4);
        $tipo_establecimiento = "M001";
        $empresa = $comprobante_procesar["emisor"];
        $identificacion = [
            "version"           => intval($encabezado->versionJson),
            "ambiente"          => $encabezado->ambiente,
            "tipoDte"           => $tipo_documento,
            "numeroControl"     => "DTE-" . $tipo_documento . "-" . $tipo_establecimiento . $caja . "-" . $numero_documento, //Cambiar
            "codigoGeneracion"  => $uuid,
            "tipoModelo"        => 1,
            "tipoOperacion"     => 1,
            "tipoContingencia"  => null,
            "motivoContin"      => null,
            "fecEmi"            => date('Y-m-d'), // "2022-07-23", //$encabezado["fecEmi"],    //Cambiar
            "horEmi"            => date("H:i:s"),      //Cambiar
            "tipoMoneda"        => "USD"            //Cambiar
        ];
        $comprobante = [
            "identificacion" => $identificacion
        ];

        $documentoRelacionado = null;

        $direccion_emisor = [
            "departamento"  => $emisor_data[0]->departamento,
            "municipio"     => $emisor_data[0]->municipio,
            "complemento"   => $emisor_data[0]->direccion
        ];
        //dd($emisor_data);
        $emisor = [
            "nit"                   => str_replace('-', '', $emisor_data[0]->nit),
            "nrc"                   => ltrim(str_replace('-', '', $emisor_data[0]->ncr), '0'),
            "nombre"                => trim($emisor_data[0]->nombre),
            "codActividad"          => $emisor_data[0]->codActividad,
            "descActividad"         => $emisor_data[0]->descActividad,
            "nombreComercial"       => $emisor_data[0]->nombreComercial,
            "tipoEstablecimiento"   => $emisor_data[0]->tipoEstablecimiento,
            "direccion"             => $direccion_emisor,
            "telefono"              => $emisor_data[0]->telefono,
            "correo"                 => $emisor_data[0]->correo,
            "codEstableMH"          => $emisor_data[0]->codEstableMH,
            "codEstable"            => $emisor_data[0]->codEstable,
            "codPuntoVentaMH"       => $emisor_data[0]->codPuntoVentaMH,
            "codPuntoVenta"         => $emisor_data[0]->codPuntoVenta
        ];

        //dd($emisor);

        $direccion_receptor = [];
        $direccion_receptor = [
            "departamento"  => $cliente[0]->departamento,
            "municipio"     => $cliente[0]->municipio,
            "complemento"   => $cliente[0]->direccion
        ];
        if (!empty($cliente[0]->codActividad)) {
            if (strlen($cliente[0]->codActividad) <= 4)
            {$codeActivity = '0' . $cliente[0]->codActividad;}
            else{ $codeActivity = $cliente[0]->codActividad; }
        }
        $receptor = [
            "nit"                   => str_replace("-", "", $cliente[0]->nit),
            "nrc"                   => ltrim(str_replace("-", "", $cliente[0]->ncr), '0'),
            "nombre"                => $cliente[0]->nombre,
            "codActividad"          => $codeActivity,
            "descActividad"         => $cliente[0]->descActividad,
            "nombreComercial"       => $cliente[0]->nombreComercial,
            "direccion"             => $direccion_receptor

        ];
        //dd($receptor);
        if ($cliente[0]->telefono != '') {
            $receptor["telefono"] = $cliente[0]->telefono;
        }
        if ($cliente[0]->correo != '') {
            $receptor["correo"] = $cliente[0]->correo;
        }

        $ventaTercero = null;

        if (isset($comprobante_procesar[3][0])) {
            // dd($comprobante_procesar[2]);
            $ventaTercero = [
                "nit"       => $comprobante_procesar[3][0]["nit"],
                "nombre"    => $comprobante_procesar[3][0]["nombre"],

            ];
        }

        $otrosDocumentos = null;

        $codigos_tributos = [];
        $i = 0;

        foreach ($cuerpo as $item) {
            # code...
            //dd($item);
            $i += 1;
            $tributos_properties_items_cuerpoDocumento = array();

            if ($item->iva != 0 and count($codigos_tributos) == 0) {
                $codigos_tributos = [
                    "codigo"        =>  "20",
                    "descripcion"   =>  "Impuesto al Valor Agregado 13%",
                    "valor"         => round((float)$item->iva, 2)
                ];
            } else {
                if ($item->iva != 0 and count($codigos_tributos) > 0) {
                    $iva = round((float)($codigos_tributos["valor"] + $item->iva), 2);
                    $codigos_tributos["valor"] = (float)$iva;
                }
            }

            $tributos_properties_items_cuerpoDocumento = ($item->iva != 0) ? "20" : "C3";

            $properties_items_cuerpoDocumento = array();
            //$precio_unitario = ($item->precio_unitario/$item->cantidad);
            $precio_unitario = ($item->precio_unitario);
            $properties_items_cuerpoDocumento = [
                "numItem"           => $i,
                "tipoItem"          => intval($item->tipo_item),  //Bienes y Servicios
                "numeroDocumento"   => null,
                "cantidad"          => floatval($item->cantidad),
                "codigo"            => "P0".$item->id_producto,
                "codTributo"        => null,
                "uniMedida"         => intval($item->uniMedida),
                "descripcion"       => $item->descripcion,
                "precioUni"         => round((float)($precio_unitario),8),
                "montoDescu"        => 0.00,
                "ventaNoSuj"        => round((float)($item->no_sujetas),8),
                "ventaExenta"       => round((float)($item->exentas),8),
                "ventaGravada"      => round((float)($item->gravadas),8),
                "tributos"          => ($item->gravadas != 0) ? ["20"] : null,
                "psv"               => (float)"0.00",
                "noGravado"         => round((float)$item->no_imponible,8)
            ];

            $items_cuerpoDocumento[] = $properties_items_cuerpoDocumento;
        }
        //dd($items_cuerpoDocumento);
        $cuerpoDocumento = $items_cuerpoDocumento;

        $properties_items_tributo_resumen = [
            "codigo"        => "",
            "descripcion"   => "",
            "valor"         => ""
        ];

        $tributos_resumen = [
            "properties"   => $properties_items_tributo_resumen
        ];

        $properties_items_pagos = [];
        //contado
        if ($totales["condicionOperacion"] == "01") {
            $properties_items_pagos[] = [
                "codigo"        => "01",
                "montoPago"     => round((float)($totales["totalPagar"] - ($totales["reteRenta"] ?? 0) - ($totales["ivaRete1"] ?? 0)),2),
                "referencia"    => null,
                "plazo"         => null,
                "periodo"       => null
            ];
        }

        //credito
        if ($totales["condicionOperacion"] == "02") {
            $properties_items_pagos[] = [
                "codigo"        => "13",
                "montoPago"     => (float)"0.00",
                "referencia"    => "Credito",
                "plazo"         => "01",
                "periodo"       => 15
            ];
        }

        //tarjeta
        if ($totales["condicionOperacion"] == "03") {
            $properties_items_pagos[] = [
                "codigo"        => "03",
                "montoPago"     => round((float)($totales["totalPagar"] - ($totales["reteRenta"] ?? 0) - ($totales["ivaRete1"] ?? 0)),2),
                "referencia"    => $totales["creditcardautorization"],
                "plazo"         => null,
                "periodo"       => null
            ];
        }
        /* if($encabezado["total_iva"] != 0){
        $codigos_tributos= [
            "codigo"        => "20",
            "descripcion"   => "Impuesto al Valor Agregado 13",
            "valor"         => (float)$encabezado["total_iva"]
        ];
        }*/

        $pagos = $properties_items_pagos;
        if (count($codigos_tributos) > 0) {
            if ($totales["totalGravada"] * 0.13 <> $codigos_tributos["valor"]) {
                $codigos_tributos["valor"] = round((float)($totales["totalGravada"] * 0.13),  2);
                // $codigos_tributos["valor"] = bcdiv($encabezado["tot_gravado"] * 0.13,1,2);

            }
        }

        // $codigos_tributos["valor"] = intval($codigos_tributos["valor"]/0.01);
        //dd($encabezado);
        $resumen = [
            "totalNoSuj"            => round((float)$totales["totalNoSuj"],2),
            "totalExenta"           => (float)$totales["totalExenta"],
            "totalGravada"          => round((float)$totales["totalGravada"],2),
            "subTotalVentas"        => round((float)$totales["subTotalVentas"],2) ,
            "descuNoSuj"            => round((float)$totales["descuNoSuj"],2),
            "descuExenta"           => round((float)$totales["descuExenta"],2),
            "descuGravada"          => round((float)$totales["descuGravada"],2),
            "porcentajeDescuento"   => (float)$totales["porcentajeDescuento"],
            "totalDescu"            => round((float)$totales["totalDescu"],2),
            "tributos"              => (!empty($codigos_tributos)) ? [$codigos_tributos] : null,
            "subTotal"              => round((float)($totales["subTotal"]- $totales["totalDescu"]),2),
            "ivaPerci1"             => round((float)($totales["ivaPerci1"] ?? 0),2),
            "ivaRete1"              => round((float)$totales["ivaRete1"],2),
            "reteRenta"             => round((float)$totales["reteRenta"],2),
            "montoTotalOperacion"   => round((float)($totales["montoTotalOperacion"] + $totales["totalIva"]), 2), //(float)$totales["montoTotalOperacion"], //(float)$encabezado["montoTotalOperacion"],
            "totalNoGravado"        => round((float)$totales["totalNoGravado"],2),
            "totalPagar"            => round((float)($totales["totalPagar"] - ($totales["reteRenta"] ?? 0) - ($totales["ivaRete1"] ?? 0)),2),
            "totalLetras"           => $totales["totalLetras"],
            "saldoFavor"            => round((float)$totales["saldoFavor"],2),
            "condicionOperacion"    => (float)$totales["condicionOperacion"],
            "pagos"                 => $pagos,
            "numPagoElectronico"    => ""
        ];

        //dd($resumen);

        $es_mayor = ($totales["totalPagar"] >= 11428.57);

        $extension = [
            "nombEntrega"   => ($es_mayor) ? $encabezado->NombreUsuario : null,
            "docuEntrega"   => ($es_mayor) ? str_replace("-", "", $encabezado->docUser) : null,
            "nombRecibe"    => ($es_mayor) ? $cliente[0]->nombre : null,
            "docuRecibe"    => ($es_mayor) ? str_replace("-", "", $cliente[0]->nit) : null,
            "observaciones" => ($es_mayor) ? null : null,
            "placaVehiculo" => ($es_mayor) ? null : null
        ];

        $apendice = null;



        $comprobante["documentoRelacionado"]     = $documentoRelacionado;
        $comprobante["emisor"]                   = $emisor;
        $comprobante["receptor"]                 = $receptor;
        $comprobante["otrosDocumentos"]          = $otrosDocumentos;
        $comprobante["ventaTercero"]             = $ventaTercero;
        $comprobante["cuerpoDocumento"]          = $cuerpoDocumento;
        $comprobante["resumen"]                  = $resumen;
        $comprobante["extension"]                = $extension;
        $comprobante["apendice"]                 = $apendice;
        //echo '<br>'. var_dump($comprobante) . '<br>';
        //dd($comprobante);
        return ($comprobante);
    }
}

if (!function_exists('fac')) {
    function fac($comprobante_procesar, $uuid_generado){

        $comprobante = [];
        $encabezado = $comprobante_procesar["documento"][0];
        $emisor_data = $comprobante_procesar["emisor"];
        $cliente = $comprobante_procesar["cliente"];
        $totales = $comprobante_procesar["totales"];
        //dd($emisor_data);
        //dd($encabezado);
        $cuerpo = $comprobante_procesar["detalle"];
        //dd($cuerpo);
        $uuid = $uuid_generado;
        $numero_documento = CerosIzquierda($encabezado->actual, 15);
        $tipo_documento = $encabezado->tipodocumento;
        //dd($encabezado);
        $caja = "P001";
        $testablecimiento = "1";
        //$tipo_establecimiento = CerosIzquierda($testablecimiento,4);//$encabezado["tipo_establecimiento"], 4);
        $tipo_establecimiento = "M001";
        $empresa = $comprobante_procesar["emisor"];
        $identificacion = [
            "version"           => intval($encabezado->versionJson),
            "ambiente"          => $encabezado->ambiente,
            "tipoDte"           => $tipo_documento,
            "numeroControl"     => "DTE-" . $tipo_documento . "-" . $tipo_establecimiento . $caja . "-" . $numero_documento, //Cambiar
            "codigoGeneracion"  => $uuid,
            "tipoModelo"        => 1,
            "tipoOperacion"     => 1,
            "tipoContingencia"  => null,
            "motivoContin"      => null,
            "fecEmi"            => date('Y-m-d'), //"2022-07-20", // $encabezado["fecEmi"],    //Cambiar
            "horEmi"            => date("H:i:s"), //$encabezado["horEmi"],      //Cambiar
            "tipoMoneda"        => "USD"            //Cambiar
        ];
        $comprobante = [
            "identificacion" => $identificacion
        ];

        $documentoRelacionado = null;
        //dd($emisor_data);
        $direccion_emisor = [
            "departamento"  => $emisor_data[0]->departamento,
            "municipio"     => $emisor_data[0]->municipio,
            "complemento"   => $emisor_data[0]->direccion
        ];

        $emisor = [
            "nit"                   => str_replace('-','', $emisor_data[0]->nit),
            "nrc"                   => ltrim(str_replace('-','', $emisor_data[0]->ncr), '0'),
            "nombre"                => trim($emisor_data[0]->nombre),
            "codActividad"          => $emisor_data[0]->codActividad,
            "descActividad"         => $emisor_data[0]->descActividad,
            "nombreComercial"       => $emisor_data[0]->nombreComercial,
            "tipoEstablecimiento"   => $emisor_data[0]->tipoEstablecimiento,
            "direccion"             => $direccion_emisor,
            "telefono"              => $emisor_data[0]->telefono,
            "codEstableMH"          => $emisor_data[0]->codEstableMH,
            "codEstable"            => $emisor_data[0]->codEstable,
            "codPuntoVentaMH"       => $emisor_data[0]->codPuntoVentaMH,
            "codPuntoVenta"         => $emisor_data[0]->codPuntoVenta,
            "correo"                => $emisor_data[0]->correo,
        ];

        $direccion_receptor = [];
        //dd($cliente);
        $direccion_receptor = [
            "departamento"  => $cliente[0]->departamento,
            "municipio"     => $cliente[0]->municipio,
            "complemento"   => $cliente[0]->direccion
        ];

        if (!empty($cliente[0]->codActividad)) {
            if (strlen($cliente[0]->codActividad) <= 4)
            {$codeActivity = '0' . $cliente[0]->codActividad;}
            else{ $codeActivity = $cliente[0]->codActividad; }
        }

        $receptor = [
            "tipoDocumento"         => ($cliente[0]->ncr == '' or is_null($cliente[0]->ncr) ) ? ($cliente[0]->tipoDocumento ?: null) : "36",
            "numDocumento"          => ($cliente[0]->ncr == '' or is_null($cliente[0]->ncr)) ? (str_replace("-","",$cliente[0]->numDocumento) ?: null) : (str_replace("-", "", $cliente[0]->nit) ?: null),
            "nrc"                   => ($cliente[0]->ncr == 'N/A' or $cliente[0]->ncr == '' or is_null($cliente[0]->ncr)) ? null : ltrim(str_replace("-","",$cliente[0]->ncr), '0'),
            "nombre"                => $cliente[0]->nombre ?: null,
            "codActividad"          => ($cliente[0]->codActividad == '' or $cliente[0]->codActividad == '0') ? null : ($codeActivity ?: null),
            "descActividad"         => ($cliente[0]->descActividad == '' or $cliente[0]->descActividad == '0') ? null : ($cliente[0]->descActividad ?: null),

        ];

        if ($cliente[0]->codPais == '9300') {

            $receptor["direccion"] = $direccion_receptor;
        }
        //dd($cliente);
        if ($cliente[0]->telefono != '') {
            $receptor["telefono"] = $cliente[0]->telefono;
        }
        if ($cliente[0]->correo != '') {
            $receptor["correo"] = $cliente[0]->correo;
        }
        $otrosDocumentos = null;

        $ventaTercero = null;
        if (isset($comprobante_procesar[3][0])) {
            // dd($comprobante_procesar[2]);
            $ventaTercero = [
                "nit"       => $comprobante_procesar[3][0]["nit"],
                "nombre"    => $comprobante_procesar[3][0]["nombre"],

            ];
        }
        $codigos_tributos = [];
        $i = 0;
        //return var_dump($cuerpo);
        //echo '<br>';
        foreach ($cuerpo as $item) {
            //var_dump($item);
            // echo '<br>';
            $i += 1;
            // echo $i.'<br>';
            # code...
            //dd($item);
            $tributos_properties_items_cuerpoDocumento = array();

            $tributos_properties_items_cuerpoDocumento = ($item->iva != 0) ? "20" : "C3";

            $properties_items_cuerpoDocumento = array();

            $iva_calculadofac = round(($item->iva/$item->cantidad),2);
            if($item->no_sujetas > 0){
            $ventagravada = 0;
            $ivaItem = 0;
            }else if($item->exentas > 0){
            $ventagravada = 0;
            $ivaItem = 0;
            }else{
            $ventagravada = round((float)($item->gravadas), 8);
            $ivaItem = round((float)($item->gravadas)-($item->gravadas/1.13), 8);
            }
            $properties_items_cuerpoDocumento = [
                "numItem"           => $i, //intval($item["corr"]),
                "tipoItem"          => intval($item->tipo_item),
                "numeroDocumento"   => null,
                "cantidad"          => floatval($item->cantidad),
                "codigo"            => "P0".$item->id_producto,
                "codTributo"        => null,
                "uniMedida"         => intval($item->uniMedida),
                "descripcion"       => $item->descripcion,
                "precioUni"         => round((float)($item->precio_unitario), 8),
                "montoDescu"        => 0.00,
                "ventaNoSuj"        => (float)$item->no_sujetas,
                "ventaExenta"       => (float)$item->exentas,
                "ventaGravada"      => $ventagravada,
                "tributos"          =>  null,
                "psv"               => (float)"0.00",
                "noGravado"         => (float)$item->no_imponible,
                "ivaItem"           => $ivaItem
            ];

            $items_cuerpoDocumento[] = $properties_items_cuerpoDocumento;
        }
        //dd($cuerpo);
        $cuerpoDocumento = $items_cuerpoDocumento;

        $properties_items_tributo_resumen = [
            "codigo"        => "",
            "descripcion"   => "",
            "valor"         => ""
        ];

        $tributos_resumen = [
            "properties"   => $properties_items_tributo_resumen
        ];



        $properties_items_pagos = [];
        //contado
        //dd($totales);
        if ($totales["condicionOperacion"] == "01") {
            $properties_items_pagos[] = [
                "codigo"        => "01",
                "montoPago"     => round((float)($totales["totalPagar"] - ($totales["reteRenta"] ?? 0) - ($totales["ivaRete1"] ?? 0)),2),
                "referencia"    => null,
                "plazo"         => null,
                "periodo"       => null
            ];
        }
        //credito
        if ($totales["condicionOperacion"] == "02") {
            $properties_items_pagos[] = [
                "codigo"        => "13",
                "montoPago"     => (float)"0.00",
                "referencia"    => "Credito",
                "plazo"         => "01",
                "periodo"       =>  15 //intval($encabezado["periodo"])
            ];
        }
        //tarjeta
        if ($totales["condicionOperacion"] == "03") {
            $properties_items_pagos[] = [
                "codigo"        => "03",
                "montoPago"     => round((float)($totales["totalPagar"] - ($totales["reteRenta"] ?? 0) - ($totales["ivaRete1"] ?? 0)),2),
                "referencia"    => $totales["creditcardautorization"],
                "plazo"         => null,
                "periodo"       => null
            ];
        }

        $pagos = $properties_items_pagos;


        $iva_resumen = ($totales["totalGravada"]) - ($totales["totalGravada"]/1.13);
        $resumen = [
            "totalNoSuj"            => (float)$totales["totalNoSuj"],
            "totalExenta"           => (float)$totales["totalExenta"],
            "totalGravada"          => round((float)($totales["totalGravada"]), 2),
            "subTotalVentas"        => round((float)($totales["subTotal"]), 2),
            "descuNoSuj"            => (float)$totales["descuNoSuj"],
            "descuExenta"           => (float)$totales["descuExenta"],
            "descuGravada"          => (float)$totales["descuGravada"],
            "porcentajeDescuento"   => (float)$totales["porcentajeDescuento"],
            "totalDescu"            => (float)$totales["totalDescu"],
            "tributos"              => $codigos_tributos,
            "subTotal"              => round((float)($totales["subTotal"]), 2),
            //"ivaPerci1"             => round((float)($totales["ivaPerci1"] ?? 0),2),
            "ivaRete1"              => round((float)($totales["ivaRete1"] ?? 0),2),
            "reteRenta"             => round((float)$totales["reteRenta"],2),
            "montoTotalOperacion"   => round((float)($totales["subTotal"]), 2), //(float)$totales["montoTotalOperacion"],
            "totalNoGravado"        => (float)$totales["totalNoGravado"],
            "totalPagar"            => round((float)($totales["totalPagar"] - ($totales["reteRenta"] ?? 0) - ($totales["ivaRete1"] ?? 0)),2),
            "totalLetras"           => $totales["totalLetras"],
            "totalIva"              => round((float)$iva_resumen,2),
            "saldoFavor"            => (float)$totales["saldoFavor"],
            "condicionOperacion"    => (float)$totales["condicionOperacion"],
            "pagos"                 => $pagos,
            "numPagoElectronico"    => ""
        ];

        $es_mayor = ($totales["totalPagar"] >= 200);

        $extension = [
            "nombEntrega"   => ($es_mayor) ? $encabezado->NombreUsuario : null,
            "docuEntrega"   => ($es_mayor) ? str_replace("-", "", $encabezado->docUser) : null,
            "nombRecibe"    => ($es_mayor) ? $cliente[0]->nombre : null,
            "docuRecibe"    => ($es_mayor) ? str_replace("-", "", $cliente[0]->nit) : null,
            "observaciones" => ($es_mayor) ? null : null,
            "placaVehiculo" => ($es_mayor) ? null : null
        ];

        $apendice[] = [
            "campo"         => "vendedor",
            "etiqueta"      => "Vendedor",
            "valor"         => $encabezado->hechopor
        ];

        $apendice[] = [
            "campo"         => "cliente",
            "etiqueta"      => "Cliente",
            "valor"         => $cliente[0]->idcliente
        ];


        $comprobante["documentoRelacionado"]     = $documentoRelacionado;
        $comprobante["emisor"]                   = $emisor;
        $comprobante["receptor"]                 = $receptor;
        $comprobante["otrosDocumentos"]          = $otrosDocumentos;
       // if (isset($comprobante_procesar[3][0])) {
        $comprobante["ventaTercero"]             =  $ventaTercero;
        //}
        $comprobante["cuerpoDocumento"]          = $cuerpoDocumento;
        $comprobante["resumen"]                  = $resumen;
        $comprobante["extension"]                = $extension;
        $comprobante["apendice"]                 = null;
        //$comprobante2 = [];
        //dd($comprobante);
        return ($comprobante);
    }
}

if (!function_exists('fan')) {
    function fan($comprobante_procesar, $uuid_generado){
        $comprobante = [];
        $encabezado = $comprobante_procesar["documento"][0];
        $emisor_data = $comprobante_procesar["emisor"];
        $cliente = $comprobante_procesar["cliente"];
        //dd($encabezado);
        $cuerpo = $comprobante_procesar["detalle"];
        //dd($cuerpo);
        $uuid = $uuid_generado;
        $numero_documento = CerosIzquierda($encabezado["nu_doc"], 15);
        $tipo_documento = $encabezado["tipoDteOriginal"];
        $caja = "P001";
        //$tipo_establecimiento = CerosIzquierda($encabezado["tipo_establecimiento"], 4);
        $tipo_establecimiento = "M001";
        $tipo_establecimiento = CerosIzquierda($encabezado["tipo_establecimiento"], 4);
        $empresa = $encabezado;
        $identificacion = [
            "version"           => intval($encabezado["version"]),
            "ambiente"          => $empresa["ambiente"],
            "codigoGeneracion"  => $uuid,
            "fecAnula" => date("Y-m-d"),  // Fecha actual en formato "año-mes-día"
            "horAnula" => date("H:i:s"),
            //"fecAnula"          => $encabezado["fecAnulado"],
            //"horAnula"          => $encabezado["horAnulado"]
        ];
        $comprobante = [
            "identificacion" => $identificacion
        ];
        $documentoRelacionado = null;
        //dd($emisor_data);
        $emisor = [
            "nit"                   => str_replace('-', '', $emisor_data[0]->nit),
            "nombre"                => trim($emisor_data[0]->nombre),
            "tipoEstablecimiento"   => $emisor_data[0]->tipoEstablecimiento,
            "nomEstablecimiento"    => "casa matriz", //cambiar
            //"codEstableMH"          => $emisor_data[0]->codEstableMH,
            "codEstable"            => $emisor_data[0]->codEstable,
            //"codPuntoVentaMH"       => $emisor_data[0]->codPuntoVentaMH,
            "codPuntoVenta"         => $emisor_data[0]->codPuntoVenta,
            "telefono"              => $emisor_data[0]->telefono,
            "correo"                => $emisor_data[0]->correo,


        ];

        //dd($encabezado);
        $documento = [
            "tipoDte"               => $encabezado["tipoDteOriginal"],
            "codigoGeneracion"      => $encabezado["codigoGeneracionOriginal"],
            "selloRecibido"         => $encabezado["selloRecibidoOriginal"],
            "numeroControl"         => $encabezado["id_doc"],
            "fecEmi"                => $encabezado["fecEmiOriginal"],
            "montoIva"              => round((float)($encabezado["total_iva"]), 2),
            "codigoGeneracionR"     => null,
            "tipoDocumento"         => "36",
            "numDocumento"          => $encabezado["numDocumento"],
            "nombre"                => $encabezado["nombre"],

        ];
            $documento["telefono"] = $cliente[0]->telefono;


            $documento["correo"] = $cliente[0]->correo;

       $motivo = [
            "tipoAnulacion"         => intval("2"),
            "motivoAnulacion"       => "Rescindir de la operacion realizada.",
            "nombreResponsable"     => $encabezado["nombre"],
            "tipDocResponsable"    => "13",
            "numDocResponsable"     => "04283072-6", ///$encabezado["docuEntrega"],
            "nombreSolicita"        => $cliente[0]->nombre,
            "tipDocSolicita"        => "36",
            "numDocSolicita"        => str_replace('-','', $cliente[0]->numDocumento)

       ];


        $comprobante["emisor"]      = $emisor;
        $comprobante["documento"]   = $documento;
        $comprobante["motivo"]      = $motivo;

        //$comprobante2 = [];
        //dd($comprobante);
        return ($comprobante);
    }
}

if (!function_exists('clq')) {
    function clq($comprobante_procesar, $uuid_generado)
    {
        $comprobante = [];

        //dd($comprobante_procesar);
        $encabezado = $comprobante_procesar["encabezado"][0];
        //dd($encabezado);
        $uuid = $uuid_generado;
        $numero_documento = CerosIzquierda($encabezado["identificacionNumeroControl"], 15);
        $tipo_documento = $encabezado["cod_tipo_documento"];
        $caja = $encabezado["cod_establecimiento"];// "0001";
        $tipo_establecimiento = CerosIzquierda($encabezado["tipo_establecimiento"], 4);
        $identificacion = [
            "version"           => intval($encabezado["identificacionVersion"]),
            "ambiente"          => $encabezado["nu_doc"],
            "tipoDte"           => $tipo_documento,
            "numeroControl"     => "DTE-" . $tipo_documento . "-" . $tipo_establecimiento . $caja . "-" . $numero_documento,
            "codigoGeneracion"  => $uuid,
            "tipoModelo"        => 1,
            "tipoOperacion"     => 1,
            "fecEmi"            => date('Y-m-d'), //"2022-07-20", // $encabezado["fecEmi"],    //Cambiar
            "horEmi"            => $encabezado["horEmi"],      //Cambiar
            "tipoMoneda"        => $encabezado["identificaciontipoMoneda"]            //Cambiar
        ];
        $comprobante = [
            "identificacion" => $identificacion
        ];

        $direccion_emisor = [
            "departamento"  => $encabezado["departamento_emisor"],
            "municipio"     => $encabezado["municipio_emisor"],
            "complemento"   => $encabezado["complemento_emisor"]
        ];

        $emisor = [
            "nit"                   => str_replace("-","",$encabezado["nit_emisor"]),
            "nrc"                   => str_replace("-", "",trim($encabezado["nrc_emisor"])),
            "nombre"                => $encabezado["nombre_empresa"],
            "codActividad"          => $encabezado["emisorCodActividad"],
            "descActividad"         => $encabezado["descActividad"],
            "nombreComercial"       => $encabezado["nombreComercial"],
            "tipoEstablecimiento"   => $encabezado["emisorEstablecimiento"],
            "direccion"             => $direccion_emisor,
            "telefono"              => $encabezado["telefono"],

            "codEstableMH"          => null,
            "codEstable"            => null,
            "codPuntoVentaMH"       => null,
            "codPuntoVenta"         => null,
            "correo"                => $encabezado["correo"],
        ];

        $tblReceptor = $comprobante_procesar["receptor"][0];
        //dd($tblReceptor);
        $direccion_receptor = [
            "departamento"  => $tblReceptor["direccionDepartamento"],
            "municipio"     => $tblReceptor["direccionMunicipio"],
            "complemento"   => $tblReceptor["direccionComplemento"]
        ];

        $receptor = [
            "nit"                   => str_replace("-", "",$tblReceptor["nit"]),
            "nrc"                   => str_replace("-","",trim($tblReceptor["nrc"])),
            "nombre"                => $tblReceptor["nombre"],
            "codActividad"          => $tblReceptor["codActividad"],
            "descActividad"         => $tblReceptor["descActividad"],
            "nombreComercial"       => $tblReceptor["nombreComercial"],
            "direccion"             => $direccion_receptor

        ];

        if ($encabezado["receptorTelefono"] != '') {
            $receptor["telefono"] = $encabezado["receptorTelefono"];
        }
        if ($encabezado["receptorCorreo"] != '') {
            $receptor["correo"] = $encabezado["receptorCorreo"];
        }

        $otrosDocumentos = null;

        $codigos_tributos = [];
        $i = 0;

        $cuerpo = $comprobante_procesar["cuerpoDocumento"];
        //dd($cuerpo[5]);
        $items_cuerpoDocumento = [];

        $tblCodigosTributos = $comprobante_procesar["resumenTributos"];

        //dd($tblCodigosTributos);
        foreach($tblCodigosTributos as $tt){
            $codigos_tributos = [
                "codigo"        =>  $tt["codigo"],
                "descripcion"   =>  $tt["descripcion"],
                "valor"         => (float)$tt["valor"]
            ];
        }
        //dd($codigos_tributos);

        foreach ($cuerpo as $item) {
            //dd($item);
            $codigo_tributo = $item["tributos"];
                        $properties_items_cuerpoDocumento = array();

            $properties_items_cuerpoDocumento = [
                "numItem"           => intval($item["numItem"]),
                "tipoDte"           =>$item["tipoDte"],
                "tipoGeneracion"    =>intval($item["tipoGeneracion"]),
                "numeroDocumento"   =>$item["numeroDocumento"],
                "fechaGeneracion"   =>$item["fechaGeneracion"],
                "ventaNoSuj"        => (float)($item["ventaNosuj"]),
                "ventaExenta"       => (float)($item["ventaExenta"]),
                "ventaGravada"      => (float)($item["ventaGravada"]),
                "exportaciones"     => (float)($item["exportaciones"]),
                "tributos"          => ($item["ventaGravada"] != 0) ? ["20"] : null,
                "ivaItem"           => (float)($item["ivaItem"]),
                "obsItem"          => $item["obsItem"],
                //"obsItem"           => null //$item["obsItem"]

            ];

            $items_cuerpoDocumento[] = $properties_items_cuerpoDocumento;
        }

        $cuerpoDocumento = $items_cuerpoDocumento;

        $properties_items_tributo_resumen = [
            "codigo"        => "",
            "descripcion"   => "",
            "valor"         => ""
        ];

        $tributos_resumen = [
            "properties"   => $properties_items_tributo_resumen
        ];



        // $codigos_tributos["valor"] = intval($codigos_tributos["valor"]/0.01);
        $tblResumen = $comprobante_procesar["resumen"][0];
        //dd($tblResumen);
        //dd(empty($codigos_tributos));
        //dd($codigo_tributos)>0);
        $resumen = [
            "totalNoSuj"            => (float)$tblResumen["totalNosuj"],
            "totalExenta"           => (float)$tblResumen["totalExenta"],
            "totalGravada"          => (float)$tblResumen["totalGravada"],
            "totalExportacion"      => (float)$tblResumen["totalExportacion"],
            "subTotalVentas"        => (float)$tblResumen["subTotalVentas"],
            "tributos"              => (empty($codigos_tributos))? null: [$codigos_tributos],
            "montoTotalOperacion"   => round((float)($tblResumen["montoTotalOperacion"]), 2), //(float)$tblResumen["montoTotalOperacion"],
            "ivaPerci"              => (float)$tblResumen["ivaPerci"],
            "total"                 => (float)$tblResumen["total"],
            "totalLetras"           => $tblResumen["totalLetras"],
            "condicionOperacion"    => (float)$tblResumen["condicionOperacion"],

        ];



        $es_mayor = ($tblResumen["total"] >= 11428.57);
        $tblExtension = $comprobante_procesar["extension"][0];
        //dd($tblExtension);
        $extension = [
            "nombEntrega"   => ($es_mayor) ? $tblExtension["nombreEntrega"] : null,
            "docuEntrega"   => ($es_mayor) ? $tblExtension["docuEntrega"] : null,
            "nombRecibe"    => ($es_mayor) ? $tblExtension["nombreRecibe"] : null,
            "docuRecibe"    => ($es_mayor) ? $tblExtension["docuRecibe"] : null,
            "observaciones" => ($es_mayor) ? $tblExtension["observaciones"] : null
        ];

        $apendice = null;


        $comprobante["emisor"]                   = $emisor;
        $comprobante["receptor"]                 = $receptor;

        $comprobante["cuerpoDocumento"]          = $cuerpoDocumento;
        $comprobante["resumen"]                  = $resumen;
        $comprobante["extension"]                = $extension;
        $comprobante["apendice"]                 = $apendice;

        //$comprobante2 = [];
        return ($comprobante);
    }
}

if (!function_exists('fex')) {
    function fex($comprobante_procesar, $uuid_generado)
    {
        $encabezado = $comprobante_procesar["encabezado"][0];
        //dd($comprobante_procesar);
        $cuerpo = $comprobante_procesar["detalle"];
        //dd($cuerpo);
        $uuid = $uuid_generado;
        $numero_documento = CerosIzquierda($encabezado["nu_doc"], 15);
        $tipo_documento = $encabezado["cod_tipo_documento"];
        $caja = $encabezado["cod_establecimiento"];// "0001";
        $tipo_establecimiento = CerosIzquierda($encabezado["tipo_establecimiento"], 4);
        $empresa = $comprobante_procesar["empresa"][0];
        $identificacion = [
            "version"           => intval($encabezado["version"]),
            "ambiente"          => $empresa["ambiente"],
            "tipoDte"           => $tipo_documento,
            "numeroControl"     => "DTE-" . $tipo_documento . "-" . $tipo_establecimiento . $caja . "-" . $numero_documento, //Cambiar
            "codigoGeneracion"  => $uuid,
            "tipoModelo"        => 1,
            "tipoOperacion"     => 1,
            "tipoContingencia"  => null,
            "motivoContigencia" => null,
            "fecEmi"            => date('Y-m-d'), // "2022-07-23", //$encabezado["fecEmi"],    //Cambiar
            "horEmi"            => $encabezado["horEmi"],      //Cambiar
            "tipoMoneda"        => "USD"            //Cambiar
        ];
        $comprobante = [
            "identificacion" => $identificacion
        ];



        $direccion_emisor = [
            "departamento"  => $encabezado["departamento_emisor"],
            "municipio"     => $encabezado["municipio_emisor"],
            "complemento"   => $encabezado["complemento_emisor"]
        ];

        $emisor = [
            "nit"                   => $encabezado["nit_emisor"],
            "nrc"                   => $encabezado["nrc_emisor"],
            "nombre"                => $encabezado["nombre_empresa"],
            "codActividad"          => $encabezado["codActividad"],
            "descActividad"         => $encabezado["descActividad"],
            "nombreComercial"       => $encabezado["nombreComercial"],
            "tipoEstablecimiento"   => $encabezado["tipo_establecimiento"],
            "direccion"             => $direccion_emisor,
            "telefono"              => $encabezado["telefono"],
            "correo"                => $encabezado["correo"],
            "codEstableMH"          => null,
            "codEstable"            => null,
            "codPuntoVentaMH"       => null,
            "codPuntoVenta"         => null,
            "tipoItemExpor"         => 2, //Cambiar
            "recintoFiscal"         => null, //Cambiar
            "regimen"               => null //Cambiar

        ];


        $receptor = [
            "nombre"                => $encabezado["nombre"],
            "tipoDocumento"         => $encabezado["tipoDocumento"] ,
            "numDocumento"          => $encabezado["numDocumento"],
            "descActividad"         => $encabezado["descActividad_receptor"],
            "nombreComercial"       => $encabezado["nombreComercial_receptor"],
            "codPais"               => $encabezado["codPais_receptor"], //cambiar
            "nombrePais"            => $encabezado["nomPais_receptor"], //cambiar
            "complemento"           => $encabezado["complemento_receptor"], //cambiar
            "tipoPersona"           => 2, //cambiar

        ];

        if ($encabezado["telefono_receptor"] != '') {
            $receptor["telefono"] = $encabezado["telefono_receptor"];
        }
        if ($encabezado["correo_receptor"] != '') {
            $receptor["correo"] = $encabezado["correo_receptor"];
        }

        $ventaTercero = null;
        if (isset($comprobante_procesar["terceros"][0])) {
            // dd($comprobante_procesar[2]);
            $ventaTercero = [
                "nit"       => $comprobante_procesar["terceros"][0]["nit"],
                "nombre"    => $comprobante_procesar["terceros"][0]["nombre"],

            ];
        }

        $otrosDocumentos = null;

        $codigos_tributos = [];
        $i = 0;

        foreach ($cuerpo as $item) {
            # code...
            //dd($item);
            $i += 1;
            $tributos_properties_items_cuerpoDocumento = array();

            if ($item["iva"] != 0 and count($codigos_tributos) == 0) {
                $codigos_tributos = [
                    "codigo"        =>  "20",
                    "descripcion"   =>  "Impuesto al Valor Agregado 13%",
                    "valor"         => (float)$item["iva"]
                ];
            } else {
                if ($item["iva"] != 0 and count($codigos_tributos) > 0) {
                    $iva =  $codigos_tributos["valor"] + $item["iva"];
                    $codigos_tributos["valor"] = $iva;
                }
            }

            $tributos_properties_items_cuerpoDocumento = ($item["iva"] != 0) ? "20" : "C3";

            $properties_items_cuerpoDocumento = array();

            $properties_items_cuerpoDocumento = [
                "numItem"           => $i,
                "cantidad"          => intval($item["cantidad"]),
                "codigo"            => $item["id_producto"],
                "uniMedida"         => intval($item["uniMedida"]),
                "descripcion"       => $item["descripcion"],
                "precioUni"         => round((float)($item["pre_unitario"]),2),
                "montoDescu"        => (float)0.00,
                "tributos"          => (is_null($item["tributos"]))? ["C3"] : explode(",", $item["tributos"]), //($item["gravado"] != 0) ? ["20"] : null,
                "ventaGravada"      => (float)($item["gravado"]),
                "noGravado"         => (float)$item["imp_int_det"]


            ];

            $items_cuerpoDocumento[] = $properties_items_cuerpoDocumento;
        }

        $cuerpoDocumento = $items_cuerpoDocumento;

        $properties_items_tributo_resumen = [
            "codigo"        => "",
            "descripcion"   => "",
            "valor"         => ""
        ];

        $tributos_resumen = [
            "properties"   => $properties_items_tributo_resumen
        ];

        $properties_items_pagos = [];
        //contado
        if ($encabezado["contado"] != 0) {
            $properties_items_pagos[] = [
                "codigo"        => "01",
                "montoPago"     => (float)$encabezado["contado"],
                "referencia"    => null,
                "plazo"         => null,
                "periodo"       => null
            ];
        }

        //credito
        if ($encabezado["credito"] != 0) {
            $properties_items_pagos[] = [
                "codigo"        => "13",
                "montoPago"     => (float)$encabezado["credito"],
                "referencia"    => "",
                "plazo"         => "01",
                "periodo"       => intval($encabezado["periodo"])
            ];
        }

        //tarjeta
        if ($encabezado["tarjeta"] != 0) {
            $properties_items_pagos[] = [
                "codigo"        => "03",
                "montoPago"     => (float)$encabezado["tarjeta"],
                "referencia"    => $encabezado["referencia_tarjeta"],
                "plazo"         => null,
                "periodo"       => null
            ];
        }
        /* if($encabezado["total_iva"] != 0){
        $codigos_tributos= [
            "codigo"        => "20",
            "descripcion"   => "Impuesto al Valor Agregado 13",
            "valor"         => (float)$encabezado["total_iva"]
        ];
        }*/

        $pagos = $properties_items_pagos;
        if (count($codigos_tributos) > 0) {
            if ($encabezado["tot_gravado"] * 0.13 <> $codigos_tributos["valor"]) {
                $codigos_tributos["valor"] = round($encabezado["tot_gravado"] * 0.13,  2);
                // $codigos_tributos["valor"] = bcdiv($encabezado["tot_gravado"] * 0.13,1,2);

            }
        }

        // $codigos_tributos["valor"] = intval($codigos_tributos["valor"]/0.01);

        $resumen = [

            "totalGravada"          => (float)$encabezado["tot_gravado"],
            "descuento"             => (float)$encabezado["totalDescu"],
            "porcentajeDescuento"   => (float)$encabezado["porcentajeDescuento"],
            "totalDescu"            => (float)$encabezado["totalDescu"],
            "seguro"                => (float)("0.00"),
            "flete"                 => (float)("0.00"),
            "montoTotalOperacion"   => round((float)($encabezado["subTotalVentas"] + $encabezado["total_iva"] +$encabezado["otrosTributos"]), 2), //(float)$encabezado["montoTotalOperacion"],
            "totalNoGravado"        => (float)$encabezado["totalNoGravado"],
            "totalPagar"            => (float)$encabezado["totalPagar"],
            "totalLetras"           => $encabezado["total_letras"],
            "condicionOperacion"    => (float)$encabezado["condicionOperacion"],
            "pagos"                 => $pagos,
            "codIncoterms"          => null,
            "descIncoterms"         => null,
            "numPagoElectronico"    => "",
            "observaciones"         => ""

        ];




        $apendice[] = [
            "campo"         => "vendedor",
            "etiqueta"      => "Vendedor",
            "valor"         => $encabezado["id_vendedor"]
        ];
        $apendice[] = [
            "campo"         => "cliente",
            "etiqueta"      => "Cliente",
            "valor"         => $encabezado["id_cliente"]
        ];




        $comprobante["emisor"]                   = $emisor;
        $comprobante["receptor"]                 = $receptor;
        $comprobante["otrosDocumentos"]          = $otrosDocumentos;
        $comprobante["ventaTercero"]             = $ventaTercero;
        $comprobante["cuerpoDocumento"]          = $cuerpoDocumento;
        $comprobante["resumen"]                  = $resumen;
        $comprobante["apendice"]                 = $apendice;
        //echo '<br>'. var_dump($comprobante) . '<br>';
        return ($comprobante);
    }
}

if (!function_exists('ncr')) {
    function ncr($comprobante_procesar, $uuid_generado)
    { //dd();
        $encabezado = $comprobante_procesar["documento"][0];
        $emisor_data = $comprobante_procesar["emisor"];
        $cliente = $comprobante_procesar["cliente"];
        $totales = $comprobante_procesar["totales"];
        $cuerpo =  $comprobante_procesar["detalle"];
        //dd($encabezado);
        $uuid = $uuid_generado;
        $numero_documento = CerosIzquierda($encabezado["nu_doc"], 15);
        $tipo_documento = $encabezado["tipodocumento"];
        $caja = "P001";
        //$tipo_establecimiento = CerosIzquierda($encabezado["tipo_establecimiento"], 4);
        $tipo_establecimiento = "M001";
        $empresa = $comprobante_procesar["emisor"];
        $identificacion = [
            "version"           => intval($encabezado["version"]),
            "ambiente"          => $encabezado["ambiente"],
            "tipoDte"           => $tipo_documento,
            "numeroControl"     => "DTE-" . $tipo_documento . "-" . $tipo_establecimiento . $caja . "-" . $numero_documento, //Cambiar
            "codigoGeneracion"  => $uuid,
            "tipoModelo"        => 1,
            "tipoOperacion"     => 1,
            "tipoContingencia"  => null,
            "motivoContin"      => null,
            "fecEmi"            => date('Y-m-d'), // "2022-07-23", //$encabezado["fecEmi"],    //Cambiar
            "horEmi"            => date("H:i:s"),      //Cambiar
            "tipoMoneda"        => "USD"            //Cambiar
        ];
        $comprobante = [
            "identificacion" => $identificacion
        ];
        $documentoRelacionado[] = [
            "tipoDocumento"     => $encabezado["tipoDteOriginal"],
            "tipoGeneracion"    => 2,
            "numeroDocumento"   => $encabezado["numeroOriginal"],
            "fechaEmision"      => $encabezado["fecEmiOriginal"],
        ];
        //dd($documentoRelacionado);
        $direccion_emisor = [
            "departamento"  => $emisor_data[0]->departamento,
            "municipio"     => $emisor_data[0]->municipio,
            "complemento"   => $emisor_data[0]->direccion
        ];

        $emisor = [
            "nit"                   => str_replace('-', '', $emisor_data[0]->nit),
            "nrc"                   => ltrim(str_replace('-', '', $emisor_data[0]->ncr), '0'),
            "nombre"                => trim($emisor_data[0]->nombre),
            "codActividad"          => $emisor_data[0]->codActividad,
            "descActividad"         => $emisor_data[0]->descActividad,
            "nombreComercial"       => $emisor_data[0]->nombreComercial,
            "tipoEstablecimiento"   => $emisor_data[0]->tipoEstablecimiento,
            "direccion"             => $direccion_emisor,
            "telefono"              => str_replace("-", "",$emisor_data[0]->telefono),

           // "codEstableMH"          => null,
            //"codEstable"            => null,
            //"codPuntoVentaMH"       => null,
            //"codPuntoVenta"         => null,
            "correo"                => $emisor_data[0]->correo,
        ];

        //dd($cliente);
        $direccion_receptor = [];
        $direccion_receptor = [
            "departamento"  => $cliente[0]->departamento,
            "municipio"     => $cliente[0]->municipio,
            "complemento"   => $cliente[0]->direccion_cliente
        ];
        if (!empty($cliente[0]->codActividad)) {
            if (strlen($cliente[0]->codActividad) <= 4)
            {$codeActivity = '0' . $cliente[0]->codActividad;}
            else{ $codeActivity = $cliente[0]->codActividad; }
        }
        $receptor = [
            "nit"                   => str_replace("-", "", $cliente[0]->nit),
            "nrc"                   => ltrim(str_replace("-", "", $cliente[0]->ncr), '0'),
            "nombre"                => $cliente[0]->nombre_cliente,
            "codActividad"          => $codeActivity,
            "descActividad"         => $cliente[0]->descActividad,
            "nombreComercial"       => $cliente[0]->nombre_comercial,
            "direccion"             => $direccion_receptor

        ];

        if ($cliente[0]->telefono_cliente != '') {
            $receptor["telefono"] = $cliente[0]->telefono_cliente;
        }
        if ($cliente[0]->email_cliente != '') {
            $receptor["correo"] = $cliente[0]->email_cliente;
        }
        //dd($receptor);
        $ventaTercero = null;
        if (isset($comprobante_procesar[3][0])) {
            // dd($comprobante_procesar[2]);
            $ventaTercero = [
                "nit"       => $comprobante_procesar[3][0]["nit"],
                "nombre"    => $comprobante_procesar[3][0]["nombre"],

            ];
        }

        $otrosDocumentos = null;

        $codigos_tributos = [];
        $i = 0;

        foreach ($cuerpo as $item) {
            # code...
            //dd($item);
            $i += 1;
            $tributos_properties_items_cuerpoDocumento = array();

            if ($item->iva != 0 and count($codigos_tributos) == 0) {
                $codigos_tributos = [
                    "codigo"        =>  "20",
                    "descripcion"   =>  "Impuesto al Valor Agregado 13%",
                    "valor"         => round((float)$item->iva, 2)
                ];
            } else {
                if ($item->iva != 0 and count($codigos_tributos) > 0) {
                    $iva = round((float)($codigos_tributos["valor"] + $item->iva), 2);
                    $codigos_tributos["valor"] = (float)$iva;
                }
            }

            $tributos_properties_items_cuerpoDocumento = ($item->iva != 0) ? "20" : "C3";

            $properties_items_cuerpoDocumento = array();

            $properties_items_cuerpoDocumento = [
                "numItem"           => $i,
                "tipoItem"          => intval("2"),  //Bienes y Servicios
                "numeroDocumento"   => $encabezado["numeroOriginal"],
                "cantidad"          => intval($item->cantidad),
                "codigo"            => "P0".$item->id_producto,
                "codTributo"        => null,
                "uniMedida"         => 99,
                "descripcion"       => $item->descripcion,
                "precioUni"         => (float)($item->precio_unitario),
                "montoDescu"        => 0.00,
                "ventaNoSuj"        => (float)($item->descuento),
                "ventaExenta"       => (float)($item->exentas),
                "ventaGravada"      => round((float)($item->gravadas),2),
                "tributos"          => ($item->gravadas != 0) ? ["20"] : null,
                //"psv"               => (float)"0.00",
                //"noGravado"         => (float)$item["no_imponible"]
            ];

            $items_cuerpoDocumento[] = $properties_items_cuerpoDocumento;
        }
        $cuerpoDocumento = $items_cuerpoDocumento;

        $properties_items_tributo_resumen = [
            "codigo"        => "",
            "descripcion"   => "",
            "valor"         => ""
        ];

        $tributos_resumen = [
            "properties"   => $properties_items_tributo_resumen
        ];
       // dd($cuerpoDocumento);
        $properties_items_pagos = [];
        //contado
        //dd($encabezado);
        if ($encabezado["condiciones"] == 1) {
            $properties_items_pagos[] = [
                "codigo"        => "01",
                "montoPago"     => round((float)($encabezado["totalPagar"]- $encabezado["reteRenta"]),2),
                "referencia"    => null,
                "plazo"         => null,
                "periodo"       => null
            ];
        }

        //credito
        if ($encabezado["condiciones"] == 2) {
            $properties_items_pagos[] = [
                "codigo"        => "13",
                "montoPago"     => (float)"0.00",
                "referencia"    => "Credito",
                "plazo"         => "01",
                "periodo"       => 15
            ];
        }

        //tarjeta
        if ($encabezado["condiciones"] == 3) {
            $properties_items_pagos[] = [
                "codigo"        => "03",
                "montoPago"     => round((float)$totales["totalPagar"],2),
                "referencia"    => $totales["creditcardautorization"],
                "plazo"         => null,
                "periodo"       => null
            ];
        }
        /* if($encabezado["total_iva"] != 0){
        $codigos_tributos= [
            "codigo"        => "20",
            "descripcion"   => "Impuesto al Valor Agregado 13",
            "valor"         => (float)$encabezado["total_iva"]
        ];
        }*/
        //dd($properties_items_pagos);
        $pagos = $properties_items_pagos;
        if (count($codigos_tributos) > 0) {
            if ($encabezado["tot_gravado"] * 0.13 <> $codigos_tributos["valor"]) {
                $codigos_tributos["valor"] = round($encabezado["tot_gravado"] * 0.13,  2);
                // $codigos_tributos["valor"] = bcdiv($encabezado["tot_gravado"] * 0.13,1,2);

            }
        }
        //dd($codigos_tributos);
        // $codigos_tributos["valor"] = intval($codigos_tributos["valor"]/0.01);

        $resumen = [
            "totalNoSuj"            => round((float)$encabezado["tot_nosujeto"],2),
            "totalExenta"           => round((float)$encabezado["tot_exento"],2),
            "totalGravada"          => round((float)$encabezado["tot_gravado"],2),
            "subTotalVentas"        => round((float)$encabezado["subTotalVentas"],2),
            "descuNoSuj"            => round((float)$encabezado["descuNoSuj"],2),
            "descuExenta"           => round((float)$encabezado["descuExenta"],2),
            "descuGravada"          => round((float)$encabezado["descuGravada"],2),
            // "porcentajeDescuento"   => (float)$encabezado["porcentajeDescuento"],
            "totalDescu"            => round((float)$encabezado["totalDescu"],2),
            "tributos"              => [$codigos_tributos],
            "subTotal"              => round((float)$encabezado["subTotal"],2),
            "ivaPerci1"             => (float)$encabezado["ivaPerci1"],
            "ivaRete1"              => (float)$encabezado["ivaRete1"],
            "reteRenta"             => round((float)$encabezado["reteRenta"],2),
            "montoTotalOperacion"   => round((float)($encabezado["subTotalVentas"] + $encabezado["total_iva"]), 2), //(float)$encabezado["montoTotalOperacion"],
            //"totalNoGravado"        => (float)$encabezado["totalNoGravado"],
            //"totalPagar"            => (float)$encabezado["totalPagar"],
            "totalLetras"           => $encabezado["total_letras"],
            //"saldoFavor"            => (float)$encabezado["saldoFavor"],
            "condicionOperacion"    => (float)$encabezado["condiciones"],
            //"pagos"                 => $pagos,
            //"numPagoElectronico"    => ""
        ];

        //dd($resumen);
        //11428.57
        $es_mayor = ($encabezado["totalPagar"] >= 11428.57);
        $extension = [
            "nombEntrega"   => ($es_mayor) ? $encabezado["NombreUsuario"] : null,
            "docuEntrega"   => ($es_mayor) ? str_replace("-", "", $encabezado["docUser"]) : null,
            "nombRecibe"    => ($es_mayor) ? $cliente[0]->nombre_cliente : null,
            "docuRecibe"    => ($es_mayor) ? str_replace("-", "", $cliente[0]->nit) : null,
            "observaciones" => ($es_mayor) ? null : null,
            //"placaVehiculo" => ($es_mayor) ? null : null
            // "placaVehiculo" => ($es_mayor) ? $encabezado["placaVehiculo"] : null
        ];


        $apendice[] = [
            "campo"         => "cliente",
            "etiqueta"      => "Cliente",
            "valor"         => "".$cliente[0]->id_cliente
        ];


        $comprobante["documentoRelacionado"]     = $documentoRelacionado;
        $comprobante["emisor"]                   = $emisor;
        $comprobante["receptor"]                 = $receptor;
        //$comprobante["otrosDocumentos"]          = $otrosDocumentos;
        $comprobante["ventaTercero"]             = $ventaTercero;
        $comprobante["cuerpoDocumento"]          = $cuerpoDocumento;
        $comprobante["resumen"]                  = $resumen;
        $comprobante["extension"]                = $extension;
        $comprobante["apendice"]                 = $apendice;
        //echo '<br>'. var_dump($comprobante) . '<br>';

        //dd($comprobante);
        return ($comprobante);
    }
}

if (!function_exists('fse')) {
    function fse($comprobante_procesar, $uuid_generado)
    {

        $comprobante = [];
        $encabezado = $comprobante_procesar["documento"][0];
        $emisor_data = $comprobante_procesar["emisor"];
        $cliente = $comprobante_procesar["cliente"];
        $cuerpo = $comprobante_procesar["detalle"];
        $totales = $comprobante_procesar["totales"];
        //dd($cuerpo);
        $uuid = $uuid_generado;
        $numero_documento = CerosIzquierda($encabezado->actual, 15);
        $tipo_documento = $encabezado->tipodocumento;
        $caja = "P001";
        $testablecimiento = "1";
        //$tipo_establecimiento = CerosIzquierda($testablecimiento,4);
        $tipo_establecimiento = "M001";
        //$tipo_establecimiento = CerosIzquierda($testablecimiento,4);
        $empresa = $comprobante_procesar["emisor"];

        $identificacion = [
            "version"           => intval($encabezado->versionJson),
            "ambiente"          => $encabezado->ambiente,
            "tipoDte"           => $tipo_documento,
            "numeroControl"     => "DTE-" . $tipo_documento . "-" . $tipo_establecimiento . $caja . "-" . $numero_documento, //Cambiar
            "codigoGeneracion"  => $uuid,
            "tipoModelo"        => 1,
            "tipoOperacion"     => 1,
            "tipoContingencia"  => null,
            "motivoContin"      => null,
            "fecEmi"            => date('Y-m-d'), //"2022-07-20", // $encabezado["fecEmi"],    //Cambiar
            "horEmi"            => date("H:i:s"),      //Cambiar
            "tipoMoneda"        => "USD"            //Cambiar
        ];
        $comprobante = [
            "identificacion" => $identificacion
        ];

        $documentoRelacionado = null;

        $direccion_emisor = [
            "departamento"  => $emisor_data[0]->departamento,
            "municipio"     => $emisor_data[0]->municipio,
            "complemento"   => $emisor_data[0]->direccion
        ];
        $emisor = [
            "nit"                   => str_replace('-','', $emisor_data[0]->nit),
            "nrc"                   => ltrim(str_replace('-','', $emisor_data[0]->ncr), '0'),
            "nombre"                => trim($emisor_data[0]->nombre),
            "codActividad"          => $emisor_data[0]->codActividad,
            "descActividad"         => $emisor_data[0]->descActividad,
            "direccion"             => $direccion_emisor,
            "telefono"              => $emisor_data[0]->telefono,
            "codEstableMH"          => $emisor_data[0]->codEstableMH,
            "codEstable"            => $emisor_data[0]->codEstable,
            "codPuntoVentaMH"       => $emisor_data[0]->codPuntoVentaMH,
            "codPuntoVenta"         => $emisor_data[0]->codPuntoVenta,
            "correo"                => $emisor_data[0]->correo,
        ];


        $direccion_sujetoExcluido = [
            "departamento"  => $cliente[0]->departamento,
            "municipio"     => $cliente[0]->municipio,
            "complemento"   => $cliente[0]->direccion
        ];
        if (!empty($cliente[0]->codActividad)) {
            if (strlen($cliente[0]->codActividad) <= 4)
            {$codeActivity = '0' . $cliente[0]->codActividad;}
            else{ $codeActivity = $cliente[0]->codActividad; }
        }
        //dd($cliente);
        $sujetoExcluido = [
            "tipoDocumento"         => ($cliente[0]->ncr == '' or is_null($cliente[0]->ncr) ) ? ($cliente[0]->tipoDocumento ?: null) : "36",
            "numDocumento"          => ($cliente[0]->ncr == '' or is_null($cliente[0]->ncr)) ? (str_replace("-","",$cliente[0]->numDocumento) ?: null) : (str_replace("-", "", $cliente[0]->nit) ?: null),
            "nombre"                => $cliente[0]->nombre ?: null,
            "codActividad"          => ($cliente[0]->codActividad == '' or $cliente[0]->codActividad == '0000') ? null : ($codeActivity ?: null),
            "descActividad"         => ($cliente[0]->descActividad == '' or $cliente[0]->codActividad == '0000') ? null : ($cliente[0]->descActividad ?: null),
            "direccion"             => $direccion_sujetoExcluido ?: null,
            "telefono"              => $cliente[0]->telefono ?: null,
            "correo"                => $cliente[0]->correo ?: null
        ];
        if ($cliente[0]->telefono != '') {
            $receptor["telefono"] = $cliente[0]->telefono;
        }
        if ($cliente[0]->correo != '') {
            $receptor["correo"] = $cliente[0]->correo;
        }


        $codigos_tributos = [];
        $i = 0;
        //var_dump($cuerpo);
        //echo '<br>';
        foreach ($cuerpo as $item) {
            //var_dump($item);
            // echo '<br>';
            $i += 1;
            // echo $i.'<br>';
            # code...
            //dd($item);
            $tributos_properties_items_cuerpoDocumento = array();

            $tributos_properties_items_cuerpoDocumento = ($item->iva != 0) ? "20" : "C3";

            $properties_items_cuerpoDocumento = array();
            $iva_calculadofac = round(($item->iva/$item->cantidad),2);
            $properties_items_cuerpoDocumento = [
                "numItem"           => $i, //intval($item["corr"]),
                "tipoItem"          => intval("2"),
                //"numeroDocumento"   => null,
                "cantidad"          => intval($item->cantidad),
                "codigo"            => "P0".$item->id_producto,
                "uniMedida"         => 99,
                "descripcion"       => $item->descripcion,
                "precioUni"         => round((float)($item->precio_unitario + $iva_calculadofac), 2),
                "montoDescu"        => 0.00,
                "compra"            => round((float)($item->gravadas + $item->iva), 2),
            ];

            $items_cuerpoDocumento[] = $properties_items_cuerpoDocumento;
        }

        $cuerpoDocumento = $items_cuerpoDocumento;

        $properties_items_tributo_resumen = [
            "codigo"        => "",
            "descripcion"   => "",
            "valor"         => ""
        ];

        $tributos_resumen = [
            "properties"   => $properties_items_tributo_resumen
        ];



        $properties_items_pagos = [];
         //contado
        //dd($totales);
        if ($totales["condicionOperacion"] == "01") {
            $properties_items_pagos[] = [
                "codigo"        => "01",
                "montoPago"     => round((float)($totales["totalPagar"]),2),
                "referencia"    => null,
                "plazo"         => null,
                "periodo"       => null
            ];
        }
        //credito
        if ($totales["condicionOperacion"] == "02") {
            $properties_items_pagos[] = [
                "codigo"        => "13",
                "montoPago"     => (float)"0.00",
                "referencia"    => "Credito",
                "plazo"         => "01",
                "periodo"       =>  15 //intval($encabezado["periodo"])
            ];
        }
        //tarjeta
        if ($totales["condicionOperacion"] == "03") {
            $properties_items_pagos[] = [
                "codigo"        => "03",
                "montoPago"     => round((float)($totales["totalPagar"]),2),
                "referencia"    => $totales["creditcardautorization"],
                "plazo"         => null,
                "periodo"       => null
            ];
        }


        $pagos = $properties_items_pagos;

        $resumen = [

            "totalCompra"           => round((float)($totales["subTotal"] + $totales["totalIva"]), 2),
            "descu"                 => (float)$totales["descuNoSuj"],
            "totalDescu"            => (float)$totales["totalDescu"],
            "subTotal"              => round((float)($totales["subTotal"] + $totales["totalIva"]), 2),
            "ivaRete1"              => (float)$totales["ivaRete1"],
            "reteRenta"             => round((float)$totales["reteRenta"],2),
            "totalPagar"            =>  round((float)($totales["totalPagar"]),2),
            "totalLetras"           => $totales["totalLetras"],
            //"saldoFavor"            => (float)$totales["saldoFavor"],
            "condicionOperacion"    => (float)$totales["condicionOperacion"],
            "pagos"                 => $pagos,
            "observaciones"    => ""
        ];


        $apendice[] = [
            "campo"         => "vendedor",
            "etiqueta"      => "Vendedor",
            "valor"         => "".$encabezado->hechopor
        ];
        $apendice[] = [
            "campo"         => "cliente",
            "etiqueta"      => "Cliente",
            "valor"         => "".$cliente[0]->idcliente
        ];


        //$comprobante["documentoRelacionado"]     = $documentoRelacionado;
        $comprobante["emisor"]                   = $emisor;
        $comprobante["sujetoExcluido"]           = $sujetoExcluido;
        $comprobante["cuerpoDocumento"]          = $cuerpoDocumento;
        $comprobante["resumen"]                  = $resumen;
        $comprobante["apendice"]                 = $apendice;
        //$comprobante2 = [];
        //dd($comprobante);
        return ($comprobante);
    }
}

if (!function_exists('ndb')) {
    function ndb($comprobante_procesar, $uuid_generado)
    {
        //dd($comprobante_procesar);
        $encabezado = $comprobante_procesar["encabezado"][0];
        //dd($encabezado);
        $cuerpo = $comprobante_procesar["detalle"];
        //dd($cuerpo);
        $uuid = $uuid_generado;
        $numero_documento = CerosIzquierda($encabezado["nu_doc"], 15);
        $tipo_documento = $encabezado["cod_tipo_documento"];
        $caja = $encabezado["cod_establecimiento"];; // "0001";
        $tipo_establecimiento = CerosIzquierda($encabezado["tipo_establecimiento"], 4);
        $empresa = $comprobante_procesar["empresa"][0];
        $identificacion = [
            "version"           => intval($encabezado["version"]),
            "ambiente"          => $empresa["ambiente"],
            "tipoDte"           => $tipo_documento,
            "numeroControl"     => "DTE-" . $tipo_documento . "-" . $tipo_establecimiento . $caja . "-" . $numero_documento, //Cambiar
            "codigoGeneracion"  => $uuid,
            "tipoModelo"        => 1,
            "tipoOperacion"     => 1,
            "tipoContingencia"  => null,
            "motivoContin"      => null,
            "fecEmi"            => date('Y-m-d'), // "2022-07-23", //$encabezado["fecEmi"],    //Cambiar
            "horEmi"            => $encabezado["horEmi"],      //Cambiar
            "tipoMoneda"        => "USD"            //Cambiar
        ];
        $comprobante = [
            "identificacion" => $identificacion
        ];
        //dd($comprobante);
        $documentoRelacionado = null;
        $docRelacionado = (isset($comprobante_procesar["Documentosrelacionados"]))? $comprobante_procesar["Documentosrelacionados"]: [];
        //dd($docRelacionado);
        foreach ($docRelacionado as $dr) {
            $documentoRelacionado[] = [
                "tipoDocumento"     =>  $dr["tipoDodocumento"],
                "tipoGeneracion"    => intval($dr["tipoGeneracion"]),
                "numeroDocumento"   => $dr["nuDocRelacionado"],
                "fechaEmision"       => $dr["fechaEmision"],
            ];
        }
        //dd($documentoRelacionado);

        $direccion_emisor = [
            "departamento"  => $encabezado["departamento_emisor"],
            "municipio"     => $encabezado["municipio_emisor"],
            "complemento"   => $encabezado["complemento_emisor"]
        ];

        $emisor = [
            "nit"                   => $encabezado["nit_emisor"],
            "nrc"                   => $encabezado["nrc_emisor"],
            "nombre"                => $encabezado["nombre_empresa"],
            "codActividad"          => $encabezado["codActividad"],
            "descActividad"         => $encabezado["descActividad"],
            "nombreComercial"       => $encabezado["nombreComercial"],
            "tipoEstablecimiento"   => $encabezado["tipo_establecimiento"],
            "direccion"             => $direccion_emisor,
            "telefono"              => $encabezado["telefono"],
            "correo"                => $encabezado["correo"]

        ];
        //dd($emisor);
        $direccion_receptor = [
            "departamento"  => $encabezado["departamento_receptor"],
            "municipio"     => $encabezado["municipio_receptor"],
            "complemento"   => $encabezado["complemento_receptor"]
        ];

        $receptor = [
            "nit"                   => $encabezado["nit_receptor"],
            "nrc"                   => $encabezado["nrc_receptor"],
            "nombre"                => $encabezado["nombre"],
            "codActividad"          => $encabezado["codActividad_receptor"],
            "descActividad"         => $encabezado["descActividad_receptor"],
            "nombreComercial"       => $encabezado["nombreComercial_receptor"],
            "direccion"             => $direccion_receptor

        ];

        if ($encabezado["telefono_receptor"] != '') {
            $receptor["telefono"] = $encabezado["telefono_receptor"];
        }
        if ($encabezado["correo_receptor"] != '') {
            $receptor["correo"] = $encabezado["correo_receptor"];
        }
        //dd($receptor);
        $ventaTercero = null;

        if (isset($comprobante_procesar["terceros"][0])) {
            // dd($comprobante_procesar[2]);
            $ventaTercero = [
                "nit"       => $comprobante_procesar["terceros"][0]["nit"],
                "nombre"    => $comprobante_procesar["terceros"][0]["nombre"],

            ];
        }



        $codigos_tributos = [];
        $i = 0;

        foreach ($cuerpo as $item) {
            # code...
            //dd($item);
            $i += 1;
            $tributos_properties_items_cuerpoDocumento = array();

            if ($item["iva"] != 0 and count($codigos_tributos) == 0) {
                $codigos_tributos = [
                    "codigo"        =>  "20",
                    "descripcion"   =>  "Impuesto al Valor Agregado 13%",
                    "valor"         => (float)$item["iva"]
                ];
            } else {
                if ($item["iva"] != 0 and count($codigos_tributos) > 0) {
                    $iva =  $codigos_tributos["valor"] + $item["iva"];
                    $codigos_tributos["valor"] = $iva;
                }
            }

            $tributos_properties_items_cuerpoDocumento = ($item["iva"] != 0) ? "20" : "C3";

            $properties_items_cuerpoDocumento = array();

            $properties_items_cuerpoDocumento = [
                "numItem"           => $i,
                "tipoItem"          => intval($item["tipoItem"]),  //Bienes y Servicios
                "numeroDocumento"   => $item["nuDocRelacionado"],
                "cantidad"          => intval($item["cantidad"]),
                "codigo"            => $item["id_producto"],
                "codTributo"        => null,
                "uniMedida"         => intval($item["uniMedida"]),
                "descripcion"       => $item["descripcion"],
                "precioUni"         => (float)($item["pre_unitario"]),
                "montoDescu"        => 0.00,
                "ventaNoSuj"        => (float)($item["no_sujetas"]),
                "ventaExenta"       => (float)($item["excento"]),
                "ventaGravada"      => (float)($item["gravado"]),
                "tributos"          => ($item["gravado"] != 0) ? ["20"] : null,
               // "noGravado"         => (float)$item["imp_int_det"]
            ];

            $items_cuerpoDocumento[] = $properties_items_cuerpoDocumento;
        }

        $cuerpoDocumento = $items_cuerpoDocumento;
        //dd($cuerpoDocumento);
        $properties_items_tributo_resumen = [
            "codigo"        => "",
            "descripcion"   => "",
            "valor"         => ""
        ];

        $tributos_resumen = [
            "properties"   => $properties_items_tributo_resumen
        ];

        $properties_items_pagos = [];
        //contado
        if ($encabezado["contado"] != 0) {
            $properties_items_pagos[] = [
                "codigo"        => "01",
                "montoPago"     => (float)$encabezado["contado"],
                "referencia"    => null,
                "plazo"         => null,
                "periodo"       => null
            ];
        }

        //credito
        if ($encabezado["credito"] != 0) {
            $properties_items_pagos[] = [
                "codigo"        => "13",
                "montoPago"     => (float)$encabezado["credito"],
                "referencia"    => "",
                "plazo"         => "01",
                "periodo"       => intval($encabezado["periodo"])
            ];
        }

        //tarjeta
        if ($encabezado["tarjeta"] != 0) {
            $properties_items_pagos[] = [
                "codigo"        => "03",
                "montoPago"     => (float)$encabezado["tarjeta"],
                "referencia"    => $totales["creditcardautorization"],
                "plazo"         => null,
                "periodo"       => null
            ];
        }
        /* if($encabezado["total_iva"] != 0){
        $codigos_tributos= [
            "codigo"        => "20",
            "descripcion"   => "Impuesto al Valor Agregado 13",
            "valor"         => (float)$encabezado["total_iva"]
        ];
        }*/

        $pagos = $properties_items_pagos;
        if (count($codigos_tributos) > 0) {
            if ($encabezado["tot_gravado"] * 0.13 <> $codigos_tributos["valor"]) {
                $codigos_tributos["valor"] = round($encabezado["tot_gravado"] * 0.13,  2);
                // $codigos_tributos["valor"] = bcdiv($encabezado["tot_gravado"] * 0.13,1,2);

            }
        }

        // $codigos_tributos["valor"] = intval($codigos_tributos["valor"]/0.01);

        $resumen = [
            "totalNoSuj"            => (float)$encabezado["tot_nosujeto"],
            "totalExenta"           => (float)$encabezado["tot_exento"],
            "totalGravada"          => (float)$encabezado["tot_gravado"],
            "subTotalVentas"        => (float)$encabezado["subTotalVentas"],
            "descuNoSuj"            => (float)$encabezado["descuNoSuj"],
            "descuExenta"           => (float)$encabezado["descuExenta"],
            "descuGravada"          => (float)$encabezado["descuGravada"],
           // "porcentajeDescuento"   => (float)$encabezado["porcentajeDescuento"],
            "totalDescu"            => (float)$encabezado["totalDescu"],
            "tributos"              => [$codigos_tributos],
            "subTotal"              => (float)$encabezado["subTotal"],
            "ivaPerci1"             => (float)$encabezado["ivaPerci1"],
            "ivaRete1"              => (float)$encabezado["ivaRete1"],
            "reteRenta"             => round((float)$encabezado["reteRenta"],2),
            "montoTotalOperacion"   => round((float)($encabezado["subTotalVentas"] + $encabezado["total_iva"]), 2), //(float)$encabezado["montoTotalOperacion"],
            //"totalNoGravado"        => (float)$encabezado["totalNoGravado"],
            //"totalPagar"            => (float)$encabezado["totalPagar"],
            "totalLetras"           => $encabezado["total_letras"],
            //"saldoFavor"            => (float)$encabezado["saldoFavor"],
            "condicionOperacion"    => intval($encabezado["condicionOperacion"]),
            //"pagos"                 => $pagos,
            "numPagoElectronico"    => ""
        ];



        $es_mayor = ($encabezado["totalPagar"] >= 11428.57);

        $extension = [
            "nombEntrega"   => ($es_mayor) ? $encabezado["nombEntrega"] : null,
            "docuEntrega"   => ($es_mayor) ? $encabezado["docuEntrega"] : null,
            "nombRecibe"    => ($es_mayor) ? $encabezado["nombRecibe"] : null,
            "docuRecibe"    => ($es_mayor) ? $encabezado["docuRecibe"] : null,
            "observaciones" => ($es_mayor) ? $encabezado["observaciones"] : null,
           // "placaVehiculo" => ($es_mayor) ? $encabezado["placaVehiculo"] : null
        ];

        $apendice[] = [
            "campo"         => "vendedor",
            "etiqueta"      => "Vendedor",
            "valor"         => $encabezado["id_vendedor"]
        ];
        $apendice[] = [
            "campo"         => "cliente",
            "etiqueta"      => "Cliente",
            "valor"         => $encabezado["id_cliente"]
        ];



        $comprobante["documentoRelacionado"]     = $documentoRelacionado;
        $comprobante["emisor"]                   = $emisor;
        $comprobante["receptor"]                 = $receptor;
        $comprobante["ventaTercero"]             = $ventaTercero;
        $comprobante["cuerpoDocumento"]          = $cuerpoDocumento;
        $comprobante["resumen"]                  = $resumen;
        $comprobante["extension"]                = $extension;
        $comprobante["apendice"]                 = $apendice;
        //echo '<br>'. var_dump($comprobante) . '<br>';

        //dd($comprobante);
        return ($comprobante);
    }
}
if (! function_exists('subfijo')) {
    function subfijo($xx)
    { // esta función regresa un subfijo para la cifra
        $xx = trim($xx);
        $xstrlen = strlen($xx);
        if ($xstrlen == 1 || $xstrlen == 2 || $xstrlen == 3)
            $xsub = "";
        //
        if ($xstrlen == 4 || $xstrlen == 5 || $xstrlen == 6)
            $xsub = "MIL";
        //
        return $xsub;
    }
}
if (!function_exists('numeroletras')) {
    function numeroletras($xcifra)
    {
        $xarray = array(
            0 => "Cero",
            1 => "UNO", "DOS", "TRES", "CUATRO", "CINCO", "SEIS", "SIETE", "OCHO", "NUEVE",
            "DIEZ", "ONCE", "DOCE", "TRECE", "CATORCE", "QUINCE", "DIECISEIS", "DIECISIETE", "DIECIOCHO", "DIECINUEVE",
            "VEINTI", 30 => "TREINTA", 40 => "CUARENTA", 50 => "CINCUENTA", 60 => "SESENTA", 70 => "SETENTA", 80 => "OCHENTA", 90 => "NOVENTA",
            100 => "CIENTO", 200 => "DOSCIENTOS", 300 => "TRESCIENTOS", 400 => "CUATROCIENTOS", 500 => "QUINIENTOS", 600 => "SEISCIENTOS", 700 => "SETECIENTOS", 800 => "OCHOCIENTOS", 900 => "NOVECIENTOS"
        );
        //
        $xcifra = trim($xcifra);
        $xlength = strlen($xcifra);
        $xpos_punto = strpos($xcifra, ".");
        $xaux_int = $xcifra;
        $xdecimales = "00";
        if (!($xpos_punto === false)) {
            if ($xpos_punto == 0) {
                $xcifra = "0" . $xcifra;
                $xpos_punto = strpos($xcifra, ".");
            }
            $xaux_int = substr($xcifra, 0, $xpos_punto); // obtengo el entero de la cifra a covertir
            $xdecimales = substr($xcifra . "00", $xpos_punto + 1, 2); // obtengo los valores decimales
        }

        $XAUX = str_pad($xaux_int, 18, " ", STR_PAD_LEFT); // ajusto la longitud de la cifra, para que sea divisible por centenas de miles (grupos de 6)
        $xcadena = "";
        for ($xz = 0; $xz < 3; $xz++) {
            $xaux = substr($XAUX, $xz * 6, 6);
            $xi = 0;
            $xlimite = 6; // inicializo el contador de centenas xi y establezco el límite a 6 dígitos en la parte entera
            $xexit = true; // bandera para controlar el ciclo del While
            while ($xexit) {
                if ($xi == $xlimite) { // si ya llegó al límite máximo de enteros
                    break; // termina el ciclo
                }

                $x3digitos = ($xlimite - $xi) * -1; // comienzo con los tres primeros digitos de la cifra, comenzando por la izquierda
                $xaux = substr($xaux, $x3digitos, abs($x3digitos)); // obtengo la centena (los tres dígitos)
                for ($xy = 1; $xy < 4; $xy++) { // ciclo para revisar centenas, decenas y unidades, en ese orden
                    switch ($xy) {
                        case 1: // checa las centenas
                            if (substr($xaux, 0, 3) < 100) { // si el grupo de tres dígitos es menor a una centena ( < 99) no hace nada y pasa a revisar las decenas

                            } else {
                                $key = (int) substr($xaux, 0, 3);
                                if (TRUE === array_key_exists($key, $xarray)) {  // busco si la centena es número redondo (100, 200, 300, 400, etc..)
                                    $xseek = $xarray[$key];
                                    $xsub = subfijo($xaux); // devuelve el subfijo correspondiente (Millón, Millones, Mil o nada)
                                    if (substr($xaux, 0, 3) == 100)
                                        $xcadena = " " . $xcadena . " CIEN " . $xsub;
                                    else
                                        $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
                                    $xy = 3; // la centena fue redonda, entonces termino el ciclo del for y ya no reviso decenas ni unidades
                                } else { // entra aquí si la centena no fue numero redondo (101, 253, 120, 980, etc.)
                                    $key = (int) substr($xaux, 0, 1) * 100;
                                    $xseek = $xarray[$key]; // toma el primer caracter de la centena y lo multiplica por cien y lo busca en el arreglo (para que busque 100,200,300, etc)
                                    $xcadena = " " . $xcadena . " " . $xseek;
                                } // ENDIF ($xseek)
                            } // ENDIF (substr($xaux, 0, 3) < 100)
                            break;
                        case 2: // checa las decenas (con la misma lógica que las centenas)
                            if (substr($xaux, 1, 2) < 10) {
                            } else {
                                $key = (int) substr($xaux, 1, 2);
                                if (TRUE === array_key_exists($key, $xarray)) {
                                    $xseek = $xarray[$key];
                                    $xsub = subfijo($xaux);
                                    if (substr($xaux, 1, 2) == 20)
                                        $xcadena = " " . $xcadena . " VEINTE " . $xsub;
                                    else
                                        $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
                                    $xy = 3;
                                } else {
                                    $key = (int) substr($xaux, 1, 1) * 10;
                                    $xseek = $xarray[$key];
                                    if (20 == substr($xaux, 1, 1) * 10)
                                        $xcadena = " " . $xcadena . " " . $xseek;
                                    else
                                        $xcadena = " " . $xcadena . " " . $xseek . " Y ";
                                } // ENDIF ($xseek)
                            } // ENDIF (substr($xaux, 1, 2) < 10)
                            break;
                        case 3: // checa las unidades
                            if (substr($xaux, 2, 1) < 1) { // si la unidad es cero, ya no hace nada

                            } else {
                                $key = (int) substr($xaux, 2, 1);
                                $xseek = $xarray[$key]; // obtengo directamente el valor de la unidad (del uno al nueve)
                                $xsub = subfijo($xaux);
                                $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
                            } // ENDIF (substr($xaux, 2, 1) < 1)
                            break;
                    } // END SWITCH
                } // END FOR
                $xi = $xi + 3;
            } // ENDDO

            if (substr(trim($xcadena), -5, 5) == "ILLON") // si la cadena obtenida termina en MILLON o BILLON, entonces le agrega al final la conjuncion DE
            $xcadena .= " DE";

            if (substr(trim($xcadena), -7, 7) == "ILLONES") // si la cadena obtenida en MILLONES o BILLONES, entoncea le agrega al final la conjuncion DE
            $xcadena .= " DE";

            // ----------- esta línea la puedes cambiar de acuerdo a tus necesidades o a tu país -------
            if (trim($xaux) != "") {
                switch ($xz) {
                    case 0:
                        if (trim(substr($XAUX, $xz * 6, 6)) == "1")
                            $xcadena .= "UN BILLON ";
                        else
                            $xcadena .= " BILLONES ";
                        break;
                    case 1:
                        if (trim(substr($XAUX, $xz * 6, 6)) == "1")
                            $xcadena .= "UN MILLON ";
                        else
                            $xcadena .= " MILLONES ";
                        break;
                    case 2:

                            $xcadena .= ""; //

                        break;
                } // endswitch ($xz)
            } // ENDIF (trim($xaux) != "")
            // ------------------      en este caso, para México se usa esta leyenda     ----------------
            $xcadena = str_replace("VEINTI ", "VEINTI", $xcadena); // quito el espacio para el VEINTI, para que quede: VEINTICUATRO, VEINTIUN, VEINTIDOS, etc
            $xcadena = str_replace("  ", " ", $xcadena); // quito espacios dobles
            $xcadena = str_replace("UN UN", "UN", $xcadena); // quito la duplicidad
            $xcadena = str_replace("  ", " ", $xcadena); // quito espacios dobles
            $xcadena = str_replace("BILLON DE MILLONES", "BILLON DE", $xcadena); // corrigo la leyenda
            $xcadena = str_replace("BILLONES DE MILLONES", "BILLONES DE", $xcadena); // corrigo la leyenda
            $xcadena = str_replace("DE UN", "UN", $xcadena); // corrigo la leyenda
        } // ENDFOR ($xz)
        return trim($xcadena);
    }
}
if (! function_exists('numtoletras')) {
    function numtoletras($xcifra)
    {
        $xarray = array(0 => "Cero",
            1 => "UN", "DOS", "TRES", "CUATRO", "CINCO", "SEIS", "SIETE", "OCHO", "NUEVE",
            "DIEZ", "ONCE", "DOCE", "TRECE", "CATORCE", "QUINCE", "DIECISEIS", "DIECISIETE", "DIECIOCHO", "DIECINUEVE",
            "VEINTI", 30 => "TREINTA", 40 => "CUARENTA", 50 => "CINCUENTA", 60 => "SESENTA", 70 => "SETENTA", 80 => "OCHENTA", 90 => "NOVENTA",
            100 => "CIENTO", 200 => "DOSCIENTOS", 300 => "TRESCIENTOS", 400 => "CUATROCIENTOS", 500 => "QUINIENTOS", 600 => "SEISCIENTOS", 700 => "SETECIENTOS", 800 => "OCHOCIENTOS", 900 => "NOVECIENTOS"
        );
    //
        $xcifra = trim($xcifra);
        $xlength = strlen($xcifra);
        $xpos_punto = strpos($xcifra, ".");
        $xaux_int = $xcifra;
        $xdecimales = "00";
        if (!($xpos_punto === false)) {
            if ($xpos_punto == 0) {
                $xcifra = "0" . $xcifra;
                $xpos_punto = strpos($xcifra, ".");
            }
            $xaux_int = substr($xcifra, 0, $xpos_punto); // obtengo el entero de la cifra a covertir
            $xdecimales = substr($xcifra . "00", $xpos_punto + 1, 2); // obtengo los valores decimales
        }

        $XAUX = str_pad($xaux_int, 18, " ", STR_PAD_LEFT); // ajusto la longitud de la cifra, para que sea divisible por centenas de miles (grupos de 6)
        $xcadena = "";
        for ($xz = 0; $xz < 3; $xz++) {
            $xaux = substr($XAUX, $xz * 6, 6);
            $xi = 0;
            $xlimite = 6; // inicializo el contador de centenas xi y establezco el límite a 6 dígitos en la parte entera
            $xexit = true; // bandera para controlar el ciclo del While
            while ($xexit) {
                if ($xi == $xlimite) { // si ya llegó al límite máximo de enteros
                    break; // termina el ciclo
                }

                $x3digitos = ($xlimite - $xi) * -1; // comienzo con los tres primeros digitos de la cifra, comenzando por la izquierda
                $xaux = substr($xaux, $x3digitos, abs($x3digitos)); // obtengo la centena (los tres dígitos)
                for ($xy = 1; $xy < 4; $xy++) { // ciclo para revisar centenas, decenas y unidades, en ese orden
                    switch ($xy) {
                        case 1: // checa las centenas
                            if (substr($xaux, 0, 3) < 100) { // si el grupo de tres dígitos es menor a una centena ( < 99) no hace nada y pasa a revisar las decenas

                            } else {
                                $key = (int) substr($xaux, 0, 3);
                                if (TRUE === array_key_exists($key, $xarray)){  // busco si la centena es número redondo (100, 200, 300, 400, etc..)
                                    $xseek = $xarray[$key];
                                    $xsub = subfijo($xaux); // devuelve el subfijo correspondiente (Millón, Millones, Mil o nada)
                                    if (substr($xaux, 0, 3) == 100)
                                        $xcadena = " " . $xcadena . " CIEN " . $xsub;
                                    else
                                        $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
                                    $xy = 3; // la centena fue redonda, entonces termino el ciclo del for y ya no reviso decenas ni unidades
                                }
                                else { // entra aquí si la centena no fue numero redondo (101, 253, 120, 980, etc.)
                                    $key = (int) substr($xaux, 0, 1) * 100;
                                    $xseek = $xarray[$key]; // toma el primer caracter de la centena y lo multiplica por cien y lo busca en el arreglo (para que busque 100,200,300, etc)
                                    $xcadena = " " . $xcadena . " " . $xseek;
                                } // ENDIF ($xseek)
                            } // ENDIF (substr($xaux, 0, 3) < 100)
                            break;
                        case 2: // checa las decenas (con la misma lógica que las centenas)
                            if (substr($xaux, 1, 2) < 10) {

                            } else {
                                $key = (int) substr($xaux, 1, 2);
                                if (TRUE === array_key_exists($key, $xarray)) {
                                    $xseek = $xarray[$key];
                                    $xsub = subfijo($xaux);
                                    if (substr($xaux, 1, 2) == 20)
                                        $xcadena = " " . $xcadena . " VEINTE " . $xsub;
                                    else
                                        $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
                                    $xy = 3;
                                }
                                else {
                                    $key = (int) substr($xaux, 1, 1) * 10;
                                    $xseek = $xarray[$key];
                                    if (20 == substr($xaux, 1, 1) * 10)
                                        $xcadena = " " . $xcadena . " " . $xseek;
                                    else
                                        $xcadena = " " . $xcadena . " " . $xseek . " Y ";
                                } // ENDIF ($xseek)
                            } // ENDIF (substr($xaux, 1, 2) < 10)
                            break;
                        case 3: // checa las unidades
                            if (substr($xaux, 2, 1) < 1) { // si la unidad es cero, ya no hace nada

                            } else {
                                $key = (int) substr($xaux, 2, 1);
                                $xseek = $xarray[$key]; // obtengo directamente el valor de la unidad (del uno al nueve)
                                $xsub = subfijo($xaux);
                                $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
                            } // ENDIF (substr($xaux, 2, 1) < 1)
                            break;
                    } // END SWITCH
                } // END FOR
                $xi = $xi + 3;
            } // ENDDO

            if (substr(trim($xcadena), -5, 5) == "ILLON") // si la cadena obtenida termina en MILLON o BILLON, entonces le agrega al final la conjuncion DE
                $xcadena.= " DE";

            if (substr(trim($xcadena), -7, 7) == "ILLONES") // si la cadena obtenida en MILLONES o BILLONES, entoncea le agrega al final la conjuncion DE
                $xcadena.= " DE";

            // ----------- esta línea la puedes cambiar de acuerdo a tus necesidades o a tu país -------
            if (trim($xaux) != "") {
                switch ($xz) {
                    case 0:
                        if (trim(substr($XAUX, $xz * 6, 6)) == "1")
                            $xcadena.= "UN BILLON ";
                        else
                            $xcadena.= " BILLONES ";
                        break;
                    case 1:
                        if (trim(substr($XAUX, $xz * 6, 6)) == "1")
                            $xcadena.= "UN MILLON ";
                        else
                            $xcadena.= " MILLONES ";
                        break;
                    case 2:
                        if ($xcifra < 1) {
                            $xcadena = "CERO DOLARES $xdecimales/100 ";
                        }
                        if ($xcifra >= 1 && $xcifra < 2) {
                            $xcadena = "UN DOLAR $xdecimales/100  ";
                        }
                        if ($xcifra >= 2) {
                            $xcadena.= " DOLARES $xdecimales/100  "; //
                        }
                        break;
                } // endswitch ($xz)
            } // ENDIF (trim($xaux) != "")
            // ------------------      en este caso, para México se usa esta leyenda     ----------------
            $xcadena = str_replace("VEINTI ", "VEINTI", $xcadena); // quito el espacio para el VEINTI, para que quede: VEINTICUATRO, VEINTIUN, VEINTIDOS, etc
            $xcadena = str_replace("  ", " ", $xcadena); // quito espacios dobles
            $xcadena = str_replace("UN UN", "UN", $xcadena); // quito la duplicidad
            $xcadena = str_replace("  ", " ", $xcadena); // quito espacios dobles
            $xcadena = str_replace("BILLON DE MILLONES", "BILLON DE", $xcadena); // corrigo la leyenda
            $xcadena = str_replace("BILLONES DE MILLONES", "BILLONES DE", $xcadena); // corrigo la leyenda
            $xcadena = str_replace("DE UN", "UN", $xcadena); // corrigo la leyenda
        } // ENDFOR ($xz)
        return trim($xcadena);
    }
    }

    if (!function_exists('descrproduct')) {
        function descrproduct($productId)
        {
            $product = Product::find($productId);

            if ($product) {
                $description = $product->description;

                // Verificar si la descripción es N/A, NULL o vacía
                if ($description === 'N/A' || $description === null || trim($description) === '') {
                    return $product->name; // Reemplaza 'name' con el nombre real del campo en tu tabla
                }

                return $description;
            }

            return 'Producto no encontrado';
        }
    }

    if (!function_exists('tipoDocumento')) {
        function tipoDocumento($tipo)
        {
            $documento = "";
            switch ($tipo) {
                case '36':
                    $documento = "NIT";
                    break;

                default:
                    # code...
                    break;
            }
            return $documento;
        }
    }

    if (!function_exists('lab_exam_es_multi_param')) {
        /**
         * Indica si el examen requiere más de un resultado (multi-parámetro).
         * Usa template_id y config lab_exam_templates; si campos_adicionales tiene > 1 clave, es multi-param.
         * Si no hay template_id: "orina completa"/"general de orina" → general_orina;
         * "depuracion" + "creatinina" → depuracion_creatinina.
         */
        function lab_exam_es_multi_param($exam): bool
        {
            $tid = $exam->template_id ?? null;
            $name = strtolower(trim($exam->nombre ?? ''));
            // Normalizar espacios múltiples
            $name = preg_replace('/\s+/', ' ', $name);
            if (!$tid && (str_contains($name, 'orina completa') || str_contains($name, 'general de orina'))) {
                $tid = 'general_orina';
            }
            if (!$tid && str_contains($name, 'depuracion') && str_contains($name, 'creatinina')) {
                $tid = 'depuracion_creatinina';
            }
            // Detectar también variantes con "24 horas"
            if (!$tid && str_contains($name, 'depuracion') && str_contains($name, 'creatinina') &&
                (str_contains($name, '24') || str_contains($name, 'veinticuatro'))) {
                $tid = 'depuracion_creatinina';
            }
            // Amilasa Lipasa
            if (!$tid && str_contains($name, 'amilasa') && str_contains($name, 'lipasa')) {
                $tid = 'amilasa_lipasa';
            }
            // Bilirrubina
            if (!$tid && str_contains($name, 'bilirrubina')) {
                $tid = 'bilirrubina';
            }
            // Colesterol HDL Y LDL
            if (!$tid && ((str_contains($name, 'hdl') && str_contains($name, 'ldl')) ||
                str_contains($name, 'colesterol hdl') || str_contains($name, 'colesterol ldl'))) {
                $tid = 'hdl_ldl';
            }
            // Toxoplasma gondii IgM IgG
            if (!$tid && (str_contains($name, 'toxoplasma gondii') ||
                (str_contains($name, 'toxoplasma') && (str_contains($name, 'igg') || str_contains($name, 'igm'))))) {
                $tid = 'toxoplasma_gondii';
            }
            // Tipo Sanguíneo / Tipificación Sanguínea / Tipeo Sanguíneo
            if (!$tid && (str_contains($name, 'tipeo sanguineo') ||
                str_contains($name, 'tipeo sanguíneo') ||
                str_contains($name, 'tipo sanguineo') ||
                str_contains($name, 'tipo sanguíneo') ||
                str_contains($name, 'tipificacion sanguinea') ||
                str_contains($name, 'tipificación sanguínea') ||
                str_contains($name, 'tipo de sangre'))) {
                $tid = 'tipo_sanguineo';
            }
            // Coombs Directo y Indirecto
            if (!$tid && ((str_contains($name, 'coombs directo') && str_contains($name, 'indirecto')) ||
                (str_contains($name, 'coombs') && (str_contains($name, 'directo') || str_contains($name, 'indirecto'))))) {
                $tid = 'coombs_directo_indirecto';
            }
            // Antígenos Febriles
            if (!$tid && (str_contains($name, 'antigenos febriles') ||
                str_contains($name, 'antígenos febriles') ||
                str_contains($name, 'antigeno febril') ||
                str_contains($name, 'antígeno febril'))) {
                $tid = 'antigenos_febriles';
            }
            // Hemograma / Hematología Completa
            if (!$tid && (str_contains($name, 'hemograma') ||
                str_contains($name, 'hematologia completa') ||
                str_contains($name, 'hematología completa'))) {
                $tid = 'hemograma';
            }
            // Frotis de Sangre Periférica - detectar por "frotis" y cualquier variante
            if (!$tid && str_contains($name, 'frotis')) {
                $tid = 'frotis_sangre_periferica';
            }
            // Hematocrito y Hemoglobina
            if (!$tid && (str_contains($name, 'hematocrito') && str_contains($name, 'hemoglobina'))) {
                $tid = 'hematocrito_hemoglobina';
            }
            // Concentrado de strout - detectar por "strout"
            if (!$tid && str_contains($name, 'strout')) {
                $tid = 'concentrado_strout';
            }
            if (!$tid && (str_contains($name, 'urocultivo') || str_contains($name, 'cultivo de orina'))) {
                $tid = 'urocultivo';
            }
            if (!$tid && str_contains($name, 'coagulacion')) {
                $tid = 'coagulacion';
            }
            if (!$tid && (str_contains($name, 'perfil bioquimica clinica')
                || str_contains($name, 'perfil bioquímica clínica')
                || str_contains($name, 'perfil quimico')
                || str_contains($name, 'perfil químico'))) {
                $tid = 'perfil_bioquimica_clinica';
            }
            // TPT y TP: permitir multiresultado aunque el examen no tenga template_id
            if (!$tid && (str_contains($name, 'tpt y tp') || str_contains($name, 'tpt tp'))) {
                $tid = 'tpt_tp';
            }
            // Pruebas Tiroideas Completo (TSH, T4 Libre, T3 Libre): detectar primero si contiene "libre" o "ft4" o "ft3"
            // También detectar variantes como "Hormona Estimulante de Tiroides (TSH) T3 Y4 Libres" o "TSH T3 T4 Libres"
            if (!$tid && (
                (str_contains($name, 'hormona estimulante de tiroides') || str_contains($name, 'tsh')) &&
                (str_contains($name, 't3') || str_contains($name, 't4') || str_contains($name, 'y4')) &&
                (str_contains($name, 'libre') || str_contains($name, 'libres'))
            )) {
                $tid = 'pruebas_tiroideas_completo';
            }
            if (!$tid && str_contains($name, 'tiroideas') && (str_contains($name, 'libre') || str_contains($name, 'ft4') || str_contains($name, 'ft3'))) {
                $tid = 'pruebas_tiroideas_completo';
            }
            // Pruebas Tiroideas para Niños: detectar primero si contiene "niños"
            if (!$tid && str_contains($name, 'tiroideas') && (str_contains($name, 'niños') || str_contains($name, 'ninos'))) {
                $tid = 'tiroideas_ninos';
            }
            // Pruebas Tiroideas (T3, T4, TSH) para adultos: detectar por nombre
            if (!$tid && (str_contains($name, 'pruebas tiroideas') || str_contains($name, 'tiroideas') ||
                (str_contains($name, 't3') && str_contains($name, 't4') && str_contains($name, 'tsh')))) {
                $tid = 'tiroideas_t3_t4_tsh';
            }
            // Curva de Tolerancia a la Glucosa: detectar por nombre
            if (!$tid && (str_contains($name, 'curva') && str_contains($name, 'tolerancia') && str_contains($name, 'glucosa'))) {
                $tid = 'curva_tolerancia_glucosa';
            }
            // TGO y TGP: detectar por nombre
            if (!$tid && ((str_contains($name, 'tgo') && str_contains($name, 'tgp')) ||
                (str_contains($name, 'transaminasa') && (str_contains($name, 'oxalacetica') || str_contains($name, 'oxalacética')) &&
                 (str_contains($name, 'piruvica') || str_contains($name, 'pirúvica'))))) {
                $tid = 'tgo_tgp';
            }
            // Test de O'Sullivan: detectar por nombre
            if (!$tid && (str_contains($name, 'osullivan') || str_contains($name, 'o\'sullivan') ||
                (str_contains($name, 'test') && str_contains($name, 'osullivan')))) {
                $tid = 'test_osullivan';
            }
            // Glucosa Post-Pandrial 2 Horas: detectar por nombre
            if (!$tid && str_contains($name, 'glucosa') &&
                (str_contains($name, 'post pandrial') || str_contains($name, 'post-pandrial') ||
                 str_contains($name, 'postpandrial') || (str_contains($name, 'post') && str_contains($name, 'pandrial'))) &&
                (str_contains($name, '2 horas') || str_contains($name, '2h'))) {
                $tid = 'glucosa_post_pandrial_2h';
            }
            // Electrolitos / Minerales: detectar por nombre
            if (!$tid && (str_contains($name, 'electrolitos') || str_contains($name, 'minerales'))) {
                $tid = 'electrolitos_minerales';
            }
            // General de Heces / Heces Completo - detectar por "heces" y ("completo" o "general")
            // También detectar solo "heces completo" o variantes
            if (!$tid && str_contains($name, 'heces') && (str_contains($name, 'completo') || str_contains($name, 'general'))) {
                $tid = 'heces_completo';
            }
            // Detectar también si solo dice "heces completo" sin "general"
            if (!$tid && str_contains($name, 'heces completo')) {
                $tid = 'heces_completo';
            }
            // Coprocultivo - detectar por "coprocultivo"
            if (!$tid && str_contains($name, 'coprocultivo')) {
                $tid = 'coprocultivo';
            }
            // PAM azul de metileno - detectar por "pam" y ("azul" o "metileno")
            if (!$tid && (str_contains($name, 'pam') && (str_contains($name, 'azul') || str_contains($name, 'metileno')))) {
                $tid = 'pam_azul_metileno';
            }
            // También detectar solo "pam" si no hay otros matches
            if (!$tid && str_contains($name, 'pam') && !str_contains($name, 'camp') && !str_contains($name, 'ampli')) {
                $tid = 'pam_azul_metileno';
            }
            // Fibrinógeno: usar siempre el template dedicado
            if (!$tid && (str_contains($name, 'fibrinogeno') || str_contains($name, 'fibrinógeno'))) {
                $tid = 'fibrinogeno';
            }
            if (!$tid) {
                return false;
            }
            $t = config("lab_exam_templates.{$tid}");
            if (!$t) {
                return false;
            }
            $campos = $t['campos_adicionales'] ?? [];
            // Para la mayoría de exámenes, solo tiene sentido multi-parámetro si hay más de un campo.
            // Excepciones: fibrinógeno, concentrado_strout (queremos usar el flujo de multiresultado aunque solo tenga un campo).
            // glucosa_post_pandrial_2h ahora tiene 2 campos, así que no necesita excepción
            if ($tid === 'fibrinogeno' || $tid === 'concentrado_strout') {
                return count($campos) >= 1;
            }
            return count($campos) > 1;
        }
    }

    if (!function_exists('lab_exam_parametros')) {
        /**
         * Devuelve los parámetros para exámenes multi-parámetro.
         * Cada elemento: key, label, valor_referencia, unidad, tipo, required, opciones.
         */
        function lab_exam_parametros($exam): array
        {
            $tid = $exam->template_id ?? null;
            $name = strtolower($exam->nombre ?? '');
            if (!$tid && (str_contains($name, 'orina completa') || str_contains($name, 'general de orina'))) {
                $tid = 'general_orina';
            }
            if (!$tid && str_contains($name, 'depuracion') && str_contains($name, 'creatinina')) {
                $tid = 'depuracion_creatinina';
            }
            // Detectar también variantes con "24 horas"
            if (!$tid && str_contains($name, 'depuracion') && str_contains($name, 'creatinina') &&
                (str_contains($name, '24') || str_contains($name, 'veinticuatro'))) {
                $tid = 'depuracion_creatinina';
            }
            // Amilasa Lipasa
            if (!$tid && str_contains($name, 'amilasa') && str_contains($name, 'lipasa')) {
                $tid = 'amilasa_lipasa';
            }
            // Bilirrubina
            if (!$tid && str_contains($name, 'bilirrubina')) {
                $tid = 'bilirrubina';
            }
            // Colesterol HDL Y LDL
            if (!$tid && ((str_contains($name, 'hdl') && str_contains($name, 'ldl')) ||
                str_contains($name, 'colesterol hdl') || str_contains($name, 'colesterol ldl'))) {
                $tid = 'hdl_ldl';
            }
            // Toxoplasma gondii IgM IgG
            if (!$tid && (str_contains($name, 'toxoplasma gondii') ||
                (str_contains($name, 'toxoplasma') && (str_contains($name, 'igg') || str_contains($name, 'igm'))))) {
                $tid = 'toxoplasma_gondii';
            }
            // Tipo Sanguíneo / Tipificación Sanguínea / Tipeo Sanguíneo
            if (!$tid && (str_contains($name, 'tipeo sanguineo') ||
                str_contains($name, 'tipeo sanguíneo') ||
                str_contains($name, 'tipo sanguineo') ||
                str_contains($name, 'tipo sanguíneo') ||
                str_contains($name, 'tipificacion sanguinea') ||
                str_contains($name, 'tipificación sanguínea') ||
                str_contains($name, 'tipo de sangre'))) {
                $tid = 'tipo_sanguineo';
            }
            // Coombs Directo y Indirecto
            if (!$tid && ((str_contains($name, 'coombs directo') && str_contains($name, 'indirecto')) ||
                (str_contains($name, 'coombs') && (str_contains($name, 'directo') || str_contains($name, 'indirecto'))))) {
                $tid = 'coombs_directo_indirecto';
            }
            // Antígenos Febriles
            if (!$tid && (str_contains($name, 'antigenos febriles') ||
                str_contains($name, 'antígenos febriles') ||
                str_contains($name, 'antigeno febril') ||
                str_contains($name, 'antígeno febril'))) {
                $tid = 'antigenos_febriles';
            }
            // Hemograma / Hematología Completa
            if (!$tid && (str_contains($name, 'hemograma') ||
                str_contains($name, 'hematologia completa') ||
                str_contains($name, 'hematología completa'))) {
                $tid = 'hemograma';
            }
            // Frotis de Sangre Periférica - detectar por "frotis" y cualquier variante
            if (!$tid && str_contains($name, 'frotis')) {
                $tid = 'frotis_sangre_periferica';
            }
            // General de Heces / Heces Completo - detectar por "heces" y ("completo" o "general")
            // También detectar solo "heces completo" o variantes
            if (!$tid && str_contains($name, 'heces') && (str_contains($name, 'completo') || str_contains($name, 'general'))) {
                $tid = 'heces_completo';
            }
            // Detectar también si solo dice "heces completo" sin "general"
            if (!$tid && str_contains($name, 'heces completo')) {
                $tid = 'heces_completo';
            }
            // Hematocrito y Hemoglobina
            if (!$tid && (str_contains($name, 'hematocrito') && str_contains($name, 'hemoglobina'))) {
                $tid = 'hematocrito_hemoglobina';
            }
            // Concentrado de strout - detectar por "strout"
            if (!$tid && str_contains($name, 'strout')) {
                $tid = 'concentrado_strout';
            }
            // Coprocultivo - detectar por "coprocultivo"
            if (!$tid && str_contains($name, 'coprocultivo')) {
                $tid = 'coprocultivo';
            }
            // PAM azul de metileno - detectar por "pam" y ("azul" o "metileno")
            if (!$tid && (str_contains($name, 'pam') && (str_contains($name, 'azul') || str_contains($name, 'metileno')))) {
                $tid = 'pam_azul_metileno';
            }
            // También detectar solo "pam" si no hay otros matches
            if (!$tid && str_contains($name, 'pam') && !str_contains($name, 'camp') && !str_contains($name, 'ampli')) {
                $tid = 'pam_azul_metileno';
            }
            if (!$tid && (str_contains($name, 'urocultivo') || str_contains($name, 'cultivo de orina'))) {
                $tid = 'urocultivo';
            }
            if (!$tid && str_contains($name, 'coagulacion')) {
                $tid = 'coagulacion';
            }
            if (!$tid && (str_contains($name, 'perfil bioquimica clinica')
                || str_contains($name, 'perfil bioquímica clínica')
                || str_contains($name, 'perfil quimico')
                || str_contains($name, 'perfil químico'))) {
                $tid = 'perfil_bioquimica_clinica';
            }
            // TPT y TP: reutilizar el template de multi-parámetro
            if (!$tid && (str_contains($name, 'tpt y tp') || str_contains($name, 'tpt tp'))) {
                $tid = 'tpt_tp';
            }
            // Pruebas Tiroideas Completo (TSH, T4 Libre, T3 Libre): detectar primero si contiene "libre" o "ft4" o "ft3"
            // También detectar variantes como "Hormona Estimulante de Tiroides (TSH) T3 Y4 Libres" o "TSH T3 T4 Libres"
            if (!$tid && (
                (str_contains($name, 'hormona estimulante de tiroides') || str_contains($name, 'tsh')) &&
                (str_contains($name, 't3') || str_contains($name, 't4') || str_contains($name, 'y4')) &&
                (str_contains($name, 'libre') || str_contains($name, 'libres'))
            )) {
                $tid = 'pruebas_tiroideas_completo';
            }
            if (!$tid && str_contains($name, 'tiroideas') && (str_contains($name, 'libre') || str_contains($name, 'ft4') || str_contains($name, 'ft3'))) {
                $tid = 'pruebas_tiroideas_completo';
            }
            // Pruebas Tiroideas para Niños: detectar primero si contiene "niños"
            if (!$tid && str_contains($name, 'tiroideas') && (str_contains($name, 'niños') || str_contains($name, 'ninos'))) {
                $tid = 'tiroideas_ninos';
            }
            // Pruebas Tiroideas (T3, T4, TSH) para adultos: detectar por nombre
            if (!$tid && (str_contains($name, 'pruebas tiroideas') || str_contains($name, 'tiroideas') ||
                (str_contains($name, 't3') && str_contains($name, 't4') && str_contains($name, 'tsh')))) {
                $tid = 'tiroideas_t3_t4_tsh';
            }
            // Glucosa Post-Pandrial 2 Horas: detectar ANTES de otras detecciones de glucosa
            if (!$tid && str_contains($name, 'glucosa') &&
                (str_contains($name, 'post pandrial') || str_contains($name, 'post-pandrial') ||
                 str_contains($name, 'postpandrial') || (str_contains($name, 'post') && str_contains($name, 'pandrial'))) &&
                (str_contains($name, '2 horas') || str_contains($name, '2h'))) {
                $tid = 'glucosa_post_pandrial_2h';
            }
            // Curva de Tolerancia a la Glucosa: detectar por nombre
            if (!$tid && (str_contains($name, 'curva') && str_contains($name, 'tolerancia') && str_contains($name, 'glucosa'))) {
                $tid = 'curva_tolerancia_glucosa';
            }
            // TGO y TGP: detectar por nombre
            if (!$tid && ((str_contains($name, 'tgo') && str_contains($name, 'tgp')) ||
                (str_contains($name, 'transaminasa') && (str_contains($name, 'oxalacetica') || str_contains($name, 'oxalacética')) &&
                 (str_contains($name, 'piruvica') || str_contains($name, 'pirúvica'))))) {
                $tid = 'tgo_tgp';
            }
            // Test de O'Sullivan: detectar por nombre
            if (!$tid && (str_contains($name, 'osullivan') || str_contains($name, 'o\'sullivan') ||
                (str_contains($name, 'test') && str_contains($name, 'osullivan')))) {
                $tid = 'test_osullivan';
            }
            // Electrolitos / Minerales: detectar por nombre
            if (!$tid && (str_contains($name, 'electrolitos') || str_contains($name, 'minerales'))) {
                $tid = 'electrolitos_minerales';
            }
            // Fibrinógeno: reutilizar template aunque solo tenga un campo adicional
            if (!$tid && (str_contains($name, 'fibrinogeno') || str_contains($name, 'fibrinógeno'))) {
                $tid = 'fibrinogeno';
            }
            if (!$tid) {
                return [];
            }
            $t = config("lab_exam_templates.{$tid}");
            if (!$t) {
                return [];
            }
            $campos = $t['campos_adicionales'] ?? [];
            // Para la mayoría de exámenes, solo construimos parámetros si hay más de un campo.
            // Excepciones: fibrinógeno, concentrado_strout (permitimos un solo campo para usar el flujo de multiresultado).
            // glucosa_post_pandrial_2h ahora tiene 2 campos, así que no necesita excepción
            if ($tid !== 'fibrinogeno' && $tid !== 'concentrado_strout' && count($campos) <= 1) {
                return [];
            }
            $vr = $t['valores_referencia'] ?? [];
            $params = [];
            foreach ($campos as $campoKey => $def) {
                $paramKey = str_starts_with($campoKey, 'resultado_')
                    ? substr($campoKey, strlen('resultado_'))
                    : $campoKey;
                $ref = $vr[$paramKey] ?? $vr[$campoKey] ?? [];
                // Mapeo específico para depuracion_creatinina
                if (empty($ref) && $tid === 'depuracion_creatinina') {
                    if ($paramKey === 'depuracion' && isset($vr['depuracion'])) {
                        $ref = $vr['depuracion'];
                    } elseif ($paramKey === 'creatinina_suero' && isset($vr['creatinina_suero'])) {
                        $ref = $vr['creatinina_suero'];
                    } elseif ($paramKey === 'creatinina_orina' && isset($vr['creatinina_orina'])) {
                        $ref = $vr['creatinina_orina'];
                    } elseif ($paramKey === 'volumen_orina' && isset($vr['volumen_orina'])) {
                        $ref = $vr['volumen_orina'];
                    }
                }
                // Mapeo específico para amilasa_lipasa
                if (empty($ref) && $tid === 'amilasa_lipasa') {
                    if ($paramKey === 'amilasa' && isset($vr['amilasa'])) {
                        $ref = $vr['amilasa'];
                    } elseif ($paramKey === 'lipasa' && isset($vr['lipasa'])) {
                        $ref = $vr['lipasa'];
                    }
                }
                // Mapeo específico para bilirrubina
                if (empty($ref) && $tid === 'bilirrubina') {
                    if ($paramKey === 'total' && isset($vr['total'])) {
                        $ref = $vr['total'];
                    } elseif ($paramKey === 'directa' && isset($vr['directa'])) {
                        $ref = $vr['directa'];
                    } elseif ($paramKey === 'indirecta' && isset($vr['indirecta'])) {
                        $ref = $vr['indirecta'];
                    }
                }
                // Mapeo específico para hdl_ldl
                if (empty($ref) && $tid === 'hdl_ldl') {
                    if ($paramKey === 'ldl' && isset($vr['ldl'])) {
                        $ref = $vr['ldl'];
                    } elseif ($paramKey === 'hdl' && isset($vr['hdl'])) {
                        $ref = $vr['hdl'];
                    }
                }
                // Mapeo específico para toxoplasma_gondii
                if (empty($ref) && $tid === 'toxoplasma_gondii') {
                    if ($paramKey === 'igg' && isset($vr['igg'])) {
                        $ref = $vr['igg'];
                    } elseif ($paramKey === 'igm' && isset($vr['igm'])) {
                        $ref = $vr['igm'];
                    }
                }
                // Mapeo específico para tipo_sanguineo
                if (empty($ref) && $tid === 'tipo_sanguineo') {
                    if ($paramKey === 'grupo' && isset($vr['grupo'])) {
                        $ref = $vr['grupo'];
                    } elseif ($paramKey === 'factor_rh' && isset($vr['factor_rh'])) {
                        $ref = $vr['factor_rh'];
                    }
                }
                // Mapeo específico para coombs_directo_indirecto
                if (empty($ref) && $tid === 'coombs_directo_indirecto') {
                    if ($paramKey === 'coombs_indirecto' && isset($vr['coombs_indirecto'])) {
                        $ref = $vr['coombs_indirecto'];
                    } elseif ($paramKey === 'coombs_directo' && isset($vr['coombs_directo'])) {
                        $ref = $vr['coombs_directo'];
                    }
                }
                // Mapeo específico para antigenos_febriles
                if (empty($ref) && $tid === 'antigenos_febriles') {
                    if ($paramKey === 'salmonella_paratyphi_ah' && isset($vr['salmonella_paratyphi_ah'])) {
                        $ref = $vr['salmonella_paratyphi_ah'];
                    } elseif ($paramKey === 'salmonella_paratyphi_bh' && isset($vr['salmonella_paratyphi_bh'])) {
                        $ref = $vr['salmonella_paratyphi_bh'];
                    } elseif ($paramKey === 'salmonella_typhi_h' && isset($vr['salmonella_typhi_h'])) {
                        $ref = $vr['salmonella_typhi_h'];
                    } elseif ($paramKey === 'salmonella_typhi_o' && isset($vr['salmonella_typhi_o'])) {
                        $ref = $vr['salmonella_typhi_o'];
                    } elseif ($paramKey === 'brucella_abortus' && isset($vr['brucella_abortus'])) {
                        $ref = $vr['brucella_abortus'];
                    } elseif ($paramKey === 'proteus_ox19' && isset($vr['proteus_ox19'])) {
                        $ref = $vr['proteus_ox19'];
                    }
                }
                // Mapeo específico para hemograma
                if (empty($ref) && $tid === 'hemograma') {
                    if ($paramKey === 'globulos_blancos' && isset($vr['globulos_blancos'])) {
                        $ref = $vr['globulos_blancos'];
                    } elseif ($paramKey === 'neutrofilos' && isset($vr['neutrofilos'])) {
                        $ref = $vr['neutrofilos'];
                    } elseif ($paramKey === 'eosinofilos' && isset($vr['eosinofilos'])) {
                        $ref = $vr['eosinofilos'];
                    } elseif ($paramKey === 'basofilos' && isset($vr['basofilos'])) {
                        $ref = $vr['basofilos'];
                    } elseif ($paramKey === 'linfocitos' && isset($vr['linfocitos'])) {
                        $ref = $vr['linfocitos'];
                    } elseif ($paramKey === 'monocitos' && isset($vr['monocitos'])) {
                        $ref = $vr['monocitos'];
                    } elseif ($paramKey === 'globulos_rojos' && isset($vr['globulos_rojos'])) {
                        $ref = $vr['globulos_rojos'];
                    } elseif ($paramKey === 'hematocrito' && isset($vr['hematocrito'])) {
                        $ref = $vr['hematocrito'];
                    } elseif ($paramKey === 'hemoglobina' && isset($vr['hemoglobina'])) {
                        $ref = $vr['hemoglobina'];
                    } elseif ($paramKey === 'vcm' && isset($vr['vcm'])) {
                        $ref = $vr['vcm'];
                    } elseif ($paramKey === 'chcm' && isset($vr['chcm'])) {
                        $ref = $vr['chcm'];
                    } elseif ($paramKey === 'hcm' && isset($vr['hcm'])) {
                        $ref = $vr['hcm'];
                    } elseif ($paramKey === 'plaquetas' && isset($vr['plaquetas'])) {
                        $ref = $vr['plaquetas'];
                    }
                }
                // Mapeo específico para frotis_sangre_periferica
                if (empty($ref) && $tid === 'frotis_sangre_periferica') {
                    if ($paramKey === 'serie_roja' && isset($vr['serie_roja'])) {
                        $ref = $vr['serie_roja'];
                    } elseif ($paramKey === 'serie_blanca' && isset($vr['serie_blanca'])) {
                        $ref = $vr['serie_blanca'];
                    } elseif ($paramKey === 'serie_plaquetaria' && isset($vr['serie_plaquetaria'])) {
                        $ref = $vr['serie_plaquetaria'];
                    }
                }
                // Mapeo específico para hematocrito_hemoglobina
                if (empty($ref) && $tid === 'hematocrito_hemoglobina') {
                    if ($paramKey === 'hematocrito' && isset($vr['hematocrito'])) {
                        $ref = $vr['hematocrito'];
                    } elseif ($paramKey === 'hemoglobina' && isset($vr['hemoglobina'])) {
                        $ref = $vr['hemoglobina'];
                    }
                }
                // Mapeo específico para concentrado_strout
                if (empty($ref) && $tid === 'concentrado_strout') {
                    if ($paramKey === 'resultado' && isset($vr['resultado'])) {
                        $ref = $vr['resultado'];
                    }
                }
                // Mapeo específico para pam_azul_metileno
                if (empty($ref) && $tid === 'pam_azul_metileno') {
                    if ($paramKey === 'leucocitos_polimorfonucleares' && isset($vr['leucocitos_polimorfonucleares'])) {
                        $ref = $vr['leucocitos_polimorfonucleares'];
                    } elseif ($paramKey === 'leucocitos_mononucleares' && isset($vr['leucocitos_mononucleares'])) {
                        $ref = $vr['leucocitos_mononucleares'];
                    }
                }
                // Mapeo específico para heces_completo
                if (empty($ref) && $tid === 'heces_completo') {
                    $paramMap = [
                        'color' => 'color',
                        'consistencia' => 'consistencia',
                        'mucus' => 'mucus',
                        'restos_alimenticios_macroscopicos' => 'restos_alimenticios_macroscopicos',
                        'restos_alimenticios_microscopicos' => 'restos_alimenticios_microscopicos',
                        'metazoarios' => 'metazoarios',
                        'protozoarios_quistes' => 'protozoarios_quistes',
                        'protozoarios_activos' => 'protozoarios_activos',
                        'hematies' => 'hematies',
                        'leucocitos' => 'leucocitos',
                        'levaduras' => 'levaduras',
                    ];
                    if (isset($paramMap[$paramKey]) && isset($vr[$paramMap[$paramKey]])) {
                        $ref = $vr[$paramMap[$paramKey]];
                    }
                }
                // Mapeo específico para coprocultivo
                if (empty($ref) && $tid === 'coprocultivo') {
                    $paramMap = [
                        'susceptible' => 'susceptible',
                        'intermedio' => 'intermedio',
                        'resistente' => 'resistente',
                        'tipo_muestra' => 'tipo_muestra',
                    ];
                    if (isset($paramMap[$paramKey]) && isset($vr[$paramMap[$paramKey]])) {
                        $ref = $vr[$paramMap[$paramKey]];
                    }
                }
                // Mapeo específico para urocultivo
                if (empty($ref) && $tid === 'urocultivo') {
                    $paramMap = [
                        'recuento_bacteriano' => 'recuento_bacteriano',
                        'susceptible' => 'susceptible',
                        'intermedio' => 'intermedio',
                        'resistente' => 'resistente',
                    ];
                    if (isset($paramMap[$paramKey]) && isset($vr[$paramMap[$paramKey]])) {
                        $ref = $vr[$paramMap[$paramKey]];
                    }
                }
                // Mapeo específico para glucosa_post_pandrial_2h
                if (empty($ref) && $tid === 'glucosa_post_pandrial_2h') {
                    if ($paramKey === 'ayunas' && isset($vr['ayunas'])) {
                        $ref = $vr['ayunas'];
                    } elseif ($paramKey === 'post_prandial' && isset($vr['post_prandial'])) {
                        $ref = $vr['post_prandial'];
                    }
                }
                $rango = is_array($ref) ? ($ref['rango'] ?? $ref['rango_simple'] ?? '') : '';
                $unidad = null;
                if (is_array($ref)) {
                    $unidad = $ref['unidad'] ?? null;
                }
                $ph = $def['placeholder'] ?? null;
                $valorDefault = null;
                if ($ph && is_string($ph) && str_starts_with($ph, 'Ej: ')) {
                    $valorDefault = trim(substr($ph, 4));
                }
                $params[] = [
                    'key' => $campoKey,
                    'param_key' => $paramKey,
                    'label' => $def['label'] ?? $campoKey,
                    'label_alt' => $def['label_alt'] ?? null,
                    'valor_referencia' => $rango,
                    'unidad' => $unidad,
                    'tipo' => $def['tipo'] ?? 'text',
                    'required' => $def['required'] ?? true,
                    'opciones' => $def['opciones'] ?? null,
                    'placeholder' => $ph,
                    'valor_default' => $valorDefault ?: null,
                ];
            }
            return $params;
        }
    }
