<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Requerimiento;
use App\requerimiento_bit as Bitacora;
use App\Usuario;

class AutorizacionyGastosController extends Controller
{
    public function nuevoRequerimiento(Request $request)
    {
        $rfc = $request->rfc;
        $valida = verificaUsuario($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $usuario = $valida[0]['usuario'];
            

            $rfc = $request->rfc;
            ConnectDatabaseRFC($rfc);

            $idsucursal = $request->idsucursal;
            $fecha = $request->fecha;
            $idusuario = $usuario[0]->idusuario;
            $descripcion = $request->descripcion;
            $importe = $request->importe;
            $status = 1;
            $idconcepto = $request->idconcepto;
            $serie = $request->serie;
            $folio = $request->folio;
            

            $requerimiento = new requerimiento();
            $requerimiento->fecha = $fecha;
            $requerimiento->id_usuario = $idusuario;
            $requerimiento->descripcion = $descripcion;
            $requerimiento->importe_estimado = $importe;
            $requerimiento->estado_documento = $status;
            $requerimiento->id_concepto = $idconcepto;
            $requerimiento->serie = $serie;
            $requerimiento->folio = $folio;
            $requerimiento->id_sucursal = $idsucursal;
            $requerimiento->save();

            $bitacora = new Bitacora();
            $bitacora->id_usuario = $requerimiento->id_usuario;
            $bitacora->id_req = $requerimiento->id_req;
            $bitacora->fecha = $fecha;
            $bitacora->descripcion = $descripcion;
            $bitacora->estatus = $requerimiento->estado_documento;
            $bitacora->save();



        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function listaRequerimientos(Request $request)
    {
        $rfc = $request->rfc;
        ConnectDatabaseRFC($rfc);
        
    }
}
