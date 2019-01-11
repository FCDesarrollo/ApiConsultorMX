<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UsuariosController extends Controller
{
    function Pruebas()
    {
        $data = array(

            array("nombre" => "Alejandro",
            "apaterno" => "Paez",
            "amaterbo" => "Tirado"),
            array("nombre" => "Gustavo",
            "apaterno" => "Gomez",
            "amaterbo" => "Floriano",
            "Prueba" => "Prueba"),
            array("nombre" => "Gustavo",
            "apaterno" => "Gomez",
            "amaterbo" => "Floriano",
            "Prueba" => "Prueba"),
            
        );        

        return json_encode($data);
    }

    function Login(Request $request)
    {
        
      /* $usuario = DB::connection("General")->select("SELECT e.*,u.* 
                                    FROM mc1000 e 
                                        INNER JOIN mc1002 r ON e.idempresa=r.idempresa 
                                            INNER JOIN mc1001 u ON r.idusuario=u.idusuario 
                                            WHERE u.cel='$request->cel' AND u.password='$request->contra' 
                                            AND u.status=1");*/
       
        $usuario = DB::connection("General")->select("SELECT * FROM mc1001 WHERE correo='$request->correo' AND password='$request->contra' AND status=1");
       
        $datos = array(
            "usuario" => $usuario,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function ListaUsuarios($idcliente)
    {
       // ConnectDatabase($idcliente);

        //$empleados = DB::select("SELECT *, coalesce((select nombre from sucursales where id = empleados.idsucursal),'') AS sucursal FROM empleados WHERE status=1 ORDER BY nombre");

        $empleados = DB::connection("General")->select("SELECT * FROM mc1001 WHERE  status=1");

        $datos = array(
            "usuario" => $empleados,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }


    public function ListaUsuariosAdmin($idempresa)
    {
      
        if ($idempresa == 0 ) {
            $usuarios = DB::connection("General")->select("SELECT DISTINCT u.*,e.*,u.status AS st FROM mc1001 u 
                                                            LEFT JOIN mc1002 r ON u.idusuario=r.idusuario
                                                            LEFT JOIN mc1000 e ON r.idempresa=e.idempresa WHERE cel IS NOT NULL" 
                                                      );
            //$usuarios = DB::connection("General")->select("SELECT DISTINCT u.*,r.*,coalesce((select nombreempresa from mc1000 where idempresa = r.idempresa),'') AS nombreempresa
             //                                           FROM mc1001 u 
              //                                          INNER JOIN mc1002 r ON u.idusuario=r.idusuario" 
                //                                        );
        }else{
            $usuarios = DB::connection("General")->select("SELECT DISTINCT u.*,r.*,coalesce((select nombreempresa from mc1000 where idempresa = r.idempresa),'') AS nombreempresa
                                                        FROM mc1001 u 
                                                        INNER JOIN mc1002 r ON u.idusuario=r.idusuario
                                                        WHERE r.idempresa='$idempresa'" 
                                                        );
        }
            

        $datos = array(
            "usuarios" => $usuarios,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function GuardaUsuario(Request $request)
    {
        if($request->idusuario==0){
            $data = $request->input();
            unset($data["idusuario"]);
            $id = DB::connection("General")->table('mc1001')->insertGetId($data);
        }else{
            $data = $request->input();
            $id = $data["idusuario"];
            unset($data["idusuario"]);
            DB::connection("General")->table('mc1001')->where("idusuario", $id)->update($data);
        }
        return $id;
    }

    function DatosUsuario($idusuario)
    {
       $usuario = DB::connection("General")->select("SELECT * FROM mc1001 WHERE idusuario='$idusuario'");    
        $datos = array(
            "usuario" => $usuario,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function EliminarUsuario(Request $request)
    {                
        $id = $request->idusuario;
        DB::connection("General")->table('mc1001')->where("idusuario", $id)->update(["status"=>"0"]);
        return $id;
    }

    public function Desvincular(Request $request)
    {                
        $id = $request->idusuario;
        $idem= $request->idemp;
        DB::connection("General")->table('mc1002')->where("idusuario", $id)->where("idempresa", $idem)->delete();
        return $id;
    }

    public function VerificaUsuario(Request $request)
    {      
        $id = $request->idusuario;
        DB::connection("General")->table('MC1001')->where("idusuario", $request->idusuario)->where("identificador", $request->identificador)->update(["tipo"=>"1"]);
        return $id;        
    }

    public function ObtenerUsuarioNuevo(Request $request)
    {        
        $identificador = $request->identificador;

        $usuario = DB::connection("General")->select("SELECT * FROM mc1001 WHERE identificador='$identificador'");    
        $datos = array(
            "usuario" => $usuario,
        );
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }  
    
     public function Modulos(Request $request)
    {

        $modulos = DB::connection("General")->select("SELECT * FROM modulos");    
        $datos = array(
            "modulos" => $modulos,
        );
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function DatosModulo($IDMod)
    {
        $modulo = DB::connection("General")->select("SELECT * FROM modulos WHERE idmodulo='$IDMod'");    
        $datos = array(
            "modulo" => $modulo,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function Perfiles(Request $request)
    {

        $modulos = DB::connection("General")->select("SELECT * FROM perfiles");    
        $datos = array(
            "perfiles" => $modulos,
        );
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function DatosPerfil($IDPer)
    {
        $modulo = DB::connection("General")->select("SELECT * FROM perfiles WHERE idperfil='$IDPer'");    
        $datos = array(
            "perfil" => $modulo,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function ListaPermisos($IDPer)
    {
        $permisos = DB::connection("General")->select("SELECT p.*,m.* FROM permisos p INNER JOIN 
                                                    modulos m ON p.idmodulo=m.idmodulo WHERE idperfil='$IDPer'");    
        $datos = array(
            "permisos" => $permisos,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function EliminaPermiso(Request $request){
        $IDPermiso = $request->idpermiso;
        DB::connection("General")->table('permisos')->where("id", $IDPermiso)->delete();
        return $IDPermiso;
    }

    public function GuardaPermiso(Request $request)
    {

        $IDPerfil = $request->idperfil;
        $IDModulo = $request->idmodulo;
        if (DB::connection("General")->select("SELECT * FROM permisos
                                         WHERE idperfil='$IDPerfil' AND idmodulo='$IDModulo'")){
        //$permiso["id"]>0){
            DB::connection("General")->table('permisos')->where("idperfil", $IDPerfil)->
                    where("idmodulo", $IDModulo)->update(["tipopermiso"=>$request->tipopermiso]);
        }else{
            $inserted = DB::connection("General")->table('permisos')->insert(
                ['idperfil' => $IDPerfil,'idmodulo' => $IDModulo,'tipopermiso' => $request->tipopermiso ]);
            //$id = DB::connection("General")->table('permisos')->insertGetId($request->input());
        } 
        return $IDPerfil;
    }

    public function GuardaPerfil(Request $request)
    {
        if($request->id==0){
            $ulidperfil = DB::connection("General")->select("SELECT max(idperfil) + 1  FROM perfiles");
            if ($ulidperfil <= 3){
                $ulidperfil=4;   
            }
            $data = $request->input();
            unset($data["id"]);
            $data["idperfil"]=$ulidperfil;
            $idp = DB::connection("General")->table('perfiles')->insertGetId($data);
        }else{
            $data = $request->input();
            $idp = $data["id"];
            unset($data["id"]);
            DB::connection("General")->table('perfiles')->where("id", $idp)->update($data);
        }
        return $idp;
    }
 
    public function ValidarCorreo(Request $request)
    {        
        $correo = $request->correo;

        $usuario = DB::connection("General")->select("SELECT * FROM mc1001 WHERE correo='$correo'");    
        $datos = array(
            "usuario" => $usuario,
        );
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    } 

}
