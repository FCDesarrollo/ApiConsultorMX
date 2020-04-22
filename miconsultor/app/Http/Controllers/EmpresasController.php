<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



class EmpresasController extends Controller
{
    function ListaSucursales($idempresa)
    {
        ConnectDatabase($idempresa);

        //$empleados = DB::select("SELECT *, coalesce((select nombre from sucursales where id = empleados.idsucursal),'') AS sucursal FROM empleados WHERE status=1 ORDER BY nombre");

        $sucursales = DB::select("SELECT * FROM sucursales WHERE status=1");

        $datos = array(
            "sucursales" => $sucursales,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }




    function ListaEmpresas(Request $request)
    {
       
        $idusuario = $request->idusuario;
        if ($request->tipo==4){
            $empresas = DB::connection("General")->select("SELECT * FROM mc1000");
        }else{
            $empresas = DB::connection("General")->select("SELECT e.*,u.* FROM mc1000 e  INNER JOIN mc1002 r ON e.idempresa=r.idempresa INNER JOIN mc1001 u ON r.idusuario=u.idusuario WHERE u.idusuario='$idusuario' AND r.estatus = 1");
        }

        for ($i=0; $i < count($empresas); $i++) { 
            $idempresa = $empresas[$i]->idempresa;
            ConnectDatabase($idempresa);

            $idper = DB::select("SELECT * FROM mc_userprofile WHERE idusuario = $idusuario");

            $idperfil = $idper[0]->idperfil;

            $perfil = DB::connection("General")->select("SELECT * FROM mc1006 WHERE idperfil = $idperfil");

            $empresas[$i]->idperfil = $idperfil;
            $empresas[$i]->perfil = $perfil[0]->nombre;

        }
        
       

        $datos = array(
            "empresas" => $empresas,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    function DatosEmpresaAD($idempresa)
    {
       $empresa = DB::connection("General")->select("SELECT * FROM mc1000 WHERE idempresa='$idempresa'");    


        $datos = array(
            "empresa" => $empresa,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function EliminarEmpresaAD(Request $request)
    {                
        $id = $request->idempresa;
        DB::connection("General")->table('mc1000')->where("idempresa", $id)->update(["status"=>"0"]);
        return $id;
    }

    public function GuardarEmpresaAD(Request $request)
    {
        if($request->idempresa==0){
            $data = $request->input();
            unset($data["idempresa"]);
            $id = DB::connection("General")->table('mc1000')->insertGetId($request->input());
        }else{
            $data = $request->input();
            $id = $data["idempresa"];
            unset($data["idempresa"]);
            DB::connection("General")->table('mc1000')->where("idempresa", $id)->update($data);
        }
        return $id;
    }

  

     public function GuardarEmpresa(Request $request)
        {
            $datos = $request->datos;   
            if($datos["idempresa"]==0){
                //$data = $request->input();
                $password = $datos["password"];
                unset($datos["idempresa"]); 
                $datos['password'] = password_hash($password, PASSWORD_BCRYPT); //md5($password);              
                //$id = DB::connection("General")->table('mc1000')->insertGetId($data);
                $id = DB::connection("General")->table('mc1000')->insertGetId($datos);
            }            
            return $id;
        }
    
    public function BDDisponible()
    {
        $consulta = DB::connection("General")->select("SELECT * FROM mc1010 WHERE id = (SELECT IF(ISNULL(MAX(id)),1,MAX(id) +1) AS idDisponible FROM mc1010 WHERE estatus <> 0);");    
        
        $datos = array(
            "basedatos" => $consulta,
        );       
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function AsignaBD(Request $request)
    {      
        $id = $request->id;
        $rfc = $request->rfc;
        $consulta = DB::connection("General")->select("SELECT * FROM mc1010 WHERE rfc = '$rfc'");
        if(!empty($consulta)){            
            $respuesta = array(
                "registro" => $consulta,
            );            
        }else{
            // query builder 
            DB::connection("General")->table('mc1010')->where("id", $request->id)->update(["rfc"=>$rfc,"estatus"=>"1"]);
            
            $respuesta = array(
                "registro" => $id,
            );            
        }
        return json_encode($respuesta, JSON_UNESCAPED_UNICODE);    
    }

    public function EliminaAsignaBD(Request $request)
    {      
        $nombre = $request->empresaBD;
        if ($nombre != "") {
            DB::connection("General")->table('mc1010')->where('nombre', $nombre)->update(['rfc' => '','estatus' => 0]);
            $id = 1;
        }else {
            $id=0;
        }    
        return $id;    
        
    }

    public function CrearTablasEmpresa(Request $request)
    {  
        $empresaBD = $request->empresaBD;        
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
              idadw INT(11) DEFAULT NULL,
              default TINYINT(1) DEFAULT 0,              
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
                nombre_concepto varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                descripcion varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                fecha date DEFAULT NULL,
                status int(11) DEFAULT NULL,
                PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
                DB::statement($mc_conceptos);

            $mc_requerimientos = "create table if not exists mc_requerimientos (
                idReq int(11) NOT NULL AUTO_INCREMENT,
                id_sucursal int(11) DEFAULT NULL,
                fecha date DEFAULT NULL,
                id_usuario int(11) DEFAULT NULL,
                id_departamento int(11) DEFAULT NULL,
                descripcion varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                importe_estimado double DEFAULT NULL,
                estado_documento int(11) DEFAULT NULL,
                id_concepto int(11) DEFAULT NULL,
                serie varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                folio varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                PRIMARY KEY (idReq)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
                DB::statement($mc_requerimientos);

              $mc_requerimientos_bit = "create table if not exists mc_requerimientos_bit (
                id_bit int(11) NOT NULL,
                id_req int(11) DEFAULT NULL,
                fecha date DEFAULT NULL,
                status int(11) DEFAULT NULL,
                PRIMARY KEY (id_bit)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
              DB::statement($mc_requerimientos_bit);

              $mc_almdigital_exp = "create table if not exists mc_almdigital_exp (
                idalmdigitaldet INT(11) NOT NULL,
                idmodulo INT(11) NOT NULL,
                cuenta VARCHAR(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                tipodoc VARCHAR(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                ejercicio INT(11) DEFAULT NULL,
                periodo INT(11) DEFAULT NULL
                ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
              DB::statement($mc_almdigital_exp);
              

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
            //$mc1014 = "insert ".$empresaBD.".mc_conceptos SELECT * FROM dublockc_MCGenerales.mc1014;";
            //DB::statement($mc1014);
            
            $id = 1;
        }else {
            $id = 0;            
        }
        return $id;
    }




    public function UsuarioEmpresa(Request $request)
        {  
            $usuario = $request->idusuario;
            $empresa = $request->idempresa;  
            if($usuario==0 && $empresa==0){   
                $id = 0; 
                
            }else{
                DB::connection("General")->table("mc1002")->insert(["idusuario" => $usuario, "idempresa" => $empresa]);            
                $id = 1;
            }
            return $id;
        }
    
    public function UsuarioProfile(Request $request)
    {
        $usuario = $request->idusuario;
        $empresaBD = $request->empresaBD;        
        ConnectaEmpresaDatabase($empresaBD);

        if($usuario != 0 && $empresaBD != ""){
            $mc_profiles = "insert into ".$empresaBD.".mc_userprofile (idusuario,idperfil) values(".$usuario.", 1);";
            DB::statement($mc_profiles);

            $mc1007 = DB::connection("General")->select("SELECT * FROM mc1007 WHERE idperfil = 1");
            for ($i=0; $i < count($mc1007); $i++) { 
                DB::table('mc_usermod')->insertGetId(["idusuario" => $usuario, "idperfil" => 1, 
                    "idmodulo" => $mc1007[$i]->idmodulo, "tipopermiso" => $mc1007[$i]->tipopermiso]);
            }

            $mc1008 = DB::connection("General")->select("SELECT * FROM mc1008 WHERE idperfil = 1");
            for ($i=0; $i < count($mc1008); $i++) { 
                DB::table('mc_usermenu')->insertGetId(["idusuario" => $usuario, "idperfil" => 1, 
                    "idmodulo" => $mc1008[$i]->idmodulo, "idmenu" => $mc1008[$i]->idmenu, "tipopermiso" => $mc1008[$i]->tipopermiso]);
            }

            $mc1009 = DB::connection("General")->select("SELECT * FROM mc1009 WHERE idperfil = 1");
            for ($i=0; $i < count($mc1009); $i++) { 
                DB::table('mc_usersubmenu')->insertGetId(["idusuario" => $usuario, "idperfil" => 1, "idmenu" => $mc1009[$i]->idmenu, 
                    "idsubmenu" => $mc1009[$i]->idsubmenu, "tipopermiso" => $mc1009[$i]->tipopermiso]);
            }            

            $id=1;            
        }else{
            $id=0;
        }      
        return $id;
    }
    public function Parametros()
    {      
        $consulta = DB::connection("General")->select("SELECT * FROM mc0000");  

        $datos = array(
            "parametros" => $consulta,
        );       

        return json_encode($datos, JSON_UNESCAPED_UNICODE);     
    }
    
    public function EliminarRegistro(Request $request)
    {   
        $rfc = $request->rfc;        
        if ($rfc != "") {
            DB::connection("General")->table('mc1000')->where('rfc', $rfc)->delete();
            $id = 1;
        }
        else{
            $id = 0;
        }
        return $id;     
    } 
    
    public function EliminarTablas(Request $request)
    {   
        $empresaBD = $request->empresaBD;        
        ConnectaEmpresaDatabase($empresaBD);                
        if ($empresaBD != "") {                 

            $QueryPerfiles = "DROP TABLE mc_almdigital;";
            DB::statement($QueryPerfiles);
            $QueryPerfiles = "DROP TABLE mc_almdigital_det;";
            DB::statement($QueryPerfiles);
            $QueryPerfiles = "DROP TABLE mc_bitcontabilidad;";
            DB::statement($QueryPerfiles);
            $QueryPerfiles = "DROP TABLE mc_bitcontabilidad_det;";
            DB::statement($QueryPerfiles);
            $QueryPerfiles = "DROP TABLE mc_catclienprov;";
            DB::statement($QueryPerfiles); 
            $QueryPerfiles = "DROP TABLE mc_catconceptos;";
            DB::statement($QueryPerfiles);
            $QueryPerfiles = "DROP TABLE mc_catproductos;";
            DB::statement($QueryPerfiles);
            $QueryPerfiles = "DROP TABLE mc_catsucursales;";
            DB::statement($QueryPerfiles);
            $QueryPerfiles = "DROP TABLE mc_lotes;";
            DB::statement($QueryPerfiles);
            $QueryPerfiles = "DROP TABLE mc_lotesdocto;";
            DB::statement($QueryPerfiles); 
            $QueryPerfiles = "DROP TABLE mc_lotesmovtos;";
            DB::statement($QueryPerfiles); 
            $QueryPerfiles = "DROP TABLE mc_menupermis;";
            DB::statement($QueryPerfiles);  
            $QueryPerfiles = "DROP TABLE mc_modpermis;";
            DB::statement($QueryPerfiles);
            $QueryPerfiles = "DROP TABLE mc_profiles;";
            DB::statement($QueryPerfiles);
            $QueryPerfiles = "DROP TABLE mc_submenupermis;";
            DB::statement($QueryPerfiles);
            $QueryPerfiles = "DROP TABLE mc_usermenu;";
            DB::statement($QueryPerfiles);
            $QueryPerfiles = "DROP TABLE mc_usermod;";
            DB::statement($QueryPerfiles);
            $QueryPerfiles = "DROP TABLE mc_userprofile;";
            DB::statement($QueryPerfiles);
            $QueryPerfiles = "DROP TABLE mc_usersubmenu;";
            DB::statement($QueryPerfiles);   
            $QueryPerfiles = "DROP TABLE mc_rubros;";
            DB::statement($QueryPerfiles);


            $id= 1;
        }else {
            $id= 0;
        }  
        return $id;   
    }
    
    public function EliminarUsuarioEmpresa(Request $request){  
        $idusuario = $request->usuarioId;        
        if ($idusuario != "") {
            DB::connection("General")->table('mc1002')->where('idusuario', $idusuario)->delete();
            $id = 1;
        }
        else{
            $id = 0;
        }
        return $id;   
    }

    function DatosEmpresa(Request $request){
      $rfc = $request->rfcempresa;
      $empresa = DB::connection("General")->select("SELECT * FROM mc1000 WHERE RFC='$rfc'");    

      $datos = array(
          "empresa" => $empresa,
      );        

      return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }   

    function DatosFacturacion(Request $request){
      $datos = $request->objeto;
      $idempresa = $datos["idempresa"];
      DB::connection("General")->table('mc1000')->where("idempresa", $idempresa)->update(["calle" => $datos['calle'], "colonia" => $datos['colonia'], "num_ext" => $datos['num_ext'], "num_int" => $datos['num_int'], "codigopostal" => $datos['codigopostal'], "municipio" => $datos['municipio'], "ciudad" => $datos['ciudad'], "estado" => $datos['estado'], "telefono" => $datos['telefono']]);
      return $idempresa;

    } 

    function ActualizaVigencia(Request $request){
      
      $vigencia = $request->vigencia;
      $idempresa = $request->idempresa;
      $empresa = DB::connection("General")->select("SELECT * FROM mc1000 WHERE idempresa = $idempresa"); 

      $fecha_actual = strtotime($empresa[0]->vigencia);
      $fecha_entrada = strtotime($vigencia);

      if($fecha_actual > $fecha_entrada){
        $status = 0;
      }else{
        DB::connection("General")->table('mc1000')->where("idempresa", $idempresa)->update(["vigencia" => $vigencia]);
        $status = 1;
      }    

      return $status;
        
    }


     
    
}
