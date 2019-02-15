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
        
        $datos = $permisos;       
        return $datos;
        //return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }
    
    function PermisoMenus(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idModulo = $request->idmodulo;
        ConnectDatabase($idempresa);

        $permisos= DB::select("SELECT u.* FROM mc_usermenu u WHERE idusuario='$idusuario' and idmodulo='$idModulo'");
        
        $datos = $permisos;       
        return $datos;

        /*$datos = array(
            "permisoMenu" => $permisos,
        );*/           

       // return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    function PermisoSubMenus(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idMenu = $request->idmenu;
        ConnectDatabase($idempresa);

        $permisos= DB::select("SELECT u.* FROM mc_usersubmenu u WHERE idusuario='$idusuario' and idmenu='$idMenu'");
        
        $datos = $permisos;       
        return $datos;

        /*$datos = array(
            "permisoMenu" => $permisos,
        );*/           

       // return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function NombreModulo(Request $request)
    {        
        $idModulo = $request->idmodulo;

        $Modulo = DB::connection("General")->select("SELECT * FROM mc1003 WHERE idmodulo='$idModulo'");    
        $datos = $Modulo;
        return $datos;
    }

    public function NombreMenu(Request $request)
    {        
        $idMenu = $request->idmenu;

        $Modulo = DB::connection("General")->select("SELECT * FROM mc1004 WHERE idmenu='$idMenu'");    
        $datos = $Modulo;
        return $datos;
    }
}
