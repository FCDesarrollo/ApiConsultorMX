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
}
