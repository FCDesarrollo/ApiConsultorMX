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
            $tabla = $request->tabla;
            if($tabla == 1) {
                $movimientos = DB::connection("General")->select("SELECT mc1017.*, CONCAT(mc1001.nombre, ' ', mc1001.apellidop, ' ', mc1001.apellidom) AS usuario, (SELECT SUM(importe) FROM mc1018 WHERE iddoccargo = mc1017.idmovimiento) AS abonos
                FROM mc1017 INNER JOIN mc1001 ON mc1017.idusuario = mc1001.idusuario WHERE idempresa = $idempresa ORDER BY mc1017.fecha ASC, mc1017.idmovimiento ASC");
            }
            else {
                $tipomovimientos = $request->tipomovimientos;
                if($tipomovimientos == 1) {
                    $movimientos = DB::connection("General")->select("SELECT mc1017.*, CONCAT(mc1001.nombre, ' ', mc1001.apellidop, ' ', mc1001.apellidom) AS usuario, (SELECT SUM(importe) FROM mc1018 WHERE iddoc = mc1017.idmovimiento) AS abonos FROM mc1017 INNER JOIN mc1001 ON mc1017.idusuario = mc1001.idusuario WHERE idempresa = $idempresa AND tipomovimiento = 2 AND pendiente <> 0 ORDER BY mc1017.fecha DESC, mc1017.idmovimiento DESC");
                }
                else {
                    $movimientos = DB::connection("General")->select("SELECT mc1017.*, CONCAT(mc1001.nombre, ' ', mc1001.apellidop, ' ', mc1001.apellidom) AS usuario, (SELECT SUM(importe) FROM mc1018 WHERE iddoccargo = mc1017.idmovimiento) AS abonos
                    FROM mc1017 INNER JOIN mc1001 ON mc1017.idusuario = mc1001.idusuario WHERE idempresa = $idempresa AND tipomovimiento = 1 AND pendiente <> 0 ORDER BY mc1017.fecha DESC, mc1017.idmovimiento DESC");
                }
            }

            $array["movimientos"] = $movimientos;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getMovimientoEmpresa(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if($valida[0]['error'] === 0) {
            $idmovimiento = $request->idmovimiento;
            $movimiento = DB::connection("General")->select("SELECT mc1017.*, CONCAT(mc1001.nombre, ' ', mc1001.apellidop, ' ', mc1001.apellidom) AS usuario FROM mc1017 INNER JOIN mc1001 ON mc1017.idusuario = mc1001.idusuario WHERE idmovimiento =  $idmovimiento");
            $abonos = DB::connection("General")->select("SELECT mc1018.*, mc1017.documento FROM mc1018 INNER JOIN mc1017 ON mc1018.iddoc = mc1017.idmovimiento WHERE iddoccargo = $idmovimiento ORDER BY mc1018.fecha DESC, mc1018.iddocabono DESC");
            $cargos = DB::connection("General")->select("SELECT mc1017.*, mc1018.iddocabono, mc1018.importe AS abono FROM mc1017 INNER JOIN mc1018 ON mc1017.idmovimiento = mc1018.iddoccargo WHERE mc1018.iddoc = $idmovimiento ORDER BY mc1017.fecha DESC, mc1017.idmovimiento DESC");
            $archivos = DB::connection("General")->select("SELECT mc1019.* FROM mc1019 INNER JOIN mc1017 ON mc1019.idmovimiento = mc1017.idmovimiento WHERE mc1019.idmovimiento = $idmovimiento UNION SELECT mc1019.* FROM mc1019 LEFT JOIN mc1018 ON mc1019.idmovimiento = mc1018.iddoc WHERE mc1018.iddoccargo = $idmovimiento");

            $array["movimiento"] = $movimiento;
            $array["abonos"] = $abonos;
            $array["cargos"] = $cargos;
            $array["archivos"] = $archivos;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getAbonosPorMovimientoEmpresa(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if($valida[0]['error'] === 0) {
            $idmovimiento = $request->idmovimiento;
            $abonos = DB::connection("General")->select("SELECT * FROM mc1018 WHERE iddoccargo = $idmovimiento");

            $array["abonos"] = $abonos;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function editarMovimientoEmpresa(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if($valida[0]['error'] === 0) {
            $idmovimiento = $request->idmovimiento;
            $documento = $request->documento;
            $pendiente = $request->pendiente;
            $tipomovimiento = $request->tipomovimiento;
            $fecha = $request->fecha;
            $asociados = $request->asociados;
            
            if($asociados != 0) {
                $idsabonos = explode(",", $request->idsabonos);
                $abonos = explode(",", $request->abonos);
                $pendientes = explode(",", $request->pendientes);
                /* $idsabonos = $request->idsabonos;
                $abonos = $request->abonos;
                $pendientes = $request->pendientes; */
                for($x=0 ; $x<count($abonos) ; $x++) {
                    if($tipomovimiento == 1) {
                        DB::connection("General")->table("mc1018")->insert(["iddoccargo" => $idmovimiento,"iddoc" => $idsabonos[$x], "importe" => $abonos[$x], "fecha" => $fecha]);
                    }
                    else {
                        DB::connection("General")->table("mc1018")->insert(["iddoccargo" => $idsabonos[$x],"iddoc" => $idmovimiento, "importe" => $abonos[$x], "fecha" => $fecha]);
                    }
                    DB::connection("General")->table('mc1017')->where("idmovimiento", $idsabonos[$x])->update(["pendiente" => $pendientes[$x]]);
                }
            }

            DB::connection("General")->table('mc1017')->where("idmovimiento", $idmovimiento)->update(["documento" => $documento, "pendiente" => $pendiente]);

            $rfc = $request->rfc;
            $codigofecha = $request->codigofecha;
            $usuariostorage = $request->usuariostorage;
            $passwordstorage = $request->passwordstorage;
            $servidor = getServidorNextcloud();
            $archivos = $request->file();

            foreach ($archivos as $key => $file) {
                $archivo = $file->getClientOriginalName();

                $mod = substr(strtoupper("EstadoCuenta"), 0, 3);
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

                $codigoarchivocompleto = $codigoarchivo . $consecutivo;
                $array["codigoarchivo"] = $codigoarchivocompleto;

                $resultado = subirMovimientoEmpresaNextcloud($archivo, $file, $rfc, $servidor, $usuariostorage, $passwordstorage, $codigoarchivo, $consecutivo);

                $directorio = $rfc . '/Cuenta/Empresa/EstadoCuenta';
                $type = explode(".", $archivo);
                $target_path = $directorio . '/' . $codigoarchivocompleto . "." . $type[count($type) - 1];
                $link = GetLinkArchivo($target_path, $servidor, $usuariostorage, $passwordstorage);

                DB::connection("General")->table("mc1019")->insertGetId(["idmovimiento" => $idmovimiento, "documento" => $archivo, "codigodocumento" => $codigoarchivocompleto, "download" => $link]);

            }
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
            $idsabonos = explode(",", $request->idsabonos);
            $abonos = explode(",", $request->abonos);
            $pendientes = explode(",", $request->pendientes);
            /* $array["idsabonos"] = $idsabonos;
            $array["abonos"] = $abonos;
            $array["pendientes"] = $pendientes;
            $array["numeroAbonos"] = count($abonos); */

            $servidor = getServidorNextcloud();
            $archivos = $request->file();

            $idmovimiento = DB::connection("General")->table("mc1017")->insertGetId(["idempresa" => $idempresa, "idusuario" => $idusuario, "fecha" => $fecha, "documento" => $documento, "importe" => $importe, "pendiente" => $pendiente, "tipomovimiento" => $tipomovimiento]);
            $array["idmovimiento"] = $idmovimiento;

            if($tipomovimiento == 2) {
                for($x=0 ; $x<count($abonos) ; $x++) {
                    DB::connection("General")->table("mc1018")->insert(["iddoccargo" => $idsabonos[$x],"iddoc" => $idmovimiento, "importe" => $abonos[$x], "fecha" => $fecha]);
                    DB::connection("General")->table('mc1017')->where("idmovimiento", $idsabonos[$x])->update(["pendiente" => $pendientes[$x]]);
                }
            }

            foreach ($archivos as $key => $file) {
                $archivo = $file->getClientOriginalName();

                $mod = substr(strtoupper("EstadoCuenta"), 0, 3);
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

                $codigoarchivocompleto = $codigoarchivo . $consecutivo;
                $array["codigoarchivo"] = $codigoarchivocompleto;

                $resultado = subirMovimientoEmpresaNextcloud($archivo, $file, $rfc, $servidor, $usuariostorage, $passwordstorage, $codigoarchivo, $consecutivo);

                $directorio = $rfc . '/Cuenta/Empresa/EstadoCuenta';
                $type = explode(".", $archivo);
                $target_path = $directorio . '/' . $codigoarchivocompleto . "." . $type[count($type) - 1];
                $link = GetLinkArchivo($target_path, $servidor, $usuariostorage, $passwordstorage);

                DB::connection("General")->table("mc1019")->insertGetId(["idmovimiento" => $idmovimiento, "documento" => $archivo, "codigodocumento" => $codigoarchivocompleto, "download" => $link]);

            }     
        }
        
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getArchivosEmpresa(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if($valida[0]['error'] === 0) {
            $idmovimiento = $request->idmovimiento;
            /* $archivos = DB::connection("General")->select("SELECT mc1019.*, mc1017.fecha, mc1017.documento as nombredocumento, mc1017.tipomovimiento FROM mc1019 LEFT JOIN mc1017 ON mc1019.idmovimiento = mc1017.idmovimiento WHERE mc1019.idmovimiento = $idmovimiento"); */
            $archivos = DB::connection("General")->select("SELECT mc1019.*, mc1017.fecha, mc1017.documento AS nombredocumento, mc1017.tipomovimiento FROM mc1017 LEFT JOIN mc1018 ON mc1018.iddoc = mc1017.idmovimiento LEFT JOIN mc1019 ON mc1019.idmovimiento = mc1017.idmovimiento WHERE mc1018.iddoccargo = $idmovimiento OR mc1019.idmovimiento = $idmovimiento");

            $array["archivos"] = $archivos;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function eliminarMovimientoEmpresa(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if($valida[0]['error'] === 0) {
            $idmovimiento = $request->idmovimiento;
            $abonosdoc = DB::connection("General")->select("SELECT * FROM mc1018 WHERE iddoc = $idmovimiento");

            for($x=0 ; $x<count($abonosdoc) ; $x++) {
                $iddocabono = $abonosdoc[$x]->iddocabono;
                $iddoccargo = $abonosdoc[$x]->iddoccargo;
                $importeabono = $abonosdoc[$x]->importe;

                $cargo = DB::connection("General")->select("SELECT * FROM mc1017 WHERE idmovimiento = $iddoccargo");
                $pendientecargo = $cargo[0]->pendiente + $importeabono;
                DB::connection("General")->table('mc1017')->where("idmovimiento", $iddoccargo)->update(["pendiente" => $pendientecargo]);
                DB::connection("General")->table("mc1018")->where("iddocabono", $iddocabono)->delete();
            }

            $abonos = DB::connection("General")->select("SELECT * FROM mc1018 WHERE iddoccargo = $idmovimiento");
            
            if(count($abonos) === 0) {
                $rfc = $request->rfc;
                $usuariostorage = $request->usuariostorage;
                $passwordstorage = $request->passwordstorage;
                $ruta = $rfc . '/Cuenta/Empresa/EstadoCuenta';
                $servidor = getServidorNextcloud();
                $archivos = DB::connection("General")->select("SELECT * FROM mc1019 WHERE idmovimiento = $idmovimiento");
                DB::connection("General")->table("mc1017")->where("idmovimiento", $idmovimiento)->delete();
                DB::connection("General")->table("mc1019")->where("idmovimiento", $idmovimiento)->delete();

                for ($i = 0; $i < count($archivos); $i++) {
                    $type = explode(".", $archivos[$i]->documento);
                    $extencionarchivo = $type[count($type) - 1];
                    $nombrearchivo = $ruta . "/" . $archivos[$i]->codigodocumento . "." . $extencionarchivo;
                    $resp = eliminaArchivoNextcloud($servidor, $usuariostorage, $passwordstorage, $nombrearchivo);
                }
            }
            else {
                $array["error"] = 55;
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function eliminarAbonoMovimientoEmpresa(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if($valida[0]['error'] === 0) {
            $idabono = $request->idabono;
            $tipomovimiento = $request->tipomovimiento;
            $abono = DB::connection("General")->select("SELECT * FROM mc1018 WHERE iddocabono = $idabono");
            $iddoc = $abono[0]->iddoc;
            $iddoccargo = $abono[0]->iddoccargo;
            $importeabono = $abono[0]->importe;
            /* $array["idmovimiento"] = $iddoc; */
            $abonosasociados = DB::connection("General")->select("SELECT * FROM mc1018 WHERE iddoc = $iddoc");
            /* $array["abonosasociados"] = $abonosasociados; */
            $array["numeroabonosasociados"] = count($abonosasociados);
            $array["tipomovimiento"] = $tipomovimiento;
            if(count($abonosasociados) === 1) {
                $rfc = $request->rfc;
                $usuariostorage = $request->usuariostorage;
                $passwordstorage = $request->passwordstorage;
                $ruta = $rfc . '/Cuenta/Empresa/EstadoCuenta';
                $servidor = getServidorNextcloud();
                $archivos = DB::connection("General")->select("SELECT * FROM mc1019 WHERE idmovimiento = $iddoc");
                DB::connection("General")->table("mc1017")->where("idmovimiento", $iddoc)->delete();
                DB::connection("General")->table("mc1019")->where("idmovimiento", $iddoc)->delete();

                for ($i = 0; $i < count($archivos); $i++) {
                    $type = explode(".", $archivos[$i]->documento);
                    $extencionarchivo = $type[count($type) - 1];
                    $nombrearchivo = $ruta . "/" . $archivos[$i]->codigodocumento . "." . $extencionarchivo;
                    $resp = eliminaArchivoNextcloud($servidor, $usuariostorage, $passwordstorage, $nombrearchivo);
                }
            }
            else {
                $cargo = DB::connection("General")->select("SELECT * FROM mc1017 WHERE idmovimiento = $iddoc");
                $pendiente = $cargo[0]->pendiente + $importeabono;
                DB::connection("General")->table('mc1017')->where("idmovimiento", $iddoc)->update(["pendiente" => $pendiente]);
            }

            DB::connection("General")->table("mc1018")->where("iddocabono", $idabono)->delete();
            $movimiento = DB::connection("General")->select("SELECT * FROM mc1017 WHERE idmovimiento = $iddoccargo");
            $pendiente = $movimiento[0]->pendiente + $importeabono;
            DB::connection("General")->table('mc1017')->where("idmovimiento", $iddoccargo)->update(["pendiente" => $pendiente]);
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function eliminarArchivoMovimientoEmpresa(Request $request)
    {
        $valida = verificarProveedor($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if($valida[0]['error'] === 0) {
            $iddocumento = $request->iddocumento;
            $rfc = $request->rfc;
            $usuariostorage = $request->usuariostorage;
            $passwordstorage = $request->passwordstorage;
            $ruta = $rfc . '/Cuenta/Empresa/EstadoCuenta';
            $servidor = getServidorNextcloud();
            $archivo = DB::connection("General")->select("SELECT * FROM mc1019 WHERE iddocumento = $iddocumento");
            DB::connection("General")->table("mc1019")->where("iddocumento", $iddocumento)->delete();

            $type = explode(".", $archivo[0]->documento);
            $extencionarchivo = $type[count($type) - 1];
            $nombrearchivo = $ruta . "/" . $archivo[0]->codigodocumento . "." . $extencionarchivo;
            $resp = eliminaArchivoNextcloud($servidor, $usuariostorage, $passwordstorage, $nombrearchivo);
            $array["type"][0] = $type;
            $array["extencionarchivo"][0] = $extencionarchivo;
            $array["nombrearchivo"][0] = $nombrearchivo;
            $array["resp"][0] = $resp;

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