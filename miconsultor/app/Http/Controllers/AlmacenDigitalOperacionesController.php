<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


class AlmacenDigitalOperacionesController extends Controller
{
    public function listaAlmacenDigital(Request $request)
    {
        
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $rfc = $request->rfc;
            $usuario = $valida[0]['usuario'];
            $idusuario = $usuario[0]->idusuario;
 
            $idsubmenu = $request->idsubmenu;

            $reg = DB::select("SELECT * FROM mc_almdigital WHERE idmodulo = $idsubmenu ORDER BY fechadocto DESC");

            for ($i = 0; $i < count($reg); $i++) {

                $idalm = $reg[$i]->id;

                $procesados = DB::select("SELECT id FROM mc_almdigital_det WHERE idalmdigital = $idalm And estatus = 1");

                $reg[$i]->procesados = count($procesados);

                $idusuario = $reg[$i]->idusuario;

                $datosuser = DB::connection("General")->select("SELECT nombre FROM mc1001 WHERE idusuario = $idusuario");

                $reg[$i]->usuario = $datosuser[0]->nombre;

                $idsucursal = $reg[$i]->idsucursal;

                $suc = DB::select("SELECT sucursal FROM mc_catsucursales WHERE idsucursal = $idsucursal");

                $reg[$i]->sucursal = $suc[0]->sucursal;
            }

            $array["registros"] = $reg;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function archivosAlmacenDigital(Request $request)
    {
        $valida = verificaUsuario($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $rfc = $request->rfc;
            $usuario = $valida[0]['usuario'];
            $idusuario = $usuario[0]->idusuario;
            $valida2 = VerificaEmpresa($rfc, $idusuario);
            $array["error"] = $valida2[0]["error"];
            
            if ($valida2[0]['error'] == 0){
                ConnectDatabaseRFC($rfc);
                $idalmacen = $request->idalmacendigital;
                $archivos = DB::select("SELECT * FROM mc_almdigital_det WHERE idalmdigital = $idalmacen");

                for ($i = 0; $i < count($archivos); $i++) {
                    if ($archivos[$i]->estatus == 1) {
                        //$idagente = ($archivos[$i]->idagente != null ? $archivos[$i]->idagente : 0);
                        $idagente = $archivos[$i]->idagente;
                        $datosagente = DB::connection("General")->select("SELECT nombre FROM mc1001 WHERE idusuario = $idagente");
                        $archivos[$i]->agente = $datosagente[0]->nombre;
                        if ($archivos[$i]->conceptoadw == null) {
                            $idalmdigitaldet = $archivos[$i]->id;
                            $det = DB::select("SELECT * FROM mc_almdigital_doc WHERE idalmdigitaldet = $idalmdigitaldet");
                            $concefolser = "";
                            for ($j = 0; $j < count($det); $j++) {
                                $concefolser = $concefolser . $det[$j]->conceptoadw . " " . $det[$j]->folioadw . "-" . $det[$j]->serieadw . ", ";
                            }
                            $archivos[$i]->conceptoadw = $concefolser;
                        }
                    } else {
                        $archivos[$i]->agente = "¡No ha sido procesado!";
                    }
                }
                $array["archivos"] = $archivos;
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function cargaArchivosAlmacenDigital(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];

            if ($permiso == 1) {
                $array["error"] = 4;
            }else{
                $usuario = $valida[0]['usuario'];
                $idusuario = $usuario[0]->idusuario;
                $servidor = getServidorNextcloud();
                if ($servidor == "") {
                    $array["error"] = 10;
                }else{
                    $idmodulo = $request->idmodulo;
                    $idmenu = $request->idmenu;
                    $idsubmenu = $request->idsubmenu;
                    $validaCarpetas = getExisteCarpeta($idmodulo, $idmenu, $idsubmenu);
                    $array["error"] = $validaCarpetas[0]["error"];
                    
                    if ($validaCarpetas[0]['error'] == 0){
                        $carpetamodulo = $validaCarpetas[0]['carpetamodulo'];
                        $carpetamenu = $validaCarpetas[0]['carpetamenu'];
                        $carpetasubmenu = $validaCarpetas[0]['carpetasubmenu'];

                        $fechadocto = $request->fechadocto;
                        $archivos = $request->file();
                        $numarchivos = count($archivos);

                        $rfc = $request->rfc;
                        $u_storage = $request->usuario_storage;
                        $p_storage = $request->password_storage;

                        $consecutivo = getNumeroConsecutivo($fechadocto, $idsubmenu);
                        $countreg = $consecutivo;

                        $cont = 0;

                        foreach ($archivos as $key) {
                            if (strlen($countreg) == 1) {
                                $consecutivo = "000" . $countreg;
                            } elseif (strlen($countreg) == 2) {
                                $consecutivo = "00" . $countreg;
                            } elseif (strlen($countreg) == 3) {
                                $consecutivo = "0" . $countreg;
                            } else {
                                $consecutivo = $countreg;
                            }

                            $resultado = subirArchivoNextcloud($key->getClientOriginalName(), $key, $rfc, $servidor, $u_storage, $p_storage,$carpetamodulo, $carpetamenu, $carpetasubmenu, $fechadocto, $consecutivo);
                            if ($resultado["archivo"]["error"] == 0) {
                                $target_path = $resultado["archivo"]["target"];
                                $link = GetLinkArchivo($target_path, $servidor, $u_storage, $p_storage);

                                if ($link != "") {
                                    $array2["archivos"][$cont] =  array(
                                        "archivo" => $key->getClientOriginalName(),
                                        "codigo" => $resultado["archivo"]["codigo"],
                                        "link" => $link,
                                        "status" => 0,
                                        "detalle" => "¡Cargado Correctamente!"
                                    );
                                    $countreg = $countreg + 1;
                                } else {
                                    $array2["archivos"][$cont] =  array(
                                        "archivo" => $key->getClientOriginalName(),
                                        "codigo" => $resultado["archivo"]["codigo"],
                                        "link" => $link,
                                        "status" => 2,
                                        "detalle" => "¡Link no generado, error al subir!"
                                    );
                                }
                            } else {
                                $array2["archivos"][$cont] =  array(
                                    "archivo" => $key->getClientOriginalName(),
                                    "codigo" => "",
                                    "link" => "",
                                    "status" => 1,
                                    "detalle" => "¡No se pudo subir el archivo!"
                                );
                            }
                            $cont = $cont + 1;
                        }

                        $sucursal = $request->sucursal;
                        $observaciones = $request->observaciones;

                        $ruta = $rfc . '/'. $carpetamodulo . '/' . $carpetamenu . '/'. $carpetasubmenu;
                        
                        $LetrasCarpeta = $carpetasubmenu;
                        $LetrasCarpeta = substr(strtoupper($LetrasCarpeta), 0, 3);

                        $string = explode("-", $fechadocto);
                        $contador = 0;
                        $now = date('Y-m-d h:i:s A');
                        //VERIFICA SI NO EXISTE EL ARCHIVO
                        $ArchivosV = getExistenArchivos($array2["archivos"], $fechadocto, $idsubmenu, $ruta, $servidor, $u_storage, $p_storage);
                        //REGISTRAR EN BASE DE DATOS LOS ARCHIVOS CARGADOS CORRECTAMENTE    
                        $suc = DB::select("SELECT * FROM mc_catsucursales WHERE sucursal = '$sucursal'");
                        if (!empty($suc)) {
                            $codigoalm = substr($string[0], 2) . $string[1] . $string[2] . $idusuario . $LetrasCarpeta . $sucursal;

                            $reg = DB::select("SELECT * FROM mc_almdigital WHERE codigoalm = '$codigoalm'");

                            $n = 0;
                            if (empty($reg)) {
                                $idalm = DB::table('mc_almdigital')->insertGetId(['fechadecarga' => $now, 'fechadocto' => $fechadocto, 'codigoalm' => $codigoalm, 'idusuario' => $idusuario, 'idmodulo' => $idsubmenu, 'idsucursal' => $suc[0]->idsucursal, 'observaciones' => $observaciones]);
                                while (isset($ArchivosV["archivos"][$contador])) {
                                    $nomDoc = $ArchivosV["archivos"][$contador]["archivo"];
                                    $codigodocumento = $ArchivosV["archivos"][$contador]["codigo"];
                                    $link = $ArchivosV["archivos"][$contador]["link"];
                                    if ($ArchivosV["archivos"][$contador]["status"] == 0) {

                                        $ArchivosV["archivos"][$contador]["idarchivo"] = DB::table('mc_almdigital_det')->insertGetId(['idalmdigital' => $idalm, 'idsucursal' => $suc[0]->idsucursal, 'documento' => $nomDoc, 'codigodocumento' => $codigodocumento, 'download' => $link]);
                                        $ArchivosV["archivos"][$contador]["idalmacen"] = $idalm;
                                        $n = $n + 1;
                                    }
                                    $contador++;
                                }
                                if ($n > 0) {
                                    DB::table('mc_almdigital')->where("id", $idalm)->update(['totalregistros' => $numarchivos, 'totalcargados' => $n]);
                                } else {
                                    DB::table('mc_almdigital')->where("id", $idalm)->delete();
                                }
                            } else {
                                $cont = 0;
                                while (isset($ArchivosV["archivos"][$contador])) {
                                    $nomDoc = $ArchivosV["archivos"][$contador]["archivo"];
                                    $codigodocumento = $ArchivosV["archivos"][$contador]["codigo"];
                                    $link = $ArchivosV["archivos"][$contador]["link"];
                                    if ($ArchivosV["archivos"][$contador]["status"] == 0) {
                                        $ArchivosV["archivos"][$contador]["idarchivo"] = DB::table('mc_almdigital_det')->insertGetId(['idalmdigital' => $reg[0]->id, 'idsucursal' => $reg[0]->idsucursal, 'documento' => $nomDoc, 'codigodocumento' => $codigodocumento, 'download' => $link]);
                                        $cont = $cont + 1;
                                        $ArchivosV["archivos"][$contador]["idalmacen"] = $reg[0]->id;
                                    }
                                    $contador++;
                                }
                                if ($observaciones == "") {
                                    $observaciones = $reg[0]->observaciones;
                                }
                                $idalm = $reg[0]->id;
                                $totalcargados = DB::select("SELECT COUNT(id) As tc FROM mc_almdigital_det WHERE idalmdigital = $idalm");
                                $totalregistros = $reg[0]->totalregistros + $cont;
                                DB::table('mc_almdigital')->where("id", $idalm)->update(['totalregistros' => $totalregistros, 'totalcargados' => $totalcargados[0]->tc, 'observaciones' => $observaciones]);
                            }

                            $array["archivos"] = $ArchivosV["archivos"];
                        }else {
                            $array["error"] = 21;
                        }
                    }
                }
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
