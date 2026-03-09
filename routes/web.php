<?php

use App\Http\Controllers\EconomicactivityController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\ContingenciasController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\MunicipalityController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacturacionElectronicaController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchasePaymentController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\PreSaleController;
use App\Http\Controllers\CorrelativoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\AIChatPageController;
use App\Http\Controllers\ProductUnitController;
use App\Http\Controllers\SaleWithUnitsController;
use App\Http\Controllers\PreSaleWithUnitsController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ProductPriceSaleController;
use App\Http\Controllers\DteController;
use App\Http\Controllers\FirmadorController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\DebitNoteController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\MedicalConsultationController;
use App\Http\Controllers\LabOrderController;
use App\Http\Controllers\LabExamController;
use App\Http\Controllers\LabExamCategoryController;
use App\Http\Controllers\LabResultController;
use App\Http\Controllers\FacturacionIntegralController;
use App\Http\Controllers\PharmaceuticalLaboratoryController;
use App\Http\Controllers\ProductCategoryController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

//Route::get('/dashboard', function () { return view('dashboard');})->middleware(['auth', 'verified'])->name('dashboard');

// Dashboard Tienda - principal para la tienda
Route::get('/dashboard', [DashboardController::class, 'storeDashboard'])->middleware(['auth', 'verified'])->name('dashboard');

// (Opcional) Dashboard Ejecutivo - se deja disponible sin estar en el menú
// Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->middleware(['auth', 'verified'])->name('dashboard.analytics');

// Alias explícito para dashboard de tienda (por si se quiere usar la ruta corta)
Route::get('/dashboard-tienda', [DashboardController::class, 'storeDashboard'])->middleware(['auth', 'verified'])->name('dashboard.tienda');

// Dashboards específicos por módulo
Route::get('/dashboard-farmacia', [DashboardController::class, 'home'])->middleware(['auth', 'verified'])->name('dashboard.farmacia');
Route::get('/dashboard-clinica', [DashboardController::class, 'home'])->middleware(['auth', 'verified'])->name('dashboard.clinica');
Route::get('/dashboard-laboratorio', [DashboardController::class, 'home'])->middleware(['auth', 'verified'])->name('dashboard.laboratorio');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

Route::group(['prefix' => 'client', 'as' => 'client.'], function(){

    Route::get('index/{company?}', [ClientController::class, 'index'])->name('index');
    Route::get('getclientbycompany/{company}', [ClientController::class, 'getclientbycompany'])->name('getclientbycompany');
    Route::get('view/{client}', [CompanyController::class, 'show'])->name('view');
    Route::get('edit/{client}', [ClientController::class, 'edit'])->name('edit');
    Route::get('getClientid/{client}', [ClientController::class, 'getClientid'])->name('getClientid');
    Route::get('keyclient/{num}/{tpersona}', [ClientController::class, 'keyclient'])->name('keyclient');
    Route::get('keyclient/{num}/{tpersona}/{campo}', [ClientController::class, 'keyclient'])->name('keyclient.field');
    Route::get('keyclient/{num}/{tpersona}/{campo}/{clientId}', [ClientController::class, 'keyclient'])->name('keyclient.edit');
    Route::get('gettypecontri/{client}', [ClientController::class, 'gettypecontri'])->name('gettypecontri');
    Route::patch('update', [ClientController::class, 'update'])->name('update');
    Route::get('create', [ClientController::class, 'create'])->name('create');
    Route::post('store', [ClientController::class, 'store'])->name('store');
    Route::get('destroy/{client}', [ClientController::class, 'destroy'])->name('destroy');

    });

Route::group(['prefix' => 'company', 'as' => 'company.'], function(){

    Route::get('index', [CompanyController::class, 'index'])->name('index');
    Route::get('view/{company}', [CompanyController::class, 'show'])->name('view');
    Route::get('getCompany', [CompanyController::class, 'getCompany'])->name('getCompany');
    Route::get('getCompanybyuser/{iduser}', [CompanyController::class, 'getCompanybyuser'])->name('getCompanybyuser');
    Route::get('gettypecontri/{company}', [CompanyController::class, 'gettypecontri'])->name('gettypecontri');
    Route::get('getcompanies', [CompanyController::class, 'getcompanies'])->name('getcompanies');
    Route::get('getCompanytag', [CompanyController::class, 'getCompanytag'])->name('getCompanytag');
    Route::get('getCompanyid/{company}', [CompanyController::class, 'getCompanyid'])->name('getCompanyid');
    Route::post('store', [CompanyController::class, 'store'])->name('store');
    Route::patch('update', [CompanyController::class, 'update'])->name('update');
    Route::get('destroy/{company}', [CompanyController::class, 'destroy'])->name('destroy');

    });

    Route::get('getcountry', [CountryController::class, 'getcountry'])->name('getcountry');
    Route::get('getdepartment/{pais}', [DepartmentController::class, 'getDepartment'])->name('getDepartment');
    Route::get('getmunicipality/{dep}', [MunicipalityController::class, 'getMunicipality'])->name('getmunicipios');
    Route::get('geteconomicactivity/{pais}', [EconomicactivityController::class, 'geteconomicactivity'])->name('geteconomicactivity');
    Route::get('getroles', [RolController::class, 'getRoles'])->name('getroles');

Route::group(['prefix' => 'user', 'as' => 'user.'], function(){
    Route::get('index', [UserController::class, 'index'])->name('index');
    Route::get('getusers', [UserController::class, 'getusers'])->name('getusers');
    Route::get('getuserid/{user}', [UserController::class, 'getuserid'])->name('getuserid');
    Route::get('valmail/{mail}', [UserController::class, 'valmail'])->name('valmail');
    Route::post('store', [UserController::class, 'store'])->name('store');
    Route::patch('update', [UserController::class, 'update'])->name('update');
    Route::get('changedtatus/{user}/status/{status}', [UserController::class, 'changedtatus'])->name('changedtatus');
    Route::get('destroy/{user}', [UserController::class, 'destroy'])->name('destroy');
    Route::post('request-password-reset/{id}', [UserController::class, 'requestPasswordReset'])->name('request-password-reset');

    });

Route::group(['prefix' => 'rol', 'as' => 'rol.'], function(){
    Route::get('index', [RolController::class, 'index'])->name('index');
    Route::patch('update', [RolController::class, 'update'])->name('update');
    Route::post('store', [RolController::class, 'store'])->name('store');

    });

Route::group(['prefix' => 'permission', 'as' => 'permission.'], function(){
    Route::get('index', [PermissionController::class, 'index'])->name('index');
    Route::patch('update', [PermissionController::class, 'update'])->name('update');
    Route::post('store', [PermissionController::class, 'store'])->name('store');
    Route::get('destroy/{id}', [PermissionController::class, 'destroy'])->name('destroy');
    Route::get('getpermission', [PermissionController::class, 'getpermission'])->name('getpermission');
    Route::get('getmenujson', [PermissionController::class, 'getmenujson'])->name('getmenujson');

    // Rutas específicas para permisos de correlativos
    Route::get('correlativos-setup', function() { return view('admin.users.permissions.correlativos'); })->name('correlativos-setup');
    Route::post('create-correlativos-permissions', [PermissionController::class, 'createCorrelativosPermissions'])->name('create-correlativos-permissions');
    Route::post('assign-correlativos-permissions', [PermissionController::class, 'assignCorrelativosPermissions'])->name('assign-correlativos-permissions');

    // Rutas específicas para permisos de contingencias DTE
    Route::post('create-contingencias-permissions', [PermissionController::class, 'createContingenciasPermissions'])->name('create-contingencias-permissions');
    Route::post('assign-contingencias-permissions', [PermissionController::class, 'assignContingenciasPermissions'])->name('assign-contingencias-permissions');

    // Rutas específicas para permisos de reportes
    Route::post('create-reports-permissions', [PermissionController::class, 'createReportsPermissions'])->name('create-reports-permissions');
    Route::post('assign-reports-permissions', [PermissionController::class, 'assignReportsPermissions'])->name('assign-reports-permissions');

    // Rutas específicas para permisos de respaldos
    Route::post('create-backups-permissions', [PermissionController::class, 'createBackupsPermissions'])->name('create-backups-permissions');
    Route::post('assign-backups-permissions', [PermissionController::class, 'assignBackupsPermissions'])->name('assign-backups-permissions');

    // Sincronizar todos los permisos desde las rutas
    Route::get('sync-permissions', [PermissionController::class, 'syncPermissionsView'])->name('sync-permissions-view');
    Route::post('sync-all-permissions', [PermissionController::class, 'syncAllPermissionsFromRoutes'])->name('sync-all-permissions');

    });

Route::group(['prefix' => 'provider', 'as' => 'provider.'], function(){
        Route::get('index', [ProviderController::class, 'index'])->name('index');
        Route::get('getproviders', [ProviderController::class, 'getproviders'])->name('getproviders');
        Route::get('getproviderid/{id}', [ProviderController::class, 'getproviderid'])->name('getproviderid');
        Route::patch('update', [ProviderController::class, 'update'])->name('update');
        Route::post('store', [ProviderController::class, 'store'])->name('store');
        Route::get('destroy/{id}', [ProviderController::class, 'destroy'])->name('destroy');
        Route::get('getpermission', [ProviderController::class, 'getpermission'])->name('getpermission');

        // Rutas para validación AJAX
        Route::post('validate-ncr', [ProviderController::class, 'validateNCR'])->name('validate-ncr');
        Route::post('validate-nit', [ProviderController::class, 'validateNIT'])->name('validate-nit');

    });

    Route::group(['prefix' => 'marcas', 'as' => 'marcas.'], function(){
        Route::get('index', [MarcaController::class, 'index'])->name('index');
        Route::get('getmarcas', [MarcaController::class, 'getmarcas'])->name('getmarcas');
        Route::get('getmarcaid/{id}', [MarcaController::class, 'getmarcaid'])->name('getmarcaid');
        Route::patch('update', [MarcaController::class, 'update'])->name('update');
        Route::post('store', [MarcaController::class, 'store'])->name('store');
        Route::get('destroy/{id}', [MarcaController::class, 'destroy'])->name('destroy');
        Route::get('getpermission', [MarcaController::class, 'getpermission'])->name('getpermission');

    });

    // Rutas para Laboratorios Farmacéuticos
    Route::resource('pharmaceutical-laboratories', PharmaceuticalLaboratoryController::class)
        ->names('pharmaceutical-laboratories');
    Route::get('pharmaceutical-laboratories/get/laboratories', [PharmaceuticalLaboratoryController::class, 'getLaboratories'])->name('pharmaceutical-laboratories.get');



Route::group(['prefix' => 'product', 'as' => 'product.'], function(){
    Route::get('index', [ProductController::class, 'index'])->name('index');
    Route::get('getproductid/{id}', [ProductController::class, 'getproductid'])->name('getproductid');
    Route::get('getproductcode/{code}', [ProductController::class, 'getproductcode'])->name('getproductcode');
    Route::get('getproductall', [ProductController::class, 'getproductall'])->name('getproductall');
    Route::patch('update', [ProductController::class, 'update'])->name('update');
    Route::post('store', [ProductController::class, 'store'])->name('store');
    Route::get('destroy/{id}', [ProductController::class, 'destroy'])->name('destroy');
    Route::post('toggleState/{id}', [ProductController::class, 'toggleState'])->name('toggleState');
    Route::get('getpermission', [ProductController::class, 'getpermission'])->name('getpermission');
    Route::get('expiration-tracking/{productId}', [ProductController::class, 'expirationTracking'])->name('expiration-tracking');
    Route::post('check-code-exists', [ProductController::class, 'checkCodeExists'])->name('check-code-exists');

    // Rutas para precios múltiples
    Route::group(['prefix' => '{productId}/prices', 'as' => 'prices.'], function(){
        Route::get('/', [\App\Http\Controllers\ProductPriceController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\ProductPriceController::class, 'create'])->name('create');
        Route::post('/store', [\App\Http\Controllers\ProductPriceController::class, 'store'])->name('store');
        Route::get('/{priceId}/edit', [\App\Http\Controllers\ProductPriceController::class, 'edit'])->name('edit');
        Route::put('/{priceId}', [\App\Http\Controllers\ProductPriceController::class, 'update'])->name('update');
        Route::delete('/{priceId}', [\App\Http\Controllers\ProductPriceController::class, 'destroy'])->name('destroy');
        Route::post('/bulk', [\App\Http\Controllers\ProductPriceController::class, 'createBulkPrices'])->name('bulk');

        // Rutas API
        Route::get('/api/prices', [\App\Http\Controllers\ProductPriceController::class, 'getProductPrices'])->name('api.prices');
        Route::get('/api/unit/{unitCode}', [\App\Http\Controllers\ProductPriceController::class, 'getPriceByUnit'])->name('api.unit');
        });
    });

    // Rutas para precios múltiples en ventas
    Route::group(['prefix' => 'product-prices', 'as' => 'product-prices.'], function(){
        Route::get('/product/{productId}/prices', [ProductPriceSaleController::class, 'getProductPrices'])->name('get-prices');
        Route::get('/product/{productId}/unit/{unitId}/price', [ProductPriceSaleController::class, 'getPriceByUnit'])->name('get-price-by-unit');
        Route::post('/calculate-sale-price', [ProductPriceSaleController::class, 'calculateSalePrice'])->name('calculate-sale-price');
        Route::get('/product/{productId}/price-info', [ProductPriceSaleController::class, 'getProductPriceInfo'])->name('get-price-info');
        Route::get('/product/{productId}/default-price', [ProductPriceSaleController::class, 'getDefaultPrice'])->name('get-default-price');
        Route::get('/product/{productId}/has-prices', [ProductPriceSaleController::class, 'hasConfiguredPrices'])->name('has-prices');
        Route::get('/product/{productId}/prices', [ProductPriceSaleController::class, 'getProductPricesForSelector'])->name('get-prices');
        Route::get('/product/{productId}/best-price', [ProductPriceSaleController::class, 'getBestPrice'])->name('get-best-price');
        Route::get('/product/{productId}/unit/{unitId}/price-types', [ProductPriceSaleController::class, 'getAvailablePriceTypes'])->name('get-price-types');
        Route::get('/test-prices', [ProductPriceSaleController::class, 'testPrices'])->name('test-prices');
    });

    // ==== Unidades de venta por producto (CRUD AJAX) ====
    Route::group(['prefix' => 'product/{productId}/unit-conversions'], function(){
        Route::get('/', [\App\Http\Controllers\ProductUnitConversionController::class, 'list']);
        Route::get('/units', [\App\Http\Controllers\ProductUnitConversionController::class, 'listUnits']);
        Route::post('/', [\App\Http\Controllers\ProductUnitConversionController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\ProductUnitConversionController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\ProductUnitConversionController::class, 'destroy']);
    });

// Rutas para unidades de medida de productos
Route::group(['prefix' => 'product-units', 'as' => 'product-units.'], function(){
    Route::get('catalog', [ProductUnitController::class, 'getCatalogUnits'])->name('catalog');
    Route::get('units-by-type/{type}', [ProductUnitController::class, 'getUnitsByType'])->name('units-by-type');
    Route::get('product/{productId}/conversions', [ProductUnitController::class, 'getProductConversions'])->name('product-conversions');
    Route::get('product/{productId}/unit/{unitCode}', [ProductUnitController::class, 'getConversionByUnitCode'])->name('conversion-by-unit-code');
    Route::post('store', [ProductUnitController::class, 'store'])->name('store');
    Route::put('update/{id}', [ProductUnitController::class, 'update'])->name('update');
    Route::delete('destroy/{id}', [ProductUnitController::class, 'destroy'])->name('destroy');
    Route::patch('toggle-status/{id}', [ProductUnitController::class, 'toggleStatus'])->name('toggle-status');
});



// Rutas para preventa con unidades de medida
Route::group(['prefix' => 'presale-units', 'as' => 'presale-units.'], function(){
    Route::post('add-product', [PreSaleWithUnitsController::class, 'addProductToPreSale'])->name('add-product');
    Route::get('items', [PreSaleWithUnitsController::class, 'getPreVentaItems'])->name('items');
    Route::put('update-item', [PreSaleWithUnitsController::class, 'updatePreVentaItem'])->name('update-item');
    Route::delete('remove-item', [PreSaleWithUnitsController::class, 'removePreVentaItem'])->name('remove-item');
    Route::post('finalize', [PreSaleWithUnitsController::class, 'finalizePreVenta'])->name('finalize');
    Route::delete('clear', [PreSaleWithUnitsController::class, 'clearPreVenta'])->name('clear');
});

Route::group(['prefix' => 'sale', 'as' => 'sale.'], function(){
        Route::get('index', [SaleController::class, 'index'])->name('index');
        Route::get('create', [SaleController::class, 'create'])->name('create');
        Route::get('getproductid/{id}', [SaleController::class, 'getproductid'])->name('getproductid');
        Route::get('getproductbyid/{id}', [SaleController::class, 'getproductbyid'])->name('getproductbyid');
Route::get('user-drafts', [SaleController::class, 'getUserDrafts'])->name('user-drafts');
Route::get('getdraftproducts/{draftId}', [SaleController::class, 'getDraftProducts'])->name('get-draft-products');
        Route::post('calculate-unit-conversion', [SaleController::class, 'calculateUnitConversion'])->name('calculate-unit-conversion');
        Route::get('inventory-by-units/{productId}', [SaleController::class, 'getInventoryByUnits'])->name('inventory-by-units');
        Route::get('getdatadocbycorr/{corr}', [SaleController::class, 'getdatadocbycorr'])->name('getdatadocbycorr');
        Route::get('getdatadocbycorr2/{corr}', [SaleController::class, 'getdatadocbycorr2'])->name('getdatadocbycorr2');
        Route::get('updateclient/{idsale}/{clientid}', [SaleController::class, 'updateclient'])->name('updateclient');
        Route::post('update-payment-method', [SaleController::class, 'updatePaymentMethod'])->name('update-payment-method');
        Route::post('update-retencion-agente', [SaleController::class, 'updateRetencionAgente'])->name('update-retencion-agente');
        Route::patch('update', [SaleController::class, 'update'])->name('update');
        Route::post('store', [SaleController::class, 'store'])->name('store');
        Route::get('createdocument/{corr}/{amount}', [SaleController::class, 'createdocument'])->name('createdocument');
        Route::get('impdoc/{corr}', [SaleController::class, 'impdoc'])->name('impdoc');
        Route::get('ticket/{id}', [SaleController::class, 'printTicket'])->name('ticket');
        Route::get('ticket-direct/{id}', [SaleController::class, 'printTicketDirect'])->name('ticket-direct');
        Route::get('ticket-print/{id}', [SaleController::class, 'printTicketDirectToprinter'])->name('ticket-print');
        Route::get('ticket-raw/{id}', [SaleController::class, 'printTicketRaw'])->name('ticket-raw');
        Route::get('check-dte/{id}', [SaleController::class, 'checkDte'])->name('check-dte');
        Route::get('ticket-test/{id?}', function($id = 1) {
            return "Test ticket para venta ID: $id - <a href='" . route('sale.ticket', $id) . "' target='_blank'>Abrir Ticket</a>";
        })->name('ticket-test');
        Route::get('test-ticket/{id?}', [SaleController::class, 'testTicket'])->name('test-ticket');
        Route::get('printer-info', [SaleController::class, 'getPrinterInfo'])->name('printer-info');
        Route::get('destroy/{id}', [SaleController::class, 'destroy'])->name('destroy');
        Route::get('savefactemp/{idsale}/{clientid}/{productid}/{cantida}/{price}/{nosujeto}/{exento}/{gravado}/{iva}/{renta}/{retenido}/{acuenta}/{fpago}/{fee}/{reserva}/{ruta}/{destino}/{linea}/{canal}/{unitCode?}/{unitId?}/{conversionFactor?}', [SaleController::class, 'savefactemp'])->name('savefactemp');
        Route::get('newcorrsale/{typedocument}', [SaleController::class, 'newcorrsale'])->name('newcorrsale');
        Route::get('getdetailsdoc/{corr}', [SaleController::class, 'getdetailsdoc'])->name('getdetailsdoc');
        Route::get('destroysaledetail/{idsaledetail}', [SaleController::class, 'destroysaledetail'])->name('destroysaledetail');
        Route::get('ncr/{id_sale}', [SaleController::class, 'ncr'])->name('ncr');
        Route::get('envia_correo', [SaleController::class, 'envia_correo'])->name('envia_correo');
        Route::post('enviar_correo_offline', [SaleController::class, 'enviar_correo_offline'])->name('enviar_correo_offline');
        Route::post('enviar-factura-correo', [SaleController::class, 'enviarFacturaPorCorreo'])->name('enviar-factura-correo');
        Route::get('enviar-factura-correo-ejemplo', function() {
            return view('sales.enviar-factura-correo');
        })->name('enviar-factura-correo-ejemplo');
        Route::get('print/{id}', [SaleController::class, 'print'])->name('print');
        Route::get('destinos', [SaleController::class, 'destinos'])->name('destinos');
        Route::get('linea', [SaleController::class, 'linea'])->name('linea');
        Route::get('get-draft-preventa/{id}', [SaleController::class, 'getDraftPreventaData'])->name('get-draft-preventa');
        Route::post('recalculate-totals', [SaleController::class, 'recalculateSalesTotals'])->name('recalculate-totals');
        Route::post('corregir-credito-fiscal/{id}', [SaleController::class, 'corregirDatosCreditoFiscal'])->name('corregir-credito-fiscal');

        // Rutas para ventas con unidades de medida
        Route::post('sale-units/add-product', [SaleWithUnitsController::class, 'addProductToSaleWithUnits'])->name('sale-units.add-product');
        Route::get('sale-units/product/{productId}/units', [SaleWithUnitsController::class, 'getProductUnits'])->name('sale-units.product-units');
        Route::post('sale-units/calculate-price', [SaleWithUnitsController::class, 'calculateUnitPrice'])->name('sale-units.calculate-price');
        Route::post('sale-units/check-stock', [SaleWithUnitsController::class, 'checkStock'])->name('sale-units.check-stock');

        // Rutas para el nuevo diseño dinámico de ventas
        Route::get('create-dynamic', [SaleController::class, 'createDynamic'])->name('create-dynamic');
        Route::get('search-product', [SaleController::class, 'searchProduct'])->name('search-product');
        Route::post('add-product', [SaleController::class, 'addProduct'])->name('add-product');
        Route::post('remove-product', [SaleController::class, 'removeProduct'])->name('remove-product');
        Route::post('process-sale', [SaleController::class, 'processSale'])->name('process-sale');
        Route::post('save-draft', [SaleController::class, 'saveDraft'])->name('save-draft');
        Route::get('clients', [SaleController::class, 'getClients'])->name('clients');
        Route::get('products', [SaleController::class, 'getProducts'])->name('products');
        Route::get('get-correlativo', [SaleController::class, 'getCorrelativo'])->name('get-correlativo');
        Route::get('get-client-info', [SaleController::class, 'getClientInfo'])->name('get-client-info');
        Route::get('updateclient/{corr}/{clientId}', [SaleController::class, 'updateClient'])->name('update-client');

    });

// Rutas para Notas de Crédito
Route::group(['prefix' => 'credit-notes', 'as' => 'credit-notes.'], function(){
    Route::get('index', [CreditNoteController::class, 'index'])->name('index');
    Route::get('create', [CreditNoteController::class, 'create'])->name('create');
    Route::post('store/{sale_id}', [SaleController::class, 'ncr'])->name('store');
    Route::get('show/{creditNote}', [CreditNoteController::class, 'show'])->name('show');
    Route::get('edit/{creditNote}', [CreditNoteController::class, 'edit'])->name('edit');
    Route::put('update/{creditNote}', [CreditNoteController::class, 'update'])->name('update');
    Route::delete('destroy/{creditNote}', [CreditNoteController::class, 'destroy'])->name('destroy');
    Route::get('print/{creditNote}', [CreditNoteController::class, 'print'])->name('print');
    Route::post('send-email/{creditNote}', [CreditNoteController::class, 'sendEmail'])->name('send-email');
    Route::get('get-sale-products/{sale}', [CreditNoteController::class, 'getSaleProducts'])->name('get-sale-products');
});

// Rutas para Notas de Débito
Route::group(['prefix' => 'debit-notes', 'as' => 'debit-notes.'], function(){
    Route::get('index', [DebitNoteController::class, 'index'])->name('index');
    Route::get('create', [DebitNoteController::class, 'create'])->name('create');
    Route::post('store/{sale_id}', [SaleController::class, 'ndr'])->name('store');
    Route::get('show/{debitNote}', [DebitNoteController::class, 'show'])->name('show');
    Route::get('edit/{debitNote}', [DebitNoteController::class, 'edit'])->name('edit');
    Route::put('update/{debitNote}', [DebitNoteController::class, 'update'])->name('update');
    Route::delete('destroy/{debitNote}', [DebitNoteController::class, 'destroy'])->name('destroy');
    Route::get('print/{debitNote}', [DebitNoteController::class, 'print'])->name('print');
    Route::post('send-email/{debitNote}', [DebitNoteController::class, 'sendEmail'])->name('send-email');
    Route::get('get-sale-products/{sale}', [DebitNoteController::class, 'getSaleProducts'])->name('get-sale-products');
});

Route::group(['prefix' => 'purchase', 'as' => 'purchase.'], function(){
        Route::get('index', [PurchaseController::class, 'index'])->name('index');
        Route::post('store', [PurchaseController::class, 'store'])->name('store');
        Route::patch('update', [PurchaseController::class, 'update'])->name('update');
        Route::get('getpurchaseid/{id}', [PurchaseController::class, 'getpurchaseid'])->name('getpurchaseid');
        Route::get('destroy/{id}', [PurchaseController::class, 'destroy'])->name('destroy');

        // Nuevas rutas para el sistema mejorado
        Route::get('details/{id}', [PurchaseController::class, 'getDetails'])->name('details');
        Route::post('add-to-inventory/{id}', [PurchaseController::class, 'addToInventory'])->name('add-to-inventory');
        Route::get('products', [PurchaseController::class, 'getProducts'])->name('products');
        Route::get('expiring-products', [PurchaseController::class, 'getExpiringProducts'])->name('expiring-products');
        Route::get('expired-products', [PurchaseController::class, 'getExpiredProducts'])->name('expired-products');
        Route::get('expiring-products-view', [PurchaseController::class, 'expiringProductsView'])->name('expiring-products-view');
        Route::post('generate-expiration-dates', [PurchaseController::class, 'generateExpirationDates'])->name('generate-expiration-dates');
        Route::get('debug-data', [PurchaseController::class, 'debugData'])->name('debug-data');
        Route::get('profit-report/{id}', [PurchaseController::class, 'getProfitReport'])->name('profit-report');
        Route::get('debug-expiring', [PurchaseController::class, 'debugExpiringProducts'])->name('debug-expiring');
        Route::get('test-simple', [PurchaseController::class, 'testSimple'])->name('test-simple');
        Route::get('inventory-status', [PurchaseController::class, 'getInventoryStatus'])->name('inventory-status');
    });


    Route::group(['prefix' => 'credit', 'as' => 'credit.'], function(){
        Route::get('index', [CreditController::class, 'index'])->name('index');
        Route::post('store', [CreditController::class, 'store'])->name('store');
        Route::patch('update', [CreditController::class, 'update'])->name('update');
        Route::patch('addpay', [CreditController::class, 'addpay'])->name('addpay');
        Route::get('getinfocredit/{id}', [CreditController::class, 'getinfocredit'])->name('getinfocredit');
        Route::get('destroy/{id}', [CreditController::class, 'destroy'])->name('destroy');
    });

    Route::group(['prefix' => 'purchase-payment', 'as' => 'purchase-payment.'], function(){
        Route::get('index', [PurchasePaymentController::class, 'index'])->name('index');
        Route::post('add-payment', [PurchasePaymentController::class, 'addPayment'])->name('add-payment');
        Route::get('balance/{id}', [PurchasePaymentController::class, 'getPurchaseBalance'])->name('balance');
        Route::get('history/{id}', [PurchasePaymentController::class, 'getPaymentHistory'])->name('history');
    });

Route::group(['prefix' => 'report', 'as' => 'report.'], function(){
        Route::get('sales', [ReportsController::class, 'sales'])->name('sales');
        Route::get('purchases', [ReportsController::class, 'purchases'])->name('purchases');
        Route::get('reportsales/{company}/{year}/{period}', [ReportsController::class, 'reportsales'])->name('reportsales');
        Route::get('reportpurchases/{company}/{year}/{period}', [ReportsController::class, 'reportpurchases'])->name('reportpurchases');
        Route::get('contribuyentes', [ReportsController::class, 'contribuyentes'])->name('contribuyentes');
        Route::get('reportyear', [ReportsController::class, 'reportyear'])->name('reportyear');
        Route::post('yearsearch', [ReportsController::class, 'yearsearch'])->name('yearsearch');
        Route::post('contribusearch', [ReportsController::class, 'contribusearch'])->name('contribusearch');
        Route::get('directas', [ReportsController::class, 'directas'])->name('directas');
        Route::get('consumidor', [ReportsController::class, 'consumidor'])->name('consumidor');
        Route::post('consumidorsearch', [ReportsController::class, 'consumidorsearch'])->name('consumidorsearch');
        Route::get('bookpurchases', [ReportsController::class, 'bookpurchases'])->name('bookpurchases');
        Route::post('comprassearch', [ReportsController::class, 'comprassearch'])->name('comprassearch');
        Route::get('sales-by-client', [ReportsController::class, 'salesByClient'])->name('sales-by-client');
        Route::post('sales-by-client-search', [ReportsController::class, 'salesByClientSearch'])->name('sales-by-client-search');
        Route::get('sales-by-client-pdf', [ReportsController::class, 'salesByClientPdf'])->name('sales-by-client-pdf');
        Route::get('sales-by-client-details-pdf', [ReportsController::class, 'salesByClientDetailsPdf'])->name('sales-by-client-details-pdf');

        // Reporte de Cuentas por Pagar
        Route::get('accounts-payable', [ReportsController::class, 'accountsPayable'])->name('accounts-payable');
        Route::post('accounts-payable-search', [ReportsController::class, 'accountsPayableSearch'])->name('accounts-payable-search');
        Route::post('accounts-payable-pdf', [ReportsController::class, 'accountsPayablePdf'])->name('accounts-payable-pdf');
        Route::get('inventory', [ReportsController::class, 'inventory'])->name('inventory');
        Route::post('inventory-search', [ReportsController::class, 'inventorySearch'])->name('inventory-search');
        Route::get('inventory-by-category', [ReportsController::class, 'inventoryByCategory'])->name('inventory-by-category');
        Route::get('inventory-by-provider', [ReportsController::class, 'inventoryByProvider'])->name('inventory-by-provider');

        // Reporte de movimientos de inventario
        Route::get('inventory-movements', [ReportsController::class, 'inventoryMovements'])->name('inventory-movements');
        Route::post('inventory-movements-search', [ReportsController::class, 'inventoryMovementsSearch'])->name('inventory-movements-search');

        // Reporte Kardex de inventario
        Route::get('inventory-kardex', [ReportsController::class, 'inventoryKardex'])->name('inventory-kardex');
        Route::post('inventory-kardex', [ReportsController::class, 'inventoryKardex'])->name('inventory-kardex.search');

        // Nuevos reportes de ventas
        Route::get('sales-by-provider', [ReportsController::class, 'salesByProvider'])->name('sales-by-provider');
        Route::post('sales-by-provider-search', [ReportsController::class, 'salesByProviderSearch'])->name('sales-by-provider-search');
        Route::get('sales-analysis', [ReportsController::class, 'salesAnalysis'])->name('sales-analysis');
        Route::post('sales-analysis-search', [ReportsController::class, 'salesAnalysisSearch'])->name('sales-analysis-search');
        Route::get('sales-by-product', [ReportsController::class, 'salesByProduct'])->name('sales-by-product');
        Route::post('sales-by-product-search', [ReportsController::class, 'salesByProductSearch'])->name('sales-by-product-search');
        Route::get('sales-by-category', [ReportsController::class, 'salesByCategory'])->name('sales-by-category');
        Route::post('sales-by-category-search', [ReportsController::class, 'salesByCategorySearch'])->name('sales-by-category-search');
    });

// Rutas del módulo DTE (Documentos Tributarios Electrónicos)
Route::group(['prefix' => 'dte', 'as' => 'dte.'], function(){
    // Dashboard principal
    Route::get('dashboard', [DteController::class, 'dashboard'])->name('dashboard');
    Route::get('estadisticas-tiempo-real', [DteController::class, 'estadisticasTiempoReal'])->name('estadisticas-tiempo-real');

    // Gestión de documentos
    Route::get('documentos', [DteController::class, 'documentos'])->name('documentos');
    Route::get('show/{id}', [DteController::class, 'show'])->name('show');
    Route::get('reprocesar/{id}', [DteController::class, 'reprocesar'])->name('reprocesar');
    Route::delete('destroy/{id}', [DteController::class, 'destroy'])->name('destroy');

    // Procesamiento de cola
    Route::post('procesar-cola', [DteController::class, 'procesarCola'])->name('procesar-cola');
    Route::post('procesar-reintentos', [DteController::class, 'procesarReintentos'])->name('procesar-reintentos');

    // Gestión de errores
    Route::get('errores', [DteController::class, 'errores'])->name('errores');
    Route::get('errores-simple', [DteController::class, 'erroresSimple'])->name('errores-simple');
    Route::get('error-show/{id}', [DteController::class, 'errorShow'])->name('error-show');

    // Contingencias
    Route::get('contingencias', [DteController::class, 'contingencias'])->name('contingencias');
    Route::post('contingencias/store', [DteController::class, 'storeContingencia'])->name('contingencias.store');
    Route::get('contingencias/activate/{id}', [DteController::class, 'activateContingencia'])->name('contingencias.activate');
    Route::get('contingencias/process/{id}', [DteController::class, 'processContingencia'])->name('contingencias.process');

        // API para contingencias
        Route::get('dtes-para-contingencia', [DteController::class, 'getDtesParaContingencia'])->name('dtes-para-contingencia');
        Route::get('test-dtes-para-contingencia', [DteController::class, 'testDtesParaContingencia'])->name('test-dtes-para-contingencia');
        Route::get('ventas-con-contingencia', [DteController::class, 'ventasConContingencia'])->name('ventas-con-contingencia');

        // Ruta para autorizar contingencia
        Route::post('autorizar-contingencia', [DteController::class, 'autorizarContingencia'])->name('autorizar-contingencia');

        // Ruta para autorizar contingencia existente
        Route::post('autorizar-contingencia-existente', [DteController::class, 'autorizarContingenciaExistente'])->name('autorizar-contingencia-existente');

        // Ruta GET para autorizar contingencia (más simple)
        Route::get('autorizar-contingencia/{empresa}/{id}', [DteController::class, 'autorizarContingenciaGet'])->name('autorizar-contingencia-get');

        // Ruta para mostrar errores de contingencia
        Route::get('errores-contingencia/{id}', [DteController::class, 'mostrarErroresContingencia'])->name('errores-contingencia');

        // Ruta para obtener documentos de una contingencia
        Route::get('documentos-contingencia', [DteController::class, 'getDocumentosContingencia'])->name('documentos-contingencia');

        // Ruta para crear contingencia
        Route::post('crear-contingencia', [DteController::class, 'crearContingencia'])->name('crear-contingencia');

    // Reportes y estadísticas
    Route::get('reportes', [DteController::class, 'reportes'])->name('reportes');
    Route::get('estadisticas', [DteController::class, 'estadisticas'])->name('estadisticas');

    // Configuración
    Route::get('configuracion', [DteController::class, 'configuracion'])->name('configuracion');
    Route::post('configuracion/update', [DteController::class, 'updateConfiguracion'])->name('configuracion.update');
});

// Rutas legacy para compatibilidad
Route::group(['prefix' => 'factmh', 'as' => 'factmh.'], function(){
    Route::get('dashboard', [DteController::class, 'dashboard'])->name('dashboard');
    Route::get('mostrar_cola', [FacturacionElectronicaController::class, 'mostrar_cola'])->name('show_queue');
    Route::get('procesa_cola', [FacturacionElectronicaController::class, 'procesa_cola'])->name('run_queue');
    Route::get('muestra_enviados', [FacturacionElectronicaController::class, 'muestra_enviados'])->name('show_sends');
    Route::get('muestra_rechazados', [FacturacionElectronicaController::class, 'muestra_rechazados'])->name('show_rejected');
    Route::get('prueba_certificado', [FacturacionElectronicaController::class, 'prueba_certificado'])->name('test_crt');
});

Route::group(['prefix' => 'config', 'as' => 'config.'], function(){

    Route::get('index', [ConfigController::class, 'index'])->name('index');
    Route::post('store', [ConfigController::class, 'store'])->name('store');
    Route::post('update', [ConfigController::class, 'update'])->name('update');
    Route::get('get/{id}', [ConfigController::class, 'getconfigid'])->name('get');
    Route::delete('destroy/{id}', [ConfigController::class, 'destroy'])->name('destroy');
});

Route::group(['prefix' => 'factmh', 'as' => 'factmh.'], function(){

    Route::get('contingencias', [ContingenciasController::class, 'contingencias'])->name('contingencias');
    Route::post('store', [ContingenciasController::class, 'store'])->name('store');
    Route::get('autoriza_contingencia/{empresa}/{id}', [ContingenciasController::class, 'autoriza_contingencia'])->name('autoriza_contingencia');
    Route::get('procesa_contingencia/{id}', [ContingenciasController::class, 'procesa_contingencia'])->name('procesa_contingencia');
    Route::get('muestra_lote/{id}', [ContingenciasController::class, 'muestra_lote'])->name('muestra_lote');
    Route::get('update', [ConfigController::class, 'update'])->name('update');
    Route::get('getconfigid/{id}', [ConfigController::class, 'getconfigid'])->name('getconfigid');
    Route::get('destroy/{id}', [ConfigController::class, 'destroy'])->name('destroy');
});

Route::get('/generate-barcode/{code}', [BarcodeController::class, 'generate'])->name('generate.barcode');
Route::get('/barcode/{code}', [BarcodeController::class, 'generate'])->name('barcode.generate');

// Rutas de pre-ventas
Route::group(['prefix' => 'presales', 'as' => 'presales.'], function(){
    Route::get('index', [PreSaleController::class, 'index'])->name('index');
    Route::post('start-session', [PreSaleController::class, 'startSession'])->name('start-session');
    Route::post('search-product', [PreSaleController::class, 'searchProduct'])->name('search-product');
    Route::post('add-product', [PreSaleController::class, 'addProduct'])->name('add-product');
    Route::post('get-details', [PreSaleController::class, 'getSaleDetails'])->name('get-details');
    Route::post('remove-product', [PreSaleController::class, 'removeProduct'])->name('remove-product');
    Route::post('finalize', [PreSaleController::class, 'finalizeSale'])->name('finalize');
    Route::post('cancel', [PreSaleController::class, 'cancelSale'])->name('cancel');
    Route::get('daily-stats', [PreSaleController::class, 'getDailyStats'])->name('daily-stats');
    Route::get('print-receipt', [PreSaleController::class, 'printReceipt'])->name('print-receipt');
    Route::get('clients', [PreSaleController::class, 'getClients'])->name('clients');
    Route::get('session-info', [PreSaleController::class, 'getSessionInfo'])->name('session-info');
    Route::post('cleanup-expired', [PreSaleController::class, 'cleanupExpiredSessions'])->name('cleanup-expired');
    Route::get('drafts', [PreSaleController::class, 'getDrafts'])->name('drafts');
    Route::get('drafts-count', [PreSaleController::class, 'getDraftsCount'])->name('drafts-count');
});

// Rutas de inventario (solo requieren autenticación)
Route::resource('inventory', InventoryController::class);

Route::group(['prefix' => 'inve', 'as' => 'inve.'], function(){
        Route::post('store', [InventoryController::class, 'store'])->name('store');
        Route::get('edit/{id}', [InventoryController::class, 'show'])->name('edit');
        Route::put('edit/{id}', [InventoryController::class, 'update'])->name('edit.update');
        Route::delete('edit/{id}', [InventoryController::class, 'destroy'])->name('edit.destroy');
        Route::get('export', [InventoryController::class, 'export'])->name('export');
        Route::get('providers', [InventoryController::class, 'getProviders'])->name('providers');
        Route::get('list', [InventoryController::class, 'list'])->name('list');
        Route::post('toggle-state/{id}', [InventoryController::class, 'toggleState'])->name('toggle-state');
        Route::get('expiration-tracking/{productId}', [InventoryController::class, 'expirationTracking'])->name('expiration-tracking');
        Route::get('expiration-report', [InventoryController::class, 'expirationReport'])->name('expiration-report');
        Route::get('movements/{productId}', [InventoryController::class, 'movements'])->name('movements');
        Route::get('movements-data/{productId}', [InventoryController::class, 'movementsData'])->name('movements-data');
    });

// Rutas de correlativos
Route::group(['prefix' => 'correlativos', 'as' => 'correlativos.'], function(){
    // Rutas CRUD principales
    Route::get('/', [\App\Http\Controllers\CorrelativoController::class, 'index'])->name('index');
    Route::get('create', [\App\Http\Controllers\CorrelativoController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\CorrelativoController::class, 'store'])->name('store');
    Route::get('{id}', [\App\Http\Controllers\CorrelativoController::class, 'show'])->name('show');
    Route::get('{id}/edit', [\App\Http\Controllers\CorrelativoController::class, 'edit'])->name('edit');
    Route::put('{id}', [\App\Http\Controllers\CorrelativoController::class, 'update'])->name('update');
    Route::delete('{id}', [\App\Http\Controllers\CorrelativoController::class, 'destroy'])->name('destroy');

    // Rutas específicas
    Route::get('estadisticas/view', [\App\Http\Controllers\CorrelativoController::class, 'estadisticas'])->name('estadisticas');
    Route::post('{id}/reactivar', [\App\Http\Controllers\CorrelativoController::class, 'reactivar'])->name('reactivar');
    Route::patch('{id}/estado', [\App\Http\Controllers\CorrelativoController::class, 'cambiarEstado'])->name('cambiar-estado');

    // Rutas AJAX
    Route::get('por-empresa/ajax', [\App\Http\Controllers\CorrelativoController::class, 'porEmpresa'])->name('por-empresa');
});

// Rutas API para correlativos
Route::group(['prefix' => 'api/correlativos', 'as' => 'correlativos.api.'], function(){
    Route::post('siguiente-numero', [\App\Http\Controllers\CorrelativoController::class, 'apiSiguienteNumero'])->name('siguiente-numero');
    Route::post('validar-disponibilidad', [\App\Http\Controllers\CorrelativoController::class, 'apiValidarDisponibilidad'])->name('validar-disponibilidad');
    Route::get('estadisticas', [\App\Http\Controllers\CorrelativoController::class, 'apiEstadisticas'])->name('estadisticas');
    Route::get('por-empresa', [\App\Http\Controllers\CorrelativoController::class, 'porEmpresa'])->name('por-empresa-api');
});

// Rutas de Cotizaciones
Route::group(['prefix' => 'cotizaciones', 'as' => 'cotizaciones.'], function(){
    // Rutas CRUD principales
    Route::get('index', [QuotationController::class, 'index'])->name('index');
    Route::get('create', [QuotationController::class, 'create'])->name('create');
    Route::post('store', [QuotationController::class, 'store'])->name('store');
    Route::get('show/{id}', [QuotationController::class, 'show'])->name('show');
    Route::get('edit/{id}', [QuotationController::class, 'edit'])->name('edit');
    Route::patch('update/{id}', [QuotationController::class, 'update'])->name('update');
    Route::get('destroy/{id}', [QuotationController::class, 'destroy'])->name('destroy');

    // Rutas para cambiar estado
    Route::patch('change-status/{id}', [QuotationController::class, 'changeStatus'])->name('changeStatus');

    // Rutas para PDF
    Route::get('pdf/{id}', [QuotationController::class, 'generatePDF'])->name('pdf');
    Route::get('download/{id}', [QuotationController::class, 'downloadPDF'])->name('download');

    // Rutas para correo
    Route::post('send-email/{id}', [QuotationController::class, 'sendEmail'])->name('sendEmail');

    // Convertir cotización a venta
    Route::post('convert-to-sale/{id}', [QuotationController::class, 'convertToSale'])->name('convertToSale');

    // Rutas AJAX
    Route::get('get-quotations', [QuotationController::class, 'getQuotations'])->name('getQuotations');
    Route::get('get-quotation/{id}', [QuotationController::class, 'getQuotation'])->name('getQuotation');
});

// Rutas de IA
Route::group(['prefix' => 'ai', 'as' => 'ai.'], function(){
    Route::post('chat', [AIController::class, 'chat'])->name('chat');
    Route::post('analyze', [AIController::class, 'analyze'])->name('analyze');
    Route::get('settings', [AIController::class, 'getSettings'])->name('settings');
    Route::post('settings', [AIController::class, 'updateSettings'])->name('updateSettings');
    Route::get('conversations', [AIController::class, 'getConversations'])->name('conversations');
});

// Rutas del módulo de Chat IA (página dedicada)
Route::group(['prefix' => 'ai-chat', 'as' => 'ai-chat.'], function(){
    Route::get('/', [AIChatPageController::class, 'index'])->name('index');
    Route::post('send', [AIChatPageController::class, 'sendMessage'])->name('send');
    Route::get('history', [AIChatPageController::class, 'getHistory'])->name('history');
    Route::get('conversation/{id}', [AIChatPageController::class, 'getConversation'])->name('conversation');
    Route::delete('conversation/{id}', [AIChatPageController::class, 'deleteConversation'])->name('delete-conversation');
    Route::delete('clear-history', [AIChatPageController::class, 'clearHistory'])->name('clear-history');
    Route::post('settings', [AIChatPageController::class, 'updateSettings'])->name('settings');
});

// Rutas del módulo de Respaldos de Base de Datos
Route::group(['prefix' => 'backups', 'as' => 'backups.'], function(){
    Route::get('/', [BackupController::class, 'index'])->name('index');
    Route::post('create', [BackupController::class, 'create'])->name('create');
    Route::get('download/{filename}', [BackupController::class, 'download'])->name('download');
    Route::delete('destroy/{filename}', [BackupController::class, 'destroy'])->name('destroy');
    Route::post('restore/{filename}', [BackupController::class, 'restore'])->name('restore');
    Route::get('list', [BackupController::class, 'getBackups'])->name('list');
    Route::get('stats', [BackupController::class, 'getStats'])->name('stats');
    Route::get('debug', [BackupController::class, 'debug'])->name('debug');
    Route::get('test', [BackupController::class, 'test'])->name('test');
});




});

// Rutas del módulo Firmador
Route::group(['prefix' => 'firmador', 'as' => 'firmador.'], function(){
    // Vista principal de pruebas
    Route::get('test', [FirmadorController::class, 'test'])->name('test');

    // API endpoints para pruebas
    Route::get('server-info', [FirmadorController::class, 'serverInfo'])->name('server-info');
    Route::get('ambientes', [FirmadorController::class, 'ambientes'])->name('ambientes');
    Route::post('test-connection', [FirmadorController::class, 'testConnection'])->name('test-connection');
    Route::post('test-firma', [FirmadorController::class, 'testFirma'])->name('test-firma');
    Route::get('test-network', [FirmadorController::class, 'testNetwork'])->name('test-network');
});

// ============================================================================
// MÓDULO DE CLÍNICA MÉDICA
// ============================================================================

// Rutas de Pacientes
Route::group(['prefix' => 'patients', 'as' => 'patients.', 'middleware' => 'auth'], function(){
    Route::get('/', [PatientController::class, 'index'])->name('index');
    Route::get('/data', [PatientController::class, 'getPatients'])->name('data');
    Route::get('/create', [PatientController::class, 'create'])->name('create');
    Route::post('/store', [PatientController::class, 'store'])->name('store');
    Route::post('/store-quick', [PatientController::class, 'storeQuick'])->name('store-quick');
    Route::get('/{id}', [PatientController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [PatientController::class, 'edit'])->name('edit');
    Route::put('/{id}', [PatientController::class, 'update'])->name('update');
    Route::delete('/{id}', [PatientController::class, 'destroy'])->name('destroy');
    Route::get('/search/document', [PatientController::class, 'searchByDocument'])->name('search-document');
});

// Rutas de Médicos
Route::group(['prefix' => 'doctors', 'as' => 'doctors.', 'middleware' => 'auth'], function(){
    Route::get('/', [DoctorController::class, 'index'])->name('index');
    Route::get('/data', [DoctorController::class, 'getDoctors'])->name('data');
    Route::get('/active', [DoctorController::class, 'getActiveDoctors'])->name('active');
    Route::get('/create', [DoctorController::class, 'create'])->name('create');
    Route::post('/store', [DoctorController::class, 'store'])->name('store');
    Route::get('/{id}', [DoctorController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [DoctorController::class, 'edit'])->name('edit');
    Route::put('/{id}', [DoctorController::class, 'update'])->name('update');
    Route::delete('/{id}', [DoctorController::class, 'destroy'])->name('destroy');
});

// Rutas de Citas Médicas
Route::group(['prefix' => 'appointments', 'as' => 'appointments.', 'middleware' => 'auth'], function(){
    Route::get('/', [AppointmentController::class, 'index'])->name('index');
    Route::get('/data', [AppointmentController::class, 'getAppointments'])->name('data');
    Route::get('/create', [AppointmentController::class, 'create'])->name('create');
    Route::post('/store', [AppointmentController::class, 'store'])->name('store');
    Route::get('/{id}', [AppointmentController::class, 'show'])->name('show');
    Route::put('/{id}/status', [AppointmentController::class, 'updateStatus'])->name('update-status');
    Route::post('/{id}/cancel', [AppointmentController::class, 'cancel'])->name('cancel');
    Route::get('/available-hours', [AppointmentController::class, 'getAvailableHours'])->name('available-hours');
});

// Rutas de Consultas Médicas
Route::group(['prefix' => 'consultations', 'as' => 'consultations.', 'middleware' => 'auth'], function(){
    Route::get('/', [MedicalConsultationController::class, 'index'])->name('index');
    Route::get('/data', [MedicalConsultationController::class, 'getConsultations'])->name('data');
    Route::get('/create', [MedicalConsultationController::class, 'create'])->name('create');
    Route::post('/store', [MedicalConsultationController::class, 'store'])->name('store');
    Route::get('/{id}', [MedicalConsultationController::class, 'show'])->name('show');
    Route::put('/{id}', [MedicalConsultationController::class, 'update'])->name('update');
    Route::post('/{id}/finalize', [MedicalConsultationController::class, 'finalize'])->name('finalize');
    Route::get('/patient/{patient_id}/history', [MedicalConsultationController::class, 'patientHistory'])->name('patient-history');
});

// ============================================================================
// MÓDULO DE LABORATORIO CLÍNICO
// ============================================================================

// Rutas de Órdenes de Laboratorio
Route::group(['prefix' => 'lab-orders', 'as' => 'lab-orders.', 'middleware' => 'auth'], function(){
    Route::get('/', [LabOrderController::class, 'index'])->name('index');
    Route::get('/data', [LabOrderController::class, 'getOrders'])->name('data');
    Route::get('/pending-count', [LabOrderController::class, 'getPendingCount'])->name('pending-count');
    Route::get('/create', [LabOrderController::class, 'create'])->name('create');
    Route::post('/store', [LabOrderController::class, 'store'])->name('store');
    Route::get('/{id}', [LabOrderController::class, 'show'])->name('show');
    Route::put('/{id}/status', [LabOrderController::class, 'updateStatus'])->name('update-status');
    Route::get('/{id}/print', [LabOrderController::class, 'print'])->name('print');
});

// Rutas de Categorías de Productos (Farmacia)
Route::group(['prefix' => 'product-categories', 'as' => 'product-categories.', 'middleware' => 'auth'], function(){
    Route::get('/', [ProductCategoryController::class, 'index'])->name('index');
    Route::post('/store', [ProductCategoryController::class, 'store'])->name('store');
    Route::put('/{id}', [ProductCategoryController::class, 'update'])->name('update');
    Route::delete('/{id}', [ProductCategoryController::class, 'destroy'])->name('destroy');
});

// Rutas de Categorías de Exámenes
Route::group(['prefix' => 'lab-categories', 'as' => 'lab-categories.', 'middleware' => 'auth'], function(){
    Route::get('/', [LabExamCategoryController::class, 'index'])->name('index');
    Route::post('/store', [LabExamCategoryController::class, 'store'])->name('store');
    Route::put('/{id}', [LabExamCategoryController::class, 'update'])->name('update');
    Route::delete('/{id}', [LabExamCategoryController::class, 'destroy'])->name('destroy');
});

// Rutas de Catálogo de Exámenes
Route::group(['prefix' => 'lab-exams', 'as' => 'lab-exams.', 'middleware' => 'auth'], function(){
    Route::get('/', [LabExamController::class, 'index'])->name('index');
    Route::get('/data', [LabExamController::class, 'getExams'])->name('data');
    Route::get('/create', [LabExamController::class, 'create'])->name('create');
    Route::post('/store', [LabExamController::class, 'store'])->name('store');
    Route::post('/clear-all', [LabExamController::class, 'clearAll'])->name('clear-all');
    Route::post('/force-clear-all', [LabExamController::class, 'forceClearAll'])->name('force-clear-all');
    Route::get('/{id}', [LabExamController::class, 'show'])->name('show');
    Route::put('/{id}', [LabExamController::class, 'update'])->name('update');
    Route::delete('/{id}', [LabExamController::class, 'destroy'])->name('destroy');
    Route::get('/active/list', [LabExamController::class, 'getActiveExams'])->name('active');
    Route::post('/{id}/toggle-status', [LabExamController::class, 'toggleStatus'])->name('toggle-status');
});

// Rutas de Resultados de Laboratorio
Route::group(['prefix' => 'lab-results', 'as' => 'lab-results.', 'middleware' => 'auth'], function(){
    Route::get('/{orderExamId}/create', [LabResultController::class, 'create'])->name('create');
    Route::post('/{orderExamId}/store', [LabResultController::class, 'store'])->name('store');
    Route::get('/{resultId}/edit', [LabResultController::class, 'edit'])->name('edit');
    Route::put('/{resultId}/update', [LabResultController::class, 'update'])->name('update');
    Route::post('/{resultId}/validate', [LabResultController::class, 'validateResult'])->name('validate');
    Route::get('/{orderExamId}/pdf', [LabResultController::class, 'printPdf'])->name('pdf');
    Route::get('/{orderExamId}/pdf/download', [LabResultController::class, 'downloadPdf'])->name('pdf.download');
    Route::get('/{orderExamId}/doc', [LabResultController::class, 'printDoc'])->name('doc');
    Route::get('/{orderExamId}/doc/download', [LabResultController::class, 'downloadDoc'])->name('doc.download');
    Route::get('/{orderExamId}/doc/download', [LabResultController::class, 'downloadDoc'])->name('doc.download');
    Route::get('/{orderExamId}/doc', [LabResultController::class, 'printDoc'])->name('doc');
});

// ============================================================================
// MÓDULO DE FACTURACIÓN INTEGRAL
// ============================================================================

// Facturación integral - Todos los módulos
Route::group(['prefix' => 'facturacion', 'as' => 'facturacion.', 'middleware' => 'auth'], function(){
    Route::get('/integral', [FacturacionIntegralController::class, 'index'])->name('integral');
    Route::get('/consultas-pendientes', [FacturacionIntegralController::class, 'getConsultasPendientes'])->name('consultas-pendientes');
    Route::get('/ordenes-lab-pendientes', [FacturacionIntegralController::class, 'getOrdenesLabPendientes'])->name('ordenes-lab-pendientes');
    Route::post('/consulta/{consultaId}', [FacturacionIntegralController::class, 'facturarConsulta'])->name('facturar-consulta');
    Route::post('/orden-lab/{ordenId}', [FacturacionIntegralController::class, 'facturarOrdenLab'])->name('facturar-orden-lab');
    Route::get('/precio-servicio', [FacturacionIntegralController::class, 'getPrecioServicio'])->name('precio-servicio');
});

// Ruta alternativa más corta
Route::get('/facturacion-integral', [FacturacionIntegralController::class, 'index'])->middleware('auth')->name('facturacion-integral');

// ============================================================================
// MÓDULO DE TUTORIALES
// ============================================================================
Route::group(['prefix' => 'tutorials', 'as' => 'tutorials.', 'middleware' => 'auth'], function(){
    Route::get('/', [App\Http\Controllers\TutorialController::class, 'index'])->name('index');
    Route::get('/{file}', [App\Http\Controllers\TutorialController::class, 'show'])->name('show');
});

require __DIR__.'/auth.php';
