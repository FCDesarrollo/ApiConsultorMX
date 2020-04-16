<?php


namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Mail;
use App\Mail\MensajesValidacion;
use App\Mail\MensajesValidacionNuevoUsuario;

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
                $data["subject"] = "Confirma tu cuenta";
                $correo = $data['correo'];
                Mail::to($correo)->send(new MensajesValidacion($data));
               
                $password = $data["password"];             
                $data['password'] = password_hash($password, PASSWORD_BCRYPT);
                unset($data["idusuario"]);
                unset($data["subject"]);
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

    public function verificaCodigo(Request $request)
    {
        $user = $request->usuario;
        $codigo = $request->codigo;
        $array["error"] = 0;
        $usuario = DB::connection("General")->select("SELECT * FROM mc1001 
                            WHERE (correo='$user' or cel='$user') AND status=1");
        if (!empty($usuario)) {
            $verificacion = DB::connection("General")->select("SELECT * FROM mc1001 
                            WHERE (correo='$user' or cel='$user') AND status=1 AND tipo=0 and identificador='$codigo'");
            if (!empty($verificacion)){
                $idusuario= $verificacion[0]->idusuario;
                $verificacion[0]->tipo= 1;
                DB::update('update mc1001 set tipo = 1 where idusuario = ?', [$idusuario]);
                $array["usuario"] = $verificacion[0];
            }else{
                $array["error"]  = 6;
            }
        }else{
            $array["error"]  = 2;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);

    }

    public function reenviaCodigo(Request $request)
    {
        $user = $request->usuario;
        $data = $request;
        $array["error"] = 0;
        $usuario = DB::connection("General")->select("SELECT * FROM mc1001 
                            WHERE (correo='$user' or cel='$user') AND status=1");
        if (!empty($usuario)) {
            $correo = $usuario[0]->correo;
            if ($usuario[0]->tipo != 0) {
                $array["error"] = 7;
            }else{
                $identificador = $request->identificador;
                $idusuario = $usuario[0]->idusuario;
                Mail::to($correo)->send(new MensajesValidacion($data));
                DB::update('update mc1001 set identificador = ? where idusuario = ?', [$identificador, $idusuario]);
            }
        }else{
            $array["error"] = 2;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function listaUsuariosEmpresa(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$request->rfc]);
            $idempresa = $empresa[0]->idempresa;
            $usuarios = DB::connection("General")->select('select u.* from mc1002 v INNER JOIN mc1001 u ON 
                                    v.idusuario=u.idusuario where idempresa = ?', [$idempresa]);
            $array["usuarios"] = $usuarios;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }


    public function modificaPermisoModulo(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso == 1) {
                $array["error"] = 4;
            }else{
                $valorPermiso = $request->permiso;
                $idusuariomod = $request->idusuario;
                $idmodulo = $request->idmodulo;
                DB::update('update mc_usermod set tipopermiso = ? where idusuario = ? and idmodulo= ?', 
                            [$valorPermiso, $idusuariomod, $idmodulo]);
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function modificaPermisoMenu(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso == 1) {
                $array["error"] = 4;
            }else{
                $valorPermiso = $request->permiso;
                $idusuariomod = $request->idusuario;
                $idmenu = $request->idmenu;
                DB::update('update mc_usermenu set tipopermiso = ? where idusuario = ? and idmenu= ?', 
                            [$valorPermiso, $idusuariomod, $idmenu]);
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function modificaPermisoSubmenu(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso == 1) {
                $array["error"] = 4;
            }else{
                $valorPermiso = $request->permiso;
                $idusuariomod = $request->idusuario;
                $idsubmenuM = $request->modidsubmenu;
                DB::update('update mc_usersubmenu set tipopermiso = ? where idusuario = ? and idsubmenu= ?', 
                            [$valorPermiso, $idusuariomod, $idsubmenuM]);
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function permisosUsuarioGeneral(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso == 1) {
                $array["error"] = 4;
            }else{
                $idusuariomod = $request->idusuario;
                $modpermiso = DB::select('select idmodulo,tipopermiso,nombre AS nombreperfil from mc_usermod muser 
			                        INNER JOIN mc_profiles mp ON muser.idperfil = mp.idperfil where idusuario = ?', [$idusuariomod]);
                for ($i=0; $i < count($modpermiso); $i++) {
                    $idmodulo = $modpermiso[$i]->idmodulo; 
                    $menupermiso = DB::select('select idmenu,tipopermiso from mc_usermenu 
                                    where idusuario = ? AND idmodulo= ?', [$idusuariomod, $idmodulo]);
                    for ($x=0; $x < count($menupermiso); $x++) { 
                        $idmenu = $menupermiso[$x]->idmenu;
                        $submenupermiso = DB::select('select idsubmenu,tipopermiso,notificaciones from mc_usersubmenu 
                                            where idusuario = ? AND idmenu = ?', [$idusuariomod, $idmenu]);
                        $menupermiso[$x]->permisossubmenus = $submenupermiso;
                    }
                    $modpermiso[$i]->permisosmenu = $menupermiso;
                    $array["permisomodulos"][$i] = $modpermiso[$i];
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function desvinculaUsuario(Request $request)
    {

        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso != 3) {
                $array["error"] = 4;
            }else{
                $rfc = $request->rfc;
                $idusuariomod = $request->idusuario;
                $estatus = $request->estatus;
                $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$rfc]);
                if (!empty($empresa)) {
                    $idempresa = $empresa[0]->idempresa;
                    DB::connection("General")->update('update mc1002 set estatus = ? where idusuario = ? and idempresa = ?', [$estatus, $idusuariomod, $idempresa]);
                }else{
                    $array["error"] = 1;
                }
                
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function eliminaUsuarioEmpresa(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso != 3) {
                $array["error"] = 4;
            }else{
                $rfc = $request->rfc;
                $idusuariomod = $request->idusuario;
                $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$rfc]);
                if (!empty($empresa)) {
                    $idempresa = $empresa[0]->idempresa;
                    DB::connection("General")->table('mc1002')->where("idusuario", $idusuariomod)->where("idempresa", $idempresa)->delete();
                    DB::table('mc_usermod')->where("idusuario", $idusuariomod)->delete();
                    DB::table('mc_usermenu')->where("idusuario", $idusuariomod)->delete();
                    DB::table('mc_usersubmenu')->where("idusuario", $idusuariomod)->delete();
                }else{
                    $array["error"] = 1;
                }
                
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function editaNotificacion(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $idusarm = $request->idusuario;
            $idsubmenu = $request->idsubmenuMod;
            $notificaion = $request->notificacion;

            DB::update('update mc_usersubmenu set notificaciones = ? 
                    where idusuario = ? AND  idsubmenu = ?', [$notificaion, $idusarm, $idsubmenu]);
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function traerNuevoUsuarioRegistrado(Request $request)
    {
        $array["error"] = 0;
        $usuarioRegistrado = DB::connection("General")->select('select * from mc1001 where idusuario = ?', [$request->idusuario]);
        $array["usuario"] = $usuarioRegistrado;
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function cambiarContraNuevoUsuarioRegistrado(Request $request)
    {
        $array["error"] = 0;
        $password = password_hash($request->password, PASSWORD_BCRYPT);
        DB::connection("General")->table('mc1001')->where("idusuario", $request->idusuario)->update(["password" => $password, "tipo" => 1]);
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function crearNuevoUsuario(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $correo = $request->correo;
            $celular = $request->celular;
            $validarcorreo = DB::connection("General")->select('select * from mc1001 where correo = ?', [$correo]);
            $validarcel = DB::connection("General")->select('select * from mc1001 where cel = ?', [$celular]);
            $array["correo"] = count($validarcorreo);
            $array["cel"] = count($validarcel);
            if(count($validarcorreo) == 0) {
                if(count($validarcel) == 0) {
                    $nombre = $request->nombre;
                    $apellidop = $request->apellidop;
                    $apellidom = $request->apellidom;
                    $perfil = $request->perfil;
                    $identificador = $request->identificador;
                    $password = password_hash($request->password, PASSWORD_BCRYPT);
                    $idUsuario = DB::connection("General")->table('mc1001')->insertGetId(['nombre' => ucwords(strtolower($nombre)), 'apellidop' => ucwords(strtolower($apellidop)), 'apellidom' => ucwords(strtolower($apellidom)), 'cel' => $celular, 'correo' => $correo, 'password' => $password, 'status' => 1, 'tipo' => -1, 'identificador' => $identificador]);
                    $idEmpresa = $request->idempresa;
                    $fechaVinculacion = $request->fecha_vinculacion;
                    $idUsuarioVinculador = $request->idusuario_vinculador;

                    $permisosmodulos = DB::select('select * from mc_modpermis where idperfil = ?', [$perfil]);
                    $permisosmenus = DB::select('select * from mc_menupermis where idperfil = ?', [$perfil]);
                    $permisossubmenus = DB::select('select * from mc_submenupermis where idperfil = ?', [$perfil]);

                    for($x=0 ; $x<count($permisosmodulos) ; $x++) {
                        DB::insert('insert into mc_usermod (idusuario, idperfil, idmodulo, tipopermiso) values (?, ?, ?, ?)', [$idUsuario, $perfil, $permisosmodulos[$x]->idmodulo, $permisosmodulos[$x]->tipopermiso]);
                    }

                    for($x=0 ; $x<count($permisosmenus) ; $x++) {
                        DB::insert('insert into mc_usermenu (idusuario, idperfil, idmodulo, idmenu, tipopermiso) values (?, ?, ?, ?, ?)', [$idUsuario, $perfil, $permisosmenus[$x]->idmodulo, $permisosmenus[$x]->idmenu, $permisosmenus[$x]->tipopermiso]);
                    }

                    for($x=0 ; $x<count($permisossubmenus) ; $x++) {
                        DB::insert('insert into mc_usersubmenu (idusuario, idperfil, idmenu, idsubmenu, tipopermiso, notificaciones) values (?, ?, ?, ?, ?, ?)', [$idUsuario, $perfil, $permisossubmenus[$x]->idmenu, $permisossubmenus[$x]->idsubmenu, $permisossubmenus[$x]->tipopermiso, $permisossubmenus[$x]->notificaciones]);
                    } 

                    DB::insert('insert into mc_userprofile (idusuario, idperfil) values (?, ?)', [$idUsuario, $perfil]);

                    DB::connection("General")->table('mc1002')->insert(['idusuario' => $idUsuario, 'idempresa' => $idEmpresa, 'estatus' => 1, 'fecha_vinculacion' => $fechaVinculacion, 'idusuario_vinculador' => $idUsuarioVinculador == $idUsuario ? 0 : $idUsuarioVinculador]);

                    $data["subject"] = "Confirma tu cuenta";
                    $data["identificador"] = $identificador;
                    $data["link"] = $request->linkconfirmacion.$idUsuario;
                    Mail::to($correo)->send(new MensajesValidacionNuevoUsuario($data));
                }
                else {
                    $array["error"] = -1;
                }
            }
            else {
                $array["error"] = -2;
            }
            
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function vincularUsuario(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $correo = $request->correo;
            $perfil = $request->perfil;
            $usuario = DB::connection("General")->select('select * from mc1001 where correo = ?', [$correo]);
            if(count($usuario) > 0) {
                $idUsuario = $usuario[0]->idusuario;
                $idEmpresa = $request->idempresa;
                $fechaVinculacion = $request->fecha_vinculacion;
                $idUsuarioVinculador = $request->idusuario_vinculador;
                
                $validarUsuario = DB::connection("General")->select('select * from mc1002 where idusuario = ? and idempresa = ?', [$idUsuario, $idEmpresa]);
                if(count($validarUsuario) > 0) {
                    $array["error"] = 47;
                }
                else {

                    $permisosmodulos = DB::select('select * from mc_modpermis where idperfil = ?', [$perfil]);
                    $permisosmenus = DB::select('select * from mc_menupermis where idperfil = ?', [$perfil]);
                    $permisossubmenus = DB::select('select * from mc_submenupermis where idperfil = ?', [$perfil]);

                    for($x=0 ; $x<count($permisosmodulos) ; $x++) {
                        DB::insert('insert into mc_usermod (idusuario, idperfil, idmodulo, tipopermiso) values (?, ?, ?, ?)', [$idUsuario, $perfil, $permisosmodulos[$x]->idmodulo, $permisosmodulos[$x]->tipopermiso]);
                    }

                    for($x=0 ; $x<count($permisosmenus) ; $x++) {
                        DB::insert('insert into mc_usermenu (idusuario, idperfil, idmodulo, idmenu, tipopermiso) values (?, ?, ?, ?, ?)', [$idUsuario, $perfil, $permisosmenus[$x]->idmodulo, $permisosmenus[$x]->idmenu, $permisosmenus[$x]->tipopermiso]);
                    }

                    for($x=0 ; $x<count($permisossubmenus) ; $x++) {
                        DB::insert('insert into mc_usersubmenu (idusuario, idperfil, idmenu, idsubmenu, tipopermiso, notificaciones) values (?, ?, ?, ?, ?, ?)', [$idUsuario, $perfil, $permisossubmenus[$x]->idmenu, $permisossubmenus[$x]->idsubmenu, $permisossubmenus[$x]->tipopermiso, $permisossubmenus[$x]->notificaciones]);
                    } 

                    DB::insert('insert into mc_userprofile (idusuario, idperfil) values (?, ?)', [$idUsuario, $perfil]);

                    DB::connection("General")->table('mc1002')->insert(['idusuario' => $idUsuario, 'idempresa' => $idEmpresa, 'estatus' => 1, 'fecha_vinculacion' => $fechaVinculacion, 'idusuario_vinculador' => $idUsuarioVinculador == $idUsuario ? 0 : $idUsuarioVinculador]);
                }
            }
            else {
                $array["error"] = 2;
            }
            
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
