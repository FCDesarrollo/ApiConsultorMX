<?php

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
    return view('welcome');
});

///Pruebas
Route::get('Pruebas', 'UsuariosController@Pruebas');

Route::post('AlmacenCargado', 'GeneralesController@AlmacenCargado');
Route::post('DatosDeInicio', 'GeneralesController@DatosDeInicio');
//Route::get('ListaSucursales/{idempresa}', 'EmpresasController@ListaSucursales');

//Empresas
Route::get('ListaEmpresas', 'EmpresasController@ListaEmpresas');
Route::get('DatosEmpresaAD/{idempresa}', 'EmpresasController@DatosEmpresaAD');
Route::post('EliminarEmpresaAD', 'EmpresasController@EliminarEmpresaAD');
Route::post('GuardarEmpresaAD', 'EmpresasController@GuardarEmpresaAD');
Route::get('DatosEmpresa', 'EmpresasController@DatosEmpresa');
Route::post('DatosFacturacion', 'EmpresasController@DatosFacturacion');
Route::post('ActualizaVigencia', 'EmpresasController@ActualizaVigencia');

///Usuarios
Route::post('Login', 'UsuariosController@Login');
Route::get('DatosUsuario/{idusuario}', 'UsuariosController@DatosUsuario');
Route::post('EliminarUsuario', 'UsuariosController@EliminarUsuario');
Route::post('Desvincular', 'UsuariosController@Desvincular');
Route::get('NotificacionesUsuario', 'UsuariosController@NotificacionesUsuario');
route::post('ModificaNotificacion', 'UsuariosController@ModificaNotificacion');
route::post('VinculacionUsuarios', 'UsuariosController@VinculacionUsuarios');

Route::get('ListaUsuariosAdmin/{idempresa}', 'UsuariosController@ListaUsuariosAdmin');
Route::get('ListaUsuarios/{idempresa}', 'UsuariosController@ListaUsuarios');
Route::post('GuardaUsuario', 'UsuariosController@GuardaUsuario');
Route::post('GuardarEmpresa', 'EmpresasController@GuardarEmpresa');

//Verifica Usuario por correo
Route::post('ObtenerUsuarioNuevo', 'UsuariosController@ObtenerUsuarioNuevo');
Route::post('VerificaUsuario', 'UsuariosController@VerificaUsuario');
Route::post('ValidarCorreo', 'UsuariosController@ValidarCorreo');
Route::post('VerificaCelular', 'UsuariosController@VerificaCelular');

//Restablece Contraseña
Route::post('RestablecerContraseña', 'UsuariosController@RestablecerContraseña');

//MODULOS Y PERFILES
Route::get('Modulos', 'UsuariosController@Modulos');
Route::get('DatosModulo/{IDMod}', 'UsuariosController@DatosModulo');
Route::get('Perfiles', 'GeneralesController@PerfilesGen');
Route::get('DatosPerfil', 'UsuariosController@DatosPerfil');
Route::get('ListaPermisos/{IDPer}', 'UsuariosController@ListaPermisos');
Route::post('EliminaPermiso', 'UsuariosController@EliminaPermiso');
Route::post('GuardaPermiso', 'UsuariosController@GuardaPermiso');
Route::post('GuardaPerfil', 'UsuariosController@GuardaPerfil');

Route::get('PerfilUsuario', 'GeneralesController@PerfilUsuario');
Route::get('PermisosUsuario', 'GeneralesController@PermisosUsuario');
Route::post('VinculaEmpresa', 'GeneralesController@VinculaEmpresa');
Route::get('PerfileEmpresa/{idempresa}', 'GeneralesController@PerfilesEmpresa');
Route::post('EliminarPerfilEmpresa', 'GeneralesController@EliminarPerfilEmpresa');
Route::get('PermisosPerfil', 'GeneralesController@PermisosPerfil');
Route::post('updatePermisoUsuario', 'GeneralesController@updatePermisoUsuario');
Route::post('EditarPerfilEmpresa', 'GeneralesController@EditarPerfilEmpresa');
Route::post('updatePermisoPerfil', 'GeneralesController@updatePermisoPerfil');

//Checa BD Disponible
Route::get('BDDisponible', 'EmpresasController@BDDisponible');
Route::post('AsignaBD', 'EmpresasController@AsignaBD');
Route::post('CrearTablasEmpresa', 'EmpresasController@CrearTablasEmpresa');
Route::post('UsuarioEmpresa', 'EmpresasController@UsuarioEmpresa');
Route::post('UsuarioProfile', 'EmpresasController@UsuarioProfile');
Route::post('EliminaAsignaBD', 'EmpresasController@EliminaAsignaBD');
Route::post('EliminarRegistro', 'EmpresasController@EliminarRegistro');
Route::post('EliminarTablas', 'EmpresasController@EliminarTablas');
Route::post('EliminarUsuarioEmpresa', 'EmpresasController@EliminarUsuarioEmpresa');
Route::post('ProfileVinculacion', 'PermisosController@ProfileVinculacion');

//PERMISOS
Route::get('PermisoModulos', 'PermisosController@PermisoModulos');
Route::get('PermisoMenus', 'PermisosController@PermisoMenus');
Route::get('PermisoSubMenus', 'PermisosController@PermisoSubMenus');
Route::get('NombreModulo', 'PermisosController@NombreModulo');
Route::get('NombreMenu', 'PermisosController@NombreMenu');
Route::get('NombreSubMenu', 'PermisosController@NombreSubMenu');
Route::get('Modulos', 'PermisosController@Modulos');
Route::get('Menus', 'PermisosController@Menus');
Route::get('SubMenus', 'PermisosController@SubMenus');
Route::get('MenusPermiso', 'PermisosController@MenusPermiso');
Route::get('SubMenuPermiso', 'PermisosController@SubMenuPermiso');

//UPDATES PERMISOS
Route::post('UpdatePermisoModulo', 'PermisosController@UpdatePermisoModulo');
Route::post('UpdatePermisoMenu', 'PermisosController@UpdatePermisoMenu');
Route::post('UpdatePermisoSubMenu', 'PermisosController@UpdatePermisoSubMenu');

//PARAMETROS GENERALES
Route::post('Parametros', 'EmpresasController@Parametros');

//PERFILES DE LA EMPRESA 23/03/2019
Route::post('GuardaPerfilEmpresa', 'GeneralesController@GuardaPerfilEmpresa');
Route::post('ModulosPerfil', 'GeneralesController@ModulosPerfil');
Route::post('MenuPerfil', 'GeneralesController@MenuPerfil');
Route::post('SubMenuPerfil', 'GeneralesController@SubMenuPerfil');
Route::get('DatosPerfilEmpresa', 'GeneralesController@DatosPerfilEmpresa');
Route::get('PermisosModPerfil', 'GeneralesController@PermisosModPerfil');
Route::get('PermisosMenusPerfil', 'GeneralesController@PermisosMenusPerfil');
Route::get('PermisoSubMenusPerfil', 'GeneralesController@PermisoSubMenusPerfil');


//ARCHIVOS EMPRESA - ALMACEN DIGITAL
Route::post('RubrosGen', 'ConsumoController@RubrosGen');
Route::post('CatSucursales', 'ConsumoController@CatSucursales');
Route::post('AlmCargaArchivos', 'ConsumoController@AlmCargaArchivos');
Route::post('AlmacenConsumo', 'ConsumoController@AlmacenConsumo');
Route::post('AlmacenMarcado', 'ConsumoController@AlmacenMarcado');
Route::post('CambiaRubroDocumento', 'ConsumoController@CambiaRubroDocumento');
Route::post('ExtraerConsecutivo', 'ConsumoController@ExtraerConsecutivo');
Route::post('EliminaDocumentosAPI', 'ConsumoController@EliminaDocumentosAPI');

//PARA EL ADMINISTRADOR GENERAL DE LA PAGINA
Route::get('LoginAdmin', 'AdministradorController@LoginAdmin');
Route::post('numEstadistica', 'AdministradorController@numEstadistica');
Route::get('allempresas', 'AdministradorController@allempresas');

//API FC_PREMIUM
Route::post('enviarModulos', 'FcPremiumController@enviarModulos');
Route::post('versionesModulos', 'FcPremiumController@versionesModulos');
Route::post('datosVersion', 'FcPremiumController@datosVersion');
Route::post('linkArchivo', 'FcPremiumController@linkArchivo');
Route::post('actualizaVersion', 'FcPremiumController@actualizaVersion');
Route::post('altaCliente', 'FcPremiumController@altaCliente');
Route::post('verificarLicencia', 'FcPremiumController@verificarLicencia');
Route::post('validarClave', 'FcPremiumController@validarClave');
Route::post('activa', 'FcPremiumController@activa');

//VALIDACIONES RECEPCION POR LOTES
Route::get('ConsultarLotes', 'GeneralesController@ConsultarLotes');
Route::get('traerLotes', 'GeneralesController@traerLotes');
Route::get('ConsultarDoctos', 'GeneralesController@ConsultarDoctos');
Route::get('traerDocumentosLote', 'GeneralesController@traerDocumentosLote');
Route::get('ConsultarMovtosLote', 'GeneralesController@ConsultarMovtosLote');
Route::get('traerMovimientosLote', 'GeneralesController@traerMovimientosLote');
Route::get('ConsultarMovtosDocto', 'GeneralesController@ConsultarMovtosDocto');
Route::get('traerMovimientosDocumentosLote', 'GeneralesController@traerMovimientosDocumentosLote');
//Route::post('RegistrarLote', 'GeneralesController@RegistrarLote');
Route::post('EliminarLote', 'GeneralesController@EliminarLote');
Route::delete('eliminaLote', 'GeneralesController@eliminaLote');
Route::post('EliminarDocto', 'GeneralesController@EliminarDocto');
Route::delete('eliminaDocto', 'GeneralesController@eliminaDocto');
Route::delete('eliminaDocumentoLote', 'GeneralesController@eliminaDocumentoLote');
Route::post('VerificarLote', 'GeneralesController@VerificarLote');
Route::post('ChecarCatalogos', 'GeneralesController@ChecarCatalogos');
Route::post('RegistrarElemento', 'GeneralesController@RegistrarElemento');
Route::get('Paginador', 'GeneralesController@Paginador');
Route::post('VerificarClave', 'GeneralesController@VerificarClave');
Route::post('validarDocumentoLote', 'GeneralesController@validarDocumentoLote');
Route::post('guardarLote', 'GeneralesController@guardarLote');
Route::post('registrarElementos', 'GeneralesController@registrarElementos');

//PARA CONSUMO DEL MODULO DE INVENTARIOS
Route::post('LoteCargado', 'GeneralesController@LoteCargado');
Route::post('LoteMarcado', 'ConsumoController@LoteMarcado');
Route::post('LoteConsumo', 'ConsumoController@LoteConsumo');
Route::post('LoteCatalogos', 'ConsumoController@LoteCatalogos');
Route::get('DatosAlmacen', 'ConsumoController@DatosAlmacen');
Route::get('ArchivosAlmacen', 'ConsumoController@ArchivosAlmacen');
Route::post('EliminaArchivoAlmacen', 'ConsumoController@EliminaArchivoAlmacen');
Route::get('DatosFiltroAvanzado', 'ConsumoController@DatosFiltroAvanzado');
Route::get('FiltrarDatos', 'ConsumoController@FiltrarDatos');
Route::post('LinkDescarga', 'ConsumoController@LinkDescarga');
Route::post('EliminaDocumentoAll', 'ConsumoController@EliminaDocumentoAll');
Route::get('ArchivosCorreccionLinks', 'ConsumoController@ArchivosCorreccionLinks');


// PARA EL PROCESO DE COMPRAS
Route::post('ReqCompras', 'ConsumoController@ReqCompras');


//10/09/2019 PARA LEER LA BITACORA
Route::post('archivosBitacora', 'FcPremiumController@archivosBitacora');

//STORAGE
Route::get('DatosStorage', 'ConsumoController@DatosStorage');
Route::get('DatosStorageADM', 'ConsumoController@DatosStorageADM');
Route::post('registraBitacora', 'AdministradorController@registraBitacora');
Route::post('DatosServicios', 'AdministradorController@DatosServicios');

//19/09/2019 PARA LA ADMINISTRACION DE EMPRESAS DE LOS AGENTES DESKTOP
Route::post('datosadmin', 'AdministradorController@datosadmin');
Route::post('empresasadmin', 'AdministradorController@empresasadmin');
Route::post('serviciosfc', 'AdministradorController@serviciosfc');
Route::post('servicioscontratados', 'AdministradorController@servicioscontratados');
Route::post('bitacoraservicios', 'AdministradorController@bitacoraservicios');
Route::post('updateBitacora', 'AdministradorController@updateBitacora');
Route::post('listaejercicios', 'AdministradorController@listaejercicios');
Route::post('listaserviciosbit', 'AdministradorController@listaServicios_bit');
Route::post('listaagentesbit', 'AdministradorController@listaAgentes_bit');
Route::post('existebitacora', 'AdministradorController@Existe_bitacora');
Route::post('MarcaBitacora', 'AdministradorController@MarcaBitacora');
Route::post('EntregadoDocumento', 'AdministradorController@EntregadoDocumento');

//ASOCIACION DIGITAL
Route::post('SubMenusFiltro', 'PermisosController@SubMenusFiltro');
Route::post('RubrosUser', 'PermisosController@RubrosUser');
Route::post('addSucursal', 'AdministradorController@addSucursal');
Route::post('addRubros', 'AdministradorController@addRubros');
Route::post('datosRubros', 'AdministradorController@datosRubros');
Route::post('datosSucursal', 'AdministradorController@datosSucursal');
Route::post('datosRubrosSubMenu', 'AdministradorController@datosRubrosSubMenu');
Route::post('documentosdigitales', 'AdministradorController@documentosdigitales');
Route::post('usuarionube', 'AdministradorController@usuarionube');
Route::post('Plantillas', 'AdministradorController@Plantillas');
Route::get('traerPlantillas', 'AdministradorController@traerPlantillas');



// AUTORIZACION Y COMPRAS
Route::get('obtenerRequerimiento', 'ComprasController@obtenerRequerimiento');
Route::get('Bitacora', 'ComprasController@Bitacora');
Route::get('ArchivosRequerimientos', 'ComprasController@ArchivosRequerimientos');
Route::post('nuevoRequerimiento', 'ComprasController@nuevoRequerimiento');
Route::post('eliminarRequerimiento', 'ComprasController@eliminarRequerimiento');
Route::post('editarRequerimiento', 'ComprasController@editarRequerimiento');
Route::post('nuevoEstado', 'ComprasController@nuevoEstado');

//APLICAICON MOVIL
Route::post('DatosUsuarios', 'AppController@DatosUsuarios');

Route::get('listaempresas', 'AdministradorController@listaempresas');

//MENU 2020
Route::get('menuWeb', 'MenuController@menuWeb');
Route::get('getEmpresaValidacion', 'MenuController@getEmpresaValidacion');

//USUARIO 2020
Route::post('inicioUsuario', 'UsuarioController@inicioUsuario');
Route::get('permisosUsuario', 'UsuarioController@permisosUsuario');
Route::post('registrarUsuario', 'UsuarioController@registrarUsuario');
Route::post('verificaCodigo', 'UsuarioController@verificaCodigo');
Route::post('reenviaCodigo', 'UsuarioController@reenviaCodigo');
Route::get('listaUsuariosEmpresa', 'UsuarioController@listaUsuariosEmpresa');
Route::put('modificaPermisoModulo', 'UsuarioController@modificaPermisoModulo');
Route::put('modificaPermisoMenu', 'UsuarioController@modificaPermisoMenu');
Route::put('modificaPermisoSubmenu', 'UsuarioController@modificaPermisoSubmenu');
Route::get('permisosUsuarioGeneral', 'UsuarioController@permisosUsuarioGeneral');
Route::put('desvinculaUsuario', 'UsuarioController@desvinculaUsuario');
Route::delete('eliminaUsuarioEmpresa', 'UsuarioController@eliminaUsuarioEmpresa');
Route::post('editaNotificacion', 'UsuarioController@editaNotificacion');
Route::post('vincularUsuario', 'UsuarioController@vincularUsuario');
Route::post('crearNuevoUsuario', 'UsuarioController@crearNuevoUsuario');
Route::get('traerNuevoUsuarioRegistrado', 'UsuarioController@traerNuevoUsuarioRegistrado');
Route::put('cambiarContraNuevoUsuarioRegistrado', 'UsuarioController@cambiarContraNuevoUsuarioRegistrado');
Route::put('cambiarContraUsuario', 'UsuarioController@cambiarContraUsuario');

//EMPRESA 2020
Route::get('listaEmpresasUsuario', 'EmpresaController@listaEmpresasUsuario');
Route::post('validaEmpresa', 'EmpresaController@validaEmpresa');
Route::get('enviaCorreoEmpresa', 'EmpresaController@enviaCorreoEmpresa');
Route::post('registraEmpresa', 'EmpresaController@registraEmpresa');
Route::get('datosEmpresa', 'EmpresaController@datosEmpresa');
Route::put('editarDatosFacturacionEmpresa', 'EmpresaController@editarDatosFacturacionEmpresa');
Route::post('renovarCertificadoEmpresa', 'EmpresaController@renovarCertificadoEmpresa');
Route::get('getServiciosEmpresaCliente', 'EmpresaController@getServiciosEmpresaCliente');
Route::post('agregarServicioEmpresaCliente', 'EmpresaController@agregarServicioEmpresaCliente');
Route::get('getMovimientosEmpresaCliente', 'EmpresaController@getMovimientosEmpresaCliente');
Route::get('getMovimientoEmpresaCliente', 'EmpresaController@getMovimientoEmpresaCliente');
Route::get('getContenidoServicioClientes', 'EmpresaController@getContenidoServicioClientes');
Route::get('traerFlujosEfectivo', 'EmpresaController@traerFlujosEfectivo');
Route::get('traerArchivosFlujos', 'EmpresaController@traerArchivosFlujos');
Route::get('traerFlujosEfectivoAcomodados', 'EmpresaController@traerFlujosEfectivoAcomodados');
Route::post('traerFlujosEfectivoFiltrados', 'EmpresaController@traerFlujosEfectivoFiltrados');
Route::post('cargarFlujosEfectivo', 'EmpresaController@cargarFlujosEfectivo');
Route::post('cargarProveedores', 'EmpresaController@cargarProveedores');
Route::post('cargarCuentasPropias', 'EmpresaController@cargarCuentasPropias');
Route::post('cargarCuentasClientesProveedores', 'EmpresaController@cargarCuentasClientesProveedores');
Route::get('getCuentasPropias', 'EmpresaController@getCuentasPropias');
Route::get('getCuentasClientesProveedores', 'EmpresaController@getCuentasClientesProveedores');
Route::get('getFlwPagos', 'EmpresaController@getFlwPagos');
Route::post('guardarFlwPagos', 'EmpresaController@guardarFlwPagos');
Route::delete('eliminarFlwPagos', 'EmpresaController@eliminarFlwPagos');
Route::post('cambiarEstatusLayoutFlwPagos', 'EmpresaController@cambiarEstatusLayoutFlwPagos');
Route::delete('borrarFlwPagosByLlaveMath', 'EmpresaController@borrarFlwPagosByLlaveMath');
Route::get('traerProveedoresFiltro', 'EmpresaController@traerProveedoresFiltro');
Route::post('cambiarPrioridadProveedor', 'EmpresaController@cambiarPrioridadProveedor');
Route::post('generarLayouts', 'EmpresaController@generarLayouts');
Route::delete('eliminarFlwPagosHechos', 'EmpresaController@eliminarFlwPagosHechos');
Route::get('traerLayoutsPorIdBanco', 'EmpresaController@traerLayoutsPorIdBanco');
Route::post('cambiarLayoutElegido', 'EmpresaController@cambiarLayoutElegido');
Route::post('reenviarCorreoLayout', 'EmpresaController@reenviarCorreoLayout');
Route::post('agregarCuentaProveedor', 'EmpresaController@agregarCuentaProveedor');
Route::post('agregarNuevoProveedor', 'EmpresaController@agregarNuevoProveedor');

//ACTUALIZA PERFILES GENERALES
Route::put('actualizaPerfilesGeneral', 'ActualizarBaseDatosController@actualizaPerfilesGeneral'); // 14/02/2020
Route::put('actualizaPerfilesEmpresa', 'ActualizarBaseDatosController@actualizaPerfilesEmpresa'); // 14/02/2020
Route::put('actualizaPermisosUsuario', 'ActualizarBaseDatosController@actualizaPermisosUsuario'); // 14/02/2020
Route::put('creaTablasRequerimientos', 'ActualizarBaseDatosController@creaTablasRequerimientos'); // 14/02/2020

//AUTORIZACION Y GASTOS 2020
Route::get('cargaConceptos', 'AutorizacionyGastosController@cargaConceptos');
Route::get('cargaEstatus', 'AutorizacionyGastosController@cargaEstatus');
Route::post('nuevoRequerimiento', 'AutorizacionyGastosController@nuevoRequerimiento');
Route::get('listaRequerimientos', 'AutorizacionyGastosController@listaRequerimientos');
Route::get('datosRequerimiento', 'AutorizacionyGastosController@datosRequerimiento');
Route::put('agregaEstatus', 'AutorizacionyGastosController@agregaEstatus');
Route::delete('eliminaEstatus', 'AutorizacionyGastosController@eliminaEstatus');
Route::get('permisosAutorizaciones', 'AutorizacionyGastosController@permisosAutorizaciones');
Route::put('guardaPermisoAutorizacion', 'AutorizacionyGastosController@guardaPermisoAutorizacion');
Route::delete('eliminaPermisoAutorizacion', 'AutorizacionyGastosController@eliminaPermisoAutorizacion');
Route::post('editarRequerimiento', 'AutorizacionyGastosController@editarRequerimiento');
Route::delete('eliminaDocumento', 'AutorizacionyGastosController@eliminaDocumento');
Route::delete('eliminaRequerimiento', 'AutorizacionyGastosController@eliminaRequerimiento');
Route::post('creaGasto', 'AutorizacionyGastosController@creaGasto');
Route::get('getTotalImporte', 'AutorizacionyGastosController@getTotalImporte');
Route::get('traerLimiteGastosUsuario', 'AutorizacionyGastosController@traerLimiteGastosUsuario');
Route::post('guardaLimiteGastos', 'AutorizacionyGastosController@guardaLimiteGastos');
Route::post('carga_ProveedoresADW', 'AutorizacionyGastosController@carga_ProveedoresADW');
Route::get('traerProveedores', 'AutorizacionyGastosController@traerProveedores');
Route::get('traerRequerimientoPorSerie', 'AutorizacionyGastosController@traerRequerimientoPorSerie');

//APIS PARA MODULO XML 26/04/2020
Route::post('RequerimientoMarcado', 'AutorizacionyGastosController@RequerimientoMarcado');
Route::get('getGastosRelacionados', 'AutorizacionyGastosController@getGastosRelacionados');
Route::get('getProveedoresRelacionadosAGastos', 'AutorizacionyGastosController@getProveedoresRelacionadosAGastos');


//ALMACEN DIGITAL OPERACIONES 2020
Route::get('listaAlmacenDigital', 'AlmacenDigitalOperacionesController@listaAlmacenDigital');
Route::get('archivosAlmacenDigital', 'AlmacenDigitalOperacionesController@archivosAlmacenDigital');
Route::post('cargaArchivosAlmacenDigital', 'AlmacenDigitalOperacionesController@cargaArchivosAlmacenDigital');
Route::post('eliminaArchivosDigital', 'AlmacenDigitalOperacionesController@eliminaArchivosDigital');

//API PARA LOS EXPEDIENTES DE LOS MODULOS
Route::post('ClipMarcado', 'ConsumoController@ClipMarcado');
Route::post('getArchivoDigital', 'ConsumoController@getArchivoDigital');
Route::post('getLogosEmpresa', 'ConsumoController@getLogosEmpresa');

//NOTIFICACIONES CRM
Route::get('notificacionesCRM', 'NotificacionesController@notificacionesCRM');
Route::get('usuariosNotificacion', 'NotificacionesController@usuariosNotificacion');
Route::post('reenviarNotificacion', 'NotificacionesController@reenviarNotificacion');
Route::delete('eliminaNotificacion', 'NotificacionesController@eliminaNotificacion');

//PERFILES
Route::get('listaPerfiles', 'PerfilesController@listaPerfiles');
Route::post('agregarPerfil', 'PerfilesController@agregarPerfil');
Route::delete('eliminarPerfil', 'PerfilesController@eliminarPerfil');
Route::get('datosPerfil', 'PerfilesController@datosPerfil');
Route::put('editarPerfil', 'PerfilesController@editarPerfil');

//CONTABILIDAD
Route::get('getBitContabilidad', 'FcPremiumController@getBitContabilidad');

//PROVEEDORES
Route::get('getUsuarios', 'ProveedoresController@getUsuarios');
Route::get('getUsuario', 'ProveedoresController@getUsuario');
Route::post('guardarUsuario', 'ProveedoresController@guardarUsuario');
Route::put('cambiarEstatusUsuario', 'ProveedoresController@cambiarEstatusUsuario');
Route::put('cambioContraUsuario', 'ProveedoresController@cambioContraUsuario');
Route::get('getEmpresas', 'ProveedoresController@getEmpresas');
Route::get('getEmpresa', 'ProveedoresController@getEmpresa');
Route::get('getUsuariosPorEmpresa', 'ProveedoresController@getUsuariosPorEmpresa');
Route::put('guardarFechaLimitePagoEmpresa', 'ProveedoresController@guardarFechaLimitePagoEmpresa');
Route::put('guardarFechaPeriodoPruebaEmpresa', 'ProveedoresController@guardarFechaPeriodoPruebaEmpresa');
Route::put('cambiarEstatusEmpresa', 'ProveedoresController@cambiarEstatusEmpresa');
Route::get('getNotificacionesEmpresa', 'ProveedoresController@getNotificacionesEmpresa');
Route::post('guardarNotificacionEmpresa', 'ProveedoresController@guardarNotificacionEmpresa');
Route::get('getMovimientosEmpresa', 'ProveedoresController@getMovimientosEmpresa');
Route::get('getMovimientoEmpresa', 'ProveedoresController@getMovimientoEmpresa');
Route::get('getAbonosPorMovimientoEmpresa', 'ProveedoresController@getAbonosPorMovimientoEmpresa');
Route::post('guardarMovimientoEmpresa', 'ProveedoresController@guardarMovimientoEmpresa');
Route::get('getArchivosEmpresa', 'ProveedoresController@getArchivosEmpresa');
Route::post('editarMovimientoEmpresa', 'ProveedoresController@editarMovimientoEmpresa');
Route::delete('eliminarMovimientoEmpresa', 'ProveedoresController@eliminarMovimientoEmpresa');
Route::delete('eliminarAbonoMovimientoEmpresa', 'ProveedoresController@eliminarAbonoMovimientoEmpresa');
Route::delete('eliminarArchivoMovimientoEmpresa', 'ProveedoresController@eliminarArchivoMovimientoEmpresa');
Route::get('getServiciosEmpresa', 'ProveedoresController@getServiciosEmpresa');
Route::get('getServiciosNoContratadosEmpresa', 'ProveedoresController@getServiciosNoContratadosEmpresa');
Route::post('agregarServiciosEmpresa', 'ProveedoresController@agregarServiciosEmpresa');
Route::delete('eliminarServicioEmpresa', 'ProveedoresController@eliminarServicioEmpresa');
Route::get('getPerfiles', 'ProveedoresController@getPerfiles');
Route::get('getMenus', 'ProveedoresController@getMenus');
Route::post('agregarPerfilGlobal', 'ProveedoresController@agregarPerfilGlobal');
Route::get('datosPerfilGlobal', 'ProveedoresController@datosPerfilGlobal');
Route::put('editarPerfilGlobal', 'ProveedoresController@editarPerfilGlobal');
Route::delete('eliminarPerfilGlobal', 'ProveedoresController@eliminarPerfilGlobal');
Route::get('getServicios', 'ProveedoresController@getServicios');
Route::get('getServicio', 'ProveedoresController@getServicio');
Route::get('getModulosAndSubmenus', 'ProveedoresController@getModulosAndSubmenus');
Route::post('guardarServicio', 'ProveedoresController@guardarServicio');
Route::post('cambiarImagenServicio', 'ProveedoresController@cambiarImagenServicio');
Route::get('getContenidoServicio', 'ProveedoresController@getContenidoServicio');
Route::post('guardarContenidoServicio', 'ProveedoresController@guardarContenidoServicio');
Route::delete('borrarContenidoServicio', 'ProveedoresController@borrarContenidoServicio');
Route::put('cambiarStatusServicio', 'ProveedoresController@cambiarStatusServicio');

Route::post('servicioscontratadosRFC', 'AdministradorController@servicioscontratadosRFC');
Route::post('serviciosfcmodulo', 'AdministradorController@serviciosfcmodulo');
Route::get('getDatosHome', 'EmpresaController@getDatosHome');
Route::post('olvidoContra', 'GeneralesController@olvidoContra');

//NUEVA CONTABILIDAD
Route::get('traerTiposDocumentosNuevaContabilidad', 'NuevaContabilidadController@traerTiposDocumentosNuevaContabilidad');
Route::get('traerTemasDocumentosNuevaContabilidad', 'NuevaContabilidadController@traerTemasDocumentosNuevaContabilidad');
Route::get('traerDatosNuevaContabilidad', 'NuevaContabilidadController@traerDatosNuevaContabilidad');
Route::get('traerDocumentosNuevaContabilidad', 'NuevaContabilidadController@traerDocumentosNuevaContabilidad');
Route::post('enviarInformacionNuevaContabilidad', 'NuevaContabilidadController@enviarInformacionNuevaContabilidad');

//PUBLICACIONES
Route::get('getPublicaciones', 'PublicacionesController@getPublicaciones');
Route::delete('eliminarPublicacion', 'PublicacionesController@eliminarPublicacion');
Route::get('getCatalogosPublicaciones', 'PublicacionesController@getCatalogosPublicaciones');
Route::delete('eliminarCatalogoPublicacion', 'PublicacionesController@eliminarCatalogoPublicacion');
Route::post('agregarPublicacion', 'PublicacionesController@agregarPublicacion');
Route::post('agregarCatalogoPublicacion', 'PublicacionesController@agregarCatalogoPublicacion');