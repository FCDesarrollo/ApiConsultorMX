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

    function getEmpresa(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if($valida[0]['error'] === 0) {
            $idempresa = $request->idempresa;
            $empresa = DB::connection("General")->select("SELECT * FROM mc1000 WHERE idempresa = $idempresa");

            $array["empresa"] = $empresa;
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

    function getNotificacionesEmpresa(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if($valida[0]['error'] === 0) {
            $idempresa = $request->idempresa;
            $notificaciones = DB::connection("General")->select("SELECT mc1016.*, CONCAT(mc1001.nombre, ' ', mc1001.apellidop, ' ', mc1001.apellidom) AS usuario FROM mc1016 INNER JOIN mc1001 ON mc1016.idusuario = mc1001.idusuario WHERE idempresa =  $idempresa");

            $array["notificaciones"] = $notificaciones;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function guardarNotificacionEmpresa(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $mensaje = $request->mensaje;
            $idempresa = $request->idempresa;
            $idusuario = $request->idusuario;
            $fechamensaje = $request->fechamensaje;
            
            DB::connection("General")->table("mc1016")->insert(["mensaje" => $mensaje, "idempresa" => $idempresa, "idusuario" => $idusuario, "fechamensaje" => $fechamensaje]);
        }
        
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function guardarFechaLimitePagoEmpresa(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if($valida[0]['error'] === 0) {
            $idempresa = $request->idempresa;
            $fecharestriccion = $request->fecharestriccion;
            DB::connection("General")->table('mc1000')->where("idempresa", $idempresa)->update(["fecharestriccion" => $fecharestriccion]);
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function guardarFechaPeriodoPruebaEmpresa(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if($valida[0]['error'] === 0) {
            $idempresa = $request->idempresa;
            $fechaprueba = $request->fechaprueba;
            DB::connection("General")->table('mc1000')->where("idempresa", $idempresa)->update(["fechaprueba" => $fechaprueba]);
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function cambiarEstatusEmpresa(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if($valida[0]['error'] === 0) {
            $idempresa = $request->idempresa;
            $status = $request->status;
            DB::connection("General")->table('mc1000')->where("idempresa", $idempresa)->update(["statusempresa" => $status]);
            $array["status"] = $status == 1 ? 0 : 1;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getMovimientosEmpresa(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if($valida[0]['error'] === 0) {
            $idempresa = $request->idempresa;
            $movimientos = DB::connection("General")->select("SELECT mc1017.*, CONCAT(mc1001.nombre, ' ', mc1001.apellidop, ' ', mc1001.apellidom) AS usuario FROM mc1017 INNER JOIN mc1001 ON mc1017.idusuario = mc1001.idusuario WHERE idempresa =  $idempresa");

            $array["movimientos"] = $movimientos;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function guardarMovimientoEmpresa(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $idempresa = $request->idempresa;
            $idusuario = $request->idusuario;
            $fecha = $request->fecha;
            $documento = $request->documento;
            $importe = $request->importe;
            $pendiente = $request->pendiente;
            $tipomovimiento = $request->tipomovimiento;
            $rfc = $request->rfc;
            $codigofecha = $request->codigofecha;
            $usuariostorage = $request->usuariostorage;
            $passwordstorage = $request->passwordstorage;

            $servidor = getServidorNextcloud();
            $archivos = $request->file();

            $idmovimiento = DB::connection("General")->table("mc1017")->insertGetId(["idempresa" => $idempresa, "idusuario" => $idusuario, "fecha" => $fecha, "documento" => $documento, "importe" => $importe, "pendiente" => $pendiente, "tipomovimiento" => $tipomovimiento]);
            $array["idmovimiento"] = $idmovimiento;

            //$x=0;
            foreach ($archivos as $key => $file) {
                $archivo = $file->getClientOriginalName();
                /* $array["archivos"][$x] = $archivo;
                $array["archivoskey"][$x] = $key; */

                $mod = substr(strtoupper("EstadoCuenta"), 0, 3);
                /* $string = explode("-", $fecha);
                $codfec = substr($string[0], 2) . $string[1]; */
                $consecutivo = "";
                $codigoarchivo = $rfc . "_" . $codigofecha . "_" . $mod . "_";

                $validacionArchivo = true;
                $numero = 1;
                while($validacionArchivo == true) {

                    if($numero >= 1000) {
                        $consecutivo = "" . $numero;
                    }
                    else if($numero >= 100) {
                        $consecutivo = "0" . $numero;
                    }
                    else if($numero >= 10) {
                        $consecutivo = "00" . $numero;
                    }
                    else {
                        $consecutivo = "000" . $numero;
                    }

                    $codigobusqueda = $codigoarchivo . $consecutivo;
                    $documento = DB::connection("General")->select("SELECT * FROM mc1019 where codigodocumento = '$codigobusqueda'");

                    if(!empty($documento)) {
                        $numero++;
                    }
                    else {
                        $validacionArchivo = false;
                    }
                }

                //$codigoarchivo = $rfc . "_" . $codfec . "_" . $mod . "_" . $numero;
                $codigoarchivocompleto = $codigoarchivo . $consecutivo;
                $array["codigoarchivo"] = $codigoarchivocompleto;

                $resultado = subirMovimientoEmpresaNextcloud($archivo, $file, $rfc, $servidor, $usuariostorage, $passwordstorage, $codigoarchivo, $consecutivo);

                $directorio = $rfc . '/Cuenta/Empresa/EstadoCuenta';
                $type = explode(".", $archivo);
                $target_path = $directorio . '/' . $codigoarchivocompleto . "." . $type[count($type) - 1];
                $link = GetLinkArchivo($target_path, $servidor, $usuariostorage, $passwordstorage);

                DB::connection("General")->table("mc1019")->insertGetId(["idmovimiento" => $idmovimiento, "documento" => $archivo, "codigodocumento" => $codigoarchivocompleto, "download" => $link]);

                //$x++;
            }            
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