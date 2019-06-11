<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class FcPremiumController extends Controller
{
    function enviarModulos(Request $request){
        $rfcempresa = $request->rfc;
        $nombrecliente = $request->razon;
        
       
        $now = date('Y-m-d');
        $empresa = DB::connection("General")->select("SELECT idcliente FROM fcclientes WHERE rfc='$rfcempresa'");    
        if (empty($empresa)){
           
            $idcliente = DB::connection("General")->table('fcclientes')->insertGetId(
                ['nombre_cliente' => $nombrecliente,'rfc' => $rfcempresa,
                'fecha' => $now,'status' =>"1" ]);  
        }else{
            $idcliente = $empresa[0]->idcliente;
        }
 
        $modulos = DB::connection("General")->select("SELECT  m.idmodulo,m.nombre_modulo,MAX(v.nombre_version) as mVer FROM fcmodulos m 
        INNER JOIN fcmodversion v ON m.idmodulo = v.idmodulo GROUP BY m.idmodulo");
        foreach($modulos as $t){
            $modulos2 = DB::connection("General")->select("SELECT idcliente FROM fcmodclientes WHERE idcliente='$idcliente' AND idmodulo='$t->idmodulo'");
            if (empty($modulos2)){
                $idU = DB::connection("General")->table('fcmodclientes')->insertGetId(
                    ['idcliente' => $idcliente,'idmodulo' => $t->idmodulo,
                    'idversion' => "0", 'permiso' => "0" ]);
            }
        }
        
        $datos = array(
            "modulo" => $modulos,
        ); 
        return json_encode($datos, JSON_UNESCAPED_UNICODE);

    }

    function versionesModulos(Request $request){

        $idmodulo = $request->idmodulo;

        $versiones = DB::connection("General")->select("SELECT idversion,nombre_version FROM fcmodversion 
                                        WHERE idmodulo='$idmodulo' and status=1");    
        $datos = array(
            "version" => $versiones,
        ); 
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
       
    }

    function datosVersion(Request $request){
        $idversion = $request->idversion;
        $version = DB::connection("General")->select("SELECT m.nombre_archivo AS nomfin,m.nombre_carpeta,
                                                    v.nombre_version,v.nombre_archivo AS nomdes,
                                                    v.nombre_ficha,v.script_gen,v.script_empresa
                                                    FROM fcmodulos m INNER JOIN fcmodversion v 
                                                    ON m.idmodulo=v.idmodulo WHERE v.idversion='$idversion'");    
        $datos = array(
            "version" => $version,
        ); 
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    } 

    function linkArchivo(Request $request){
            $linkurl = $request->link;
            $carpeta = $request->archivo;
            $usernube = $request->user;
            $passnube = $request->pass;
            $ch = curl_init();


            curl_setopt($ch, CURLOPT_URL, $linkurl);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
        
            curl_setopt($ch, CURLOPT_USERPWD, $usernube.":".$passnube);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "path=".$carpeta."&shareType=3");

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('OCS-APIRequest:true'));
            curl_setopt($ch, CURLOPT_HEADER, true);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

            $httpResponse = curl_exec($ch);

            $httpResponse = explode("\n\r\n", $httpResponse);

            $body = $httpResponse[1];
        
            $Respuesta= simplexml_load_string($body);
            
            $url = ((string)$Respuesta[0]->data->url);
            curl_close($ch);
            $prueba="Hola";
            return $url;
    }

    
}
