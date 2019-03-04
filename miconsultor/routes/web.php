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

Route::get('ListaUsuariosAdmin/{idempresa}', 'UsuariosController@ListaUsuariosAdmin'); 
Route::get('ListaUsuarios/{idempresa}', 'UsuariosController@ListaUsuarios'); 
Route::post('GuardaUsuario', 'UsuariosController@GuardaUsuario');
Route::post('GuardarEmpresa', 'EmpresasController@GuardarEmpresa');

//Verifica Usuario por correo
Route::post('ObtenerUsuarioNuevo', 'UsuariosController@ObtenerUsuarioNuevo');
Route::post('VerificaUsuario', 'UsuariosController@VerificaUsuario');
Route::post('ValidarCorreo', 'UsuariosController@ValidarCorreo');

//Restablece Contraseña
Route::post('RestablecerContraseña', 'UsuariosController@RestablecerContraseña');

//MODULOS Y PERFILES
Route::get('Modulos', 'UsuariosController@Modulos');
Route::get('DatosModulo/{IDMod}', 'UsuariosController@DatosModulo'); 
Route::get('Perfiles', 'UsuariosController@Perfiles');
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
Route::get('DatosPerfilEmpresa', 'GeneralesController@DatosPerfilEmpresa');
Route::get('PermisosPerfil', 'GeneralesController@PermisosPerfil');
Route::post('updatePermisoUsuario', 'GeneralesController@updatePermisoUsuario');
Route::post('GuardaPerfilEmpresa', 'GeneralesController@GuardaPerfilEmpresa');
Route::post('EditarPerfilEmpresa', 'GeneralesController@EditarPerfilEmpresa');
Route::post('updatePermisoPerfil', 'GeneralesController@updatePermisoPerfil');

//Checa BD Disponible
Route::get('BDDisponible', 'EmpresasController@BDDisponible');
Route::post('AsignaBD', 'EmpresasController@AsignaBD');
Route::post('CrearTablasEmpresa', 'EmpresasController@CrearTablasEmpresa');

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
