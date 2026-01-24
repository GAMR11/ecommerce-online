<?php

use App\Models\Cliente;
use App\Models\KardexCliente;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\GaranteController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ProductoController;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\HistorialPagoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Auth::routes();

Route::get('/', function () {
    return view('auth.login');
});

Route::middleware(['auth'])->group(function () {

Route::get('/dashboard',action: [HomeController::class,'dashboard'])->name('dashboard');

Route::resource('categoria', CategoriaController::class);
Route::get('/form-cargar-archivo-categoria',[CategoriaController::class,'formUploadFile'])->name('categoria.formUploadFile');
Route::post('/cargar-archivo-categoria',[CategoriaController::class,'uploadFile'])->name('categoria.uploadFile');

Route::resource('producto', ProductoController::class);
Route::get('/form-cargar-archivo-producto',[ProductoController::class,'formUploadFile'])->name('producto.formUploadFile');
Route::post('/cargar-archivo-producto',[ProductoController::class,'uploadFile'])->name('producto.uploadFile');
Route::get('/products-reports',action: [ProductoController::class,'reports'])->name('producto.reports');
Route::get('/generar-reporte',action: [ProductoController::class,'generarReporte'])->name('producto.generarReporte');
Route::get('/descargar-reporte-excel',action: [ProductoController::class,'descargarReporteExcel'])->name('producto.descargarReporteExcel');
Route::get('/descargar-reporte-pdf',action: [ProductoController::class,'descargarReportePDF'])->name('producto.descargarReportePDF');


Route::resource('inventario', InventarioController::class);

Route::resource('venta', VentaController::class);
Route::get('/form-venta',[VentaController::class,'form'])->name('venta.form');
Route::post('/guardar-venta',[VentaController::class,'guardarVenta'])->name('venta.guardarVenta');
Route::get('/historial-venta',[VentaController::class,'historialVenta'])->name('venta.historial');


Route::resource('pago', HistorialPagoController::class);
Route::get('/form-pago',[HistorialPagoController::class,'formPago'])->name('pago.form');
Route::get('/compras-by-cliente',[HistorialPagoController::class,'comprasByCliente'])->name('pago.comprasByCliente');
Route::get('/pagos-by-articulo/{client_id}/{kardex_id}',[HistorialPagoController::class,'pagosByArticulo'])->name('pago.pagosByArticulo');
Route::post('/actualizar-comprobante-pago',action: [HistorialPagoController::class,'actualizarPago'])->name('pago.actualizarPago');
Route::get('/descargar-comprobante-pago/{id}',action: [HistorialPagoController::class,'descargarComprobante'])->name('pago.download');
Route::post('/agregar-mora',action: [HistorialPagoController::class,'agregarMora'])->name('pago.agregarMora');


Route::resource('cliente', ClienteController::class);
Route::get('/buscar-by-identificacion',[ClienteController::class,'buscarByIdentificacion'])->name('cliente.buscarByIdentificacion');
Route::get('/form-cargar-archivo-cliente',[ClienteController::class,'formUploadFile'])->name('cliente.formUploadFile');
Route::post('/cargar-archivo-cliente',[ClienteController::class,'uploadFile'])->name('cliente.uploadFile');
Route::get('/creditos-por-cliente/{id}',[ClienteController::class,'creditos'])->name('cliente.creditos');
Route::get('/detalle-credito/{id}',[ClienteController::class,'detalleCredito'])->name('cliente.detalleCredito');

Route::resource('garante', GaranteController::class);
Route::get('/buscar-garante-by-identificacion',[GaranteController::class,'buscarByIdentificacion'])->name('garante.buscarByIdentificacion');
Route::get('/form-cargar-archivo-garante',[GaranteController::class,'formUploadFile'])->name('garante.formUploadFile');
Route::post('/cargar-archivo-garante',[GaranteController::class,'uploadFile'])->name('garante.uploadFile');


Route::resource('reporte', ReporteController::class);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/send-sms', [App\Http\Controllers\AlertaController::class, 'sendSMS'])->name('alerta.sendSMS');

Route::get('/generar-factura',[App\Http\Controllers\HomeController::class, 'generarFacturaXML'])->name('home.generarFacturaXML');

});



