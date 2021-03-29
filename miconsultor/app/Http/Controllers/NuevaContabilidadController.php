<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class NuevaContabilidadController extends Controller {

    function traerTiposDocumentosNuevaContabilidad() {
        $tiposDocumentos = DB::connection("NuevaContabilidad")->select("SELECT * FROM tipos_documentos");
        $array["tiposDocumentos"] = $tiposDocumentos;
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function traerTemasDocumentosNuevaContabilidad() {
        $temasDocumentos = DB::connection("NuevaContabilidad")->select("SELECT * FROM temas_documentos");
        $array["temasDocumentos"] = $temasDocumentos;
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function traerDatosNuevaContabilidad() {
        $soluciones = DB::connection("NuevaContabilidad")->select("SELECT * FROM soluciones");
        $array["soluciones"] = $soluciones;
        $modulos = DB::connection("NuevaContabilidad")->select("SELECT * FROM modulos");
        $array["modulos"] = $modulos;
        $modulos = DB::connection("NuevaContabilidad")->select("SELECT * FROM estados");
        $array["estados"] = $modulos;
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function traerDocumentosNuevaContabilidad() {
        $documentos = DB::connection("NuevaContabilidad")->select("SELECT * FROM documentos ORDER BY fechaSubida");
        $array["documentos"] = $documentos;
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}