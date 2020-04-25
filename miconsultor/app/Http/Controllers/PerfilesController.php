<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerfilesController extends Controller
{
    public function listaPerfiles(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $todos = $request->all;
            if ($todos == 1) {
                $perfiles = DB::select('select * from mc_profiles', []); 
            }else{
                $perfiles = DB::select('select * from mc_profiles where status = ?', [1]);
            }
            $array["perfiles"] = $perfiles;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function agregarPerfil(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permisosDatos = $request->permisosdatos;
            $array["permisosdatos"] = $permisosDatos;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
