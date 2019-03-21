<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

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
            $empresas = DB::connection("General")->select("SELECT * FROM mc1000 ");
        }else{
            $empresas = DB::connection("General")->select("SELECT e.*,u.* FROM mc1000 e 
                                            INNER JOIN mc1002 r ON e.idempresa=r.idempresa 
                                    INNER JOIN mc1001 u ON r.idusuario=u.idusuario WHERE u.idusuario='$idusuario'");
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
            
            if($request->idempresa==0){
                $data = $request->input();
                $password = $data["password"];
                unset($data["idempresa"]); 
                $data['password'] = md5($password);              
                $id = DB::connection("General")->table('mc1000')->insertGetId($data);
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
        DB::connection("General")->table('mc1010')->where("id", $request->id)->update(["rfc"=>$rfc,"estatus"=>"1"]);
        return $id;        
    }

    public function CrearTablasEmpresa(Request $request)
    {  
        $empresaBD = $request->empresaBD;
        ConnectaEmpresaDatabase($empresaBD);

        //$QueryRubros = 'create table if not exists Rubros(id int(255),nombre varchar(250) COLLATE utf8_spanish_ci DEFAULT NULL,status int(11) DEFAULT 1, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;';
        //DB::statement($QueryRubros);   
        $QueryPerfiles = "create table if not exists mc_profiles (
            id INT(11) NOT NULL,
            idperfil INT(11) NOT NULL,
            nombre VARCHAR(120) COLLATE latin1_spanish_ci DEFAULT NULL,
            descripcion VARCHAR(254) COLLATE latin1_spanish_ci DEFAULT NULL,
            fecha DATE DEFAULT NULL,
            STATUS INT(11) DEFAULT '1'
        ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
        DB::statement($QueryPerfiles);   

        $modpermis = "create table if not exists mc_modpermis (
            id INT(11) NOT NULL,
            idperfil INT(11) DEFAULT NULL,
            idmodulo INT(11) DEFAULT NULL,
            tipopermiso INT(11) DEFAULT NULL
          ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
        DB::statement($modpermis); 

        $menupermis = "create table if not exists mc_menupermis (
            id INT(11) NOT NULL,
            idperfil INT(11) DEFAULT NULL,
            idmodulo INT(11) DEFAULT NULL,
            idmenu INT(11) DEFAULT NULL,
            tipopermiso INT(11) DEFAULT NULL
          ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
        DB::statement($menupermis); 
        
        $submenupermis = "create table if not exists mc_submenupermis (
            id INT(11) NOT NULL,
            idperfil INT(11) DEFAULT NULL,
            idmenu INT(11) DEFAULT NULL,
            idsubmenu INT(11) DEFAULT NULL,
            tipopermiso INT(11) DEFAULT NULL
          ) ENGINE=INNODB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
        DB::statement($submenupermis); 
        
        $mc_profiles = "insert ".$empresaBD.".mc_profiles SELECT * FROM dublockc_MCGenerales.mc1006;";
        DB::statement($mc_profiles);

        $mc_profiles = "insert ".$empresaBD.".mc_modpermis SELECT * FROM dublockc_MCGenerales.mc1007;";
        DB::statement($mc_profiles);
        
        $mc_profiles = "insert ".$empresaBD.".mc_menupermis SELECT * FROM dublockc_MCGenerales.mc1008;";
        DB::statement($mc_profiles);
        
        $mc_profiles = "insert ".$empresaBD.".mc_submenupermis SELECT * FROM dublockc_MCGenerales.mc1009;";
        DB::statement($mc_profiles);

        /*$QueryPerfiles = "create table if not exists mc_profiles(id int(11) NOT NULL, perfiles varchar(250) DEFAULT NULL, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        DB::statement($QueryPerfiles);   
        
        $QueryPermisos = "create table if not exists Permisos (id int(11) NOT NULL,idUsuario int(11) DEFAULT NULL, idPerfil int(11) DEFAULT NULL,idModulo int(11) DEFAULT NULL,Permiso varchar(11) COLLATE utf8_spanish_ci DEFAULT NULL,
            PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;";
        DB::statement($QueryPermisos); 

        $QueryRubros = 'create table if not exists Rubros(id int(255),nombre varchar(250) COLLATE utf8_spanish_ci DEFAULT NULL,status int(11) DEFAULT 1, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;';
        DB::statement($QueryRubros); */

       return 1;
    }
    public function UsuarioEmpresa(Request $request)
        {  
            $usuario = $request->idusuario;
            $empresa = $request->idempresa;  
            if($usuario==0 && $empresa==0){   
                $respuesta = 0; 
                
            }else{
                DB::connection("General")->table("mc1002")->insert(["idusuario" => $usuario, "idempresa" => $empresa]);            
                $respuesta = 1;
            }
            return $respuesta;
        }
    
}
