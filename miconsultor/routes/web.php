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
Route::get('ListaEmpresas/{idusuario}', 'EmpresasController@ListaEmpresas');
Route::get('DatosEmpresaAD/{idempresa}', 'EmpresasController@DatosEmpresaAD'); 
Route::post('EliminarEmpresaAD', 'EmpresasController@EliminarEmpresaAD');

///Usuarios
Route::post('Login', 'UsuariosController@Login');
Route::post('EliminarUsuario', 'UsuariosController@EliminarUsuario');

Route::get('ListaUsuariosAdmin', 'UsuariosController@ListaUsuariosAdmin'); 