<?php
namespace App\Http\Controllers;

use App\Mail\MensajesEntregas;
use Illuminate\Support\Facades\DB;
use App\Mail\MensajesNotificacion;

use Mail;
use Config;

const Mod_Contabilidad = 1;
const Mod_BandejaEntrada = 2;
const Mod_Administracion = 3;

const Menu_Contabilidad = 1;
const Menu_ProcesoFiscal = 2;
const Menu_Finanzas = 3;
const Menu_Compras = 4;
const Menu_AlmacenDigital = 5;
const Menu_RecepcionLotes = 6;
const Menu_Empresa = 7;
const Menu_Usuario = 8;
const Menu_Perfiles = 9;

const SubM_EstadosFinancieros = 1;
const SubM_ContabilidadElectronica = 2;
const SubM_ExpedientesAdministrativos = 3;
const SubM_ExpedientesContables = 4;
const SubM_PagosProvicionales = 5;
const SubM_PagosMensuales = 6;
const SubM_DeclaracionesAnuales = 7;
const SubM_ExpedientesFiscales = 8;
const SubM_IndicadoresFinancieros = 9;
const SubM_AsesorFlujoEfectivo = 10;
const SubM_AnalisisProyecto = 11;
const SubM_Requerimientos = 12;
const SubM_Autorizaciones = 13;
const SubM_RecepcionCompras = 14;
const SubM_NotificacionesAutoridades = 15;
const SubM_ExpedientesDigitales = 16;
const SubM_ProcesoProduccion = 17;
const SubM_ProcesoCompras = 18;
const SubM_ProcesoVentas = 19;
const SubM_Empresas = 20;
const SubM_Usuarios = 21;
const SubM_Perfiles = 22;

function ConnectDatabase($idempresa)
{
    $empresa = DB::connection("General")->select("SELECT * FROM mc1000 WHERE idempresa='$idempresa' AND status=1");
    // return $clientes[0]->database;

    Config::set('database.connections.mysql', array(
        'driver' => 'mysql',
        'host' => env('DB_HOST', ''),
        'port' => env('DB_PORT', ''),
        'database' => env('dublockc_MCGenerales', $empresa[0]->rutaempresa),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'unix_socket' => env('DB_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => false,
        'engine' => null,
    ));

    DB::reconnect('mysql');    
}


function newConexion($rfc){
        // Obtenemos la empresa que tenga el rfc que nos llega en la peticion
        $empresa = DB::connection("General")->table('mc1000')->where('RFC', $rfc)->first(); //DB::purgue() ->si llegara a ser necesario
        // Jalamos la configuracion de las conexiones de laravel, en este caso la conexion original que se llama mysql
        $config = \Config::get('database.connections.mysql');
        // Sobreescribimos el nombre de la base de datos a la cual nos queremos conectar
        $config['database'] = $empresa->rutaempresa;
        // Aplicamos el cambio a la session de base de datos
        config()->set('database.connections.mysql', $config);
}

function ConnectDatabaseRFC($rfc)
{
    $empresa = DB::connection("General")->select("SELECT * FROM mc1000 WHERE rfc='$rfc' AND status=1");
    //return $clientes[0]->database;
    Config::set('database.connections.mysql', array(
        'driver' => 'mysql',
        'host' => env('DB_HOST', ''),
        'port' => env('DB_PORT', ''),
        'database' => env('dublockc_MCGenerales', $empresa[0]->rutaempresa),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'unix_socket' => env('DB_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => false,
        'engine' => null,
    ));

    DB::reconnect('mysql');    
}

function GetNomCarpetaModulo($idmodulo)
{
    $carpeta ="";
    $result = DB::connection("General")->select("SELECT nombre_carpeta FROM mc1003 WHERE idmodulo=$idmodulo");
    if (!empty($result)) {
        $carpeta = $result[0]->nombre_carpeta;
    }
    
    return $carpeta;
}

function GetNomModulo($idmodulo)
{
    $carpeta ="";
    $result = DB::connection("General")->select("SELECT nombre_modulo FROM mc1003 WHERE idmodulo=$idmodulo");
    if (!empty($result)) {
        $carpeta = $result[0]->nombre_modulo;
    }
    
    return $carpeta;
}

function GetNomCarpetaMenu($idmenu)
{
    $carpeta ="";
    $result = DB::connection("General")->select("SELECT nombre_carpeta FROM mc1004 WHERE idmenu=$idmenu");
    if (!empty($result)) {
        $carpeta = $result[0]->nombre_carpeta;
    }
    
    return $carpeta;
}

function GetNomMenu($idmenu)
{
    $carpeta ="";
    $result = DB::connection("General")->select("SELECT nombre_menu FROM mc1004 WHERE idmenu=$idmenu");
    if (!empty($result)) {
        $carpeta = $result[0]->nombre_menu;
    }
    
    return $carpeta;
}

function GetNomCarpetaSubMenu($idsubmenu)
{
    $carpeta ="";
    $result = DB::connection("General")->select("SELECT nombre_carpeta FROM mc1005 WHERE idsubmenu=$idsubmenu");
    if (!empty($result)) {
        $carpeta = $result[0]->nombre_carpeta;
    }
    
    return $carpeta;
}

function GetNomSubMenu($idsubmenu)
{
    $carpeta ="";
    $result = DB::connection("General")->select("SELECT nombre_submenu FROM mc1005 WHERE idsubmenu=$idsubmenu");
    if (!empty($result)) {
        $carpeta = $result[0]->nombre_submenu;
    }
    
    return $carpeta;
}

function subirArchivoNextcloud($archivo_name, $ruta_temp, $rfcempresa, $servidor, $usuario, $password,$carpetamodulo, $carpetamenu, $carpetasubmenu, $codarchivo, $consecutivo)
    {

        set_time_limit(0);
        $directorio = $rfcempresa . '/'. $carpetamodulo .'/' . $carpetamenu . '/' . $carpetasubmenu;

        $ch = curl_init();
        $file = $archivo_name;
        $filename = $codarchivo . $consecutivo;
        $source = $ruta_temp; //Obtenemos un nombre temporal del archivo        
        $type = explode(".", $file);
        $target_path = $directorio . '/' . $filename . "." . $type[count($type) - 1];

        $gestor = fopen($source, "r");

        if (filesize($source) > 0){
            $contenido = fread($gestor, filesize($source));

            curl_setopt_array(
                $ch,
                array(
                    CURLOPT_URL => 'https://' . $servidor . '/remote.php/dav/files/' . $usuario . '/CRM/' . $target_path,
                    CURLOPT_VERBOSE => 1,
                    CURLOPT_USERPWD => $usuario . ':' . $password,
                    CURLOPT_POSTFIELDS => $contenido,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_BINARYTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => 'PUT',
                )
            );
            $resp = curl_exec($ch);
            $error_no = curl_errno($ch);
        }else{
            $error_no = 3;
        }
        fclose($gestor);
        curl_close($ch);

        $array["archivo"]["target"] = $target_path;
        $array["archivo"]["codigo"] = $filename;
        $array["archivo"]["error"] = $error_no;
        $array["archivo"]["directorio"] = $directorio;
        $array["archivo"]["filename"] = $filename . "." . $type[count($type) - 1];

        return $array;
    }

    function subirMovimientoEmpresaNextcloud($archivo_name, $ruta_temp, $rfcempresa, $servidor, $usuario, $password, $codarchivo, $consecutivo)
    {

        set_time_limit(0);
        $directorio = $rfcempresa . '/Cuenta/Empresa/EstadoCuenta';

        $ch = curl_init();
        $file = $archivo_name;
        $filename = $codarchivo . $consecutivo;
        $source = $ruta_temp; //Obtenemos un nombre temporal del archivo        
        $type = explode(".", $file);
        $target_path = $directorio . '/' . $filename . "." . $type[count($type) - 1];

        $gestor = fopen($source, "r");

        if (filesize($source) > 0){
            $contenido = fread($gestor, filesize($source));

            curl_setopt_array(
                $ch,
                array(
                    CURLOPT_URL => 'https://' . $servidor . '/remote.php/dav/files/' . $usuario . '/CRM/' . $target_path,
                    CURLOPT_VERBOSE => 1,
                    CURLOPT_USERPWD => $usuario . ':' . $password,
                    CURLOPT_POSTFIELDS => $contenido,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_BINARYTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => 'PUT',
                )
            );
            $resp = curl_exec($ch);
            $error_no = curl_errno($ch);
        }else{
            $error_no = 3;
        }
        fclose($gestor);
        curl_close($ch);

        $array["archivo"]["target"] = $target_path;
        $array["archivo"]["codigo"] = $filename;
        $array["archivo"]["error"] = $error_no;

        return $array;
    }

    function subirNuevoCertificadoNextcloud($archivo_name, $ruta_temp, $rfcempresa, $servidor, $usuario, $password, $filename)
    {

        set_time_limit(0);
        $directorio = $rfcempresa;

        $ch = curl_init();
        $file = $archivo_name;
        $source = $ruta_temp; //Obtenemos un nombre temporal del archivo 
        $target_path = $directorio . '/' . $filename;

        $gestor = fopen($source, "r");

        if (filesize($source) > 0){
            $contenido = fread($gestor, filesize($source));

            curl_setopt_array(
                $ch,
                array(
                    CURLOPT_URL => 'https://' . $servidor . '/remote.php/dav/files/' . $usuario . '/CRM/' . $target_path,
                    CURLOPT_VERBOSE => 1,
                    CURLOPT_USERPWD => $usuario . ':' . $password,
                    CURLOPT_POSTFIELDS => $contenido,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_BINARYTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => 'PUT',
                )
            );
            $resp = curl_exec($ch);
            $error_no = curl_errno($ch);
        }else{
            $error_no = 3;
        }
        fclose($gestor);
        curl_close($ch);

        $array["archivo"]["target"] = $target_path;
        $array["archivo"]["codigo"] = $filename;
        $array["archivo"]["error"] = $error_no;

        return $array;
    }

    function GetLinkArchivo($link, $server, $user, $pass){
        set_time_limit(0);
        $ch = curl_init();
     //curl_setopt($ch, CURLOPT_URL, "https://".$user.":".$pass."@".$server."/ocs/v2.php/apps/files_sharing/api/v1/shares");
         curl_setopt($ch, CURLOPT_URL, "https://".$server."/ocs/v2.php/apps/files_sharing/api/v1/shares");
         curl_setopt($ch, CURLOPT_VERBOSE, 1);       
         curl_setopt($ch, CURLOPT_USERPWD, $user.":".$pass);
         curl_setopt($ch, CURLOPT_POSTFIELDS, "path=CRM/".$link."&shareType=3");
         curl_setopt($ch, CURLOPT_HTTPHEADER, array('OCS-APIRequest:true'));
         curl_setopt($ch, CURLOPT_HEADER, true);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
         curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
         $httpResponse = curl_exec($ch);
         $httpResponse = explode("\n\r\n", $httpResponse);
         $body = $httpResponse[1];

         libxml_use_internal_errors(true);
         $Respuesta = simplexml_load_string($body);
         $xml = explode("\n", $body);

        if (!$Respuesta) {
            /* $errors = libxml_get_errors();

            foreach ($errors as $error) {
                echo display_xml_error($error, $xml);
            }

            libxml_clear_errors();
            return $errors; */
            return "";
        }
         $url = ((string) $Respuesta[0]->data->url);
         curl_close($ch);
         return $url;
     }

     function GetLinkArchivoAdmin($link, $server, $user, $pass){
        set_time_limit(0);
        $ch = curl_init();
     //curl_setopt($ch, CURLOPT_URL, "https://".$user.":".$pass."@".$server."/ocs/v2.php/apps/files_sharing/api/v1/shares");
         curl_setopt($ch, CURLOPT_URL, "https://".$server."/ocs/v2.php/apps/files_sharing/api/v1/shares");
         curl_setopt($ch, CURLOPT_VERBOSE, 1);       
         curl_setopt($ch, CURLOPT_USERPWD, $user.":".$pass);
         curl_setopt($ch, CURLOPT_POSTFIELDS, "path=Archivos Generales/".$link."&shareType=3");
         curl_setopt($ch, CURLOPT_HTTPHEADER, array('OCS-APIRequest:true'));
         curl_setopt($ch, CURLOPT_HEADER, true);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
         curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
         $httpResponse = curl_exec($ch);
         $httpResponse = explode("\n\r\n", $httpResponse);
         $body = $httpResponse[1];
         $Respuesta = simplexml_load_string($body);
         $url = ((string) $Respuesta[0]->data->url);
         curl_close($ch);
         return $url;
     }



    function ConnectaEmpresaDatabase($empresa){

        Config::set('database.connections.mysql', array(
            'driver' => 'mysql',
            'host' => env('DB_HOST', ''),
            'port' => env('DB_PORT', ''),
            'database' => env('', $empresa),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ));

        DB::reconnect('mysql');    
    }

    function verificaLogin($user, $pass){
        $datos[0]['error'] = 0;
        $usuario = DB::select("SELECT * FROM mc1001 WHERE (correo='$user' or cel='$user') AND status = 1");
        if (!empty($usuario)){
            $hash_BD = $usuario[0]->password;

            if (password_verify($pass, $hash_BD)) {
                $datos[0]['usuario'] = $usuario;
            } else {
                $datos[0]['error'] = 3;
            } 
        }else {
            $datos[0]['error'] = 2;;
        }
        return $datos;
    }

    function verificaUsuario($user, $pass){
        $datos[0]['error'] = 0;
        $usuario = DB::select("SELECT * FROM mc1001 WHERE (correo='$user' or cel='$user') AND status=1");
        if (!empty($usuario)){
            $hash_BD = $usuario[0]->password;

            if ($pass == $hash_BD) {
                $datos[0]['usuario'] = $usuario;
            } else {
                $datos[0]['error'] = 3;
            } 
        }else {
            $datos[0]['error'] = 2;;
        }
        return $datos;
    }

    function validaNuevoUsuario($correo, $cel){        
        $datos[0]['error'] = 0;
        $usuario = DB::connection("General")->select("SELECT * FROM mc1001 WHERE correo='$correo'");
        if (!empty($usuario)) {
            $datos[0]['error'] = -2;
        }
        $usuario = DB::connection("General")->select("SELECT * FROM mc1001 WHERE cel='$cel'");
        if (!empty($usuario)) {
            $datos[0]['error'] = -1;
        }     
        return $datos;
    }

    function VerificaEmpresa($rfc, $idusuario){
        $datos[0]['error'] = 0;
        $empresa = DB::connection("General")->select("SELECT * FROM mc1000 WHERE RFC='$rfc'");
        if (!empty($empresa)) {
            $idempresa = $empresa[0]->idempresa;
            $asociacion = DB::connection("General")->select('select * from mc1002 where idusuario = ? AND idempresa= ?', [$idusuario, $idempresa]);
            if (empty($asociacion)) {
                $datos[0]['error'] = 8;
            }
        }else{
            $datos[0]['error'] = 1;
        }
        return $datos;
    }

    function permisoSubMenu($idusuario, $idsubmenu)
    {
        $permiso = 0;
        $usuario = DB::select('select tipopermiso from mc_usersubmenu where idusuario = ? AND idsubmenu = ?', [$idusuario, $idsubmenu]);
        if (!empty($usuario)) {
            $permiso = $usuario[0]->tipopermiso;
        }

        return $permiso;
    }

    function verificarProveedor($user, $pass)
    {
        $datos[0]['error'] = 0;
        $usuario = DB::select("SELECT * FROM mc1001 WHERE (correo='$user' or cel='$user') AND status=1 AND tipo = 4");
        if (!empty($usuario)){
            $hash_BD = $usuario[0]->password;

            if ($pass == $hash_BD) {
                $datos[0]['usuario'] = $usuario;
            } else {
                $datos[0]['error'] = 3;
            } 
        }else {
            $datos[0]['error'] = 2;
        }
        return $datos;
    }

    function verificaPermisos($usuario, $pwd, $rfc, $idsubmenu)
    {

        $valida = verificaUsuario($usuario, $pwd);

        $datos[0]['error'] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $usuario = $valida[0]['usuario'];
            $datos[0]['usuario'] = $valida[0]['usuario'];

            $idusuario = $usuario[0]->idusuario;
            $valida2 = VerificaEmpresa($rfc, $idusuario);
            $datos[0]['error']  = $valida2[0]["error"];
            
            if ($valida2[0]['error'] == 0){
                ConnectDatabaseRFC($rfc);

                $permisoUsuario = permisoSubMenu($idusuario, $idsubmenu);
                if ($permisoUsuario == 0) {
                    $datos[0]['error']  = 4;
                }else{
                    $datos[0]['permiso']  = $permisoUsuario;
                }
            }
        }
        return $datos;
    }

    function getServidorNextcloud()
    {
        $servidor ="";
        $result = DB::connection("General")->select("SELECT servidor_storage FROM mc0000 WHERE id = 1");
        if (!empty($result)) {
            $servidor = $result[0]->servidor_storage;
        }
        return $servidor;
    }

    function getParametros()
    {
        $servidor ="";
        $result = DB::connection("General")->select("SELECT * FROM mc0000 WHERE id = 1");
        if (!empty($result)) {
            $servidor = $result;
        }
        return $servidor;
    }

    function getExisteCarpeta($idmodulo, $idmenu, $idsubmenu)
    {
        $datos[0]['error'] = 0;
        $carpetamodulo = GetNomCarpetaModulo($idmodulo);
        $carpetamenu = GetNomCarpetaMenu($idmenu);
        $carpetasubmenu = GetNomCarpetaSubMenu($idsubmenu);
        if ($carpetamodulo == "") {
            $datos[0]['error'] = 15;
        }elseif($carpetamenu == ""){
            $datos[0]['error'] = 16;
        }elseif ($carpetasubmenu == "") {
            $datos[0]['error'] = 17;
        }
        $datos[0]['carpetamodulo'] = $carpetamodulo;
        $datos[0]['carpetamenu'] = $carpetamenu;
        $datos[0]['carpetasubmenu'] = $carpetasubmenu;
        return $datos;
    }

    function getNumeroConsecutivo($fechadocto, $idsubmenu)
    {
            $fecha = $fechadocto;
            $fecha = strtotime($fecha);
            $mes = intval(date("m", $fecha));
            $año = intval(date("Y", $fecha));
            $mod = $idsubmenu;
            $ultregistro = DB::select("SELECT MAX(d.id) AS id FROM mc_almdigital a INNER JOIN mc_almdigital_det d ON a.id = d.idalmdigital WHERE a.idmodulo = $mod AND MONTH(a.fechadocto) = $mes AND YEAR(a.fechadocto) = $año");

            if (!empty($ultregistro)) {
                $ultimoid = $ultregistro[0]->id;
                if ($ultimoid > 0) {
                    $ultarchivo = DB::select("SELECT codigodocumento FROM mc_almdigital_det WHERE id = $ultimoid");
                    $nombre_a = $ultarchivo[0]->codigodocumento;
                    $consecutivo = substr($nombre_a, -4);
                    $consecutivo = $consecutivo + 1;
                } else {
                    $consecutivo = "0001";
                }
            } else {
                $consecutivo = "0001";
            }

            return $consecutivo;
    }

    function getExistenArchivos($archivos, $fecha, $idsubmenu, $ruta, $servidor, $userSt, $pwdSt)
    {
        $array["error"] = 1;

        for ($i = 0; $i < count($archivos); $i++) {
            $archivo = $archivos[$i]["archivo"];

            $codigodocumento = $archivos[$i]["codigo"];
            $status = $archivos[$i]["status"];
            $link = $archivos[$i]["link"];

            if ($status == 0) {
                $ele = DB::select("SELECT det.* FROM mc_almdigital_det AS det INNER JOIN mc_almdigital AS a ON det.idalmdigital = a.id WHERE documento = '$archivo' AND a.fechadocto = '$fecha' AND a.idmodulo = $idsubmenu");
                if (empty($ele)) {
                    $array["error"] = 0;
                    $array["archivos"][$i]["archivo"] = $archivo;
                    $array["archivos"][$i]["codigo"] = $codigodocumento;
                    $array["archivos"][$i]["link"] = $link;
                    $array["archivos"][$i]["status"] = 0; //Nuevo  
                    $array["archivos"][$i]["detalle"] = "¡Cargado Correctamente!";
                } else {
                    //$archivos[$i]["status"] = 1;    
                    $array["archivos"][$i]["archivo"] = $archivo;
                    $array["archivos"][$i]["codigo"] = $codigodocumento;
                    $array["archivos"][$i]["link"] = "";
                    $array["archivos"][$i]["status"] = 4; //Duplicado    
                    $array["archivos"][$i]["detalle"] = "¡Ya existe!";
                }
            } else {
                $array["archivos"][$i]["archivo"] = $archivo;
                $array["archivos"][$i]["codigo"] = $codigodocumento;
                $array["archivos"][$i]["link"] = "";
                $array["archivos"][$i]["status"] = $status; //Archivo Dañado
                $array["archivos"][$i]["detalle"] = $archivos[$i]["detalle"];
            }


            if ($array["archivos"][$i]["status"] != 0) {
                $type = explode(".", $archivo);
                $archivo = $ruta . "/" . $codigodocumento . "." . $type[1];
                $resp = eliminaArchivoNextcloud($servidor, $userSt, $pwdSt, $archivo);
            }
        }

        return $array;
    }

    function eliminaArchivoNextcloud(String $servidor, String $userSto, String $passSto,  string $archivo)
    {
        $ch = curl_init();
        $url = 'https://' . $servidor . '/remote.php/dav/files/' . $userSto . '/CRM/' . $archivo;
        curl_setopt_array(
            $ch,
            array(
                CURLOPT_URL => $url,
                CURLOPT_VERBOSE => 1,
                CURLOPT_USERPWD => $userSto . ':' . $passSto,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_BINARYTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
            )
        );
        $regresa = curl_exec($ch);
        //print_r($regresa);   
        curl_close($ch);
        return $regresa;
    }

    function eliminaImagenServicioNextcloud(String $servidor, String $userSto, String $passSto,  string $imagen)
    {
        $ch = curl_init();
        $url = 'https://' . $servidor . '/remote.php/dav/files/' . $userSto . '/Archivos Generales/' . $imagen;
        curl_setopt_array(
            $ch,
            array(
                CURLOPT_URL => $url,
                CURLOPT_VERBOSE => 1,
                CURLOPT_USERPWD => $userSto . ':' . $passSto,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_BINARYTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
            )
        );
        $regresa = curl_exec($ch);
        //print_r($regresa);   
        curl_close($ch);
        return $regresa;
    }

    function conectaFTP()
    {
        try {
            $result = DB::connection("General")->select("SELECT * FROM mc0000 WHERE id = 2");
            $ftp_server = $result[0]->servidor_storage;
            $conn_id = ftp_connect($ftp_server);
            // login con usuario y contraseña
            $ftp_user_name = $result[0]->usuario_storage;
            $ftp_user_pass = $result[0]->password_storage;
            $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
                
        } catch (\Throwable $th) {
            $conn_id = '';
            return 0;
        }
        return $conn_id;
    }

    function CallAPI($method, $url, $data = false)
    {
        $curl = curl_init();
        switch ($method)
        {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        curl_close($curl);
        return $result;

    }

    function enviaNotificacion($datos)
    {
        $idusuario = $datos[0]["idusuario"];
        $encabezado = $datos[0]["encabezado"];
        $mensaje = $datos[0]["mensaje"];

        //$fecha = $datos[0]["fecha"];
        $idmodulo = $datos[0]["idmodulo"];
        $idmenu = $datos[0]["idmenu"];
        $idsubmenu = $datos[0]["idsubmenu"];
        $idregistro = $datos[0]["idregistro"];

        $usuarios  = $datos[0]["usuarios"];

        $idnotificacion = DB::table('mc_notificaciones')->insertGetId(['idusuario' => $idusuario, 'encabezado' => $encabezado,
                 'mensaje' => $mensaje,'idmodulo' => $idmodulo, 'idmenu' => $idmenu,
                 'idsubmenu' => $idsubmenu, 'idregistro' => $idregistro]);
        
        for ($i=0; $i < count($usuarios); $i++) { 
            $idusernotifica = $usuarios[$i]->id_usuario;
            $tipo = $usuarios[$i]->notificaciones;

            DB::insert('insert into mc_notificaciones_det (idusuario, idnotificacion, status) 
                        values (?, ?, ?)', [$idusernotifica, $idnotificacion, 0]);  

            if ($tipo == 1 or $tipo == 3 or $tipo == 5 or $tipo == 7) {
                $correo = $usuarios[$i]->correo;
                Mail::to($correo)->send(new MensajesNotificacion($datos));
            }
        }
        return 0;

    }

    function enviaNotificacionEntre($datos)
    {
        $idusuario = $datos[0]["idusuario"];
        $encabezado = $datos[0]["encabezado"];
        $mensaje = $datos[0]["mensaje"];

        //$fecha = $datos[0]["fecha"];
        $idmodulo = $datos[0]["idmodulo"];
        $idmenu = $datos[0]["idmenu"];
        $idsubmenu = $datos[0]["idsubmenu"];
        $idregistro = $datos[0]["idregistro"];

        $usuarios  = $datos[0]["usuarios"];

        $idnotificacion = DB::table('mc_notificaciones')->insertGetId(['idusuario' => $idusuario, 'encabezado' => $encabezado,
                 'mensaje' => $mensaje,'idmodulo' => $idmodulo, 'idmenu' => $idmenu,
                 'idsubmenu' => $idsubmenu, 'idregistro' => $idregistro]);
        
        for ($i=0; $i < count($usuarios); $i++) { 
            $idusernotifica = $usuarios[$i]->id_usuario;
            $tipo = $usuarios[$i]->notificaciones;

            DB::insert('insert into mc_notificaciones_det (idusuario, idnotificacion, status) 
                        values (?, ?, ?)', [$idusernotifica, $idnotificacion, 0]);  

            if ($tipo == 1 or $tipo == 3 or $tipo == 5 or $tipo == 7) {
                $correo = $usuarios[$i]->correo;
                Mail::to($correo)->send(new MensajesEntregas($datos));
            }
        }
        return 0;

    }

    function enviaNotificacionCo($datos)
    {
        $idusuario = $datos[0]["idusuario"];
        $encabezado = $datos[0]["encabezado"];
        $mensaje = $datos[0]["mensaje"];

        //$fecha = $datos[0]["fecha"];
        $idmodulo = $datos[0]["idmodulo"];
        $idmenu = $datos[0]["idmenu"];
        $idsubmenu = $datos[0]["idsubmenu"];
        $idregistro = $datos[0]["idregistro"];

        $usuarios  = $datos[0]["usuarios"];

        $idnotificacion = DB::table('mc_notificaciones')->insertGetId(['idusuario' => $idusuario, 'encabezado' => $encabezado,
                 'mensaje' => $mensaje,'idmodulo' => $idmodulo, 'idmenu' => $idmenu,
                 'idsubmenu' => $idsubmenu, 'idregistro' => $idregistro]);
        
        
        for ($i=0; $i < count($usuarios); $i++) { 
            $idusernotifica = $usuarios[$i]['id_usuario'];
            $tipo = $usuarios[$i]['notificaciones'];

            DB::insert('insert into mc_notificaciones_det (idusuario, idnotificacion, status) 
                        values (?, ?, ?)', [$idusernotifica, $idnotificacion, 0]);  

            if ($tipo == 1 or $tipo == 3 or $tipo == 5 or $tipo == 7) {
                $correo = $usuarios[$i]['correo'];
                Mail::to($correo)->send(new MensajesNotificacion($datos));
            }
        }
        return 0;

    }

    function ValidarFolio($variable) {
        
        $permitidos = "0123456789"; 
        $flag = true;
        for ($i=0; $i<strlen($variable); $i++){ 
            if (strpos($permitidos, substr($variable,$i,1))===false){ 
                $flag = false;
                break;
            } 
        }
        return $flag;  		
    }

    function armarLayout($IdUsuario, $idBanco, $destino, $datosLayout/* , $ReferenciaNumerica */, $nombrearchivonuevo, $urldestino, $RFC, $Servidor, $u_storage, $p_storage, $FechaServidor, $Consecutivo, $IdsBancosOrigen) {
        /* $layoutsusuario = DB::select('SELECT mc_flw_layouts_usuarios.*, mc_flw_layouts_config.LinkLayout FROM mc_flw_layouts_usuarios 
        INNER JOIN mc_flw_layouts_config ON mc_flw_layouts_usuarios.IdLayoutConfig = mc_flw_layouts_config.id
        WHERE mc_flw_layouts_usuarios.IdUsuario = ? AND mc_flw_layouts_usuarios.IdBanco = ?', [$IdUsuario, $idBanco]); */
        $layoutsusuario = DB::select('SELECT * FROM mc_flw_layouts_config WHERE IdBanco = ? AND Destino = ?', [$idBanco, $destino]);
        if(count($layoutsusuario) > 0) {
            $configlayout = DB::select('SELECT * FROM mc_flw_layouts_config_content WHERE IdLayoutConfig = ? ORDER BY Posicion', [$layoutsusuario[0]->id]);
        }
        else {
            $layoutsusuario = DB::select('SELECT * FROM mc_flw_layouts_config WHERE id = ?', [1]);
            $configlayout = DB::select('SELECT * FROM mc_flw_layouts_config_content WHERE IdLayoutConfig = ? ORDER BY Posicion', [1]);
        }


        for($x=0 ; $x<count($datosLayout["idsFlw"]) ; $x++) {
            $IdsFlw[$x] = explode(",", $datosLayout["idsFlw"][$x]);
            $datosLayout["idsflwtransaccion"][$x] = $IdsFlw[$x][0];
            /* $datosLayout["tipodocumento"][$x] = $IdsFlw[$x][0] > 0 ? 1 : 2; */
            $infopagoencontrado = $datosLayout["tipodocumento"][$x] == "1" ? DB::select("SELECT mc_flw_pagos.*, DATE_FORMAT(mc_flw_pagos.Fecha, '%d%m%y') AS ReferenciaNumerica,
            mc_flow_bancuentas.Cuenta AS CuentaOrigen,
            mc_flow_bancuentas.Clabe AS ClabeOrigen, mc_flow_bancuentas.Sucursal AS SucursalOrigen,
            mc_flow_cliproctas.Cuenta AS CuentaDestino,
            mc_flow_cliproctas.Clabe AS ClabeDestino, mc_flow_cliproctas.Sucursal AS SucursalDestino
            FROM mc_flw_pagos INNER JOIN mc_flw_pagos_det ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id
            LEFT JOIN mc_flow_bancuentas ON mc_flw_pagos.IdCuentaOrigen = mc_flow_bancuentas.IdCuenta
            LEFT JOIN mc_flow_cliproctas ON mc_flw_pagos.IdCuentaDestino = mc_flow_cliproctas.Id
            WHERE mc_flw_pagos.IdUsuario = ? AND mc_flw_pagos.Layout = ? AND mc_flw_pagos_det.IdFlw = ?", [$IdUsuario, 0, $IdsFlw[$x][0]]) : DB::select("SELECT mc_flw_pagos.*, DATE_FORMAT(mc_flw_pagos.Fecha, '%d%m%y') AS ReferenciaNumerica,
            mc_flow_bancuentas.Cuenta AS CuentaOrigen,
            mc_flow_bancuentas.Clabe AS ClabeOrigen, mc_flow_bancuentas.Sucursal AS SucursalOrigen,
            mc_flow_cliproctas.Cuenta AS CuentaDestino,
            mc_flow_cliproctas.Clabe AS ClabeDestino, mc_flow_cliproctas.Sucursal AS SucursalDestino
            FROM mc_flw_pagos
            LEFT JOIN mc_flow_bancuentas ON mc_flw_pagos.IdCuentaOrigen = mc_flow_bancuentas.IdCuenta
            LEFT JOIN mc_flow_cliproctas ON mc_flw_pagos.IdCuentaDestino = mc_flow_cliproctas.Id 
            WHERE mc_flw_pagos.IdUsuario = ? AND mc_flw_pagos.Layout = ? AND mc_flw_pagos.id = ?", [$IdUsuario, 0, $IdsFlw[$x][0]]);
            $datosLayout["llaveMatch"][$x] = $infopagoencontrado[0]->LlaveMatch;
            $datosLayout["descripcion"][$x] = $infopagoencontrado[0]->LlaveMatch;

            if($infopagoencontrado[0]->ClabeOrigen != null) {
                if(strlen($infopagoencontrado[0]->ClabeOrigen) < 18) {
                    $clabeBancoOrigen = str_pad_unicode($infopagoencontrado[0]->ClabeOrigen, 18, '0', STR_PAD_LEFT);
                }
                else {
                    $clabeBancoOrigen = $infopagoencontrado[0]->ClabeOrigen;
                }
                $codigoBancoOrigen = substr($clabeBancoOrigen, 0, 3);
                $sucursalBancoOrigen = $infopagoencontrado[0]->SucursalOrigen != null ? $infopagoencontrado[0]->SucursalOrigen : substr($clabeBancoOrigen, 3, 3);
                /* $numeroCuentaOrigen = substr($clabeBancoOrigen, 6, 11); */
                $numeroCuentaOrigen = substr($clabeBancoOrigen, 10, 7);
                $digitoControlCuentaOrigen = substr($clabeBancoOrigen, 17);
            }
            else {
                if($infopagoencontrado[0]->CuentaOrigen != null) {
                    if(strlen($infopagoencontrado[0]->CuentaOrigen) < 18) {
                        $clabeBancoOrigen = str_pad_unicode($infopagoencontrado[0]->CuentaOrigen, 18, '0', STR_PAD_LEFT);
                    }
                    else {
                        $clabeBancoOrigen = $infopagoencontrado[0]->CuentaOrigen;
                    }
                    $codigoBancoOrigen = substr($clabeBancoOrigen, 0, 3);
                    $sucursalBancoOrigen = $infopagoencontrado[0]->SucursalOrigen != null ? $infopagoencontrado[0]->SucursalOrigen : substr($clabeBancoOrigen, 3, 3);
                    /* $numeroCuentaOrigen = substr($clabeBancoOrigen, 6, 11); */
                    $numeroCuentaOrigen = strlen($infopagoencontrado[0]->CuentaOrigen) == 7 ? substr($clabeBancoOrigen, 11, 7) : substr($clabeBancoOrigen, 10, 7);
                    $digitoControlCuentaOrigen = substr($clabeBancoOrigen, 17);
                }
                else {
                    $clabeBancoOrigen = '000000000000000000';
                    $codigoBancoOrigen = substr($clabeBancoOrigen, 0, 3);
                    $sucursalBancoOrigen = $infopagoencontrado[0]->SucursalOrigen != null ? $infopagoencontrado[0]->SucursalOrigen : substr($clabeBancoOrigen, 3, 3);
                    /* $numeroCuentaOrigen = substr($clabeBancoOrigen, 6, 11); */
                    $numeroCuentaOrigen = substr($clabeBancoOrigen, 10, 7);
                    $digitoControlCuentaOrigen = substr($clabeBancoOrigen, 17);
                }
            }
            
            if($infopagoencontrado[0]->ClabeDestino != null) {
                if(strlen($infopagoencontrado[0]->ClabeDestino) < 18) {
                    $clabeBancoDestino = str_pad_unicode($infopagoencontrado[0]->ClabeDestino, 18, '0', STR_PAD_LEFT);
                }
                else {
                    $clabeBancoDestino = $infopagoencontrado[0]->ClabeDestino;
                }
                $codigoBancoDestino = substr($clabeBancoDestino, 0, 3);
                $sucursalBancoDestino = $infopagoencontrado[0]->SucursalDestino != null ? $infopagoencontrado[0]->SucursalDestino : substr($clabeBancoDestino, 3, 3);
                /* $numeroCuentaDestino = substr($clabeBancoDestino, 6, 11); */
                $numeroCuentaDestino = substr($clabeBancoDestino, 10, 7);
                $digitoControlCuentaDestino = substr($clabeBancoDestino, 17);
            }
            else {
                if($infopagoencontrado[0]->CuentaDestino != null) {
                    if(strlen($infopagoencontrado[0]->CuentaDestino) < 18) {
                        $clabeBancoDestino = str_pad_unicode($infopagoencontrado[0]->CuentaDestino, 18, '0', STR_PAD_LEFT);
                    }
                    else {
                        $clabeBancoDestino = $infopagoencontrado[0]->CuentaDestino;
                    }
                    $codigoBancoDestino = substr($clabeBancoDestino, 0, 3);
                    $sucursalBancoDestino = $infopagoencontrado[0]->SucursalDestino != null ? $infopagoencontrado[0]->SucursalDestino : substr($clabeBancoDestino, 3, 3);
                    /* $numeroCuentaDestino = substr($clabeBancoDestino, 6, 11); */
                    $numeroCuentaDestino = strlen($infopagoencontrado[0]->CuentaDestino) == 7 ? substr($clabeBancoDestino, 11, 7) : substr($clabeBancoDestino, 10, 7);
                    $digitoControlCuentaDestino = substr($clabeBancoDestino, 17);
                }
                else {
                    $clabeBancoDestino = '000000000000000000';
                    $codigoBancoDestino = substr($clabeBancoDestino, 0, 3);
                    $sucursalBancoDestino = $infopagoencontrado[0]->SucursalDestino != null ? $infopagoencontrado[0]->SucursalDestino : substr($clabeBancoDestino, 3, 3);
                    /* $numeroCuentaDestino = substr($clabeBancoDestino, 6, 11); */
                    $numeroCuentaDestino = substr($clabeBancoDestino, 10, 7);
                    $digitoControlCuentaDestino = substr($clabeBancoDestino, 17);
                }
            }

            /* $clabeBancoOrigen = $infopagoencontrado[0]->ClabeOrigen != null ? count($infopagoencontrado[0]->ClabeOrigen) < 18 ? str_pad_unicode($infopagoencontrado[0]->ClabeOrigen, 18, '0', STR_PAD_LEFT) : $infopagoencontrado[0]->ClabeOrigen : '000000000000000000';
            $clabeBancoDestino = $infopagoencontrado[0]->ClabeDestino != null ? count($infopagoencontrado[0]->ClabeDestino) < 18 ? str_pad_unicode($infopagoencontrado[0]->ClabeDestino, 18, '0', STR_PAD_LEFT) : $infopagoencontrado[0]->ClabeDestino : '000000000000000000'; */
            
            $datosLayout["clabeBancoOrigen"][$x] = $clabeBancoOrigen;
            $datosLayout["clabeBancoDestino"][$x] = $clabeBancoDestino;
            $datosLayout["codigoBancoOrigen"][$x] = $codigoBancoOrigen;
            $datosLayout["codigoBancoDestino"][$x] = $codigoBancoDestino;
            $datosLayout["sucursal"][$x] = $sucursalBancoOrigen;
            $datosLayout["sucursalOrigen"][$x] = $sucursalBancoOrigen;
            $datosLayout["sucursalDestino"][$x] = $sucursalBancoDestino;

            /* $datosLayout["numeroCuenta"][$x] = $infopagoencontrado[0]->CuentaOrigen;
            $datosLayout["numeroCuentaOrigen"][$x] = $infopagoencontrado[0]->CuentaOrigen;
            $datosLayout["numeroCuentaDestino"][$x] = $infopagoencontrado[0]->CuentaDestino; */

            $datosLayout["numeroCuenta"][$x] = $numeroCuentaOrigen;
            $datosLayout["numeroCuentaOrigen"][$x] = $numeroCuentaOrigen;
            $datosLayout["numeroCuentaDestino"][$x] = $numeroCuentaDestino;
            $datosLayout["digitoControlCuentaOrigen"][$x] = $digitoControlCuentaOrigen;
            $datosLayout["digitoControlCuentaDestino"][$x] = $digitoControlCuentaDestino;
            $datosLayout["proveedor"][$x] = $infopagoencontrado[0]->Proveedor;
            $datosLayout["clabe"][$x] = $clabeBancoDestino;
            $datosLayout["referenciaNumerica"][$x] = $infopagoencontrado[0]->ReferenciaNumerica;
            /* $datosLayout["importe"][$x] = number_format($datosLayout["importe"][$x], 2, '',''); */
            $datosLayout["importe"][$x] = number_format($infopagoencontrado[0]->Importe, 2, '','');
        }

        /* return $datosLayout; */
        
        $layouturl = $layoutsusuario[0]->LinkLayout;
        $layout = fopen($layouturl, "rb");

        if ($layout) {
            $nuevolayout = fopen($urldestino, "a");
            if ($nuevolayout) {
                while (!feof($layout)) {
                    $layoutcontent = fread($layout, 1024 * 8);
                }
                
                if(count($datosLayout["idsFlw"]) > 1) {
                    $layoutcontent.= "\n";
                }

                for($x=0 ; $x<count($datosLayout["idsFlw"]) ; $x++) {
                    $datosLayout["layoutContent"][$x] = $layoutcontent;
                    fwrite($nuevolayout, $layoutcontent, 1024 * 8);
                    $contenidolayout = file_get_contents($urldestino);

                    $variables = array();
                    $valores = array();
                    for($y=0 ; $y<count($configlayout) ; $y++) {
                        $maxcaracteres = $configlayout[$y]->Longitud;
                        $countvalor = mb_strlen($datosLayout[$configlayout[$y]->NombreVariable][$x]);
                        $valor = $countvalor < $maxcaracteres ? str_pad_unicode($datosLayout[$configlayout[$y]->NombreVariable][$x], $maxcaracteres, $configlayout[$y]->Llenado != null ? $configlayout[$y]->Llenado : ' ', $configlayout[$y]->Alineacion == 1 ? STR_PAD_LEFT : STR_PAD_RIGHT) : mb_substr($datosLayout[$configlayout[$y]->NombreVariable][$x], 0, $maxcaracteres);
                        array_push ($variables, $configlayout[$y]->Etiqueta);
                        array_push ($valores, $valor);
                    }
                    $nuevocontenido = str_replace($variables, $valores, $contenidolayout);
                    file_put_contents($urldestino, $nuevocontenido);
                }

                fclose($nuevolayout);
            }
            fclose($layout);

            /* $codigoarchivo = "Layout_" . $IdUsuario . "_" . $RFC . "_" . $FechaServidor . "_" . $Consecutivo; */
            $codigoarchivo = $IdUsuario . $FechaServidor . $Consecutivo;
            $consecutivo = "";
            $resultado = subirArchivoNextcloud($nombrearchivonuevo, $urldestino, $RFC, $Servidor, $u_storage, $p_storage, "Administracion", "FinanzasTesoreria", "LayoutsTemporales", $codigoarchivo, $consecutivo);
            if ($resultado["archivo"]["error"] == 0) {
                $codigodocumento = $codigoarchivo . $consecutivo;
                $directorio = $RFC . '/' . "Administracion" . '/' . "FinanzasTesoreria" . '/' . "LayoutsTemporales";
                $target_path = $directorio . '/' . $codigodocumento . ".txt";
                $resultado["archivo"]["link"] = GetLinkArchivo($target_path, $Servidor, $u_storage, $p_storage);
                /* $resultado["datosLayout"] = $datosLayout; */
                if(count($IdsBancosOrigen) == 1) {
                    unlink($urldestino);
                }
            }
        }

        return $resultado;
    }

    function actualizarLayout($IdUsuario, $IdLayout, $RFC, $usuariostorage, $passwordstorage) {
        $servidor = getServidorNextcloud();
        
        $layoutactual = DB::select('SELECT * FROM mc_flw_layouts WHERE id = ?', [$IdLayout]);
        $pagoslayout = DB::select("SELECT mc_flw_pagos.*, DATE_FORMAT(mc_flw_pagos.Fecha, '%d%m%y') AS ReferenciaNumerica, IF(ISNULL(mc_flow_cliproctas.Clabe), CONCAT(REPLACE(mc_flow_cliproctas.Banco,', S.A.', ''),' ',
        SUBSTRING(mc_flow_cliproctas.Cuenta, -4)), CONCAT(REPLACE(mc_flow_cliproctas.Banco,', S.A.',''), ' ',
        SUBSTRING(mc_flow_cliproctas.Clabe, -4))) AS CuentaBeneficiaria,
        mc_flow_bancuentas.IdBanco AS IdBancoOrigen,
        IF(!ISNULL(mc_flow_bancuentas.Clabe), mc_flow_bancuentas.Clabe, mc_flow_bancuentas.Cuenta) AS CuentaOrigen,
        IF(!ISNULL(mc_flow_cliproctas.Clabe), mc_flow_cliproctas.Clabe, mc_flow_cliproctas.Cuenta) AS CuentaDestino,
        (SELECT mc_flw_layouts_usuarios.IdLayoutConfig FROM mc_flw_layouts_usuarios WHERE
        mc_flw_layouts_usuarios.IdUsuario = mc_flw_pagos.IdUsuario AND mc_flw_layouts_usuarios.IdBanco = IdBancoOrigen) 
        AS IdLayoutConfig FROM mc_flw_pagos 
        LEFT JOIN mc_flow_cliproctas ON mc_flow_cliproctas.Id = mc_flw_pagos.IdCuentaDestino
        LEFT JOIN mc_flow_bancuentas ON mc_flw_pagos.IdCuentaOrigen = mc_flow_bancuentas.IdCuenta
        WHERE mc_flw_pagos.IdLayout = ?", [$IdLayout]);

        $datosEliminacionLayoutAntiguo = '';
        if($layoutactual[0]->UrlLayout != null && $layoutactual[0]->NombreLayout != null) {
            $rutaarchivo = $layoutactual[0]->UrlLayout . "/" . $layoutactual[0]->NombreLayout;
            $datosEliminacionLayoutAntiguo = eliminaArchivoNextcloud($servidor, $usuariostorage, $passwordstorage, $rutaarchivo);
        }

        $resultado = '';
        if(count($pagoslayout) > 0) {
            for($x=0 ; $x<count($pagoslayout) ; $x++) {
                $clabeBancoOrigen = $pagoslayout[$x]->CuentaOrigen != null ? count($pagoslayout[$x]->CuentaOrigen) < 18 ? str_pad_unicode($pagoslayout[$x]->CuentaOrigen, 18, '0', STR_PAD_LEFT) : $pagoslayout[$x]->CuentaOrigen : '000000000000000000';
                $clabeBancoDestino = $pagoslayout[$x]->CuentaDestino != null ? count($pagoslayout[$x]->CuentaDestino) < 18 ? str_pad_unicode($pagoslayout[$x]->CuentaDestino, 18, '0', STR_PAD_LEFT) : $pagoslayout[$x]->CuentaDestino : '000000000000000000';
                $codigoBancoOrigen = substr($clabeBancoOrigen, 0, 3);
                $codigoBancoDestino = substr($clabeBancoDestino, 0, 3);
                $sucursalBancoOrigen = substr($clabeBancoOrigen, 3, 3);
                $sucursalBancoDestino = substr($clabeBancoDestino, 3, 3);
                $numeroCuentaOrigen = substr($clabeBancoOrigen, 6, 11);
                $numeroCuentaDestino = substr($clabeBancoDestino, 6, 11);
                $digitoControlCuentaOrigen = substr($clabeBancoOrigen, 17);
                $digitoControlCuentaDestino = substr($clabeBancoDestino, 17);
                $datosLayout["clabeBancoOrigen"][$x] = $clabeBancoOrigen;
                $datosLayout["clabeBancoDestino"][$x] = $clabeBancoDestino;
                $datosLayout["codigoBancoOrigen"][$x] = $codigoBancoOrigen;
                $datosLayout["codigoBancoDestino"][$x] = $codigoBancoDestino;
                $datosLayout["sucursal"][$x] = $sucursalBancoOrigen;
                $datosLayout["sucursalOrigen"][$x] = $sucursalBancoOrigen;
                $datosLayout["sucursalDestino"][$x] = $sucursalBancoDestino;
                $datosLayout["numeroCuenta"][$x] = $numeroCuentaOrigen;
                $datosLayout["numeroCuentaOrigen"][$x] = $numeroCuentaOrigen;
                $datosLayout["numeroCuentaDestino"][$x] = $numeroCuentaDestino;
                $datosLayout["digitoControlCuentaOrigen"][$x] = $digitoControlCuentaOrigen;
                $datosLayout["digitoControlCuentaDestino"][$x] = $digitoControlCuentaDestino;
                $datosLayout["clabe"][$x] = $clabeBancoDestino;
                $datosLayout["cuentaBeneficiario"][$x] = $pagoslayout[$x]->CuentaBeneficiaria;
                $datosLayout["importe"][$x] = number_format($pagoslayout[$x]->Importe, 2, '','');
                $datosLayout["razon"][$x] = $pagoslayout[$x]->Proveedor;
    
                $datosLayout["nombre"][$x] = $pagoslayout[$x]->Proveedor;
                $datosLayout["proveedor"][$x] = $pagoslayout[$x]->Proveedor;
    
                $datosLayout["referenciaAlfanumerica"][$x] = "XXXXXAAAAA";
                $datosLayout["descripcion"][$x] = $pagoslayout[$x]->LlaveMatch;
                $datosLayout["referenciaNumerica"][$x] = $pagoslayout[$x]->ReferenciaNumerica;
    
                $datosLayout["numeroConsecutivo"][$x] = "00".($x+1);
                $datosLayout["motivoPago"][$x] = "prueba motivo";
                $datosLayout["indicadorComprobanteFiscal"][$x] = "1";
                $datosLayout["importeIVA"][$x] = "000";
                $datosLayout["RFC"][$x] = $pagoslayout[$x]->RFC;
                $datosLayout["llaveMatch"][$x] = $pagoslayout[$x]->LlaveMatch;
            }
    
            $configlayout = DB::select('SELECT mc_flw_layouts_config_content.*, mc_flw_layouts_config.LinkLayout FROM mc_flw_layouts_config_content INNER JOIN mc_flw_layouts_config
            ON mc_flw_layouts_config_content.IdLayoutConfig = mc_flw_layouts_config.id WHERE mc_flw_layouts_config_content.IdLayoutConfig = ? ORDER BY mc_flw_layouts_config_content.posicion', [$pagoslayout[0]->IdLayoutConfig != null ? $pagoslayout[0]->IdLayoutConfig : 1]);
    
            $FechaServidor = date("YmdHis");
            $CarpetaDestino = $_SERVER['DOCUMENT_ROOT'] . '/public/archivostemp/';
            mkdir($CarpetaDestino . "Layouts_" . $IdUsuario . "_" . $RFC . "_" . $FechaServidor, 0700);
            $CarpetaDestino = $CarpetaDestino . "Layouts_" . $IdUsuario . "_" . $RFC . "_" . $FechaServidor . "/";
            $nombrearchivonuevo = $layoutactual[0]->NombreLayout;
            $urldestino = $CarpetaDestino . $nombrearchivonuevo;
            $layouturl = $configlayout[0]->LinkLayout;
            $layout = fopen($layouturl, "rb");
    
            if ($layout) {
                $nuevolayout = fopen($urldestino, "a");
                if ($nuevolayout) {
                    while (!feof($layout)) {
                        $layoutcontent = fread($layout, 1024 * 8);
                    }

                    if(count($datosLayout["cuentaBeneficiario"]) > 1) {
                        $layoutcontent.= "\n";
                    }
                    
                    for($x=0 ; $x<count($datosLayout["cuentaBeneficiario"]) ; $x++) {
                        fwrite($nuevolayout, $layoutcontent, 1024 * 8);
                        $contenidolayout = file_get_contents($urldestino);
    
                        $variables = array();
                        $valores = array();
                        for($y=0 ; $y<count($configlayout) ; $y++) {
                            $maxcaracteres = $configlayout[$y]->Longitud;
                            $countvalor = mb_strlen($datosLayout[$configlayout[$y]->NombreVariable][$x]);
                            $valor = $countvalor < $maxcaracteres ? str_pad_unicode($datosLayout[$configlayout[$y]->NombreVariable][$x], $maxcaracteres, $configlayout[$y]->Llenado != null ? $configlayout[$y]->Llenado : ' ', $configlayout[$y]->Alineacion == 1 ? STR_PAD_LEFT : STR_PAD_RIGHT) : mb_substr($datosLayout[$configlayout[$y]->NombreVariable][$x], 0, $maxcaracteres);
                            array_push ($variables, $configlayout[$y]->Etiqueta);
                            array_push ($valores, $valor);
                        }
                        $nuevocontenido = str_replace($variables, $valores, $contenidolayout);
                        file_put_contents($urldestino, $nuevocontenido);
                    }
    
                    fclose($nuevolayout);
                }
                fclose($layout);
    
                $codigoarchivo = substr($layoutactual[0]->NombreLayout, 0, -4);
                $consecutivo = "";
                $resultado = subirArchivoNextcloud($nombrearchivonuevo, $urldestino, $RFC, $servidor, $usuariostorage, $passwordstorage, "Administracion", "FinanzasTesoreria", "LayoutsTemporales", $codigoarchivo, $consecutivo);
    
                if ($resultado["archivo"]["error"] == 0) {
                    $codigodocumento = $codigoarchivo . $consecutivo;
                    $directorio = $RFC . '/' . "Administracion" . '/' . "FinanzasTesoreria" . '/' . "LayoutsTemporales";
                    $target_path = $directorio . '/' . $codigodocumento . ".txt";
                    $link = GetLinkArchivo($target_path, $servidor, $usuariostorage, $passwordstorage);
                    $resultado["archivo"]["link"] = $link;
    
                    DB::table('mc_flw_layouts')->where("id", $IdLayout)->update(['UrlLayout' => $resultado["archivo"]["directorio"], 'NombreLayout' => $resultado["archivo"]["filename"], 'LinkLayout' => $link]);
                    unlink($urldestino);
                }
                $resultado["datosEliminacionLayoutAntiguo"] = $datosEliminacionLayoutAntiguo;
            }
            $urlcarpetaaborrar = substr($CarpetaDestino, 0, -1);
            rmdir($urlcarpetaaborrar);
        }
        else {
            DB::table('mc_flw_layouts')->where("id", $IdLayout)->delete();
        }

        return $resultado;
    }

    function str_pad_unicode($str, $pad_len, $pad_str = ' ', $dir = STR_PAD_RIGHT) {
        $str_len = mb_strlen($str);
        $pad_str_len = mb_strlen($pad_str);
        if (!$str_len && ($dir == STR_PAD_RIGHT || $dir == STR_PAD_LEFT)) {
            $str_len = 1; // @debug
        }
        if (!$pad_len || !$pad_str_len || $pad_len <= $str_len) {
            return $str;
        }
    
        $result = null;
        $repeat = ceil($str_len - $pad_str_len + $pad_len);
        if ($dir == STR_PAD_RIGHT) {
            $result = $str . str_repeat($pad_str, $repeat);
            $result = mb_substr($result, 0, $pad_len);
        } else if ($dir == STR_PAD_LEFT) {
            $result = str_repeat($pad_str, $repeat) . $str;
            $result = mb_substr($result, -$pad_len);
        } else if ($dir == STR_PAD_BOTH) {
            $length = ($pad_len - $str_len) / 2;
            $repeat = ceil($length / $pad_str_len);
            $result = mb_substr(str_repeat($pad_str, $repeat), 0, floor($length))
                        . $str
                           . mb_substr(str_repeat($pad_str, $repeat), 0, ceil($length));
        }
    
        return $result;
    }