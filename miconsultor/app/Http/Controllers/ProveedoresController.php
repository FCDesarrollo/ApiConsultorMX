<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProveedoresController extends Controller
{
    function getUsuarios(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $usuarios = DB::connection("General")->select("SELECT mc1001.*, mc1006.nombre AS tipoUsuario FROM mc1001 LEFT JOIN mc1006 ON mc1001.tipo = mc1006.idperfil");

            $array["usuarios"] = $usuarios;
        }
        
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
    
    function getUsuario(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $idusuario = $request->idusuario;
            $usuario = DB::connection("General")->select("SELECT mc1001.*, mc1006.nombre AS tipoUsuario FROM mc1001 LEFT JOIN mc1006 ON mc1001.tipo = mc1006.idperfil WHERE mc1001.idusuario = $idusuario");

            $array["usuario"] = $usuario;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function guardarUsuario(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $nombre = $request->nombre;
            $apellidop = $request->apellidop;
            $apellidom = $request->apellidom;
            $cel = $request->cel;
            $correo = $request->correo;
            $password = password_hash($request->password, PASSWORD_BCRYPT);
            $tipo = $request->tipo;
            $accion = $request->accion;
            $validacioncel = $request->validacioncel;
            $validacioncorreo = $request->validacioncorreo;
            $idusuario = $request->idusuario;
            
            $validarcel = DB::connection("General")->select("SELECT * FROM mc1001 WHERE cel = '$cel'");
            if(count($validarcel) == 0 || $validacioncel == 0) {
                $validarcorreo = DB::connection("General")->select("SELECT * FROM mc1001 WHERE correo = '$correo'");
                if(count($validarcorreo) == 0 || $validacioncorreo == 0) {
                    if($accion == 1) {
                        do {
                            $identificador = rand(100000, 999999);
                            $validaridentificador = DB::connection("General")->select("SELECT * FROM mc1001 WHERE identificador = '$identificador'");
                        }while($validaridentificador == 0);
                
                        DB::connection("General")->table("mc1001")->insert(["nombre" => $nombre, "apellidop" => $apellidop, "apellidom" => $apellidom, "cel" => $cel, "correo" => $correo, "password" => $password, "status" => 1 , "tipo" => $tipo, "identificador" => $identificador]);
                    }
                    else {
                        DB::connection("General")->table('mc1001')->where("idusuario", $idusuario)->update(["nombre" => $nombre, "apellidop" => $apellidop, "apellidom" => $apellidom, "cel" => $cel, "correo" => $correo, "tipo" => $tipo]);
                    }
                }
                else {
                    $array["error"] = -2;
                }
            }
            else {
                $array["error"] = -1;
            }
        }
        
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function cambioContraUsuario(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $idusuario = $request->idusuario;
            $password = password_hash($request->password, PASSWORD_BCRYPT);
            DB::connection("General")->table('mc1001')->where("idusuario", $idusuario)->update(["password" => $password]);
            /* $usuario = DB::connection("General")->select("SELECT * FROM mc1001 
            WHERE idusuario='$idusuario'");
            $array["usuario"] = $usuario; */
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function cambiarEstatusUsuario(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $idusuario = $request->idusuario;
            $estatus = $request->estatus;
            DB::connection("General")->table('mc1001')->where("idusuario", $idusuario)->update(["status" => $estatus]);
            DB::connection("General")->table('mc1002')->where("idusuario", $idusuario)->update(["estatus" => $estatus]);
            /* DB::connection("General")->table('mc1001')->where("idusuario", $idusuario)->delete();
            DB::connection("General")->table('mc1002')->where("idusuario", $idusuario)->delete(); */
        }
        
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getEmpresas(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if($valida[0]['error'] === 0) {
            $empresas = DB::connection("General")->select("SELECT * FROM mc1000");

            $array["empresas"] = $empresas;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getUsuariosPorEmpresa(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if($valida[0]['error'] === 0) {
            $idempresa = $request->idempresa;
            $db = $request->db;
            /* $usuarios = DB::connection("General")->select("SELECT mc1001.* FROM mc1002 INNER JOIN mc1001 ON mc1002.idusuario = mc1001.idusuario WHERE idempresa = $idempresa"); */
            $usuarios = DB::connection("General")->select("SELECT mc1001.*, $db.mc_profiles.nombre AS perfil FROM mc1002 INNER JOIN mc1001 ON mc1002.idusuario = mc1001.idusuario 
            INNER JOIN $db.mc_userprofile ON mc1001.idusuario = $db.mc_userprofile.idusuario  
            INNER JOIN $db.mc_profiles ON $db.mc_userprofile.idperfil = $db.mc_profiles.idperfil
            WHERE idempresa = $idempresa");

            $array["usuarios"] = $usuarios;
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