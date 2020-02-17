<?php


namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Mail;
use App\Mail\MensajesValidacion;

class UsuarioController extends Controller
{
    function inicioUsuario(Request $request)
    {
        $valida = verificaLogin($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $array["usuario"] = $valida[0]['usuario'];
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function permisosUsuario(Request $request)
    {
        $rfc = $request->rfc;
        $valida = verificaUsuario($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            
            ConnectDatabaseRFC($rfc);

            $usuario = $valida[0]['usuario'];
            $idusuario = $usuario[0]->idusuario;
            $modpermiso = DB::select('select idmodulo,tipopermiso,nombre AS nombreperfil from mc_usermod muser 
			                        INNER JOIN mc_profiles mp ON muser.idperfil = mp.idperfil where idusuario = ?', [$idusuario]);
            for ($i=0; $i < count($modpermiso); $i++) {
                $idmodulo = $modpermiso[$i]->idmodulo; 
                $menupermiso = DB::select('select idmenu,tipopermiso from mc_usermenu 
                                where idusuario = ? AND idmodulo= ?', [$idusuario, $idmodulo]);
                for ($x=0; $x < count($menupermiso); $x++) { 
                    $idmenu = $menupermiso[$x]->idmenu;
                    $submenupermiso = DB::select('select idsubmenu,tipopermiso,notificaciones from mc_usersubmenu 
                                        where idusuario = ? AND idmenu = ?', [$idusuario, $idmenu]);
                    $menupermiso[$x]->permisossubmenus = $submenupermiso;
                }
                $modpermiso[$i]->permisosmenu = $menupermiso;
                $array["permisomodulos"][$i] = $modpermiso[$i];
            }
            
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function registrarUsuario(Request $request)
    {
        if($request->idusuario==0){
            $valida = validaNuevoUsuario($request->correo, $request->cel);

            $array["error"] = $valida[0]["error"];
   
            if ($valida[0]['error'] == 0){
                $data = $request->input();
                $correo = $data['correo'];
                Mail::to($correo)->send(new MensajesValidacion($data));
               
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

                    $permod = DB::select("SELECT * FROM mc_modpermis WHERE idperfil = $idperfil");
                    for ($i=0; $i < count($permod); $i++) { 
                        DB::table('mc_usermod')->insertGetId(['idusuario' => $id, 'idperfil' => $idperfil, 'idmodulo' => $permod[$i]->idmodulo, 'tipopermiso' => $permod[$i]->tipopermiso]);
                    }
                    $permen = DB::select("SELECT * FROM mc_menupermis WHERE idperfil = $idperfil");
                    for ($j=0; $j < count($permen); $j++) { 
                        DB::table('mc_usermenu')->insertGetId(['idusuario' => $id, 'idperfil' => $idperfil, 'idmodulo' => $permen[$j]->idmodulo, 'idmenu' => $permen[$j]->idmenu, 'tipopermiso' => $permen[$j]->tipopermiso]);
                    }                
                    $persub = DB::select("SELECT * FROM mc_submenupermis WHERE idperfil = $idperfil");
                    for ($k=0; $k < count($persub); $k++) { 
                        DB::table('mc_usersubmenu')->insertGetId(['idusuario' => $id, 'idperfil' => $idperfil, 'idmenu' => $persub[$k]->idmenu, 'idsubmenu' => $persub[$k]->idsubmenu, 'tipopermiso' => $persub[$k]->tipopermiso]);
                    }

                }else{
                    $id = DB::connection("General")->table('mc1001')->insertGetId($data);    
                }
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
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
