<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Http\Controller\LoteCargadoExt;

class GeneralesController extends Controller
{
    function PerfilUsuario(Request $request)
    {
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        ConnectDatabase($idempresa);
        //$id = $request->$idusuario;
        //$empleados = DB::select("SELECT *, coalesce((select nombre from sucursales where id = empleados.idsucursal),'') AS sucursal FROM empleados WHERE status=1 ORDER BY nombre");

        //$perfil = DB::select("SELECT * FROM perfiles");

        $perfil = DB::select("SELECT * FROM perfiles p 
                 INNER JOIN usuarioperfil u ON p.idperfil=u.idperfil WHERE u.idusuario='$idusuario'");

        $datos = array(
            "perfil" => $perfil,
        );

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    function PermisosUsuario(Request $request)
    {
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        ConnectDatabase($idempresa);

        $permisos = DB::select("SELECT u.*,p.nombre FROM usuariopermiso u 
        INNER JOIN perfiles p ON u.idperfil=p.idperfil WHERE idusuario='$idusuario'");

        $datos = array(
            "permisos" => $permisos,
        );

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    function updatePermisoUsuario(Request $request)
    {
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idmodulo = $request->idmodulo;
        ConnectDatabase($idempresa);

        DB::table('usuariopermiso')->where("idusuario", $idusuario)->where("idmodulo", $idmodulo)->update(["tipopermiso" => $request->tipopermiso]);
        return $idusuario;
    }

    function VinculaEmpresa(Request $request)
    {
        $rfcempresa = $request->rfc;
        $passempresa = $request->contra;
        $iduser = $request->idusuario;
        $idperfil = $request->idperfil;

        $empresa = DB::connection("General")->select("SELECT idempresa FROM mc1000 WHERE rfc='$rfcempresa' AND password='$passempresa'");
        if (empty($empresa)) {
            $idempresa = 0;
        } else {
            $idempresa = $empresa[0]->idempresa;
            $id = DB::connection("General")->table('mc1002')->insert(
                ['idusuario' => $iduser, 'idempresa' => $idempresa]
            );

            ConnectDatabase($idempresa);
            $idP = DB::table('mc_userprofile')->insert(
                ['idusuario' => $iduser, 'idperfil' => $idperfil]
            );

            //SELECCIONAMOS LOS PERMISOS A LOS MODULOS DEL PERFIL
            $perfil = DB::select("SELECT idmodulo,tipopermiso FROM mc_modpermis WHERE idperfil='$idperfil'");

            //INSERTAMOS LOS PERMISOS DE MODULOS DEL PERFIL AL USUARIO
            foreach ($perfil as $t) {
                $idU = DB::table('mc_usermod')->insert(
                    [
                        'idusuario' => $iduser, 'idperfil' => $idperfil,
                        'idmodulo' => $t->idmodulo, 'tipopermiso' => $t->tipopermiso
                    ]
                );
            }

            //SELECCIONAMOS LOS PERMISOS A LOS MENUS DEL PERFIL
            $perfil = DB::select("SELECT idmodulo,idmenu,tipopermiso FROM mc_menupermis WHERE idperfil='$idperfil'");

            //INSERTAMOS LOS PERMISOS DE MENU DEL PERFIL AL USUARIO
            foreach ($perfil as $t) {
                $idU = DB::table('mc_usermenu')->insert(
                    [
                        'idusuario' => $iduser, 'idperfil' => $idperfil,
                        'idmodulo' => $t->idmodulo, 'idmenu' => $t->idmenu, 'tipopermiso' => $t->tipopermiso
                    ]
                );
            }

            //SELECCIONAMOS LOS PERMISOS A LOS SUBMENUS DEL PERFIL
            $perfil = DB::select("SELECT idmenu,idsubmenu,tipopermiso,notificaciones FROM mc_submenupermis WHERE idperfil='$idperfil'");

            //INSERTAMOS LOS PERMISOS DE SUBMENU DEL PERFIL AL USUARIO
            foreach ($perfil as $t) {
                $idU = DB::table('mc_usersubmenu')->insert(
                    [
                        'idusuario' => $iduser, 'idperfil' => $idperfil,
                        'idmenu' => $t->idmenu, 'idsubmenu' => $t->idsubmenu, 'tipopermiso' => $t->tipopermiso, 'notificaciones' => $t->notificaciones
                    ]
                );
            }
        }
        return $idempresa;
    }

    function PerfilesEmpresa($idempresa)
    {

        ConnectDatabase($idempresa);

        $modulos = DB::select("SELECT * FROM mc_profiles");
        $datos = array(
            "perfiles" => $modulos,
        );
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function EliminarPerfilEmpresa(Request $request)
    {
        ConnectDatabase($request->idempresa);

        $id = $request->idperfil;
        DB::table('mc_profiles')->where("idperfil", $id)->delete(["status" => "0"]);
        return response($id, 200);
        //return $id;
    }

    public function DatosPerfilEmpresa(Request $request)
    {
        ConnectDatabase($request->idempresa);
        $IDPer = $request->idperfil;

        $perfil = DB::select("SELECT * FROM mc_profiles WHERE idperfil='$IDPer'");
        $datos = array(
            "perfil" => $perfil,
        );

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function PermisosPerfil(Request $request)
    {
        ConnectDatabase($request->idempresa);
        $IDPer = $request->idperfil;

        $permisos = DB::select("SELECT * FROM permisos WHERE idperfil='$IDPer'");
        $datos = array(
            "permisos" => $permisos,
        );

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function GuardaPerfilEmpresa(Request $request)
    {
        $idP = $request->idperfil;
        $now = date('Y-m-d');
        ConnectDatabase($request->idempresa);

        if ($idP == 0) {
            $uperfil = DB::select("SELECT max(idperfil) + 1 as idper  FROM mc_profiles");
            if ($uperfil[0]->idper <= 4) {
                $uidperfil = 5;
            } else {
                $uidperfil = $uperfil[0]->idper;
            }

            $idP = DB::table('mc_profiles')->insertGetId(
                [
                    'idperfil' => $uidperfil, 'nombre' => $request->nombre,
                    'descripcion' => $request->desc, 'fecha' => $now, 'status' => "1"
                ]
            );

            $idP = $uidperfil;
        } else {
            DB::table('mc_profiles')->where("idperfil", $idP)->update(["nombre" => $request->nombre, 'descripcion' => $request->desc]);
        }
        return $idP;
    }

    public function ModulosPerfil(Request $request)
    {
        $idU = $request->id;
        ConnectDatabase($request->idempresa);
        if ($idU == 0) {
            $idU = DB::table('mc_modpermis')->insertGetId(
                ['idperfil' => $request->idperfil, 'idmodulo' => $request->idmodulo, 'tipopermiso' => $request->tipopermiso]
            );
        } else {
            DB::table('mc_modpermis')->where("idperfil", $request->idperfil)->where("idmodulo", $request->idmodulo)->update(['tipopermiso' => $request->tipopermiso]);
        }
        return $idU;
    }

    public function MenuPerfil(Request $request)
    {
        $idU = $request->id;
        ConnectDatabase($request->idempresa);
        if ($idU == 0) {
            $idU = DB::table('mc_menupermis')->insertGetId(
                [
                    'idperfil' => $request->idperfil, 'idmodulo' => $request->idmodulo,
                    'idmenu' => $request->idmenu, 'tipopermiso' => $request->tipopermiso
                ]
            );
        } else {
            DB::table('mc_menupermis')->where("idperfil", $request->idperfil)->where("idmodulo", $request->idmodulo)->where("idmenu", $request->idmenu)->update(['tipopermiso' => $request->tipopermiso]);
        }
        return $idU;
    }

    public function SubMenuPerfil(Request $request)
    {
        $idU = $request->id;
        ConnectDatabase($request->idempresa);
        if ($idU == 0) {
            $idU = DB::table('mc_submenupermis')->insertGetId(
                [
                    'idperfil' => $request->idperfil, 'idmenu' => $request->idmenu,
                    'idsubmenu' => $request->idsubmenu, 'tipopermiso' => $request->tipopermiso,
                    'notificaciones' => $request->notificaciones
                ]
            );
        } else {
            DB::table('mc_submenupermis')->where("idperfil", $request->idperfil)->where("idmenu", $request->idmenu)->where("idsubmenu", $request->idsubmenu)->update(['tipopermiso' => $request->tipopermiso, 'notificaciones' => $request->notificaciones]);
        }
        return $idU;
    }

    public function EditarPerfilEmpresa(Request $request)
    {
        ConnectDatabase($request->idempresa);
        $idp = $request->idperfil;
        DB::table('perfiles')->where("idperfil", $idp)->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->desc, 'status' => $request->status
        ]);
        return $idp;
    }

    public function PerfilesGen(Request $request)
    {

        $modulos = DB::connection("General")->select("SELECT * FROM mc1006");
        $datos = array(
            "perfiles" => $modulos,
        );
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    public function PermisosModPerfil(Request $request)
    {
        ConnectDatabase($request->idempresa);
        $IDPer = $request->idperfil;

        $permisos = DB::select("SELECT * FROM mc_modpermis WHERE idperfil='$IDPer'");
        $datos = $permisos;
        return $datos;
    }

    function PermisosMenusPerfil(Request $request)
    {
        $idempresa = $request->idempresa;
        $idModulo = $request->idmodulo;
        $IDPer = $request->idperfil;
        ConnectDatabase($idempresa);

        $permisos = DB::select("SELECT u.* FROM mc_menupermis u WHERE u.idmodulo='$idModulo' and u.idperfil='$IDPer' ORDER BY u.idmodulo DESC");

        $datos = $permisos;
        return $datos;
    }

    function PermisoSubMenusPerfil(Request $request)
    {
        $idempresa = $request->idempresa;
        $idMenu = $request->idmenu;
        $IDPer = $request->idperfil;
        ConnectDatabase($idempresa);

        $permisos = DB::select("SELECT u.* FROM mc_submenupermis u WHERE u.idmenu='$idMenu' and u.idperfil='$IDPer' ORDER BY u.idmenu DESC");

        $datos = $permisos;
        return $datos;
    }




    //---------------RECEPCION POR LOTES------------------------//

    function ConsultarLotes(Request $request)
    {
        $idempresa = $request->idempresa;
        ConnectDatabase($idempresa);
        $idmenu = $request->idmenu;
        $idsubmenu = $request->idsubmenu;

        $tipos = DB::select("SELECT claveplantilla FROM mc_rubros WHERE idmenu = $idmenu And idsubmenu = $idsubmenu");

        $filtro = "";
        for ($i = 0; $i < count($tipos); $i++) {
            $filtro = $filtro . " l.tipo = " . $tipos[$i]->claveplantilla . " OR ";
        }
        $filtro = substr($filtro, 0, -4);

        if (count($tipos) > 0) {

            $lotes = DB::select("SELECT l.*,SUM(IF(d.error>0,d.error,0)) AS cError, d.sucursal FROM mc_lotes l LEFT JOIN mc_lotesdocto d ON l.id = d.idlote WHERE l.totalregistros <> 0 AND l.totalcargados <> 0 And d.estatus <> 2 And " . $filtro . " GROUP BY l.id ORDER BY l.id DESC");


            for ($i = 0; $i < count($lotes); $i++) {

                $idlote = $lotes[$i]->id;

                $procesados = DB::select("SELECT id FROM mc_lotesdocto WHERE idlote = $idlote And estatus = 1");

                $lotes[$i]->procesados = count($procesados);

                $idusuario = $lotes[$i]->usuario;

                $datosuser = DB::connection("General")->select("SELECT nombre FROM mc1001 WHERE idusuario = $idusuario");

                $lotes[$i]->usuario = $datosuser[0]->nombre;

                $clave = $lotes[$i]->tipo;

                $tipo = DB::connection("General")->select("SELECT tipo FROM mc1011 WHERE clave = '$clave'");

                $lotes[$i]->tipodet = $tipo[0]->tipo;

                //$suc = DB::select("SELECT sucursal FROM mc_lotesdocto WHERE idlote = $idlote");

                //$lotes[$i]->sucursal = $suc[0]->sucursal;

            }
        } else {
            $lotes = [];
        }

        return $lotes;
    }

    function ConsultarDoctos(Request $request)
    {
        $idempresa = $request->idempresa;
        $idlote = $request->idlote;

        ConnectDatabase($idempresa);

        $doctos = DB::select("SELECT * FROM mc_lotesdocto WHERE idlote = $idlote");
        for ($i = 0; $i < count($doctos); $i++) {
            $codigocon = $doctos[$i]->concepto;
            $nombrec = DB::select("SELECT nombreconcepto FROM mc_catconceptos WHERE codigoconcepto = '$codigocon'");
            if (!empty($nombrec)) {
                $doctos[$i]->concepto = $nombrec[0]->nombreconcepto;
            }
        }
        /*for ($i=0; $i < count($datos); $i++) { 
            $clave = $datos[$i]->codigo;
            $clave = $clave.substr(8,1);
            $tipo = DB::connection("General")->select("SELECT tipo FROM mc1011 WHERE clave = '$clave'");
            $datos[$i]->tipo = $tipo[0]->tipo;
        } */


        return $doctos;
    }

    function ConsultarMovtosLote(Request $request)
    {
        $idempresa = $request->idempresa;
        $id = $request->id;

        ConnectDatabase($idempresa);

        $movtos = DB::select("SELECT m.* FROM mc_lotesdocto d, mc_lotesmovtos m WHERE d.id = m.iddocto AND d.estatus <> 2 AND m.idlote = $id");


        for ($i = 0; $i < count($movtos); $i++) {
            $codigoprod = $movtos[$i]->producto;
            $doctos = DB::select("SELECT nombreprod FROM mc_catproductos WHERE codigoprod = '$codigoprod'");
            if (!empty($doctos)) {
                $movtos[$i]->producto = $doctos[0]->nombreprod;
            }
        }

        return $movtos;
    }

    function ConsultarMovtosDocto(Request $request)
    {
        $idempresa = $request->idempresa;
        $id = $request->id;
        ConnectDatabase($idempresa);

        $movtos = DB::select("SELECT d.folio, d.serie, d.sucursal, m.* FROM mc_lotesdocto d, mc_lotesmovtos m WHERE d.id = m.iddocto AND d.estatus <> 2 AND m.iddocto = $id");

        for ($i = 0; $i < count($movtos); $i++) {
            $codigoprod = $movtos[$i]->producto;
            $doctos = DB::select("SELECT nombreprod FROM mc_catproductos WHERE codigoprod = '$codigoprod'");
            if (!empty($doctos)) {
                $movtos[$i]->producto = $doctos[0]->nombreprod;
            }
        }

        return $movtos;
    }

    function EliminarLote(Request $request)
    {
        $idempresa = $request->idempresa;
        $idlote = $request->idlote;

        ConnectDatabase($idempresa);

        $doctos = DB::select("SELECT * FROM mc_lotesdocto WHERE idlote = $idlote And estatus = 1");

        if (empty($doctos)) {
            DB::table('mc_lotes')->where("id", $idlote)->delete();
            DB::table('mc_lotesdocto')->where("idlote", $idlote)->delete();
            DB::table('mc_lotesmovtos')->where("idlote", $idlote)->delete();
        } else {
        }

        return $doctos;
    }

    function EliminarDocto(Request $request)
    {
        $idempresa = $request->idempresa;
        $iddocto = $request->iddocto;

        ConnectDatabase($idempresa);

        $docto = DB::select("SELECT * FROM mc_lotesdocto WHERE id = $iddocto");

        if ($docto[0]->estatus == 0) {

            $idlote = $docto[0]->idlote;
            DB::table('mc_lotesdocto')->where("id", $iddocto)->update(['estatus' => 2]);

            $doctos = DB::select("SELECT COUNT(id) AS doctos FROM mc_lotesdocto WHERE idlote = '$idlote' AND estatus <> 2");

            //$cargados = DB::select("SELECT totalregistros FROM mc_lotes WHERE id = '$idlote'");

            if ($doctos[0]->doctos > 0) {
                DB::table('mc_lotes')->where("id", $idlote)->update(['totalcargados' => $doctos[0]->doctos]);
            } else {
                DB::table('mc_lotes')->where("id", $idlote)->delete();
                DB::table('mc_lotesdocto')->where("idlote", $idlote)->delete();
                DB::table('mc_lotesmovtos')->where("idlote", $idlote)->delete();
            }


            $docto = DB::select("SELECT * FROM mc_lotesdocto WHERE id = $iddocto And estatus <> 2");
        }

        return $docto;
    }

    function VerificarLote(Request $request)
    {
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $tipodocto = $request->tipodocto;

        //$a  = isset($request->conexion);

        ConnectDatabase($idempresa);

        $lote = $request->movtos;

        $num_movtos = count($request->movtos);


        for ($i = 0; $i < $num_movtos; $i++) {
            $fecha = $request->movtos[$i]['fecha'];
            $fecha = str_replace("-", "", $fecha);


            if ($request->movtos[$i]['idconce'] == 3) {
                $folio = $request->movtos[$i]['folio'];
                //$estatus = $request->movtos[$i];
                $codigo = $fecha . $tipodocto . $folio;
            } else if ($request->movtos[$i]['idconce'] == 2) {
                $unidad = $request->movtos[$i]['unidad'];
                //$estatus = $request->movtos[$i];
                $litros = $request->movtos[$i]['cantidad'];
                $codigo = $fecha . $tipodocto . $litros . $unidad;
            } else if ($request->movtos[$i]['idconce'] == 4) {
                $cantidad = $request->movtos[$i]['cantidad'];
                $unidad = $request->movtos[$i]['unidad'];
                $precio = $request->movtos[$i]['total'];
                $codigo = $fecha . $tipodocto . $cantidad . $unidad . $precio;
            } else if ($request->movtos[$i]['idconce'] == 5) {
                $cantidad = $request->movtos[$i]['cantidad'];
                $unidad = $request->movtos[$i]['unidad'];
                //$estatus = $request->movtos[$i];                
                $codigo = $fecha . $tipodocto . $cantidad . $unidad;
            }


            $result = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigo' And error <> 1");


            if (empty($result)) {
                $lote[$i]['estatus'] = "False";
            } else {
                $lote[$i]['estatus'] = "True";
                $lote[$i]['iddocto'] = $result[0]->id;
                $lote[$i]['procesado'] = $result[0]->estatus;
            }

            $lote[$i]['codigo'] = $codigo;
        }
        return $lote;
    }




    function ConvertirValor($variable)
    {
        $flag = true;
        $var = $variable;
        $permitidos = "0123456789.,$";
        for ($i = 0; $i < strlen($variable); $i++) {
            if (strpos($permitidos, substr($variable, $i, 1)) === false) {
                $flag = false;
                break;
            }
        }
        if ($flag == true) {
            $var = floatval($var);
        }
        return $var;
    }

    function ValidarDatos($documentos, $tipodocto, $num_movtos)
    {

        $val4 = "";
        $val5 = "";
        $val6 = "";
        $val7 = "";
        $val8 = "";
        $val9 = "";
        $val10 = "";
        $val11 = "";
        $val12 = "";
        $val13 = "";
        $codigolote = "";
        for ($i = 0; $i < $num_movtos; $i++) {
            $error = "";
            $fecha = $documentos[$i]["fecha"];
            $concepto = $documentos[$i]["codigoconcepto"];
            $rfc = $this->ConvertirValor($documentos[$i]["rfc"]);
            $producto = $documentos[$i]["codigoproducto"];
            $suc = $documentos[$i]["sucursal"];

            if ($tipodocto == 3) {
                $val4 = $this->ConvertirValor($documentos[$i]["folio"]);
                $val5 = $this->ConvertirValor($documentos[$i]["serie"]);
                $val6 = $this->ConvertirValor($documentos[$i]["subtotal"]);
                $val7 = $this->ConvertirValor($documentos[$i]["descuento"]);
                $val8 = $this->ConvertirValor($documentos[$i]["iva"]);
                $val9 = $this->ConvertirValor($documentos[$i]["total"]);
                $codigolote = str_replace("-", "", $fecha) . $tipodocto . $val4;
            } else if ($tipodocto == 2) {
                $val9 = $this->ConvertirValor($documentos[$i]["total"]);
                $val10 = $this->ConvertirValor($documentos[$i]["almacen"]);
                $val11 = $this->ConvertirValor($documentos[$i]["cantidad"]);
                $val12 = $this->ConvertirValor($documentos[$i]["unidad"]);
                $val13 = $this->ConvertirValor($documentos[$i]["horometro"]);
                $val14 = $this->ConvertirValor($documentos[$i]["kilometro"]);
                $codigolote = str_replace("-", "", $fecha) . $tipodocto . $val11 . $val12;
            } else if ($tipodocto == 4) {
                $val9 = $this->ConvertirValor($documentos[$i]["total"]);
                $val10 = $this->ConvertirValor($documentos[$i]["almacen"]);
                $val11 = $this->ConvertirValor($documentos[$i]["cantidad"]);
                $val12 = $this->ConvertirValor($documentos[$i]["unidad"]);
                $codigolote = str_replace("-", "", $fecha) . $tipodocto . $val11 . $val12 . $val9;
            } else if ($tipodocto == 5) {
                $val10 = $this->ConvertirValor($documentos[$i]["almacen"]);
                $val11 = $this->ConvertirValor($documentos[$i]["cantidad"]);
                $val12 = $this->ConvertirValor($documentos[$i]["unidad"]);
                $codigolote = str_replace("-", "", $fecha) . $tipodocto . $val11 . $val12;
            }

            $valores = explode('-', $fecha);
            if (count($valores) == 3 && checkdate($valores[1], $valores[2], $valores[0])) {
                if (is_string($rfc)) {

                    if ($tipodocto == 3) {
                        if ($val4 != "" && is_float($val4)) {
                            if ($val6 != "" && is_float($val6) && (is_float($val7) || $val6 == "") && (is_float($val8) || $val8 == "") && ($val9 != "" || is_float($val9))) {
                            } else {
                                if ($val6 == "" || !is_float($val6)) {
                                    $error = "Neto incorrecto o vacio.";
                                } else if (!is_float($val7)) {
                                    $error = "Desc. Incorrecto.";
                                } else if (!is_float($val8)) {
                                    $error = "IVA incorrecto.";
                                } else if ($val9 == "" || !is_float($val9)) {
                                    $error = "Total incorrecto o vacio.";
                                }
                            }
                        } else {
                            $error = "Folio Incorrecto";
                        }
                    } else if ($tipodocto == 2) {
                        if ($val10 != "") {
                            if ($val11 != "" || is_float($val11)) {
                                if ($val12 != "") {
                                    if ($val13 == 0 || is_float($val13)) {
                                        if (($val14 != "" || $val14 == 0) && is_float($val14)) {
                                        } else {
                                            $error = "Error Kilometros.";
                                        }
                                    } else {
                                        $error = "Error Horometro.";
                                    }
                                } else {
                                    $error = "Campo vacio(Unidad).";
                                }
                            } else {
                                $error = "Error en Litros.";
                            }
                        } else {
                            $error = "Error con el Almacen.";
                        }
                    } else if ($tipodocto == 4 || $tipodocto == 5) {
                        if ($val10 != "" && is_float($val10)) {
                            if ($val11 = !"" && is_float($val11)) {
                                if ($val12 != "") {
                                    if ($tipodocto == 4) {
                                        if ($val9 = !"" && is_float($val9)) {
                                        } else {
                                            $error = "Error con el precio.";
                                        }
                                    }
                                } else {
                                    $error = "Campo vacio(Unidad).";
                                }
                            } else {
                                $error = "Error con la cantidad.";
                            }
                        } else {
                            $error = "Error con el Almacen.";
                        }
                    }/*else if($tipodocto == 5){
                        if($val10 != "" && is_float($val10)){
                            if($val11 =! "" && is_float($val11)){
                                if($val12 != ""){

                                }else{
                                    $error = "Campo vacio(Unidad).";
                                }
                            }else{
                                $error = "Error con la cantidad.";       
                            }
                        }else{
                            $error = "Error con el Almacen.";
                        }
                    }*/
                } else {
                    $error = "Error con el RFC";
                }
            } else {
                $error = "Fecha Incorrecta";
            }

            if ($error != "") {
                $documentos[$i]['error'] = 1;
            } else {
                $documentos[$i]['error'] = 0;
            }
            $documentos[$i]['error_det'] = $error;
        } //FIN FOR
        return $documentos;
    }

    /*    //function RegistrarLote(Request $request){
    function RegistrarLote($idempresa, $idusuario, $tipodocto, $sucursal){
        
        //$idempresa = $request->idempresa;
        //$idusuario = $request->idusuario;        
        ConnectDatabase($idempresa);

        //$tipodocto = $request->tipodocto;
        //$sucursal = $request->sucursal;
        //$fechac = now();
        $fechac = date("Ymd"); 
        $codigolote = $fechac.$idusuario.$tipodocto.$sucursal;

        //$idlote = DB::select("SELECT id FROM mc_lotes WHERE tipo = 0 LIMIT 1");
        $idlote = DB::select("SELECT id FROM mc_lotes WHERE codigolote = '$codigolote'");

        if(empty($idlote)){
            $lote = DB::table('mc_lotes')->insertGetId(['fechadecarga' => $fechac, 'codigolote' => $codigolote, 'usuario' => $idusuario, 'tipo' => 0]);
            $idlote = DB::select("SELECT id FROM mc_lotes WHERE codigolote = '$codigolote'");
        }
        return $idlote[0]->id; 
    }    */

    function LoteCargado(Request $request)
    {

        $tipoconexion = isset($request->conexion) ? $request->conexion : 0; // 1=Web 0=Otros(Externos).
        $tipodocto = $request->tipodocto;

        $movimientos = $request->movimientos;

        if ($tipoconexion == 1) {


            $idempresa = $request->idempresa;
            $idusuario = $request->idusuario;
            $span = $request->span;
            $documentos = $request->documentos;
            //$codigo = $request->codigo;

            ConnectDatabase($idempresa);

            $num_doctos = count($documentos);
            $num_movtos = count($movimientos);

            $flag = 0;
            $val4 = "";
            $val5 = "";
            $val6 = "";
            $val7 = "";
            $val8 = "";
            $val9 = "";
            $val10 = "";
            $val11 = "";
            $val12 = "";
            $val13 = "";
            $val14 = "";

            $codigolote = "";
            $k = 0;

            $documentos = $this->ValidarDatos($documentos, $tipodocto, $num_doctos);

            for ($i = 0; $i < $num_doctos; $i++) {

                $fecha = $documentos[$i]["fecha"];
                $codigoconcepto = $documentos[$i]["codigoconcepto"];
                $concepto = $documentos[$i]["nombreconcepto"];
                $rfc = $documentos[$i]["rfc"];
                $razonsocial = $documentos[$i]["razonsocial"];
                $codigoproducto = $documentos[$i]["codigoproducto"];
                $producto = $documentos[$i]["nombreproducto"];
                $suc = $documentos[$i]["sucursal"];

                //$idlote = $this->RegistrarLote($idempresa, $idusuario, $tipodocto, $suc);
                $idlote = (new ConsumoController)->RegistrarLote($idempresa, $idusuario, $tipodocto, $suc);

                if ($tipodocto == 3) {
                    $val4 = $documentos[$i]["folio"];
                    $val5 = $documentos[$i]["serie"];
                    $val6 = $documentos[$i]["subtotal"];
                    $val7 = $documentos[$i]["descuento"];
                    $val8 = $documentos[$i]["iva"];
                    $val9 = $documentos[$i]["total"];
                    $codigolote = str_replace("-", "", $fecha) . $tipodocto . $val4;
                } else if ($tipodocto == 2) {
                    $val9 = $documentos[$i]["total"];
                    $val10 = $documentos[$i]["almacen"];
                    $val11 = $documentos[$i]["cantidad"];
                    $val12 = $documentos[$i]["unidad"];
                    $val13 = $documentos[$i]["horometro"];
                    $val14 = $documentos[$i]["kilometro"];
                    $codigolote = str_replace("-", "", $fecha) . $tipodocto . $val11 . $val12;
                } else if ($tipodocto == 4 || $tipodocto == 5) {
                    $val10 = $documentos[$i]["almacen"];
                    $val11 = $documentos[$i]["cantidad"];
                    $val12 = $documentos[$i]["unidad"];
                    if ($tipodocto == 4) {
                        $val9 = $documentos[$i]["total"];
                        $codigolote = str_replace("-", "", $fecha) . $tipodocto . $val11 . $val12 . $val9;
                    } else {
                        $codigolote = str_replace("-", "", $fecha) . $tipodocto . $val11 . $val12;
                    }
                }

                $error = $documentos[$i]["error"];
                $error_det = $documentos[$i]["error_det"];

                $documentos[$i]["estatus"] = 0;

                if ($documentos[$i]["codigo"] != "") {

                    $codigo = $documentos[$i]["codigo"];
                    $lote = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigo'");

                    if (empty($lote)) {

                        DB::table('mc_lotesdocto')->insertGetId(['idlote' => $idlote, 'codigo' => $codigo, 'sucursal' => $suc, 'concepto' => $codigoconcepto, 'proveedor' => $rfc, 'fecha' => $fecha, 'folio' => $val4, 'serie' => $val5, 'subtotal' => $val6, 'descuento' => $val7, 'iva' => $val8, 'total' => $val9, 'campoextra1' => $val11, 'campoextra2' => $val10, 'error' => $error,  'detalle_error' => $error_det]);

                        $lote = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigo'");

                        $documentos[$i]["estatus"] = 1; //Nuevo Registro

                        $this->RegistrarMovtos2($idempresa, $idusuario, $idlote, $lote[0]->id, $tipodocto, $codigo, $movimientos, $num_movtos);
                    } else {
                        $id = $lote[0]->id;
                        $movtos = DB::select("SELECT * FROM mc_lotesmovtos WHERE iddocto = $id");

                        DB::table('mc_lotesdocto')->where("id", $lote[0]->id)->update(['idlote' => $lote[0]->idlote, 'codigo' => $codigo, 'sucursal' => $suc, 'concepto' => $codigoconcepto, 'proveedor' => $rfc, 'fecha' => $fecha, 'folio' => $val4, 'serie' => $val5, 'subtotal' => $val6, 'descuento' => $val7, 'iva' => $val8, 'total' => $val9, 'campoextra1' => $val11, 'campoextra2' => $val10, 'error' => $error,  'detalle_error' => $error_det]);

                        if ($error == 0) {
                            DB::table('mc_lotesdocto')->where("id", $lote[0]->id)->update(['error' => $error, 'detalle_error' => $error_det]);
                        }

                        $lote = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigo'");

                        $documentos[$i]["estatus"] =  2; //Actualizado

                        $idlote = $lote[0]->idlote;

                        $this->RegistrarMovtos2($idempresa, $idusuario, $idlote, $lote[0]->id, $tipodocto, $codigo, $movimientos, $num_movtos);
                    }
                    //$this->UpdateLote($idempresa, $tipodocto, $idlote);
                    (new ConsumoController)->UpdateLote($idempresa, $tipodocto, $idlote);
                }
            }
            return $documentos;
        } else {
            $RFCEmpresa = $request->rfcempresa;
            $Usuario = $request->usuario;
            $Pwd = $request->pwd;
            //$documentos[0] = LoteCargadoExt($RFCEmpresa, $Usuario, $Pwd, $tipodocto, $documentos);

            $result = (new ConsumoController)->LoteCargadoExt($RFCEmpresa, $Usuario, $Pwd, $tipodocto, $movimientos);
            return $result;
        }


        //return $lote;          



    }

    /*function UpdateLote($idempresa, $tipodocto, $idlote){     

        ConnectDatabase($idempresa);

        $n = DB::select("SELECT count(id) AS reg FROM mc_lotesdocto WHERE idlote = '$idlote' And error <> 1");
        $totalregistros = DB::select("SELECT count(id) AS totalreg FROM mc_lotesdocto WHERE idlote = '$idlote'");
        
        DB::table('mc_lotes')->where("id", $idlote)->update(['tipo' => $tipodocto, 'totalregistros' => $totalregistros[0]->totalreg, 'totalcargados' => $n[0]->reg]);

    }*/

    function RegistrarMovtos2($idempresa, $idusuario, $idlote, $iddocto, $tipodocto, $codigo, $movtos, $num_movtos)
    {

        ConnectDatabase($idempresa);

        $cont = 0;

        for ($i = 0; $i < $num_movtos; $i++) {

            $fecha = $movtos[$i]["fecha"];
            $codigoproducto = $movtos[$i]["codigoproducto"];

            if ($tipodocto == 3) {
                $folio = $movtos[$i]["folio"];
                $cantidad = $movtos[$i]["cantidad"];
                $subtotal = floatval($movtos[$i]["subtotal"]);
                $descuento = floatval($movtos[$i]["descuento"]);
                $iva = floatval($movtos[$i]["iva"]);
                $total = floatval($movtos[$i]["total"]);
                $codigotemp = str_replace("-", "", $fecha) . $tipodocto . $folio;


                if ($codigo == $codigotemp) { // tipo 3
                    $iddocumento = DB::table('mc_lotesmovtos')->insertGetId(['idlote' => $idlote, 'iddocto' => $iddocto, 'fechamov' => $fecha, 'producto' => $codigoproducto, 'cantidad' => $cantidad, 'subtotal' => $subtotal, 'descuento' => $descuento, 'iva' => $iva, 'total' => $total]);
                }
            } elseif ($tipodocto == 2) {
                $cantidad = $movtos[$i]['cantidad'];
                $almacen = $movtos[$i]['almacen'];
                $kilometros = $movtos[$i]['kilometro'];
                $horometros = $movtos[$i]['horometro'];
                $unidad = $movtos[$i]['unidad'];
                $total = floatval($movtos[$i]['total']);
                $codigotemp = str_replace("-", "", $fecha) . $tipodocto . $cantidad . $unidad;


                if ($codigo == $codigotemp) { // tipo 2
                    $docto = DB::select("SELECT * FROM mc_lotesmovtos WHERE idlote = '$idlote' And iddocto =  '$iddocto' And fechamov = '$fecha' And total = '$total'");

                    if (empty($docto)) {
                        $iddocumento = DB::table('mc_lotesmovtos')->insertGetId(['idlote' => $idlote, 'iddocto' => $iddocto, 'fechamov' => $fecha, 'producto' => $codigoproducto, 'cantidad' => $cantidad, 'almacen' => $almacen, 'kilometros' => $kilometros, 'horometro' => $horometros, 'unidad' => $unidad, 'total' => $total]);
                    } else {
                        $iddocumento = DB::table('mc_lotesmovtos')->where("id", $docto[0]->id)->update(['producto' => $codigoproducto, 'almacen' => $almacen, 'kilometros' => $kilometros, 'horometro' => $horometros, 'total' => $total]);
                    }
                }
            } elseif ($tipodocto == 4 || $tipodocto == 5) {
                $cantidad = $movtos[$i]["cantidad"];
                $almacen = $movtos[$i]['almacen'];
                $unidad = $movtos[$i]['unidad'];

                if ($tipodocto == 4) {
                    $precio = $movtos[$i]['total'];
                    $codigotemp = str_replace("-", "", $fecha) . $tipodocto . $cantidad . $unidad . $precio;
                    if ($codigo == $codigotemp) {
                        $movto = DB::select("SELECT * FROM mc_lotesmovtos WHERE idlote = '$idlote' And iddocto = '$iddocto' And fechamov = '$fecha' And total = '$precio'");
                        if (empty($movto)) {
                            DB::table('mc_lotesmovtos')->insertGetId(['idlote' => $idlote, 'iddocto' => $iddocto, 'fechamov' => $fecha, 'producto' => $codigoproducto, 'almacen' => $almacen, 'cantidad' => $cantidad, 'unidad' => $unidad, 'total' => $precio]);
                        }
                    }
                } else {
                    $codigotemp = str_replace("-", "", $fecha) . $tipodocto . $cantidad . $unidad;

                    if ($codigo == $codigotemp) {
                        $movto = DB::select("SELECT * FROM mc_lotesmovtos WHERE idlote = '$idlote' And iddocto = '$iddocto' And fechamov = '$fecha'");
                        if (empty($movto)) {
                            DB::table('mc_lotesmovtos')->insertGetId(['idlote' => $idlote, 'iddocto' => $iddocto, 'fechamov' => $fecha, 'producto' => $codigoproducto, 'almacen' => $almacen, 'cantidad' => $cantidad, 'unidad' => $unidad]);
                        }
                    }
                }
            }
        }

        //return $iddocumento; 
    }


    function Paginador(Request $request)
    {
        ConnectDatabase($request->idempresa);
        $inicio = $request->iniciar;
        $lotespagina = $request->lotespag;

        $tabla = $request->tabla; //Contiene el id del submenu

        if ($tabla == 0) { //Recepcion por lotes

            $lotes = DB::select("SELECT l.*,SUM(IF(d.error>0,d.error,0)) AS cError, d.sucursal FROM mc_lotes l LEFT JOIN mc_lotesdocto d ON l.id = d.idlote WHERE l.totalregistros <> 0 AND l.totalcargados <> 0 And d.estatus <> 2 GROUP BY l.id ORDER BY l.id DESC LIMIT $inicio, $lotespagina");

            for ($i = 0; $i < count($lotes); $i++) {

                $idlote = $lotes[$i]->id;


                $procesados = DB::select("SELECT id FROM mc_lotesdocto WHERE idlote = $idlote And estatus = 1");

                $lotes[$i]->procesados = count($procesados);

                $idusuario = $lotes[$i]->usuario;

                $datosuser = DB::connection("General")->select("SELECT nombre FROM mc1001 WHERE idusuario = $idusuario");

                $lotes[$i]->usuario = $datosuser[0]->nombre;

                $clave = $lotes[$i]->tipo;

                $tipo = DB::connection("General")->select("SELECT tipo FROM mc1011 WHERE clave = '$clave'");

                $lotes[$i]->tipodet = $tipo[0]->tipo;
            }

            return $lotes;
        } else if ($tabla == 16) { //Expedientes Digitales

        }
    }

    function ChecarCatalogos(Request $request)
    {
        $datos = $request->array;
        ConnectDatabase($request->idempresa);

        $count = count($datos);

        $dato[1]['status'] = 0;

        $RFCGenerico = "XAXX010101000";


        for ($i = 0; $i < $count; $i++) {
            $codprod = $datos[$i]['codigoproducto'];
            $codigocliprov = $datos[$i]['codigocliprov'];
            $rfc = $datos[$i]['rfc'];
            $codconcepto = $datos[$i]['codigoconcepto'];
            $razonsocial = $datos[$i]['razonsocial'];


            $suc = $datos[$i]['sucursal'];
            $tipodocto = $datos[$i]['idconce'];

            $datos[$i]['productoreg'] = 0;
            $datos[$i]['clienprovreg'] = 0;
            $datos[$i]['conceptoreg'] = 0;
            $datos[$i]['sucursalreg'] = 0;

            switch ($tipodocto) { //Tipo de Cliente/Proveedor
                case 2: //DIESEL
                case 4:
                case 5:
                    $tipocli = 2; //Proveedor
                    break;
                case 3: //REMISION
                    $tipocli = 1; //Cliente
                    break;
            }


            $producto = DB::select("SELECT * FROM mc_catproductos WHERE codigoprod = '$codprod'");
            if (empty($producto)) {
                $dato[1]['status'] = 1;
                $datos[$i]['productoreg'] = 1;
            } else {
                if (is_null($datos[$i]['nombreproducto'])) {
                    $datos[$i]['nombreproducto'] = $producto[0]->nombreprod;
                }
            }

            if ($rfc == $RFCGenerico) {
                $proveedor = DB::select("SELECT * FROM mc_catclienprov WHERE codigoc = '$codigocliprov' And (tipocli = '$tipocli' OR tipocli = 3)");
            } else {
                $proveedor = DB::select("SELECT * FROM mc_catclienprov WHERE rfc = '$rfc' And (tipocli = '$tipocli' OR tipocli = 3)");
            }
            if (empty($proveedor)) {
                $dato[1]['status'] = 1;
                $datos[$i]['clienprovreg'] = 1;
            } else {
            }

            //$concepto = DB::select("SELECT * FROM mc_catconceptos WHERE codigoconcepto = '$codconcepto'");
            $concepto = DB::select("SELECT * FROM mc_rubros WHERE clave = '$codconcepto'");
            if (empty($concepto)) {
                $dato[1]['status'] = 1;
                $datos[$i]['conceptoreg'] = 1;
            } else {
                if (is_null($datos[$i]['nombreconcepto'])) {
                    $datos[$i]['nombreconcepto'] = $concepto[0]->nombre;
                    $datos[$i]['idmenu'] = $concepto[0]->idmenu;
                    $datos[$i]['idsubmenu'] = $concepto[0]->idsubmenu;
                    $datos[$i]['clave'] = $concepto[0]->claveplantilla;
                }
            }

            $sucursal = DB::select("SELECT * FROM mc_catsucursales WHERE sucursal = '$suc'");
            if (empty($sucursal)) {
                $dato[1]['status'] = 1;
                $datos[$i]['sucursalreg'] = 1;
            } else {
            }
        }

        $dato[0] = $datos;

        return $dato;
    }

    function RegistrarElemento(Request $request)
    {
        $datos = $request->datos;
        $tipo = $request->tipo;
        ConnectDatabase($request->idempresa);
        $num_registros = count($datos);

        for ($i = 0; $i < $num_registros; $i++) {

            $campo1 = strtoupper($datos[$i][0]);
            $campo2 = strtoupper($datos[$i][1]);
            switch ($datos[$i][2]) { //Tipo de Cliente/Proveedor
                case 2: //DIESEL
                case 4:
                case 5:
                    $campo3 = 2; //Proveedor
                    break;
                case 3: //REMISION
                    $campo3 = 1; //Cliente
                    break;
            }
            $elemento = $datos[$i][4]; //Elemento pendiente

            //if($elemento == $campo1){ 
            //    $campo4 = $campo1; 
            //    echo "Elemento igual al campo1";
            //}else{ 
            //    $campo4 = $campo1; 
            //    $campo1 = $elemento; 
            //    echo "Asignamos Elemento a campo1";
            //}


            if ($tipo == "productos") {
                $ele = DB::select("SELECT * FROM mc_catproductos WHERE codigoprod = '$campo1' OR codigoadw = '$campo1'");
                if (empty($ele)) {
                    DB::table('mc_catproductos')->insertGetId(['codigoprod' => $elemento, 'nombreprod' => $campo2, 'codigoadw' => $campo1, 'nombreadw' => $campo2, 'fechaalta' => now()]);

                    $datos[$i]['registrado'] = 1;
                } else {
                    $datos[$i]['registrado'] = 0;
                }
            } else if ($tipo == "clientesproveedores") {
                if ($campo1 == "XAXX010101000") {
                    $codigoclienteproveedor = strtoupper($datos[$i][5]);
                    //$codigoclienteproveedor = strtoupper($elemento);
                    $ele = DB::select("SELECT * FROM mc_catclienprov WHERE codigoc = '$codigoclienteproveedor'");
                } else {
                    $codigoclienteproveedor = ($datos[$i][5] == "" ? $campo1 : strtoupper($datos[$i][5]));
                    $ele = DB::select("SELECT * FROM mc_catclienprov WHERE rfc = '$campo1'");
                }

                if (empty($ele)) {
                    DB::table('mc_catclienprov')->insertGetId(['codigoc' => $codigoclienteproveedor, 'rfc' => $campo1, 'razonsocial' => $campo2, 'tipocli' => $campo3]);

                    $datos[$i]['registrado'] = 1;
                } else {
                    if ($ele[0]->tipocli == $campo3) {
                        $datos[$i]['registrado'] = 0;
                    } else {
                        if ($ele[0]->tipocli != 3) {
                            if ($ele[0]->razonsocial == $campo2 || $ele[0]->codigoc == $codigoclienteproveedor) {
                                DB::table('mc_catclienprov')->where("id", $ele[0]->id)->update(['tipocli' => 3, 'razonsocial' => $campo2]);
                                $datos[$i]['registrado'] = 1;
                            } else {
                                $datos[$i]['registrado'] = 0;
                            }
                        } else {
                            $datos[$i]['registrado'] = 0;
                        }
                    }
                }
            } else if ($tipo == "conceptos") {
                $ele = DB::select("SELECT * FROM mc_catconceptos WHERE codigoconcepto = '$campo1' OR codigoadw = '$campo1'");
                if (empty($ele)) {
                    DB::table('mc_catconceptos')->insertGetId(['codigoconcepto' => $elemento, 'nombreconcepto' => $campo2, 'codigoadw' => $campo1, 'nombreadw' => $campo2]);

                    $datos[$i]['registrado'] = 1;
                } else {

                    $datos[$i]['registrado'] = 0;
                }
            } else if ($tipo == "sucursales") {
                $ele = DB::select("SELECT * FROM mc_catsucursales WHERE sucursal = '$campo1'");
                if (empty($ele)) {
                    DB::table('mc_catsucursales')->insertGetId(['sucursal' => $campo1]);

                    $datos[$i]['registrado'] = 1;
                } else {

                    $datos[$i]['registrado'] = 0;
                }
            }
        }

        return $datos;
        //return $respuesta;

    }

    function VerificarClave(Request $request)
    {
        ConnectDatabase($request->idempresa);
        $clave = $request->clave;

        if (isset($request->idmenu)) {
            $idmenu = $request->idmenu;
        } else {
            $idmenu = 6;
        }

        $idsubmenu = $request->idsubmenu;

        $rubros = DB::select("SELECT * FROM mc_rubros WHERE claveplantilla = $clave AND idmenu = $idmenu AND idsubmenu = $idsubmenu");

        return $rubros;
    }







    //-----------------//
    public function AlmacenCargado(Request $request)
    {
        //$datos = $request->datos;
        $archivos = $request->file();
        $numarchivos = count($archivos);
        $autenticacion = (new ConsumoController)->ValidarConexion($request->rfcempresa, $request->usuario, $request->pwd, 0, 2, $request->idmenu, $request->idsubmenu);

        $array["error"] = $autenticacion[0]["error"];

        if ($autenticacion[0]['error'] == 0) {

            ConnectDatabase($autenticacion[0]["idempresa"]);
            $idUsuario = $autenticacion[0]["idusuario"];
            $idmenu = $request->idmenu;
            $idsubmenu = $request->idsubmenu;
            $empresa = $request->rfcempresa;
            $fechadocto = $request->fechadocto;
            $sucursal = $request->sucursal;
            $observaciones = $request->observaciones;
            $result = DB::connection("General")->select("SELECT servidor_storage FROM mc0000");
            $servidor = $result[0]->servidor_storage;
            $u_storage = $autenticacion[0]["userstorage"];
            $p_storage = $autenticacion[0]["passstorage"];
            $result = DB::connection("General")->select("SELECT nombre_carpeta FROM mc1004 WHERE idmenu=$idmenu");
            $menu = $result[0]->nombre_carpeta;
            $result = DB::connection("General")->select("SELECT nombre_carpeta FROM mc1005 WHERE idsubmenu=$idsubmenu");
            $submenu = $result[0]->nombre_carpeta;

            $consecutivo = $this->SiguienteNumero($autenticacion[0]["idempresa"], $fechadocto, $idsubmenu);
            $countreg = $consecutivo;

            $cont = 0;

            foreach ($archivos as $key) {

                if (strlen($countreg) == 1) {
                    $consecutivo = "000" . $countreg;
                } elseif (strlen($countreg) == 2) {
                    $consecutivo = "00" . $countreg;
                } elseif (strlen($countreg) == 3) {
                    $consecutivo = "0" . $countreg;
                } else {
                    $consecutivo = $countreg;
                }

                $resultado = $this->SubirArchivosCloud($key->getClientOriginalName(), $key, $request->rfcempresa, $servidor, $u_storage, $p_storage, $menu, $submenu, $fechadocto, $consecutivo);

                if ($resultado["archivo"]["error"] == 0) {

                    $target_path = $resultado["archivo"]["target"];
                    $link = $this->GetLinkCloud($target_path, $servidor, $u_storage, $p_storage);

                    if ($link != "") {
                        $array2["archivos"][$cont] =  array(
                            "archivo" => $key->getClientOriginalName(),
                            "codigo" => $resultado["archivo"]["codigo"],
                            "link" => $link,
                            "status" => 0,
                            "detalle" => "¡Cargado Correctamente!"
                        );
                        $countreg = $countreg + 1;
                    } else {
                        $array2["archivos"][$cont] =  array(
                            "archivo" => $key->getClientOriginalName(),
                            "codigo" => $resultado["archivo"]["codigo"],
                            "link" => $link,
                            "status" => 2,
                            "detalle" => "¡Link no generado, error al subir!"
                        );
                    }
                } else {
                    $array2["archivos"][$cont] =  array(
                        "archivo" => $key->getClientOriginalName(),
                        "codigo" => "",
                        "link" => "",
                        "status" => 1,
                        "detalle" => "¡No se pudo subir el archivo!"
                    );
                }

                $cont = $cont + 1;
            }

            $carpIni = 'CRM/' . $autenticacion[0]["rfc"] . '/Entrada';
            $CarpSubM = $submenu;
            $CarpSubM = substr(strtoupper($CarpSubM), 0, 3);
            $string = explode("-", $fechadocto);
            $contador = 0;
            $now = date('Y-m-d h:i:s A');
            //VERIFICA SI NO EXISTE EL ARCHIVO
            $ArchivosV = (new ConsumoController)->VerificaArchivos($autenticacion[0]["idempresa"], $array2["archivos"], $fechadocto, $idmenu, $idsubmenu, $carpIni, $autenticacion[0]["userstorage"], $autenticacion[0]["passstorage"]);
            //REGISTRAR EN BASE DE DATOS LOS ARCHIVOS CARGADOS CORRECTAMENTE    
            $suc = DB::select("SELECT * FROM mc_catsucursales WHERE sucursal = '$sucursal'");
            if (!empty($suc)) {

                ConnectDatabase($autenticacion[0]["idempresa"]);

                $codigoalm = substr($string[0], 2) . $string[1] . $string[2] . $idUsuario . $CarpSubM . $sucursal;

                $reg = DB::select("SELECT * FROM mc_almdigital WHERE codigoalm = '$codigoalm'");

                $n = 0;
                if (empty($reg)) {
                    $idalm = DB::table('mc_almdigital')->insertGetId(['fechadecarga' => $now, 'fechadocto' => $fechadocto, 'codigoalm' => $codigoalm, 'idusuario' => $idUsuario, 'idmodulo' => $idsubmenu, 'idsucursal' => $suc[0]->idsucursal, 'observaciones' => $observaciones]);
                    while (isset($ArchivosV["archivos"][$contador])) {
                        $nomDoc = $ArchivosV["archivos"][$contador]["archivo"];
                        $codigodocumento = $ArchivosV["archivos"][$contador]["codigo"];
                        $link = $ArchivosV["archivos"][$contador]["link"];
                        if ($ArchivosV["archivos"][$contador]["status"] == 0) {

                            $ArchivosV["archivos"][$contador]["idarchivo"] = DB::table('mc_almdigital_det')->insertGetId(['idalmdigital' => $idalm, 'idsucursal' => $suc[0]->idsucursal, 'documento' => $nomDoc, 'codigodocumento' => $codigodocumento, 'download' => $link]);
                            $ArchivosV["archivos"][$contador]["idalmacen"] = $idalm;
                            $n = $n + 1;
                        }
                        $contador++;
                    }
                    if ($n > 0) {
                        DB::table('mc_almdigital')->where("id", $idalm)->update(['totalregistros' => $numarchivos, 'totalcargados' => $n]);
                    } else {
                        DB::table('mc_almdigital')->where("id", $idalm)->delete();
                    }
                } else {
                    $cont = 0;
                    while (isset($ArchivosV["archivos"][$contador])) {
                        $nomDoc = $ArchivosV["archivos"][$contador]["archivo"];
                        $codigodocumento = $ArchivosV["archivos"][$contador]["codigo"];
                        $link = $ArchivosV["archivos"][$contador]["link"];
                        if ($ArchivosV["archivos"][$contador]["status"] == 0) {
                            $ArchivosV["archivos"][$contador]["idarchivo"] = DB::table('mc_almdigital_det')->insertGetId(['idalmdigital' => $reg[0]->id, 'idsucursal' => $reg[0]->idsucursal, 'documento' => $nomDoc, 'codigodocumento' => $codigodocumento, 'download' => $link]);
                            $cont = $cont + 1;
                            $ArchivosV["archivos"][$contador]["idalmacen"] = $reg[0]->id;
                        }
                        $contador++;
                    }
                    if ($observaciones == "") {
                        $observaciones = $reg[0]->observaciones;
                    }
                    $idalm = $reg[0]->id;
                    $totalcargados = DB::select("SELECT COUNT(id) As tc FROM mc_almdigital_det WHERE idalmdigital = $idalm");
                    $totalregistros = $reg[0]->totalregistros + $cont;
                    DB::table('mc_almdigital')->where("id", $idalm)->update(['totalregistros' => $totalregistros, 'totalcargados' => $totalcargados[0]->tc, 'observaciones' => $observaciones]);
                }
                $array["archivos"] = $ArchivosV["archivos"];
            } else {
                $array["error"] = 21; //ERROR EN LA SUCURSAL, NO REGISTRADA
            }
        } else {
            $array["error"] = $autenticacion[0]["error"]; //ERROR DE AUTENTICACION
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function SubirArchivosCloud($archivo_name, $ruta_temp, $rfcempresa, $servidor, $usuario, $password, $menu, $submenu, $fechadocto, $consecutivo)
    {

        $mod = substr(strtoupper($submenu), 0, 3);
        $directorio = $rfcempresa . '/Entrada/' . $menu . '/' . $submenu;
        $string = explode("-", $fechadocto);
        $codfec = substr($string[0], 2) . $string[1];
        $codarchivo = $rfcempresa . "_" . $codfec . "_" . $mod . "_";

        $ch = curl_init();
        $file = $archivo_name;
        $filename = $codarchivo . $consecutivo;
        $source = $ruta_temp; //Obtenemos un nombre temporal del archivo        
        $type = explode(".", $file);
        $target_path = $directorio . '/' . $filename . "." . $type[count($type) - 1];

        $gestor = fopen($source, "r");
        $contenido = fread($gestor, filesize($source));

        curl_setopt_array(
            $ch,
            array(
                CURLOPT_URL => 'https://' . $servidor . '/remote.php/dav/files/' . $usuario . '/CRM/' . $target_path,
                CURLOPT_VERBOSE => 1,
                CURLOPT_USERPWD => $usuario . ':' . $password,
                CURLOPT_POSTFIELDS => $contenido,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_BINARYTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'PUT',
            )
        );
        $resp = curl_exec($ch);
        $error_no = curl_errno($ch);
        fclose($gestor);
        curl_close($ch);

        $array["archivo"]["target"] = $target_path;
        $array["archivo"]["codigo"] = $filename;
        $array["archivo"]["error"] = $error_no;

        return $array;
    }

    function GetLinkCloud($link, $server, $user, $pass)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://" . $user . ":" . $pass . "@" . $server . "/ocs/v2.php/apps/files_sharing/api/v1/shares");
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $pass);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "path=CRM/" . $link . "&shareType=3");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('OCS-APIRequest:true'));
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        $httpResponse = curl_exec($ch);
        $httpResponse = explode("\n\r\n", $httpResponse);
        $body = $httpResponse[1];
        $Respuesta = simplexml_load_string($body);
        $url = ((string) $Respuesta[0]->data->url);
        curl_close($ch);
        return $url;
    }

    function SiguienteNumero($idempresa, $fechadocto, $idmodulo)
    {
        ConnectDatabase($idempresa);

        $fecha = $fechadocto;
        $fecha = strtotime($fecha);
        $mes = intval(date("m", $fecha));
        $año = intval(date("Y", $fecha));
        $mod = $idmodulo;
        $ultregistro = DB::select("SELECT MAX(d.id) AS id FROM mc_almdigital a INNER JOIN mc_almdigital_det d ON a.id = d.idalmdigital WHERE a.idmodulo = $mod AND MONTH(a.fechadocto) = $mes AND YEAR(a.fechadocto) = $año");

        if (!empty($ultregistro)) {
            $ultimoid = $ultregistro[0]->id;
            if ($ultimoid > 0) {
                $ultarchivo = DB::select("SELECT codigodocumento FROM mc_almdigital_det WHERE id = $ultimoid");
                $nombre_a = $ultarchivo[0]->codigodocumento;
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





    //FUNCION UTILIZADA AL CARGA EL CRM, PARA MOSTRAR DATOS DE LA EMPRESA Y DEL USUARIO. TAMBIEN SE CONSUMO MENUS Y SUBMENUS QUE SON ALMACENADOS EN UNA VARIABLE GLOBAL EN EL CRM PARA NO VOLVER CONSUMIR CADA QUE SE REQUIERE.
    function DatosDeInicio(Request $request)
    {
        $Menus = DB::connection("General")->select("SELECT men.*,modu.nombre_modulo FROM mc1004 men INNER JOIN mc1003 modu ON men.idmodulo=modu.idmodulo WHERE men.Status = '1'");
        $SubMenus = DB::connection("General")->select("SELECT sub.*,men.nombre_menu FROM mc1005 sub INNER JOIN mc1004 men ON sub.idmenu=men.idmenu WHERE sub.Status = '1'");
        $DatosEmpresa = DB::connection("General")->select("SELECT nombreempresa, RFC, usuario_storage, password_storage FROM mc1000 WHERE idempresa='$request->idempresa'");
        $DatosUsuario = DB::connection("General")->select("SELECT nombre, apellidop, apellidom, correo, tipo FROM mc1001 WHERE idusuario='$request->idusuario'");
        $ServidorStorage = DB::connection("General")->select("SELECT servidor_storage FROM mc0000");

        $array = array(
            "Menus" => $Menus,
            "SubMenus" => $SubMenus,
            "Usuario" => $DatosUsuario,
            "Empresa" => $DatosEmpresa,
            "ServidorStorage" => $ServidorStorage,
        );

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
