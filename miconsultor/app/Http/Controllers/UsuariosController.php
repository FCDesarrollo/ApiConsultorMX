<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UsuariosController extends Controller
{
    function Pruebas()
    {
        $data = array(

            array("nombre" => "Gustavo",
            "apaterno" => "Gomez",
            "amaterbo" => "Floriano"),
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
        
        $usuario = DB::connection("General")->select("SELECT e.*,u.* 
                                    FROM mc1000 e 
                                        INNER JOIN mc1002 r ON e.idempresa=r.idempresa 
                                            INNER JOIN mc1001 u ON r.idusuario=u.idusuario 
                                            WHERE u.cel='$request->cel' AND u.password='$request->contra' 
                                            AND u.status=1");
       
       // $usuario = DB::connection("General")->select("SELECT * FROM mc1001 WHERE cel='$request->cel' AND password='$request->contra' AND status=1");
       
        $datos = array(
            "usuario" => $usuario,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    function ListaUsuarios($idcliente)
    {
       // ConnectDatabase($idcliente);

        //$empleados = DB::select("SELECT *, coalesce((select nombre from sucursales where id = empleados.idsucursal),'') AS sucursal FROM empleados WHERE status=1 ORDER BY nombre");

        $empleados = DB::connection("General")->select("SELECT * FROM mc1001 WHERE  status=1");

        $datos = array(
            "usuario" => $empleados,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function EliminarUsuario(Request $request)
    {
       ConnectDatabase($request->idcliente);

        $id = $request->idusuario;
        DB::table('mc1001')->where("idusuario", $id)->update(["status"=>"0"]);
        return $id;
    }
}
