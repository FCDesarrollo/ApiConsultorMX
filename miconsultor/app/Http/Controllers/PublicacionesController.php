<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PublicacionesController extends Controller
{
    function getPublicaciones(Request $request)
    { 
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $publicaciones = DB::select("SELECT * FROM mc_publicaciones ORDER BY fechaPublicacion DESC");
            for($x=0 ; $x<count($publicaciones) ; $x++) {
                $documentos = DB::select("SELECT * FROM mc_publicaciones_docs WHERE idPublicacion = ?", [$publicaciones[$x]->id]);
                $publicaciones[$x]->documentos = $documentos;
            }
            $array["publicaciones"] = $publicaciones;
        }
                
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function eliminarPublicacion(Request $request)
    {
        $idPublicacion = $request->idPublicacion;
        $tipoPublicacion = $request->tipoPublicacion;
        $fechaEliminacion = $request->fechaEliminacion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            if($tipoPublicacion == 1) {
                DB::table('mc_publicaciones')->where("id", $idPublicacion)->update(["fechaEliminado" => $fechaEliminacion]);
            }
            else {
                DB::table('mc_publicaciones')->where("id", $idPublicacion)->delete();
                DB::table('mc_publicaciones_docs')->where("idPublicacion", $idPublicacion)->delete();
                //falta eliminar los documentos de NextCloud
            }
        }
                
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}