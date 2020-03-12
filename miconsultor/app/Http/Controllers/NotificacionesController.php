<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificacionesController extends Controller
{
    public function notificacionesCRM(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $idusuario = $valida[0]['usuario'][0]->idusuario;
            $notificaciones = DB::select('select n.*,d.* from mc_notificaciones n inner join mc_notificaciones_det d on n.id=d.idnotificacion
                 where  n.idusuario = ?', [$idusuario]);
                 $array["notificacion"]  = $notificaciones;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function usuariosNotificacion(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $idconcepto = $request->idconcepto;
            $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$request->rfc]);
            $bdd = $empresa[0]->rutaempresa;
            $usuarios = DB::select("select c.*,u.* from  $bdd.mc_usuarios_concepto c 
                    INNER JOIN " .env('DB_DATABASE_GENERAL').".mc1001 u ON c.id_usuario=u.idusuario  
                    where id_concepto = ?", [$idconcepto]);
            $array["usuarios"]  = $usuarios;

        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function reenviarNotificacion(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $idusuario = $valida[0]['usuario'][0]->idusuario;
            $idreq = $request->idregistro;
            $idmenu = $request->idmenu;
            $idsubmenu= $request->idsubmenu;
            $datosNoti[0]["idusuario"] = $idusuario;
            $datosNoti[0]["encabezado"] = $request->encabezado;

            $mensaje = str_replace('iddocumento=0','iddocumento='.$idreq, $request->mensaje);
            $datosNoti[0]["mensaje"] = $mensaje;
            $datosNoti[0]["idmodulo"] = 4;
            $datosNoti[0]["idmenu"] = $idmenu;
            $datosNoti[0]["idsubmenu"] = $idsubmenu;
            $datosNoti[0]["idregistro"] = $idreq;
            $datosNoti[0]["usuarios"] ="";
            $usuarios = $request->usuarios;
            $datosNoti[0]["usuarios"] ="";
            // $usuarios = DB::select("select c.id_usuario,s.notificaciones,u.correo from dublockc_empresa02.mc_usuarios_concepto c 
            //                             inner join dublockc_empresa02.mc_usersubmenu s on c.id_usuario=s.idusuario 
            //                             inner join " .env('DB_DATABASE_GENERAL').".mc1001 u on c.id_usuario=u.idusuario
            //                             where c.id_concepto = ? and s.idsubmenu= ?", [3, $idsubmenu]);
            // return $usuarios;
            if (count($usuarios) > 0) {
                $datosNoti[0]["usuarios"] = $usuarios ;
            }
            if ($datosNoti[0]["usuarios"] != "") {
                $resp = enviaNotificacionCo($datosNoti);
            }else{
                $array["error"] = 10;
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function eliminaNotificaion(Request $request)
    {

        $valida = verificaUsuario($request->usuario,$request->pwd);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] == 0) {
            $idusuario = $valida[0]["usuario"][0]->idusuario;
            $rfc = $request->rfc;
            $valida = VerificaEmpresa($rfc, $idusuario);
            $array["error"] = $valida[0]["error"];

            if ($valida[0]['error'] == 0){
                $idnotificacion =  $request->idnotificacion;
                

                DB::delete('delete mc_notificaciones_det where idusuario = ? and idnotificacion= ?',
                     [$idusuario, $idnotificacion]);
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
