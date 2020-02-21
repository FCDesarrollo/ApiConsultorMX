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
                        $sucursal = $request->sucursal;
                        $suc = DB::select("SELECT * FROM mc_catsucursales WHERE sucursal = '$sucursal'");
                        if (!empty($suc)) {
                            $idsucursal = $suc[0]->idsucursal;
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

                           
                            $observaciones = $request->observaciones;
                            $LetrasCarpeta = $carpetasubmenu;
                            $LetrasCarpeta = substr(strtoupper($LetrasCarpeta), 0, 3);
                            $string = explode("-", $fechadocto);
                            $contador = 0;
                            $now = date('Y-m-d h:i:s A');
                            $codigoalm = substr($string[0], 2) . $string[1] . $string[2] . $idusuario . $LetrasCarpeta . $sucursal;

                            $reg = DB::select("SELECT * FROM mc_almdigital WHERE codigoalm = '$codigoalm'");
                            if (empty($reg)) {
                                $existe = 0;
                                $idalmacen = DB::table('mc_almdigital')->insertGetId(['fechadecarga' => $now, 
                                        'fechadocto' => $fechadocto, 'codigoalm' => $codigoalm, 
                                        'idusuario' => $idusuario, 'idmodulo' => $idsubmenu, 
                                        'idsucursal' => $idsucursal, 'observaciones' => $observaciones]);
                            }else{
                                $idalmacen = $reg[0]->id;
                                if ($observaciones == "") {
                                    $observaciones = $reg[0]->observaciones;
                                }
                                $existe = 1;
                            }

                            $cont = 0;
                            $n = 0;
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

                                $archivo = $key->getClientOriginalName();

                                $mod = substr(strtoupper($carpetasubmenu), 0, 3);
        
                                $string = explode("-", $fechadocto);
                                $codfec = substr($string[0], 2) . $string[1];
                                $codigoarchivo = $rfc . "_" . $codfec . "_" . $mod . "_";

                                $existe = DB::select("SELECT det.* FROM mc_almdigital_det AS det 
                                        INNER JOIN mc_almdigital AS a ON det.idalmdigital = a.id 
                                          WHERE documento = '$archivo' AND a.fechadocto = '$fechadocto' AND a.idmodulo = $idsubmenu");
                                if (empty($existe)) {
                                    $resultado = subirArchivoNextcloud($archivo, $key, $rfc, $servidor, $u_storage, $p_storage,$carpetamodulo, $carpetamenu, $carpetasubmenu, $codigoarchivo, $consecutivo);
                                    if ($resultado["archivo"]["error"] == 0) {
                                        $codigodocumento = $codigoarchivo . $consecutivo;
                                        $type = explode(".", $archivo);
                                        $directorio = $rfc . '/'. $carpetamodulo .'/' . $carpetamenu . '/' . $carpetasubmenu;
                                        $target_path = $directorio . '/' . $codigodocumento . "." . $type[count($type) - 1];   
                                        $link = GetLinkArchivo($target_path, $servidor, $u_storage, $p_storage);
                                        
                                        $codigodocumento = $codigoarchivo . $consecutivo;
                                        $idarchivo= 0;
                                       
                                        if ($link != "") {
                                            $idarchivo = DB::table('mc_almdigital_det')->insertGetId(['idalmdigital' => $idalmacen,
                                             'idsucursal' => $idsucursal, 'documento' => $archivo, 'codigodocumento' => $codigodocumento, 'download' => $link]);
                                            $n= $n +1;
                                        }
                                        
                                        $array2["archivos"][$cont] =  array(
                                            "archivo" => $key->getClientOriginalName(),
                                            "codigo" => $resultado["archivo"]["codigo"],
                                            "link" => $link,
                                            "status" => ($link != "" ? 0 : 2),
                                            "detalle" => ($link != "" ? "¡Cargado Correctamente!" : "¡Link no generado, error al subir!"),
                                            "idarchivo" => $idarchivo,
                                            "idalmacen" => $idalmacen 
                                        );
                                        $countreg = $countreg + ($link != "" ? 1 : 0);
                                    }else{
                                        $array2["archivos"][$cont] =  array(
                                            "archivo" => $key->getClientOriginalName(),
                                            "codigo" => "",
                                            "link" => "",
                                            "status" => 1,
                                            "detalle" => "¡No se pudo subir el archivo!"
                                        );
                                    }
                                }else{
                                    $array2["archivos"][$cont] =  array(
                                        "archivo" => $archivo,
                                        "codigo" => $codigoarchivo . $consecutivo,
                                        "link" => "",
                                        "status" => 4,
                                        "detalle" => "¡Ya existe!"
                                    );
                                    $countreg = $countreg + 1;
                                }
                                $cont = $cont + 1;
                            }
                            
                            if ($n > 0) {
                               if ($existe = 0) {
                                    DB::table('mc_almdigital')->where("id", $idalmacen)->update(['totalregistros' => $numarchivos, 'totalcargados' => $n]);
                               }else{
                                    $totalcargados = DB::select("SELECT COUNT(id) As tc FROM mc_almdigital_det WHERE idalmdigital = $idalmacen");
                                    $totalregistros = $reg[0]->totalregistros + $n;
                                    DB::table('mc_almdigital')->where("id", $idalmacen)->update(['totalregistros' => $totalregistros, 'totalcargados' => $totalcargados[0]->tc, 'observaciones' => $observaciones]);
                               }
                            }else{
                                if ($existe = 0) {
                                    DB::table('mc_almdigital')->where("id", $idalmacen)->delete();
                                }
                            }
                            $array["archivos"] = $array2["archivos"];
                        }else {
                            $array["error"] = 21;
                        }
                    }
                }
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function eliminaArchivosDigital(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso < 3) {
                $array["error"] = 4;
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
                    
                    $rfc= $request->rfc;
                    $datos = $request->archivos;

                    $servidor = getServidorNextcloud();
                    $u_storage = $request->usuario_storage;
                    $p_storage = $request->password_storage;

                    $ruta = $rfc . '/'. $carpetamodulo . '/' . $carpetamenu . '/'. $carpetasubmenu;

                    for ($i = 0; $i < count($datos); $i++) {
                        $idarchivo = $datos[$i]["idarchivo"];
    
                        $archivo = DB::select("SELECT idalmdigital, codigodocumento, documento, estatus FROM mc_almdigital_det WHERE id = $idarchivo");
    
                        $idalmacen = $archivo[0]->idalmdigital;
                        $type = explode(".", $archivo[0]->documento);
                        $nombrearchivo = $ruta . "/" . $archivo[0]->codigodocumento . "." . $type[1];
    
                        if ($archivo[0]->estatus == 0) {
                            $resp = eliminaArchivoNextcloud($servidor, $u_storage, $p_storage, $nombrearchivo);

                            if (empty($resp)) {
                                $array["idalmacen"] = $idalmacen;
    
                                DB::table('mc_almdigital_det')->where("id", $idarchivo)->delete();
                                $totalr = DB::select("SELECT totalregistros FROM mc_almdigital WHERE id = $idalmacen");
                                $totalc = DB::select("SELECT count(id) as tc FROM mc_almdigital_det WHERE idalmdigital = $idalmacen");
                                if ($totalc[0]->tc > 0) {
                                    $totalregistros = $totalr[0]->totalregistros - 1;
                                    DB::table('mc_almdigital')->where("id", $idalmacen)->update(['totalregistros' => $totalregistros, 'totalcargados' => $totalc[0]->tc]);
                                } else {
                                    DB::table('mc_almdigital')->where("id", $idalmacen)->delete();
                                    $array["idalmacen"] = 0;
                                }
    
                                $array["archivos"][$i]["status"] = 0;
                                $array["archivos"][$i]["detalle"] = "¡Archivo Eliminado Correctamente!";
                                $array["archivos"][$i]["archivo"] = $archivo[0]->documento;
                            } else {
                                $array["archivos"][$i]["status"] = 1;
                                $array["archivos"][$i]["detalle"] = "¡No se pudo eliminar el archivo!";
                                $array["archivos"][$i]["archivo"] = $archivo[0]->documento;
                                $array["archivos"][$i]["curlError"] = $resp;
                            }
                        } else {
                            $array["archivos"][$i]["status"] = 2;
                            $array["archivos"][$i]["detalle"] = "¡No se puede eliminar un archivo que ya ha sido procesado!";
                            $array["archivos"][$i]["archivo"] = $archivo[0]->documento;
                        }
                    }
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
