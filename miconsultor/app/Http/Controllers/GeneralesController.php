<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class GeneralesController extends Controller
{
    function PerfilUsuario(Request $request)
    {
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        ConnectDatabase($idempresa);
        //$id = $request->$idusuario;
        //$empleados = DB::select("SELECT *, coalesce((select nombre from sucursales where id = empleados.idsucursal),'') AS sucursal FROM empleados WHERE status=1 ORDER BY nombre");

        //$perfil = DB::select("SELECT * FROM perfiles");

        $perfil = DB::select("SELECT * FROM perfiles p 
                 INNER JOIN usuarioperfil u ON p.idperfil=u.idperfil WHERE u.idusuario='$idusuario'");

        $datos = array(
            "perfil" => $perfil,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    function PermisosUsuario(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        ConnectDatabase($idempresa);

        $permisos= DB::select("SELECT u.*,p.nombre FROM usuariopermiso u 
        INNER JOIN perfiles p ON u.idperfil=p.idperfil WHERE idusuario='$idusuario'");
        
        $datos = array(
            "permisos" => $permisos,
        );           

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    function updatePermisoUsuario(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idmodulo = $request->idmodulo;
        ConnectDatabase($idempresa);

        DB::table('usuariopermiso')->where("idusuario", $idusuario)->where("idmodulo", $idmodulo)->update(["tipopermiso"=>$request->tipopermiso]);
        return $idusuario;
    }

    function VinculaEmpresa(Request $request){
        $rfcempresa = $request->rfc;
        $passempresa = $request->contra;
        $iduser = $request->idusuario;
        $idperfil = $request->idperfil;

        $empresa = DB::connection("General")->select("SELECT idempresa FROM mc1000 WHERE rfc='$rfcempresa' AND password='$passempresa'");    
        if (empty($empresa)){
            $idempresa = 0;
            
        }else{
            $idempresa = $empresa[0]->idempresa;
            $id = DB::connection("General")->table('mc1002')->insert(
                ['idusuario' => $iduser,'idempresa' => $idempresa]);
            
            ConnectDatabase($idempresa);
            $idP = DB::table('mc_userprofile')->insert(
                ['idusuario' => $iduser,'idperfil' => $idperfil]);
            
            //SELECCIONAMOS LOS PERMISOS A LOS MODULOS DEL PERFIL
            $perfil = DB::select("SELECT idmodulo,tipopermiso FROM mc_modpermis WHERE idperfil='$idperfil'");     
            
            //INSERTAMOS LOS PERMISOS DE MODULOS DEL PERFIL AL USUARIO
            foreach($perfil as $t){
                $idU = DB::table('mc_usermod')->insert(
                    ['idusuario' => $iduser,'idperfil' => $idperfil,
                    'idmodulo' => $t->idmodulo,'tipopermiso' => $t->tipopermiso ]);
            }

            //SELECCIONAMOS LOS PERMISOS A LOS MENUS DEL PERFIL
            $perfil = DB::select("SELECT idmodulo,idmenu,tipopermiso FROM mc_menupermis WHERE idperfil='$idperfil'");     
            
            //INSERTAMOS LOS PERMISOS DE MENU DEL PERFIL AL USUARIO
            foreach($perfil as $t){
                $idU = DB::table('mc_usermenu')->insert(
                    ['idusuario' => $iduser,'idperfil' => $idperfil,
                    'idmodulo' => $t->idmodulo,'idmenu' => $t->idmenu,'tipopermiso' => $t->tipopermiso ]);
            }

            //SELECCIONAMOS LOS PERMISOS A LOS SUBMENUS DEL PERFIL
            $perfil = DB::select("SELECT idmenu,idsubmenu,tipopermiso,notificaciones FROM mc_submenupermis WHERE idperfil='$idperfil'");     
            
            //INSERTAMOS LOS PERMISOS DE SUBMENU DEL PERFIL AL USUARIO
            foreach($perfil as $t){
                $idU = DB::table('mc_usersubmenu')->insert(
                    ['idusuario' => $iduser,'idperfil' => $idperfil,
                    'idmenu' => $t->idmenu,'idsubmenu' => $t->idsubmenu,'tipopermiso' => $t->tipopermiso,'notificaciones' => $t->notificaciones ]);
            }

        }
        return $idempresa;
    }

    function PerfilesEmpresa($idempresa){

        ConnectDatabase($idempresa);

        $modulos = DB::select("SELECT * FROM mc_profiles");    
        $datos = array(
            "perfiles" => $modulos,
        );
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function EliminarPerfilEmpresa(Request $request)
    {       
        ConnectDatabase($request->idempresa);
        
        $id = $request->idperfil;
        DB::table('mc_profiles')->where("idperfil", $id)->update(["status"=>"0"]);
        return response($id, 200);
        //return $id;
    }

    public function DatosPerfilEmpresa(Request $request)
    {
        ConnectDatabase($request->idempresa);
        $IDPer = $request->idperfil;

        $perfil = DB::select("SELECT * FROM mc_profiles WHERE idperfil='$IDPer'");    
        $datos = array(
            "perfil" => $perfil,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function PermisosPerfil(Request $request)
    {
        ConnectDatabase($request->idempresa);
        $IDPer = $request->idperfil;

        $permisos = DB::select("SELECT * FROM permisos WHERE idperfil='$IDPer'");    
        $datos = array(
            "permisos" => $permisos,
        );

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function GuardaPerfilEmpresa(Request $request)
    {
        $idP=$request->idperfil;
        $now = date('Y-m-d');
        ConnectDatabase($request->idempresa);
        
        if ($idP == 0 ){
            $uperfil = DB::select("SELECT max(idperfil) + 1 as idper  FROM mc_profiles");
            if ($uperfil[0]->idper <= 4){
                $uidperfil=5;   
            }else{
                $uidperfil = $uperfil[0]->idper;
            }
            
            $idP = DB::table('mc_profiles')->insertGetId(
                ['idperfil' => $uidperfil,'nombre' => $request->nombre,
                'descripcion' => $request->desc,'fecha' => $now,'status' =>"1" ]); 

            $idP = $uidperfil;    
        }else{
            DB::table('mc_profiles')->where("idperfil", $idP)->
                    update(["nombre"=>$request->nombre, 'descripcion' => $request->desc]);    
        }
        return $idP;
    }

    public function ModulosPerfil(Request $request)
    {
        $idU = $request->id;
        ConnectDatabase($request->idempresa);
        if ($idU == 0){
            $idU = DB::table('mc_modpermis')->insertGetId(
                ['idperfil' => $request->idperfil,'idmodulo' => $request->idmodulo,'tipopermiso' => $request->tipopermiso]); 
        }else{
            DB::table('mc_modpermis')->where("idperfil", $request->idperfil)->
            where("idmodulo", $request->idmodulo)->update(['tipopermiso' => $request->tipopermiso]); 
        }
        return $idU;
    }

    public function MenuPerfil(Request $request)
    {
        $idU = $request->id;
        ConnectDatabase($request->idempresa);
        if ($idU == 0){
            $idU = DB::table('mc_menupermis')->insertGetId(
                ['idperfil' => $request->idperfil,'idmodulo' => $request->idmodulo,
                'idmenu' => $request->idmenu,'tipopermiso' => $request->tipopermiso]);
        }else{
            DB::table('mc_menupermis')->where("idperfil", $request->idperfil)->
            where("idmodulo", $request->idmodulo)->where("idmenu", $request->idmenu)->update(['tipopermiso' => $request->tipopermiso]); 
        }
        return $idU;
    }

    public function SubMenuPerfil(Request $request)
    {
        $idU = $request->id;
        ConnectDatabase($request->idempresa);
        if ($idU == 0){
            $idU = DB::table('mc_submenupermis')->insertGetId(
                ['idperfil' => $request->idperfil,'idmenu' => $request->idmenu,
                'idsubmenu' => $request->idsubmenu, 'tipopermiso' => $request->tipopermiso,
                 'notificaciones' => $request->notificaciones]);
        }else{
            DB::table('mc_submenupermis')->where("idperfil", $request->idperfil)->
            where("idmenu", $request->idmenu)->where("idsubmenu", $request->idsubmenu)->
            update(['tipopermiso' => $request->tipopermiso, 'notificaciones' => $request->notificaciones]); 
        }
        return $idU;
    }

    public function EditarPerfilEmpresa(Request $request){
        ConnectDatabase($request->idempresa);
        $idp = $request->idperfil;
        DB::table('perfiles')->where("idperfil", $idp)->update(['nombre' => $request->nombre,
        'descripcion' => $request->desc,'status' => $request->status ]);
        return $idp;
    }

    public function PerfilesGen(Request $request)
    {

        $modulos = DB::connection("General")->select("SELECT * FROM mc1006");    
        $datos = array(
            "perfiles" => $modulos,
        );
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function PermisosModPerfil(Request $request)
    {
        ConnectDatabase($request->idempresa);
        $IDPer = $request->idperfil;

        $permisos = DB::select("SELECT * FROM mc_modpermis WHERE idperfil='$IDPer'");    
        $datos = $permisos;       
        return $datos;
    }

    function PermisosMenusPerfil(Request $request){
        $idempresa = $request->idempresa;
        $idModulo = $request->idmodulo;
        $IDPer = $request->idperfil;
        ConnectDatabase($idempresa);

        $permisos= DB::select("SELECT u.* FROM mc_menupermis u WHERE u.idmodulo='$idModulo' and u.idperfil='$IDPer' ORDER BY u.idmodulo DESC");
        
        $datos = $permisos;       
        return $datos;
    }

    function PermisoSubMenusPerfil(Request $request){
        $idempresa = $request->idempresa;
        $idMenu = $request->idmenu;
        $IDPer = $request->idperfil;
        ConnectDatabase($idempresa);

        $permisos= DB::select("SELECT u.* FROM mc_submenupermis u WHERE u.idmenu='$idMenu' and u.idperfil='$IDPer' ORDER BY u.idmenu DESC");
        
        $datos = $permisos;       
        return $datos;
    }

    //RECEPCION POR LOTES
    function RegistrarLote(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        ConnectDatabase($idempresa);
        $tipodocto = $request->tipodocto;
        $tipodoctodet = $request->tipodoctodet;
        $fechadocto = $request->fecha;
        $foliodocto = $request->folio;
        $seriedocto = $request->serie;

        $now = getdate();
        $fecha_act = $now['mday']."/".$now['mon']."/".$now['year'];

    
        $lote = DB::select("SELECT * FROM mc_lotesdocto WHERE folio = '$foliodocto' And tipodocto = '$tipodocto'");
        echo $lote[0]->iddocto;
        if($lote[0]->iddocto <> 0){
            $lote = DB::table('mc_lotesdocto')->insertGetId(
                ['idusuario' => $idusuario,'tipodocto' => $tipodocto,'tipodoctodet' => $tipodoctodet, 'fecha' => $fechadocto,'folio' => $foliodocto, 'serie' => $seriedocto]);
        }else{
            $lote = DB::table('perfiles')->where("iddocto", $lote[0]->iddocto)->update(['ultimacarga' => $fecha_act]);
        }

        return $lote;
    }

    function RegistrarMovtos(Request $request){
        $idempresa = $request->idempresa;
        ConnectDatabase($idempresa);

    }    
    //-----------------//

}
