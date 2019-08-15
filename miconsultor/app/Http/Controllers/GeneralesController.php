<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

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

    function PermisosUsuario(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        ConnectDatabase($idempresa);

        $permisos= DB::select("SELECT u.*,p.nombre FROM usuariopermiso u 
        INNER JOIN perfiles p ON u.idperfil=p.idperfil WHERE idusuario='$idusuario'");
        
        $datos = array(
            "permisos" => $permisos,
        );           

        return json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    function updatePermisoUsuario(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idmodulo = $request->idmodulo;
        ConnectDatabase($idempresa);

        DB::table('usuariopermiso')->where("idusuario", $idusuario)->where("idmodulo", $idmodulo)->update(["tipopermiso"=>$request->tipopermiso]);
        return $idusuario;
    }

    function VinculaEmpresa(Request $request){
        $rfcempresa = $request->rfc;
        $passempresa = $request->contra;
        $iduser = $request->idusuario;
        $idperfil = $request->idperfil;

        $empresa = DB::connection("General")->select("SELECT idempresa FROM mc1000 WHERE rfc='$rfcempresa' AND password='$passempresa'");    
        if (empty($empresa)){
            $idempresa = 0;
            
        }else{
            $idempresa = $empresa[0]->idempresa;
            $id = DB::connection("General")->table('mc1002')->insert(
                ['idusuario' => $iduser,'idempresa' => $idempresa]);
            
            ConnectDatabase($idempresa);
            $idP = DB::table('mc_userprofile')->insert(
                ['idusuario' => $iduser,'idperfil' => $idperfil]);
            
            //SELECCIONAMOS LOS PERMISOS A LOS MODULOS DEL PERFIL
            $perfil = DB::select("SELECT idmodulo,tipopermiso FROM mc_modpermis WHERE idperfil='$idperfil'");     
            
            //INSERTAMOS LOS PERMISOS DE MODULOS DEL PERFIL AL USUARIO
            foreach($perfil as $t){
                $idU = DB::table('mc_usermod')->insert(
                    ['idusuario' => $iduser,'idperfil' => $idperfil,
                    'idmodulo' => $t->idmodulo,'tipopermiso' => $t->tipopermiso ]);
            }

            //SELECCIONAMOS LOS PERMISOS A LOS MENUS DEL PERFIL
            $perfil = DB::select("SELECT idmodulo,idmenu,tipopermiso FROM mc_menupermis WHERE idperfil='$idperfil'");     
            
            //INSERTAMOS LOS PERMISOS DE MENU DEL PERFIL AL USUARIO
            foreach($perfil as $t){
                $idU = DB::table('mc_usermenu')->insert(
                    ['idusuario' => $iduser,'idperfil' => $idperfil,
                    'idmodulo' => $t->idmodulo,'idmenu' => $t->idmenu,'tipopermiso' => $t->tipopermiso ]);
            }

            //SELECCIONAMOS LOS PERMISOS A LOS SUBMENUS DEL PERFIL
            $perfil = DB::select("SELECT idmenu,idsubmenu,tipopermiso,notificaciones FROM mc_submenupermis WHERE idperfil='$idperfil'");     
            
            //INSERTAMOS LOS PERMISOS DE SUBMENU DEL PERFIL AL USUARIO
            foreach($perfil as $t){
                $idU = DB::table('mc_usersubmenu')->insert(
                    ['idusuario' => $iduser,'idperfil' => $idperfil,
                    'idmenu' => $t->idmenu,'idsubmenu' => $t->idsubmenu,'tipopermiso' => $t->tipopermiso,'notificaciones' => $t->notificaciones ]);
            }

        }
        return $idempresa;
    }

    function PerfilesEmpresa($idempresa){

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
        DB::table('mc_profiles')->where("idperfil", $id)->update(["status"=>"0"]);
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
        $idP=$request->idperfil;
        $now = date('Y-m-d');
        ConnectDatabase($request->idempresa);
        
        if ($idP == 0 ){
            $uperfil = DB::select("SELECT max(idperfil) + 1 as idper  FROM mc_profiles");
            if ($uperfil[0]->idper <= 4){
                $uidperfil=5;   
            }else{
                $uidperfil = $uperfil[0]->idper;
            }
            
            $idP = DB::table('mc_profiles')->insertGetId(
                ['idperfil' => $uidperfil,'nombre' => $request->nombre,
                'descripcion' => $request->desc,'fecha' => $now,'status' =>"1" ]); 

            $idP = $uidperfil;    
        }else{
            DB::table('mc_profiles')->where("idperfil", $idP)->
                    update(["nombre"=>$request->nombre, 'descripcion' => $request->desc]);    
        }
        return $idP;
    }

    public function ModulosPerfil(Request $request)
    {
        $idU = $request->id;
        ConnectDatabase($request->idempresa);
        if ($idU == 0){
            $idU = DB::table('mc_modpermis')->insertGetId(
                ['idperfil' => $request->idperfil,'idmodulo' => $request->idmodulo,'tipopermiso' => $request->tipopermiso]); 
        }else{
            DB::table('mc_modpermis')->where("idperfil", $request->idperfil)->
            where("idmodulo", $request->idmodulo)->update(['tipopermiso' => $request->tipopermiso]); 
        }
        return $idU;
    }

    public function MenuPerfil(Request $request)
    {
        $idU = $request->id;
        ConnectDatabase($request->idempresa);
        if ($idU == 0){
            $idU = DB::table('mc_menupermis')->insertGetId(
                ['idperfil' => $request->idperfil,'idmodulo' => $request->idmodulo,
                'idmenu' => $request->idmenu,'tipopermiso' => $request->tipopermiso]);
        }else{
            DB::table('mc_menupermis')->where("idperfil", $request->idperfil)->
            where("idmodulo", $request->idmodulo)->where("idmenu", $request->idmenu)->update(['tipopermiso' => $request->tipopermiso]); 
        }
        return $idU;
    }

    public function SubMenuPerfil(Request $request)
    {
        $idU = $request->id;
        ConnectDatabase($request->idempresa);
        if ($idU == 0){
            $idU = DB::table('mc_submenupermis')->insertGetId(
                ['idperfil' => $request->idperfil,'idmenu' => $request->idmenu,
                'idsubmenu' => $request->idsubmenu, 'tipopermiso' => $request->tipopermiso,
                 'notificaciones' => $request->notificaciones]);
        }else{
            DB::table('mc_submenupermis')->where("idperfil", $request->idperfil)->
            where("idmenu", $request->idmenu)->where("idsubmenu", $request->idsubmenu)->
            update(['tipopermiso' => $request->tipopermiso, 'notificaciones' => $request->notificaciones]); 
        }
        return $idU;
    }

    public function EditarPerfilEmpresa(Request $request){
        ConnectDatabase($request->idempresa);
        $idp = $request->idperfil;
        DB::table('perfiles')->where("idperfil", $idp)->update(['nombre' => $request->nombre,
        'descripcion' => $request->desc,'status' => $request->status ]);
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

    function PermisosMenusPerfil(Request $request){
        $idempresa = $request->idempresa;
        $idModulo = $request->idmodulo;
        $IDPer = $request->idperfil;
        ConnectDatabase($idempresa);

        $permisos= DB::select("SELECT u.* FROM mc_menupermis u WHERE u.idmodulo='$idModulo' and u.idperfil='$IDPer' ORDER BY u.idmodulo DESC");
        
        $datos = $permisos;       
        return $datos;
    }

    function PermisoSubMenusPerfil(Request $request){
        $idempresa = $request->idempresa;
        $idMenu = $request->idmenu;
        $IDPer = $request->idperfil;
        ConnectDatabase($idempresa);

        $permisos= DB::select("SELECT u.* FROM mc_submenupermis u WHERE u.idmenu='$idMenu' and u.idperfil='$IDPer' ORDER BY u.idmenu DESC");
        
        $datos = $permisos;       
        return $datos;
    }




//---------------RECEPCION POR LOTES------------------------//

    function ConsultarLotes(Request $request){
        $idempresa = $request->idempresa;
    //    $lotespagina = $request->iniciar - 1;
        ConnectDatabase($idempresa); 

        $lotes = DB::select("SELECT l.*,SUM(IF(d.error>0,d.error,0)) AS cError FROM mc_lotes l LEFT JOIN mc_lotesdocto d ON l.id = d.idlote WHERE l.totalregistros <> 0 AND l.totalcargados <> 0 And d.estatus <> 2 GROUP BY l.id ORDER BY l.id DESC");
        //$lotes = DB::select("SELECT l.*,SUM(IF(d.error>0,d.error,0)) AS cError FROM mc_lotes l LEFT JOIN mc_lotesdocto d ON l.id = d.idlote WHERE l.totalregistros <> 0 AND l.totalcargados <> 0 And d.estatus <> 2 GROUP BY l.id ORDER BY l.id DESC LIMIT $lotespagina, 1");


        
        for($i=0; $i < count($lotes); $i++){

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

    }

    function ConsultarDoctos(Request $request){
        $idempresa = $request->idempresa;
        $idlote = $request->idlote;

        ConnectDatabase($idempresa); 

        $doctos = DB::select("SELECT * FROM mc_lotesdocto WHERE idlote = $idlote");
        /*for ($i=0; $i < count($datos); $i++) { 
            $clave = $datos[$i]->codigo;
            $clave = $clave.substr(8,1);
            $tipo = DB::connection("General")->select("SELECT tipo FROM mc1011 WHERE clave = '$clave'");
            $datos[$i]->tipo = $tipo[0]->tipo;
        } */


        return $doctos;        
    }

    function ConsultarMovtos(Request $request){
        $idempresa = $request->idempresa;
        $idlote = $request->idlote;

        ConnectDatabase($idempresa); 

        //$movtos = DB::select("SELECT * FROM mc_lotesmovtos WHERE idlote = $idlote");
        $movtos = DB::select("SELECT m.* FROM mc_lotesdocto d, mc_lotesmovtos m WHERE d.id = m.iddocto AND d.estatus <> 2 AND m.idlote = $idlote");

        return $movtos;        
    }

    function EliminarLote(Request $request){
        $idempresa = $request->idempresa;
        $idlote = $request->idlote;

        ConnectDatabase($idempresa); 

        $doctos = DB::select("SELECT * FROM mc_lotesdocto WHERE idlote = $idlote And estatus = 1");

        if(empty($doctos)){            
            DB::table('mc_lotes')->where("id", $idlote)->delete();
            DB::table('mc_lotesdocto')->where("idlote", $idlote)->delete();
            DB::table('mc_lotesmovtos')->where("idlote", $idlote)->delete();
            
        }else{
            
        }

        return $doctos;        
    }

    function EliminarDocto(Request $request){
        $idempresa = $request->idempresa;
        $iddocto = $request->iddocto;

        ConnectDatabase($idempresa); 

        $docto = DB::select("SELECT * FROM mc_lotesdocto WHERE id = $iddocto");

        if($docto[0]->estatus == 0){

            $idlote = $docto[0]->idlote;
            DB::table('mc_lotesdocto')->where("id", $iddocto)->update(['estatus' => 2]);
            
            $doctos = DB::select("SELECT COUNT(id) AS doctos FROM mc_lotesdocto WHERE idlote = '$idlote' AND estatus <> 2");

            //$cargados = DB::select("SELECT totalregistros FROM mc_lotes WHERE id = '$idlote'");
            
            DB::table('mc_lotes')->where("id", $idlote)->update(['totalcargados' => $doctos[0]->doctos]);            

            $docto = DB::select("SELECT * FROM mc_lotesdocto WHERE id = $iddocto And estatus <> 2");

        }

        return $docto;
    }

    function VerificarLote(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $tipodocto = $request->tipodocto;
        ConnectDatabase($idempresa);

        $lote = $request->movtos;

        $num_movtos = count($request->movtos);


        for ($i=0; $i < $num_movtos; $i++) { 
            $fecha = $request->movtos[$i]['fecha'];
            $fecha = str_replace("-", "", $fecha);


            if($request->movtos[$i]['idconce'] == 3){
                $folio = $request->movtos[$i]['folio'];                
                //$estatus = $request->movtos[$i];
                $codigo = $fecha.$tipodocto.$folio;                
            }else if($request->movtos[$i]['idconce'] == 2){
                $unidad = $request->movtos[$i]['unidad'];            
                //$estatus = $request->movtos[$i];
                $litros = $request->movtos[$i]['litros'];
                $codigo = $fecha.$tipodocto.$litros.$unidad;
            }else if($request->movtos[$i]['idconce'] == 4){
                $cantidad = $request->movtos[$i]['cantidad'];
                $unidad = $request->movtos[$i]['unidad'];
                $precio = $request->movtos[$i]['precio'];
                $codigo = $fecha.$tipodocto.$cantidad.$unidad.$precio;
            }else if($request->movtos[$i]['idconce'] == 5){
                $cantidad = $request->movtos[$i]['cantidad'];
                $unidad = $request->movtos[$i]['unidad'];
                //$estatus = $request->movtos[$i];                
                $codigo = $fecha.$tipodocto.$cantidad.$unidad;
            }


            $result = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigo' And error <> 1");
            

            if(empty($result)){
                $lote[$i]['estatus'] = "False";
            }else{                
                $lote[$i]['estatus'] = "True";                
                $lote[$i]['iddocto'] = $result[0]->id;
                $lote[$i]['procesado'] = $result[0]->estatus;
            }

            $lote[$i]['codigo'] = $codigo;

        }
        return $lote;

    }


    function RegistrarLote(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;        
        ConnectDatabase($idempresa);

        $tipodocto = $request->tipodocto;
        $fechac = now();

        $idlote = DB::select("SELECT id FROM mc_lotes WHERE tipo = 0 LIMIT 1");

        if(empty($idlote)){

            $lote = DB::table('mc_lotes')->insertGetId(['fechadecarga' => $fechac, 'usuario' => $idusuario, 'tipo' => 0]);
            $idlote = DB::select("SELECT id FROM mc_lotes WHERE id = '$lote'");
        }
        return $idlote; 
    }

    function ConvertirValor($variable){
        $flag = true;

        $var = $variable;

        $permitidos = "0123456789.,$"; 
       
        for ($i=0; $i<strlen($variable); $i++){ 
            if (strpos($permitidos, substr($variable,$i,1))===false){ 
                $flag = false;                
                break;
            } 
        }

        if($flag == true){
            $var = floatval($var);            
        }

        return $var;
    }

    function ValidarDatos($doctumentos, $tipodocto, $num_movtos){
        
        $val4 = "";$val5 = "";$val6 = "";$val7 = "";$val8 = "";$val9 = "";$val10 = "";$val11 = "";$val12 = "";$val13 = "";
        $codigolote = "";
        for ($i=0; $i < $num_movtos; $i++) { 
            $error = "";
            $fecha = $doctumentos[0][$i]["fecha"];
            $concepto = $doctumentos[0][$i]["codigoconcepto"];
            $rfc = $this->ConvertirValor($doctumentos[0][$i]["rfc"]);
            $producto = $doctumentos[0][$i]["codigoproducto"]; 
            $suc = $doctumentos[0][$i]["sucursal"]; 

            if($tipodocto == 3){                
                $val4 = $this->ConvertirValor($doctumentos[0][$i]["folio"]);
                $val5 = $this->ConvertirValor($doctumentos[0][$i]["serie"]);
                $val6 = $this->ConvertirValor($doctumentos[0][$i]["subtotal"]);
                $val7 = $this->ConvertirValor($doctumentos[0][$i]["descuento"]);
                $val8 = $this->ConvertirValor($doctumentos[0][$i]["iva"]);
                $val9 = $this->ConvertirValor($doctumentos[0][$i]["total"]);
                $codigolote = str_replace("-", "", $fecha).$tipodocto.$val4;
            }else if($tipodocto == 2){
                $val9 = $this->ConvertirValor($doctumentos[0][$i]["importe"]);
                $val10 = $this->ConvertirValor($doctumentos[0][$i]["almacen"]);
                $val11 = $this->ConvertirValor($doctumentos[0][$i]["litros"]);                
                $val12 = $this->ConvertirValor($doctumentos[0][$i]["unidad"]);                
                $val13 = $this->ConvertirValor($doctumentos[0][$i]["horometro"]);
                $val14 = $this->ConvertirValor($doctumentos[0][$i]["kilometro"]);
                $codigolote = str_replace("-", "", $fecha).$tipodocto.$val11.$val12;
            }else if($tipodocto == 4){
                $val9 = $this->ConvertirValor($doctumentos[0][$i]["precio"]);
                $val10 = $this->ConvertirValor($doctumentos[0][$i]["almacen"]);
                $val11 = $this->ConvertirValor($doctumentos[0][$i]["cantidad"]);                
                $val12 = $this->ConvertirValor($doctumentos[0][$i]["unidad"]);                
                $codigolote = str_replace("-", "", $fecha).$tipodocto.$val11.$val12.$val9;
            }else if($tipodocto == 5){
                $val10 = $this->ConvertirValor($doctumentos[0][$i]["almacen"]);
                $val11 = $this->ConvertirValor($doctumentos[0][$i]["cantidad"]);                
                $val12 = $this->ConvertirValor($doctumentos[0][$i]["unidad"]);                
                $codigolote = str_replace("-", "", $fecha).$tipodocto.$val11.$val12;
            } 

            $valores = explode('-', $fecha);
            if(count($valores) == 3 && checkdate($valores[1], $valores[2], $valores[0])){
                if(is_string($rfc)){                    
                        
                    if($tipodocto == 3){
                        if($val4 != "" && is_float($val4)){                            
                            if($val6 != "" && is_float($val6) && (is_float($val7) || $val6 == "") && (is_float($val8) || $val8 == "") && ($val9 != "" || is_float($val9))){

                            }else{
                                if($val6 == "" || !is_float($val6)){
                                    $error = "Neto incorrecto o vacio.";
                                }else if(!is_float($val7)) {
                                    $error = "Desc. Incorrecto.";
                                }else if(!is_float($val8)) {
                                    $error = "IVA incorrecto.";
                                }else if($val9 == "" || !is_float($val9)){
                                    $error = "Total incorrecto o vacio.";                                        
                                }                                
                            }
                        }else{
                            $error = "Folio Incorrecto";                                                             
                        }
                    }else if($tipodocto == 2) {
                        if($val10 != ""){                                    
                            if($val11 != "" && is_float($val11)){
                                if($val12 != ""){
                                    if($val13 == 0 && is_float($val13)){
                                        if(($val14 != "" || $val14 == 0) && is_float($val14)){
                                            
                                        }else{
                                            $error = "Error Kilometros.";
                                        }
                                    }else{
                                        $error = "Error Horometro.";
                                    }                                        
                                }else{
                                    $error = "Campo vacio(Unidad).";
                                }
                            }else{
                                $error = "Error en Litros.";
                            }
                        }else{
                            $error = "Error con el Almacen.";
                        }                            
                    }else if($tipodocto == 4 || $tipodocto == 5){
                        if($val10 != "" && is_float($val10)){
                            if($val11 =! "" && is_float($val11)){
                                if($val12 != ""){
                                    if($tipodocto == 4){
                                        if($val9 =! "" && is_float($val9)){

                                        }else{
                                            $error = "Error con el precio.";           
                                        }                                        
                                    }
                                }else{
                                    $error = "Campo vacio(Unidad).";
                                }
                            }else{
                                $error = "Error con la cantidad.";       
                            }
                        }else{
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

                    
                }else{
                    $error = "Error con el RFC";
                }
            }else{
                $error = "Fecha Incorrecta";
            }

            if($error != ""){                
                $doctumentos[0][$i]['error'] = 1;                
            }else{
                $doctumentos[0][$i]['error'] = 0;                
            }   
            $doctumentos[0][$i]['error_det'] = $error;        

        } //FIN FOR
        return $doctumentos;
    }

    function RegistrarDoctos(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idlote = $request->IDlotes;
        $tipodocto = $request->tipodocto;
        $codigo = $request->codigo;
        $doctumentos = $request->doctos;
        $span = $request->span;
        

        ConnectDatabase($idempresa);

        $num_doctos = count($doctumentos[0]);
        $num_movtos = count($doctumentos[1]);
    
        $flag = 0;
        $val4 = "";$val5 = "";$val6 = "";$val7 = "";$val8 = "";$val9 = "";$val10 = "";$val11 = "";$val12 = "";$val13 = "";
        
        $codigolote = "";

        $doctumentos = $this->ValidarDatos($doctumentos, $tipodocto, $num_doctos);

        for ($i=0; $i < $num_doctos; $i++) { 
            $fecha = $doctumentos[0][$i]["fecha"];
            $codigoconcepto = $doctumentos[0][$i]["codigoconcepto"];
            $concepto = $doctumentos[0][$i]["concepto"];
            $rfc = $doctumentos[0][$i]["rfc"];
            $razonsocial = $doctumentos[0][$i]["razonsocial"];
            $codigoproducto = $doctumentos[0][$i]["codigoproducto"]; 
            $producto = $doctumentos[0][$i]["producto"]; 
            $suc = $doctumentos[0][$i]["sucursal"];

            if($tipodocto == 3){                
                $val4 = $doctumentos[0][$i]["folio"];
                $val5 = $doctumentos[0][$i]["serie"];
                $val6 = $doctumentos[0][$i]["subtotal"];
                $val7 = $doctumentos[0][$i]["descuento"];
                $val8 = $doctumentos[0][$i]["iva"];
                $val9 = $doctumentos[0][$i]["total"];
                $codigolote = str_replace("-", "", $fecha).$tipodocto.$val4;                           
            }else if($tipodocto == 2){
                $val9 = $doctumentos[0][$i]["importe"];
                $val10 = $doctumentos[0][$i]["almacen"];
                $val11 = $doctumentos[0][$i]["litros"];                
                $val12 = $doctumentos[0][$i]["unidad"];                
                $val13 = $doctumentos[0][$i]["horometro"];
                $val14 = $doctumentos[0][$i]["kilometro"];
                $codigolote = str_replace("-", "", $fecha).$tipodocto.$val11.$val12;
            }else if($tipodocto == 4 || $tipodocto == 5){
                $val10 = $doctumentos[0][$i]["almacen"];
                $val11 = $doctumentos[0][$i]["cantidad"];                
                $val12 = $doctumentos[0][$i]["unidad"];
                if($tipodocto == 4){
                    $val9 = $doctumentos[0][$i]["precio"];
                    $codigolote = str_replace("-", "", $fecha).$tipodocto.$val11.$val12.$val9;
                }else{
                    $codigolote = str_replace("-", "", $fecha).$tipodocto.$val11.$val12;
                }                
            }

            $error = $doctumentos[0][$i]["error"];
            $error_det = $doctumentos[0][$i]["error_det"];
           
            $doctumentos[0][$i]["estatus"] = 0;

            if($doctumentos[0][$i]["codigo"] != ""){
                
                $codigo = $doctumentos[0][$i]["codigo"];
                $lote = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigo'");                
                                
                if(empty($lote)){
                    
                    DB::table('mc_lotesdocto')->insertGetId(['idlote' => $idlote, 'codigo' => $codigo, 'sucursal' => $suc, 'concepto' => $codigoconcepto, 'proveedor' => $rfc, 'fecha' => $fecha, 'folio' => $val4, 'serie' => $val5, 'subtotal' => $val6, 'descuento' => $val7, 'iva' => $val8, 'total' => $val9,'campoextra1' => $val11, 'campoextra2' => $val10, 'error' => $error,  'detalle_error' => $error_det]);
    
                    $lote = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigo'");
    
                    $doctumentos[0][$i]["estatus"] = 1; //Nuevo Registro

                    $this->RegistrarMovtos2($idempresa, $idusuario, $idlote, $lote[0]->id, $tipodocto, $codigo, $doctumentos, $num_movtos);                       
                }else{
                    $id = $lote[0]->id;
                    $movtos = DB::select("SELECT * FROM mc_lotesmovtos WHERE iddocto = $id");
                    
                    DB::table('mc_lotesdocto')->where("id", $lote[0]->id)->update(['idlote' => $lote[0]->idlote, 'codigo' => $codigo, 'sucursal' => $suc, 'concepto' => $codigoconcepto, 'proveedor' => $rfc, 'fecha' => $fecha, 'folio' => $val4, 'serie' => $val5, 'subtotal' => $val6, 'descuento' => $val7, 'iva' => $val8, 'total' => $val9, 'campoextra1' => $val11, 'campoextra2' => $val10, 'error' => $error,  'detalle_error' => $error_det]);

                    if($error == 0){
                        DB::table('mc_lotesdocto')->where("id", $lote[0]->id)->update(['error' => $error, 'detalle_error' => $error_det]);
                    }

                    $lote = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigo'");

                    $doctumentos[0][$i]["estatus"] =  2; //Actualizado

                    $idlote = $lote[0]->idlote;

                    $this->RegistrarMovtos2($idempresa, $idusuario, $idlote, $lote[0]->id, $tipodocto, $codigo, $doctumentos, $num_movtos);
                           

                }                    
                $this->UpdateLote($idempresa, $tipodocto, $idlote, $num_doctos);
            }
        }
         
         

        //return $lote;          
        return $doctumentos[0];


    }

    function UpdateLote($idempresa, $tipodocto, $idlote, $num_doctos){     

        ConnectDatabase($idempresa);

        $n = DB::select("SELECT count(id) AS reg FROM mc_lotesdocto WHERE idlote = '$idlote' And error <> 1");
        
        DB::table('mc_lotes')->where("id", $idlote)->update(['tipo' => $tipodocto, 'totalregistros' => $num_doctos, 'totalcargados' => $n[0]->reg]);
    }

    function RegistrarMovtos2($idempresa, $idusuario, $idlote, $iddocto, $tipodocto, $codigo, $movtos, $num_movtos){

        ConnectDatabase($idempresa);   
        
        $cont = 0;
        
        for ($i=0; $i < $num_movtos; $i++) {

            $fecha = $movtos[1][$i]["fecha"];            
            $codigoproducto = $movtos[1][$i]["codigoproducto"]; 
            


            if($tipodocto == 3){
                $folio = $movtos[1][$i]["folio"];
                $cantidad = $movtos[1][$i]["cantidad"];
                $subtotal = floatval($movtos[1][$i]["subtotal"]);
                $descuento = floatval($movtos[1][$i]["descuento"]);
                $iva = floatval($movtos[1][$i]["iva"]);
                $total = floatval($movtos[1][$i]["total"]);
                $codigotemp = str_replace("-", "", $fecha).$tipodocto.$folio;    

                if($codigo == $codigotemp){ // tipo 3
                    $iddocumento = DB::table('mc_lotesmovtos')->insertGetId(['idlote' => $idlote, 'iddocto' => $iddocto, 'fechamov' => $fecha, 'producto' => $codigoproducto, 'cantidad' => $cantidad, 'subtotal' => $subtotal, 'descuento' => $descuento, 'iva' => $iva, 'total' => $total]);
                }                        
            }elseif($tipodocto == 2){
                $cantidad = $movtos[1][$i]['litros'];
                $almacen = $movtos[1][$i]['almacen'];
                $kilometros = $movtos[1][$i]['kilometro'];
                $horometros = $movtos[1][$i]['horometro'];
                $unidad = $movtos[1][$i]['unidad'];
                $total = floatval($movtos[1][$i]['importe']);
                $codigotemp = str_replace("-", "", $fecha).$tipodocto.$cantidad.$unidad;
                if($codigo == $codigotemp){ // tipo 2
                    $docto = DB::select("SELECT * FROM mc_lotesmovtos WHERE idlote = '$idlote' And iddocto =  '$iddocto' And fechamov = '$fecha' And total = '$total'");

                    if(empty($docto)){
                        $iddocumento = DB::table('mc_lotesmovtos')->insertGetId(['idlote' => $idlote, 'iddocto' => $iddocto, 'fechamov' => $fecha, 'producto' => $codigoproducto, 'cantidad' => $cantidad, 'almacen' => $almacen, 'kilometros' => $kilometros, 'horometro' => $horometros, 'unidad' => $unidad, 'total' => $total]);
                    }else{
                        $iddocumento = DB::table('mc_lotesmovtos')->where("id", $docto[0]->id)->update(['producto' => $codigoproducto, 'almacen' => $almacen, 'kilometros' => $kilometros, 'horometro' => $horometros, 'total' => $total]);
                    }
                }                
            }elseif($tipodocto == 4 || $tipodocto == 5){
                $cantidad = $movtos[1][$i]["cantidad"];
                $almacen = $movtos[1][$i]['almacen'];
                $unidad = $movtos[1][$i]['unidad'];

                if($tipodocto == 4){
                    $precio = $movtos[1][$i]['precio'];
                    $codigotemp = str_replace("-", "", $fecha).$tipodocto.$cantidad.$unidad.$precio;
                    if($codigo == $codigotemp){
                        $movto = DB::select("SELECT * FROM mc_lotesmovtos WHERE idlote = '$idlote' And iddocto = '$iddocto' And fechamov = '$fecha' And total = '$precio'");
                        if(empty($movto)){
                            DB::table('mc_lotesmovtos')->insertGetId(['idlote' => $idlote, 'iddocto' => $iddocto, 'fechamov' => $fecha, 'producto' => $codigoproducto, 'almacen' => $almacen, 'cantidad' => $cantidad, 'unidad' => $unidad, 'total' => $precio]);                            
                        }

                    }                    
                }else{                    
                    $codigotemp = str_replace("-", "", $fecha).$tipodocto.$cantidad.$unidad; 

                    if($codigo == $codigotemp){ 
                        $movto = DB::select("SELECT * FROM mc_lotesmovtos WHERE idlote = '$idlote' And iddocto = '$iddocto' And fechamov = '$fecha'");
                        if(empty($movto)){
                            DB::table('mc_lotesmovtos')->insertGetId(['idlote' => $idlote, 'iddocto' => $iddocto, 'fechamov' => $fecha, 'producto' => $codigoproducto, 'almacen' => $almacen, 'cantidad' => $cantidad, 'unidad' => $unidad]);                    
                        }                        
                    }                    
                }
                

            }

        }        
        
        //return $iddocumento; 
    }    
 

    function Paginador(Request $request){
        ConnectDatabase($request->idempresa);
        $inicio = $request->iniciar;
        $lotespagina = $request->lotespag;

    
        $lotes = DB::select("SELECT l.*,SUM(IF(d.error>0,d.error,0)) AS cError FROM mc_lotes l LEFT JOIN mc_lotesdocto d ON l.id = d.idlote WHERE l.totalregistros <> 0 AND l.totalcargados <> 0 And d.estatus <> 2 GROUP BY l.id ORDER BY l.id DESC LIMIT $inicio, $lotespagina");

        
        for($i=0; $i < count($lotes); $i++){

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


    }

    function ChecarCatalogos(Request $request){
        $datos = $request->array;
        ConnectDatabase($request->idempresa);

        $count = count($datos);
        
        $dato[1]['status'] = 0;


        for($i=0; $i < $count; $i++){
            $codprod = $datos[$i]['codigoproducto'];
            $clienprov = $datos[$i]['rfc'];
            $codconcepto = $datos[$i]['codigoconcepto'];

            $suc = $datos[$i]['sucursal'];
            $tipodocto = $datos[$i]['idconce'];

            $datos[$i]['prodreg'] = 0;
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
            if(empty($producto)){
                $dato[1]['status'] = 1;
                $datos[$i]['prodreg'] = 1;  
            }

            $proveedor = DB::select("SELECT * FROM mc_catclienprov WHERE rfc = '$clienprov' And tipocli = '$tipocli' OR tipocli = 3");
            if(empty($proveedor)){
                $dato[1]['status'] = 1;
                $datos[$i]['clienprovreg'] = 1;
            }
            
            $concepto = DB::select("SELECT * FROM mc_catconceptos WHERE codigoconcepto = '$codconcepto'");
            if(empty($concepto)){
                $dato[1]['status'] = 1;
                $datos[$i]['conceptoreg'] = 1;
            }
            
            $sucursal = DB::select("SELECT * FROM mc_catsucursales WHERE sucursal = '$suc'");
            if(empty($sucursal)){
                $dato[1]['status'] = 1;
                $datos[$i]['sucursalreg'] = 1;
            }       

        }

        $dato[0] = $datos;

        return $dato;

    }

    function RegistrarElemento(Request $request){
        $datos = $request->datos;
        $tipo = $request->tipo;
        ConnectDatabase($request->idempresa);

        $campo1 = strtoupper($datos[0]); // Codigo
        $campo2 = strtoupper($datos[1]); //Nombre
        switch ($datos[2]) { //Tipo de Cliente/Proveedor
            case 2: //DIESEL
            case 4:
            case 5:
                $campo3 = 2; //Proveedor
                break;            
            case 3: //REMISION
                $campo3 = 1; //Cliente
                break;
        }
        $elemento = $datos[4]; //Codigo de la plantilla
        if($elemento == $campo1){ $campo4 = $campo1; }else{ $campo4 = $campo1; $campo1 = $elemento; }
        

        if($tipo == "productos"){            
            $ele = DB::select("SELECT * FROM mc_catproductos WHERE codigoprod = '$campo1' OR codigoadw = '$campo1'");
            if(empty($ele)){
                DB::table('mc_catproductos')->insertGetId(['codigoprod' => $campo1, 'nombreprod' => $campo2, 'codigoadw' => $campo4, 'nombreadw' => $campo2, 'fechaalta' => now()]);
                $respuesta = 1;
            }else{
                $respuesta = 0;
            }        
        }else if($tipo == "clientesproveedores"){            
            $ele = DB::select("SELECT * FROM mc_catclienprov WHERE rfc = '$campo1'");
            if(empty($ele)){
                DB::table('mc_catclienprov')->insertGetId(['rfc' => $campo1, 'razonsocial' => $campo2, 'tipocli' => $campo3]);
                $respuesta = 1;
            }else{
                if($ele[0]->tipocli == $campo3){
                    $respuesta = 0;    
                }else{
                    if($ele[0]->tipocli != 3){
                        DB::table('mc_catclienprov')->where("id", $ele[0]->id)->update(['tipocli' => 3, 'razonsocial' => $campo2]); //Registrar como Cliente/Proveedor    
                        $respuesta = 1;
                    }else{
                        $respuesta = 0;
                    }           
                }                
            }            
        }else if($tipo == "conceptos"){
            $ele = DB::select("SELECT * FROM mc_catconceptos WHERE codigoconcepto = '$campo1' OR codigoadw = '$campo1'");
            if(empty($ele)){
                DB::table('mc_catconceptos')->insertGetId(['codigoconcepto' => $campo1, 'nombreconcepto' => $campo2, 'codigoadw' => $campo4, 'nombreadw' => $campo2]);
                $respuesta = 1;
            }else{
                $respuesta = 0;
            }
        }else if($tipo == "sucursales"){
            $ele = DB::select("SELECT * FROM mc_catsucursales WHERE sucursal = '$campo1'");
            if(empty($ele)){
                DB::table('mc_catsucursales')->insertGetId(['sucursal' => $campo1]);
                $respuesta = 1;
            }else{
                $respuesta = 0;
            }            
        }
        
        return $respuesta;

    }

    //-----------------//

}
