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
            $idP = DB::table('usuarioperfil')->insert(
                ['idusuario' => $iduser,'idperfil' => $idperfil]);

            $perfil = DB::select("SELECT idmodulo,tipopermiso FROM perfiles p 
                            INNER JOIN permisos per ON p.idperfil=per.idperfil WHERE p.idperfil='$idperfil'");     
            
            foreach($perfil as $t){
                $idU = DB::table('usuariopermiso')->insert(
                    ['idusuario' => $iduser,'idperfil' => $idperfil,
                    'idmodulo' => $t->idmodulo,'tipopermiso' => $t->tipopermiso ]);
            }
        }
        return $idempresa;
    }

    function PerfilesEmpresa($idempresa){

        ConnectDatabase($idempresa);

        $modulos = DB::select("SELECT * FROM perfiles");    
        $datos = array(
            "perfiles" => $modulos,
        );
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function EliminarPerfilEmpresa(Request $request)
    {       
        ConnectDatabase($request->idempresa);
        
        $id = $request->idperfil;
        DB::table('perfiles')->where("idperfil", $id)->update(["status"=>"0"]);
        return response($id, 200);
        //return $id;
    }

    public function DatosPerfilEmpresa(Request $request)
    {
        ConnectDatabase($request->idempresa);
        $IDPer = $request->idperfil;

        $perfil = DB::select("SELECT * FROM perfiles WHERE idperfil='$IDPer'");    
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
        $idP=0;
        $now = date('Y-m-d');
        ConnectDatabase($request->idempresa);

        $uperfil = DB::select("SELECT max(idperfil) + 1 as idper  FROM perfiles");
        if ($uperfil[0]->idper <= 3){
            $uidperfil=4;   
        }else{
            $uidperfil = $uperfil[0]->idper;
        }
        
        $idP = DB::table('perfiles')->insertGetId(
            ['idperfil' => $uidperfil,'nombre' => $request->nombre,
            'descripcion' => $request->desc,'fecha' => $now,'status' =>"1" ]);

               
        $Permisos = $request->todos;
        foreach($Permisos as $t){
           $idU = DB::table('permisos')->insert(
                ['idperfil' => $uidperfil,
                'idmodulo' => $t['idmod'],'tipopermiso' => $t['permiso'] ]);
        }   

        $idP = $uidperfil;    
        
        return $idP;
    }

    public function EditarPerfilEmpresa(Request $request){
        ConnectDatabase($request->idempresa);
        $idp = $request->idperfil;
        DB::table('perfiles')->where("idperfil", $idp)->update(['nombre' => $request->nombre,
        'descripcion' => $request->desc,'status' => $request->status ]);
        return $idp;
    }

    function updatePermisoPerfil(Request $request){
        $idempresa = $request->idempresa;
        $idperfil = $request->idperfil;
        $idmodulo = $request->idmodulo;
        ConnectDatabase($idempresa);

        DB::table('permisos')->where("idperfil", $idperfil)->where("idmodulo", $idmodulo)->update(["tipopermiso"=>$request->tipopermiso]);
        return $idperfil;
    }

}
