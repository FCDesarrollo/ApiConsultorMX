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

}
