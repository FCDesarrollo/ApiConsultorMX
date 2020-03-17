<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class AutorizacionyGastosController extends Controller
{
    public function cargaConceptos(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $idsubmenu = $request->idsubmenu;
            $todos = $request->all;
            if ($todos ==1) {
                $conceptos = DB::select('select * from mc_conceptos where status = ?', [1]);
            }else{
                $conceptos = DB::select('select * from mc_conceptos where status = ? and idsubmenu = ?', [1, $idsubmenu]);
            }
            
            $array["conceptos"] = $conceptos;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function cargaEstatus(Request $request)
    {
        $valida = verificaUsuario($request->usuario, $request->pwd);
          
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
           $estatus = DB::connection("General")->select('select * from mc1015');
           $array["estatus"] = $estatus;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function nuevoRequerimiento(Request $request)
    {
        set_time_limit(0);
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            }else{
                $idsucursal = $request->idsucursal;
                $fecha = $request->fecha;
                $idusuario = $valida[0]['usuario'][0]->idusuario;
                $fechareq = $request->fechareq;
                $descripcion = $request->descripcion;
                $importe = $request->importe;
                $estado = 1;

                $idconcepto = $request->idconcepto;
                $serie = $request->serie;
                $folio = $request->folio;
                $idsubmenu = $request->idsubmenu;
                $array["idrequerimiento"] = 0;
                $idreq = DB::table('mc_requerimientos')->insertGetId(['id_sucursal' => $idsucursal, 'fecha' => $fecha, 
                                'id_usuario' => $idusuario, 'fecha_req' => $fechareq, 'id_departamento' => $idsubmenu,
                                'descripcion' => $descripcion, 'importe_estimado' => $importe, 'estado_documento' => $estado,
                                'id_concepto' => $idconcepto, 'serie' => $serie, 'folio' => $folio, 'estatus' => 1]);
                if ($idreq !=0) {
                    $array["idrequerimiento"] = $idreq;
                    DB::insert('insert into mc_requerimientos_bit (id_usuario, id_req, fecha, status)values(?, ?, ?, ?)',
                             [$idusuario, $idreq, $fecha, $estado]);
                    $servidor = getServidorNextcloud();
                    if ($servidor == "") {
                        $array["error"] = 10;
                    }else{
                        $idmodulo = 4;
                        $idmenu = $request->idmenu;
                        
                        $validaCarpetas = getExisteCarpeta($idmodulo, $idmenu, $idsubmenu);
                        $array["error"] = $validaCarpetas[0]["error"];
                        if ($validaCarpetas[0]['error'] == 0){
                            $carpetamodulo = $validaCarpetas[0]['carpetamodulo'];
                            $carpetamenu = $validaCarpetas[0]['carpetamenu'];
                            $carpetasubmenu = $validaCarpetas[0]['carpetasubmenu'];

                          
                            $archivos = $request->file();

                            $rfc = $request->rfc;
                            $u_storage = $request->usuario_storage;
                            $p_storage = $request->password_storage;

                            $consecutivo = $this->getConsecutioRequ($fechareq, $idconcepto, 1);
                            $countreg = $consecutivo;
                            $consecutivo = $this->getConsecutioRequ($fechareq, $idconcepto, 2);
                            $countreg2 = $consecutivo;

                            $cont = 0;
                            $n = 0;
                            foreach($archivos as $key => $file){
                                    //return $key;
                                    $posp = strpos($key, 'principal');
                                    $poss = strpos($key, 'secundario');
                                    
                                    if ($poss===false and $posp >=0) {
                                        $tipo =1;
                                        $ts = 'P';
                                        if (strlen($countreg) == 1) {
                                            $consecutivo = "000" . $countreg;
                                        } elseif (strlen($countreg) == 2) {
                                            $consecutivo = "00" . $countreg;
                                        } elseif (strlen($countreg) == 3) {
                                            $consecutivo = "0" . $countreg;
                                        } else {
                                            $consecutivo = $countreg;
                                        }
                                    }elseif ($posp===false and $poss >= 0) {
                                        $tipo =2;
                                        $ts = 'S';
                                        if (strlen($countreg2) == 1) {
                                            $consecutivo = "000" . $countreg2;
                                        } elseif (strlen($countreg2) == 2) {
                                            $consecutivo = "00" . $countreg2;
                                        } elseif (strlen($countreg2) == 3) {
                                            $consecutivo = "0" . $countreg2;
                                        } else {
                                            $consecutivo = $countreg2;
                                        }
                                    }

                                    $archivo = $file->getClientOriginalName();
                                    //return $archivo;

                                    $mod = substr(strtoupper($carpetasubmenu), 0, 3);

                                    $string = explode("-", $fechareq);
                                    $codfec = substr($string[0], 2) . $string[1];
                                    $codigoarchivo = $rfc . "_" . $codfec . "_" . $ts . $mod . "_";

                                    $existe = DB::select("SELECT doc.* FROM mc_requerimientos_doc AS doc 
                                            INNER JOIN mc_requerimientos AS r ON doc.id_req = r.idReq 
                                            WHERE documento = '$archivo' AND r.fecha_req = '$fechareq' AND r.id_concepto = $idconcepto");
                                    if (empty($existe)) {
                                        $resultado = subirArchivoNextcloud($archivo, $file, $rfc, $servidor, $u_storage, $p_storage,$carpetamodulo, $carpetamenu, $carpetasubmenu, $codigoarchivo, $consecutivo);
                                        //return $resultado;
                                        if ($resultado["archivo"]["error"] == 0) {
                                            $codigodocumento = $codigoarchivo . $consecutivo;
                                            $type = explode(".", $archivo);
                                            $directorio = $rfc . '/'. $carpetamodulo .'/' . $carpetamenu . '/' . $carpetasubmenu;
                                            $target_path = $directorio . '/' . $codigodocumento . "." . $type[count($type) - 1];   
                                            $link = GetLinkArchivo($target_path, $servidor, $u_storage, $p_storage);

                                            $idarchivo= 0;
                                            if ($link != "") {
                                                $idarchivo = DB::table('mc_requerimientos_doc')->insertGetId(['id_usuario' => $idusuario, 'id_req' => $idreq,
                                                  'documento' => $archivo, 'codigo_documento' => $codigodocumento,'tipo_doc' => $tipo, 'download' => $link]);
                                                $n= $n +1;
                                            }
                                            
                                            $array2["archivos"][$cont] =  array(
                                                "archivo" => $file->getClientOriginalName(),
                                                "codigo" => $resultado["archivo"]["codigo"],
                                                "link" => $link,
                                                "status" => ($link != "" ? 0 : 2),
                                                "detalle" => ($link != "" ? "¡Cargado Correctamente!" : "¡Link no generado, error al subir!"),
                                                "idarchivo" => $idarchivo
                                            );
                                            if ($posp >=0) {
                                                $countreg = $countreg + ($link != "" ? 1 : 0);
                                            }elseif ($poss >= 0) {
                                                $countreg2 = $countreg2 + ($link != "" ? 1 : 0);
                                            }
                                        }else{
                                            $array2["archivos"][$cont] =  array(
                                                "archivo" => $file->getClientOriginalName(),
                                                "codigo" => "",
                                                "link" => "",
                                                "status" => ($resultado["archivo"]["error"] == 3 ? 3 : 1),
                                                "detalle" => "¡No se pudo subir el archivo!"
                                            );
                                        }
                                    }else{
                                        $array2["archivos"][$cont] =  array(
                                            "archivo" => $archivo,
                                            "codigo" => $codigoarchivo . $consecutivo,
                                            "link" => "",
                                            "status" => 4,
                                            "detalle" => "¡Ya existe!"
                                        );
                                        if ($posp >=0) {
                                            $countreg = $countreg + 1;
                                        }elseif ($poss >= 0) {
                                            $countreg2 = $countreg2 + 1;
                                        }
                                    }
                                    $cont = $cont + 1;
                            }
                            $array["archivos"] = $array2["archivos"];
                            
                            $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$request->rfc]);
                            $bdd = $empresa[0]->rutaempresa;
                            $datosNoti[0]["idusuario"] = $idusuario;
                            $datosNoti[0]["encabezado"] = $request->encabezado;

                            $mensaje = str_replace('iddocumento=0','iddocumento='.$idreq, $request->mensaje);
                            $datosNoti[0]["mensaje"] = $mensaje;
                            $datosNoti[0]["fecha"] = $request->fecha;
                            $datosNoti[0]["idmodulo"] = 4;
                            $datosNoti[0]["idmenu"] = $idmenu;
                            $datosNoti[0]["idsubmenu"] = $idsubmenu;
                            $datosNoti[0]["idregistro"] = $idreq;
                            $datosNoti[0]["usuarios"] ="";
                            $usuarios = DB::select("select c.id_usuario,s.notificaciones,u.correo from $bdd.mc_usuarios_concepto c 
                                        inner join $bdd.mc_usersubmenu s on c.id_usuario=s.idusuario 
                                        inner join " .env('DB_DATABASE_GENERAL').".mc1001 u on c.id_usuario=u.idusuario
                                        where c.id_concepto = ? and s.idsubmenu= ?", [$idconcepto, $idsubmenu]);
                            if (!empty($usuarios)) {
                                $datosNoti[0]["usuarios"] = $usuarios;
                                
                            }
                            
                            if ($datosNoti[0]["usuarios"] != "") {
                                $resp = enviaNotificacion($datosNoti);
                            }else{
                                $array["error"] = 10;
                            }
                            
                        }
                    }
                }else{
                    $array["error"] = 4; //no se registro
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function listaRequerimientos(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $idusuario = $valida[0]['usuario'][0]->idusuario;
            $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$request->rfc]);
            $bdd = $empresa[0]->rutaempresa;
            $query ="select r.*,s.sucursal,u.nombre,u.apellidop,u.apellidom from $bdd.mc_requerimientos r INNER JOIN " .env('DB_DATABASE_GENERAL').".mc1001 u ON r.id_usuario=u.idusuario 
                            inner join $bdd.mc_catsucursales s ON r.id_sucursal=s.idsucursal                     
                                where id_usuario =$idusuario and id_departamento=$request->idsubmenu";
            $requser = DB::select($query);
            
            $conceptos = DB::select('select id_concepto from mc_usuarios_concepto where id_usuario = ?', [$idusuario]);
            for ($i=0; $i < count($conceptos); $i++) { 
                $idconcepto = $conceptos[$i]->id_concepto;
                $query ="select r.*,s.sucursal,u.nombre,u.apellidop,u.apellidom from $bdd.mc_requerimientos r INNER JOIN " .env('DB_DATABASE_GENERAL').".mc1001 u ON r.id_usuario=u.idusuario 
                                inner join $bdd.mc_catsucursales s ON r.id_sucursal=s.idsucursal    
                            where id_concepto =$idconcepto and id_usuario<>$idusuario and id_departamento=$request->idsubmenu";
                $reqconceto = DB::select($query);
                $requerimientos = array_merge($requser, $reqconceto);
                $requser = $requerimientos;
            }
            if (empty($requerimientos)) {
                $requerimientos = $requser;
            }
            $array["requerimientos"] = $requerimientos;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function getConsecutioRequ($fechareq, $idconcepto, $tipodoc)
    {
            $fecha = $fechareq;
            $fecha = strtotime($fecha);
            $mes = intval(date("m", $fecha));
            $año = intval(date("Y", $fecha));
            $ultregistro = DB::select("SELECT MAX(d.id) AS id FROM mc_requerimientos a 
                        INNER JOIN mc_requerimientos_doc d ON a.idReq = d.id_req 
                        WHERE a.id_concepto = $idconcepto AND MONTH(a.fecha_req) = $mes AND
                         YEAR(a.fecha_req) = $año AND tipo_doc=$tipodoc");

            if (!empty($ultregistro)) {
                $ultimoid = $ultregistro[0]->id;
                if ($ultimoid > 0) {
                    $ultarchivo = DB::select("SELECT codigo_documento FROM mc_requerimientos_doc WHERE id = $ultimoid");
                    $nombre_a = $ultarchivo[0]->codigo_documento;
                    $consecutivo = substr($nombre_a, -4);
                    $consecutivo = $consecutivo + 1;
                } else {
                    $consecutivo = "0001";
                }
            } else {
                $consecutivo = "0001";
            }

            return $consecutivo;
    }

    public function datosRequerimiento(Request $request)
    {
        
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        
        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            }else{
                $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$request->rfc]);
                $bdd = $empresa[0]->rutaempresa;
                $idrequerimiento = $request->idrequerimiento;
                $requerimiento = DB::select('select * from mc_requerimientos where idReq = ?', [$idrequerimiento]);
                
                if (!empty($requerimiento)) {
                    $requerimiento[0]->historial = DB::select('select b.*,u.nombre,u.apellidop,u.apellidom
                    from '.$bdd.'.mc_requerimientos_bit b 
                    INNER JOIN ' .env("DB_DATABASE_GENERAL").'.mc1001 u ON b.id_usuario=u.idusuario 
                     where id_req = ?', [$idrequerimiento]);
                    //return $requerimiento;
                    $requerimiento[0]->documentos = DB::select('select d.*,u.nombre,u.apellidop,u.apellidom
                                    from '.$bdd.'.mc_requerimientos_doc d 
                                    INNER JOIN ' .env("DB_DATABASE_GENERAL").'.mc1001 u ON d.id_usuario=u.idusuario 
                                    where id_req = ?', [$idrequerimiento]);
                    
                                    $requerimiento[0]->prueba ="prueba"; 
                    $array["requerimiento"] = $requerimiento;
                }
                
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function agregaEstatus(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            }else{
                $idusuario = $valida[0]['usuario'][0]->idusuario;
                $idrequerimiento = $request->idrequerimiento;
                $observaciones = $request->observaciones;
                $fecha = $request->fecha;
                $estatus = $request->estatus;

                $idmenu = $request->idmenu;
                $idsubmenu = $request->idsubmenu;

                DB::insert('insert into mc_requerimientos_bit (id_usuario,id_req, fecha,observaciones, status) values 
                    (?, ?, ?, ?, ?)', [$idusuario, $idrequerimiento, $fecha,$observaciones, $estatus]);
                DB::update('update mc_requerimientos set estado_documento = ? where idReq = ?', [$estatus, $idrequerimiento]);
                
                $requerimiento = DB::select('select id_concepto from mc_requerimientos where idReq = ?', [$idrequerimiento]);
                $idconcepto = $requerimiento[0]->id_concepto;
                $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$request->rfc]);
                $bdd = $empresa[0]->rutaempresa;
                $datosNoti[0]["idusuario"] = $idusuario;
                $datosNoti[0]["encabezado"] = $request->encabezado;
                $datosNoti[0]["mensaje"] = $request->mensaje;
                $datosNoti[0]["fecha"] = $request->fecha;
                $datosNoti[0]["idmodulo"] = 4;
                $datosNoti[0]["idmenu"] = $idmenu;
                $datosNoti[0]["idsubmenu"] = $idsubmenu;
                $datosNoti[0]["idregistro"] = $idrequerimiento;
                $usuarios = DB::select("select c.id_usuario,s.notificaciones,u.correo from $bdd.mc_usuarios_concepto c 
                            inner join $bdd.mc_usersubmenu s on c.id_usuario=s.idusuario 
                            inner join " .env('DB_DATABASE_GENERAL').".mc1001 u on c.id_usuario=u.idusuario
                            where c.id_concepto = ? and s.idsubmenu= ?", [$idconcepto, $idsubmenu]);
                if (!empty($usuarios)) {
                    $datosNoti[0]["usuarios"] = $usuarios;
                }
                
                if ($datosNoti[0]["usuarios"] != "") {
                    $resp = enviaNotificacion($datosNoti);
                }
            
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function eliminaEstatus(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            }else{
                $idbit = $request->idbitacora;
                $idrequerimiento = $request->idrequerimiento;
                $estatus = $request->estatus;
                DB::delete('delete from mc_requerimientos_bit where id_req = ? and id_bit = ?', [$idrequerimiento, $idbit]);
                DB::update('update mc_requerimientos set estado_documento = ? where idReq = ?', [$estatus, $idrequerimiento]);
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function permisosAutorizaciones(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            }else{
                $idmenu = $request->idmenu;
                $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$request->rfc]);
                $idempresa = $empresa[0]->idempresa;
                $bdd = $empresa[0]->rutaempresa;
                $usuarios = DB::select("select u.* from ".env('DB_DATABASE_GENERAL').".mc1002 v INNER JOIN ".env('DB_DATABASE_GENERAL').".mc1001 u ON 
                                        v.idusuario=u.idusuario 
                                        inner join  $bdd.mc_usermenu m ON u.idusuario=m.idusuario
                                        where m.tipopermiso>0 and m.idmenu= ? and idempresa = ?", [$idmenu, $idempresa]);
                for ($i=0; $i < count($usuarios) ; $i++) { 
                    $idusuario = $usuarios[$i]->idusuario;
                    $conceptos = DB::select('select c.* from mc_usuarios_concepto u 
                        inner join mc_conceptos c on u.id_concepto=c.id where id_usuario = ?', [$idusuario]);
                    $usuarios[$i]->conceptos =  $conceptos;    
                }
                $array["usuarios"] = $usuarios;
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardaPermisoAutorizacion(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            }else{
                $idusuario = $request->idusuario;
                $idconcepto = $request->idconcepto;
                DB::insert('insert into mc_usuarios_concepto (id_usuario, id_concepto) values (?, ?)',
                                 [$idusuario, $idconcepto]);
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public  function eliminaPermisoAutorizacion(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso < 3) {
                $array["error"] = 4;
            }else{
                $idusuario = $request->idusuario;
                $idconcepto = $request->idconcepto;
                DB::delete('delete from mc_usuarios_concepto where id_usuario = ? and id_concepto=?', [$idusuario, $idconcepto]);
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function editarRequerimiento(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            }else{
                $idreq = $request->idrequerimiento;
                $requerimiento = DB::select('select id_concepto,fecha_req from mc_requerimientos where idReq = ?', [$idreq]);
                if (empty($requerimiento)) {
                    $array["error"] = 9;
                }else{
                    $fechareq = $requerimiento[0]->fecha_req;
                    $idconcepto = $requerimiento[0]->id_concepto;
                    $idmodulo = 4;
                    $idmenu = $request->idmenu;
                    $idsubmenu = $request->idsubmenu;
                    $idusuario = $valida[0]['usuario'][0]->idusuario;
                    $descripcion = $request->descripcion;

                    DB::update('update mc_requerimientos set descripcion = ? where idReq = ?', [$descripcion, $idreq]);

                    $validaCarpetas = getExisteCarpeta($idmodulo, $idmenu, $idsubmenu);
                    $array["error"] = $validaCarpetas[0]["error"];
                    if ($validaCarpetas[0]['error'] == 0){
                        $carpetamodulo = $validaCarpetas[0]['carpetamodulo'];
                        $carpetamenu = $validaCarpetas[0]['carpetamenu'];
                        $carpetasubmenu = $validaCarpetas[0]['carpetasubmenu'];

                        $servidor = getServidorNextcloud();
                        $archivos = $request->file();
                        $fecha = $request->fecha;

                        $rfc = $request->rfc;
                        $u_storage = $request->usuario_storage;
                        $p_storage = $request->password_storage;

                        $consecutivo = $this->getConsecutioRequ($fecha , $idconcepto, 1);
                        $countreg = $consecutivo;
                        $consecutivo = $this->getConsecutioRequ($fecha , $idconcepto, 2);
                        $countreg2 = $consecutivo;

                        $cont = 0;
                        $n = 0;
                        
                        foreach($archivos as $key => $file){
                            //return $key;
                            $posp = strpos($key, 'principal');
                            $poss = strpos($key, 'secundario');
                            
                            
                            if ($poss===false and $posp >=0 ) {
                                $tipo =1;
                                $ts = 'P';
                                //return $tipo;
                                if (strlen($countreg) == 1) {
                                    $consecutivo = "000" . $countreg;
                                } elseif (strlen($countreg) == 2) {
                                    $consecutivo = "00" . $countreg;
                                } elseif (strlen($countreg) == 3) {
                                    $consecutivo = "0" . $countreg;
                                } else {
                                    $consecutivo = $countreg;
                                }
                            }elseif ($posp===false and $poss >= 0) {
                                $tipo =2;
                                $ts = 'S';
                                //return $tipo;
                                if (strlen($countreg2) == 1) {
                                    $consecutivo = "000" . $countreg2;
                                } elseif (strlen($countreg2) == 2) {
                                    $consecutivo = "00" . $countreg2;
                                } elseif (strlen($countreg2) == 3) {
                                    $consecutivo = "0" . $countreg2;
                                } else {
                                    $consecutivo = $countreg2;
                                }
                            }
                            //return 0;
                            $archivo = $file->getClientOriginalName();
                            //return $archivo;

                            $mod = substr(strtoupper($carpetasubmenu), 0, 3);

                            $string = explode("-", $fecha);
                            $codfec = substr($string[0], 2) . $string[1];
                            $codigoarchivo = $rfc . "_" . $codfec . "_" .$ts. $mod . "_";

                            $existe = DB::select("SELECT doc.* FROM mc_requerimientos_doc AS doc 
                                    INNER JOIN mc_requerimientos AS r ON doc.id_req = r.idReq 
                                    WHERE documento = '$archivo' AND r.fecha_req = '$fechareq' AND r.id_concepto = $idconcepto");
                            if (empty($existe)) {
                                $resultado = subirArchivoNextcloud($archivo, $file, $rfc, $servidor, $u_storage, $p_storage,$carpetamodulo, $carpetamenu, $carpetasubmenu, $codigoarchivo, $consecutivo);
                                //return $resultado;
                                if ($resultado["archivo"]["error"] == 0) {
                                    $codigodocumento = $codigoarchivo . $consecutivo;
                                    $type = explode(".", $archivo);
                                    $directorio = $rfc . '/'. $carpetamodulo .'/' . $carpetamenu . '/' . $carpetasubmenu;
                                    $target_path = $directorio . '/' . $codigodocumento . "." . $type[count($type) - 1];   
                                    $link = GetLinkArchivo($target_path, $servidor, $u_storage, $p_storage);

                                    $idarchivo= 0;
                                    if ($link != "") {
                                        $idarchivo = DB::table('mc_requerimientos_doc')->insertGetId(['id_usuario' => $idusuario, 'id_req' => $idreq,
                                        'documento' => $archivo, 'codigo_documento' => $codigodocumento,'tipo_doc' => $tipo, 'download' => $link]);
                                        $n= $n +1;
                                    }
                                    
                                    $array2["archivos"][$cont] =  array(
                                        "archivo" => $file->getClientOriginalName(),
                                        "codigo" => $resultado["archivo"]["codigo"],
                                        "link" => $link,
                                        "status" => ($link != "" ? 0 : 2),
                                        "detalle" => ($link != "" ? "¡Cargado Correctamente!" : "¡Link no generado, error al subir!"),
                                        "idarchivo" => $idarchivo
                                    );
                                    if ($posp >=0) {
                                        $countreg = $countreg + ($link != "" ? 1 : 0);
                                    }elseif ($poss >= 0) {
                                        $countreg2 = $countreg2 + ($link != "" ? 1 : 0);
                                    }
                                }else{
                                    $array2["archivos"][$cont] =  array(
                                        "archivo" => $file->getClientOriginalName(),
                                        "codigo" => "",
                                        "link" => "",
                                        "status" => ($resultado["archivo"]["error"] == 3 ? 3 : 1),
                                        "detalle" => "¡No se pudo subir el archivo!"
                                    );
                                }
                            }else{
                                $array2["archivos"][$cont] =  array(
                                    "archivo" => $archivo,
                                    "codigo" => $codigoarchivo . $consecutivo,
                                    "link" => "",
                                    "status" => 4,
                                    "detalle" => "¡Ya existe!"
                                );
                                if ($posp >=0) {
                                    $countreg = $countreg + 1;
                                }elseif ($poss >= 0) {
                                    $countreg2 = $countreg2 + 1;
                                }
                            }
                            $cont = $cont + 1;
                        }
                        if (isset($array2["archivos"])) {
                            $array["archivos"] = $array2["archivos"];
                        }
                        $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$request->rfc]);
                        $bdd = $empresa[0]->rutaempresa;
                        $datosNoti[0]["idusuario"] = $idusuario;
                        $datosNoti[0]["encabezado"] = $request->encabezado;
                        $datosNoti[0]["mensaje"] = $request->mensaje;
                        $datosNoti[0]["fecha"] = $request->fecha;
                        $datosNoti[0]["idmodulo"] = 4;
                        $datosNoti[0]["idmenu"] = $idmenu;
                        $datosNoti[0]["idsubmenu"] = $idsubmenu;
                        $datosNoti[0]["idregistro"] = $idreq;
                        $datosNoti[0]["usuarios"]="";
                        $usuarios = DB::select("select c.id_usuario,s.notificaciones,u.correo from $bdd.mc_usuarios_concepto c 
                                    inner join $bdd.mc_usersubmenu s on c.id_usuario=s.idusuario 
                                    inner join " .env('DB_DATABASE_GENERAL').".mc1001 u on c.id_usuario=u.idusuario
                                    where c.id_concepto = ? and s.idsubmenu= ?", [$idconcepto, $idsubmenu]);
                        if (!empty($usuarios)) {
                            $datosNoti[0]["usuarios"] = $usuarios;
                        }
                        
                        if ($datosNoti[0]["usuarios"] != "") {
                            $resp = enviaNotificacion($datosNoti);
                        }
                   
                    }
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function eliminaDocumento(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso < 3) {
                $array["error"] = 4;
            }else{
                $idreq = $request->idrequerimiento;
                $requerimiento = DB::select('select id_concepto,fecha_req from mc_requerimientos where idReq = ?', [$idreq]);
                if (empty($requerimiento)) {
                    $array["error"] = 9;
                }else{
                    $rfc = $request->rfc;
                    $idmodulo = 4;
                    $idmenu = $request->idmenu;
                    $idsubmenu = $request->idsubmenu;
                    $validaCarpetas = getExisteCarpeta($idmodulo, $idmenu, $idsubmenu);
                    $array["error"] = $validaCarpetas[0]["error"];
                
                    if ($validaCarpetas[0]['error'] == 0){
                        $carpetamodulo = $validaCarpetas[0]['carpetamodulo'];
                        $carpetamenu = $validaCarpetas[0]['carpetamenu'];
                        $carpetasubmenu = $validaCarpetas[0]['carpetasubmenu'];

                        $servidor = getServidorNextcloud();
                        $u_storage = $request->usuario_storage;
                        $p_storage = $request->password_storage;

                        $ruta = $rfc . '/'. $carpetamodulo . '/' . $carpetamenu . '/'. $carpetasubmenu;

                        $idarchivo = $request->idarchivo;
                        $idrequerimiento = $request->idrequerimiento;
                        $archivo = DB::select("SELECT  codigo_documento, documento FROM mc_requerimientos_doc WHERE id = $idarchivo AND id_req=$idrequerimiento");
    
                        $type = explode(".", $archivo[0]->documento);
                        $nombrearchivo = $ruta . "/" . $archivo[0]->codigo_documento . "." . $type[1];
                        $resp = eliminaArchivoNextcloud($servidor, $u_storage, $p_storage, $nombrearchivo);

                        if (empty($resp)) {
                            DB::table('mc_requerimientos_doc')->where("id", $idarchivo)->where("id_req", $idrequerimiento)->delete();
                        }
                    }
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function eliminaRequerimiento(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permiso = $valida[0]['permiso'];
            if ($permiso < 3) {
                $array["error"] = 4;
            }else{
                $idrequerimiento = $request->idrequerimiento;

                $rfc = $request->rfc;
                $idmodulo = 4;
                $idmenu = $request->idmenu;
                $idsubmenu = $request->idsubmenu;
                $validaCarpetas = getExisteCarpeta($idmodulo, $idmenu, $idsubmenu);
                $array["error"] = $validaCarpetas[0]["error"];
            
                if ($validaCarpetas[0]['error'] == 0){
                    $carpetamodulo = $validaCarpetas[0]['carpetamodulo'];
                    $carpetamenu = $validaCarpetas[0]['carpetamenu'];
                    $carpetasubmenu = $validaCarpetas[0]['carpetasubmenu'];

                    $servidor = getServidorNextcloud();
                    $u_storage = $request->usuario_storage;
                    $p_storage = $request->password_storage;

                    $ruta = $rfc . '/'. $carpetamodulo . '/' . $carpetamenu . '/'. $carpetasubmenu;

                    $archivos = DB::select("SELECT  codigo_documento, documento FROM mc_requerimientos_doc 
                                            WHERE id_req=$idrequerimiento");
                    for ($i=0; $i < count($archivos); $i++) { 
                        $type = explode(".", $archivos[$i]->documento);
                        $nombrearchivo = $ruta . "/" . $archivos[$i]->codigo_documento . "." . $type[1];
                        $resp = eliminaArchivoNextcloud($servidor, $u_storage, $p_storage, $nombrearchivo);
                    }
                }
                DB::table('mc_requerimientos')->where("idReq", $idrequerimiento)->delete();
                DB::table('mc_requerimientos_bit')->where("id_req", $idrequerimiento)->delete();
                DB::table('mc_requerimientos_doc')->where("id_req", $idrequerimiento)->delete();
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function listaRequerimientosGastos(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $idusuario = $valida[0]['usuario'][0]->idusuario;
            $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$request->rfc]);
            $bdd = $empresa[0]->rutaempresa;
            $query ="select r.*,s.sucursal,u.nombre,u.apellidop,u.apellidom from $bdd.mc_requerimientos r INNER JOIN " .env('DB_DATABASE_GENERAL').".mc1001 u ON r.id_usuario=u.idusuario 
                            inner join $bdd.mc_catsucursales s ON r.id_sucursal=s.idsucursal                     
                                where id_usuario =$idusuario and id_departamento=$request->idsubmenu";
            $requser = DB::select($query);
            
            $conceptos = DB::select('select id_concepto from mc_usuarios_concepto where id_usuario = ?', [$idusuario]);
            for ($i=0; $i < count($conceptos); $i++) { 
                $idconcepto = $conceptos[$i]->id_concepto;
                $query ="select r.*,s.sucursal,u.nombre,u.apellidop,u.apellidom from $bdd.mc_requerimientos_gastos r INNER JOIN " .env('DB_DATABASE_GENERAL').".mc1001 u ON r.id_usuario=u.idusuario 
                                inner join $bdd.mc_catsucursales s ON r.id_sucursal=s.idsucursal    
                            where id_concepto =$idconcepto and id_usuario<>$idusuario and id_departamento=$request->idsubmenu";
                $reqconceto = DB::select($query);
                $requerimientos = array_merge($requser, $reqconceto);
                $requser = $requerimientos;
            }
            if (empty($requerimientos)) {
                $requerimientos = $requser;
            }
            $array["requerimientos"] = $requerimientos;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
    
}
