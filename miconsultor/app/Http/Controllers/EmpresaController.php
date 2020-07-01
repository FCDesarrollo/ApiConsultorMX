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
                                                    WHERE m02.idusuario=$iduser AND m02.estatus=1");
            for ($i=0; $i < count($empresas); $i++) { 
                $empresaBD = $empresas[$i]->rutaempresa;
                ConnectaEmpresaDatabase($empresaBD);

                $perfil = DB::select('select nombre from mc_userprofile INNER JOIN mc_profiles ON mc_userprofile.idperfil = mc_profiles.idperfil
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
            $idusuario =  $valida[0]['usuario'][0]->idusuario;
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
                
                if (isset($request->valida)) { //PARA VINCULAR
                    $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$rfc]);
                    if (!empty($empresa)) {
                        $idempresa = $empresa[0]->idempresa;
                        $bdd = $empresa[0]->rutaempresa;
                        $fecha = date('Y-m-d');

                        $agregado = DB::connection("General")->select('select * from mc1002 where 
                                            idusuario = ? AND idempresa= ?', [$idusuario, $idempresa]);

                        if (!empty($agregado)) {
                            $array["error"] = 47; //ya esta vinculado
                        }else{
                            //INSERTA LA RELACION USUARIO Y EMPRESA
                            DB::connection("General")->insert('insert into mc1002 (idusuario, 
                            idempresa, estatus, fecha_vinculacion, idusuario_vinculador)
                            values (?, ?, ?, ?, ?)', [$idusuario, $idempresa, 1, $fecha, 0]);
                    
                            ConnectaEmpresaDatabase($bdd);

                            DB::insert('insert into mc_userprofile (idusuario, idperfil) values (?, ?)', [$idusuario, 1]);
                
                            $mc1007 = DB::connection("General")->select("SELECT * FROM mc1007 WHERE idperfil = 1");
                            for ($i=0; $i < count($mc1007); $i++) { 
                                DB::table('mc_usermod')->insertGetId(["idusuario" => $idusuario, "idperfil" => 1, 
                                    "idmodulo" => $mc1007[$i]->idmodulo, "tipopermiso" => $mc1007[$i]->tipopermiso]);
                            }
                
                            $mc1008 = DB::connection("General")->select("SELECT * FROM mc1008 WHERE idperfil = 1");
                            for ($i=0; $i < count($mc1008); $i++) { 
                                DB::table('mc_usermenu')->insertGetId(["idusuario" => $idusuario, "idperfil" => 1, 
                                    "idmodulo" => $mc1008[$i]->idmodulo, "idmenu" => $mc1008[$i]->idmenu, "tipopermiso" => $mc1008[$i]->tipopermiso]);
                            }
                
                            $mc1009 = DB::connection("General")->select("SELECT * FROM mc1009 WHERE idperfil = 1");
                            for ($i=0; $i < count($mc1009); $i++) { 
                                DB::table('mc_usersubmenu')->insertGetId(["idusuario" => $idusuario, "idperfil" => 1, "idmenu" => $mc1009[$i]->idmenu, 
                                    "idsubmenu" => $mc1009[$i]->idsubmenu, "tipopermiso" => $mc1009[$i]->tipopermiso]);
                            } 
                        }
                    }else {
                        $array["datos"] = 1; 
                    }
                }else{
                    $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$rfc]);
                    if (!empty($empresa)) {
                        $array["error"] = 41; //RFCEXISTE
                    }else {
                        $array["datos"] = $datosEmpresa;
                    }
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
        }elseif ($datos['ArregloCertificado']['result'] == 0) {
            $array[0]["error"] = 38;//No se logro validar el certificado
        }
        $fechavencido = date('Y-m-d', strtotime($datos['Arreglofecha']['fecha']));
        
        $now = date('Y-m-d');
        
        $dato = $datos['ArregloCertificado']['datos'][1];
        $dato = \explode("=", $dato);
        $empresa = $dato[1];

        $dato = $datos['ArregloCertificado']['datos'][6];
        $dato = \explode("=", $dato);
        $rfc = $dato[1];

        if (strtotime($now) > strtotime($fechavencido)) {
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
               if($certificado->getClientMimeType() == "application/x-x509-ca-cert" && $key->getClientMimeType() == "application/octet-stream"){
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

    public function registraEmpresa(Request $request)
    {
        $valida = verificaUsuario($request->usuario, $request->pwd);
          
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $rfc = $request->rfc;
            $idusuario =  $valida[0]['usuario'][0]->idusuario;
            $archivocer = $request->file('certificado');
            $archivokey = $request->file('key');
            $password = $request->password; 

            $validabdd = $this->GetBddDisponible();
            $array["error"] = $validabdd[0]["error"];
            
            if ($validabdd[0]['error'] == 0){
                $idasigna = $validabdd[0]["base"][0]->id;
                
                $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$rfc]);
                if (!empty($empresa)) {
                    $array["error"] = 41; //RFCEXISTE
                }else {
                    $bdd = $validabdd[0]["base"][0]->nombre;
                    $creotablas = $this->creaTablasEmpresa($bdd);
                    if ($creotablas == 0) {
                        $array["error"] = 43;
                    }else{
                        DB::connection("General")->table('mc1010')->where("id", $idasigna)->update(["rfc"=>$rfc,"estatus"=>"1"]);
                        $empresa = $request->nombreempresa;
                        
                        $fecha = date('Y-m-d');
                        
                        $password = password_hash($password, PASSWORD_BCRYPT);
                        $correo = $request->correo;
                        $vigencia = $request->fechavencimiento;
                        $userstorage = $rfc;
                        $passwordstorage = $request->password;

                        //INSERTA EL REGISTRO
                        $idempresa = DB::connection("General")->table('mc1000')->insertGetId(["nombreempresa" => $empresa,
                                                "rutaempresa" => $bdd, "RFC" => $rfc,"fecharegistro" => $fecha,
                                                "status" => 1, "password" => $password,"correo" => $correo,
                                                "empresaBD" => $bdd,"vigencia" => $vigencia,
                                                "usuario_storage" => $userstorage, "password_storage" => $passwordstorage]);
                        if ($idempresa != 0) {
                            //INSERTA LA RELACION USUARIO Y EMPRESA
                            DB::connection("General")->insert('insert into mc1002 (idusuario, 
                                    idempresa, estatus, fecha_vinculacion, idusuario_vinculador)
                                    values (?, ?, ?, ?, ?)', [$idusuario, $idempresa, 1, $fecha, 0]);
                            
                            ConnectaEmpresaDatabase($bdd);

                            DB::insert('insert into mc_userprofile (idusuario, idperfil) values (?, ?)', [$idusuario, 1]);
                
                            $mc1007 = DB::connection("General")->select("SELECT * FROM mc1007 WHERE idperfil = 1");
                            for ($i=0; $i < count($mc1007); $i++) { 
                                DB::table('mc_usermod')->insertGetId(["idusuario" => $idusuario, "idperfil" => 1, 
                                    "idmodulo" => $mc1007[$i]->idmodulo, "tipopermiso" => $mc1007[$i]->tipopermiso]);
                            }
                
                            $mc1008 = DB::connection("General")->select("SELECT * FROM mc1008 WHERE idperfil = 1");
                            for ($i=0; $i < count($mc1008); $i++) { 
                                DB::table('mc_usermenu')->insertGetId(["idusuario" => $idusuario, "idperfil" => 1, 
                                    "idmodulo" => $mc1008[$i]->idmodulo, "idmenu" => $mc1008[$i]->idmenu, "tipopermiso" => $mc1008[$i]->tipopermiso]);
                            }
                
                            $mc1009 = DB::connection("General")->select("SELECT * FROM mc1009 WHERE idperfil = 1");
                            for ($i=0; $i < count($mc1009); $i++) { 
                                DB::table('mc_usersubmenu')->insertGetId(["idusuario" => $idusuario, "idperfil" => 1, "idmenu" => $mc1009[$i]->idmenu, 
                                    "idsubmenu" => $mc1009[$i]->idsubmenu, "tipopermiso" => $mc1009[$i]->tipopermiso]);
                            } 

                            $validacarpetas = $this->creaCarpetas($rfc, $archivocer, $archivokey, $passwordstorage);
                            $array["error"] = $validacarpetas;
                        }else{
                            $array["error"] = 44; //ERROR AL REGISTRAR
                        }
                    }
                }
            }

        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function GetBddDisponible()
    {
        $array[0]["error"] = 0;
        $num = DB::connection("General")->select("SELECT count(*) FROM mc1010 WHERE rfc='' AND estatus=0");

        if ($num == 0) {
            $array[0]["error"] = 42;
        }else{
            $consulta = DB::connection("General")->select("SELECT * FROM mc1010 WHERE rfc='' AND estatus=0");    
        
            if (!empty($consulta)) {
                $array[0]["base"] = $consulta;
                
            }else{
                $array[0]["error"] = 42; //SIN BASES DE DATOS DISPONIBLES
            }
        }
        
      
        return $array;
    }

    public function creaTablasEmpresa($empresaBD)
    {       
        ConnectaEmpresaDatabase($empresaBD);                
        if ($empresaBD != "") {    

            $mc_almdigital = "create table if not exists mc_almdigital (
                id INT(11) NOT NULL AUTO_INCREMENT,
                fechadecarga DATETIME DEFAULT NULL,
                fechadocto DATE DEFAULT NULL,
                codigoalm VARCHAR(50) COLLATE utf8_spanish_ci DEFAULT NULL,
                idusuario INT(11) DEFAULT NULL,
                idmodulo INT(11) DEFAULT 0,
                idsucursal INT(11) DEFAULT NULL,
                observaciones VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                totalregistros INT(11) DEFAULT 0,
                totalcargados INT(11) DEFAULT 0,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;";
            DB::statement($mc_almdigital);

            $mc_almdigital_det = "create table if not exists mc_almdigital_det (
              id INT(11) NOT NULL AUTO_INCREMENT,
              idalmdigital INT(11) DEFAULT NULL,
              idsucursal INT(11) DEFAULT NULL,
              codigodocumento VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
              documento VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
              idagente INT(11) DEFAULT NULL,
              fechaprocesado DATETIME DEFAULT NULL,
              estatus INT(11) DEFAULT 0,
              download VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
              idrubro INT(11) DEFAULT 0,
              conceptoadw VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
              folioadw INT(11) DEFAULT 0,
              serieadw VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;";
            DB::statement($mc_almdigital_det);

            $mc_bitcontabilidad = "create table if not exists mc_bitcontabilidad (
              id INT(11) NOT NULL AUTO_INCREMENT,
              idsubmenu INT(11) DEFAULT NULL,
              tipodocumento VARCHAR(255) DEFAULT NULL,
              periodo INT(11) DEFAULT NULL,
              ejercicio INT(11) DEFAULT NULL,
              fecha DATE DEFAULT NULL,
              fechamodificacion DATETIME DEFAULT NULL,
              archivo VARCHAR(255) DEFAULT NULL,
              nombrearchivoG VARCHAR(255) DEFAULT NULL,
              idusuarioG INT(11) DEFAULT NULL,
              status INT(11) DEFAULT NULL,
              idusuarioE INT(11) DEFAULT NULL,
              nombrearchivoE VARCHAR(255) DEFAULT NULL,
              fechacorte DATE DEFAULT NULL,
              fechaentregado DATE DEFAULT NULL,
              PRIMARY KEY (id)
            ) ENGINE=MYISAM DEFAULT CHARSET=latin1;";
            DB::statement($mc_bitcontabilidad);            

            $mc_bitcontabilidad_det = "create table if not exists mc_bitcontabilidad_det (
              id INT(11) NOT NULL AUTO_INCREMENT,
              idbitacora INT(11) DEFAULT NULL,
              nombrearchivoE VARCHAR(255) CHARACTER SET latin1 DEFAULT NULL,
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_bitcontabilidad_det); 

            $mc_catclienprov = "create table if not exists mc_catclienprov (
              id INT(11) NOT NULL AUTO_INCREMENT,
              codigoc VARCHAR(30) COLLATE latin1_spanish_ci DEFAULT NULL,
              rfc VARCHAR(15) COLLATE latin1_spanish_ci DEFAULT NULL,
              razonsocial VARCHAR(150) COLLATE latin1_spanish_ci DEFAULT NULL,
              tipocli INT(11) DEFAULT NULL,
              campoextra1 VARCHAR(150) COLLATE latin1_spanish_ci DEFAULT NULL,
              campoextra2 VARCHAR(150) COLLATE latin1_spanish_ci DEFAULT NULL,
              campoextra3 VARCHAR(150) COLLATE latin1_spanish_ci DEFAULT NULL,
              sincronizado INT(11) DEFAULT '0',
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_catclienprov); 

            $mc_catconceptos = "create table if not exists mc_catconceptos (
              id INT(11) NOT NULL AUTO_INCREMENT,
              codigoconcepto VARCHAR(50) COLLATE latin1_spanish_ci DEFAULT NULL,
              nombreconcepto VARCHAR(150) COLLATE latin1_spanish_ci DEFAULT NULL,
              codigoadw VARCHAR(50) COLLATE latin1_spanish_ci DEFAULT NULL,
              nombreadw VARCHAR(150) COLLATE latin1_spanish_ci DEFAULT NULL,
              campoextra1 VARCHAR(150) COLLATE latin1_spanish_ci DEFAULT NULL,
              campoextra2 VARCHAR(150) COLLATE latin1_spanish_ci DEFAULT NULL,
              campoextra3 VARCHAR(150) COLLATE latin1_spanish_ci DEFAULT NULL,
              sincronizado INT(11) DEFAULT '0',
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_catconceptos); 
            
            $mc_catproductos = "create table if not exists mc_catproductos (
              id INT(11) NOT NULL AUTO_INCREMENT,
              codigoprod VARCHAR(50) COLLATE latin1_spanish_ci DEFAULT NULL,
              nombreprod VARCHAR(150) COLLATE latin1_spanish_ci DEFAULT NULL,
              fechaalta DATE DEFAULT NULL,
              codigoadw VARCHAR(50) COLLATE latin1_spanish_ci DEFAULT NULL,
              nombreadw VARCHAR(150) COLLATE latin1_spanish_ci DEFAULT NULL,
              campoextra1 VARCHAR(150) COLLATE latin1_spanish_ci DEFAULT NULL,
              campoextra2 VARCHAR(150) COLLATE latin1_spanish_ci DEFAULT NULL,
              campoextra3 VARCHAR(150) COLLATE latin1_spanish_ci DEFAULT NULL,
              sincronizado INT(11) DEFAULT '0',
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_catproductos); 
            
            $mc_catsucursales = "create table if not exists mc_catsucursales (
              idsucursal INT(11) NOT NULL AUTO_INCREMENT,
              sucursal VARCHAR(100) COLLATE latin1_spanish_ci DEFAULT NULL,
              rutaadw VARCHAR(250) COLLATE latin1_spanish_ci DEFAULT NULL,
              sincronizado INT(11) DEFAULT '0',
              PRIMARY KEY (idsucursal)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_catsucursales); 

           
            
            $mc_lotes = "create table if not exists mc_lotes (
              id INT(11) NOT NULL AUTO_INCREMENT,
              fechadecarga DATE DEFAULT NULL,
              codigolote VARCHAR(50) COLLATE latin1_spanish_ci DEFAULT NULL,
              usuario INT(11) DEFAULT NULL,
              tipo INT(11) DEFAULT NULL,
              totalregistros INT(11) DEFAULT '0',
              totalcargados INT(11) DEFAULT '0',
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_lotes);                                                 

            $mc_lotesdocto = "create table if not exists mc_lotesdocto (
              id INT(11) NOT NULL AUTO_INCREMENT,
              idlote INT(11) DEFAULT NULL,
              idadw INT(11) DEFAULT NULL,
              sucursal VARCHAR(100) COLLATE latin1_spanish_ci DEFAULT NULL,
              codigo VARCHAR(100) CHARACTER SET latin1 DEFAULT NULL,
              concepto VARCHAR(150) COLLATE latin1_spanish_ci DEFAULT NULL,
              proveedor VARCHAR(150) CHARACTER SET latin1 DEFAULT NULL,
              fecha DATE DEFAULT NULL,
              folio VARCHAR(100) COLLATE latin1_spanish_ci DEFAULT NULL,
              serie VARCHAR(100) CHARACTER SET latin1 DEFAULT NULL,
              subtotal DECIMAL(10,2) DEFAULT NULL,
              descuento DECIMAL(10,2) DEFAULT NULL,
              iva DECIMAL(10,2) DEFAULT NULL,
              total DECIMAL(10,2) DEFAULT NULL,
              campoextra1 VARCHAR(150) CHARACTER SET latin1 DEFAULT NULL,
              campoextra2 VARCHAR(150) CHARACTER SET latin1 DEFAULT NULL,
              campoextra3 VARCHAR(150) CHARACTER SET latin1 DEFAULT NULL,
              idsupervisor INT(11) DEFAULT NULL,
              estatus INT(11) DEFAULT '0',
              error INT(11) DEFAULT NULL,
              detalle_error VARCHAR(150) COLLATE latin1_spanish_ci DEFAULT NULL,
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_lotesdocto);   

            $mc_lotesmovtos = "create table if not exists mc_lotesmovtos (
              id INT(11) NOT NULL AUTO_INCREMENT,
              iddocto INT(11) DEFAULT NULL,
              idlote INT(11) DEFAULT NULL,
              fechamov DATE DEFAULT NULL,
              producto VARCHAR(100) DEFAULT NULL,
              almacen VARCHAR(50) DEFAULT NULL,
              kilometros VARCHAR(50) DEFAULT NULL,
              horometro VARCHAR(50) DEFAULT NULL,
              unidad VARCHAR(50) DEFAULT NULL,
              cantidad VARCHAR(50) DEFAULT NULL,
              subtotal DECIMAL(10,2) DEFAULT NULL,
              descuento DECIMAL(10,2) DEFAULT NULL,
              iva DECIMAL(10,2) DEFAULT NULL,
              total DECIMAL(10,2) DEFAULT NULL,
              PRIMARY KEY (id)
            ) ENGINE=MYISAM DEFAULT CHARSET=latin1;";
            DB::statement($mc_lotesmovtos);   

            $mc_menupermis = "create table if not exists mc_menupermis (
              id INT(11) NOT NULL AUTO_INCREMENT,
              idperfil INT(11) DEFAULT NULL,
              idmodulo INT(11) DEFAULT NULL,
              idmenu INT(11) DEFAULT NULL,
              tipopermiso INT(11) DEFAULT NULL,
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_menupermis);   

            $mc_modpermis = "create table if not exists mc_modpermis (
              id INT(11) NOT NULL AUTO_INCREMENT,
              idperfil INT(11) DEFAULT NULL,
              idmodulo INT(11) DEFAULT NULL,
              tipopermiso INT(11) DEFAULT NULL,
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_modpermis);   

            $mc_profiles = "create table if not exists mc_profiles (
              id INT(11) NOT NULL AUTO_INCREMENT,
              idperfil INT(11) NOT NULL,
              nombre VARCHAR(120) COLLATE latin1_spanish_ci DEFAULT NULL,
              descripcion VARCHAR(254) COLLATE latin1_spanish_ci DEFAULT NULL,
              fecha DATE DEFAULT NULL,
              status INT(11) DEFAULT '1',
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_profiles);   

            $mc_rubros = "create table if not exists mc_rubros (
              id INT(11) NOT NULL AUTO_INCREMENT,
              clave VARCHAR(30) COLLATE latin1_spanish_ci DEFAULT NULL,
              nombre VARCHAR(50) COLLATE latin1_spanish_ci DEFAULT NULL,
              tipo INT(11) DEFAULT NULL,
              status INT(11) DEFAULT '0',
              idmenu INT(11) DEFAULT NULL,
              idsubmenu INT(11) DEFAULT NULL,
              claveplantilla INT(11) DEFAULT NULL,
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_rubros);   

            $mc_submenupermis = "create table if not exists mc_submenupermis (
              id INT(11) NOT NULL AUTO_INCREMENT,
              idperfil INT(11) DEFAULT NULL,
              idmenu INT(11) DEFAULT NULL,
              idsubmenu INT(11) DEFAULT NULL,
              tipopermiso INT(11) DEFAULT NULL,
              notificaciones INT(11) DEFAULT '0',
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_submenupermis);   

            $mc_usermenu = "create table if not exists mc_usermenu (
              id INT(11) NOT NULL AUTO_INCREMENT,
              idusuario INT(11) NOT NULL,
              idperfil INT(11) NOT NULL,
              idmodulo INT(11) NOT NULL,
              idmenu INT(11) NOT NULL,
              tipopermiso INT(11) NOT NULL,
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_usermenu);

            $mc_usermod = "create table if not exists mc_usermod (
              id INT(11) NOT NULL AUTO_INCREMENT,
              idusuario INT(11) DEFAULT NULL,
              idperfil INT(11) DEFAULT NULL,
              idmodulo INT(11) DEFAULT NULL,
              tipopermiso INT(11) DEFAULT NULL,
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_usermod);                                                                                                    

            $mc_userprofile = "create table if not exists mc_userprofile (
              id INT(11) NOT NULL AUTO_INCREMENT,
              idusuario INT(11) DEFAULT NULL,
              idperfil INT(11) DEFAULT NULL,
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_userprofile);           

            $mc_usersubmenu = "create table if not exists mc_usersubmenu (
              id INT(11) NOT NULL AUTO_INCREMENT,
              idusuario INT(11) NOT NULL,
              idperfil INT(11) NOT NULL,
              idmenu INT(11) NOT NULL,
              idsubmenu INT(11) NOT NULL,
              tipopermiso INT(11) NOT NULL,
              notificaciones INT(11) DEFAULT '0',
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_usersubmenu);

            $mc_almdigital_doc = "create table if not exists mc_almdigital_doc (
              idalmdigitaldet INT(11) NOT NULL,
              iddocadw INT(11) NOT NULL,
              idrubro INT(11) DEFAULT NULL,
              conceptoadw VARCHAR(255) COLLATE latin1_spanish_ci DEFAULT NULL,
              folioadw INT(11) DEFAULT NULL,
              serieadw VARCHAR(255) COLLATE latin1_spanish_ci DEFAULT NULL
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_almdigital_doc);
            
            $mc_conceptos = "create table if not exists mc_conceptos (
                id int(11) NOT NULL AUTO_INCREMENT,
                idsubmenu int(11) DEFAULT NULL,
                nombre_concepto varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                descripcion varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                fecha date DEFAULT NULL,
                status int(11) DEFAULT NULL,
                concepto_relacion int(11) DEFAULT NULL,
                PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
                DB::statement($mc_conceptos);

            $mc_requerimientos = "create table if not exists mc_requerimientos (
                idReq int(11) NOT NULL AUTO_INCREMENT,
                id_sucursal int(11) DEFAULT NULL,
                fecha date DEFAULT NULL,
                id_usuario int(11) DEFAULT NULL,
                fecha_req date DEFAULT NULL,
                id_departamento int(11) DEFAULT NULL,
                descripcion varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                importe_estimado double DEFAULT NULL,
                estado_documento int(11) DEFAULT NULL,
                id_concepto int(11) DEFAULT NULL,
                serie varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                folio varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                estatus int(11) DEFAULT NULL,
                PRIMARY KEY (idReq)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
                DB::statement($mc_requerimientos);

              $mc_requerimientos_bit = "create table if not exists mc_requerimientos_bit (
                id_bit int(11) NOT NULL AUTO_INCREMENT,
                id_req int(11) DEFAULT NULL,
                id_usuario int(11) DEFAULT NULL,
                fecha date DEFAULT NULL,
                observaciones varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                status int(11) DEFAULT NULL,
                PRIMARY KEY (id_bit)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
              DB::statement($mc_requerimientos_bit);

              $mc_requerimientos_doc = "create table if not exists mc_requerimientos_doc (
                id int(11) NOT NULL AUTO_INCREMENT,
                id_usuario int(11) DEFAULT NULL,
                id_req int(11) DEFAULT NULL,
                documento varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                codigo_documento varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                tipo_doc int(11) DEFAULT NULL,
                download varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
              DB::statement($mc_requerimientos_doc);

              $mc_usuarios_concepto = "create table if not exists mc_usuarios_concepto(
                id_usuario int(11) DEFAULT NULL,
                id_concepto int(11) DEFAULT NULL
              ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
              DB::statement($mc_usuarios_concepto);

              $mc_notificaciones = "create table if not exists mc_notificaciones(
                id int(11) NOT NULL AUTO_INCREMENT,
                idusuario int(11) NOT NULL,
                encabezado text NOT NULL,
                mensaje text NOT NULL,
                fecha timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                idmodulo int(11) NOT NULL,
                idmenu int(11) NOT NULL,
                idsubmenu int(11) NOT NULL,
                idregistro int(11) NOT NULL,
                PRIMARY KEY (id)
              ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
              DB::statement($mc_notificaciones);

              $mc_notificaciones_det = "create table if not exists mc_notificaciones_det(
                id int(11) NOT NULL AUTO_INCREMENT,
                idusuario int(11) NOT NULL,
                idnotificacion int(11) NOT NULL,
                status int(11) DEFAULT 0 COMMENT '0 = no visto, 1 = visto',
                PRIMARY KEY (id)
              ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
              DB::statement($mc_notificaciones_det);

              $mc_usuarios_limite_gastos = "create table if not exists mc_usuarios_limite_gastos (
                id int(11) not null auto_increment,
                id_usuario int(11) not null,
                id_concepto int(11) not null,
                importe float not null,
                primary key (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
              DB::statement($mc_usuarios_limite_gastos);

              $mc_config_time = "create table if not exists mc_config_timeapp(
                idusuario int(11) DEFAULT NULL,
                idsubmenu int(11) DEFAULT NULL,
                tiempo_dias int(11) DEFAULT NULL
              ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
              DB::statement($mc_config_time);
              

            $mc1006 = "insert ".$empresaBD.".mc_profiles SELECT * FROM dublockc_MCGenerales.mc1006;";
            DB::statement($mc1006);

            $mc1007 = "insert ".$empresaBD.".mc_modpermis SELECT * FROM dublockc_MCGenerales.mc1007;";
            DB::statement($mc1007);
            
            $mc1008 = "insert ".$empresaBD.".mc_menupermis SELECT * FROM dublockc_MCGenerales.mc1008;";
            DB::statement($mc1008);
            
            $mc1009 = "insert ".$empresaBD.".mc_submenupermis SELECT * FROM dublockc_MCGenerales.mc1009;";
            DB::statement($mc1009);

            $mc1013 = "insert ".$empresaBD.".mc_rubros SELECT * FROM dublockc_MCGenerales.mc1013;";
            DB::statement($mc1013);
            
            $SucTemp = "insert into mc_catsucursales (sucursal) VALUES ('TEMPORAL')";
            DB::statement($SucTemp);

            $conceptos = "insert ".$empresaBD.".mc_conceptos SELECT * FROM dublockc_MCGenerales.mc1014;";
            DB::statement($conceptos);
            //$mc1014 = "insert ".$empresaBD.".mc_conceptos SELECT * FROM dublockc_MCGenerales.mc1014;";
            //DB::statement($mc1014);
            
            $id = 1;
        }else {
            $id = 0;            
        }
        return $id;
    }

    public function creaCarpetas($rfc, $certificado, $key, $pass)
    {
        set_time_limit(300);
        $error = 0;
        $datosParam = getParametros();
        if ($datosParam != "") {
            $servercloud = $datosParam[0]->servidor_storage;
            $usercloud = $datosParam[0]->usuario_storage;
            $passcloud = $datosParam[0]->password_storage;

            

            //CREA USUARIO
            $ch = curl_init();
            $DatosUser = array("userid" => $rfc, "password" => $pass);
            curl_setopt($ch, CURLOPT_URL, "https://".$usercloud.":".$passcloud."@".$servercloud."/ocs/v1.php/cloud/users");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $DatosUser);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('OCS-APIRequest:true'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $error = ($response == false ? 46 : 0 );
            curl_close($ch);

            if ($error == 0) {
                //CREA CARPETAS
                $ch = curl_init();
                $url = 'https://'.$servercloud.'/remote.php/dav/files/'.$rfc.'/CRM';
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_VERBOSE, 1);
                curl_setopt($ch, CURLOPT_USERPWD, $rfc.':'.$pass);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "MKCOL");
                $response = curl_exec($ch);
                
                $url = 'https://'.$servercloud.'/remote.php/dav/files/'.$rfc.'/CRM/'. $rfc;
                curl_setopt($ch, CURLOPT_URL, $url);
                $response = curl_exec($ch);

                $modulos = DB::connection("General")->select('select idmodulo,nombre_carpeta from mc1003');
                for ($i=0; $i < count($modulos); $i++) { 
                    $idmodulo = $modulos[$i]->idmodulo;
                    $carpetamodulo = $modulos[$i]->nombre_carpeta;

                    $url = 'https://'.$servercloud.'/remote.php/dav/files/'.$rfc.'/CRM/'. $rfc.'/'.$carpetamodulo;
                    curl_setopt($ch, CURLOPT_URL, $url);
                    $response = curl_exec($ch);

                    $menus = DB::connection("General")->select('select idmenu,nombre_carpeta from mc1004 
                                                    where idmodulo = ?', [$idmodulo]);
                    for ($x=0; $x < count($menus); $x++) { 
                        $idmenu = $menus[$x]->idmenu;
                        $carpetamenu = $menus[$x]->nombre_carpeta;

                        $url = 'https://'.$servercloud.'/remote.php/dav/files/'.$rfc.'/CRM/'. $rfc.'/'.$carpetamodulo.'/'.$carpetamenu;
                        curl_setopt($ch, CURLOPT_URL, $url);
                        $response = curl_exec($ch);

                        $submenus =DB::connection("General")->select('select nombre_carpeta from mc1005
                                             where idmenu = ?', [$idmenu]);
                        for ($z=0; $z < count($submenus); $z++) { 
                            $carpetasubmenu = $submenus[$z]->nombre_carpeta;

                            $url = 'https://'.$servercloud.'/remote.php/dav/files/'.$rfc.'/CRM/'. $rfc.'/'.$carpetamodulo.'/'.$carpetamenu.'/'.$carpetasubmenu;
                            curl_setopt($ch, CURLOPT_URL, $url);
                            $response = curl_exec($ch);
                        }
                    }

                }
                
                //SUBIR ARCHIVOS
                $gestor = fopen($certificado, "r");
                $contenido = fread($gestor, filesize($certificado));
                fclose($gestor);
                curl_setopt_array($ch,
                    array(
                        CURLOPT_URL => 'https://'.$servercloud.'/remote.php/dav/files/'.$rfc.'/CRM/'. $rfc .'/'. $certificado->getClientOriginalName(),
                        CURLOPT_VERBOSE => 1,
                        CURLOPT_USERPWD => $rfc.':'.$pass,
                        CURLOPT_POSTFIELDS => $contenido,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_BINARYTRANSFER => true,
                        CURLOPT_CUSTOMREQUEST => 'PUT',
                        )
                );
                $response = curl_exec($ch);

                $gestor = fopen($key, "r");
                $contenido = fread($gestor, filesize($key));
                fclose($gestor);
                curl_setopt_array($ch,
                    array(
                        CURLOPT_URL => 'https://'.$servercloud.'/remote.php/dav/files/'.$rfc.'/CRM/'. $rfc .'/'. $key->getClientOriginalName(),
                        CURLOPT_VERBOSE => 1,
                        CURLOPT_USERPWD => $rfc.':'.$pass,
                        CURLOPT_POSTFIELDS => $contenido,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_BINARYTRANSFER => true,
                        CURLOPT_CUSTOMREQUEST => 'PUT',
                        )
                );
                $response = curl_exec($ch);

                $contenido = $pass;
                curl_setopt_array($ch,
                    array(
                        CURLOPT_URL => 'https://'.$servercloud.'/remote.php/dav/files/'.$rfc.'/CRM/'. $rfc .'/'. $rfc.'.txt',
                        CURLOPT_VERBOSE => 1,
                        CURLOPT_USERPWD => $rfc.':'.$pass,
                        CURLOPT_POSTFIELDS => $contenido,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_BINARYTRANSFER => true,
                        CURLOPT_CUSTOMREQUEST => 'PUT',
                        )
                );
                $response = curl_exec($ch);

                $error = ($response != '' ? 46 : 0 );
                curl_close($ch);
            }
        }else{
            $error = 45;
        }
        return $error;
    }

    public function datosEmpresa(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $rfc = $request->rfc;
            $empresa = DB::select('select * from mc1000 where rfc = ?', [$rfc]);
            $array[0]['empresa'] = $empresa;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
