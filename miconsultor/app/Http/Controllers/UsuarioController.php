<?php


namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    function inicioUsuario(Request $request)
    {
        $usuario = DB::connection("General")->select("SELECT * FROM mc1001 
                                    WHERE correo='$request->usuario' or cel='$request->usuario' AND status=1");

        if(!empty($usuario)){
            $hash_BD = $usuario[0]->password;

            if (password_verify($request->pwd, $hash_BD)) {
                $datos = array(
                    "usuario" => $usuario,
                );
            } else {
                $datos = array(
                    "usuario" => "",
                );
            }
        }else{
            $datos = array(
                "usuario" => "",
            );
        }              
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }
}
