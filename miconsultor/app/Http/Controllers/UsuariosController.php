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
        
      /* $usuario = DB::connection("General")->select("SELECT e.*,u.* 
                                    FROM mc1000 e 
                                        INNER JOIN mc1002 r ON e.idempresa=r.idempresa 
                                            INNER JOIN mc1001 u ON r.idusuario=u.idusuario 
                                            WHERE u.cel='$request->cel' AND u.password='$request->contra' 
                                            AND u.status=1");*/
        //$password = md5($request->contra);
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

        $empleados = DB::connection("General")->select("SELECT u.*,u.idusuario AS iduser FROM mc1001 u 
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
            $password = $data["password"];
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);    
            DB::connection("General")->table('mc1001')->where("idusuario", $id)->update($data);
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
        $id = $request->idusuario;
        DB::connection("General")->table('mc1001')->where("idusuario", $id)->update(["status"=>"0"]);
        return $id;
    }

    public function Desvincular(Request $request)
    {                
        $id = $request->idusuario;
        $idem= $request->idemp;
        DB::connection("General")->table('mc1002')->where("idusuario", $id)->where("idempresa", $idem)->delete();
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
    
     public function Modulos(Request $request)
    {

        $modulos = DB::connection("General")->select("SELECT * FROM modulos");    
        $datos = array(
            "modulos" => $modulos,
        );
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function DatosModulo($IDMod)
    {
        $modulo = DB::connection("General")->select("SELECT * FROM modulos WHERE idmodulo='$IDMod'");    
        $datos = array(
            "modulo" => $modulo,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }


    public function DatosPerfil(Request $request){
        $rfc = $request->rfcempresa;
        $usuario = $request->usuario;

        $empresa = DB::connection("General")->select("SELECT idempresa FROM mc1000 WHERE RFC = '$rfc'");
        
        $usuario = DB::connection("General")->select("SELECT idusuario FROM mc1001 WHERE correo = '$usuario'");

        ConnectDatabase($empresa[0]->idempresa);

        $idperfil = DB::select("SELECT idperfil FROM mc_userprofile WHERE idusuario = '$usuario[0]->idusuario'");         

        $perfil = DB::connection("General")->select("SELECT nombre FROM mc1006 WHERE idperfil = '$idperfil[0]->idperfil'");    

        $datos = array(
            "perfil" => $perfil,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function ListaPermisos($IDPer)
    {
        $permisos = DB::connection("General")->select("SELECT p.*,m.* FROM permisos p INNER JOIN 
                                                    modulos m ON p.idmodulo=m.idmodulo WHERE idperfil='$IDPer'");    
        $datos = array(
            "permisos" => $permisos,
        );        

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function EliminaPermiso(Request $request){
        $IDPermiso = $request->idpermiso;
        DB::connection("General")->table('permisos')->where("id", $IDPermiso)->delete();
        return $IDPermiso;
    }

    public function GuardaPermiso(Request $request)
    {

        $IDPerfil = $request->idperfil;
        $IDModulo = $request->idmodulo;
        if (DB::connection("General")->select("SELECT * FROM permisos
                                         WHERE idperfil='$IDPerfil' AND idmodulo='$IDModulo'")){
        //$permiso["id"]>0){
            DB::connection("General")->table('permisos')->where("idperfil", $IDPerfil)->
                    where("idmodulo", $IDModulo)->update(["tipopermiso"=>$request->tipopermiso]);
        }else{
            $inserted = DB::connection("General")->table('permisos')->insert(
                ['idperfil' => $IDPerfil,'idmodulo' => $IDModulo,'tipopermiso' => $request->tipopermiso ]);
            //$id = DB::connection("General")->table('permisos')->insertGetId($request->input());
        } 
        return $IDPerfil;
    }

    public function GuardaPerfil(Request $request)
    {
        if($request->id==0){
            $ulidperfil = DB::connection("General")->select("SELECT max(idperfil) + 1  FROM perfiles");
            if ($ulidperfil <= 3){
                $ulidperfil=4;   
            }
            $data = $request->input();
            unset($data["id"]);
            $data["idperfil"]=$ulidperfil;
            $idp = DB::connection("General")->table('perfiles')->insertGetId($data);
        }else{
            $data = $request->input();
            $idp = $data["id"];
            unset($data["id"]);
            DB::connection("General")->table('perfiles')->where("id", $idp)->update($data);
        }
        return $idp;
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

        $permisos= DB::select("SELECT mc_usersubmenu.idsubmenu, mc_usersubmenu.notificaciones, mc_usermod.idmodulo, mc_usermod.tipopermiso, mc_usermenu.idmenu, mc_usermenu.tipopermiso FROM mc_usersubmenu LEFT JOIN mc_usermenu ON mc_usermenu.idmenu = mc_usersubmenu.idmenu LEFT JOIN  mc_usermod ON mc_usermod.idmodulo = mc_usermenu.idmodulo WHERE mc_usersubmenu.idusuario = '$idusuario' AND mc_usermod.tipopermiso <> 0");
        
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



}
