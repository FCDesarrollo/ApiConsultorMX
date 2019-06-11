<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AdministradorController extends Controller
{
    function LoginAdmin(Request $request)
    {
        $usuario = DB::connection("General")->select("SELECT * FROM mc1001 WHERE 
                correo='$request->correo' AND password='$request->contra' AND status=1 AND tipo=4");
       
        $datos = $usuario;       
        return $datos;
    }

    public function numEstadistica(Request $request)
    {   
        $num = DB::connection("General")->select("SELECT count(*) as num FROM $request->tabla ");
        return $num;
    }

    function allempresas(Request $request)
    {
        $iduser = $request->idusuario;
        if ($iduser <> 0){
            $empresas = DB::connection("General")->select("SELECT * FROM mc1000");
            $datos = $empresas; 
        }else{
            $datos = 0;
        }
              
        return $datos;
    }
}
