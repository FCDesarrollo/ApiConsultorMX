<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\requerimiento;
use App\requerimiento_bit as Bitacora;
use App\documento as Documento;


class ComprasController extends Controller
{
    
    // GET REQUERIMIENTO NO HISTORIAL
    function getRequerimiento(Request $request){
        $rfc = $request->rfcempresa;
        $idempresa = DB::connection("General")->select("SELECT idempresa FROM mc1000 WHERE rfc ='$rfc'"); 
        // ME TRAE EL ID DE LA EMPRESA 52
        // return $idempresa; 
        if(!empty($idempresa)){
            ConnectDatabase($idempresa[0]->idempresa); 
            $idmenu = $request->idmenu;
            $idsubmenu = $request->idsubmenu;
            $req = DB::select("SELECT * FROM mc_requerimientos ORDER BY fecha DESC");
            for ($i=0; $i < count($req); $i++) { 
                // Concepto del Documento
                $idconcepto = $req[$i]->id_concepto;
                // return $idconcepto;
                $concepto = DB::select("SELECT * FROM mc_conceptos WHERE id = $idconcepto");
                $req[$i]->concepto = $concepto[0]->nombre_concepto;
                // Estado del documento
                $idestado = $req[$i]->estado_documento;
                $estado_documentos = DB::connection("General")->select("SELECT nombre_estado FROM mc1015 WHERE id = $idestado");
                $req[$i]->estado = $estado_documentos[0]->nombre_estado;
            } 
        }else {
            $req = array(

                "datos" => "",

            );      
        }
        return json_encode($req, JSON_UNESCAPED_UNICODE);
    }



// TRAE EL HISTORIAL DE REQUERIMIENTOS
    function DatosReq(Request $request){
        $rfc = $request->rfcempresa;
        $idempresa = DB::connection("General")->select("SELECT idempresa FROM mc1000 WHERE rfc ='$rfc'"); 
        if(!empty($idempresa)){
            ConnectDatabase($idempresa[0]->idempresa); 
            $idmenu = $request->idmenu;
            $idsubmenu = $request->idsubmenu;
            $bit = DB::select("SELECT * FROM mc_requerimientos_bit"); 
            // return $bit;
            for ($i=0; $i < count($bit); $i++) {
                // Estado de la bitacora
                $idestado = $bit[$i]->status;
                $estado_documentos = DB::connection("General")->select("SELECT nombre_estado FROM mc1015 WHERE id = $idestado");
                $bit[$i]->estado = $estado_documentos[0]->nombre_estado;
            }
        }else {
            $bit = array(
                "datos" => "",
            );      
        }
        return json_encode($bit, JSON_UNESCAPED_UNICODE);
    }

    // Requerimentos Documentos
    function ArchivosRequerimientos(Request $request){
        $idempresa = $request->idempresa;
        ConnectDatabase($idempresa);
        $archivos = DB::select("SELECT * FROM mc_requerimientos_doc");
        // return $archivos;
        return json_encode($archivos, JSON_UNESCAPED_UNICODE);
    }    




// DATA STORAGE
    function DatosStorage(Request $request){
        $rfc = $request->rfcempresa;
        $server = DB::connection("General")->select("SELECT servidor_storage FROM mc0000");
        $storage = DB::connection("General")->select("SELECT usuario_storage, password_storage FROM mc1000 WHERE RFC = '$rfc'");
        $storage[0]->server = $server[0]->servidor_storage;
        return json_encode($storage, JSON_UNESCAPED_UNICODE);
    }




// POST REQUERIMIENTOS
    function addRequerimiento(Request $request){

        // $user = Auth::user();
        // return $request->idusuario;

        // descripcion folio concepto serie fecha importe
        $descripcion = $request->descripcion;
        $folio = $request->folio;
        $concepto = $request->concepto;
        $serie = $request->serie;
        $fecha = $request->fecha;
        $importe = $request->importe;
        $idempresa = $request->idempresa;
        $rfc = $request->rfc;
        $idsucursal = $request->idsucursal;


        // Guardamos un nuevo registro en requerimientos
        $requerimiento = new requerimiento();
        $requerimiento->fecha = $fecha;
        $requerimiento->id_usuario = 1;
        $requerimiento->descripcion = $descripcion;
        $requerimiento->importe_estimado = 100;
        $requerimiento->estado_documento = 1;
        $requerimiento->id_concepto = 1;
        $requerimiento->serie = $serie;
        $requerimiento->folio = $folio;
        $requerimiento->id_sucursal = $idsucursal;
        $requerimiento->save(); 
        
        // guardamos un segundo regristro en bitacora
        $bitacora = new Bitacora();
        $bitacora->id_usuario = $requerimiento->id_usuario;
        $bitacora->id_req = $requerimiento->id_req;
        $bitacora->fecha = $fecha;
        $bitacora->descripcion = $descripcion;
        $bitacora->status = 1;
        $bitacora->save();

        // Subir documento a unidad de storage
        $documento = new Documento();
        $documento->id_req = $requerimiento->id_req;
        $documento->documento = 'Documento.' . $request->documento->extension();
        $documento->tipo_doc = 1;
        $documento->download = '<ESTA PENDIENTE>';
        $documento->save();
        // return 'success';
        // $requerimiento = DB::select("SELECT id_requ FROM mc_reqerimientos WHERE rfc='$rfcempresa");
    }






    // ELIMINAR REQ
    public function eliminarRequerimiento(Request $request)
    {       
        ConnectDatabase($request->idempresa);
        $id = $request->idperfil;
        DB::table('mc_requerimientos')->where("id_req", $id)->update(["status"=>"0"]);
        return response($id, 200);
    }
}