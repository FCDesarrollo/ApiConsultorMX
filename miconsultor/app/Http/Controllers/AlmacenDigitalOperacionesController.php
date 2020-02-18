<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


class AlmacenDigitalOperacionesController extends Controller
{
    public function listaAlmacenDigital(Request $request)
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
}
