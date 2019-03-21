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
        INNER JOIN mc_profiles p ON u.idperfil=p.idperfil WHERE u.idusuario='$idusuario'");
        
        $datos = $permisos;       
        return $datos;
        //return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }
    
    function MenusPermiso(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        ConnectDatabase($idempresa);

        $permisos= DB::select("SELECT u.* FROM mc_usermenu u WHERE u.idusuario='$idusuario'");
        
        $datos = $permisos;       
        return $datos;
    }


    function PermisoMenus(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idModulo = $request->idmodulo;
        ConnectDatabase($idempresa);

        $permisos= DB::select("SELECT u.* FROM mc_usermenu u WHERE u.idusuario='$idusuario' and u.idmodulo='$idModulo'");
        
        $datos = $permisos;       
        return $datos;
    }

    function SubMenuPermiso(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        ConnectDatabase($idempresa);

        $permisos= DB::select("SELECT u.* FROM mc_usersubmenu u WHERE idusuario='$idusuario'");
        
        $datos = $permisos;       
        return $datos;
    }


    function PermisoSubMenus(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idMenu = $request->idmenu;
        ConnectDatabase($idempresa);

        $permisos= DB::select("SELECT u.* FROM mc_usersubmenu u WHERE idusuario='$idusuario' and idmenu='$idMenu'");
        
        $datos = $permisos;       
        return $datos;
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

    public function NombreSubMenu(Request $request)
    {        
        $IdSubMenu = $request->idsubmenu;

        $Modulo = DB::connection("General")->select("SELECT * FROM mc1005 WHERE idsubmenu='$IdSubMenu'");    
        $datos = $Modulo;
        return $datos;
    }
    public function Modulos(){
        $Modulo = DB::connection("General")->select("SELECT * FROM mc1003 WHERE Status = '1'");
        $datos = $Modulo;   
        return $datos;
    }

    public function Menus(){
        $Modulo = DB::connection("General")->select("SELECT men.*,modu.nombre_modulo FROM mc1004 men  
                            INNER JOIN mc1003 modu ON men.idmodulo=modu.idmodulo WHERE men.Status = '1'");
        $datos = $Modulo;   
        return $datos;
    }    

    public function SubMenus(){
        $Modulo = DB::connection("General")->select("SELECT sub.*,men.nombre_menu FROM mc1005 sub 
                                INNER JOIN mc1004 men ON sub.idmenu=men.idmenu WHERE sub.Status = '1'");
        $datos = $Modulo;   
        return $datos;
    }     


    public function UpdatePermisoModulo(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idmodulo = $request->idmodulo;
        ConnectDatabase($idempresa);

        DB::table('mc_usermod')->where("idusuario", $idusuario)->where("idmodulo", $idmodulo)->update(["tipopermiso"=>$request->tipopermiso]);
        return $idusuario;
    }
    public function UpdatePermisoMenu(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idmenu = $request->idmenu;
        ConnectDatabase($idempresa);

        DB::table('mc_usermenu')->where("idusuario", $idusuario)->where("idmenu", $idmenu)->update(["tipopermiso"=>$request->tipopermiso]);
        return $idusuario;
    }
    public function UpdatePermisoSubMenu(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idsubmenu = $request->idsubmenu;
        ConnectDatabase($idempresa);

        DB::table('mc_usersubmenu')->where("idusuario", $idusuario)->where("idsubmenu", $idsubmenu)->update(["tipopermiso"=>$request->tipopermiso]);
        return $idusuario;
    }

}
