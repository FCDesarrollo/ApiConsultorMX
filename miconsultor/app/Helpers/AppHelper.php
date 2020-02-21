<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
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

function GetNomCarpetaMenu($idmenu)
{
    $carpeta ="";
    $result = DB::connection("General")->select("SELECT nombre_carpeta FROM mc1004 WHERE idmenu=$idmenu");
    if (!empty($result)) {
        $carpeta = $result[0]->nombre_carpeta;
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

function subirArchivoNextcloud($archivo_name, $ruta_temp, $rfcempresa, $servidor, $usuario, $password,$carpetamodulo, $carpetamenu, $carpetasubmenu, $codarchivo, $consecutivo)
    {

        
        $directorio = $rfcempresa . '/'. $carpetamodulo .'/' . $carpetamenu . '/' . $carpetasubmenu;

        $ch = curl_init();
        $file = $archivo_name;
        $filename = $codarchivo . $consecutivo;
        $source = $ruta_temp; //Obtenemos un nombre temporal del archivo        
        $type = explode(".", $file);
        $target_path = $directorio . '/' . $filename . "." . $type[count($type) - 1];

        $gestor = fopen($source, "r");
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
        fclose($gestor);
        curl_close($ch);

        $array["archivo"]["target"] = $target_path;
        $array["archivo"]["codigo"] = $filename;
        $array["archivo"]["error"] = $error_no;

        return $array;
    }

    function GetLinkArchivo($link, $server, $user, $pass){
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
        $usuario = DB::select("SELECT * FROM mc1001 WHERE correo='$user' or cel='$user' AND status=1");
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
        $result = DB::connection("General")->select("SELECT servidor_storage FROM mc0000");
        if (!empty($result)) {
            $servidor = $result[0]->servidor_storage;
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
