<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Mail;
use App\Mail\MensajesValidacion;
use App\Mail\MensajesGenerales;
use App\Mail\MensajesLayouts;
use App\Mail\MensajesLayoutsPagosAdicionales;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class EmpresaController extends Controller
{
    function listaEmpresasUsuario(Request $request)
    {
        $valida = verificaUsuario($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $usuario = $valida[0]['usuario'];
            $iduser = $usuario[0]->idusuario;
            $array["usuario"] = $valida[0]['usuario'];
            $empresas = DB::connection("General")->select("SELECT mc1000.* FROM mc1002 m02 
                                                    INNER JOIN mc1000 on m02.idempresa=mc1000.idempresa 
                                                    WHERE m02.idusuario=$iduser AND m02.estatus=1");
            for ($i = 0; $i < count($empresas); $i++) {
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

        if ($valida[0]['error'] == 0) {
            $idusuario =  $valida[0]['usuario'][0]->idusuario;
            $conexionFTP = conectaFTP();
            if ($conexionFTP != '') {

                $archivocer = $request->file('certificado');
                $archivokey = $request->file('key');

                $passwordcer = $request->passwordcertificado;

                $resSubirArchivos = $this->subeCertificados($conexionFTP, $archivocer, $archivokey);
                $array["error"] = $resSubirArchivos;

                ftp_close($conexionFTP);

                $resDatos = $this->verificaDatosCertificados($archivocer->getClientOriginalName(), $archivokey->getClientOriginalName(), $passwordcer);
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
                        } else {
                            //INSERTA LA RELACION USUARIO Y EMPRESA
                            DB::connection("General")->insert('insert into mc1002 (idusuario, 
                            idempresa, estatus, fecha_vinculacion, idusuario_vinculador)
                            values (?, ?, ?, ?, ?)', [$idusuario, $idempresa, 1, $fecha, 0]);

                            ConnectaEmpresaDatabase($bdd);

                            DB::insert('insert into mc_userprofile (idusuario, idperfil) values (?, ?)', [$idusuario, 1]);

                            $mc1007 = DB::connection("General")->select("SELECT * FROM mc1007 WHERE idperfil = 1");
                            for ($i = 0; $i < count($mc1007); $i++) {
                                DB::table('mc_usermod')->insertGetId([
                                    "idusuario" => $idusuario, "idperfil" => 1,
                                    "idmodulo" => $mc1007[$i]->idmodulo, "tipopermiso" => $mc1007[$i]->tipopermiso
                                ]);
                            }

                            $mc1008 = DB::connection("General")->select("SELECT * FROM mc1008 WHERE idperfil = 1");
                            for ($i = 0; $i < count($mc1008); $i++) {
                                DB::table('mc_usermenu')->insertGetId([
                                    "idusuario" => $idusuario, "idperfil" => 1,
                                    "idmodulo" => $mc1008[$i]->idmodulo, "idmenu" => $mc1008[$i]->idmenu, "tipopermiso" => $mc1008[$i]->tipopermiso
                                ]);
                            }

                            $mc1009 = DB::connection("General")->select("SELECT * FROM mc1009 WHERE idperfil = 1");
                            for ($i = 0; $i < count($mc1009); $i++) {
                                DB::table('mc_usersubmenu')->insertGetId([
                                    "idusuario" => $idusuario, "idperfil" => 1, "idmenu" => $mc1009[$i]->idmenu,
                                    "idsubmenu" => $mc1009[$i]->idsubmenu, "tipopermiso" => $mc1009[$i]->tipopermiso
                                ]);
                            }
                        }
                    } else {
                        $array["datos"] = 1;
                    }
                } else {
                    $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$rfc]);
                    if (!empty($empresa)) {
                        $array["error"] = 41; //RFCEXISTE
                    } else {
                        $array["datos"] = $datosEmpresa;
                    }
                }
            } else {
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
        } elseif ($datos['Arreglofecha']['result'] == 0) {
            $array[0]["error"] = 36; //No se logro obtener la fecha de vigencia del certificado
        } elseif ($datos['KeyPemR']['result'] == 0) {
            $array[0]["error"] = 37; //Contraseña incorrecta
        } elseif ($datos['ArregloCertificado']['result'] == 0) {
            $array[0]["error"] = 38; //No se logro validar el certificado
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

        $nombrecertificado = $temp . '/' . $certificado->getClientOriginalName();
        $nombrekey = $temp . '/' . $key->getClientOriginalName();

        $nombrecertificadotemp = $certificado;
        $nombrekeytemp = $key;

        if (ftp_mkdir($conexionFTP, $temp)) {
            if (ftp_chmod($conexionFTP, 0777, $temp) !== false) {
                if ($certificado->getClientMimeType() == "application/x-x509-ca-cert" && $key->getClientMimeType() == "application/octet-stream") {
                    if (ftp_put($conexionFTP, $nombrecertificado, $nombrecertificadotemp, FTP_BINARY)) {
                        if (!ftp_put($conexionFTP, $nombrekey, $nombrekeytemp, FTP_BINARY)) {
                            $error = 33; //KEY MAL
                        }
                    } else {
                        $error = 32; //CERTIFICADO MAL
                    }
                } else {
                    if (ftp_rmdir($conexionFTP, $temp)) { }
                    $error = 31; //NO SON ARCHIVOS VALIDOS
                }
            }
        } else {
            $error = 34;
        }
        return $error;
    }

    public function enviaCorreoEmpresa(Request $request)
    {
        $valida = verificaUsuario($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
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

        if ($valida[0]['error'] == 0) {
            $rfc = $request->rfc;
            $idusuario =  $valida[0]['usuario'][0]->idusuario;
            $archivocer = $request->file('certificado');
            $archivokey = $request->file('key');
            $password = $request->password;

            $validabdd = $this->GetBddDisponible();
            $array["error"] = $validabdd[0]["error"];

            if ($validabdd[0]['error'] == 0) {
                $idasigna = $validabdd[0]["base"][0]->id;

                $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$rfc]);
                if (!empty($empresa)) {
                    $array["error"] = 41; //RFCEXISTE
                } else {
                    $bdd = $validabdd[0]["base"][0]->nombre;
                    $creotablas = $this->creaTablasEmpresa($bdd);
                    if ($creotablas == 0) {
                        $array["error"] = 43;
                    } else {
                        DB::connection("General")->table('mc1010')->where("id", $idasigna)->update(["rfc" => $rfc, "estatus" => "1"]);
                        $empresa = $request->nombreempresa;

                        $fecha = date('Y-m-d');

                        $password = password_hash($password, PASSWORD_BCRYPT);
                        $correo = $request->correo;
                        $vigencia = $request->fechavencimiento;
                        $userstorage = $rfc;
                        /* $passwordstorage = $request->password; */
                        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        $contraNextCloud = substr(str_shuffle($permitted_chars), 0, 10);

                        //INSERTA EL REGISTRO
                        $idempresa = DB::connection("General")->table('mc1000')->insertGetId([
                            "nombreempresa" => $empresa,
                            "rutaempresa" => $bdd, "RFC" => $rfc, "fecharegistro" => $fecha,
                            "status" => 1, "password" => $password, "correo" => $correo,
                            "empresaBD" => $bdd, "vigencia" => $vigencia,
                            "usuario_storage" => $userstorage, "password_storage" => $contraNextCloud/* $passwordstorage */
                        ]);
                        if ($idempresa != 0) {
                            //INSERTA LA RELACION USUARIO Y EMPRESA
                            DB::connection("General")->insert('insert into mc1002 (idusuario, 
                                    idempresa, estatus, fecha_vinculacion, idusuario_vinculador)
                                    values (?, ?, ?, ?, ?)', [$idusuario, $idempresa, 1, $fecha, 0]);

                            ConnectaEmpresaDatabase($bdd);

                            DB::insert('insert into mc_userprofile (idusuario, idperfil) values (?, ?)', [$idusuario, 1]);

                            $mc1007 = DB::connection("General")->select("SELECT * FROM mc1007 WHERE idperfil = 1");
                            for ($i = 0; $i < count($mc1007); $i++) {
                                DB::table('mc_usermod')->insertGetId([
                                    "idusuario" => $idusuario, "idperfil" => 1,
                                    "idmodulo" => $mc1007[$i]->idmodulo, "tipopermiso" => $mc1007[$i]->tipopermiso
                                ]);
                            }

                            $mc1008 = DB::connection("General")->select("SELECT * FROM mc1008 WHERE idperfil = 1");
                            for ($i = 0; $i < count($mc1008); $i++) {
                                DB::table('mc_usermenu')->insertGetId([
                                    "idusuario" => $idusuario, "idperfil" => 1,
                                    "idmodulo" => $mc1008[$i]->idmodulo, "idmenu" => $mc1008[$i]->idmenu, "tipopermiso" => $mc1008[$i]->tipopermiso
                                ]);
                            }

                            $mc1009 = DB::connection("General")->select("SELECT * FROM mc1009 WHERE idperfil = 1");
                            for ($i = 0; $i < count($mc1009); $i++) {
                                DB::table('mc_usersubmenu')->insertGetId([
                                    "idusuario" => $idusuario, "idperfil" => 1, "idmenu" => $mc1009[$i]->idmenu,
                                    "idsubmenu" => $mc1009[$i]->idsubmenu, "tipopermiso" => $mc1009[$i]->tipopermiso
                                ]);
                            }

                            $validacarpetas = $this->creaCarpetas($rfc, $contraNextCloud);
                            $array["error"] = $validacarpetas;

                            /* if ($validacarpetas == 0) {
                                $servercloud = "cloud.dublock.com";
                                $respuestaCrearCarpetasEmpresa = $this->crearCarpetasEmpresa($servercloud, $rfc, $contraNextCloud, $archivocer, $archivokey);
                                $array["respuestaCrearCarpetasEmpresa"] = $respuestaCrearCarpetasEmpresa;
                            } */

                            $dbvacias = DB::connection("General")->select("SELECT id FROM mc1010 WHERE rfc='' AND estatus=0");
                            $proveedores = DB::connection("General")->select("SELECT * FROM mc1001 WHERE tipo = 4 AND notificaciondb = 1");
                            for ($i = 0; $i < count($proveedores); $i++) {
                                $data["titulo"] = "Nueva base de datos ocupada";
                                $data["cabecera"] = "Nueva base de datos ocupada";
                                $data["mensaje"] = "La base de datos " . $bdd . " ha sido ocupada por la empresa " . $empresa . ". Base de datos disponibles: " . count($dbvacias) . ".";
                                Mail::to($proveedores[$i]->correo)->send(new MensajesGenerales($data));
                            }
                        } else {
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
        } else {
            $consulta = DB::connection("General")->select("SELECT * FROM mc1010 WHERE rfc='' AND estatus=0");

            if (!empty($consulta)) {
                $array[0]["base"] = $consulta;
            } else {
                $array[0]["error"] = 42; //SIN BASES DE DATOS DISPONIBLES
            }
        }


        return $array;
    }

    public function creaTablasEmpresa($empresaBD)
    {
        ConnectaEmpresaDatabase($empresaBD);
        if ($empresaBD != "") {

            $mc_agente_entregas = "create table if not exists mc_agente_entregas (
                id int(11) NOT NULL AUTO_INCREMENT,
                idusuario int(11) DEFAULT NULL,
                idservicio int(11) DEFAULT NULL,
                tipodocumento VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                fecha timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                ejercicio int(11) DEFAULT NULL,
                periodo int(11) DEFAULT NULL,
                fechacorte DATE DEFAULT NULL,
                status int(11) DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_agente_entregas);

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
              idservicio INT(11) DEFAULT NULL,
              url VARCHAR(250) DEFAULT NULL,
              PRIMARY KEY (id)
            ) ENGINE=MYISAM DEFAULT CHARSET=latin1;";
            DB::statement($mc_bitcontabilidad);

            $mc_bitcontabilidad_det = "create table if not exists mc_bitcontabilidad_det (
              id INT(11) NOT NULL AUTO_INCREMENT,
              idbitacora INT(11) DEFAULT NULL,
              nombrearchivoE VARCHAR(255) CHARACTER SET latin1 DEFAULT NULL,
              fechacorte DATE DEFAULT NULL,
              PRIMARY KEY (id)
            ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_bitcontabilidad_det);

            $mc_bitcontabilidad_entregas = "create table if not exists mc_bitcontabilidad_entregas (
                idbitacora INT(11) DEFAULT NULL,
                idusuario INT(11) DEFAULT NULL,
                idservicio INT(11) DEFAULT NULL,
                accion INT(11) DEFAULT NULL,
                fechadecarga DATETIME DEFAULT current_timestamp()
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_bitcontabilidad_entregas);

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

            $mc_catproveedores = "create table if not exists mc_catproveedores (
                id INT(11) NOT NULL AUTO_INCREMENT,
                codigo VARCHAR(100) COLLATE latin1_spanish_ci DEFAULT NULL,
                rfc VARCHAR(70) COLLATE latin1_spanish_ci DEFAULT NULL,
                razonsocial VARCHAR(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                sucursal VARCHAR(250) COLLATE latin1_spanish_ci DEFAULT NULL,
                IdMoneda INT(11) DEFAULT NULL,
                Escliente INT(11) DEFAULT NULL,
                Prioridad INT(11) DEFAULT 0,
                Correo1 VARCHAR(250) COLLATE latin1_spanish_ci DEFAULT NULL,
                Correo2 VARCHAR(250) COLLATE latin1_spanish_ci DEFAULT NULL,
                Correo3 VARCHAR(250) COLLATE latin1_spanish_ci DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_catproveedores);

            $mc_catsucursales = "create table if not exists mc_catsucursales (
              idsucursal INT(11) NOT NULL AUTO_INCREMENT,
              sucursal VARCHAR(100) COLLATE latin1_spanish_ci DEFAULT NULL,
              rutaadw VARCHAR(250) COLLATE latin1_spanish_ci DEFAULT NULL,
              sincronizado INT(11) DEFAULT '0',
              idadw INT(11),
              mc_catsucursales.default tinyint(1) DEFAULT '0',
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
                id_agente int(11) DEFAULT NULL,
                fecha_procesado DATETIME DEFAULT NULL,
                estatus_procesado int(11) DEFAULT '0',
                PRIMARY KEY (idReq)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_requerimientos);

            $mc_requerimientos_aso = "create table if not exists mc_requerimientos_aso (
                id int(11) NOT NULL AUTO_INCREMENT,
                idrequerimiento int(11) NOT NULL,
                id_bit int(11) DEFAULT NULL,
                idgasto int(11) NOT NULL,
                importe double NOT NULL,
                rfc varchar(255) COLLATE latin1_spanish_ci NOT NULL,
                nombre varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                creado timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_requerimientos_aso);

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

            $mc_requerimientos_rel = "create table if not exists mc_requerimientos_rel (
                idgasto int(11) NOT NULL,
                iddocadw int(11) NOT NULL,
                idmodulo int(11) DEFAULT NULL,
                conceptoadw varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                folioadw int(11) DEFAULT NULL,
                serieadw varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                UUID varchar(250) COLLATE latin1_spanish_ci DEFAULT NULL
              ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_requerimientos_rel);

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

            $mc_almdigital_exp = "create table if not exists mc_almdigital_exp(
                idalmdigitaldet int(11) NOT NULL,
                idmodulo int(11) NOT NULL,
                cuenta varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                tipodoc varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                ejercicio int(11) DEFAULT NULL,
                periodo int(11) DEFAULT NULL
              ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_almdigital_exp);

            $mc_modulos_exped = "create table if not exists mc_modulos_exped(
                id int(11) NOT NULL AUTO_INCREMENT,
                idusuario int(11) DEFAULT NULL,
                idmodulo int(11) DEFAULT NULL,
                idcuenta int(11) DEFAULT NULL,
                periodo int(11) DEFAULT NULL,
                ejercicio int(11) DEFAULT NULL,
                tipo_doc varchar(250) COLLATE latin1_spanish_ci DEFAULT NULL,
                ruta varchar(250) COLLATE latin1_spanish_ci DEFAULT NULL,
                fecha_reg varchar(30) COLLATE latin1_spanish_ci DEFAULT NULL,
                fecha varchar(30) COLLATE latin1_spanish_ci DEFAULT NULL,
                descripcion text NOT NULL,
                numero1 int(11) DEFAULT NULL,
                numero2 int(11) DEFAULT NULL,
                numero3 int(11) DEFAULT NULL,
                texto1 varchar(250) COLLATE latin1_spanish_ci DEFAULT NULL,
                texto2 varchar(250) COLLATE latin1_spanish_ci DEFAULT NULL,
                texto3 varchar(250) COLLATE latin1_spanish_ci DEFAULT NULL,
                iddigital int(11) DEFAULT 0,
                version varchar(30) COLLATE latin1_spanish_ci DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_modulos_exped);

            $mc_flujosefectivo = "create table if not exists mc_flujosefectivo(
                id bigint(20) NOT NULL AUTO_INCREMENT,
                IdDoc INT(11) DEFAULT NULL,
                Idcon INT(11) DEFAULT NULL,
                Fecha DATE DEFAULT NULL,
                Vence DATE DEFAULT NULL,
                Idclien INT(11) DEFAULT NULL,
                Razon VARCHAR(200) COLLATE utf8_spanish_ci DEFAULT NULL,
                CodConcepto VARCHAR(250) COLLATE utf8_spanish_ci DEFAULT NULL,
                Concepto VARCHAR(100) COLLATE utf8_spanish_ci DEFAULT NULL,
                Serie VARCHAR(50) COLLATE utf8_spanish_ci DEFAULT NULL,
                Folio DECIMAL(18, 0) DEFAULT NULL,
                Total DECIMAL(18, 2) DEFAULT NULL,
                Pendiente DECIMAL(18, 2) DEFAULT NULL,
                Tipo VARCHAR(10) COLLATE utf8_spanish_ci DEFAULT NULL,
                Suc VARCHAR(250) COLLATE utf8_spanish_ci DEFAULT NULL,
                cRFC VARCHAR(15) COLLATE utf8_spanish_ci DEFAULT NULL,
                SaldoInt DECIMAL(18, 2) DEFAULT NULL,
                IdMoneda INT(11) DEFAULT NULL,
                IdUsuario INT(11) DEFAULT NULL,
                Comentarios VARCHAR(250) COLLATE utf8_spanish_ci DEFAULT NULL,
                Prioridad INT(11) DEFAULT NULL,
                Procesando INT(11) DEFAULT 0,
                Actualizacion DATE DEFAULT NULL,
                ImporteOriginal DECIMAL(18,2) DEFAULT NULL,
                TipoCambio DECIMAL(18,2) DEFAULT NULL,
                Moneda VARCHAR(100) COLLATE utf8_spanish_ci DEFAULT NULL,
                RutaArchivo VARCHAR(250) COLLATE utf8_spanish_ci DEFAULT NULL,
                NombreArchivo VARCHAR(250) COLLATE utf8_spanish_ci DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_flujosefectivo);

            $mc_flow_cliproctas = "create table if not exists mc_flow_cliproctas(
                Id int(11) NOT NULL AUTO_INCREMENT,
                RFC VARCHAR(15) COLLATE utf8_spanish_ci DEFAULT NULL,
                Cuenta VARCHAR(15) COLLATE utf8_spanish_ci DEFAULT NULL,
                Clabe VARCHAR(20) COLLATE utf8_spanish_ci DEFAULT NULL,
                Banco VARCHAR(250) COLLATE utf8_spanish_ci DEFAULT NULL,
                IdBanco INT(11) DEFAULT NULL,
                Sucursal INT(11) DEFAULT NULL,
                Escliente INT(11) DEFAULT NULL,
                PRIMARY KEY (Id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_flow_cliproctas);

            $mc_flow_bancuentas = "create table if not exists mc_flow_bancuentas(
                IdCuenta int(11) DEFAULT NULL,
                Clabe VARCHAR(100) COLLATE utf8_spanish_ci DEFAULT NULL,
                Cuenta VARCHAR(50) COLLATE utf8_spanish_ci DEFAULT NULL,
                Nombre VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                Banco VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                IdBanco INT(11) DEFAULT NULL,
                IdMoneda INT(11) DEFAULT NULL,
                Sucursal INT(11) DEFAULT NULL,
                Activa int(11) DEFAULT NULL
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_flow_bancuentas);

            $mc_flw_pagos = "create table if not exists mc_flw_pagos(
                id int(11) NOT NULL AUTO_INCREMENT,
                Fecha DATE DEFAULT NULL,
                Importe NUMERIC(18, 2) DEFAULT NULL,
                LlaveMatch VARCHAR(100) COLLATE utf8_spanish_ci DEFAULT NULL,
                Tipo int(11) DEFAULT NULL,
                RFC VARCHAR(100) COLLATE utf8_spanish_ci DEFAULT NULL,
                Proveedor VARCHAR(250) COLLATE utf8_spanish_ci DEFAULT NULL,
                IdCuentaOrigen INT(11) DEFAULT NULL,
                IdCuentaDestino INT(11) DEFAULT NULL,
                IdUsuario INT(11) DEFAULT NULL,
                Layout INT(11) DEFAULT 0,
                IdLayout INT(11) DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_flw_pagos);

            $mc_flw_pagos_det = "create table if not exists mc_flw_pagos_det(
                id bigint(20) NOT NULL AUTO_INCREMENT,
                IdPago int(11) DEFAULT NULL,
                IdFlw bigint(20) DEFAULT NULL,
                Importe DECIMAL(18,2) DEFAULT NULL,
                ImporteOriginal DECIMAL(18,2) DEFAULT NULL,
                TipoCambio DECIMAL(18,2) DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_flw_pagos_det);

            $mc_flw_correos = "create table if not exists mc_flw_correos(
                id int(11) NOT NULL AUTO_INCREMENT,
                IdPago int(11) DEFAULT NULL,
                Correo VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                Tipo int(11) DEFAULT NULL,
                CodigoMensaje bigint(20) DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_flw_correos);
            
            $mc_flw_layouts = "create table if not exists mc_flw_layouts(
                id int(11) NOT NULL AUTO_INCREMENT,
                UrlLayout VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                NombreLayout VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                LinkLayout VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_flw_layouts);

            $mc_flw_layouts_config = "create table if not exists mc_flw_layouts_config(
                id int(11) NOT NULL AUTO_INCREMENT,
                IdBanco int(11) DEFAULT NULL,
                NombreLayout VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                LinkLayout VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_flw_layouts_config);

            $mc_flw_layouts_config_content = "create table if not exists mc_flw_layouts_config_content(
                id int(11) NOT NULL AUTO_INCREMENT,
                IdLayoutConfig int(11) DEFAULT NULL,
                NombreVariable VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                Etiqueta VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                posicion int(11) DEFAULT NULL,
                Descripcion VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                PosicionInicial int(11) DEFAULT NULL,
                PosicionFinal int(11) DEFAULT NULL,
                Longitud int(11) DEFAULT NULL,
                Observaciones VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                Alineacion int(11) DEFAULT NULL,
                Llenado VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_flw_layouts_config_content);

            $mc_flw_layouts_bancos = "create table if not exists mc_flw_layouts_bancos(
                id int(11) NOT NULL AUTO_INCREMENT,
                IdBanco int(11) DEFAULT NULL,
                Destino int(11) DEFAULT NULL,
                IdLayoutConfig int(11) DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_flw_layouts_bancos);

            $mc_publicaciones = "create table if not exists mc_publicaciones(
                id bigint(20) NOT NULL AUTO_INCREMENT,
                titulo VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                descripcion VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                tipoPublicacion int(11) DEFAULT NULL,
                tipoCatalogo int(11) DEFAULT NULL,
                idUsuario int(11) DEFAULT NULL,
                fechaPublicacion DATETIME DEFAULT NULL,
                fechaEditado DATETIME DEFAULT NULL,
                fechaEliminado DATETIME DEFAULT NULL,
                status int(11) DEFAULT 1,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_publicaciones);

            $mc_publicaciones_docs = "create table if not exists mc_publicaciones_docs(
                id bigint(20) NOT NULL AUTO_INCREMENT,
                idPublicacion bigint(20) DEFAULT NULL,
                ruta VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                nombre VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                link VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_publicaciones_docs);

            $mc_publicaciones_catalogos = "create table if not exists mc_publicaciones_catalogos(
                id int(11) NOT NULL AUTO_INCREMENT,
                nombre VARCHAR(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                tipo int(11) DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_publicaciones_catalogos);

            $mc_pry_proyectos = "create table if not exists mc_pry_proyectos(
                id INT(11) NOT NULL AUTO_INCREMENT,
                Proyecto VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                Codigo VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                IdClas1 INT(11) DEFAULT NULL,
                IdClas2 INT(11) DEFAULT NULL,
                IdClas3 INT(11) DEFAULT NULL,
                IdClas4 INT(11) DEFAULT NULL,
                idAgente INT(11) DEFAULT NULL,
                Estatus INT(11) DEFAULT NULL,
                FecIni DATE DEFAULT NULL,
                FecFin DATE DEFAULT NULL,
                Avance INT(11) DEFAULT NULL,
                FecUltAccion DATE DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_pry_proyectos);

            $mc_pry_agentes = "create table if not exists mc_pry_agentes(
                id INT(11) NOT NULL AUTO_INCREMENT,
                Agente VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                Estatus INT(11) DEFAULT NULL,
                tipo INT(11) DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_pry_agentes);

            $mc_pry_proycatagentes = "create table if not exists mc_pry_proycatagentes(
                id INT(11) NOT NULL AUTO_INCREMENT,
                IDPersona INT(11) DEFAULT NULL,
                IDProyecto INT(11) DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_pry_proycatagentes);

            $mc_pry_proyactividades = "create table if not exists mc_pry_proyactividades(
                id INT(11) NOT NULL AUTO_INCREMENT,
                IdProyecto INT(11) DEFAULT NULL,
                Pos INT(11) DEFAULT NULL,
                Nivel INT(11) DEFAULT NULL,
                Actividad VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                FecIni DATE DEFAULT NULL,
                FecFin DATE DEFAULT NULL,
                idAgente INT(11) DEFAULT NULL,
                Avance INT(11) DEFAULT NULL,
                Estatus INT(11) DEFAULT NULL,
                FecUltAccion DATE DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_pry_proyactividades);

            $mc_pry_proyacciones = "create table if not exists mc_pry_proyacciones(
                id INT(11) NOT NULL AUTO_INCREMENT,
                idproyecto INT(11) DEFAULT NULL,
                idactividad INT(11) DEFAULT NULL,
                fecha DATE DEFAULT NULL,
                Avance INT(11) DEFAULT NULL,
                estatus INT(11) DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_pry_proyacciones);

            $mc_pry_proypersonas = "create table if not exists mc_pry_proypersonas(
                id INT(11) NOT NULL AUTO_INCREMENT,
                idproyecto INT(11) DEFAULT NULL,
                idactividad INT(11) DEFAULT NULL,
                idaccion INT(11) DEFAULT NULL,
                idpersona INT(11) DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_pry_proypersonas);

            $mc_pry_proydocumentos = "create table if not exists mc_pry_proydocumentos(
                id INT(11) NOT NULL AUTO_INCREMENT,
                idproyecto INT(11) DEFAULT NULL,
                idactividad INT(11) DEFAULT NULL,
                idaccion INT(11) DEFAULT NULL,
                NombreDocumento VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                ExtencionDocumento VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                RutaDocumento VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                LinkDocumento VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_pry_proydocumentos);

            $mc_pry_proyplanes = "create table if not exists mc_pry_proyplanes(
                id INT(11) NOT NULL AUTO_INCREMENT,
                idproyecto INT(11) DEFAULT NULL,
                idactividades INT(11) DEFAULT NULL,
                fecini DATE DEFAULT NULL,
                fecfin DATE DEFAULT NULL,
                idagente INT(11) DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_pry_proyplanes);

            /* $mc_flw_layouts_usuarios = "create table if not exists mc_flw_layouts_usuarios(
                id int(11) NOT NULL AUTO_INCREMENT,
                IdLayoutConfig int(11) DEFAULT NULL,
                IdUsuario int(11) DEFAULT NULL,
                IdBanco int(11) DEFAULT NULL,
                PRIMARY KEY (id)
              ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
            DB::statement($mc_flw_layouts_usuarios); */

            $mc1006 = "insert " . $empresaBD . ".mc_profiles SELECT * FROM dublockc_MCGenerales.mc1006;";
            DB::statement($mc1006);

            $mc1007 = "insert " . $empresaBD . ".mc_modpermis SELECT * FROM dublockc_MCGenerales.mc1007;";
            DB::statement($mc1007);

            $mc1008 = "insert " . $empresaBD . ".mc_menupermis SELECT * FROM dublockc_MCGenerales.mc1008;";
            DB::statement($mc1008);

            $mc1009 = "insert " . $empresaBD . ".mc_submenupermis SELECT * FROM dublockc_MCGenerales.mc1009;";
            DB::statement($mc1009);

            $mc1013 = "insert " . $empresaBD . ".mc_rubros SELECT * FROM dublockc_MCGenerales.mc1013;";
            DB::statement($mc1013);

            $SucTemp = "insert into mc_catsucursales (sucursal) VALUES ('TEMPORAL')";
            DB::statement($SucTemp);

            $conceptos = "insert " . $empresaBD . ".mc_conceptos SELECT * FROM dublockc_MCGenerales.mc1014;";
            DB::statement($conceptos);
            //$mc1014 = "insert ".$empresaBD.".mc_conceptos SELECT * FROM dublockc_MCGenerales.mc1014;";
            //DB::statement($mc1014);

            $id = 1;
        } else {
            $id = 0;
        }
        return $id;
    }

    public function creaCarpetas($rfc, $pass)
    {
        set_time_limit(300);
        $error = 0;
        $datosParam = getParametros();
        if ($datosParam != "") {
            $servercloud = $datosParam[0]->servidor_storage;
            $usercloud = $datosParam[0]->usuario_storage;
            $passcloud = $datosParam[0]->password_storage;
            $array["rfc"] = $rfc;
            $array["pass"] = $pass;

            //CREA USUARIO
            $ch = curl_init();
            $DatosUser = array("userid" => $rfc, "password" => $pass);
            curl_setopt($ch, CURLOPT_URL, "https://" . $usercloud . ":" . $passcloud . "@" . $servercloud . "/ocs/v1.php/cloud/users");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $DatosUser);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('OCS-APIRequest:true'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $error = ($response == false ? 46 : 0);
            curl_close($ch);

            /* $array["response1"] = $response;
            $array["error1"] = $error; */
            
        } else {
            $error = 45;
        }
        return $error;
    }

    public function crearCarpetasEmpresa($servercloud, $rfc, $pass, $certificado, $key) {
        set_time_limit(300);
        //CREA CARPETAS
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $rfc . ':' . $pass);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "MKCOL");
        $url = 'https://' . $servercloud . '/remote.php/dav/files/' . $rfc . '/CRM';
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);

        $array["response2"] = $response;
        

        $url = 'https://' . $servercloud . '/remote.php/dav/files/' . $rfc . '/CRM/' . $rfc;
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);

        $array["response3"] = $response;


        $modulos = DB::connection("General")->select('select idmodulo,nombre_carpeta from mc1003');
        for ($i = 0; $i < count($modulos); $i++) {
            $idmodulo = $modulos[$i]->idmodulo;
            $carpetamodulo = $modulos[$i]->nombre_carpeta;

            $url = 'https://' . $servercloud . '/remote.php/dav/files/' . $rfc . '/CRM/' . $rfc . '/' . $carpetamodulo;
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = curl_exec($ch);
            $array["response4"] = $response;

            $menus = DB::connection("General")->select('select idmenu,nombre_carpeta from mc1004 
                                            where idmodulo = ?', [$idmodulo]);
            for ($x = 0; $x < count($menus); $x++) {
                $idmenu = $menus[$x]->idmenu;
                $carpetamenu = $menus[$x]->nombre_carpeta;

                $url = 'https://' . $servercloud . '/remote.php/dav/files/' . $rfc . '/CRM/' . $rfc . '/' . $carpetamodulo . '/' . $carpetamenu;
                curl_setopt($ch, CURLOPT_URL, $url);
                $response = curl_exec($ch);
                $array["response5"] = $response;

                $submenus = DB::connection("General")->select('select nombre_carpeta from mc1005
                                     where idmenu = ?', [$idmenu]);
                for ($z = 0; $z < count($submenus); $z++) {
                    $carpetasubmenu = $submenus[$z]->nombre_carpeta;

                    $url = 'https://' . $servercloud . '/remote.php/dav/files/' . $rfc . '/CRM/' . $rfc . '/' . $carpetamodulo . '/' . $carpetamenu . '/' . $carpetasubmenu;
                    curl_setopt($ch, CURLOPT_URL, $url);
                    $response = curl_exec($ch);
                    $array["response6"] = $response;
                }
            }
        }

        //SUBIR ARCHIVOS
        $gestor = fopen($certificado, "r");
        $contenido = fread($gestor, filesize($certificado));
        fclose($gestor);
        curl_setopt_array(
            $ch,
            array(
                CURLOPT_URL => 'https://' . $servercloud . '/remote.php/dav/files/' . $rfc . '/CRM/' . $rfc . '/' . $certificado->getClientOriginalName(),
                CURLOPT_VERBOSE => 1,
                CURLOPT_USERPWD => $rfc . ':' . $pass,
                CURLOPT_POSTFIELDS => $contenido,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_BINARYTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'PUT',
            )
        );
        $response = curl_exec($ch);
        $array["response7"] = $response;

        $gestor = fopen($key, "r");
        $contenido = fread($gestor, filesize($key));
        fclose($gestor);
        curl_setopt_array(
            $ch,
            array(
                CURLOPT_URL => 'https://' . $servercloud . '/remote.php/dav/files/' . $rfc . '/CRM/' . $rfc . '/' . $key->getClientOriginalName(),
                CURLOPT_VERBOSE => 1,
                CURLOPT_USERPWD => $rfc . ':' . $pass,
                CURLOPT_POSTFIELDS => $contenido,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_BINARYTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'PUT',
            )
        );
        $response = curl_exec($ch);
        $array["response8"] = $response;

        $contenido = $pass;
        curl_setopt_array(
            $ch,
            array(
                CURLOPT_URL => 'https://' . $servercloud . '/remote.php/dav/files/' . $rfc . '/CRM/' . $rfc . '/' . $rfc . '.txt',
                CURLOPT_VERBOSE => 1,
                CURLOPT_USERPWD => $rfc . ':' . $pass,
                CURLOPT_POSTFIELDS => $contenido,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_BINARYTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'PUT',
            )
        );
        $response = curl_exec($ch);
        $array["response9"] = $response;

        $error = ($response != '' ? 46 : 0);
        curl_close($ch);
        return $array;
    }

    public function datosEmpresa(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $rfc = $request->rfc;
            $empresa = DB::select('select * from mc1000 where rfc = ?', [$rfc]);
            $array[0]['empresa'] = $empresa;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function editarDatosFacturacionEmpresa(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $idempresa = $request->idempresa;
            $calle = $request->calle;
            $colonia = $request->colonia;
            $num_ext = $request->num_ext;
            $num_int = $request->num_int;
            $codigopostal = $request->codigopostal;
            $municipio = $request->municipio;
            $ciudad = $request->ciudad;
            $estado = $request->estado;
            $telefono = $request->telefono;
            DB::connection("General")->table('mc1000')->where("idempresa", $idempresa)->update(["calle" => $calle, "colonia" => $colonia, "num_ext" => $num_ext, "num_int" => $num_int, "codigopostal" => $codigopostal, "municipio" => $municipio, "ciudad" => $ciudad, "estado" => $estado, "telefono" => $telefono]);

            $datosempresa = DB::connection("General")->select("SELECT * FROM mc1000 WHERE idempresa = $idempresa");

            $usuario = $valida[0]['usuario'];
            $iduser = $usuario[0]->idusuario;

            $empresaBD = $datosempresa[0]->rutaempresa;
            ConnectaEmpresaDatabase($empresaBD);

            $perfil = DB::select('select nombre from mc_userprofile INNER JOIN mc_profiles ON mc_userprofile.idperfil = mc_profiles.idperfil
                            where idusuario = ?', [$iduser]);
            $datosempresa[0]->perfil = $perfil[0]->nombre;

            $sucursales = DB::select('select * from mc_catsucursales');

            $datosempresa[0]->sucursales = $sucursales;

            $array["datosempresa"] = $datosempresa;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function renovarCertificadoEmpresa(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);

        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $conexionFTP = conectaFTP();
            if ($conexionFTP != '') {
                $archivocer = $request->file('certificado');
                $archivokey = $request->file('key');
                $passwordcer = $request->passwordcertificado;
                $resSubirArchivos = $this->subeCertificados($conexionFTP, $archivocer, $archivokey);
                $array["error"] = $resSubirArchivos;

                ftp_close($conexionFTP);

                $resDatos = $this->verificaDatosCertificados($archivocer->getClientOriginalName(), $archivokey->getClientOriginalName(), $passwordcer);
                $array["error"] = $resDatos[0]["error"];

                $datosEmpresa = $resDatos[0]["datos"];
                $fechavencimiento = $datosEmpresa["fechavencimiento"];

                $idempresa = $request->idempresa;
                DB::connection("General")->table('mc1000')->where("idempresa", $idempresa)->update(["vigencia" => $fechavencimiento]);

                $rfc = $request->rfc;
                $fecha = $request->fecha;
                $servidor = getServidorNextcloud();
                $usuariostorage = $request->usuariostorage;
                $passwordstorage = $request->passwordstorage;
                $filenamecer = $fecha . "_" . $archivocer->getClientOriginalName();
                $filenamekey = $fecha . "_" . $archivokey->getClientOriginalName();
                $filenamepassword = $fecha . "_" . $rfc;

                subirNuevoCertificadoNextcloud($archivocer->getClientOriginalName(), $archivocer, $rfc, $servidor, $usuariostorage, $passwordstorage, $filenamecer);
                subirNuevoCertificadoNextcloud($archivokey->getClientOriginalName(), $archivokey, $rfc, $servidor, $usuariostorage, $passwordstorage, $filenamekey);

                set_time_limit(0);

                $ch = curl_init();
                $target_path = $rfc . '/' . $filenamepassword . '.txt';

                curl_setopt_array(
                    $ch,
                    array(
                        CURLOPT_URL => 'https://' . $servidor . '/remote.php/dav/files/' . $usuariostorage . '/CRM/' . $target_path,
                        CURLOPT_VERBOSE => 1,
                        CURLOPT_USERPWD => $usuariostorage . ':' . $passwordstorage,
                        CURLOPT_POSTFIELDS => $passwordcer,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_BINARYTRANSFER => true,
                        CURLOPT_CUSTOMREQUEST => 'PUT',
                    )
                );
                $resp = curl_exec($ch);
                $error_no = curl_errno($ch);

                curl_close($ch);
            } else {
                $array["error"] = 30;
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function getServiciosEmpresaCliente(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $idempresa = $request->idempresa;
            /*  $servicios = DB::connection("General")->select("SELECT mc0001.* FROM mc0001 INNER JOIN mc0002 ON mc0001.id = mc0002.idservicio WHERE mc0002.idempresa = $idempresa"); */
            $servicios = DB::connection("General")->select("SELECT mc0001.*, (SELECT mc0002.id FROM mc0002 WHERE mc0002.idservicio = mc0001.id AND mc0002.idempresa = $idempresa) AS serviciocontratado FROM mc0001");

            $array["servicios"] = $servicios;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function agregarServicioEmpresaCliente(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $idempresa = $request->idempresa;
            $idservicios = $request->idservicios;
            /* $array["idservicios"] = $idservicios;
            $array["idservicioslength"] = count($idservicios); */
            $fecha = $request->fecha;
            for ($x = 0; $x < count($idservicios); $x++) {
                DB::connection("General")->table("mc0002")->insert(["idempresa" => $idempresa, "idservicio" => $idservicios[$x], "fechaContratacion" => $fecha]);
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function quitarServicioEmpresaCliente(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $idempresa = $request->idempresa;
            $idservicio = $request->idservicio;
            $fechacancelacion = $request->fechacancelacion;
            $idusuariocancelacion = $request->idusuariocancelacion;
            DB::connection("General")->table("mc0002")->where("idempresa", $idempresa)->where("idservicio", $idservicio)->update(["fechaCancelacion" => $fechacancelacion, "idUsuarioCancelacion" => $idusuariocancelacion, "status" => 0]);
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function getNotificacionesServicios(Request $request)
    {
        $idusuario = $request->idusuario;
        $idempresa = $request->idempresa;
        $accion = $request->accion;
        $valida = verificaUsuario($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            switch($accion){
                case 0:
                    $notificaciones = DB::connection("General")->select("SELECT mc0005.*, mc0001.nombreservicio, mc1000.nombreempresa, mc1001A.nombre AS nombreusuario, CONCAT(mc1001B.nombre, ' ', mc1001B.apellidop, ' ',mc1001B.apellidom) AS nombreusuariocreador, 
                    mc1003.idmodulo, mc1003.nombre_modulo AS nombremodulo, mc1003.nombre_carpeta AS nombrecarpetamodulo,
                    mc1004.idmenu, mc1004.nombre_menu AS nombremenu, mc1004.nombre_carpeta AS nombrecarpetamenu, mc1004.ref AS refmenu,
                    mc1005.idsubmenu, mc1005.nombre_submenu AS nombresubmenu, mc1005.nombre_carpeta AS nombrecarpetasubmenu 
                    FROM mc0005 
                    INNER JOIN mc0001 ON mc0005.idServicio = mc0001.id 
                    INNER JOIN mc1000 ON mc0005.idEmpresa = mc1000.idempresa 
                    INNER JOIN mc1001 AS mc1001A ON mc0005.idUsuario = mc1001A.idusuario 
                    LEFT JOIN mc1001 AS mc1001B ON mc0005.idUsuarioCreador = mc1001B.idusuario
                    LEFT JOIN mc1003 ON mc0001.idmodulo = mc1003.idmodulo
                    LEFT JOIN mc1004 ON mc0001.idmenu = mc1004.idmenu
                    LEFT JOIN mc1005 ON mc0001.idsubmenu = mc1005.idsubmenu 
                    WHERE mc0005.idEmpresa = ? AND mc0005.idUsuario = ? AND mc0005.status = ? ORDER BY mc0005.fecha DESC, mc0005.hora DESC", [$idempresa, $idusuario, 1]);
                    break;
                case 1:
                    $notificaciones = DB::connection("General")->select("SELECT mc0005.*, mc0001.nombreservicio, mc1000.nombreempresa, mc1001A.nombre AS nombreusuario, CONCAT(mc1001B.nombre, ' ', mc1001B.apellidop, ' ',mc1001B.apellidom) AS nombreusuariocreador, 
                    mc1003.idmodulo, mc1003.nombre_modulo AS nombremodulo, mc1003.nombre_carpeta AS nombrecarpetamodulo,
                    mc1004.idmenu, mc1004.nombre_menu AS nombremenu, mc1004.nombre_carpeta AS nombrecarpetamenu, mc1004.ref AS refmenu,
                    mc1005.idsubmenu, mc1005.nombre_submenu AS nombresubmenu, mc1005.nombre_carpeta AS nombrecarpetasubmenu 
                    FROM mc0005 
                    INNER JOIN mc0001 ON mc0005.idServicio = mc0001.id 
                    INNER JOIN mc1000 ON mc0005.idEmpresa = mc1000.idempresa 
                    INNER JOIN mc1001 AS mc1001A ON mc0005.idUsuario = mc1001A.idusuario 
                    LEFT JOIN mc1001 AS mc1001B ON mc0005.idUsuarioCreador = mc1001B.idusuario
                    LEFT JOIN mc1003 ON mc0001.idmodulo = mc1003.idmodulo
                    LEFT JOIN mc1004 ON mc0001.idmenu = mc1004.idmenu
                    LEFT JOIN mc1005 ON mc0001.idsubmenu = mc1005.idsubmenu WHERE mc0005.idUsuario = ?  AND mc0005.status = ?", [$idusuario, 1]);
                    break;
                case 2:
                    $notificaciones = DB::connection("General")->select("SELECT mc0005.*, mc0001.nombreservicio, mc1000.nombreempresa, mc1001A.nombre AS nombreusuario, CONCAT(mc1001B.nombre, ' ', mc1001B.apellidop, ' ',mc1001B.apellidom) AS nombreusuariocreador, 
                    mc1003.idmodulo, mc1003.nombre_modulo AS nombremodulo, mc1003.nombre_carpeta AS nombrecarpetamodulo,
                    mc1004.idmenu, mc1004.nombre_menu AS nombremenu, mc1004.nombre_carpeta AS nombrecarpetamenu, mc1004.ref AS refmenu,
                    mc1005.idsubmenu, mc1005.nombre_submenu AS nombresubmenu, mc1005.nombre_carpeta AS nombrecarpetasubmenu 
                    FROM mc0005 
                    INNER JOIN mc0001 ON mc0005.idServicio = mc0001.id 
                    INNER JOIN mc1000 ON mc0005.idEmpresa = mc1000.idempresa 
                    INNER JOIN mc1001 AS mc1001A ON mc0005.idUsuario = mc1001A.idusuario 
                    LEFT JOIN mc1001 AS mc1001B ON mc0005.idUsuarioCreador = mc1001B.idusuario
                    LEFT JOIN mc1003 ON mc0001.idmodulo = mc1003.idmodulo
                    LEFT JOIN mc1004 ON mc0001.idmenu = mc1004.idmenu
                    LEFT JOIN mc1005 ON mc0001.idsubmenu = mc1005.idsubmenu WHERE mc0005.idUsuario = ? AND mc0005.favorita = ? AND mc0005.status = ?", [$idusuario, 1, 1]);
                    break;
            }
    
            $array["notificaciones"] = $notificaciones;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function agregarNotificacionFavoritas(Request $request)
    {
        $idnotificacion = $request->idnotificacion;
        $favorita = $request->favorita;
        $valida = verificaUsuario($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            DB::connection("General")->table('mc0005')->where("id", $idnotificacion)->update(["favorita" => $favorita]);
        } 

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function cambiarVistaNotificacionServicios(Request $request)
    {
        $idnotificacion = $request->idnotificacion;
        $vista = $request->vista;
        $menuRedirect = $request->menuRedirect;

        $valida = verificaUsuario($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $array["menuRedirect"] = $menuRedirect != "" ? $menuRedirect : "";
            DB::connection("General")->table('mc0005')->where("id", $idnotificacion)->update(["vista" => $vista]);
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function eliminarNotificacionServicios(Request $request)
    {
        $idnotificacion = $request->idnotificacion;
        $status = $request->status;

        $valida = verificaUsuario($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            DB::connection("General")->table('mc0005')->where("id", $idnotificacion)->update(["status" => $status]);
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function getMovimientosEmpresaCliente(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $idempresa = $request->idempresa;
            $tabla = $request->tabla;
            $movimientos = DB::connection("General")->select("SELECT mc1017.*, CONCAT(mc1001.nombre, ' ', mc1001.apellidop, ' ', mc1001.apellidom) AS usuario, (SELECT SUM(importe) FROM mc1018 WHERE iddoccargo = mc1017.idmovimiento) AS abonos
            FROM mc1017 INNER JOIN mc1001 ON mc1017.idusuario = mc1001.idusuario WHERE idempresa = $idempresa ORDER BY mc1017.fecha ASC, mc1017.idmovimiento ASC");

            $array["movimientos"] = $movimientos;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function getMovimientoEmpresaCliente(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $idmovimiento = $request->idmovimiento;
            $movimiento = DB::connection("General")->select("SELECT mc1017.*, CONCAT(mc1001.nombre, ' ', mc1001.apellidop, ' ', mc1001.apellidom) AS usuario FROM mc1017 INNER JOIN mc1001 ON mc1017.idusuario = mc1001.idusuario WHERE idmovimiento =  $idmovimiento");
            $abonos = DB::connection("General")->select("SELECT mc1018.*, mc1017.documento FROM mc1018 INNER JOIN mc1017 ON mc1018.iddoc = mc1017.idmovimiento WHERE iddoccargo = $idmovimiento ORDER BY mc1018.fecha DESC, mc1018.iddocabono DESC");
            $cargos = DB::connection("General")->select("SELECT mc1017.*, mc1018.iddocabono, mc1018.importe AS abono FROM mc1017 INNER JOIN mc1018 ON mc1017.idmovimiento = mc1018.iddoccargo WHERE mc1018.iddoc = $idmovimiento ORDER BY mc1017.fecha DESC, mc1017.idmovimiento DESC");
            $archivos = DB::connection("General")->select("SELECT mc1019.* FROM mc1019 INNER JOIN mc1017 ON mc1019.idmovimiento = mc1017.idmovimiento WHERE mc1019.idmovimiento = $idmovimiento UNION SELECT mc1019.* FROM mc1019 LEFT JOIN mc1018 ON mc1019.idmovimiento = mc1018.iddoc WHERE mc1018.iddoccargo = $idmovimiento");

            $array["movimiento"] = $movimiento;
            $array["abonos"] = $abonos;
            $array["cargos"] = $cargos;
            $array["archivos"] = $archivos;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getContenidoServicioClientes(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $idservicio = $request->idservicio;
            $contenido = DB::connection("General")->select("SELECT * FROM mc0004 WHERE idservicio = $idservicio");
            $array["contenido"] = $contenido;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getDatosHome(Request $request)
    {
        $db = $request->db;
        $idusuario = $request->idusuario;

        $documento = DB::connection("General")->select("SELECT $db.mc_requerimientos.idreq AS id , $db.mc_requerimientos.fecha AS fecharegistro, $db.mc_requerimientos.fecha AS fechadocumento, 
        DATE_FORMAT($db.mc_requerimientos.fecha, '%m') AS periodo, DATE_FORMAT($db.mc_requerimientos.fecha, '%Y') AS ejercicio,
        mc1005.idsubmenu, mc1005.nombre_submenu, mc1004.idmenu, mc1004.nombre_menu, mc1004.ref AS refmenu, mc1003.idmodulo, mc1003.nombre_modulo, mc1001.idusuario, 
        CONCAT(mc1001.nombre, ' ',mc1001.apellidop, ' ',mc1001.apellidom) AS usuario, $db.mc_requerimientos.estatus AS extra1
        FROM $db.mc_requerimientos INNER JOIN mc1005 ON mc1005.idsubmenu = $db.mc_requerimientos.id_departamento 
        INNER JOIN mc1004 ON mc1004.idmenu = mc1005.idmenu INNER JOIN mc1003 ON mc1003.idmodulo = mc1004.idmodulo
        INNER JOIN mc1001 ON mc1001.idusuario = $db.mc_requerimientos.id_usuario
        WHERE (SELECT $db.mc_usermod.tipopermiso FROM $db.mc_usermod 
        WHERE $db.mc_usermod.idusuario = $idusuario AND $db.mc_usermod.idmodulo = mc1003.idmodulo) <> 0 
        AND (SELECT $db.mc_usermenu.tipopermiso FROM $db.mc_usermenu 
        WHERE $db.mc_usermenu.idusuario = $idusuario AND $db.mc_usermenu.idmenu = mc1004.idmenu) <> 0 
        AND (SELECT $db.mc_usersubmenu.tipopermiso FROM $db.mc_usersubmenu 
        WHERE $db.mc_usersubmenu.idusuario = $idusuario AND $db.mc_usersubmenu.idsubmenu = mc1005.idsubmenu) <> 0 UNION        
        SELECT $db.mc_almdigital.id , DATE_FORMAT($db.mc_almdigital.fechadecarga, '%Y-%m-%d') AS fecharegistro, $db.mc_almdigital.fechadocto AS fechadocumento, 
        DATE_FORMAT($db.mc_almdigital.fechadocto, '%m') AS periodo, DATE_FORMAT($db.mc_almdigital.fechadocto, '%Y') AS ejercicio,
        mc1005.idsubmenu, mc1005.nombre_submenu, mc1004.idmenu, mc1004.nombre_menu, mc1004.ref AS refmenu, mc1003.idmodulo, mc1003.nombre_modulo, mc1001.idusuario, 
        CONCAT(mc1001.nombre, ' ',mc1001.apellidop, ' ',mc1001.apellidom) AS usuario, 
        CONCAT('Registros: ',$db.mc_almdigital.totalregistros, ' Cargados: ', $db.mc_almdigital.totalcargados, ' Procesados: ',(SELECT COUNT(id) FROM $db.mc_almdigital_det WHERE $db.mc_almdigital_det.idalmdigital = $db.mc_almdigital.id AND $db.mc_almdigital_det.estatus = 1)) AS extra1
        FROM $db.mc_almdigital INNER JOIN mc1005 ON mc1005.idsubmenu = $db.mc_almdigital.idmodulo 
        INNER JOIN mc1004 ON mc1004.idmenu = mc1005.idmenu INNER JOIN mc1003 ON mc1003.idmodulo = mc1004.idmodulo
        INNER JOIN mc1001 ON mc1001.idusuario = $db.mc_almdigital.idusuario
        WHERE (SELECT $db.mc_usermod.tipopermiso FROM $db.mc_usermod 
        WHERE $db.mc_usermod.idusuario = $idusuario AND $db.mc_usermod.idmodulo = mc1003.idmodulo) <> 0 
        AND (SELECT $db.mc_usermenu.tipopermiso FROM $db.mc_usermenu 
        WHERE $db.mc_usermenu.idusuario = $idusuario AND $db.mc_usermenu.idmenu = mc1004.idmenu) <> 0 
        AND (SELECT $db.mc_usersubmenu.tipopermiso FROM $db.mc_usersubmenu 
        WHERE $db.mc_usersubmenu.idusuario = $idusuario AND $db.mc_usersubmenu.idsubmenu = mc1005.idsubmenu) <> 0 UNION        
        SELECT $db.mc_bitcontabilidad.id , $db.mc_bitcontabilidad.fecha AS fecharegistro, $db.mc_bitcontabilidad.fecha AS fechadocumento, 
        DATE_FORMAT($db.mc_bitcontabilidad.fecha, '%m') AS periodo, DATE_FORMAT($db.mc_bitcontabilidad.fecha, '%Y') AS ejercicio,
        mc1005.idsubmenu, mc1005.nombre_submenu, mc1004.idmenu, mc1004.nombre_menu, mc1004.ref AS refmenu, mc1003.idmodulo, mc1003.nombre_modulo, mc1001.idusuario, 
        CONCAT(mc1001.nombre, ' ',mc1001.apellidop, ' ',mc1001.apellidom) AS usuario, 0 AS extra1
        FROM $db.mc_bitcontabilidad INNER JOIN mc1005 ON mc1005.idsubmenu = $db.mc_bitcontabilidad.idsubmenu 
        INNER JOIN mc1004 ON mc1004.idmenu = mc1005.idmenu INNER JOIN mc1003 ON mc1003.idmodulo = mc1004.idmodulo
        INNER JOIN mc1001 ON mc1001.idusuario = $db.mc_bitcontabilidad.idusuarioE WHERE $db.mc_bitcontabilidad.status <> 0
        AND (SELECT $db.mc_usermod.tipopermiso FROM $db.mc_usermod 
        WHERE $db.mc_usermod.idusuario = $idusuario AND $db.mc_usermod.idmodulo = mc1003.idmodulo) <> 0 
        AND (SELECT $db.mc_usermenu.tipopermiso FROM $db.mc_usermenu 
        WHERE $db.mc_usermenu.idusuario = $idusuario AND $db.mc_usermenu.idmenu = mc1004.idmenu) <> 0 
        AND (SELECT $db.mc_usersubmenu.tipopermiso FROM $db.mc_usersubmenu 
        WHERE $db.mc_usersubmenu.idusuario = $idusuario AND $db.mc_usersubmenu.idsubmenu = mc1005.idsubmenu) <> 0 UNION 
        SELECT $db.mc_lotes.id , $db.mc_lotes.fechadecarga AS fecharegistro, $db.mc_lotes.fechadecarga AS fechadocumento, 
        DATE_FORMAT($db.mc_lotes.fechadecarga, '%m') AS periodo, DATE_FORMAT($db.mc_lotes.fechadecarga, '%Y') AS ejercicio,
        mc1005.idsubmenu, mc1005.nombre_submenu, mc1004.idmenu, mc1004.nombre_menu, mc1004.ref AS refmenu, mc1003.idmodulo, mc1003.nombre_modulo, mc1001.idusuario, CONCAT(mc1001.nombre, ' ',mc1001.apellidop, ' ',mc1001.apellidom) AS usuario,
        (CONCAT('Registros: ', $db.mc_lotes.totalregistros, ' Cargados: ', $db.mc_lotes.totalcargados, ' Error: ', 
        (SELECT SUM(IF($db.mc_lotesdocto.error>0, $db.mc_lotesdocto.error, 0)) FROM $db.mc_lotes ))) AS extra1
        FROM $db.mc_lotes INNER JOIN mc1005 ON mc1005.idsubmenu = 
        (SELECT $db.mc_rubros.idsubmenu FROM $db.mc_rubros WHERE $db.mc_rubros.idmenu = 6 AND $db.mc_rubros.claveplantilla = $db.mc_lotes.tipo LIMIT 1)
        INNER JOIN mc1004 ON mc1004.idmenu = 6 INNER JOIN mc1003 ON mc1003.idmodulo = 2 INNER JOIN mc1001 ON mc1001.idusuario = $db.mc_lotes.usuario
        LEFT JOIN $db.mc_lotesdocto ON $db.mc_lotes.id = $db.mc_lotesdocto.idlote 
        WHERE $db.mc_lotes.totalregistros <> 0 AND $db.mc_lotes.totalcargados <> 0 AND $db.mc_lotesdocto.estatus <> 2 GROUP BY $db.mc_lotes.id");

        $array["documento"] = $documento;

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function traerFlujosEfectivo(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $filtro = $request->filtro;
            $pendiente = $request->pendiente;
            $tabla = $request->tabla;
            /* $query = $tabla == 1 ?
                "SELECT mc_flujosefectivo.* FROM mc_flujosefectivo LEFT JOIN mc_flw_pagos_det ON mc_flujosefectivo.id = mc_flw_pagos_det.IdFlw LEFT JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id"
                : "SELECT mc_flujosefectivo.id, mc_flujosefectivo.Razon AS RazonPrincipal, SUM(mc_flujosefectivo.Pendiente) AS Pendiente, mc_flujosefectivo.Tipo, (SELECT SUM(mc_flujosefectivo.Pendiente) FROM mc_flujosefectivo WHERE mc_flujosefectivo.Razon = RazonPrincipal GROUP BY Razon) AS PendientePorRazon FROM mc_flujosefectivo LEFT JOIN mc_flw_pagos_det ON mc_flujosefectivo.id = mc_flw_pagos_det.IdFlw
            LEFT JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id"; */
            $query = $tabla == 1 ?
                "SELECT mc_flujosefectivo.* FROM mc_flujosefectivo "
                : "SELECT mc_flujosefectivo.id, mc_flujosefectivo.Razon AS RazonPrincipal, SUM(mc_flujosefectivo.Pendiente) AS Pendiente, mc_flujosefectivo.Tipo, (SELECT SUM(mc_flujosefectivo.Pendiente) FROM mc_flujosefectivo WHERE mc_flujosefectivo.Razon = RazonPrincipal GROUP BY Razon) AS PendientePorRazon FROM mc_flujosefectivo";
            if ($filtro == 1) {
                /* $query .= " WHERE mc_flujosefectivo.Pendiente >= $pendiente AND (ISNULL(mc_flw_pagos.Layout) OR mc_flw_pagos.Layout = 0 OR mc_flujosefectivo.Pendiente > 0)"; */
                $query .= " WHERE mc_flujosefectivo.Pendiente >= $pendiente";
            } else if ($filtro == 3) {
                /* $query .= " LEFT JOIN mc_catproveedores ON mc_flujosefectivo.cRFC = mc_catproveedores.rfc
                WHERE mc_catproveedores.Prioridad = 1 AND mc_flujosefectivo.Pendiente >= $pendiente AND (ISNULL(mc_flw_pagos.Layout) OR mc_flw_pagos.Layout = 0 OR mc_flujosefectivo.Pendiente > 0)"; */
                $query .= " LEFT JOIN mc_catproveedores ON mc_flujosefectivo.cRFC = mc_catproveedores.rfc
                WHERE mc_catproveedores.Prioridad = 1 AND mc_flujosefectivo.Pendiente >= $pendiente";
            } else if ($filtro == 4) {
                /* $query .= " WHERE mc_flujosefectivo.Pendiente >= $pendiente AND mc_flujosefectivo.Prioridad = 1 AND (ISNULL(mc_flw_pagos.Layout) OR mc_flw_pagos.Layout = 0 OR mc_flujosefectivo.Pendiente > 0)"; */
                $query .= " WHERE mc_flujosefectivo.Pendiente >= $pendiente AND mc_flujosefectivo.Prioridad = 1";
            } else {
                /* $query .= " LEFT JOIN mc_catproveedores ON mc_flujosefectivo.cRFC = mc_catproveedores.rfc
                WHERE mc_catproveedores.Prioridad = 1 AND mc_flujosefectivo.Prioridad = 1 AND mc_flujosefectivo.Pendiente >= $pendiente AND (ISNULL(mc_flw_pagos.Layout) OR mc_flw_pagos.Layout = 0 OR mc_flujosefectivo.Pendiente > 0)"; */
                $query .= " LEFT JOIN mc_catproveedores ON mc_flujosefectivo.cRFC = mc_catproveedores.rfc
                WHERE mc_catproveedores.Prioridad = 1 AND mc_flujosefectivo.Prioridad = 1 AND mc_flujosefectivo.Pendiente >= $pendiente";
            }

            $query .= $tabla != 1 ? " GROUP BY mc_flujosefectivo.Razon, mc_flujosefectivo.Tipo, mc_flujosefectivo.id ORDER BY PendientePorRazon DESC" : " GROUP BY mc_flujosefectivo.id";
            $flujosefectivo = DB::select($query);
            $ultimaactualizacion = DB::select("SELECT IF(ISNULL(mc_flujosefectivo.Actualizacion), 'No actualizados', mc_flujosefectivo.Actualizacion) AS Actualizacion 
            FROM mc_flujosefectivo ORDER BY mc_flujosefectivo.Actualizacion DESC LIMIT 1");

            $array["flujosefectivo"] = $flujosefectivo;
            $array["ultimaactualizacion"] = $ultimaactualizacion;
            $array["tabla"] = $tabla;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function traerArchivosFlujos(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $servidor = getServidorNextcloud();
            $idempresa = $request->idEmpresa;
            $datosempresa = DB::connection("General")->select("SELECT usuario_storage, password_storage FROM mc1000 WHERE idempresa = $idempresa");
            $u_storage = $datosempresa[0]->usuario_storage;
            $p_storage = $datosempresa[0]->password_storage;

            $rutaarchivo = $request->rutaArchivo;
            $nombrearchivo = $request->nombreArchivo;
            $target_path = $rutaarchivo . $nombrearchivo;
            $link = $rutaarchivo == null || $nombrearchivo == null ? "" : GetLinkArchivo($target_path, $servidor, $u_storage, $p_storage);
            $array["link"] = $link;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function traerFlujosEfectivoAcomodados(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $filtro = $request->filtro;
            $pendiente = $request->pendiente;
            if ($filtro == 1) {
                $flujosefectivo = DB::select("SELECT flw.Razon AS Proveedor, 
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2  WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'V4 +45' ) AS V4,
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2 WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'V3 30-45') AS V3,
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2 WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'V2 15-30') AS V2,
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2 WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'V1 01-15') AS V1,
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2  WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'PorVencer') AS PorVencer,
                SUM(flw.Pendiente) AS TotalResultado
                FROM mc_flujosefectivo flw WHERE flw.Pendiente > $pendiente GROUP BY flw.Razon ORDER BY TotalResultado DESC");
            } else if ($filtro == 3) {
                $flujosefectivo = DB::select("SELECT flw.Razon AS Proveedor, 
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2  WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'V4 +45' ) AS V4,
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2 WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'V3 30-45') AS V3,
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2 WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'V2 15-30') AS V2,
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2 WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'V1 01-15') AS V1,
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2  WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'PorVencer') AS PorVencer,
                SUM(flw.Pendiente) AS TotalResultado
                FROM mc_flujosefectivo flw 
                LEFT JOIN mc_catproveedores pro ON flw.cRFC = pro.rfc WHERE pro.Prioridad = 1
                AND flw.Pendiente > $pendiente GROUP BY flw.Razon ORDER BY TotalResultado DESC");
            } else if ($filtro == 4) {
                $flujosefectivo = DB::select("SELECT flw.Razon AS Proveedor, 
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2  WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'V4 +45' ) AS V4,
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2 WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'V3 30-45') AS V3,
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2 WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'V2 15-30') AS V2,
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2 WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'V1 01-15') AS V1,
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2  WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'PorVencer') AS PorVencer,
                SUM(flw.Pendiente) AS TotalResultado
                FROM mc_flujosefectivo flw WHERE flw.Pendiente > $pendiente AND flw.Prioridad = 1 GROUP BY flw.Razon ORDER BY TotalResultado DESC");
            } else {
                $flujosefectivo = DB::select("SELECT flw.Razon AS Proveedor, 
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2  WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'V4 +45' ) AS V4,
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2 WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'V3 30-45') AS V3,
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2 WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'V2 15-30') AS V2,
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2 WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'V1 01-15') AS V1,
                (SELECT IF(ISNULL(SUM(flw2.Pendiente)), 0, SUM(flw2.Pendiente)) FROM mc_flujosefectivo flw2  WHERE flw2.Razon = flw.Razon AND flw2.Tipo = 'PorVencer') AS PorVencer,
                SUM(flw.Pendiente) AS TotalResultado
                FROM mc_flujosefectivo flw 
                LEFT JOIN mc_catproveedores pro ON flw.cRFC = pro.rfc WHERE pro.Prioridad = 1
                AND flw.Pendiente > $pendiente AND flw.Prioridad = 1 GROUP BY flw.Razon ORDER BY TotalResultado DESC");
            }

            $array["flujosefectivo"] = $flujosefectivo;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function traerFlujosEfectivoFiltrados(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $filtro = $request->filtro;
            $pendiente = $request->pendiente;
            $query = "";
            switch ($filtro) {
                case 1:
                    $query = "SELECT mc_flujosefectivo.* FROM mc_flujosefectivo LEFT JOIN mc_flw_pagos_det ON mc_flujosefectivo.id = mc_flw_pagos_det.IdFlw LEFT JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id WHERE mc_flujosefectivo.Pendiente >= $pendiente AND (ISNULL(mc_flw_pagos.Layout) OR mc_flw_pagos.Layout = 0 OR mc_flujosefectivo.Pendiente > 0)";
                    break;
                case 3:
                    $query = "SELECT mc_flujosefectivo.* FROM mc_flujosefectivo LEFT JOIN mc_catproveedores ON mc_flujosefectivo.cRFC = mc_catproveedores.rfc LEFT JOIN mc_flw_pagos_det ON mc_flujosefectivo.id = mc_flw_pagos_det.IdFlw LEFT JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id WHERE mc_catproveedores.Prioridad = 1 AND mc_flujosefectivo.Pendiente >= $pendiente AND (ISNULL(mc_flw_pagos.Layout) OR mc_flw_pagos.Layout = 0 OR mc_flujosefectivo.Pendiente > 0)";
                    break;
                case 4:
                    $query = "SELECT mc_flujosefectivo.* FROM mc_flujosefectivo LEFT JOIN mc_flw_pagos_det ON mc_flujosefectivo.id = mc_flw_pagos_det.IdFlw LEFT JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id WHERE mc_flujosefectivo.Prioridad = 1 AND mc_flujosefectivo.Pendiente >= $pendiente AND (ISNULL(mc_flw_pagos.Layout) OR mc_flw_pagos.Layout = 0 OR mc_flujosefectivo.Pendiente > 0)";
                    break;
                case 6:
                    $query = "SELECT mc_flujosefectivo.* FROM mc_flujosefectivo LEFT JOIN mc_catproveedores ON mc_flujosefectivo.cRFC = mc_catproveedores.rfc LEFT JOIN mc_flw_pagos_det ON mc_flujosefectivo.id = mc_flw_pagos_det.IdFlw LEFT JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id WHERE mc_catproveedores.Prioridad = 1 AND mc_flujosefectivo.Prioridad = 1 AND mc_flujosefectivo.Pendiente >= $pendiente AND (ISNULL(mc_flw_pagos.Layout) OR mc_flw_pagos.Layout = 0 OR mc_flujosefectivo.Pendiente > 0)";
                    break;
                default:
                    break;
            }

            $forma = $request->forma;

            switch ($forma) {
                case 1:
                    $query .= " AND mc_flujosefectivo.Razon = '$request->razon' GROUP BY mc_flujosefectivo.id";
                    break;
                case 2:
                    $query .= " AND mc_flujosefectivo.Tipo = '$request->tipo' GROUP BY mc_flujosefectivo.id";
                    break;
                case 3:
                    $query .= " AND mc_flujosefectivo.Razon = '$request->razon' AND mc_flujosefectivo.Tipo = '$request->tipo' GROUP BY mc_flujosefectivo.id";
                    break;
                case 4:
                    $query .= "SELECT mc_flujosefectivo.*, mc_flw_pagos_det.Importe, mc_flw_pagos.Layout FROM mc_flujosefectivo 
                    INNER JOIN mc_flw_pagos_det ON mc_flujosefectivo.id = mc_flw_pagos_det.IdFlw 
                    INNER JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id
                    WHERE (mc_flujosefectivo.id = " . $request->ids[0];
                    for ($x = 1; $x < count($request->ids); $x++) {
                        $query .= " OR mc_flujosefectivo.id = " . $request->ids[$x];
                    }
                    $query .= ") AND mc_flw_pagos.Layout = 0 GROUP BY mc_flujosefectivo.id";
                    break;
                case 5:
                    $query .= " GROUP BY mc_flujosefectivo.id";
                    break;
                default:
                    break;
            }
            /* $array["query"] = $query; */
            $flujosefectivo = DB::select($query);
            $array["flujosefectivo"] = $flujosefectivo;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function cargarFlujosEfectivo(Request $request)
    {
        $permisos = $request->permisos;
        $valida = verificaPermisos($permisos["usuario"], $permisos["pwd"], $permisos["rfc"], $permisos["idsubmenu"]);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $flujos = $request->flujos;
            $procesando = $permisos["Procesando"];
            /* $flujostotales = DB::select('select * from mc_flujosefectivo'); */

            if ($procesando == 1) {
                DB::table('mc_flujosefectivo')->update(['Procesando' => 0]);
            }
            for ($x = 0; $x < count($flujos) && array_key_exists('IdDoc', $flujos[$x]); $x++) {
                $flujoencontrado = DB::select('select * from mc_flujosefectivo where IdDoc = ? and Suc = ?', [$flujos[$x]["IdDoc"], $flujos[$x]["Suc"]]);
                if (count($flujoencontrado) > 0) {
                    DB::table('mc_flujosefectivo')->where("IdDoc", $flujos[$x]["IdDoc"])->where("Suc", $flujos[$x]["Suc"])->update(['Pendiente' => $flujos[$x]["Pendiente"], "IdUsuario" => $flujos[$x]["IdUsuario"], "Comentarios" => $flujos[$x]["Comentarios"], "Prioridad" => $flujos[$x]["Prioridad"], 'Procesando' => 1, "Actualizacion" => $permisos["Actualizacion"], "ImporteOriginal" => $flujos[$x]["ImporteOriginal"], "TipoCambio" => $flujos[$x]["TipoCambio"], "Moneda" => $flujos[$x]["Moneda"], "RutaArchivo" => $flujos[$x]["RutaArchivo"], "NombreArchivo" => $flujos[$x]["NombreArchivo"]]);
                    //$IdDoc = $flujos[$x]["IdDoc"];
                } else {
                    DB::table('mc_flujosefectivo')->insert(['IdDoc' => $flujos[$x]["IdDoc"], 'Idcon' => $flujos[$x]["Idcon"], "Fecha" => $flujos[$x]["Fecha"], "Vence" => $flujos[$x]["Vence"], "Idclien" => $flujos[$x]["Idclien"], "Razon" => trim($flujos[$x]["Razon"]), "CodConcepto" => $flujos[$x]["CodConcepto"], "Concepto" => $flujos[$x]["Concepto"], "Serie" => $flujos[$x]["Serie"], "Folio" => $flujos[$x]["Folio"], "Total" => $flujos[$x]["Total"], "Pendiente" => $flujos[$x]["Pendiente"], "Tipo" => trim($flujos[$x]["Tipo"]), "Suc" => $flujos[$x]["Suc"], "cRFC" => $flujos[$x]["cRFC"], "SaldoInt" => $flujos[$x]["SaldoInt"], "IdMoneda" => $flujos[$x]["IdMoneda"], "IdUsuario" => $flujos[$x]["IdUsuario"], "Comentarios" => $flujos[$x]["Comentarios"], "Prioridad" => $flujos[$x]["Prioridad"], "Procesando" => 1, "Actualizacion" => $permisos["Actualizacion"], "ImporteOriginal" => $flujos[$x]["ImporteOriginal"], "TipoCambio" => $flujos[$x]["TipoCambio"], "Moneda" => $flujos[$x]["Moneda"], "RutaArchivo" => $flujos[$x]["RutaArchivo"], "NombreArchivo" => $flujos[$x]["NombreArchivo"]]);
                    //$IdDoc = 0;
                }
                /* for ($y = 0; $y < count($flujostotales) && $IdDoc != 0; $y++) {
                    if ($IdDoc == $flujostotales[$y]->IdDoc) {
                        unset($flujostotales[$y]);
                        $flujostotales = array_values($flujostotales);
                    }
                } */
            }

            if ($procesando == 2) {
                $pagodeteliminar = DB::select('select * from mc_flujosefectivo where Procesando = ?', [0]);
                for ($y = 0; $y < count($pagodeteliminar); $y++) {
                    $pagoeliminar = DB::select('select * from mc_flw_pagos_det where IdFlw = ?', [$pagodeteliminar[$y]->id]);
                    for ($z = 0; $z < count($pagoeliminar); $z++) {
                        DB::table('mc_flw_pagos')->where("id", $pagoeliminar[$z]->IdPago)->delete();
                    }
                    DB::table('mc_flw_pagos_det')->where("IdFlw", $pagodeteliminar[$y]->id)->delete();
                }
                DB::table('mc_flujosefectivo')->where("Procesando", 0)->delete();
            }

            /* for ($x = 0; $x < count($flujostotales); $x++) {
                DB::table('mc_flujosefectivo')->where("IdDoc", $flujostotales[$x]->IdDoc)->where("Suc", $flujostotales[$x]->Suc)->delete();
            } */
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function cargarProveedores(Request $request)
    {
        $permisos = $request->permisos;
        $valida = verificaPermisos($permisos["usuario"], $permisos["pwd"], $permisos["rfc"], $permisos["idsubmenu"]);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $proveedores = $request->proveedores;

            for ($x = 0; $x < count($proveedores); $x++) {
                $proveedorencontrado = DB::select('select * from mc_catproveedores where codigo = ? and sucursal = ?', [$proveedores[$x]["codigo"], trim($proveedores[$x]["sucursal"])]);
                if (count($proveedorencontrado) == 0) {
                    DB::table('mc_catproveedores')->insert(['codigo' => $proveedores[$x]["codigo"], 'rfc' => trim($proveedores[$x]["rfc"]), "razonsocial" => trim($proveedores[$x]["razonsocial"]), "sucursal" => trim($proveedores[$x]["sucursal"]), "IdMoneda" => $proveedores[$x]["IdMoneda"], "Escliente" => $proveedores[$x]["Escliente"], "Correo1" => $proveedores[$x]["Correo1"], "Correo2" => $proveedores[$x]["Correo2"], "Correo3" => $proveedores[$x]["Correo3"]]);
                } else {
                    DB::table('mc_catproveedores')->where("codigo", $proveedores[$x]["codigo"])->update(['sucursal' => $proveedores[$x]["sucursal"], "IdMoneda" => $proveedores[$x]["IdMoneda"], 'Escliente' => $proveedores[$x]["Escliente"], "Correo1" => $proveedores[$x]["Correo1"], "Correo2" => $proveedores[$x]["Correo2"], "Correo3" => $proveedores[$x]["Correo3"]]);
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function cargarCuentasPropias(Request $request)
    {
        $permisos = $request->permisos;
        $valida = verificaPermisos($permisos["usuario"], $permisos["pwd"], $permisos["rfc"], $permisos["idsubmenu"]);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $cuentas = $request->cuentas;

            for ($x = 0; $x < count($cuentas); $x++) {
                $cuentaencontrada = DB::select('select * from mc_flow_bancuentas where IdCuenta = ? ', [$cuentas[$x]["IdCuenta"]]);
                if (count($cuentaencontrada) == 0) {
                    DB::table('mc_flow_bancuentas')->insert(['IdCuenta' => $cuentas[$x]["IdCuenta"], 'Clabe' => $cuentas[$x]["Clabe"], "Cuenta" => $cuentas[$x]["Cuenta"], "Nombre" => $cuentas[$x]["Nombre"], "Banco" => $cuentas[$x]["Banco"], "IdBanco" => $cuentas[$x]["IdBanco"], "IdMoneda" => $cuentas[$x]["IdMoneda"], "Sucursal" => $cuentas[$x]["Sucursal"], "Activa" => $cuentas[$x]["Activa"]]);
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function cargarCuentasClientesProveedores(Request $request)
    {
        $permisos = $request->permisos;
        $valida = verificaPermisos($permisos["usuario"], $permisos["pwd"], $permisos["rfc"], $permisos["idsubmenu"]);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $cuentas = $request->cuentas;

            for ($x = 0; $x < count($cuentas); $x++) {
                $cuentaencontrada = DB::select('select * from mc_flow_cliproctas where Id = ? ', [$cuentas[$x]["Id"]]);
                if (count($cuentaencontrada) == 0) {
                    DB::table('mc_flow_cliproctas')->insert(['Id' => $cuentas[$x]["Id"], 'RFC' => $cuentas[$x]["RFC"], 'Cuenta' => $cuentas[$x]["Cuenta"], "Clabe" => $cuentas[$x]["Clabe"], "Banco" => $cuentas[$x]["Banco"], "IdBanco" => $cuentas[$x]["IdBanco"], "Sucursal" => $cuentas[$x]["Sucursal"], "Escliente" => $cuentas[$x]["Escliente"]]);
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getCuentasPropias(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $cuentas = DB::select("SELECT * FROM mc_flow_bancuentas WHERE Activa = 1 ORDER BY Nombre");
            $array["cuentas"] = $cuentas;
        }
                
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getCuentasClientesProveedores(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $cuentas = DB::select("SELECT mc_flow_cliproctas.*,
            IF(ISNULL(Clabe), CONCAT(REPLACE(Banco,', S.A.', ''),' ',SUBSTRING(Cuenta, -4)), CONCAT(REPLACE(Banco,', S.A.',''), ' ',SUBSTRING(Clabe, -4))) AS Layout
            FROM mc_flow_cliproctas WHERE Cuenta IS NOT NULL OR Clabe IS NOT NULL GROUP BY Id ORDER BY Layout");
            $array["cuentas"] = $cuentas;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getFlwPagos(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $Layout = $request->Layout;
            $IdUsuario = $request->IdUsuario;

            $filtro = $IdUsuario != 0 ? "WHERE mc_flw_pagos.IdUsuario = $IdUsuario ORDER BY mc_flw_pagos.Fecha DESC" : "WHERE Layout = $Layout ORDER BY mc_flw_pagos.Fecha DESC";
            $pagospendientes = DB::select("SELECT mc_flw_pagos.*, mc_flow_bancuentas.IdBanco AS idBancoOrigen, mc_flow_bancuentas.Sucursal AS sucursalOrigen, mc_flow_bancuentas.Nombre AS cuentaOrigen, REPLACE(mc_flow_bancuentas.Banco,', S.A.', '') AS bancoOrigen, mc_flow_cliproctas.IdBanco AS idBancoDestino,
            IF(ISNULL(mc_flow_cliproctas.Clabe), CONCAT(REPLACE(mc_flow_cliproctas.Banco,', S.A.', ''),' ',SUBSTRING(mc_flow_cliproctas.Cuenta, -4)), CONCAT(REPLACE(mc_flow_cliproctas.Banco,', S.A.',''), ' ',SUBSTRING(mc_flow_cliproctas.Clabe, -4))) AS cuentaDestino, mc_flow_cliproctas.Banco AS BancoDestino, mc_flow_cliproctas.Clabe AS ClabeBancoDestino, mc_flow_cliproctas.Sucursal AS sucursalDestino FROM mc_flw_pagos 
            LEFT JOIN mc_flow_bancuentas ON mc_flow_bancuentas.IdCuenta = mc_flw_pagos.IdCuentaOrigen
            LEFT JOIN mc_flow_cliproctas ON mc_flow_cliproctas.Id = mc_flw_pagos.IdCuentaDestino " . $filtro);
            for ($x = 0; $x < count($pagospendientes); $x++) {
                $pagosdetalle = DB::select('SELECT mc_flw_pagos_det.id AS IdPagoDet, mc_flw_pagos_det.ImporteOriginal AS ImporteOriginalPago, mc_flw_pagos_det.TipoCambio as TipoCambioPago, mc_flw_pagos_det.IdPago ,mc_flw_pagos_det.Importe, mc_flujosefectivo.* FROM mc_flw_pagos_det
                INNER JOIN mc_flujosefectivo ON mc_flw_pagos_det.IdFlw = mc_flujosefectivo.id
                WHERE mc_flw_pagos_det.IdPago = ?', [$pagospendientes[$x]->id]);
                for ($y = 0; $y < count($pagosdetalle); $y++) {
                    $pagospendientes[$x]->Detalles[$y] = $pagosdetalle[$y];
                }

                $pagoscorreos = DB::select('SELECT * FROM mc_flw_correos WHERE IdPago = ?', [$pagospendientes[$x]->id]);
                for ($y = 0; $y < count($pagoscorreos); $y++) {
                    $pagospendientes[$x]->Correos[$y] = $pagoscorreos[$y];
                }

                //$pagoslayouts = DB::select('SELECT * FROM mc_flw_layouts WHERE IdPago = ?', [$pagospendientes[$x]->id]);
                $pagoslayouts = DB::select('SELECT * FROM mc_flw_layouts WHERE id = ?', [$pagospendientes[$x]->IdLayout]);
                for ($y = 0; $y < count($pagoslayouts); $y++) {
                    $pagospendientes[$x]->Layouts[$y] = $pagoslayouts[$y];
                }
            }
            $array["pagospendientes"] = $pagospendientes;

            $filtrolayouts = $IdUsuario != 0 ? "WHERE mc_flw_pagos.IdUsuario = $IdUsuario GROUP BY mc_flw_layouts.id ORDER BY mc_flw_pagos.Fecha DESC, mc_flw_layouts.id ASC" : "WHERE Layout = $Layout GROUP BY mc_flw_layouts.id ORDER BY mc_flw_pagos.Fecha DESC, mc_flw_layouts.id ASC";

            $layouts = DB::select("SELECT mc_flw_layouts.*,SUM(mc_flw_pagos.Importe) AS Importe, mc_flow_bancuentas.Nombre AS CuentaOrigen,
            IF(mc_flow_bancuentas.IdBanco = mc_flow_cliproctas.IdBanco, 'Mismo Banco', 'Otros Bancos') AS BancoDestino
            FROM mc_flw_layouts 
            INNER JOIN mc_flw_pagos ON mc_flw_pagos.IdLayout = mc_flw_layouts.id 
            INNER JOIN mc_flow_bancuentas ON mc_flow_bancuentas.IdCuenta = mc_flw_pagos.IdCuentaOrigen
            INNER JOIN mc_flow_cliproctas ON mc_flow_cliproctas.Id = mc_flw_pagos.IdCuentaDestino " . $filtrolayouts);

            for ($x = 0; $x < count($layouts); $x++) {
                $pagoslayout = DB::select("SELECT mc_flw_pagos.*, mc_flow_bancuentas.Nombre AS cuentaOrigen, 
                IF(ISNULL(mc_flow_cliproctas.Clabe), CONCAT(REPLACE(mc_flow_cliproctas.Banco,', S.A.', ''),' ', SUBSTRING(mc_flow_cliproctas.Cuenta, -4)), CONCAT(REPLACE(mc_flow_cliproctas.Banco,', S.A.',''), ' ',
                SUBSTRING(mc_flow_cliproctas.Clabe, -4))) AS cuentaDestino FROM mc_flw_pagos
                LEFT JOIN mc_flow_bancuentas ON mc_flow_bancuentas.IdCuenta = mc_flw_pagos.IdCuentaOrigen
                LEFT JOIN mc_flow_cliproctas ON mc_flow_cliproctas.Id = mc_flw_pagos.IdCuentaDestino WHERE mc_flw_pagos.IdLayout = ?", [$layouts[$x]->id]);
                for ($y = 0; $y < count($pagoslayout); $y++) {
                    $documentospago = DB::select("SELECT mc_flw_pagos_det.id AS IdPagoDet, mc_flw_pagos_det.ImporteOriginal AS ImporteOriginalPago, mc_flw_pagos_det.TipoCambio AS TipoCambioPago, mc_flw_pagos_det.IdPago ,mc_flw_pagos_det.Importe, mc_flujosefectivo.* FROM mc_flw_pagos_det
                    INNER JOIN mc_flujosefectivo ON mc_flw_pagos_det.IdFlw = mc_flujosefectivo.id
                    WHERE mc_flw_pagos_det.IdPago = ?", [$pagoslayout[$y]->id]);
                    $correospago = DB::select("SELECT mc_flw_correos.* FROM mc_flw_correos WHERE mc_flw_correos.IdPago = ?", [$pagoslayout[$y]->id]);
                    $pagoslayout[$y]->documentos = $documentospago;
                    $pagoslayout[$y]->correos = $correospago;
                }
                $layouts[$x]->pagos = $pagoslayout;
            }

            $array["layouts"] = $layouts;

            $layoutsexistentes = DB::select('SELECT * FROM mc_flw_layouts_config');
            $array["layoutsexistentes"] = $layoutsexistentes;
            
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarFlwPagos(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $IdFlw = $request->IdFlw;
            $IdUsuario = $request->IdUsuario;
            $forma = $request->forma;

            if ($forma == 1) {
                $paso = $request->paso;
                if ($paso == 1) {
                    $IdsFlw = $request->IdsFlw;
                    $Fecha = $request->Fecha;
                    $Importes = $request->Importes;
                    $ImportesOriginales = $request->ImportesOriginales;
                    $TiposCambio = $request->TiposCambio;
                    $LlaveMatch = $request->LlaveMatch;
                    $Tipo = $request->Tipo;
                    $RFCS = $request->RFCS;
                    $Proveedores = $request->Proveedores;

                    for ($x = 0; $x < count($IdsFlw); $x++) {
                        $TiposCambio[$x] = $TiposCambio[$x] == -1 ? 1 : $TiposCambio[$x];
                        $pagoencontrado = DB::select('SELECT mc_flw_pagos_det.* FROM mc_flw_pagos_det LEFT JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id WHERE mc_flw_pagos_det.IdFlw = ? AND mc_flw_pagos.IdUsuario = ? AND mc_flw_pagos.Layout = ?', [$IdsFlw[$x], $IdUsuario, 0]);

                        if (count($pagoencontrado) > 0) {
                            DB::table('mc_flw_pagos_det')->where("id", $pagoencontrado[0]->id)->update([
                                "Importe" => $Importes[$x], "ImporteOriginal" => $ImportesOriginales[$x], "TipoCambio" => $TiposCambio[$x]
                            ]);
                            $importetotal = DB::select('SELECT SUM(Importe) AS Importe FROM mc_flw_pagos_det WHERE IdPago = ?', [$pagoencontrado[0]->IdPago]);
                            DB::table('mc_flw_pagos')->where("id", $pagoencontrado[0]->IdPago)->update([
                                "Importe" => $importetotal[0]->Importe, "LlaveMatch" => $LlaveMatch, "RFC" => $RFCS[$x], "Proveedor" => $Proveedores[$x]
                            ]);
                        } else {
                            $pagoProvencontrado = DB::select('SELECT * FROM mc_flw_pagos WHERE Proveedor = ? AND IdUsuario = ? AND Layout = ?', [$Proveedores[$x], $IdUsuario, 0]);

                            if (count($pagoProvencontrado) > 0) {
                                DB::table('mc_flw_pagos')->where("id", $pagoProvencontrado[0]->id)->update([
                                    "Importe" => $pagoProvencontrado[0]->Importe + $Importes[$x]
                                ]);
                                DB::table('mc_flw_pagos_det')->insert([
                                    "IdPago" => $pagoProvencontrado[0]->id, "IdFlw" => $IdsFlw[$x], "Importe" => $Importes[$x], "ImporteOriginal" => $ImportesOriginales[$x], "TipoCambio" => $TiposCambio[$x]
                                ]);
                            } else {
                                $IdPago = DB::table('mc_flw_pagos')->insertGetId([
                                    'Fecha' => $Fecha, "Importe" => $Importes[$x], "LlaveMatch" => $LlaveMatch, "Tipo" => $Tipo, "RFC" => $RFCS[$x], "Proveedor" => $Proveedores[$x], "IdUsuario" => $IdUsuario
                                ]);
                                DB::table('mc_flw_pagos_det')->insert([
                                    "IdPago" => $IdPago, "IdFlw" => $IdsFlw[$x], "Importe" => $Importes[$x], "ImporteOriginal" => $ImportesOriginales[$x], "TipoCambio" => $TiposCambio[$x]
                                ]);
                            }
                        }
                    }

                    $IdsFlwBuscados = implode(",", $IdsFlw);
                    $pagoseliminado = DB::select("SELECT mc_flw_pagos_det.* FROM mc_flw_pagos_det
                    INNER JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id
                    WHERE mc_flw_pagos.IdUsuario = $IdUsuario AND mc_flw_pagos.layout = 0 AND mc_flw_pagos_det.IdFlw NOT IN ($IdsFlwBuscados)");
                    for ($y = 0; $y < count($pagoseliminado); $y++) {
                        DB::table('mc_flw_pagos_det')->where("id", $pagoseliminado[$y]->id)->delete();

                        $buscarpagosdet = DB::select('SELECT mc_flw_pagos_det.* FROM mc_flw_pagos_det 
                        WHERE mc_flw_pagos_det.IdPago = ?', [$pagoseliminado[$y]->IdPago]);
                        if (count($buscarpagosdet) > 0) {
                            $importetotal = DB::select('SELECT SUM(Importe) AS Importe FROM mc_flw_pagos_det WHERE IdPago = ?', [$pagoseliminado[$y]->IdPago]);
                            DB::table('mc_flw_pagos')->where("id", $pagoseliminado[$y]->IdPago)->update([
                                "Importe" => $importetotal[0]->Importe
                            ]);
                        } else {
                            DB::table('mc_flw_pagos')->where("id", $pagoseliminado[$y]->IdPago)->where("IdUsuario", $IdUsuario)->delete();
                        }
                    }
                } else if ($paso == 2) {
                    $idsflw = $request->idsflw;
                    $idscuentasorigen = $request->idscuentasorigen;
                    $idscuentasdestino = $request->idscuentasdestino;
                    $fechas = $request->fechas;
                    /* $tipos = $request->tipos; */


                    for ($x = 0; $x < count($idsflw); $x++) {
                        $pagoencontrado = DB::select('SELECT mc_flw_pagos_det.* FROM mc_flw_pagos_det 
                        INNER JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id
                        WHERE mc_flw_pagos_det.IdFlw = ? AND mc_flw_pagos.IdUsuario = ? AND mc_flw_pagos.Layout = ?', [$idsflw[$x], $IdUsuario, 0]);
                        if (count($pagoencontrado) > 0) {
                            DB::table('mc_flw_pagos')->where("id", $pagoencontrado[0]->IdPago)->update(['Fecha' => $fechas[$x], 'Tipo' => 1/* $tipos[$x] */, 'idCuentaOrigen' => $idscuentasorigen[$x], 'idCuentaDestino' => $idscuentasdestino[$x]]);
                        }
                    }
                    /* for ($x = 0; $x < count($idsflw); $x++) {
                        $pagoencontrado = DB::select('SELECT mc_flw_pagos_det.* FROM mc_flw_pagos_det 
                        INNER JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id
                        WHERE IdFlw = ? AND mc_flw_pagos.IdUsuario = ?', [$idsflw[$x], $IdUsuario]);
                        if (count($pagoencontrado) > 0) {
                            $pagodetencontrado = DB::select('select * from mc_flw_pagos_det where idPago = ?', [$pagoencontrado[0]->id]);
                            if (count($pagodetencontrado) > 0) {
                                DB::table('mc_flw_pagos_det')->where("id", $pagodetencontrado[0]->id)->update(['idCuentaOrigen' => $idscuentasorigen[$x], 'idCuentaDestino' => $idscuentasdestino[$x], 'fecha' => $fechas[$x], 'tipo' => $tipos[$x]]);
                            } else {
                                DB::table('mc_flw_pagos_det')->insert(['idPago' => $pagoencontrado[0]->id, 'idCuentaOrigen' => $idscuentasorigen[$x], 'idCuentaDestino' => $idscuentasdestino[$x], 'fecha' => $fechas[$x], 'tipo' => $tipos[$x]]);
                            }
                        }
                    } */
                } else if ($paso == 3) {
                    $idsflw = $request->idsflw;

                    for ($x = 0; $x < count($idsflw); $x++) {
                        $pagoencontrado = DB::select('SELECT mc_flw_pagos_det.* FROM mc_flw_pagos_det 
                        INNER JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id
                        WHERE mc_flw_pagos_det.IdFlw = ? AND mc_flw_pagos.IdUsuario = ? AND mc_flw_pagos.Layout = ?', [$idsflw[$x], $IdUsuario, 0]);
                        if (count($pagoencontrado) > 0) {
                            $IdPago = $pagoencontrado[0]->IdPago;
                            $LlaveMatch = "[m";
                            for($y=0 ; $y<(6 - strlen($IdPago)) ; $y++) {
                                $LlaveMatch .= "0";
                            }
                            $LlaveMatch .= $IdPago."]";
                            DB::table('mc_flw_pagos')->where("id", $IdPago)->update(['LlaveMatch' => $LlaveMatch]);
                        }
                        
                    }
                    
                    $fechasInstruccionesAdicionales = $request->fechasInstruccionesAdicionales;
                    $importesInstruccionesAdicionales = $request->importesInstruccionesAdicionales;
                    $tiposInstruccionesAdicionales = $request->tiposInstruccionesAdicionales;
                    $rfcsInstruccionesAdicionales = $request->rfcsInstruccionesAdicionales;
                    $proveedoresInstruccionesAdicionales = $request->proveedoresInstruccionesAdicionales;
                    $idsCuentasOrigenInstruccionesAdicionales = $request->idsCuentasOrigenInstruccionesAdicionales;
                    $idsCuentasDestinoInstruccionesAdicionales = $request->idsCuentasDestinoInstruccionesAdicionales;

                    $pagosadicionalesencontrados = DB::select('SELECT * FROM mc_flw_pagos WHERE Tipo <> ? AND IdUsuario = ? AND Layout = ?', [1, $IdUsuario, 0]);

                    for($x = 0 ; $x < count($pagosadicionalesencontrados) ; $x++) {
                        DB::table('mc_flw_pagos')->where("id", $pagosadicionalesencontrados[$x]->id)->delete();
                        DB::table('mc_flw_pagos_det')->where("IdPago", $pagosadicionalesencontrados[$x]->id)->delete();
                    }

                    for ($x = 0; $x < count($fechasInstruccionesAdicionales); $x++) {
                        $IdPago = DB::table('mc_flw_pagos')->insertGetId(['Fecha' => $fechasInstruccionesAdicionales[$x], 'Importe' => $importesInstruccionesAdicionales[$x], 'Tipo' => $tiposInstruccionesAdicionales[$x], 'RFC' => $rfcsInstruccionesAdicionales[$x], 'Proveedor' => $proveedoresInstruccionesAdicionales[$x], 'IdCuentaOrigen' => $idsCuentasOrigenInstruccionesAdicionales[$x], 'IdCuentaDestino' => $idsCuentasDestinoInstruccionesAdicionales[$x], 'IdUsuario' => $IdUsuario]);

                        DB::table('mc_flw_pagos_det')->insert([
                            "IdPago" => $IdPago, "Importe" => $importesInstruccionesAdicionales[$x], "ImporteOriginal" => $importesInstruccionesAdicionales[$x], "TipoCambio" => 1
                        ]);

                        $LlaveMatch = "[m";
                        for($y=0 ; $y<(6 - strlen($IdPago)) ; $y++) {
                            $LlaveMatch .= "0";
                        }
                        $LlaveMatch .= $IdPago."]";
                        DB::table('mc_flw_pagos')->where("id", $IdPago)->update(['LlaveMatch' => $LlaveMatch]);

                        $array["idPago"][$x] = $IdPago;

                    }
                }
            } else {
                $IdPago = $request->IdPago;
                $Tipo = $request->Tipo;
                $IdEmpresa = $request->IdEmpresa;

                if($Tipo == 1) {
                    $pagoencontrado = DB::select("SELECT mc_flw_pagos_det.*, FORMAT(mc_flw_pagos.Importe,2) AS ImportePago, mc_flw_pagos.Proveedor,
                    mc_flw_pagos.IdCuentaOrigen, mc_flow_bancuentas.Nombre AS CuentaOrigen, mc_flw_pagos.IdCuentaDestino,
                    IF(ISNULL(mc_flow_cliproctas.Clabe), CONCAT(REPLACE(mc_flow_cliproctas.Banco,', S.A.', ''),' ',
                    SUBSTRING(mc_flow_cliproctas.Cuenta, -4)), CONCAT(REPLACE(mc_flow_cliproctas.Banco,', S.A.',''), ' ',
                    SUBSTRING(mc_flow_cliproctas.Clabe, -4))) AS CuentaDestino, mc_flw_pagos.IdLayout FROM mc_flw_pagos_det 
                    INNER JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id
                    INNER JOIN mc_flow_bancuentas ON mc_flw_pagos.IdCuentaOrigen = mc_flow_bancuentas.IdCuenta
                    INNER JOIN mc_flow_cliproctas ON mc_flw_pagos.IdCuentaDestino = mc_flow_cliproctas.Id WHERE mc_flw_pagos_det.IdFlw = ? AND mc_flw_pagos.IdUsuario = ? AND mc_flw_pagos.Layout = ? AND mc_flw_pagos.id = ?", [$IdFlw, $IdUsuario, 1, $IdPago]);

                    $pagodetantes = DB::select('SELECT mc_flw_pagos.Fecha, 
                    CONCAT(IF(ISNULL(mc_flujosefectivo.Serie),"Sin Serie" ,mc_flujosefectivo.Serie),"-" ,mc_flujosefectivo.Folio) AS SerieFolio, 
                    CONCAT("$",FORMAT((mc_flw_pagos_det.Importe + mc_flujosefectivo.Pendiente), 2)) AS Total, 
                    CONCAT("$", FORMAT(mc_flw_pagos_det.Importe, 2)) AS Pagado, CONCAT("$", FORMAT(mc_flujosefectivo.Pendiente, 2)) AS Pendiente 
                    FROM mc_flw_pagos_det INNER JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id
                    INNER JOIN mc_flujosefectivo ON mc_flw_pagos_det.IdFlw = mc_flujosefectivo.id WHERE mc_flw_pagos_det.IdPago = ?', [$pagoencontrado[0]->IdPago]);

                    DB::table('mc_flw_pagos_det')->where("id", $pagoencontrado[0]->id)->delete();

                    $pagodetdespues = DB::select('SELECT mc_flw_pagos.Fecha, 
                    CONCAT(IF(ISNULL(mc_flujosefectivo.Serie),"Sin Serie" ,mc_flujosefectivo.Serie),"-" ,mc_flujosefectivo.Folio) AS SerieFolio, 
                    CONCAT("$",FORMAT((mc_flw_pagos_det.Importe + mc_flujosefectivo.Pendiente), 2)) AS Total, 
                    CONCAT("$", FORMAT(mc_flw_pagos_det.Importe, 2)) AS Pagado, CONCAT("$", FORMAT(mc_flujosefectivo.Pendiente, 2)) AS Pendiente 
                    FROM mc_flw_pagos_det INNER JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id
                    INNER JOIN mc_flujosefectivo ON mc_flw_pagos_det.IdFlw = mc_flujosefectivo.id WHERE mc_flw_pagos_det.IdPago = ?', [$pagoencontrado[0]->IdPago]);
        
                    $DatosEmpresa = DB::connection("General")->select("SELECT nombreempresa, usuario_storage, password_storage FROM mc1000 WHERE idempresa = $IdEmpresa");
                    $nombreempresa = $DatosEmpresa[0]->nombreempresa;
                    $usuariostorage = $DatosEmpresa[0]->usuario_storage;
                    $passwordstorage = $DatosEmpresa[0]->password_storage;

                    if (count($pagodetdespues) > 0) {
                        $importetotalnuevo = DB::select('SELECT SUM(Importe) AS Importe, FORMAT(SUM(Importe), 2) AS ImporteConFormato FROM mc_flw_pagos_det WHERE IdPago = ?', [$pagoencontrado[0]->IdPago]);

                        DB::table('mc_flw_pagos')->where("id", $pagoencontrado[0]->IdPago)->update([
                            "Importe" => $importetotalnuevo[0]->Importe
                        ]);

                        $infocorreospago = DB::select('SELECT * FROM mc_flw_correos WHERE IdPago = ?', [$pagoencontrado[0]->IdPago]);
                        if (count($infocorreospago) > 0) {

                            $CodigoMensajeNuevo = rand(1000000000, 9999999999);
                            $ValidarCodigoMensajeNuevo = false;
                            while ($ValidarCodigoMensajeNuevo) {
                                $codigomensajeencontrado = DB::select('SELECT * FROM mc_flw_correos WHERE CodigoMensaje = ?', [$CodigoMensajeNuevo]);
                                if (count($codigomensajeencontrado) == 0) {
                                    $ValidarCodigoMensajeNuevo = true;
                                } else {
                                    $CodigoMensajeNuevo = rand(1000000000, 9999999999);
                                }
                            }

                            $data["titulo"] = "Modificación De Pago De " . $nombreempresa;
                            $data["codigoMensaje"] = $CodigoMensajeNuevo. " (Favor de ignorar el mensaje con código " . $infocorreospago[0]->CodigoMensaje . ")";
                            $data["cuentaOrigen"] = $pagoencontrado[0]->CuentaOrigen;
                            $data["cuentaDestino"] = $pagoencontrado[0]->CuentaDestino;
                            $data["proveedor"] = $pagoencontrado[0]->Proveedor;
                            $data["importePagado"] = "$".$importetotalnuevo[0]->ImporteConFormato;
                            $data["detallesPago"] = $pagodetdespues;

                            $Correos = [];
                            for ($x = 0; $x < count($infocorreospago); $x++) {
                                $Correos[$x] = $infocorreospago[$x]->Correo;
                            }
                            $CorreoPrincipal = $Correos[0];
                            unset($Correos[0]);
                            $CorreosCC = array_values($Correos);

                            DB::table('mc_flw_correos')->where("IdPago", $pagoencontrado[0]->IdPago)->update(['CodigoMensaje' => $CodigoMensajeNuevo]);
                            if (count($CorreosCC) == 0) {
                                Mail::to($CorreoPrincipal)->send(new MensajesLayouts($data));
                            } else {
                                Mail::to($CorreoPrincipal)->cc($CorreosCC)->send(new MensajesLayouts($data));
                            }
                        }
                    } else {
                        DB::table('mc_flw_pagos')->where("id", $pagoencontrado[0]->IdPago)->where("IdUsuario", $IdUsuario)->delete();
                        $infocorreospago = DB::select('SELECT * FROM mc_flw_correos WHERE IdPago = ?', [$pagoencontrado[0]->IdPago]);
                        DB::table('mc_flw_correos')->where("IdPago", $pagoencontrado[0]->IdPago)->delete();

                        if (count($infocorreospago) > 0) {

                            $data["titulo"] = "Pago De " . $nombreempresa . " Cancelado";
                            $data["codigoMensaje"] = $infocorreospago[0]->CodigoMensaje ." (Cancelado)";
                            $data["cuentaOrigen"] = $pagoencontrado[0]->CuentaOrigen;
                            $data["cuentaDestino"] = $pagoencontrado[0]->CuentaDestino;
                            $data["proveedor"] = $pagoencontrado[0]->Proveedor;
                            $data["importePagado"] = $pagoencontrado[0]->ImportePago;
                            $data["detallesPago"] = $pagodetantes;

                            $Correos = [];
                            for ($x = 0; $x < count($infocorreospago); $x++) {
                                $Correos[$x] = $infocorreospago[$x]->Correo;
                            }
                            $CorreoPrincipal = $Correos[0];
                            unset($Correos[0]);
                            $CorreosCC = array_values($Correos);

                            if (count($CorreosCC) == 0) {
                                Mail::to($CorreoPrincipal)->send(new MensajesLayouts($data));
                            } else {
                                Mail::to($CorreoPrincipal)->cc($CorreosCC)->send(new MensajesLayouts($data));
                            }
                        }
                    }

                    DB::table('mc_flujosefectivo')->where("id", $pagoencontrado[0]->IdFlw)->update(['Pendiente' => DB::raw('Pendiente + ' . $pagoencontrado[0]->Importe), 'ImporteOriginal' => DB::raw('ImporteOriginal + ' . $pagoencontrado[0]->ImporteOriginal)]);

                    $respuesta = actualizarLayout($IdUsuario, $pagoencontrado[0]->IdLayout, $request->rfc, $usuariostorage, $passwordstorage);
                }
                else {
                    $infopago = DB::select('SELECT * FROM mc_flw_pagos WHERE IdLayout = (SELECT IdLayout FROM mc_flw_pagos WHERE id = ?)', [$IdPago]);
                    $pagodetalles = DB::select("SELECT mc_flw_pagos.Fecha, CONCAT('$', FORMAT(mc_flw_pagos.Importe, 2)) AS Importe,
                        (
                        CASE
                            WHEN mc_flw_pagos.Tipo = 2 THEN 'Anticipo a proveedores'
                            WHEN mc_flw_pagos.Tipo = 3 THEN 'Pago a prestamos a acreedores'
                            WHEN mc_flw_pagos.Tipo = 4 THEN 'Entrega de prestamos a deudores'
                            ELSE 'Flujo de efectivo'
                        END
                        ) AS TipoPago,
                        mc_flw_pagos.Proveedor , mc_flow_bancuentas.Nombre AS cuentaOrigen,
                        IF(ISNULL(mc_flow_cliproctas.Clabe), CONCAT(REPLACE(mc_flow_cliproctas.Banco,', S.A.', ''),' ',
                        SUBSTRING(mc_flow_cliproctas.Cuenta, -4)), CONCAT(REPLACE(mc_flow_cliproctas.Banco,', S.A.',''), ' ',
                        SUBSTRING(mc_flow_cliproctas.Clabe, -4))) AS cuentaDestino FROM mc_flw_pagos
                        LEFT JOIN mc_flow_bancuentas ON mc_flow_bancuentas.IdCuenta = mc_flw_pagos.IdCuentaOrigen
                        LEFT JOIN mc_flow_cliproctas ON mc_flow_cliproctas.Id = mc_flw_pagos.IdCuentaDestino
                        WHERE mc_flw_pagos.id = ?", [$IdPago]);
                    DB::table('mc_flw_pagos_det')->where("IdPago", $IdPago)->delete();
                    DB::table('mc_flw_pagos')->where("id", $IdPago)->where("IdUsuario", $IdUsuario)->delete();

                    $DatosEmpresa = DB::connection("General")->select("SELECT nombreempresa, usuario_storage, password_storage FROM mc1000 WHERE idempresa = $IdEmpresa");
                    $nombreempresa = $DatosEmpresa[0]->nombreempresa;
                    $usuariostorage = $DatosEmpresa[0]->usuario_storage;
                    $passwordstorage = $DatosEmpresa[0]->password_storage;

                    $infocorreospago = DB::select('SELECT * FROM mc_flw_correos WHERE IdPago = ?', [$IdPago]);
                    DB::table('mc_flw_correos')->where("IdPago", $IdPago)->delete();

                    if (count($infocorreospago) > 0) {

                        $data["titulo"] = "Pago De " . $nombreempresa . " Cancelado";
                        $data["codigoMensaje"] = $infocorreospago[0]->CodigoMensaje ." (Cancelado)";
                        $data["cuentaOrigen"] = $pagodetalles[0]->cuentaOrigen;
                        $data["cuentaDestino"] = $pagodetalles[0]->cuentaDestino;
                        $data["proveedor"] = $pagodetalles[0]->Proveedor;
                        $data["importePagado"] = $pagodetalles[0]->Importe;
                        $data["detallesPago"] = $pagodetalles;

                        $Correos = [];
                        for ($x = 0; $x < count($infocorreospago); $x++) {
                            $Correos[$x] = $infocorreospago[$x]->Correo;
                        }
                        $CorreoPrincipal = $Correos[0];
                        unset($Correos[0]);
                        $CorreosCC = array_values($Correos);

                        if (count($CorreosCC) == 0) {
                            Mail::to($CorreoPrincipal)->send(new MensajesLayoutsPagosAdicionales($data));
                        } else {
                            Mail::to($CorreoPrincipal)->cc($CorreosCC)->send(new MensajesLayoutsPagosAdicionales($data));
                        }
                    }

                    $respuesta = actualizarLayout($IdUsuario, $infopago[0]->IdLayout, $request->rfc, $usuariostorage, $passwordstorage);
                }
                $array["respuesta"] = $respuesta;
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function eliminarFlwPagos(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $idspagodet = $request->idspagodet;
            $idspago = $request->idspago;
            $idspagosadicionales = $request->idspagosadicionales;
            $idusuario = $request->idusuario;

            for ($x = 0; $x < count($idspagodet); $x++) {
                DB::table('mc_flw_pagos_det')->where("id", $idspagodet[$x])->delete();
                $buscarpagosdet = DB::select('SELECT mc_flw_pagos_det.* FROM mc_flw_pagos_det 
                WHERE mc_flw_pagos_det.IdPago = ?', [$idspago[$x]]);
                if (count($buscarpagosdet) > 0) {
                    $importetotal = DB::select('SELECT SUM(Importe) AS Importe FROM mc_flw_pagos_det WHERE IdPago = ?', [$idspago[$x]]);
                    DB::table('mc_flw_pagos')->where("id", $idspago[$x])->update([
                        "Importe" => $importetotal[0]->Importe
                    ]);
                } else {
                    DB::table('mc_flw_pagos')->where("id", $idspago[$x])->where("IdUsuario", $idusuario)->delete();
                }
            }

            for ($x = 0; $x < count($idspagosadicionales); $x++) {
                DB::table('mc_flw_pagos_det')->where("IdPago", $idspagosadicionales[$x])->delete();
                DB::table('mc_flw_pagos')->where("id", $idspagosadicionales[$x])->where("IdUsuario", $idusuario)->delete();
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function cambiarEstatusLayoutFlwPagos(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $IdsFlw = $request->IdsFlw;
            $NombreEmpresa = $request->nombreempresa;
            $IdUsuario = $request->IdUsuario;
            $Importes = $request->Importes;
            $PagosOriginales = $request->PagosOriginales;
            $TiposCambio = $request->TiposCambio;
            $Correos = $request->Correos;
            $CorreosPagosAdicionales = $request->CorreosPagosAdicionales;
            $CuentasOrigen = $request->CuentasOrigen;
            $CuentasDestino = $request->CuentasDestino;
            $IdsFlwsBancos = $request->idsFlwsBancos;
            $IdsBancosOrigen = $request->IdsBancosOrigen;
            $CombinacionesBancos = $request->combinacionesBancos;
            $TiposDocumentos = $request->TiposDocumentos;
            $TiposDocumentosBancos = $request->TiposDocumentosBancos;
            $FechaHoraLayout = $request->FechaHoraLayout;
            /* $SucursalesOrigen = $request->sucursalesOrigen;
            $SucursalesDestino = $request->sucursalesDestino;  */

            for ($x = 0; $x < count($IdsFlw); $x++) {
                $flujo = DB::select('SELECT * FROM mc_flujosefectivo WHERE id = ?', [$IdsFlw[$x]]);
                
                if($flujo[0]->Pendiente == "0") {
                    $array["error"] = 58;
                    return json_encode($array, JSON_UNESCAPED_UNICODE);
                }
            }

            $IdEmpresa = $request->IdEmpresa;
            $RFC = $request->rfc;
            $Servidor = getServidorNextcloud();
            $DatosEmpresa = DB::connection("General")->select("SELECT usuario_storage, password_storage FROM mc1000 WHERE idempresa = $IdEmpresa");
            $u_storage = $DatosEmpresa[0]->usuario_storage;
            $p_storage = $DatosEmpresa[0]->password_storage;
            $FechaServidor = date("YmdHis");
            /* $IdsCuentasOrigen = $request->IdsCuentasOrigen;
            $IdsCuentasDestino = $request->IdsCuentasDestino; */
            $ProveedoresInfoBancos = $request->ProveedoresInfoBancos;
            
            //$TipoLayout = $request->TipoLayout;
            $CuentasBeneficiarios = $request->CuentasBeneficiarios;
            //$ImportesPagados = $request->ImportesPagados;
            $ImportesPorPagos = $request->ImportesPorPagos;
            $IdsFlwPorPago = $request->idsFlwPorPago;
            $RfcProveedores = $request->rfcProveedores;
            /* $ReferenciaNumerica = $request->referenciaNumerica; */
              
            $CarpetaDestino = $_SERVER['DOCUMENT_ROOT'] . '/public/archivostemp/';
            mkdir($CarpetaDestino . "Layouts_" . $IdUsuario . "_" . $RFC . "_" . $FechaServidor, 0700);
            $CarpetaDestino = $CarpetaDestino . "Layouts_" . $IdUsuario . "_" . $RFC . "_" . $FechaServidor . "/";

            for ($x = 0; $x < count($IdsBancosOrigen); $x++) {
                $datosLayout["cuentaBeneficiario"] = explode("-$-", $CuentasBeneficiarios[$x]);
                /* $datosLayout["importe"] = explode("-$-", $ImportesPorPagos[$x]); */
                $datosLayout["razon"] = explode("-$-", $ProveedoresInfoBancos[$x]);

                $datosLayout["nombre"] = explode("-$-", $ProveedoresInfoBancos[$x]);
                $referenciaalfanumerica = "alfa1";

                $numeroConsecutivo = "001";
                $motivoPago = "prueba motivo";
                $indicadorComprobanteFiscal = "1";
                $importeIVA = "000";
                $fechaHora = $FechaHoraLayout;
                /* $sucursal = "0394";
                $sucursalOrigen = "7008";
                $sucursalDestino = "0441"; */
                for($y=1 ; $y<count($datosLayout["cuentaBeneficiario"]) ; $y++) {
                    $referenciaalfanumerica.= "-$-alfa".($y+1);
                    $numeroConsecutivo.= "-$-00".($y+1);
                    $motivoPago.= "-$-prueba motivo";
                    $indicadorComprobanteFiscal.= "-$-".($y+1);
                    $importeIVA.= "-$-000";
                    $fechaHora.= "-$-".$FechaHoraLayout;
                    /* $sucursal.= "-$-0394";
                    $sucursalOrigen.= "-$-7008";
                    $sucursalDestino.= "-$-0441"; */
                }
                $datosLayout["referenciaAlfanumerica"] = explode("-$-", $referenciaalfanumerica);
                $datosLayout["idsFlw"] = explode("-$-", $IdsFlwPorPago[$x]);

                $datosLayout["numeroConsecutivo"] = explode("-$-", $numeroConsecutivo);
                $datosLayout["motivoPago"] = explode("-$-", $motivoPago);
                $datosLayout["indicadorComprobanteFiscal"] = explode("-$-", $indicadorComprobanteFiscal);
                $datosLayout["importeIVA"] = explode("-$-", $importeIVA);
                $datosLayout["fechaHora"] = explode("-$-", $fechaHora);
                $datosLayout["RFC"] = explode("-$-", $RfcProveedores[$x]);
                $datosLayout["tipodocumento"] = explode("-$-", $TiposDocumentos[$x]);
                /* $datosLayout["sucursal"] = explode("-$-", $sucursal);
                $datosLayout["sucursalOrigen"] = explode("-$-", $SucursalesOrigen[$x]);
                $datosLayout["sucursalDestino"] = explode("-$-", $SucursalesDestino[$x]); */
                
                /* $nombrearchivonuevo = "Layout_" . $IdUsuario . "_" . $RFC . "_" . $FechaServidor . "_" . ($x+1) . ".txt"; */
                $nombrearchivonuevo = $IdUsuario . $FechaServidor . ($x+1) . ".txt";
                $urldestino = $CarpetaDestino . $nombrearchivonuevo;

                $resultado = armarLayout($IdUsuario, $IdsBancosOrigen[$x], $CombinacionesBancos[$x], $datosLayout/* , $ReferenciaNumerica */, $nombrearchivonuevo, $urldestino, $RFC, $Servidor, $u_storage, $p_storage, $FechaServidor, ($x+1), $IdsBancosOrigen);
                $array["resultado"][$x] = $resultado;
                /* return json_encode($array, JSON_UNESCAPED_UNICODE); */
                
                $array["link"] = $resultado["archivo"]["link"];

                $layoutinsertado = DB::table('mc_flw_layouts')->insertGetId(['UrlLayout' => $resultado["archivo"]["directorio"], 'NombreLayout' => $resultado["archivo"]["filename"], 'LinkLayout' => $resultado["archivo"]["link"]]);
                for($y=0 ; $y<count($IdsFlwsBancos[$x]) ; $y++) {
                    $infopagoencontrado = $TiposDocumentosBancos[$x][$y] == 1 ? DB::select('SELECT mc_flw_pagos.* FROM mc_flw_pagos INNER JOIN mc_flw_pagos_det ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id WHERE mc_flw_pagos.IdUsuario = ? AND mc_flw_pagos.Layout = ? AND mc_flw_pagos_det.IdFlw = ?', [$IdUsuario, 0, $IdsFlwsBancos[$x][$y]]) : DB::select('SELECT mc_flw_pagos.* FROM mc_flw_pagos 
                    WHERE mc_flw_pagos.IdUsuario = ? AND mc_flw_pagos.Layout = ? AND mc_flw_pagos.id = ?', [$IdUsuario, 0, $IdsFlwsBancos[$x][$y]]);
                    if($TiposDocumentosBancos[$x][$y] == 1) {
                        DB::table('mc_flw_pagos')->where("id", $infopagoencontrado[0]->id)->update(['IdLayout' => $layoutinsertado]);
                    }
                    else {
                        DB::table('mc_flw_pagos')->where("id", $infopagoencontrado[0]->id)->update(['IdLayout' => $layoutinsertado, 'Layout' => 1]);
                    }
                }
            }
            /* return json_encode($array, JSON_UNESCAPED_UNICODE); */

            if (count($IdsBancosOrigen) > 1) {
                $zip = new ZipArchive();
                $zipname = "layouts.zip";
                $zip->open($zipname, ZipArchive::CREATE | ZipArchive::OVERWRITE);
                $da = opendir($CarpetaDestino);
                $archibosaborrar = [];
                $y = 0;
                while (($archivo = readdir($da)) !== false) {
                    if (is_file($CarpetaDestino . $archivo) && $archivo != "." && $archivo != ".." && $archivo != $zipname) {
                        $zip->addFile($CarpetaDestino . $archivo, $archivo);
                        $archibosaborrar[$y] = $CarpetaDestino . $archivo;
                        $y++;
                    }
                }
                closedir($da);
                $zip->close();
                $rutaFinal = $CarpetaDestino;
                rename($zipname, "$rutaFinal/$zipname");

                /* $codigoarchivo = "Layouts_" . $IdUsuario . "_" . $RFC . "_" . $FechaServidor; */
                $codigoarchivo = $IdUsuario . $FechaServidor;
                $consecutivo = "";
                $resultado = subirArchivoNextcloud($zipname, "$rutaFinal/$zipname", $RFC, $Servidor, $u_storage, $p_storage, "Administracion", "FinanzasTesoreria", "LayoutsTemporales", $codigoarchivo, $consecutivo);
                if ($resultado["archivo"]["error"] == 0) {
                    $codigodocumento = $codigoarchivo . $consecutivo;
                    $directorio = $RFC . '/' . "Administracion" . '/' . "FinanzasTesoreria" . '/' . "LayoutsTemporales";
                    $target_path = $directorio . '/' . $codigodocumento . ".zip";
                    $array["target_path"] = $target_path;
                    $link = GetLinkArchivo($target_path, $Servidor, $u_storage, $p_storage);
                    $array["link"] = $link;
                    unlink($CarpetaDestino . $zipname);
                }

                for ($x = 0; $x < count($archibosaborrar); $x++) {
                    unlink($archibosaborrar[$x]);
                }
            }

            $urlcarpetaaborrar = substr($CarpetaDestino, 0, -1);
            rmdir($urlcarpetaaborrar);
            //return json_encode($array, JSON_UNESCAPED_UNICODE);

            $IdsPago = [];
            $CorreosMandar = [];
            $ProveedoresMandar = [];
            $ImportesMandar = [];
            $CuentasOrigenMandar = [];
            $CuentasDestinoMandar = [];
            $FoliosMandar = [];
            $CountInfo = 0;
            for ($x = 0; $x < count($IdsFlw); $x++) {
                $pagoencontrado = DB::select('SELECT mc_flw_pagos_det.*, mc_flw_pagos.Proveedor, FORMAT(mc_flw_pagos.Importe,2) AS Importe, mc_flujosefectivo.Folio FROM mc_flw_pagos_det 
                INNER JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id
                INNER JOIN mc_flujosefectivo ON mc_flw_pagos_det.IdFlw = mc_flujosefectivo.id
                WHERE mc_flw_pagos_det.IdFlw = ? AND mc_flw_pagos.IdUsuario = ? AND mc_flw_pagos.Layout = ?', [$IdsFlw[$x], $IdUsuario, 0]);
                $array["pagoencontrado"][$x] = $pagoencontrado;

                $ImporteOriginalRestar = $TiposCambio[$x] != -1 ? $PagosOriginales[$x] : $Importes[$x];
                DB::table('mc_flujosefectivo')->where("id", $IdsFlw[$x])->update(['Pendiente' => DB::raw('Pendiente - ' . $Importes[$x]), 'ImporteOriginal' => DB::raw('ImporteOriginal - ' . $ImporteOriginalRestar)]);

                if (!in_array($pagoencontrado[0]->IdPago, $IdsPago)) {
                    $IdsPago[$CountInfo] = $pagoencontrado[0]->IdPago;
                    $CorreosMandar[$CountInfo] = $Correos[$x];
                    $ProveedoresMandar[$CountInfo] = $pagoencontrado[0]->Proveedor;
                    $ImportesMandar[$CountInfo] = $pagoencontrado[0]->Importe;
                    $CuentasOrigenMandar[$CountInfo] = $CuentasOrigen[$x];
                    $CuentasDestinoMandar[$CountInfo] = $CuentasDestino[$x];
                    $FoliosMandar[$CountInfo] = $pagoencontrado[0]->Folio;
                    $CountInfo++;
                } else {
                    $pos = array_search($pagoencontrado[0]->IdPago, $IdsPago);
                    $FoliosMandar[$pos] = $FoliosMandar[$pos] . "," . $pagoencontrado[0]->Folio;
                }
            }

            for ($x = 0; $x < count($IdsPago); $x++) {
                $detallespago = DB::select('SELECT mc_flw_pagos.Fecha, 
                CONCAT(IF(ISNULL(mc_flujosefectivo.Serie),"Sin Serie" ,mc_flujosefectivo.Serie),"-" ,mc_flujosefectivo.Folio) AS SerieFolio, 
                CONCAT("$",FORMAT((mc_flw_pagos_det.Importe + mc_flujosefectivo.Pendiente), 2)) AS Total, 
                CONCAT("$", FORMAT(mc_flw_pagos_det.Importe, 2)) AS Pagado, CONCAT("$", FORMAT(mc_flujosefectivo.Pendiente, 2)) AS Pendiente 
                FROM mc_flw_pagos_det INNER JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id
                INNER JOIN mc_flujosefectivo ON mc_flw_pagos_det.IdFlw = mc_flujosefectivo.id WHERE mc_flw_pagos_det.IdPago = ? 
                AND mc_flw_pagos.IdUsuario = ? AND mc_flw_pagos.Layout = ?', [$IdsPago[$x], $IdUsuario, 0]);
                DB::table('mc_flw_pagos')->where("id", $IdsPago[$x])->update(['Layout' => 1]);
                $correos = [];
                $CountCorreos = 0;
                for ($y = 0; $y < count($CorreosMandar[$x]); $y++) {
                    $enviar = $CorreosMandar[$x][$y]["enviar"];
                    if ($enviar) {
                        $correos[$CountCorreos] = $CorreosMandar[$x][$y]["correo"];
                        $CountCorreos++;
                    }
                }

                if (count($correos) > 0) {
                    $CorreoPrincipal = $correos[0];
                    unset($correos[0]);
                    $CorreosCC = array_values($correos);

                    $CodigoMensaje = rand(1000000000, 9999999999);
                    $ValidarCodigoMensaje = false;
                    while ($ValidarCodigoMensaje) {
                        $codigomensajeencontrado = DB::select('SELECT * FROM mc_flw_correos WHERE CodigoMensaje = ?', [$CodigoMensaje]);
                        if (count($codigomensajeencontrado) == 0) {
                            $ValidarCodigoMensaje = true;
                        } else {
                            $CodigoMensaje = rand(1000000000, 9999999999);
                        }
                    }

                    $data["titulo"] = "Nueva Pago De " . $NombreEmpresa;
                    $data["codigoMensaje"] = $CodigoMensaje;
                    $data["cuentaOrigen"] = $CuentasOrigenMandar[$x];
                    $data["cuentaDestino"] = $CuentasDestinoMandar[$x];
                    $data["proveedor"] = $ProveedoresMandar[$x];
                    $data["importePagado"] = $ImportesMandar[$x];
                    $data["detallesPago"] = $detallespago;

                    DB::table('mc_flw_correos')->insert(['IdPago' => $IdsPago[$x], 'Correo' => $CorreoPrincipal, 'Tipo' => 1, 'CodigoMensaje' => $CodigoMensaje]);
                    if (count($CorreosCC) == 0) {
                        Mail::to($CorreoPrincipal)->send(new MensajesLayouts($data));
                    } else {
                        Mail::to($CorreoPrincipal)->cc($CorreosCC)->send(new MensajesLayouts($data));
                        for ($y = 0; $y < count($CorreosCC); $y++) {
                            DB::table('mc_flw_correos')->insert(['IdPago' => $IdsPago[$x], 'Correo' => $CorreosCC[$y], 'Tipo' => 2, 'CodigoMensaje' => $CodigoMensaje]);
                        }
                    }
                }
            }

            for($x=0 ; $x<count($CorreosPagosAdicionales) ; $x++) {
                if(count($CorreosPagosAdicionales[$x]) > 0) {
                    $pagodetalles = DB::select("SELECT mc_flw_pagos.Fecha, CONCAT('$', FORMAT(mc_flw_pagos.Importe, 2)) AS Importe,
                    (
                    CASE
                        WHEN mc_flw_pagos.Tipo = 2 THEN 'Anticipo a proveedores'
                        WHEN mc_flw_pagos.Tipo = 3 THEN 'Pago a prestamos a acreedores'
                        WHEN mc_flw_pagos.Tipo = 4 THEN 'Entrega de prestamos a deudores'
                        ELSE 'Flujo de efectivo'
                    END
                    ) AS TipoPago,
                    mc_flw_pagos.Proveedor , mc_flow_bancuentas.Nombre AS cuentaOrigen,
                    IF(ISNULL(mc_flow_cliproctas.Clabe), CONCAT(REPLACE(mc_flow_cliproctas.Banco,', S.A.', ''),' ',
                    SUBSTRING(mc_flow_cliproctas.Cuenta, -4)), CONCAT(REPLACE(mc_flow_cliproctas.Banco,', S.A.',''), ' ',
                    SUBSTRING(mc_flow_cliproctas.Clabe, -4))) AS cuentaDestino FROM mc_flw_pagos
                    LEFT JOIN mc_flow_bancuentas ON mc_flow_bancuentas.IdCuenta = mc_flw_pagos.IdCuentaOrigen
                    LEFT JOIN mc_flow_cliproctas ON mc_flow_cliproctas.Id = mc_flw_pagos.IdCuentaDestino
                    WHERE mc_flw_pagos.id = ?", [$CorreosPagosAdicionales[$x][0]["idPago"]]);
                    $CorreosEnviarPagosAdicionales = [];
                    $count = 0;
                    for($y=0 ; $y<count($CorreosPagosAdicionales[$x]) ; $y++) {
                        if($CorreosPagosAdicionales[$x][$y]["enviar"]) {
                            $CorreosEnviarPagosAdicionales[$count] = $CorreosPagosAdicionales[$x][$y]["correo"];
                            $count++;
                        }
                    }
                    if(count($CorreosEnviarPagosAdicionales) > 0) {
                        $CorreoPrincipalPagosAdicionales = $CorreosEnviarPagosAdicionales[0];
                        unset($CorreosEnviarPagosAdicionales[0]);
                        $CorreosCCPagosAdicionales = array_values($CorreosEnviarPagosAdicionales);

                        $CodigoMensajePagosAdicionales = rand(1000000000, 9999999999);
                        $ValidarCodigoMensajePagosAdicionales = false;
                        while ($ValidarCodigoMensajePagosAdicionales) {
                            $codigomensajeencontradopagosadicionales = DB::select('SELECT * FROM mc_flw_correos WHERE CodigoMensaje = ?', [$CodigoMensajePagosAdicionales]);
                            if (count($codigomensajeencontradopagosadicionales) == 0) {
                                $ValidarCodigoMensajePagosAdicionales = true;
                            } else {
                                $CodigoMensajePagosAdicionales = rand(1000000000, 9999999999);
                            }
                        }

                        $data["titulo"] = "Nueva Pago De " . $NombreEmpresa;
                        $data["codigoMensaje"] = $CodigoMensajePagosAdicionales;
                        $data["cuentaOrigen"] = $pagodetalles[0]->cuentaOrigen;
                        $data["cuentaDestino"] = $pagodetalles[0]->cuentaDestino;
                        $data["proveedor"] = $pagodetalles[0]->Proveedor;
                        $data["importePagado"] = $pagodetalles[0]->Importe;
                        $data["detallesPago"] = $pagodetalles;

                        DB::table('mc_flw_correos')->insert(['IdPago' => $CorreosPagosAdicionales[$x][0]["idPago"], 'Correo' => $CorreoPrincipalPagosAdicionales, 'Tipo' => 1, 'CodigoMensaje' => $CodigoMensajePagosAdicionales]);
                        if (count($CorreosCCPagosAdicionales) == 0) {
                            Mail::to($CorreoPrincipalPagosAdicionales)->send(new MensajesLayoutsPagosAdicionales($data));
                        } else {
                            Mail::to($CorreoPrincipalPagosAdicionales)->cc($CorreosCCPagosAdicionales)->send(new MensajesLayoutsPagosAdicionales($data));
                            for ($y = 0; $y < count($CorreosCCPagosAdicionales); $y++) {
                                DB::table('mc_flw_correos')->insert(['IdPago' => $CorreosPagosAdicionales[$x][0]["idPago"], 'Correo' => $CorreosCCPagosAdicionales[$y], 'Tipo' => 2, 'CodigoMensaje' => $CodigoMensajePagosAdicionales]);
                            }
                        }
                    }
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function borrarFlwPagosByLlaveMath(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $LlaveMatch = $request->LlaveMatch;

            DB::table('mc_flw_pagos')->where("LlaveMatch", $LlaveMatch)->delete();
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function traerProveedoresFiltro(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $proveedores = DB::select('SELECT * FROM mc_catproveedores WHERE mc_catproveedores.rfc IS NOT NULL ORDER BY mc_catproveedores.razonsocial');
            $array["proveedores"] = $proveedores;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function cambiarPrioridadProveedor(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $idproveedor = $request->idproveedor;
            $prioridad = $request->prioridad;
            DB::table('mc_catproveedores')->where("id", $idproveedor)->update(['Prioridad' => $prioridad]);
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function generarLayouts(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $idusuario = $request->idusuario;
            $rfc = $request->rfc;
            $servidor = getServidorNextcloud();
            $u_storage = $request->usuario_storage;
            $p_storage = $request->password_storage;
            $fecha = date("YmdHis");
            $idsbancosorigen = $request->idsbancosorigen;
            $tipolayout = $request->tipolayout;
            $cuentabeneficiario = $request->cuentabeneficiario;
            $importepagado = $request->importepagado;
            $carpetadestino = $_SERVER['DOCUMENT_ROOT'] . '/public/archivostemp/';
            mkdir($carpetadestino . "Layouts_" . $idusuario . "_" . $rfc . "_" . $fecha, 0700);
            $carpetadestino = $carpetadestino . "Layouts_" . $idusuario . "_" . $rfc . "_" . $fecha . "/";

            $array["numlayouts"] = count($idsbancosorigen);
            for ($x = 0; $x < count($idsbancosorigen); $x++) {
                $nombrearchivonuevo = "Layout_" . $idusuario . "_" . $rfc . "_" . $fecha . "_" . $x . ".txt";
                $urldestino = $carpetadestino . $nombrearchivonuevo;
                $layouturl = $tipolayout[$x] == 1 ? "http://cloud.dublock.com/index.php/s/oBBcHJqm3snMAA7/download" : "http://cloud.dublock.com/index.php/s/BynSRPHBXCo7234/download";
                $layout = fopen($layouturl, "rb");
                if ($layout) {
                    $nuevolayout = fopen($urldestino, "a");

                    if ($nuevolayout) {
                        while (!feof($layout)) {
                            fwrite($nuevolayout, fread($layout, 1024 * 8), 1024 * 8);
                        }
                        $contenidolayout = file_get_contents($urldestino);
                        $importepagadosindecimal = str_replace(".", "", $importepagado[$x]);
                        $referenciaalfanumerica = 'AAAAA0000000000';
                        $descripcion = 'XXXXX';
                        $referencianumerica = '5555';

                        if ($tipolayout[$x] == 2) {
                            $maxcuentabeneficiario = 20;
                            $maximportepagadosindecimal = 10;
                            $maxreferenciaalfanumerica = 10;
                            $maxdescripcion = 30;
                            $maxferencianumerica = 10;

                            $countcuentabeneficiario = strlen($cuentabeneficiario[$x]);
                            //$countimportepagado = strlen($importepagado);
                            $countimportepagadosindecimal = strlen($importepagadosindecimal);
                            $countreferenciaalfanumerica = strlen($referenciaalfanumerica);
                            $countdescripcion = strlen($descripcion);
                            $countreferencianumerica = strlen($referencianumerica);

                            $cuentabeneficiario[$x] = $countcuentabeneficiario < $maxcuentabeneficiario ? str_pad($cuentabeneficiario[$x], $maxcuentabeneficiario) : substr($cuentabeneficiario[$x], 0, $maxcuentabeneficiario);
                            $importepagadosindecimal = $countimportepagadosindecimal < $maximportepagadosindecimal ? str_pad($importepagadosindecimal, $maximportepagadosindecimal, "0") : substr($importepagadosindecimal, 0, $maximportepagadosindecimal);
                            $referenciaalfanumerica = $countreferenciaalfanumerica < $maxreferenciaalfanumerica ? str_pad($referenciaalfanumerica, $maxreferenciaalfanumerica) : substr($referenciaalfanumerica, 0, $maxreferenciaalfanumerica);
                            $descripcion = $countdescripcion < $maxdescripcion ? str_pad($descripcion, $maxdescripcion) : substr($descripcion, 0, $maxdescripcion);
                            $referencianumerica = $countreferencianumerica < $maxferencianumerica ? str_pad($referencianumerica, $maxferencianumerica) : substr($referencianumerica, 0, $maxferencianumerica);

                            /* $array["cuentabeneficiario"] = $cuentabeneficiario;
                            $array["importepagadosindecimal"] = $importepagadosindecimal;
                            $array["referenciaalfanumerica"] = $referenciaalfanumerica;
                            $array["descripcion"] = $descripcion;
                            $array["referencianumerica"] = $referencianumerica; */
                        }

                        $variables = array('${cuentaBeneficiario}', '${importePagadoSinDecimal}', '${importePagado}', '${referenciaAlfanumerica}', '${descripcion}', '${referenciaNumerica}');
                        $valores   = array($cuentabeneficiario[$x], $importepagadosindecimal, $importepagado[$x], $referenciaalfanumerica, $descripcion, $referencianumerica);
                        $nuevocontenido = str_replace($variables, $valores, $contenidolayout);
                        file_put_contents($urldestino, $nuevocontenido);

                        fclose($nuevolayout);
                    }
                    fclose($layout);

                    if (count($idsbancosorigen) == 1) {
                        $codigoarchivo = "Layout_" . $idusuario . "_" . $rfc . "_" . $fecha . "_" . $x;
                        $consecutivo = "";
                        $resultado = subirArchivoNextcloud($nombrearchivonuevo, $urldestino, $rfc, $servidor, $u_storage, $p_storage, "Administracion", "FinanzasTesoreria", "LayoutsTemporales", $codigoarchivo, $consecutivo);
                        if ($resultado["archivo"]["error"] == 0) {
                            $codigodocumento = $codigoarchivo . $consecutivo;
                            $directorio = $rfc . '/' . "Administracion" . '/' . "FinanzasTesoreria" . '/' . "LayoutsTemporales";
                            $target_path = $directorio . '/' . $codigodocumento . ".txt";
                            $link = GetLinkArchivo($target_path, $servidor, $u_storage, $p_storage);
                            $array["link"] = $link;
                            unlink($urldestino);
                        }
                    }
                }
            }

            if (count($idsbancosorigen) > 1) {
                $zip = new ZipArchive();
                $zipname = "layouts.zip";
                $zip->open($zipname, ZipArchive::CREATE | ZipArchive::OVERWRITE);
                $da = opendir($carpetadestino);
                $archibosaborrar = [];
                $y = 0;
                while (($archivo = readdir($da)) !== false) {
                    if (is_file($carpetadestino . $archivo) && $archivo != "." && $archivo != ".." && $archivo != $zipname) {
                        $zip->addFile($carpetadestino . $archivo, $archivo);
                        $archibosaborrar[$y] = $carpetadestino . $archivo;
                        $y++;
                    }
                }
                closedir($da);
                $zip->close();
                $rutaFinal = $carpetadestino;
                rename($zipname, "$rutaFinal/$zipname");

                $codigoarchivo = "Layouts_" . $idusuario . "_" . $rfc . "_" . $fecha;
                $consecutivo = "";
                $resultado = subirArchivoNextcloud($zipname, "$rutaFinal/$zipname", $rfc, $servidor, $u_storage, $p_storage, "Administracion", "FinanzasTesoreria", "LayoutsTemporales", $codigoarchivo, $consecutivo);
                if ($resultado["archivo"]["error"] == 0) {
                    $codigodocumento = $codigoarchivo . $consecutivo;
                    $directorio = $rfc . '/' . "Administracion" . '/' . "FinanzasTesoreria" . '/' . "LayoutsTemporales";
                    $target_path = $directorio . '/' . $codigodocumento . ".zip";
                    $link = GetLinkArchivo($target_path, $servidor, $u_storage, $p_storage);
                    $array["link"] = $link;
                    unlink($carpetadestino . $zipname);
                }

                for ($x = 0; $x < count($archibosaborrar); $x++) {
                    unlink($archibosaborrar[$x]);
                }
            }
            $urlcarpetaaborrar = substr($carpetadestino, 0, -1);
            rmdir($urlcarpetaaborrar);
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function eliminarFlwPagosHechos(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $idspago = $request->idspago;
            for ($x = 0; $x < count($idspago); $x++) {
                DB::table('mc_flw_pagos')->where("id", $idspago[$x])->delete();
                $pagodetencontrado = DB::select('SELECT * FROM mc_flw_pagos_det WHERE IdPago = ?', [$idspago[$x]]);
                for ($y = 0; $y < count($pagodetencontrado); $y++) {
                    DB::table('mc_flujosefectivo')->where("id", $pagodetencontrado[$y]->IdFlw)->delete();
                }
                DB::table('mc_flw_pagos_det')->where("IdPago", $idspago[$x])->delete();
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function traerLayoutsPorIdBanco(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            /* $layouts = DB::select('SELECT * FROM mc_flw_layouts_config'); */
            $layouts = DB::select('SELECT mc_flw_layouts_config.id, mc_flw_layouts_config.NombreLayout, mc_flw_layouts_config.LinkLayout,
            mc_flw_layouts_bancos.IdBanco, mc_flw_layouts_bancos.Destino FROM mc_flw_layouts_config
            LEFT JOIN mc_flw_layouts_bancos ON mc_flw_layouts_config.id = mc_flw_layouts_bancos.IdLayoutConfig');

            for($x=0 ; $x<count($layouts) ; $x++) {
                $layoutconfigcontent = DB::select('SELECT * FROM mc_flw_layouts_config_content WHERE IdLayoutConfig = ? ORDER BY posicion', [$layouts[$x]->id]);
                $layouts[$x]->ConfigContent = $layoutconfigcontent;
            }

            $array["layouts"] = $layouts;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function cambiarLayoutElegido(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $idsBancosOrigen = $request->idsBancosOrigen;
            $combinacionesBancos = $request->combinacionesBancos;
            $idsLayouts = $request->idsLayouts;

            for($x=0 ; $x<count($idsBancosOrigen) ; $x++) {
                $layoutencontrado = DB::select('SELECT * FROM mc_flw_layouts_bancos WHERE IdBanco = ? AND Destino = ?', [$idsBancosOrigen[$x], $combinacionesBancos[$x]]);
                if(count($layoutencontrado) > 0) {
                    DB::table('mc_flw_layouts_bancos')->where("id", $layoutencontrado[0]->id)->update(["IdLayoutConfig" => $idsLayouts[$x]]);
                }
                else {
                    DB::table('mc_flw_layouts_bancos')->insert([
                        "IdBanco" => $idsBancosOrigen[$x], "Destino" => $combinacionesBancos[$x], "IdLayoutConfig" => $idsLayouts[$x]
                    ]);
                }
            }
            /* $idUsuario = $request->idUsuario;
            $idBancoActual = $request->idBancoActual;
            $idBanco = $request->idBanco;
            $idLayout = $request->idLayout;
            $layoutsusuario = DB::select('SELECT * FROM mc_flw_layouts_usuarios WHERE IdUsuario = ? AND IdBanco = ? ', [$idUsuario, $idBancoActual]);
            if(count($layoutsusuario) > 0) {
                DB::table('mc_flw_layouts_usuarios')->where("id", $layoutsusuario[0]->id)->update(["IdLayoutConfig" => $idLayout, "IdBanco" => $idBanco]);
            }
            else {
                DB::table('mc_flw_layouts_usuarios')->insert([
                    "IdLayoutConfig" => $idLayout,"IdUsuario" => $idUsuario, "IdBanco" => $idBanco
                ]);
            }

            $layouts = DB::select('SELECT mc_flw_layouts_config.*, 
            IF((SELECT mc_flw_layouts_usuarios.id FROM mc_flw_layouts_usuarios 
            WHERE mc_flw_layouts_usuarios.IdLayoutConfig = mc_flw_layouts_config.id AND mc_flw_layouts_usuarios.IdUsuario = ?) > 0 , 1, 0) AS Eleccion 
            FROM mc_flw_layouts_config 
            WHERE mc_flw_layouts_config.IdBanco = ?', [$idUsuario, $idBancoActual]);
            $array["layouts"] = $layouts; */
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function reenviarCorreoLayout(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $idPago = $request->idPago;
            $titulo = $request->titulo;
            $codigoMensaje = $request->codigoMensaje;
            $cuentaOrigen = $request->cuentaOrigen;
            $cuentaDestino = $request->cuentaDestino;
            $proveedor = $request->proveedor;
            $importePagado = $request->importePagado;
            $correo = $request->correo;
            $forma = $request->forma;
            $tipoPago = $request->tipoPago;

            $data["titulo"] = $titulo;
            $data["codigoMensaje"] = $codigoMensaje;
            $data["cuentaOrigen"] = $cuentaOrigen;
            $data["cuentaDestino"] = $cuentaDestino;
            $data["proveedor"] = $proveedor;
            $data["importePagado"] = $importePagado;

            $detallespago = $tipoPago == 1 ? DB::select('SELECT mc_flw_pagos.Fecha, 
                CONCAT(IF(ISNULL(mc_flujosefectivo.Serie),"Sin Serie" ,mc_flujosefectivo.Serie),"-" ,mc_flujosefectivo.Folio) AS SerieFolio, 
                CONCAT("$",FORMAT((mc_flw_pagos_det.Importe + mc_flujosefectivo.Pendiente), 2)) AS Total, 
                CONCAT("$", FORMAT(mc_flw_pagos_det.Importe, 2)) AS Pagado, CONCAT("$", FORMAT(mc_flujosefectivo.Pendiente, 2)) AS Pendiente 
                FROM mc_flw_pagos_det INNER JOIN mc_flw_pagos ON mc_flw_pagos_det.IdPago = mc_flw_pagos.id
                INNER JOIN mc_flujosefectivo ON mc_flw_pagos_det.IdFlw = mc_flujosefectivo.id WHERE mc_flw_pagos_det.IdPago = ?', [$idPago]) : 
                DB::select("SELECT mc_flw_pagos.Fecha, CONCAT('$', FORMAT(mc_flw_pagos.Importe, 2)) AS Importe,
                (
                CASE
                    WHEN mc_flw_pagos.Tipo = 2 THEN 'Anticipo a proveedores'
                    WHEN mc_flw_pagos.Tipo = 3 THEN 'Pago a prestamos a acreedores'
                    WHEN mc_flw_pagos.Tipo = 4 THEN 'Entrega de prestamos a deudores'
                    ELSE 'Flujo de efectivo'
                END
                ) AS TipoPago,
                mc_flw_pagos.Proveedor , mc_flow_bancuentas.Nombre AS cuentaOrigen,
                IF(ISNULL(mc_flow_cliproctas.Clabe), CONCAT(REPLACE(mc_flow_cliproctas.Banco,', S.A.', ''),' ',
                SUBSTRING(mc_flow_cliproctas.Cuenta, -4)), CONCAT(REPLACE(mc_flow_cliproctas.Banco,', S.A.',''), ' ',
                SUBSTRING(mc_flow_cliproctas.Clabe, -4))) AS cuentaDestino FROM mc_flw_pagos
                LEFT JOIN mc_flow_bancuentas ON mc_flow_bancuentas.IdCuenta = mc_flw_pagos.IdCuentaOrigen
                LEFT JOIN mc_flow_cliproctas ON mc_flow_cliproctas.Id = mc_flw_pagos.IdCuentaDestino
                WHERE mc_flw_pagos.id = ?", [$idPago]);
            $data["detallesPago"] = $detallespago;

            if($forma === 2) {
                $correoencontrado = DB::select('SELECT * FROM mc_flw_correos WHERE IdPago = ? AND Correo = ?', [$idPago, $correo]);
                if(count($correoencontrado) == 0) {
                    if($codigoMensaje == 0) {
                        $codigoMensaje = rand(1000000000, 9999999999);
                        $ValidarCodigoMensaje = false;
                        while ($ValidarCodigoMensaje) {
                            $codigomensajeencontrado = DB::select('SELECT * FROM mc_flw_correos WHERE CodigoMensaje = ?', [$codigoMensaje]);
                            if (count($codigomensajeencontrado) == 0) {
                                $ValidarCodigoMensaje = true;
                            } else {
                                $codigoMensaje = rand(1000000000, 9999999999);
                            }
                        }
                        $data["codigoMensaje"] = $codigoMensaje;
                    }
                    
                    DB::table('mc_flw_correos')->insert(['IdPago' => $idPago, 'Correo' => $correo, 'Tipo' => 3, 'CodigoMensaje' => $codigoMensaje]);
                    if($tipoPago == 1) {
                        Mail::to($correo)->send(new MensajesLayouts($data));
                    }
                    else {
                        Mail::to($correo)->send(new MensajesLayoutsPagosAdicionales($data));
                    }
                }
                else {
                    $array["error"] = -2;
                }
            }
            else {
                if($tipoPago == 1) {
                    Mail::to($correo)->send(new MensajesLayouts($data));
                }
                else {
                    Mail::to($correo)->send(new MensajesLayoutsPagosAdicionales($data));
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function agregarCuentaProveedor(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $rfcProveedor = $request->rfcProveedor;
            $cuenta = $request->cuenta;
            $clabe = $request->clabe;
            $banco = $request->banco;
            $idBanco = $request->idBanco;
            $sucursal = $request->sucursal;
            $esCliente = $request->esCliente;

            $maxid = DB::select('SELECT Id FROM mc_flow_cliproctas ORDER BY Id DESC LIMIT 1');
            $maxid = $maxid[0]->Id + 1;

            $existenciaId = true;

            while(!$existenciaId) {
                $buscarid = DB::select('SELECT * FROM mc_flow_cliproctas WHERE Id = ?', [$maxid]);

                if(count($buscarid) == 0) {
                    $existenciaId = false;
                }
                else {
                    $maxid = $maxid + 1;
                }
            }

            DB::table('mc_flow_cliproctas')->insert(['Id' => $maxid, 'RFC' => $rfcProveedor, 'Cuenta' => $cuenta, 'Clabe' => $clabe, 'Banco' => $banco, 'IdBanco' => $idBanco, 'Sucursal' => $sucursal, 'Escliente' => $esCliente]);

            $cuentas = DB::select("SELECT mc_flow_cliproctas.*,
            IF(ISNULL(Clabe), CONCAT(REPLACE(Banco,', S.A.', ''),' ',SUBSTRING(Cuenta, -4)), CONCAT(REPLACE(Banco,', S.A.',''), ' ',SUBSTRING(Clabe, -4))) AS Layout
            FROM mc_flow_cliproctas WHERE Cuenta IS NOT NULL OR Clabe IS NOT NULL GROUP BY Id ORDER BY Layout");
            $array["cuentas"] = $cuentas;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function agregarNuevoProveedor(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $razonSocial = $request->razonSocial;
            $rfcProveedor = $request->rfcProveedor;
            $codigo = $request->codigo;
            $sucursal = $request->sucursal;
            $idMoneda = $request->idMoneda;
            $correo1 = $request->correo1;
            $correo2 = $request->correo2;
            $correo3 = $request->correo3;
            $cuenta = $request->cuenta;
            $clabe = $request->clabe;
            $banco = $request->banco;
            $idBanco = $request->idBanco;
            $sucursalBanco = $request->sucursalBanco;
            $esCliente = $request->esCliente;

            $maxid = DB::select('SELECT Id FROM mc_flow_cliproctas ORDER BY Id DESC LIMIT 1');
            $maxid = $maxid[0]->Id + 1;

            $existenciaId = true;

            while(!$existenciaId) {
                $buscarid = DB::select('SELECT * FROM mc_flow_cliproctas WHERE Id = ?', [$maxid]);

                if(count($buscarid) == 0) {
                    $existenciaId = false;
                }
                else {
                    $maxid = $maxid + 1;
                }
            }

            if($rfcProveedor != "XAXX010101000" && $rfcProveedor != "XEXX010101000") {
                $buscarproveedor = DB::select("SELECT * FROM mc_catproveedores WHERE rfc = ?", [$rfcProveedor]);
                if(count($buscarproveedor) === 0) {
                    DB::table('mc_catproveedores')->insert(['codigo' => $codigo, 'rfc' => $rfcProveedor, 'razonsocial' => $razonSocial, 'sucursal' => $sucursal, 'IdMoneda' => $idMoneda, 'Correo1' => $correo1, 'Correo2' => $correo2, 'Correo3' => $correo3]);
                }
                else {
                    $array["error"] = 41;
                    return json_encode($array, JSON_UNESCAPED_UNICODE);
                }
            }
            else {
                DB::table('mc_catproveedores')->insert(['codigo' => $codigo, 'rfc' => $rfcProveedor, 'razonsocial' => $razonSocial, 'sucursal' => $sucursal, 'IdMoneda' => $idMoneda, 'Correo1' => $correo1, 'Correo2' => $correo2, 'Correo3' => $correo3]);
            }

            DB::table('mc_flow_cliproctas')->insert(['Id' => $maxid, 'RFC' => $rfcProveedor, 'Cuenta' => $cuenta, 'Clabe' => $clabe, 'Banco' => $banco, 'IdBanco' => $idBanco, 'Sucursal' => $sucursalBanco, 'Escliente' => $esCliente]);

            $cuentas = DB::select("SELECT mc_flow_cliproctas.*,
            IF(ISNULL(Clabe), CONCAT(REPLACE(Banco,', S.A.', ''),' ',SUBSTRING(Cuenta, -4)), CONCAT(REPLACE(Banco,', S.A.',''), ' ',SUBSTRING(Clabe, -4))) AS Layout
            FROM mc_flow_cliproctas WHERE Cuenta IS NOT NULL OR Clabe IS NOT NULL GROUP BY Id ORDER BY Layout");
            $array["cuentas"] = $cuentas;

            $proveedores = DB::select('SELECT * FROM mc_catproveedores WHERE mc_catproveedores.rfc IS NOT NULL ORDER BY mc_catproveedores.razonsocial');
            $array["proveedores"] = $proveedores;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}