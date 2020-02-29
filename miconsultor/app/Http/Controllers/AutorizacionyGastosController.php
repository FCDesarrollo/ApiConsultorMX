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

    public function cargaEstatus(Request $request)
    {
        $valida = verificaUsuario($request->usuario, $request->pwd);
          
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
           $estatus = DB::connection("General")->select('select * from mc1015');
           $array["estatus"] = $estatus;
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
                
                $idreq = 1;
                // $idreq = DB::table('mc_requerimientos')->insertGetId(['id_sucursal' => $idsucursal, 'fecha' => $fecha, 
                //                 'id_usuario' => $idusuario, 'fecha_req' => $fechareq, 'id_departamento' => 0,
                //                 'descripcion' => $descripcion, 'importe_estimado' => $importe, 'estado_documento' => $estado,
                //                 'id_concepto' => $idconcepto, 'serie' => $serie, 'folio' => $folio]);
                if ($idreq !=0) {
                    // DB::insert('insert into mc_requerimientos_bit (id_req, fecha, status) values (?, ?, ?)',
                    //          [$idreq, $fecha, $estado]);
                    $servidor = getServidorNextcloud();
                    if ($servidor == "") {
                        $array["error"] = 10;
                    }else{
                        $idmodulo = 4;
                        $idmenu = $request->idmenu;
                        $idsubmenu = $request->idsubmenu;
                        $validaCarpetas = getExisteCarpeta($idmodulo, $idmenu, $idsubmenu);
                        $array["error"] = $validaCarpetas[0]["error"];
                        if ($validaCarpetas[0]['error'] == 0){

                        }
                    }
                    $array["id"] = $idreq;
                }else{
                    $array["error"] = 4; //no se registro
                }
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
