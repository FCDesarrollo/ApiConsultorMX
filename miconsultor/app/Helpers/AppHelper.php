<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Config;

const Mod_Contabilidad = 1;
const Mod_BandejaEntrada = 2;
const Mod_Administracion = 3;

const Menu_Contabilidad = 1;
const Menu_ProcesoFiscal = 2;
const Menu_Finanzas = 3;
const Menu_Compras = 4;
const Menu_AlmacenDigital = 5;
const Menu_RecepcionLotes = 6;
const Menu_Empresa = 7;
const Menu_Usuario = 8;
const Menu_Perfiles = 9;

const SubM_EstadosFinancieros = 1;
const SubM_ContabilidadElectronica = 2;
const SubM_ExpedientesAdministrativos = 3;
const SubM_ExpedientesContables = 4;
const SubM_PagosProvicionales = 5;
const SubM_PagosMensuales = 6;
const SubM_DeclaracionesAnuales = 7;
const SubM_ExpedientesFiscales = 8;
const SubM_IndicadoresFinancieros = 9;
const SubM_AsesorFlujoEfectivo = 10;
const SubM_AnalisisProyecto = 11;
const SubM_Requerimientos = 12;
const SubM_Autorizaciones = 13;
const SubM_RecepcionCompras = 14;
const SubM_NotificacionesAutoridades = 15;
const SubM_ExpedientesDigitales = 16;
const SubM_ProcesoProduccion = 17;
const SubM_ProcesoCompras = 18;
const SubM_ProcesoVentas = 19;
const SubM_Empresas = 20;
const SubM_Usuarios = 21;
const SubM_Perfiles = 22;


function ConnectDatabase($idempresa)
{
    $empresa = DB::connection("General")->select("SELECT * FROM mc1000 WHERE idempresa='$idempresa' AND status=1");
    // return $clientes[0]->database;

    Config::set('database.connections.mysql', array(
        'driver' => 'mysql',
        'host' => env('DB_HOST', ''),
        'port' => env('DB_PORT', ''),
        'database' => env('dublockc_MCGenerales', $empresa[0]->rutaempresa),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'unix_socket' => env('DB_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => false,
        'engine' => null,
    ));

    DB::reconnect('mysql');    
}


function newConexion($rfc){
        // Obtenemos la empresa que tenga el rfc que nos llega en la peticion
        $empresa = DB::connection("General")->table('mc1000')->where('RFC', $rfc)->first(); //DB::purgue() ->si llegara a ser necesario
        // Jalamos la configuracion de las conexiones de laravel, en este caso la conexion original que se llama mysql
        $config = \Config::get('database.connections.mysql');
        // Sobreescribimos el nombre de la base de datos a la cual nos queremos conectar
        $config['database'] = $empresa->rutaempresa;
        // Aplicamos el cambio a la session de base de datos
        config()->set('database.connections.mysql', $config);
}

function ConnectDatabaseRFC($rfc)
{
    $empresa = DB::connection("General")->select("SELECT * FROM mc1000 WHERE rfc='$rfc' AND status=1");
    //return $clientes[0]->database;
    Config::set('database.connections.mysql', array(
        'driver' => 'mysql',
        'host' => env('DB_HOST', ''),
        'port' => env('DB_PORT', ''),
        'database' => env('dublockc_MCGenerales', $empresa[0]->rutaempresa),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'unix_socket' => env('DB_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => false,
        'engine' => null,
    ));

    DB::reconnect('mysql');    
}






function ConnectaEmpresaDatabase($empresa){

    Config::set('database.connections.mysql', array(
        'driver' => 'mysql',
        'host' => env('DB_HOST', ''),
        'port' => env('DB_PORT', ''),
        'database' => env('', $empresa),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'unix_socket' => env('DB_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => false,
        'engine' => null,
    ));

    DB::reconnect('mysql');    
}
