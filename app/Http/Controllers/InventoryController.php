<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Provider;
use App\Models\PurchaseDetail;
use App\Models\Unit;
use App\Services\PurchaseInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Schema;

class InventoryController extends Controller
{
    public function index()
    {
        $products = Product::with(['provider', 'inventory'])->get();
        return view('inventory.index', compact('products'));
    }

    public function getProviders()
    {
        $providers = Provider::select('id', 'razonsocial')->where('state', 'activo')->get();
        return response()->json($providers);
    }

    public function getinventoryid($id)
    {
        $inventory = Product::join('providers', 'products.provider_id', '=', 'providers.id')
            ->select('products.id as inventoryid', DB::raw('products.name as inventoryname'), 'products.*')
            ->where('products.id', '=', base64_decode($id))
            ->get();
        return response()->json($inventory);
    }

    public function getinventorycode($code)
    {
        $inventory = Product::join('providers', 'products.provider_id', '=', 'providers.id')
            ->select('products.id as inventoryid', DB::raw('products.name as inventoryname'), 'products.*', 'providers.razonsocial as provider')
            ->where('products.code', '=', base64_decode($code))
            ->get();
        return response()->json($inventory);
    }

    public function getinventoryall()
    {
        $inventory = Product::join('providers', 'products.provider_id', '=', 'providers.id')
            ->select('providers.razonsocial as nameprovider', 'providers.id as idprovider', 'products.*')
            ->get();
        return response()->json($inventory);
    }
/**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'productid' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'location' => 'nullable|string|max:255',
            // nuevos campos opcionales
            'unit-select' => 'nullable|string',
            'selected-unit-id' => 'nullable|exists:units,id',
            'conversion-factor' => 'nullable|numeric|min:0.0001'
        ]);

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($request->productid);

            // Verificar si ya existe inventario para este producto
            $existingInventory = Inventory::where('product_id', $request->productid)->first();

            if ($existingInventory) {
                return response()->json(['message' => 'Este producto ya tiene inventario registrado. Use la función de editar para modificar el inventario existente.'], 400);
            }

            // Calcular equivalencia a base si viene unidad
            $unitId = $request->input('selected-unit-id');
            $factor = (float)$request->input('conversion-factor', 0);
            $inputQuantity = (float)$request->quantity;

            // Determinar la unidad base según el tipo de producto
            $baseUnitId = null;
            $baseQuantity = 0;

            // Verificar si es un producto farmacéutico (tiene configuración de pastillas/blisters/cajas)
            if ($product->pastillas_per_blister || $product->blisters_per_caja) {
                // Para productos farmacéuticos, la unidad base es PASTILLA
                $pastillaUnit = Unit::where('unit_code', 'PASTILLA')->first();
                if (!$pastillaUnit) {
                    return response()->json(['message' => 'Error: No se encontró la unidad PASTILLA en el sistema'], 500);
                }
                
                $baseUnitId = $pastillaUnit->id;
                
                // Calcular cantidad en pastillas según la unidad seleccionada
                if ($unitId) {
                    $unit = Unit::find($unitId);
                    if ($unit) {
                        // Obtener conversión del producto
                        $conversion = \App\Models\ProductUnitConversion::where('product_id', $product->id)
                            ->where('unit_id', $unitId)
                            ->where('is_active', true)
                            ->first();
                        
                        if ($conversion) {
                            // Usar el factor de conversión de la tabla product_unit_conversions
                            $baseQuantity = $inputQuantity * $conversion->conversion_factor;
                        } else {
                            // Si no hay conversión, calcular manualmente
                            if ($unit->unit_code === 'PASTILLA') {
                                $baseQuantity = $inputQuantity;
                            } elseif ($unit->unit_code === 'BLISTER') {
                                $baseQuantity = $inputQuantity * ($product->pastillas_per_blister ?: 1);
                            } elseif ($unit->unit_code === 'CAJA') {
                                $pastillasPerBlister = $product->pastillas_per_blister ?: 1;
                                $blistersPerCaja = $product->blisters_per_caja ?: 1;
                                $baseQuantity = $inputQuantity * $pastillasPerBlister * $blistersPerCaja;
                            } else {
                                // Unidad no reconocida, usar factor si existe
                                $baseQuantity = $inputQuantity * ($factor ?: 1);
                            }
                        }
                    } else {
                        $baseQuantity = $inputQuantity;
                    }
                } else {
                    // Si no se especifica unidad, asumir que es en pastillas
                    $baseQuantity = $inputQuantity;
                }
            } elseif ($product->sale_type === 'weight') {
                // Para productos de peso, permitir elegir entre libra y unidad (sacos)
                if ($unitId) {
                    $unit = Unit::find($unitId);
                    if ($unit->unit_code === '59') {
                        // Si se ingresa en sacos, usar sacos como unidad base
                        $baseUnitId = Unit::where('unit_code', '59')->first()->id; // Unidad (Sacos)
                        $baseQuantity = $inputQuantity; // Cantidad en sacos
                    } elseif ($unit->unit_code === '36') {
                        // Si se ingresa en libras, usar libras como unidad base
                        $baseUnitId = Unit::where('unit_code', '36')->first()->id; // Libra
                        $baseQuantity = $inputQuantity; // Cantidad en libras
                    } else {
                        // Para otras unidades, usar el factor de conversión y libras como base
                        $baseUnitId = Unit::where('unit_code', '36')->first()->id; // Libra
                        $baseQuantity = $inputQuantity * $factor;
                    }
                } else {
                    // Si no se especifica unidad, asumir que es en libras
                    $baseUnitId = Unit::where('unit_code', '36')->first()->id; // Libra
                    $baseQuantity = $inputQuantity;
                }
            } elseif ($product->sale_type === 'volume') {
                // Para productos de volumen, la unidad base es litro
                $baseUnitId = Unit::where('unit_code', '23')->first()->id; // Litro

                if ($unitId) {
                    $unit = Unit::find($unitId);
                    if ($unit->unit_code === '59') {
                        // Si se ingresa en unidades (galones, etc.), convertir a litros
                        $baseQuantity = $inputQuantity * $product->volume_per_unit;
                    } elseif ($unit->unit_code === '23') {
                        // Si se ingresa en litros, usar directamente
                        $baseQuantity = $inputQuantity;
                    } else {
                        // Para otras unidades, usar el factor de conversión
                        $baseQuantity = $inputQuantity * $factor;
                    }
                } else {
                    // Si no se especifica unidad, asumir que es en litros
                    $baseQuantity = $inputQuantity;
                }
            } else {
                // Para productos por unidad, la unidad base es unidad
                $baseUnitId = Unit::where('unit_code', '59')->first()->id; // Unidad
                $baseQuantity = $inputQuantity;
            }

            // Crear registro de inventario con todos los campos necesarios
            $inventory = Inventory::create([
                'product_id' => $request->productid,
                'sku' => 'SKU-' . $request->productid . '-' . time(),
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'category' => $product->type,
                'user_id' => auth()->id(),
                'provider_id' => $product->provider_id,
                'active' => true,
                'quantity' => $request->quantity,
                'base_unit_id' => $baseUnitId,
                'base_quantity' => $baseQuantity,
                'minimum_stock' => $request->minimum_stock,
                'location' => $request->location
            ]);

            // Registrar movimiento de entrada inicial
            InventoryMovement::record(
                $inventory,
                'entrada_inicial',
                0,
                (float)$baseQuantity,
                0,
                (float)$baseQuantity,
                'Manual',
                null,
                null,
                auth()->id(),
                'Entrada inicial de inventario'
            );

            DB::commit();
            return response()->json(['message' => 'Inventario creado correctamente para el producto: ' . $product->name]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear el inventario: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $inventory = Inventory::with(['product', 'baseUnit'])->find($id);

            if (!$inventory) {
                return response()->json(['message' => 'No se encontró inventario para este producto'], 404);
            }

            return response()->json([
                'inventory' => $inventory,
                'product' => $inventory->product,
                'base_unit' => $inventory->baseUnit
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los datos del inventario'], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'location' => 'nullable|string|max:255',
        ], [
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.min' => 'La cantidad no puede ser negativa.',
            'minimum_stock.required' => 'El stock mínimo es obligatorio.',
        ]);

        try {
            DB::beginTransaction();

            $inventory = Inventory::find($id);

            if (!$inventory) {
                return response()->json(['message' => 'No se encontró inventario para actualizar'], 404);
            }

            $qtyBefore   = (float)$inventory->quantity;
            $baseBefore  = (float)$inventory->base_quantity;

            $inventory->quantity = $request->quantity;

            // Obtener el producto para determinar la lógica de conversión
            $product = Product::find($inventory->product_id);
            if (!$product) {
                return response()->json(['message' => 'No se encontró el producto asociado al inventario'], 404);
            }

            // Calcular equivalencia a base si viene unidad
            $unitId = $request->input('selected-unit-id');
            $factor = (float)$request->input('conversion-factor', 0);
            $inputQuantity = (float)$request->quantity;

            // Determinar la unidad base según el tipo de producto
            $baseUnitId = null;
            $baseQuantity = 0;

            // Verificar si es un producto farmacéutico (tiene configuración de pastillas/blisters/cajas)
            if ($product->pastillas_per_blister || $product->blisters_per_caja) {
                // Para productos farmacéuticos, la unidad base es PASTILLA
                $pastillaUnit = Unit::where('unit_code', 'PASTILLA')->first();
                if (!$pastillaUnit) {
                    return response()->json(['message' => 'Error: No se encontró la unidad PASTILLA en el sistema'], 500);
                }
                
                $baseUnitId = $pastillaUnit->id;
                
                // Calcular cantidad en pastillas según la unidad seleccionada
                if ($unitId) {
                    $unit = Unit::find($unitId);
                    if ($unit) {
                        // Obtener conversión del producto
                        $conversion = \App\Models\ProductUnitConversion::where('product_id', $product->id)
                            ->where('unit_id', $unitId)
                            ->where('is_active', true)
                            ->first();
                        
                        if ($conversion) {
                            // Usar el factor de conversión de la tabla product_unit_conversions
                            $baseQuantity = $inputQuantity * $conversion->conversion_factor;
                        } else {
                            // Si no hay conversión, calcular manualmente
                            if ($unit->unit_code === 'PASTILLA') {
                                $baseQuantity = $inputQuantity;
                            } elseif ($unit->unit_code === 'BLISTER') {
                                $baseQuantity = $inputQuantity * ($product->pastillas_per_blister ?: 1);
                            } elseif ($unit->unit_code === 'CAJA') {
                                $pastillasPerBlister = $product->pastillas_per_blister ?: 1;
                                $blistersPerCaja = $product->blisters_per_caja ?: 1;
                                $baseQuantity = $inputQuantity * $pastillasPerBlister * $blistersPerCaja;
                            } else {
                                // Unidad no reconocida, usar factor si existe
                                $baseQuantity = $inputQuantity * ($factor ?: 1);
                            }
                        }
                    } else {
                        $baseQuantity = $inputQuantity;
                    }
                } else {
                    // Si no se especifica unidad, mantener la cantidad base actual o asumir pastillas
                    $baseQuantity = $inputQuantity;
                }
            } elseif ($product->sale_type === 'weight') {
                // Para productos de peso, permitir elegir entre libra y unidad (sacos)
                if ($unitId) {
                    $unit = Unit::find($unitId);
                    if ($unit && $unit->unit_code === '59') {
                        // Si se ingresa en sacos, usar sacos como unidad base
                        $baseUnitId = Unit::where('unit_code', '59')->first()->id; // Unidad (Sacos)
                        $baseQuantity = $inputQuantity; // Cantidad en sacos
                    } elseif ($unit && $unit->unit_code === '36') {
                        // Si se ingresa en libras, usar libras como unidad base
                        $baseUnitId = Unit::where('unit_code', '36')->first()->id; // Libra
                        $baseQuantity = $inputQuantity; // Cantidad en libras
                    } else {
                        // Para otras unidades o unidad no encontrada
                        $baseUnitId = Unit::where('unit_code', '36')->first()?->id;
                        $baseQuantity = $inputQuantity * ($factor ?: 1);
                    }
                } else {
                    // Si no se especifica unidad, asumir que es en libras
                    $baseUnitId = Unit::where('unit_code', '36')->first()->id; // Libra
                    $baseQuantity = $inputQuantity;
                }
            } elseif ($product->sale_type === 'volume') {
                // Para productos de volumen, la unidad base es litro
                $baseUnitId = Unit::where('unit_code', '23')->first()->id; // Litro

                if ($unitId) {
                    $unit = Unit::find($unitId);
                    if ($unit && $unit->unit_code === '59') {
                        // Si se ingresa en unidades (galones, etc.), convertir a litros
                        $baseQuantity = $inputQuantity * ($product->volume_per_unit ?? 1);
                    } elseif ($unit && $unit->unit_code === '23') {
                        // Si se ingresa en litros, usar directamente
                        $baseQuantity = $inputQuantity;
                    } else {
                        // Para otras unidades o unidad no encontrada
                        $baseQuantity = $inputQuantity * ($factor ?: 1);
                    }
                } else {
                    // Si no se especifica unidad, asumir que es en litros
                    $baseQuantity = $inputQuantity;
                }
            } else {
                // Para productos por unidad, la unidad base es unidad
                $baseUnitId = Unit::where('unit_code', '59')->first()->id; // Unidad
                $baseQuantity = $inputQuantity; // No hay conversión necesaria
            }

            // Actualizar campos de base
            $inventory->base_unit_id = $baseUnitId;
            $inventory->base_quantity = $baseQuantity;
            $inventory->minimum_stock = $request->minimum_stock;
            $inventory->location = $request->location;
            $inventory->save();

            // Registrar movimiento de ajuste manual
            $baseAfter  = (float)$inventory->base_quantity;
            $baseChange = $baseAfter - $baseBefore;
            InventoryMovement::record(
                $inventory,
                'ajuste_manual',
                $qtyBefore,
                (float)$inventory->quantity - $qtyBefore,
                $baseBefore,
                $baseChange,
                'Manual',
                null,
                null,
                auth()->id(),
                'Ajuste manual de inventario'
            );

            DB::commit();
            return response()->json(['message' => 'Inventario actualizado correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar el inventario: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $inventory = Inventory::find($id);
            if ($inventory) {
                $inventory->delete();
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Inventario eliminado correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function export()
    {
        $inventory = Inventory::with(['product', 'product.provider', 'provider'])
            ->select('inventory.*')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventario.csv"',
        ];

        $callback = function() use ($inventory) {
            $file = fopen('php://output', 'w');

            // Headers del CSV
            fputcsv($file, [
                'SKU',
                'Código',
                'Nombre',
                'Descripción',
                'Cantidad',
                'Precio',
                'Categoría',
                'Stock Mínimo',
                'Ubicación',
                'Proveedor',
                'Estado'
            ]);

            // Datos
            foreach ($inventory as $item) {
                fputcsv($file, [
                    $item->sku,
                    $item->product ? $item->product->code : 'N/A',
                    $item->name ?: ($item->product ? $item->product->name : 'N/A'),
                    $item->description ?: ($item->product ? $item->product->description : 'N/A'),
                    $item->quantity,
                    $item->price ?: ($item->product ? $item->product->price : 0),
                    $item->category ?: ($item->product ? $item->product->type : 'N/A'),
                    $item->minimum_stock,
                    $item->location,
                    $item->provider ? $item->provider->razonsocial :
                           ($item->product && $item->product->provider ? $item->product->provider->razonsocial : 'N/A'),
                    $item->active ? 'Activo' : 'Inactivo'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function list()
    {
        try {
            $inventory = Inventory::with(['product', 'product.provider', 'baseUnit'])
                ->select('inventory.*')
                ->get();

            return DataTables::of($inventory)
                ->addColumn('id', function($item) { return $item->id; })
                ->addColumn('product_id', function($item) { return $item->product_id; })
                ->addColumn('code', function($item) {
                    return $item->product ? $item->product->code : 'N/A';
                })
                ->addColumn('name', function($item) {
                    return $item->name ?: ($item->product ? $item->product->name : 'N/A');
                })
                ->addColumn('description', function($item) {
                    return $item->description ?: ($item->product ? $item->product->description : 'N/A');
                })
                ->addColumn('price', function($item) {
                    return $item->price ?: ($item->product ? $item->product->price : 0);
                })
                ->addColumn('type', function($item) {
                    return $item->category ?: ($item->product ? $item->product->type : 'N/A');
                })
                ->addColumn('provider_name', function($item) {
                    return $item->provider ? $item->provider->razonsocial :
                           ($item->product && $item->product->provider ? $item->product->provider->razonsocial : 'N/A');
                })
                ->addColumn('quantity_raw', function($item) {
                    // Columna numérica para ordenamiento y comparaciones
                    return (float)($item->base_quantity ?? $item->quantity ?? 0);
                })
                ->addColumn('quantity', function($item) {
                    $baseQty = (float)($item->base_quantity ?? $item->quantity ?? 0);
                    $quantityValue = $baseQty;
                    $product = $item->product;
                    
                    // Verificar si es un producto farmacéutico
                    if ($product && ($product->pastillas_per_blister || $product->blisters_per_caja)) {
                        $pastillasPerBlister = (float)($product->pastillas_per_blister ?: 1);
                        $blistersPerCaja = (float)($product->blisters_per_caja ?: 1);
                        
                        $html = '<div data-quantity="' . $quantityValue . '">';
                        $html .= '<strong>' . number_format($quantityValue, 0) . ' pastillas</strong>';
                        
                        if ($pastillasPerBlister > 0) {
                            $blisters = floor($quantityValue / $pastillasPerBlister);
                            if ($blisters > 0) {
                                $html .= '<br><small class="text-muted">≈ ' . number_format($blisters, 0) . ' blisters</small>';
                            }
                        }
                        
                        if ($pastillasPerBlister > 0 && $blistersPerCaja > 0) {
                            $cajas = floor($quantityValue / ($pastillasPerBlister * $blistersPerCaja));
                            if ($cajas > 0) {
                                $html .= '<br><small class="text-muted">≈ ' . number_format($cajas, 0) . ' cajas</small>';
                            }
                        }
                        
                        $html .= '</div>';
                        return $html;
                    }
                    
                    // Para productos no farmacéuticos, mostrar solo la cantidad base
                    $unit = $item->baseUnit;
                    $unitName = $unit ? $unit->unit_name : 'unidades';
                    return '<span data-quantity="' . $quantityValue . '">' . number_format($quantityValue, 4) . ' ' . $unitName . '</span>';
                })
                ->addColumn('minimum_stock', function($item) {
                    return $item->minimum_stock;
                })
                ->addColumn('location', function($item) {
                    return $item->location ?? 'N/A';
                })
                ->addColumn('active', function($item) {
                    return $item->active ? 'Activo' : 'Inactivo';
                })
                ->addColumn('expiration_status', function($item) {
                    // Como la tabla inventory no tiene campos de vencimiento, retornar 'none'
                    return 'none';
                })
                ->addColumn('actions', function($item) {
                    return '<div class="dropdown">
                                <button class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a href="javascript:editinventory(' . $item->id . ');" class="dropdown-item">
                                        <i class="ti ti-edit ti-sm me-2"></i>Editar
                                    </a>
                                    <a href="javascript:showExpirationTracking(' . $item->product_id . ');" class="dropdown-item">
                                        <i class="ti ti-calendar-time ti-sm me-2"></i>Vencimiento
                                    </a>
                                    <a href="/inve/movements/' . $item->product_id . '" class="dropdown-item">
                                        <i class="ti ti-history ti-sm me-2"></i>Movimientos
                                    </a>
                                </div>
                            </div>';
                })
                ->rawColumns(['actions', 'quantity'])
                ->make(true);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar los datos'], 500);
        }
    }

    public function toggleState($id)
    {
        try {
            $inventory = Inventory::findOrFail($id);
            $inventory->active = !$inventory->active;
            $inventory->save();

            return response()->json([
                'success' => true,
                'message' => 'Estado cambiado correctamente',
                'active' => $inventory->active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado'
            ], 500);
        }
    }

    /**
     * Mostrar seguimiento de vencimiento de un producto específico
     */
    public function expirationTracking($productId)
    {
        try {
            $product = Product::with(['provider'])->findOrFail($productId);

            // Obtener todos los detalles de compra para este producto
            $purchaseDetails = PurchaseDetail::where('product_id', $productId)
                ->where('quantity', '>', 0)
                ->with(['purchase', 'purchase.provider'])
                ->orderBy('expiration_date', 'asc')
                ->get();

            // Agrupar por estado de vencimiento
            $expired = $purchaseDetails->filter(function($detail) {
                return $detail->isExpired();
            });

            $critical = $purchaseDetails->filter(function($detail) {
                return $detail->isExpiringSoon(7) && !$detail->isExpired();
            });

            $warning = $purchaseDetails->filter(function($detail) {
                return $detail->isExpiringSoon(30) && !$detail->isExpiringSoon(7) && !$detail->isExpired();
            });

            $ok = $purchaseDetails->filter(function($detail) {
                return !$detail->isExpiringSoon(30) && !$detail->isExpired();
            });

            $html = view('inventory.partials.expiration-tracking', compact(
                'product',
                'purchaseDetails',
                'expired',
                'critical',
                'warning',
                'ok'
            ))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el seguimiento de vencimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vista de historial de movimientos de un producto
     */
    public function movements($productId)
    {
        $inventory = Inventory::with(['product', 'baseUnit'])
            ->where('product_id', $productId)
            ->firstOrFail();

        return view('inventory.movements', compact('inventory'));
    }

    /**
     * DataTable de movimientos de un producto (o todos si no se pasa id)
     */
    public function movementsData(Request $request, $productId = null)
    {
        $query = InventoryMovement::with(['product', 'user', 'inventory'])
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderByDesc('created_at');

        return DataTables::of($query)
            ->addColumn('fecha', fn($m) => $m->created_at->format('d/m/Y H:i'))
            ->addColumn('tipo_label', fn($m) => '<span class="badge ' . $m->getTypeBadgeClass() . '">' . $m->getTypeLabel() . '</span>')
            ->addColumn('producto', fn($m) => $m->product ? $m->product->name : 'N/A')
            ->addColumn('cambio_base', function ($m) {
                $change = (float)$m->base_quantity_change;
                $class  = $change >= 0 ? 'text-success' : 'text-danger';
                $sign   = $change >= 0 ? '+' : '';
                return '<span class="' . $class . ' fw-bold">' . $sign . number_format($change, 4) . '</span>';
            })
            ->addColumn('stock_despues', fn($m) => number_format((float)$m->base_quantity_after, 4))
            ->addColumn('referencia', fn($m) => $m->reference_doc ?: ($m->reference_id ? '#' . $m->reference_id : '—'))
            ->addColumn('usuario', fn($m) => $m->user ? $m->user->name : 'Sistema')
            ->addColumn('notas', fn($m) => $m->notes ?: '—')
            ->rawColumns(['tipo_label', 'cambio_base'])
            ->make(true);
    }

    /**
     * Mostrar reporte general de vencimiento
     */
    public function expirationReport()
    {
        try {
            $service = new PurchaseInventoryService();
            $expiringProducts = $service->checkExpiringProducts(30);

            $html = view('inventory.partials.expiration-report', compact('expiringProducts'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el reporte de vencimiento: ' . $e->getMessage()
            ], 500);
        }
    }
}
