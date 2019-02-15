<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PermisosController extends Controller
{
    function PermisoModulos(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        ConnectDatabase($idempresa);

        $permisos= DB::select("SELECT u.*,p.nombre FROM mc_usermod u 
        INNER JOIN mc_profiles p ON u.idperfil=p.idperfil WHERE idusuario='$idusuario'");
        
        $datos = array(
            "permisoMod" => $permisos,
        );           

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }
    
    function PermisoMenus(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idModulo = $request->idModulo;
        ConnectDatabase($idempresa);

        $permisos= DB::select("SELECT u.* FROM mc_usermenu u WHERE idusuario='$idusuario' and idmodulo='$idModulo'");
        
        $datos = array(
            "permisoMenu" => $permisos,
        );           

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }
}
