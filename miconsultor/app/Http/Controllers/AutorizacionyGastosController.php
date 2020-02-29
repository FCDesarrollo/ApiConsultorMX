<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class AutorizacionyGastosController extends Controller
{
    public function cargaConceptos(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
           $conceptos = DB::select('select * from mc_conceptos where status = ?', [1]);
           $array["conceptos"] = $conceptos;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function nuevoRequerimiento(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            }else{
                $idsucursal = $request->idsucursal;
                $fecha = $request->fecha;
                $idusuario = $valida[0]['usuario'][0]->idusuario;
                $fechareq = $request->fecharequerimiento;
                $descripcion = $request->descripcion;
                $importe = $request->importe;
                $estado = 1;

                $idconcepto = $request->idconcepto;
                $serie = $request->serie;
                $folio = $request->folio;
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function listaRequerimientos(Request $request)
    {
        $rfc = $request->rfc;
        ConnectDatabaseRFC($rfc);
        
    }
}
