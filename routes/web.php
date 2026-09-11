<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\FabricanteController;
use App\Http\Controllers\TipoEquipoController;
use App\Http\Controllers\ClaseEquipoController;
use App\Http\Controllers\EquipoManagementController;
use App\Http\Controllers\EquipoInventarioController;
use App\Http\Controllers\EquiposBajaController;
use App\Http\Controllers\EquiposAuditoriaController;
use App\Http\Controllers\MaterialDidacticoController;
use App\Http\Controllers\TraspasoController;
use App\Http\Controllers\FormatosController;
use App\Http\Controllers\HojaVidaController;
use App\Http\Controllers\InspeccionController;
use App\Http\Controllers\ExportarController;
use App\Http\Controllers\EmpresaManagementController;
use App\Http\Controllers\Auth\ForcedPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AsignarController;
use App\Http\Controllers\SugerenciaController;
use App\Http\Controllers\PrestamoTemporalController;
use App\Http\Controllers\ManualTecnicoController;
use App\Http\Controllers\PublicStorageController;
use App\Http\Controllers\InstallController;

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

Route::get('/instalar', [InstallController::class, 'show'])->name('install.show');
Route::post('/instalar', [InstallController::class, 'store'])->name('install.store');

// Dashboard principal: requiere autenticación.
Route::middleware(['auth'])->get('/', [DashboardController::class, 'index'])->name('dashboard');

// Bienvenida / información del sistema (sin sesión). Usuarios autenticados van al panel admin.
Route::get('/inicio', [DashboardController::class, 'index'])->name('sistema.info');

// Fallback para servir archivos públicos en entornos donde el enlace public/storage falle.
// Se protege con auth para evitar exponer archivos sensibles por accidente.
Route::middleware(['auth'])->get('/storage/{path}', [PublicStorageController::class, 'show'])
    ->where('path', '.*')
    ->name('storage.public');

// Contraseña obligatoria (tras registro / alta): sin este middleware de bloqueo
Route::middleware(['auth'])->group(function () {
    Route::get('/password/segura', [ForcedPasswordController::class, 'show'])->name('password.secure.show');
    Route::post('/password/segura', [ForcedPasswordController::class, 'update'])->name('password.secure.update');
});

// Perfil (requiere ya haber definido contraseña segura si aplica)
Route::middleware(['auth', 'password.must_change'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Rutas de administración (perfil completo)
Route::middleware(['auth', 'password.must_change', 'profile.complete'])->group(function () {
    Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/dashboard/equipos-chart-data', [AdminController::class, 'equiposChartData'])->name('admin.dashboard.equipos-chart-data');
    Route::get('/admin/docs/manual-tecnico', [ManualTecnicoController::class, 'download'])->name('admin.docs.manual-tecnico');
    Route::get('/admin/docs/manual-tecnico.pdf', [ManualTecnicoController::class, 'downloadPdf'])->name('admin.docs.manual-tecnico-pdf');

    Route::get('/sugerencias', [SugerenciaController::class, 'index'])->name('sugerencias.index');
    Route::post('/sugerencias', [SugerenciaController::class, 'store'])->name('sugerencias.store');
    Route::post('/sugerencias/{aviso}/cumplir', [SugerenciaController::class, 'cumplir'])->name('sugerencias.cumplir')->whereNumber('aviso');

    Route::prefix('formatos')->name('formatos.')->group(function () {
        Route::get('/', [FormatosController::class, 'index'])->name('index');
        Route::post('/', [FormatosController::class, 'store'])->name('store');
        Route::get('/plantilla/{plantilla}/reemplazar', [FormatosController::class, 'formReemplazar'])->name('reemplazar.form')->whereNumber('plantilla');
        Route::post('/plantilla/{plantilla}/reemplazar', [FormatosController::class, 'reemplazar'])->name('reemplazar')->whereNumber('plantilla');
        Route::get('/{plantilla}/download', [FormatosController::class, 'download'])->name('download')->whereNumber('plantilla');
        Route::get('/clase/{clase}', [FormatosController::class, 'clase'])->name('clase')->whereNumber('clase');
        Route::get('/clase/{clase}/equipo/{equipo}', [FormatosController::class, 'show'])->name('show')->whereNumber('clase')->whereNumber('equipo');
        Route::get('/clase/{clase}/equipo/{equipo}/html', [FormatosController::class, 'html'])->name('html')->whereNumber('clase')->whereNumber('equipo');
        Route::get('/clase/{clase}/equipo/{equipo}/pdf', [FormatosController::class, 'pdf'])->name('pdf')->whereNumber('clase')->whereNumber('equipo');
    });

    Route::prefix('hoja-vida')->name('hoja-vida.')->group(function () {
        Route::get('/', [HojaVidaController::class, 'index'])->name('index');
        Route::get('/clase/{clase}', [HojaVidaController::class, 'clase'])->name('clase')->whereNumber('clase');
        Route::get('/clase/{clase}/equipo/{equipo}', [HojaVidaController::class, 'form'])->name('form')->whereNumber('clase')->whereNumber('equipo');
        Route::post('/clase/{clase}/equipo/{equipo}', [HojaVidaController::class, 'store'])->name('store')->whereNumber('clase')->whereNumber('equipo');
        Route::get('/clase/{clase}/equipo/{equipo}/download', [HojaVidaController::class, 'download'])->name('download')->whereNumber('clase')->whereNumber('equipo');
        Route::get('/clase/{clase}/equipo/{equipo}/pdf/preview', [HojaVidaController::class, 'pdfPreview'])->name('pdf.preview')->whereNumber('clase')->whereNumber('equipo');
        Route::get('/clase/{clase}/equipo/{equipo}/pdf/status', [HojaVidaController::class, 'pdfStatus'])->name('pdf.status')->whereNumber('clase')->whereNumber('equipo');
        Route::get('/clase/{clase}/equipo/{equipo}/pdf/file', [HojaVidaController::class, 'pdfFile'])->name('pdf.file')->whereNumber('clase')->whereNumber('equipo');
        Route::get('/clase/{clase}/equipo/{equipo}/pdf', [HojaVidaController::class, 'pdf'])->name('pdf')->whereNumber('clase')->whereNumber('equipo');
        Route::get('/clase/{clase}/equipo/{equipo}/html', [HojaVidaController::class, 'html'])->name('html')->whereNumber('clase')->whereNumber('equipo');
    });

    Route::prefix('inspeccion')->name('inspeccion.')->group(function () {
        Route::get('/', [InspeccionController::class, 'index'])->name('index');
        Route::post('/plantilla', [InspeccionController::class, 'storePlantilla'])->name('plantilla.store');
        Route::get('/clase/{clase}/equipos', [InspeccionController::class, 'equipos'])->name('equipos')->whereNumber('clase');
        Route::get('/equipo/{equipo}/form', [InspeccionController::class, 'form'])->name('form')->whereNumber('equipo');
        Route::post('/verificar-obligatoria', [InspeccionController::class, 'verificarObligatoria'])->name('verificar-obligatoria');
        Route::post('/equipo/{equipo}', [InspeccionController::class, 'store'])->name('store')->whereNumber('equipo');
        Route::get('/registro/{inspeccion}/edit', [InspeccionController::class, 'edit'])->name('edit')->whereNumber('inspeccion');
        Route::put('/registro/{inspeccion}', [InspeccionController::class, 'update'])->name('update')->whereNumber('inspeccion');
        Route::get('/registro/{inspeccion}/html', [InspeccionController::class, 'html'])->name('html')->whereNumber('inspeccion');
        Route::get('/registro/{inspeccion}/download', [InspeccionController::class, 'download'])->name('download')->whereNumber('inspeccion');
        Route::get('/equipo/{equipo}/dar-de-baja', [InspeccionController::class, 'darDeBaja'])->name('dar-de-baja')->whereNumber('equipo');
    });

    Route::prefix('exportar')->name('exportar.')->group(function () {
        Route::post('/actualizar', [ExportarController::class, 'actualizar'])->name('actualizar');
        Route::get('/', [ExportarController::class, 'index'])->name('index');
        Route::get('/hoja-vida/{equipo}', [ExportarController::class, 'downloadHojaVida'])->name('hoja-vida')->whereNumber('equipo');
        Route::get('/inspeccion/{equipo}', [ExportarController::class, 'downloadInspeccion'])->name('inspeccion')->whereNumber('equipo');
        Route::get('/baja/{equipo}', [ExportarController::class, 'downloadBaja'])->name('baja')->whereNumber('equipo');
        Route::get('/hoja-vida-completa/{equipo}/preview', [ExportarController::class, 'previewHojaVidaCompleta'])->name('hoja-vida-completa.preview')->whereNumber('equipo');
        Route::post('/hoja-vida-completa/{equipo}/download', [ExportarController::class, 'downloadHojaVidaCompletaWithHtml'])->name('hoja-vida-completa.download')->whereNumber('equipo');
        Route::post('/hoja-vida-completa/{equipo}/guardar-hoja-vida', [ExportarController::class, 'guardarHojaVidaFromPreview'])->name('hoja-vida-completa.guardar-hoja-vida')->whereNumber('equipo');
        Route::get('/hoja-vida-completa/{equipo}', [ExportarController::class, 'downloadHojaVidaCompleta'])->name('hoja-vida-completa')->whereNumber('equipo');
    });

    // Asignar equipos a usuarios
    Route::get('/asignar', [AsignarController::class, 'index'])->name('asignar.index');
    Route::get('/mis-cosas', [AsignarController::class, 'misCosas'])->name('asignar.mis-cosas');
    Route::get('/asignar/users', [AsignarController::class, 'users'])->name('asignar.users');
    Route::get('/asignar/equipos', [AsignarController::class, 'equipos'])->name('asignar.equipos');
    Route::post('/asignar', [AsignarController::class, 'store'])->name('asignar.store');
    Route::post('/asignar/preview', [AsignarController::class, 'preview'])->name('asignar.preview');
    Route::post('/asignar/solicitud', [AsignarController::class, 'submitSolicitud'])->name('asignar.solicitud');
    Route::get('/asignar/solicitud/{solicitud}/aceptar', [AsignarController::class, 'showAceptarSolicitud'])->name('asignar.solicitud.aceptar')->whereNumber('solicitud');
    Route::post('/asignar/solicitud/{solicitud}/aceptar', [AsignarController::class, 'aceptarSolicitudFirmada'])->name('asignar.solicitud.aceptar.store')->whereNumber('solicitud');
    Route::post('/asignar/solicitud/{solicitud}/responder', [AsignarController::class, 'responderSolicitud'])->name('asignar.solicitud.responder')->whereNumber('solicitud');
    Route::get('/asignar/seguimiento', [AsignarController::class, 'seguimiento'])->name('asignar.seguimiento');
    Route::get('/asignar/seguimiento/{solicitud}/formato', [AsignarController::class, 'verFormatoSeguimiento'])->name('asignar.seguimiento.formato')->whereNumber('solicitud');
    Route::post('/asignar/seguimiento/{solicitud}/formato', [AsignarController::class, 'guardarFormatoSeguimiento'])->name('asignar.seguimiento.formato.store')->whereNumber('solicitud');
    Route::post('/asignar/seguimiento/{solicitud}/revision', [AsignarController::class, 'resolverRevisionDevolucion'])->name('asignar.seguimiento.revision')->whereNumber('solicitud');
    Route::get('/asignar/seguimiento/{solicitud}/devolucion', [AsignarController::class, 'devolucionForm'])->name('asignar.devolucion.form')->whereNumber('solicitud');
    Route::post('/asignar/seguimiento/{solicitud}/devolucion', [AsignarController::class, 'submitDevolucion'])->name('asignar.devolucion.store')->whereNumber('solicitud');
    Route::delete('/asignar/{asignacion}', [AsignarController::class, 'destroy'])->name('asignar.destroy')->whereNumber('asignacion');

    // Prestamos temporales
    Route::get('/prestamos-temporales', [PrestamoTemporalController::class, 'index'])->name('prestamos-temporales.index');
    Route::post('/prestamos-temporales/preview', [PrestamoTemporalController::class, 'preview'])->name('prestamos-temporales.preview');
    Route::post('/prestamos-temporales', [PrestamoTemporalController::class, 'store'])->name('prestamos-temporales.store');
    Route::get('/prestamos-temporales/{prestamo}/devolver', [PrestamoTemporalController::class, 'formDevolver'])->name('prestamos-temporales.devolver.form')->whereNumber('prestamo');
    Route::post('/prestamos-temporales/{prestamo}/devolver', [PrestamoTemporalController::class, 'devolver'])->name('prestamos-temporales.devolver')->whereNumber('prestamo');
    Route::get('/prestamos-temporales/{prestamo}/revisar', [PrestamoTemporalController::class, 'formRevisar'])->name('prestamos-temporales.revisar.form')->whereNumber('prestamo');
    Route::post('/prestamos-temporales/{prestamo}/revisar', [PrestamoTemporalController::class, 'revisar'])->name('prestamos-temporales.revisar')->whereNumber('prestamo');

    // Rutas de usuarios
    Route::get('/users/complete', [UserController::class, 'complete'])->name('users.complete');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::delete('/users/bulk', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::get('/users/{user}/pending-meta', [UserController::class, 'pendingMeta'])->name('users.pending-meta');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users/{user}/approve-pending', [UserController::class, 'approvePending'])->name('users.approve-pending');
    Route::post('/users/{user}/reject-pending', [UserController::class, 'rejectPending'])->name('users.reject-pending');
    
    // Rutas de roles
    Route::get('/roles/complete', [RoleController::class, 'complete'])->name('roles.complete');
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::post('/roles/{role}/toggle-status', [RoleController::class, 'toggleStatus'])->name('roles.toggle-status');
    
    // Rutas de cargos
    Route::get('/cargos/complete', [CargoController::class, 'complete'])->name('cargos.complete');
    Route::get('/cargos', [CargoController::class, 'index'])->name('cargos.index');
    Route::get('/cargos/create', [CargoController::class, 'create'])->name('cargos.create');
    Route::post('/cargos', [CargoController::class, 'store'])->name('cargos.store');
    Route::get('/cargos/{cargo}', [CargoController::class, 'show'])->name('cargos.show');
    Route::get('/cargos/{cargo}/edit', [CargoController::class, 'edit'])->name('cargos.edit');
    Route::put('/cargos/{cargo}', [CargoController::class, 'update'])->name('cargos.update');
    Route::delete('/cargos/{cargo}', [CargoController::class, 'destroy'])->name('cargos.destroy');
    Route::post('/cargos/{cargo}/toggle-status', [CargoController::class, 'toggleStatus'])->name('cargos.toggle-status');
    
    // Rutas de grupos
    Route::get('/grupos/complete', [GrupoController::class, 'complete'])->name('grupos.complete');
    Route::get('/grupos', [GrupoController::class, 'index'])->name('grupos.index');
    Route::get('/grupos/create', [GrupoController::class, 'create'])->name('grupos.create');
    Route::post('/grupos', [GrupoController::class, 'store'])->name('grupos.store');
    Route::get('/grupos/{grupo}', [GrupoController::class, 'show'])->name('grupos.show');
    Route::get('/grupos/{grupo}/users', [GrupoController::class, 'usersJson'])->name('grupos.users-json');
    Route::get('/grupos/{grupo}/edit', [GrupoController::class, 'edit'])->name('grupos.edit');
    Route::put('/grupos/{grupo}', [GrupoController::class, 'update'])->name('grupos.update');
    Route::delete('/grupos/{grupo}', [GrupoController::class, 'destroy'])->name('grupos.destroy');
    Route::post('/grupos/{grupo}/toggle-status', [GrupoController::class, 'toggleStatus'])->name('grupos.toggle-status');
    
    // Rutas de fabricantes
    Route::get('/fabricantes/complete', [FabricanteController::class, 'complete'])->name('fabricantes.complete');
    Route::get('/fabricantes', [FabricanteController::class, 'index'])->name('fabricantes.index');
    Route::get('/fabricantes/create', [FabricanteController::class, 'create'])->name('fabricantes.create');
    Route::post('/fabricantes', [FabricanteController::class, 'store'])->name('fabricantes.store');
    Route::get('/fabricantes/{fabricante}', [FabricanteController::class, 'show'])->name('fabricantes.show');
    Route::get('/fabricantes/{fabricante}/edit', [FabricanteController::class, 'edit'])->name('fabricantes.edit');
    Route::put('/fabricantes/{fabricante}', [FabricanteController::class, 'update'])->name('fabricantes.update');
    Route::delete('/fabricantes/{fabricante}', [FabricanteController::class, 'destroy'])->name('fabricantes.destroy');
    Route::post('/fabricantes/{fabricante}/toggle-status', [FabricanteController::class, 'toggleStatus'])->name('fabricantes.toggle-status');

    // Gestión de Empresa
    Route::get('/empresa/gestion', [EmpresaManagementController::class, 'gestion'])->name('empresa.gestion');
    Route::post('/empresa/entrar/{empresa}', [EmpresaManagementController::class, 'entrarEmpresa'])->name('empresa.entrar');
    Route::post('/empresa/salir', [EmpresaManagementController::class, 'salirEmpresa'])->name('empresa.salir');

    Route::post('/empresa/geocode', [EmpresaManagementController::class, 'geocode'])->middleware('throttle:geocode')->name('empresa.geocode');

    // Empresa CRUD
    Route::post('/empresa', [EmpresaManagementController::class, 'storeEmpresa'])->name('empresa.store');
    Route::get('/empresa/{empresa}/edit', [EmpresaManagementController::class, 'editEmpresa'])->name('empresa.edit');
    Route::get('/empresa/{empresa}', [EmpresaManagementController::class, 'showEmpresa'])->whereNumber('empresa')->name('empresa.show');
    Route::put('/empresa/{empresa}', [EmpresaManagementController::class, 'updateEmpresa'])->name('empresa.update');
    Route::delete('/empresa/{empresa}', [EmpresaManagementController::class, 'destroyEmpresa'])->name('empresa.destroy');
    Route::post('/empresa/{empresa}/toggle-status', [EmpresaManagementController::class, 'toggleEmpresaStatus'])->name('empresa.toggle-status');
    Route::post('/empresa/{empresa}/modulos', [EmpresaManagementController::class, 'updateEmpresaModulos'])->name('empresa.modulos');
    Route::post('/empresa/{empresa}/modulos-todo', [EmpresaManagementController::class, 'updateEmpresaModulosTodo'])->name('empresa.modulos-todo');
    Route::get('/empresa/codigos', [EmpresaManagementController::class, 'codigos'])->name('empresa.codigos');
    Route::put('/empresa/{empresa}/codigos', [EmpresaManagementController::class, 'updateCodigos'])->name('empresa.codigos.update');
    Route::post('/empresa/{empresa}/usuarios', [EmpresaManagementController::class, 'storeEmpresaUser'])->name('empresa.usuarios.store');

    // Endpoints dependientes
    Route::get('/empresa/{empresa}/sedes', [EmpresaManagementController::class, 'sedesByEmpresa'])->name('empresa.sedes-json');
    Route::get('/sede/{sede}/bodegas', [EmpresaManagementController::class, 'bodegasBySede'])->name('sede.bodegas-json');
    Route::get('/sede/{sede}/oficinas', [EmpresaManagementController::class, 'oficinasBySede'])->name('sede.oficinas-json');
    Route::get('/sede/{sede}/espacios', [EmpresaManagementController::class, 'espaciosBySede'])->name('sede.espacios-json');
    Route::get('/empresa/{empresa}/bodegas', [EmpresaManagementController::class, 'bodegasByEmpresa'])->name('empresa.bodegas-json');

    // Sede CRUD
    Route::post('/sedes', [EmpresaManagementController::class, 'storeSede'])->name('sedes.store');
    Route::get('/sedes/{sede}/edit', [EmpresaManagementController::class, 'editSede'])->name('sedes.edit');
    Route::put('/sedes/{sede}', [EmpresaManagementController::class, 'updateSede'])->name('sedes.update');
    Route::delete('/sedes/{sede}', [EmpresaManagementController::class, 'destroySede'])->name('sedes.destroy');
    Route::post('/sedes/{sede}/toggle-status', [EmpresaManagementController::class, 'toggleSedeStatus'])->name('sedes.toggle-status');

    // Bodega CRUD
    Route::post('/bodegas', [EmpresaManagementController::class, 'storeBodega'])->name('bodegas.store');
    Route::get('/bodegas/{bodega}/edit', [EmpresaManagementController::class, 'editBodega'])->name('bodegas.edit');
    Route::put('/bodegas/{bodega}', [EmpresaManagementController::class, 'updateBodega'])->name('bodegas.update');
    Route::delete('/bodegas/{bodega}', [EmpresaManagementController::class, 'destroyBodega'])->name('bodegas.destroy');
    Route::post('/bodegas/{bodega}/toggle-status', [EmpresaManagementController::class, 'toggleBodegaStatus'])->name('bodegas.toggle-status');

    Route::post('/oficinas', [EmpresaManagementController::class, 'storeOficina'])->name('oficinas.store');
    Route::get('/oficinas/{oficina}/edit', [EmpresaManagementController::class, 'editOficina'])->name('oficinas.edit');
    Route::put('/oficinas/{oficina}', [EmpresaManagementController::class, 'updateOficina'])->name('oficinas.update');
    Route::delete('/oficinas/{oficina}', [EmpresaManagementController::class, 'destroyOficina'])->name('oficinas.destroy');

    Route::post('/espacios', [EmpresaManagementController::class, 'storeEspacio'])->name('espacios.store');
    Route::get('/espacios/{espacio}/edit', [EmpresaManagementController::class, 'editEspacio'])->name('espacios.edit');
    Route::put('/espacios/{espacio}', [EmpresaManagementController::class, 'updateEspacio'])->name('espacios.update');
    Route::delete('/espacios/{espacio}', [EmpresaManagementController::class, 'destroyEspacio'])->name('espacios.destroy');
    
    // Rutas de tipos y clases de equipos
    Route::prefix('tipos')->name('tipos.')->group(function () {
        Route::get('/gestion', [TipoEquipoController::class, 'index'])->name('gestion');
        Route::post('/', [TipoEquipoController::class, 'store'])->name('store');
        Route::get('/{tipoEquipo}/edit', [TipoEquipoController::class, 'edit'])->name('edit');
        Route::put('/{tipoEquipo}', [TipoEquipoController::class, 'update'])->name('update');
        Route::delete('/{tipoEquipo}', [TipoEquipoController::class, 'destroy'])->name('destroy');
        Route::post('/{tipoEquipo}/toggle-status', [TipoEquipoController::class, 'toggleStatus'])->name('toggle-status');
    });
    
    Route::prefix('clases')->name('clases.')->group(function () {
        Route::get('/gestion', [ClaseEquipoController::class, 'index'])->name('gestion');
        Route::post('/', [ClaseEquipoController::class, 'store'])->name('store');
        Route::get('/{claseEquipo}/edit', [ClaseEquipoController::class, 'edit'])->name('edit');
        Route::put('/{claseEquipo}', [ClaseEquipoController::class, 'update'])->name('update');
        Route::delete('/{claseEquipo}', [ClaseEquipoController::class, 'destroy'])->name('destroy');
        Route::post('/{claseEquipo}/toggle-status', [ClaseEquipoController::class, 'toggleStatus'])->name('toggle-status');
    });
    
    // Gestión de Equipos - Nuevo sistema unificado
    Route::prefix('equipos')->name('equipos.')->group(function () {
        Route::get('/', [EquipoInventarioController::class, 'index'])->name('index');
        Route::get('/create', [EquipoInventarioController::class, 'create'])->name('create');
        Route::get('/codigo-sugerido', [EquipoInventarioController::class, 'codigoSugerido'])->name('codigo-sugerido');
        Route::post('/codigos/{codigo}/usar', [EquipoInventarioController::class, 'usarCodigo'])->name('codigos.usar');
        Route::post('/', [EquipoInventarioController::class, 'store'])->name('store');
        Route::get('/{equipo}', [EquipoInventarioController::class, 'show'])->name('show')->whereNumber('equipo');
        Route::post('/{equipo}/archivos', [EquipoInventarioController::class, 'storeArchivo'])->name('archivos.store')->whereNumber('equipo');
        Route::get('/archivos/{archivo}/download', [EquipoInventarioController::class, 'downloadArchivo'])->name('archivos.download');
        Route::delete('/{equipo}/archivos/{archivo}', [EquipoInventarioController::class, 'destroyArchivo'])->name('archivos.destroy')->whereNumber('equipo');
        Route::delete('/{equipo}/evidencias/{imagen}', [EquipoInventarioController::class, 'destroyEvidencia'])->name('evidencias.destroy')->whereNumber('equipo');
        Route::get('/{equipo}/edit', [EquipoInventarioController::class, 'edit'])->name('edit')->whereNumber('equipo');
        Route::put('/{equipo}', [EquipoInventarioController::class, 'update'])->name('update')->whereNumber('equipo');
        Route::delete('/{equipo}', [EquipoInventarioController::class, 'destroy'])->name('destroy')->whereNumber('equipo');

        Route::get('/bajas', [EquiposBajaController::class, 'index'])->name('bajas.index');
        Route::post('/bajas/plantilla', [EquiposBajaController::class, 'uploadPlantilla'])->name('bajas.plantilla.upload');
        Route::post('/bajas/plantilla/regenerar', [EquiposBajaController::class, 'regenerarPlantillaHtml'])->name('bajas.plantilla.regenerar');
        Route::get('/bajas/{equipo}/form', [EquiposBajaController::class, 'form'])->name('bajas.form')->whereNumber('equipo');
        Route::post('/bajas/{equipo}/preview-pdf', [EquiposBajaController::class, 'previewPdf'])->name('bajas.preview-pdf')->whereNumber('equipo');
        Route::post('/bajas/{equipo}', [EquiposBajaController::class, 'store'])->name('bajas.store')->whereNumber('equipo');
        Route::get('/bajas/{baja}/edit', [EquiposBajaController::class, 'edit'])->name('bajas.edit')->whereNumber('baja');
        Route::put('/bajas/{baja}', [EquiposBajaController::class, 'update'])->name('bajas.update')->whereNumber('baja');
        Route::get('/bajas/pdf/{baja}', [EquiposBajaController::class, 'showPdf'])->name('bajas.pdf.show')->whereNumber('baja');
        Route::get('/bajas/pdf/{baja}/download', [EquiposBajaController::class, 'downloadPdf'])->name('bajas.pdf.download')->whereNumber('baja');

        Route::get('/auditoria', [EquiposAuditoriaController::class, 'index'])->name('auditoria.index');
        Route::post('/auditoria/traspasar', [EquiposAuditoriaController::class, 'traspasar'])->name('auditoria.traspasar');
        Route::post('/traspasar', [TraspasoController::class, 'traspasar'])->name('traspasar');

        Route::get('/material-didactico', [MaterialDidacticoController::class, 'index'])->name('material-didactico.index');
        Route::post('/material-didactico/traspasar', [MaterialDidacticoController::class, 'traspasar'])->name('material-didactico.traspasar');

        Route::post('/bajas/eliminar', [EquiposBajaController::class, 'eliminar'])->name('bajas.eliminar');

        Route::get('/gestion', [EquipoManagementController::class, 'index'])->name('gestion');
        Route::post('/store-tipo', [EquipoManagementController::class, 'storeTipo'])->name('store-tipo');
        Route::post('/store-clase', [EquipoManagementController::class, 'storeClase'])->name('store-clase');
        Route::get('/edit-tipo/{id}', [EquipoManagementController::class, 'editTipo'])->name('edit-tipo');
        Route::get('/edit-clase/{id}', [EquipoManagementController::class, 'editClase'])->name('edit-clase');
        Route::put('/update-tipo/{id}', [EquipoManagementController::class, 'updateTipo'])->name('update-tipo');
        Route::put('/update-clase/{id}', [EquipoManagementController::class, 'updateClase'])->name('update-clase');
        Route::delete('/delete-tipo/{id}', [EquipoManagementController::class, 'deleteTipo'])->name('delete-tipo');
        Route::delete('/delete-clase/{id}', [EquipoManagementController::class, 'deleteClase'])->name('delete-clase');
        Route::post('/toggle-tipo/{id}', [EquipoManagementController::class, 'toggleTipoStatus'])->name('toggle-tipo');
        Route::post('/toggle-clase/{id}', [EquipoManagementController::class, 'toggleClaseStatus'])->name('toggle-clase');
    });
});

Auth::routes(['register' => (bool) config('sams.allow_registration', false)]);

Route::get('/home', [HomeController::class, 'index'])->name('home');
