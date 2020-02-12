<?php


namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    function inicioUsuario(Request $request)
    {
        $valida = verificaLogin($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $array["usuario"] = $valida[0]['usuario'];
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
