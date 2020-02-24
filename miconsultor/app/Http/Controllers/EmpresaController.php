<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Mail;
use App\Mail\MensajesValidacion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;


class EmpresaController extends Controller
{
    function listaEmpresasUsuario(Request $request)
    {
        $valida = verificaUsuario($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $usuario = $valida[0]['usuario'];
            $iduser = $usuario[0]->idusuario;
            $array["usuario"] = $valida[0]['usuario'];
            $empresas = DB::connection("General")->select("SELECT mc1000.* FROM mc1002 m02 
                                                    INNER JOIN mc1000 on m02.idempresa=mc1000.idempresa 
                                                    WHERE m02.idusuario=$iduser AND mc1000.status=1");
            for ($i=0; $i < count($empresas); $i++) { 
                $empresaBD = $empresas[$i]->rutaempresa;        
                ConnectaEmpresaDatabase($empresaBD);

                $perfil = DB::select('select nombre from mc_userprofile INNER JOIN mc_profiles 
                                where idusuario = ?', [$iduser]);
                $empresas[$i]->perfil = $perfil[0]->nombre;

                $sucursales = DB::select('select * from mc_catsucursales');

                $empresas[$i]->sucursales = $sucursales;
            }
            
            $array["empresas"] = $empresas;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function validaEmpresa(Request $request)
    {
        $valida = verificaUsuario($request->usuario, $request->pwd);
          

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $conexionFTP = conectaFTP();
            if ($conexionFTP != '') {
                
                $archivocer = $request->file('certificado');
                $archivokey = $request->file('key');

                $passwordcer = $request->passwordcertificado;

                $resSubirArchivos = $this->subeCertificados($conexionFTP, $archivocer, $archivokey);
                $array["error"] = $resSubirArchivos;
                
                ftp_close($conexionFTP);

                $resDatos = $this->verificaDatosCertificados($archivocer->getClientOriginalName(), $archivokey->getClientOriginalName(),$passwordcer);

                $array["error"] = $resDatos[0]["error"];

                $datosEmpresa = $resDatos[0]["datos"];
                $rfc = $datosEmpresa["rfc"];
                
                $empresa = DB::select('select * from mc1000 where rfc = ?', [$rfc]);
                if (!empty($empresa)) {
                    $array["error"] = 41; //RFCEXISTE
                }else {
                    $array["datos"] = $datosEmpresa;
                }
                
                
            }else{
                $array["error"] = 30; //Error en conexion
            }
            
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function verificaDatosCertificados($cer, $key, $pass)
    {
        $array[0]["error"] = 0;
        $data = array(
            "password" => $pass,
            "key" => $key,
            "cer" => $cer,
            "carpeta" => "temporal",
        );
        $resultado2 = CallAPI("POST", "certificados.inroute.mx/documentos/leerarchivos.php", $data); 
        
        $datos = json_decode($resultado2, true);
       
        if ($datos['pareja']['result'] == 0) {
            $array[0]["error"] = 35; //NO SON PAREJA o contraseña incorrecta
        }elseif ($datos['Arreglofecha']['result'] == 0) {
            $array[0]["error"]= 36; //No se logro obtener la fecha de vigencia del certificado
        }elseif ($datos['KeyPemR']['result'] == 0) {
            $array[0]["error"] = 37;//Contraseña incorrecta
        }elseif ($datos['KeyPemR']['result'] == 0) {
            $array[0]["error"] = 38;//No se logro validar el certificado
        }
        $fechavencido = date("d/m/Y", strtotime($datos['Arreglofecha']['fecha']));
        $now = date('d/m/Y');
        
        $dato = $datos['ArregloCertificado']['datos'][1];
        $dato = \explode("=", $dato);
        $empresa = $dato[1];

        $dato = $datos['ArregloCertificado']['datos'][6];
        $dato = \explode("=", $dato);
        $rfc = $dato[1];

        if ($now > $fechavencido) {
            $array[0]["error"] = 39;
        }
        if ($rfc == "") {
            $array[0]["error"] = 40;
        }

        $array[0]["datos"] = array(
            "empresa" => $empresa,
            "rfc" => trim($rfc),
            "fechavencimiento" => $fechavencido
        );

        return $array;
    }

    public function subeCertificados($conexionFTP, $archivocer, $archivokey)
    {
        $temp = 'temporal';
        $error = 0;
        $certificado = $archivocer;    
        $key = $archivokey; 

        $nombrecertificado = $temp .'/'. $certificado->getClientOriginalName();
        $nombrekey = $temp .'/'.$key->getClientOriginalName();

        $nombrecertificadotemp = $certificado;
        $nombrekeytemp = $key;

        if (ftp_mkdir($conexionFTP, $temp)) {
            if (ftp_chmod($conexionFTP, 0777, $temp) !== false){
               if($certificado->getClientMimeType() == "application/pkix-cert" && $key->getClientMimeType() == "application/octet-stream"){
                    if (ftp_put($conexionFTP, $nombrecertificado, $nombrecertificadotemp, FTP_BINARY)) {
                        if (!ftp_put($conexionFTP, $nombrekey, $nombrekeytemp, FTP_BINARY)) {
                            $error = 33; //KEY MAL
                        }
                    }else{
                        $error = 32; //CERTIFICADO MAL
                    }
                }else {
                    if (ftp_rmdir($conexionFTP, $temp)) {
                    }
                    $error = 31; //NO SON ARCHIVOS VALIDOS
                }
            }
        }else{
            $error = 34;
        }
        return $error;
    }

    public function enviaCorreoEmpresa(Request $request)
    {
        $valida = verificaUsuario($request->usuario, $request->pwd);
          

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $data["subject"] = "Registro de empresa";
            $codigo = \rand();
            $data["identificador"] = $codigo;
            $correo = $request->correo;
            Mail::to($correo)->send(new MensajesValidacion($data));
            $array["codigo"] = $codigo;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
