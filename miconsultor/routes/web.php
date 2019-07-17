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

//Route::get('ListaSucursales/{idempresa}', 'EmpresasController@ListaSucursales');

//Empresas
Route::get('ListaEmpresas', 'EmpresasController@ListaEmpresas');
Route::get('DatosEmpresaAD/{idempresa}', 'EmpresasController@DatosEmpresaAD'); 
Route::post('EliminarEmpresaAD', 'EmpresasController@EliminarEmpresaAD');
Route::post('GuardarEmpresaAD', 'EmpresasController@GuardarEmpresaAD');

///Usuarios
Route::post('Login', 'UsuariosController@Login');
Route::get('DatosUsuario/{idusuario}', 'UsuariosController@DatosUsuario'); 
Route::post('EliminarUsuario', 'UsuariosController@EliminarUsuario');
Route::post('Desvincular', 'UsuariosController@Desvincular');
Route::get('NotificacionesUsuario', 'UsuariosController@NotificacionesUsuario');
route::post('ModificaNotificacion', 'UsuariosController@ModificaNotificacion');

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
Route::get('DatosPerfil/{IDPer}', 'UsuariosController@DatosPerfil'); 
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
Route::get('ConsultarDoctos', 'GeneralesController@ConsultarDoctos');
Route::get('ConsultarMovtos', 'GeneralesController@ConsultarMovtos');
Route::post('RegistrarLote', 'GeneralesController@RegistrarLote');
Route::post('EliminarLote', 'GeneralesController@EliminarLote');
Route::post('EliminarDocto', 'GeneralesController@EliminarDocto');
//Route::post('RegistrarMovtos', 'GeneralesController@RegistrarMovtos');
Route::post('VerificarLote', 'GeneralesController@VerificarLote');
Route::post('RegistrarDoctos', 'GeneralesController@RegistrarDoctos');

//PARA CONSUMO DEL MODULO DE INVENTARIOS
Route::post('ProcesarLote', 'ConsumoController@ProcesarLote');
Route::get('ObtenerDatos', 'ConsumoController@ObtenerDatos');
Route::get('Paginador', 'GeneralesController@Paginador');
