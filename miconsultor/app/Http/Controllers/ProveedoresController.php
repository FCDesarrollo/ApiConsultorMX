<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProveedoresController extends Controller
{

    function getEmpresas(Request $request)
    {
        $empresas = DB::connection("General")->select("SELECT * FROM mc1000");

        $array["empresas"] = $empresas;

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getUsuarios(Request $request)
    {
        $usuarios = DB::connection("General")->select("SELECT mc1001.*, mc1006.nombre AS tipoUsuario FROM mc1001 LEFT JOIN mc1006 ON mc1001.tipo = mc1006.idperfil");

        $array["usuarios"] = $usuarios;

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
    
    function getUsuario(Request $request)
    {
        $idusuario = $request->idusuario;
        $usuario = DB::connection("General")->select("SELECT mc1001.*, mc1006.nombre AS tipoUsuario FROM mc1001 LEFT JOIN mc1006 ON mc1001.tipo = mc1006.idperfil WHERE mc1001.idusuario = $idusuario");

        $array["usuario"] = $usuario;

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function guardarUsuario(Request $request)
    {
        $nombre = $request->nombre;
        $apellidop = $request->apellidop;
        $apellidom = $request->apellidom;
        $cel = $request->cel;
        $correo = $request->correo;
        $password = password_hash($request->password, PASSWORD_BCRYPT);
        $tipo = $request->tipo;
        $accion = $request->accion;
        $validacion = $request->validacion;
        $idusuario = $request->idusuario;

        $array["error"] = 0;
        $validarcel = DB::connection("General")->select("SELECT * FROM mc1001 WHERE cel = '$cel'");
        if(count($validarcel) == 0 || $validacion == 0) {
            $validarcorreo = DB::connection("General")->select("SELECT * FROM mc1001 WHERE correo = '$correo'");
            if(count($validarcorreo) == 0 || $validacion == 0) {
                if($accion == 1) {
                    do {
                        $identificador = rand(100000, 999999);
                        $validaridentificador = DB::connection("General")->select("SELECT * FROM mc1001 WHERE identificador = '$identificador'");
                    }while($validaridentificador == 0);
            
                    DB::connection("General")->table("mc1001")->insert(["nombre" => $nombre, "apellidop" => $apellidop, "apellidom" => $apellidom, "cel" => $cel, "correo" => $correo, "password" => $password, "status" => 1 , "tipo" => $tipo, "identificador" => $identificador]);
                }
                else {
                    DB::connection("General")->table('mc1001')->where("idusuario", $idusuario)->update(["nombre" => $nombre, "apellidop" => $apellidop, "apellidom" => $apellidom, "cel" => $cel, "correo" => $correo, "password" => $password, "tipo" => $tipo]);
                }
            }
            else {
                $array["error"] = -2;
            }
        }
        else {
            $array["error"] = -1;
        }
        
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getPerfiles(Request $request)
    {
        $perfiles = DB::connection("General")->select("SELECT * FROM mc1006");

        $array["perfiles"] = $perfiles;

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}