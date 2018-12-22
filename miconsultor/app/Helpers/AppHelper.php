<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Config;

function ConnectDatabase($idempresa)
{
    $empresa = DB::connection("General")->select("SELECT * FROM mc1000 WHERE idempresa='$idempresa' AND status=1");
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