<?php


namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    function inicioUsuario(Request $request)
    {
        $valida = verificaUsuario($request->usuario, $request->pwd);
        if ($valida != "2" and $valida != "3"){
            $usuario = $valida['usuario'];
            $iduser = $usuario[0]->idusuario;
            $empresa = DB::connection("General")->select("SELECT mc1000.* FROM mc1002 m02 
                                                    INNER JOIN mc1000 on m02.idempresa=mc1000.idempresa 
                                                    WHERE m02.idusuario=$iduser AND mc1000.status=1");
                $datos = array(
                   "empresa" => $empresa,
                );
        }else{
            $datos = $valida;
        }
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }
}
