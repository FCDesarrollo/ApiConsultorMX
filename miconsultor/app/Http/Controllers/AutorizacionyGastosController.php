<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class AutorizacionyGastosController extends Controller
{
    public function cargaConceptos(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $idsubmenu = $request->idsubmenu;
            $todos = $request->all;
            if ($todos == 1) {
                $conceptos = DB::select('select * from mc_conceptos where status = ?', [1]);
            } else {
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

        if ($valida[0]['error'] == 0) {
            $estatus = DB::connection("General")->select('select * from mc1015');
            $array["estatus"] = $estatus;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function nuevoRequerimiento(Request $request)
    {
        set_time_limit(0);
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            } else {
                $idsucursal = $request->idsucursal;
                $fecha = $request->fecha;
                $idusuario = $valida[0]['usuario'][0]->idusuario;
                $fechareq = $request->fechareq;
                $descripcion = $request->descripcion;
                $importe = $request->importe;
                $estado = $request->estadodocumento;
                $estatus = $request->estatus;

                $idconcepto = $request->idconcepto;
                $serie = $request->serie;
                $folio = $request->folio;
                $idsubmenu = $request->idsubmenu;
                $validarCFS = DB::select('select * from mc_requerimientos where id_concepto = ? and serie = ? and folio = ?', [$idconcepto, $serie, $folio]);
                if(count($validarCFS) > 0) {
                    $array["error"] = 48;
                    return json_encode($array, JSON_UNESCAPED_UNICODE);
                }
                $array["idrequerimiento"] = 0;
                $idreq = DB::table('mc_requerimientos')->insertGetId([
                    'id_sucursal' => $idsucursal, 'fecha' => $fecha,
                    'id_usuario' => $idusuario, 'fecha_req' => $fechareq, 'id_departamento' => $idsubmenu,
                    'descripcion' => $descripcion, 'importe_estimado' => $importe, 'estado_documento' => $estado,
                    'id_concepto' => $idconcepto, 'serie' => $serie, 'folio' => $folio, 'estatus' => $estatus
                ]);
                if ($idreq != 0) {
                    $array["idrequerimiento"] = $idreq;
                    DB::insert(
                        'insert into mc_requerimientos_bit (id_usuario, id_req, fecha, status)values(?, ?, ?, ?)',
                        [$idusuario, $idreq, $fecha, $estado]
                    );
                    $servidor = getServidorNextcloud();
                    if ($servidor == "") {
                        $array["error"] = 10;
                    } else {
                        $idmodulo = 4;
                        $idmenu = $request->idmenu;

                        $validaCarpetas = getExisteCarpeta($idmodulo, $idmenu, $idsubmenu);
                        $array["error"] = $validaCarpetas[0]["error"];
                        if ($validaCarpetas[0]['error'] == 0) {
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
                            foreach ($archivos as $key => $file) {
                                //return $key;
                                $posp = strpos($key, 'principal');
                                $poss = strpos($key, 'secundario');

                                if ($poss === false and $posp >= 0) {
                                    $tipo = 1;
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
                                } elseif ($posp === false and $poss >= 0) {
                                    $tipo = 2;
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
                                    $resultado = subirArchivoNextcloud($archivo, $file, $rfc, $servidor, $u_storage, $p_storage, $carpetamodulo, $carpetamenu, $carpetasubmenu, $codigoarchivo, $consecutivo);
                                    //return $resultado;
                                    if ($resultado["archivo"]["error"] == 0) {
                                        $codigodocumento = $codigoarchivo . $consecutivo;
                                        $type = explode(".", $archivo);
                                        $directorio = $rfc . '/' . $carpetamodulo . '/' . $carpetamenu . '/' . $carpetasubmenu;
                                        $target_path = $directorio . '/' . $codigodocumento . "." . $type[count($type) - 1];
                                        $link = GetLinkArchivo($target_path, $servidor, $u_storage, $p_storage);

                                        $idarchivo = 0;
                                        if ($link != "") {
                                            $idarchivo = DB::table('mc_requerimientos_doc')->insertGetId([
                                                'id_usuario' => $idusuario, 'id_req' => $idreq,
                                                'documento' => $archivo, 'codigo_documento' => $codigodocumento, 'tipo_doc' => $tipo, 'download' => $link
                                            ]);
                                            $n = $n + 1;
                                        }

                                        $array2["archivos"][$cont] =  array(
                                            "archivo" => $file->getClientOriginalName(),
                                            "codigo" => $resultado["archivo"]["codigo"],
                                            "link" => $link,
                                            "status" => ($link != "" ? 0 : 2),
                                            "detalle" => ($link != "" ? "¡Cargado Correctamente!" : "¡Link no generado, error al subir!"),
                                            "idarchivo" => $idarchivo
                                        );
                                        if ($posp >= 0) {
                                            $countreg = $countreg + ($link != "" ? 1 : 0);
                                        } elseif ($poss >= 0) {
                                            $countreg2 = $countreg2 + ($link != "" ? 1 : 0);
                                        }
                                    } else {
                                        $array2["archivos"][$cont] =  array(
                                            "archivo" => $file->getClientOriginalName(),
                                            "codigo" => "",
                                            "link" => "",
                                            "status" => ($resultado["archivo"]["error"] == 3 ? 3 : 1),
                                            "detalle" => "¡No se pudo subir el archivo!"
                                        );
                                    }
                                } else {
                                    $array2["archivos"][$cont] =  array(
                                        "archivo" => $archivo,
                                        "codigo" => $codigoarchivo . $consecutivo,
                                        "link" => "",
                                        "status" => 4,
                                        "detalle" => "¡Ya existe!"
                                    );
                                    if ($posp >= 0) {
                                        $countreg = $countreg + 1;
                                    } elseif ($poss >= 0) {
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

                            $mensaje = str_replace('iddocumento=0', 'iddocumento=' . $idreq, $request->mensaje);
                            $datosNoti[0]["mensaje"] = $mensaje;
                            $datosNoti[0]["fecha"] = $request->fecha;
                            $datosNoti[0]["idmodulo"] = 4;
                            $datosNoti[0]["idmenu"] = $idmenu;
                            $datosNoti[0]["idsubmenu"] = $idsubmenu;
                            $datosNoti[0]["idregistro"] = $idreq;
                            $datosNoti[0]["usuarios"] = "";
                            $usuarios = DB::select("select c.id_usuario,s.notificaciones,u.correo from $bdd.mc_usuarios_concepto c 
                                        inner join $bdd.mc_usersubmenu s on c.id_usuario=s.idusuario 
                                        inner join " . env('DB_DATABASE_GENERAL') . ".mc1001 u on c.id_usuario=u.idusuario
                                        where c.id_concepto = ? and s.idsubmenu= ?", [$idconcepto, $idsubmenu]);
                            if (!empty($usuarios)) {
                                $datosNoti[0]["usuarios"] = $usuarios;
                            }

                            if ($datosNoti[0]["usuarios"] != "") {
                                $resp = enviaNotificacion($datosNoti);
                            } else {
                                $array["error"] = 10;
                            }

                            if ($estatus == 2) {
                                $rfcproveedor = $request->rfcproveedor;
                                $nombreproveedor = $request->nombreproveedor;
                                $resp = $this->insertaAsociacion($idreq, $idreq, $importe, $rfcproveedor, $nombreproveedor, 0);
                            }
                        }
                    }
                } else {
                    $array["error"] = 4; //no se registro
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }


    public function creaGasto(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            } else {
                $idrequerimiento = $request->idrequerimiento;
                $importe = $request->importe;
                $rfcproveedor = $request->rfcproveedor;
                $nombreproveedor = $request->nombreproveedor;

                $requerimiento = DB::select('select * from mc_requerimientos where idReq = ?', [$idrequerimiento]);
                if (!empty($requerimiento)) {
                    $idsuc = $requerimiento[0]->id_sucursal;
                    $fecha = $request->fecha;
                    $idusuario = $valida[0]['usuario'][0]->idusuario;
                    $fechagasto = $request->fechagasto;
                    $iddepar = $requerimiento[0]->id_departamento;
                    $des = $requerimiento[0]->descripcion;
                    $estado = $request->estatusgasto;
                    $idconce = $request->idconcepto;
                    $serie = $requerimiento[0]->serie;
                    $folio = $requerimiento[0]->folio;
                    $estatus = 2;
                    $idbitacora = $request->idbitacora;

                    $idgasto = DB::table('mc_requerimientos')->insertGetId([
                        "id_sucursal" => $idsuc,
                        "fecha" => $fecha, "id_usuario" => $idusuario, "fecha_req" => $fechagasto,
                        "id_departamento" => $iddepar, "descripcion" => $des, "importe_estimado" => $importe,
                        "estado_documento" => $estado, "id_concepto" => $idconce, "serie" => $serie,
                        "folio" => $folio, "estatus" => $estatus
                    ]);
                    $array["idgasto"] = $idgasto != 0 ? $idgasto : 0;
                    $resp = $this->insertaAsociacion($idrequerimiento, $idgasto, $importe, $rfcproveedor, $nombreproveedor, $idbitacora);

                    $documentos = DB::select('select * from mc_requerimientos_doc where id_req = ?', [$idrequerimiento]);
                    for ($i = 0; $i < count($documentos); $i++) {
                        DB::insert('insert into mc_requerimientos_doc (id_usuario, id_req, documento,
                            codigo_documento, tipo_doc, download) values (?, ?, ?, ?, ?, ?)', [
                            $documentos[$i]->id_usuario, $idgasto, $documentos[$i]->documento,
                            $documentos[$i]->codigo_documento, $documentos[$i]->tipo_doc,
                            $documentos[$i]->download
                        ]);
                    }
                    /* $idmenu = $request->idmenu;
                    $idsubmenu = $request->idsubmenu;
                    $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$request->rfc]);
                    $bdd = $empresa[0]->rutaempresa;
                    $datosNoti[0]["idusuario"] = $idusuario;
                    $datosNoti[0]["encabezado"] = $request->encabezado;

                    $mensaje = str_replace('iddocumento=0', 'iddocumento=' . $idgasto, $request->mensaje);
                    $datosNoti[0]["mensaje"] = $mensaje;
                    $datosNoti[0]["fecha"] = $request->fecha;
                    $datosNoti[0]["idmodulo"] = 4;
                    $datosNoti[0]["idmenu"] = $idmenu;
                    $datosNoti[0]["idsubmenu"] = $idsubmenu;
                    $datosNoti[0]["idregistro"] = $idgasto;
                    $datosNoti[0]["usuarios"] = "";
                    $usuarios = DB::select("select c.id_usuario,s.notificaciones,u.correo from $bdd.mc_usuarios_concepto c 
                                inner join $bdd.mc_usersubmenu s on c.id_usuario=s.idusuario 
                                inner join " . env('DB_DATABASE_GENERAL') . ".mc1001 u on c.id_usuario=u.idusuario
                                where c.id_concepto = ? and s.idsubmenu= ?", [$idconce, $idsubmenu]);
                    if (!empty($usuarios)) {
                        $datosNoti[0]["usuarios"] = $usuarios;
                    }

                    if ($datosNoti[0]["usuarios"] != "") {
                        $resp = enviaNotificacion($datosNoti);
                    } else {
                        $array["error"] = 10;
                    } */
                } else {
                    $array["error"] = 9;
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function insertaAsociacion($idrequerimiento, $idgasto, $importe, $rfc, $nombre, $idbitacora)
    {
        DB::insert('insert into mc_requerimientos_aso (idrequerimiento, idgasto, importe, rfc, nombre, id_bit) 
                    values (?, ?, ?, ?, ?, ?)', [$idrequerimiento, $idgasto, $importe, $rfc, $nombre, $idbitacora]);
        return 0;
    }

    public function listaRequerimientos(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $estatus = $request->estatus;
            $idusuario = $valida[0]['usuario'][0]->idusuario;
            $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$request->rfc]);
            $bdd = $empresa[0]->rutaempresa;
            $query = "select r.*,s.sucursal,u.nombre,u.apellidop,u.apellidom, 
            if(!isnull(a.idrequerimiento) and a.idrequerimiento <> a.idgasto and a.id_bit = 0, 1 , 0 ) as requerimiento_gasto, 
            if(isnull(a.idrequerimiento) and r.estatus = 2 and (SELECT id_bit FROM mc_requerimientos_aso WHERE idgasto = r.idReq) = 0, 1 , 0 ) as gasto_requerimiento, 
            if(isnull(a.idrequerimiento) and a.idrequerimiento <> a.idgasto and r.idReq <> a.idrequerimiento and a.id_bit <> 0, 1 , 0 ) as gasto_surtido,
            if(!isnull(a.idrequerimiento) and a.idrequerimiento = a.idgasto and a.id_bit = 0, 1 , 0 ) as gasto, 
            if((isnull(a.idrequerimiento) OR r.idReq = a.idrequerimiento) and r.estatus = 1 and (a.id_bit <> 0 || isnull(a.id_bit)), 1 , 0 ) as requerimiento 
            from $bdd.mc_requerimientos r INNER JOIN " . env('DB_DATABASE_GENERAL') . ".mc1001 u ON r.id_usuario=u.idusuario inner join $bdd.mc_catsucursales s ON r.id_sucursal=s.idsucursal left join mc_requerimientos_aso a ON a.idrequerimiento = r.idReq where id_usuario =$idusuario and id_departamento=$request->idsubmenu and estatus=$estatus";
            $requser = DB::select($query);

            $conceptos = DB::select('select id_concepto from mc_usuarios_concepto where id_usuario = ?', [$idusuario]);
            for ($i = 0; $i < count($conceptos); $i++) {
                $idconcepto = $conceptos[$i]->id_concepto;
                $query = "select r.*,s.sucursal,u.nombre,u.apellidop,u.apellidom, if(!isnull(a.idrequerimiento) and a.idrequerimiento <> a.idgasto and a.id_bit = 0, 1 , 0 ) as requerimiento_gasto, 
                if(isnull(a.idrequerimiento) and r.estatus = 2 and (SELECT id_bit FROM mc_requerimientos_aso WHERE idgasto = r.idReq) = 0, 1 , 0 ) as gasto_requerimiento, 
                if(isnull(a.idrequerimiento) and a.idrequerimiento <> a.idgasto and r.idReq <> a.idrequerimiento and a.id_bit <> 0, 1 , 0 ) as gasto_surtido,
                if(!isnull(a.idrequerimiento) and a.idrequerimiento = a.idgasto and a.id_bit = 0, 1 , 0 ) as gasto, 
                if((isnull(a.idrequerimiento) OR r.idReq = a.idrequerimiento) and r.estatus = 1 and (a.id_bit <> 0 || isnull(a.id_bit)), 1 , 0 ) as requerimiento from $bdd.mc_requerimientos r INNER JOIN " . env('DB_DATABASE_GENERAL') . ".mc1001 u ON r.id_usuario=u.idusuario inner join $bdd.mc_catsucursales s ON r.id_sucursal=s.idsucursal left join mc_requerimientos_aso a ON a.idrequerimiento = r.idReq where id_concepto =$idconcepto and id_usuario<>$idusuario and id_departamento=$request->idsubmenu and estatus=$estatus";
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

        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            } else {
                $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$request->rfc]);
                $bdd = $empresa[0]->rutaempresa;
                $idrequerimiento = $request->idrequerimiento;
                $requerimiento = DB::select('select * from mc_requerimientos where idReq = ?', [$idrequerimiento]);

                if (!empty($requerimiento)) {
                    $requerimiento[0]->historial = DB::select('select b.*,u.nombre,u.apellidop,u.apellidom
                    from ' . $bdd . '.mc_requerimientos_bit b 
                    INNER JOIN ' . env("DB_DATABASE_GENERAL") . '.mc1001 u ON b.id_usuario=u.idusuario 
                     where id_req = ?', [$idrequerimiento]);
                    //return $requerimiento;
                    $requerimiento[0]->documentos = DB::select('select d.*,u.nombre,u.apellidop,u.apellidom
                                    from ' . $bdd . '.mc_requerimientos_doc d 
                                    INNER JOIN ' . env("DB_DATABASE_GENERAL") . '.mc1001 u ON d.id_usuario=u.idusuario 
                                    where id_req = ?', [$idrequerimiento]);
                    $requerimiento[0]->gastos = DB::select('select * from mc_requerimientos_aso where idrequerimiento = ?', [$idrequerimiento]);
                    $requerimiento[0]->requerimiento = DB::select('select mc_requerimientos.*, mc_conceptos.nombre_concepto as concepto, mc_catsucursales.sucursal as sucursal, if(mc_requerimientos_aso.idrequerimiento <> mc_requerimientos_aso.idgasto and mc_requerimientos_aso.id_bit = 0, 1 , 0 ) as gasto_requerimiento, ( select concat(u.nombre, " " , u.apellidop, " " ,u.apellidom) from ' . env('DB_DATABASE_GENERAL') . '.mc1001 u where idusuario = (select b.id_usuario from mc_requerimientos_bit b WHERE b.id_req = mc_requerimientos.idReq and mc_requerimientos.estado_documento = b.status 
                    )) as usuario from mc_requerimientos inner join mc_requerimientos_aso on mc_requerimientos.idReq = mc_requerimientos_aso.idrequerimiento inner join mc_conceptos on mc_requerimientos.id_concepto = mc_conceptos.id inner join mc_catsucursales on mc_requerimientos.id_sucursal = mc_catsucursales.idsucursal where mc_requerimientos_aso.idgasto = ?', [$idrequerimiento]);
                    if ($requerimiento[0]->estatus == 2) {
                        $datosExtra = DB::select('select rfc,nombre from mc_requerimientos_aso where idgasto = ?', [$idrequerimiento]);
                        $requerimiento[0]->rfcproveedor = $datosExtra[0]->rfc;
                        $requerimiento[0]->nombreproveedor = $datosExtra[0]->nombre;
                    }
                    $array["requerimiento"] = $requerimiento;
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function agregaEstatus(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            } else {
                $idusuario = $valida[0]['usuario'][0]->idusuario;
                $idrequerimiento = $request->idrequerimiento;
                $observaciones = $request->observaciones;
                $fecha = $request->fecha;
                $estatus = $request->estatus;

                $idmenu = $request->idmenu;
                $idsubmenu = $request->idsubmenu;

                $asociacionrequerimiento = DB::select('select * from mc_requerimientos_aso where idrequerimiento = ?', [$idrequerimiento]);
                if (count($asociacionrequerimiento) != 0 && $asociacionrequerimiento[0]->idrequerimiento != $asociacionrequerimiento[0]->idgasto && $asociacionrequerimiento[0]->id_bit == 0) {
                    DB::update('update mc_requerimientos set estado_documento = ? where idReq = ?', [$estatus, $asociacionrequerimiento[0]->idgasto]);
                }

                $idhistorial = DB::table('mc_requerimientos_bit')->insertGetId([
                    "id_req" => $idrequerimiento,
                    "id_usuario" => $idusuario, "fecha" => $fecha, "observaciones" => $observaciones, "status" => $estatus
                ]);

                $array["idbitacora"] = $idhistorial;
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
                            inner join " . env('DB_DATABASE_GENERAL') . ".mc1001 u on c.id_usuario=u.idusuario
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
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            } else {
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
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $permiso = $valida[0]['permiso'];
            if ($permiso < 1) {
                $array["error"] = 4;
            } else {
                $idmenu = $request->idmenu;
                $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$request->rfc]);
                $idempresa = $empresa[0]->idempresa;
                $bdd = $empresa[0]->rutaempresa;
                $usuarios = DB::select("select u.* from " . env('DB_DATABASE_GENERAL') . ".mc1002 v INNER JOIN " . env('DB_DATABASE_GENERAL') . ".mc1001 u ON 
                                        v.idusuario=u.idusuario 
                                        inner join  $bdd.mc_usermenu m ON u.idusuario=m.idusuario
                                        where m.tipopermiso>0 and m.idmenu= ? and idempresa = ?", [$idmenu, $idempresa]);
                for ($i = 0; $i < count($usuarios); $i++) {
                    $idusuario = $usuarios[$i]->idusuario;
                    $conceptos = DB::select('select c.*, (select l.importe from mc_usuarios_limite_gastos l where id_usuario = u.id_usuario and id_concepto = u.id_concepto) as limite from mc_usuarios_concepto u 
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
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            } else {
                $idusuario = $request->idusuario;
                $idconcepto = $request->idconcepto;
                DB::insert(
                    'insert into mc_usuarios_concepto (id_usuario, id_concepto) values (?, ?)',
                    [$idusuario, $idconcepto]
                );
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function traerLimiteGastosUsuario(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $permiso = $valida[0]['permiso'];
            if ($permiso < 1) {
                $array["error"] = 4;
            } else {
                $idusuario = $request->idusuario;
                $idconcepto = $request->idconcepto;
                $limiteGastoUsuario = DB::select('select * from mc_usuarios_limite_gastos where id_usuario = ? and id_concepto = ?', [$idusuario, $idconcepto]);
                $array["limiteGasto"] = $limiteGastoUsuario;
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardaLimiteGastos(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            } else {
                $idusuario = $request->idusuario;
                $idconcepto = $request->idconcepto;
                $importe = $request->importe;
                $limistesGastoUsuario = DB::select('select * from mc_usuarios_limite_gastos where id_usuario = ? and id_concepto = ?', [$idusuario, $idconcepto]);
                if (count($limistesGastoUsuario) === 0) {
                    DB::insert(
                        'insert into mc_usuarios_limite_gastos (id_usuario, id_concepto, importe) values (?, ?, ?)',
                        [$idusuario, $idconcepto, $importe]
                    );
                } else {
                    DB::update('update mc_usuarios_limite_gastos set importe = ? where id_usuario = ? and id_concepto = ?', [$importe, $idusuario, $idconcepto]);
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public  function eliminaPermisoAutorizacion(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $permiso = $valida[0]['permiso'];
            if ($permiso < 3) {
                $array["error"] = 4;
            } else {
                $idusuario = $request->idusuario;
                $idconcepto = $request->idconcepto;
                DB::delete('delete from mc_usuarios_concepto where id_usuario = ? and id_concepto=?', [$idusuario, $idconcepto]);
                DB::delete('delete from mc_usuarios_limite_gastos where id_usuario = ? and id_concepto=?', [$idusuario, $idconcepto]);
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function editarRequerimiento(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            } else {
                $idreq = $request->idrequerimiento;
                $requerimiento = DB::select('select id_concepto,fecha_req,estatus from mc_requerimientos where idReq = ?', [$idreq]);
                if (empty($requerimiento)) {
                    $array["error"] = 9;
                } else {
                    $fechareq = $requerimiento[0]->fecha_req;
                    $idconcepto = $requerimiento[0]->id_concepto;
                    $idmodulo = 4;
                    $idmenu = $request->idmenu;
                    $idsubmenu = $request->idsubmenu;
                    $idusuario = $valida[0]['usuario'][0]->idusuario;
                    $descripcion = $request->descripcion;

                    DB::update('update mc_requerimientos set descripcion = ? where idReq = ?', [$descripcion, $idreq]);

                    $estatus = $requerimiento[0]->estatus;
                    if ($estatus == 2) {
                        $importe = $request->importe;
                        $fecha2 = $request->fecha_req;
                        DB::update('update mc_requerimientos set importe_estimado = ?, fecha_req = ? where idReq = ?', [$importe, $fecha2, $idreq]);
                        DB::update('update mc_requerimientos_aso set importe = ? where idgasto = ?', [$importe, $idreq]);
                    }

                    $validaCarpetas = getExisteCarpeta($idmodulo, $idmenu, $idsubmenu);
                    $array["error"] = $validaCarpetas[0]["error"];
                    if ($validaCarpetas[0]['error'] == 0) {
                        $carpetamodulo = $validaCarpetas[0]['carpetamodulo'];
                        $carpetamenu = $validaCarpetas[0]['carpetamenu'];
                        $carpetasubmenu = $validaCarpetas[0]['carpetasubmenu'];

                        $servidor = getServidorNextcloud();
                        $archivos = $request->file();
                        $fecha = $request->fecha;

                        $rfc = $request->rfc;
                        $u_storage = $request->usuario_storage;
                        $p_storage = $request->password_storage;

                        $consecutivo = $this->getConsecutioRequ($fecha, $idconcepto, 1);
                        $countreg = $consecutivo;
                        $consecutivo = $this->getConsecutioRequ($fecha, $idconcepto, 2);
                        $countreg2 = $consecutivo;

                        $cont = 0;
                        $n = 0;

                        foreach ($archivos as $key => $file) {
                            //return $key;
                            $posp = strpos($key, 'principal');
                            $poss = strpos($key, 'secundario');


                            if ($poss === false and $posp >= 0) {
                                $tipo = 1;
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
                            } elseif ($posp === false and $poss >= 0) {
                                $tipo = 2;
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
                            $codigoarchivo = $rfc . "_" . $codfec . "_" . $ts . $mod . "_";

                            $existe = DB::select("SELECT doc.* FROM mc_requerimientos_doc AS doc 
                                    INNER JOIN mc_requerimientos AS r ON doc.id_req = r.idReq 
                                    WHERE documento = '$archivo' AND r.fecha_req = '$fechareq' AND r.id_concepto = $idconcepto");
                            if (empty($existe)) {
                                $resultado = subirArchivoNextcloud($archivo, $file, $rfc, $servidor, $u_storage, $p_storage, $carpetamodulo, $carpetamenu, $carpetasubmenu, $codigoarchivo, $consecutivo);
                                //return $resultado;
                                if ($resultado["archivo"]["error"] == 0) {
                                    $codigodocumento = $codigoarchivo . $consecutivo;
                                    $type = explode(".", $archivo);
                                    $directorio = $rfc . '/' . $carpetamodulo . '/' . $carpetamenu . '/' . $carpetasubmenu;
                                    $target_path = $directorio . '/' . $codigodocumento . "." . $type[count($type) - 1];
                                    $link = GetLinkArchivo($target_path, $servidor, $u_storage, $p_storage);

                                    $idarchivo = 0;
                                    if ($link != "") {
                                        $idarchivo = DB::table('mc_requerimientos_doc')->insertGetId([
                                            'id_usuario' => $idusuario, 'id_req' => $idreq,
                                            'documento' => $archivo, 'codigo_documento' => $codigodocumento, 'tipo_doc' => $tipo, 'download' => $link
                                        ]);
                                        $n = $n + 1;
                                    }

                                    $array2["archivos"][$cont] =  array(
                                        "archivo" => $file->getClientOriginalName(),
                                        "codigo" => $resultado["archivo"]["codigo"],
                                        "link" => $link,
                                        "status" => ($link != "" ? 0 : 2),
                                        "detalle" => ($link != "" ? "¡Cargado Correctamente!" : "¡Link no generado, error al subir!"),
                                        "idarchivo" => $idarchivo
                                    );
                                    if ($posp >= 0) {
                                        $countreg = $countreg + ($link != "" ? 1 : 0);
                                    } elseif ($poss >= 0) {
                                        $countreg2 = $countreg2 + ($link != "" ? 1 : 0);
                                    }
                                } else {
                                    $array2["archivos"][$cont] =  array(
                                        "archivo" => $file->getClientOriginalName(),
                                        "codigo" => "",
                                        "link" => "",
                                        "status" => ($resultado["archivo"]["error"] == 3 ? 3 : 1),
                                        "detalle" => "¡No se pudo subir el archivo!"
                                    );
                                }
                            } else {
                                $array2["archivos"][$cont] =  array(
                                    "archivo" => $archivo,
                                    "codigo" => $codigoarchivo . $consecutivo,
                                    "link" => "",
                                    "status" => 4,
                                    "detalle" => "¡Ya existe!"
                                );
                                if ($posp >= 0) {
                                    $countreg = $countreg + 1;
                                } elseif ($poss >= 0) {
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
                        $datosNoti[0]["usuarios"] = "";
                        $usuarios = DB::select("select c.id_usuario,s.notificaciones,u.correo from $bdd.mc_usuarios_concepto c 
                                    inner join $bdd.mc_usersubmenu s on c.id_usuario=s.idusuario 
                                    inner join " . env('DB_DATABASE_GENERAL') . ".mc1001 u on c.id_usuario=u.idusuario
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
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $permiso = $valida[0]['permiso'];
            if ($permiso < 3) {
                $array["error"] = 4;
            } else {
                $idreq = $request->idrequerimiento;
                $requerimiento = DB::select('select id_concepto,fecha_req from mc_requerimientos where idReq = ?', [$idreq]);
                if (empty($requerimiento)) {
                    $array["error"] = 9;
                } else {
                    $rfc = $request->rfc;
                    $idmodulo = 4;
                    $idmenu = $request->idmenu;
                    $idsubmenu = $request->idsubmenu;
                    $validaCarpetas = getExisteCarpeta($idmodulo, $idmenu, $idsubmenu);
                    $array["error"] = $validaCarpetas[0]["error"];

                    if ($validaCarpetas[0]['error'] == 0) {
                        $carpetamodulo = $validaCarpetas[0]['carpetamodulo'];
                        $carpetamenu = $validaCarpetas[0]['carpetamenu'];
                        $carpetasubmenu = $validaCarpetas[0]['carpetasubmenu'];

                        $servidor = getServidorNextcloud();
                        $u_storage = $request->usuario_storage;
                        $p_storage = $request->password_storage;

                        $ruta = $rfc . '/' . $carpetamodulo . '/' . $carpetamenu . '/' . $carpetasubmenu;

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
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $permiso = $valida[0]['permiso'];
            if ($permiso < 3) {
                $array["error"] = 4;
            } else {
                $idrequerimiento = $request->idrequerimiento;

                $rfc = $request->rfc;
                $idmodulo = 4;
                $idmenu = $request->idmenu;
                $idsubmenu = $request->idsubmenu;
                $validaCarpetas = getExisteCarpeta($idmodulo, $idmenu, $idsubmenu);
                $array["error"] = $validaCarpetas[0]["error"];
                $estatus = $request->estatus;
                if ($validaCarpetas[0]['error'] == 0 and $estatus == 1) {
                    $carpetamodulo = $validaCarpetas[0]['carpetamodulo'];
                    $carpetamenu = $validaCarpetas[0]['carpetamenu'];
                    $carpetasubmenu = $validaCarpetas[0]['carpetasubmenu'];

                    $servidor = getServidorNextcloud();
                    $u_storage = $request->usuario_storage;
                    $p_storage = $request->password_storage;

                    $ruta = $rfc . '/' . $carpetamodulo . '/' . $carpetamenu . '/' . $carpetasubmenu;

                    $archivos = DB::select("SELECT  codigo_documento, documento FROM mc_requerimientos_doc 
                                            WHERE id_req=$idrequerimiento");
                    for ($i = 0; $i < count($archivos); $i++) {
                        $type = explode(".", $archivos[$i]->documento);
                        $nombrearchivo = $ruta . "/" . $archivos[$i]->codigo_documento . "." . $type[1];
                        $resp = eliminaArchivoNextcloud($servidor, $u_storage, $p_storage, $nombrearchivo);
                    }
                }
                DB::table('mc_requerimientos')->where("idReq", $idrequerimiento)->delete();
                DB::table('mc_requerimientos_bit')->where("id_req", $idrequerimiento)->delete();
                DB::table('mc_requerimientos_doc')->where("id_req", $idrequerimiento)->delete();

                if ($estatus == 2) {
                    $asociacion = DB::select('select id_bit from mc_requerimientos_aso where idgasto = ?', [$idrequerimiento]);
                    if (!empty($asociacion)) {
                        $idbit = $asociacion[0]->id_bit;
                        if ($idbit != 0) {
                            $bitacora = DB::select('select id_req from mc_requerimientos_bit where id_bit = ?', [$idbit]);
                            if (!empty($bitacora)) {
                                $idreqAsoc = $bitacora[0]->id_req;
                                DB::update('update mc_requerimientos_bit set status = 5 where id_req = ? and status=4', [$idreqAsoc]);
                                DB::table('mc_requerimientos_bit')->where("id_bit", $idbit)->delete();
                                $utbitacora = DB::select('SELECT  * FROM mc_requerimientos_bit 
                                WHERE id_req = ? ORDER BY id_bit DESC LIMIT 1', [$idreqAsoc]);
                            }

                            if (!empty($utbitacora)) {
                                $esta = ($utbitacora[0]->status == 4) ? 5 : $utbitacora[0]->status;
                                DB::update('update mc_requerimientos set estado_documento = ? 
                                            where idReq = ?', [$esta, $idreqAsoc]);
                            }
                        }
                    }

                    if ($request->gastoRequerimiento == 1) {
                        $requerimiento = DB::select('select * from mc_requerimientos_aso where idgasto = ? AND id_bit = 0', [$idrequerimiento]);
                        if (count($requerimiento) > 0) {
                            $idrequerimientogasto = $requerimiento[0]->idrequerimiento;
                            DB::table('mc_requerimientos')->where("idReq", $idrequerimientogasto)->delete();
                            DB::table('mc_requerimientos_doc')->where("id_req", $idrequerimientogasto)->delete();
                            DB::table('mc_requerimientos_bit')->where("id_req", $idrequerimientogasto)->delete();
                            DB::table('mc_requerimientos_doc')->where("id_req", $idrequerimiento)->delete();
                            DB::table('mc_requerimientos_aso')->where("idgasto", $idrequerimiento)->delete();
                        }

                        if ($validaCarpetas[0]['error'] == 0) {
                            $carpetamodulo = $validaCarpetas[0]['carpetamodulo'];
                            $carpetamenu = $validaCarpetas[0]['carpetamenu'];
                            $carpetasubmenu = $validaCarpetas[0]['carpetasubmenu'];

                            $servidor = getServidorNextcloud();
                            $u_storage = $request->usuario_storage;
                            $p_storage = $request->password_storage;

                            $ruta = $rfc . '/' . $carpetamodulo . '/' . $carpetamenu . '/' . $carpetasubmenu;

                            $archivos = DB::select("SELECT  codigo_documento, documento FROM mc_requerimientos_doc 
                                                WHERE id_req=$idrequerimiento");
                            for ($i = 0; $i < count($archivos); $i++) {
                                $type = explode(".", $archivos[$i]->documento);
                                $nombrearchivo = $ruta . "/" . $archivos[$i]->codigo_documento . "." . $type[1];
                                $resp = eliminaArchivoNextcloud($servidor, $u_storage, $p_storage, $nombrearchivo);
                            }
                        }
                    } else {
                        DB::table('mc_requerimientos_aso')->where("idgasto", $idrequerimiento)->delete();
                    }
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function getTotalImporte(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        $array["importe"] = 0;
        if ($valida[0]['error'] == 0) {
            $idrequerimiento = $request->idrequerimiento;
            $requeAsoc = DB::select('select sum(importe) as importe from mc_requerimientos_aso where idrequerimiento = ?', [$idrequerimiento]);
            if (!empty($requeAsoc)) {
                $array["importe"] = $requeAsoc[0]->importe;
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function carga_ProveedoresADW(Request $request)
    {
        $valida = verificaUsuario($request->usuario, $request->pwd);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] == 0) {
            $proveedores = $request->proveedores;
            $idusuario = $valida[0]["usuario"][0]->idusuario;
            $rfc = $request->rfc;
            ConnectDatabaseRFC($rfc);
            $valida = VerificaEmpresa($rfc, $idusuario);
            $array["error"] = $valida[0]["error"];

            if ($valida[0]['error'] == 0) {
                for ($i = 0; $i < count($proveedores); $i++) {
                    $cod = $proveedores[$i]['codigo'];
                    $rfcpro = $proveedores[$i]['rfcproveedor'];
                    $razon = $proveedores[$i]['razon'];
                    $pro = DB::select('select * from mc_catproveedores where codigo = ? and rfc= ?', [$cod, $rfcpro]);
                    if (empty($pro)) {
                        DB::insert('insert into mc_catproveedores (codigo, rfc, razonsocial) 
                        values (?, ?, ?)', [$cod, $rfcpro, $razon]);
                    }
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function traerProveedores(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $proveedores = DB::select('select * from mc_catproveedores where rfc is not null order by rfc');
            $array["proveedores"] = $proveedores;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function traerRequerimientoPorSerie(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0) {
            $requerimiento = DB::select('select * from mc_requerimientos where serie = ? order by idreq desc limit 1', [$request->serie]);
            $array["requerimiento"] = $requerimiento;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function RequerimientoMarcado(Request $request)
    {

        $autenticacion = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);

        $array["error"] = $autenticacion[0]["error"];

        $idusuario = $autenticacion[0]["usuario"][0]->idusuario;

        if ($autenticacion[0]['error'] == 0) {
            
            $registros = $request->registros;          

            for ($i = 0; $i < count($registros); $i++) {                

                if ($registros[$i]["status"] == 1) {
                    $iddoc = $registros[$i]['iddoc'];
                    $idgasto = $registros[$i]['id'];
                    $doc = DB::select("SELECT * FROM mc_requerimientos_rel WHERE idgasto = $idgasto AND iddocadw=$iddoc");

                    if (empty($doc)) {
                        DB::table('mc_requerimientos_rel')->insertGetId([
                            'idgasto' => $idgasto,
                            'iddocadw' => $iddoc,
                            'conceptoadw' => $registros[$i]["concepto"],
                            'idmodulo' => $registros[$i]["idmodulo"],
                            'folioadw' => $registros[$i]["folio"],
                            'serieadw' => $registros[$i]["serie"],
                            'UUID' => $registros[$i]["UUID"]
                        ]);                      
                    }
                } else {
                    if(isset($registros[$i]['iddoc'])) {
                        $iddoc = $registros[$i]['iddoc'];
                        $gasto = DB::select("SELECT idgasto FROM mc_requerimientos_rel WHERE iddocadw=$iddoc");
                        $idgasto = $gasto[0]->idgasto;                        
                        DB::table('mc_requerimientos_rel')->where("idgasto", $idgasto)->where("iddocadw", $iddoc)->delete();
                    } else {
                        $UUID = $registros[$i]['UUID'];
                        $gasto = DB::select("SELECT idgasto FROM mc_requerimientos_rel WHERE UUID=$UUID");
                        $idgasto = $gasto[0]->idgasto;
                        DB::table('mc_requerimientos_rel')->where("idgasto", $idgasto)->where("UUID", $UUID)->delete();
                    }
                }

                $reg = DB::select("SELECT count(idgasto) as reg FROM mc_requerimientos_rel WHERE idgasto=$idgasto");
                $sta = ($reg[0]->reg > 0 ? 1 : 0);

                $resp = DB::table('mc_requerimientos')->where("idReq", $idgasto)->update([
                        'id_agente' => $idusuario,
                        'fecha_procesado' => date_create($registros[$i]["fechapro"]), 
                        'estatus_procesado' => $sta
                    ]);  

                if (!empty($resp)) {
                    $registros[$i]["estatus"] = true;
                } else {
                    $registros[$i]["estatus"] = false;
                }
            }

            $array["registros"] = $registros;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }    

    public function getGastosRelacionados(Request $request)
    {
        $autenticacion = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);

        $idsubmenu = $request->idsubmenu;

        $array["error"] = $autenticacion[0]["error"];    
        
        if ($autenticacion[0]['error'] == 0) {
            $reg = DB::select("SELECT * FROM mc_requerimientos_rel WHERE idmodulo = $idsubmenu");
            if(!empty($reg)){
                $array["relacionados"] = $reg;  
            }
        }
        
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function getProveedoresRelacionadosAGastos(Request $request)
    {
        $autenticacion = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);

        $idsubmenu = $request->idsubmenu;

        $array["error"] = $autenticacion[0]["error"];    
        
        if ($autenticacion[0]['error'] == 0) {
            $result = DB::select("SELECT asoc.* FROM mc_requerimientos r INNER JOIN mc_requerimientos_aso asoc ON r.idReq = asoc.idgasto WHERE r.estatus = 2");
            if(!empty($result)){
                $array["proveedores"] = $result;  
            }
        }
        
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }    
}
