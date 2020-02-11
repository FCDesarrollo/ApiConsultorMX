<?php


namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    function inicioUsuario(Request $request)
    {
        $valida = verificaUsuario($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $usuario = $valida[0]['usuario'];
            $iduser = $usuario[0]->idusuario;
            $array["usuario"] = $valida[0]['usuario'];
            $empresa = DB::connection("General")->select("SELECT mc1000.* FROM mc1002 m02 
                                                    INNER JOIN mc1000 on m02.idempresa=mc1000.idempresa 
                                                    WHERE m02.idusuario=$iduser AND mc1000.status=1");
            $array["empresas"] = $empresa;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
