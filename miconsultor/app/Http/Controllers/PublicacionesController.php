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
        /* $tipoPublicacion = $request->tipoPublicacion; */
        $fechaEliminacion = $request->fechaEliminacion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            DB::table('mc_publicaciones')->where("id", $idPublicacion)->update(["fechaEliminado" => $fechaEliminacion, "status" => 0]);
            /* if($tipoPublicacion == 1) { // borrado logico
                DB::table('mc_publicaciones')->where("id", $idPublicacion)->update(["fechaEliminado" => $fechaEliminacion]);
            }
            else { //borrado fisico
                DB::table('mc_publicaciones')->where("id", $idPublicacion)->delete();
                DB::table('mc_publicaciones_docs')->where("idPublicacion", $idPublicacion)->delete();
                //falta eliminar los documentos de NextCloud
            } */
        }
                
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getCatalogosPublicaciones(Request $request)
    { 
        $idTipoPublicacion = $request->idTipoPublicacion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $catalogos = DB::select("SELECT * FROM mc_publicaciones_catalogos WHERE tipo = ?", [$idTipoPublicacion]);
            $array["catalogos"] = $catalogos;
        }
                
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function eliminarCatalogoPublicacion(Request $request)
    {
        $idCatalogo = $request->idCatalogo;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            DB::table('mc_publicaciones_catalogos')->where("id", $idCatalogo)->delete();
        }
                
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function agregarPublicacion(Request $request)
    { 
        $titulo = $request->titulo;
        $descripcion = $request->descripcion;
        $tipoPublicacion = $request->tipoPublicacion;
        $tipoCatalogo = $request->tipoCatalogo;
        $idUsuario = $request->idUsuario;
        $fechaPublicacion = $request->fechaPublicacion;
        $documentos = $request->documentos;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $idPublicacion = DB::table('mc_publicaciones')->insertGetId(['titulo' => $titulo, 'descripcion' => $descripcion, 'tipoPublicacion' => $tipoPublicacion, 'tipoCatalogo' => $tipoCatalogo, 'idUsuario' => $idUsuario, 'fechaPublicacion' => $fechaPublicacion]);

            $nombreDocumento = "";
            $linkDocumento = "";

            DB::table('mc_publicaciones_docs')->insert(['idPublicacion' => $idPublicacion, 'nombre' => $nombreDocumento, 'link' => $linkDocumento]);
        }
                
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function agregarCatalogoPublicacion(Request $request)
    {
        $nombre = $request->nombre;
        $tipo = $request->tipo;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            DB::table('mc_publicaciones_catalogos')->insert(['nombre' => $nombre, 'tipo' => $tipo]);
        }
                
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}