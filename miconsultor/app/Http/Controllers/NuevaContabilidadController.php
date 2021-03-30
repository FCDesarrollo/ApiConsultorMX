<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Mail;
use App\Mail\MensajesGenerales;

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

    function enviarInformacionNuevaContabilidad(Request $request) {
        $nombre = $request->nombre;
        $apellido = $request->apellido;
        $nombreEmpresa = $request->nombreEmpresa;
        $correo = $request->correo;
        $numeroTelefono = $request->numeroTelefono;
        $estado = $request->estado;
        $planInteres = $request->planInteres;
        $correoDestinatario = "mkt@francocabanillas.com.mx";

        $data["titulo"] = "Solicitud de información";
        $data["cabecera"] = $nombre . " ".$apellido." solicita información";
        $data["mensaje"] = $nombre . " ".$apellido." perteneciente a la empresa ".$nombreEmpresa." ubicada en ".$estado." con correo ".$correo." y numero de teléfono ".$numeroTelefono." solicita información acerca de ".$planInteres.".";
        Mail::to($correoDestinatario)->send(new MensajesGenerales($data));
        $array["error"] = 0;
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}