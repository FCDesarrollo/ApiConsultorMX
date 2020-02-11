<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UsuariosController extends Controller
{
    function Pruebas()
    {
        $data = array(

            array("nombre" => "Alejandro",
            "apaterno" => "Paez",
            "amaterbo" => "Tirado"),
            array("nombre" => "Gustavo",
            "apaterno" => "Gomez",
            "amaterbo" => "Floriano",
            "Prueba" => "Prueba"),
            array("nombre" => "Gustavo",
            "apaterno" => "Gomez",
            "amaterbo" => "Floriano",
            "Prueba" => "Prueba"),
            
        );        

        return json_encode($data);
    }

    function Login(Request $request)
    {
        $usuario = DB::connection("General")->select("SELECT * FROM mc1001 WHERE correo='$request->correo' AND status=1");

        if(!empty($usuario)){
        
            $hash_BD = $usuario[0]->password;

            if (password_verify($request->contra, $hash_BD)) {
                $datos = array(
                    "usuario" => $usuario,
                );
            } else {
                $datos = array(
                    "usuario" => "",
                );
            }
        }else{
            $datos = array(
                "usuario" => "",
            );
        }              

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function ListaUsuarios($idempresa)
    {
       // ConnectDatabase($idcliente);

        //$empleados = DB::select("SELECT *, coalesce((select nombre from sucursales where id = empleados.idsucursal),'') AS sucursal FROM empleados WHERE status=1 ORDER BY nombre");

        $empleados = DB::connection("General")->select("SELECT u.*,u.idusuario AS iduser, r.estatus As vinculado FROM mc1001 u 
        INNER JOIN mc1002 r ON u.idusuario=r.idusuario 
        INNER JOIN mc1000 e ON r.idempresa=e.idempresa WHERE r.idempresa='$idempresa'");

        $datos = array(
            "usuarios" => $empleados,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }


    public function ListaUsuariosAdmin($idempresa)
    {
      
        if ($idempresa == 0 ) {
            $usuarios = DB::connection("General")->select("SELECT DISTINCT u.*,e.*,u.status AS st FROM mc1001 u 
                                                            LEFT JOIN mc1002 r ON u.idusuario=r.idusuario
                                                            LEFT JOIN mc1000 e ON r.idempresa=e.idempresa WHERE cel IS NOT NULL" 
                                                      );
            //$usuarios = DB::connection("General")->select("SELECT DISTINCT u.*,r.*,coalesce((select nombreempresa from mc1000 where idempresa = r.idempresa),'') AS nombreempresa
             //                                           FROM mc1001 u 
              //                                          INNER JOIN mc1002 r ON u.idusuario=r.idusuario" 
                //                                        );
        }else{
            $usuarios = DB::connection("General")->select("SELECT DISTINCT u.*,r.*,coalesce((select nombreempresa from mc1000 where idempresa = r.idempresa),'') AS nombreempresa
                                                        FROM mc1001 u 
                                                        INNER JOIN mc1002 r ON u.idusuario=r.idusuario
                                                        WHERE r.idempresa='$idempresa'" 
                                                        );
        }
            

        $datos = array(
            "usuarios" => $usuarios,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function GuardaUsuario(Request $request)
    {
        if($request->idusuario==0){
            $data = $request->input();
            $password = $data["password"];             
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
            unset($data["idusuario"]);
            if(isset($data["user_perfil"])){
                $id = DB::connection("General")->table('mc1001')->insertGetId(['nombre' => ucwords(strtolower($data['nombre'])), 'apellidop' => ucwords(strtolower($data['apellidop'])), 'apellidom' => ucwords(strtolower($data['apellidom'])), 'cel' => $data['cel'], 'correo' => $data['correo'], 'password' => $data['password'], 'status' => $data['status'], 'identificador' => $data['identificador']]);

                $idempresa = $data['idempresa'];       

                DB::connection("General")->table('mc1002')->insert(['idusuario' => $id, 'idempresa' => $idempresa]);

                ConnectDatabase($idempresa);

                $idperfil = $data["user_perfil"];

                DB::table('mc_userprofile')->insertGetId(['idusuario' => $id, 'idperfil' => $idperfil]);

                $permod = DB::connection("General")->select("SELECT * FROM mc1007 WHERE idperfil = $idperfil");
                for ($i=0; $i < count($permod); $i++) { 
                    DB::table('mc_usermod')->insertGetId(['idusuario' => $id, 'idperfil' => $idperfil, 'idmodulo' => $permod[$i]->idmodulo, 'tipopermiso' => $permod[$i]->tipopermiso]);
                }
                $permen = DB::connection("General")->select("SELECT * FROM mc1008 WHERE idperfil = $idperfil");
                for ($j=0; $j < count($permen); $j++) { 
                    DB::table('mc_usermenu')->insertGetId(['idusuario' => $id, 'idperfil' => $idperfil, 'idmodulo' => $permen[$j]->idmodulo, 'idmenu' => $permen[$j]->idmenu, 'tipopermiso' => $permen[$j]->tipopermiso]);
                }                
                $persub = DB::connection("General")->select("SELECT * FROM mc1009 WHERE idperfil = $idperfil");
                for ($k=0; $k < count($persub); $k++) { 
                    DB::table('mc_usersubmenu')->insertGetId(['idusuario' => $id, 'idperfil' => $idperfil, 'idmenu' => $persub[$k]->idmenu, 'idsubmenu' => $persub[$k]->idsubmenu, 'tipopermiso' => $persub[$k]->tipopermiso]);
                }

            }else{
                $id = DB::connection("General")->table('mc1001')->insertGetId($data);    
            }
        }else{
            $data = $request->input();
            $id = $data["idusuario"];
            unset($data["idusuario"]);
            if(isset($data["editarusuario"])){
                DB::connection("General")->table('mc1001')->where("idusuario", $id)->update(["nombre" => $data['nombre'], "apellidop" => $data['apellidop'], "apellidom" => $data['apellidom']]);                
            }else{
                $password = $data["password"];
                $data['password'] = password_hash($password, PASSWORD_BCRYPT);                    
                DB::connection("General")->table('mc1001')->where("idusuario", $id)->update($data);
            }

        }
        return $id;
    }

    function DatosUsuario($idusuario)
    {
       $usuario = DB::connection("General")->select("SELECT * FROM mc1001 WHERE idusuario='$idusuario'");    
        $datos = array(
            "usuario" => $usuario,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function EliminarUsuario(Request $request)
    {                
        $idusuario = $request->idusuario;
        $idempresa = $request->idempresa;
        DB::connection("General")->table('mc1002')->where("idusuario", $idusuario)->where("idempresa", $idempresa)->delete();
        
        ConnectDatabase($idempresa);
        DB::table('mc_usermod')->where("idusuario", $idusuario)->delete();
        DB::table('mc_usermenu')->where("idusuario", $idusuario)->delete();
        DB::table('mc_usersubmenu')->where("idusuario", $idusuario)->delete();
        DB::table('mc_userprofile')->where("idusuario", $idusuario)->delete();

        return $idusuario;
    }

    public function Desvincular(Request $request)
    {                
        $datos = $request->datos;

        $idusuario = $datos["idusuario"];
        $iduser_vincula = $datos["iduser_vincula"];
        $idempresa = $datos["idempresa"];
        $status = $datos["status"];
        
        //DB::connection("General")->table('mc1002')->where("idusuario", $id)->where("idempresa", $idem)->delete();
        $id = DB::connection("General")->table('mc1002')->where("idusuario", $idusuario)->where("idempresa", $idempresa)->update(["estatus" => $status, "fecha_vinculacion" => date("Ymd"), "idusuario_vinculador" => $iduser_vincula]);        
        return $id; 
    }

    public function VerificaUsuario(Request $request)
    {      
        $id = $request->idusuario;
        $flag = $request->flag;
        if($flag == 1){
            $usuario = DB::connection("General")->select("SELECT * FROM mc1001 WHERE idusuario = $id");
            if (!password_verify($request->pwd, $usuario[0]->password)) {
                $pwd = password_hash($request->pwd, PASSWORD_BCRYPT);   
                DB::connection("General")->table('mc1001')->where("idusuario", $request->idusuario)->where("identificador", $request->identificador)->update(["password" => $pwd, "tipo" => "1"]);

            }
            return $usuario;
        }else{
            DB::connection("General")->table('mc1001')->where("idusuario", $request->idusuario)->where("identificador", $request->identificador)->update(["tipo"=>"1"]);
            return $id;
        }   
    }

    public function ObtenerUsuarioNuevo(Request $request)
    {        
        $identificador = $request->identificador;

        $usuario = DB::connection("General")->select("SELECT * FROM mc1001 WHERE identificador='$identificador'");    
        $datos = array(
            "usuario" => $usuario,
        );
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }  

    public function DatosPerfil(Request $request){

        $iduser = $request->idusuario;
        ConnectDatabase($request->idempresa);

        $idperfil = DB::select("SELECT idperfil FROM mc_userprofile WHERE idusuario = $iduser");         
        $idperfil = $idperfil[0]->idperfil;
        
        $perfil = DB::connection("General")->select("SELECT * FROM mc1006 WHERE idperfil = $idperfil");            

        return json_encode($perfil, JSON_UNESCAPED_UNICODE);
    }

 
    public function ValidarCorreo(Request $request)
    {        
        $correo = $request->correo;

        $usuario = DB::connection("General")->select("SELECT * FROM mc1001 WHERE correo='$correo'");    
        $datos = array(
            "usuario" => $usuario,
        );
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }
    
    public function RestablecerContraseña(Request $request)
    {      
        $id = $request->idusuario;    
        $password = password_hash($request->password, PASSWORD_BCRYPT);
        DB::connection("General")->table('mc1001')->where("idusuario", $request->idusuario)->update(["password" => $password]);
        return $id;        
    }    

    public function VerificaCelular(Request $request)
    {      
        $id = $request->idusuario;
        DB::connection("General")->table('mc1001')->where("idusuario", $request->idusuario)->where("identificador", $request->identificador)->update(["verificacel"=>"1"]);
        return $id;        
    }    

    public function NotificacionesUsuario(Request $request)
    {      
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        ConnectDatabase($idempresa);

        $permisos= DB::select("SELECT mc_usersubmenu.idsubmenu, mc_usersubmenu.notificaciones, mc_usermod.idmodulo, mc_usermod.tipopermiso, mc_usermenu.idmenu, mc_usermenu.tipopermiso FROM mc_usersubmenu LEFT JOIN mc_usermenu ON mc_usermenu.idmenu = mc_usersubmenu.idmenu LEFT JOIN  mc_usermod ON mc_usermod.idmodulo = mc_usermenu.idmodulo WHERE mc_usersubmenu.idusuario = '$idusuario' AND mc_usermenu.idusuario = '$idusuario' AND mc_usermod.idusuario = '$idusuario' AND mc_usermod.tipopermiso <> 0");
        
        $datos = $permisos;       
        return $datos;     
    }       

    public function ModificaNotificacion(Request $request)
    {
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idsubmenu = $request->idsubmenu;
        
        ConnectDatabase($idempresa);        

        DB::table('mc_usersubmenu')->where("idusuario", $idusuario)->where("idsubmenu", $idsubmenu)->update(["notificaciones"=>$request->tiponotificacion]);
        return $idsubmenu;
    }

    function VinculacionUsuarios(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idperfil = $request->user_perfil;

        DB::connection("General")->table('mc1002')->insert(['idusuario' => $idusuario, 'idempresa' => $idempresa]);

        ConnectDatabase($idempresa);        

        DB::table('mc_userprofile')->insertGetId(['idusuario' => $idusuario, 'idperfil' => $idperfil]);

        $permod = DB::connection("General")->select("SELECT * FROM mc1007 WHERE idperfil = $idperfil");
        for ($i=0; $i < count($permod); $i++) { 
            DB::table('mc_usermod')->insertGetId(['idusuario' => $idusuario, 'idperfil' => $idperfil, 'idmodulo' => $permod[$i]->idmodulo, 'tipopermiso' => $permod[$i]->tipopermiso]);
        }
        $permen = DB::connection("General")->select("SELECT * FROM mc1008 WHERE idperfil = $idperfil");
        for ($j=0; $j < count($permen); $j++) { 
            DB::table('mc_usermenu')->insertGetId(['idusuario' => $idusuario, 'idperfil' => $idperfil, 'idmodulo' => $permen[$j]->idmodulo, 'idmenu' => $permen[$j]->idmenu, 'tipopermiso' => $permen[$j]->tipopermiso]);
        }                
        $persub = DB::connection("General")->select("SELECT * FROM mc1009 WHERE idperfil = $idperfil");
        for ($k=0; $k < count($persub); $k++) { 
            DB::table('mc_usersubmenu')->insertGetId(['idusuario' => $idusuario, 'idperfil' => $idperfil, 'idmenu' => $persub[$k]->idmenu, 'idsubmenu' => $persub[$k]->idsubmenu, 'tipopermiso' => $persub[$k]->tipopermiso]);
        }

        return $idusuario;        
    }



}
