<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class EmpresasController extends Controller
{
    function ListaSucursales($idempresa)
    {
        ConnectDatabase($idempresa);

        //$empleados = DB::select("SELECT *, coalesce((select nombre from sucursales where id = empleados.idsucursal),'') AS sucursal FROM empleados WHERE status=1 ORDER BY nombre");

        $sucursales = DB::select("SELECT * FROM sucursales WHERE status=1");

        $datos = array(
            "sucursales" => $sucursales,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    function ListaEmpresas(Request $request)
    {
       
        $idusuario = $request->idusuario;
        if ($request->tipo==3){
            $empresas = DB::connection("General")->select("SELECT * FROM mc1000 ");
        }else{
            $empresas = DB::connection("General")->select("SELECT e.*,u.* FROM mc1000 e 
                                            INNER JOIN mc1002 r ON e.idempresa=r.idempresa 
                                    INNER JOIN mc1001 u ON r.idusuario=u.idusuario WHERE u.idusuario='$idusuario'");
        }
        
       

        $datos = array(
            "empresas" => $empresas,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    function DatosEmpresaAD($idempresa)
    {
       $empresa = DB::connection("General")->select("SELECT * FROM mc1000 WHERE idempresa='$idempresa'");    


        $datos = array(
            "empresa" => $empresa,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function EliminarEmpresaAD(Request $request)
    {                
        $id = $request->idempresa;
        DB::connection("General")->table('mc1000')->where("idempresa", $id)->update(["status"=>"0"]);
        return $id;
    }

    public function GuardarEmpresaAD(Request $request)
    {
        if($request->idempresa==0){
            $data = $request->input();
            unset($data["idempresa"]);
            $id = DB::connection("General")->table('mc1000')->insertGetId($request->input());
        }else{
            $data = $request->input();
            $id = $data["idempresa"];
            unset($data["idempresa"]);
            DB::connection("General")->table('mc1000')->where("idempresa", $id)->update($data);
        }
        return $id;
    }

     public function GuardarEmpresa(Request $request)
        {
            
            if($request->idempresa==0){
                $data = $request->input();
                $password = $data["password"];
                unset($data["idempresa"]); 
                $data['password'] = md5($password);              
                $id = DB::connection("General")->table('mc1000')->insertGetId($data);
            }
            return $id;
        }
}
