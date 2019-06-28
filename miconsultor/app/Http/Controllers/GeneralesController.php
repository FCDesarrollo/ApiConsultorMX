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

//---------------RECEPCION POR LOTES
    function ConsultarLotes(Request $request){
        $idempresa = $request->idempresa;

        ConnectDatabase($idempresa); 

        $lotes = DB::select("SELECT * FROM mc_lotes WHERE totalregistros <> 0 And totalcargados <> 0 ORDER BY id DESC LIMIT 100");
        
        for($i=0; $i < count($lotes); $i++){

            $idlote = $lotes[$i]->id;
            
            $procesados = DB::select("SELECT id FROM mc_lotesdocto WHERE idlote = $idlote And estatus = 1");

            $lotes[$i]->procesados = count($procesados);

            $idusuario = $lotes[$i]->usuario;            

            $datosuser = DB::connection("General")->select("SELECT nombre FROM mc1001 WHERE idusuario = $idusuario");
            
            //echo $datosuser[0]->nombre;

            $lotes[$i]->usuario = $datosuser[0]->nombre;
        }

        return $lotes;           

    }

    function ConsultarDoctos(Request $request){
        $idempresa = $request->idempresa;
        $idlote = $request->idlote;

        ConnectDatabase($idempresa); 

        $doctos = DB::select("SELECT * FROM mc_lotesdocto WHERE idlote = $idlote");

        return $doctos;        
    }

    function ConsultarMovtos(Request $request){
        $idempresa = $request->idempresa;
        $idlote = $request->idlote;

        ConnectDatabase($idempresa); 

        $movtos = DB::select("SELECT * FROM mc_lotesmovtos WHERE idlote = $idlote");

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
            //$doctos = true;
        }else{
            //$doctos = false;
        }

        return $doctos;        
    }

    function EliminarDocto(Request $request){
        $idempresa = $request->idempresa;
        $iddocto = $request->iddocto;

        ConnectDatabase($idempresa); 

        $docto = DB::select("SELECT * FROM mc_lotesdocto WHERE id = $iddocto");

        if($docto[0]->estatus == 0){
//            DB::table('mc_lotes')->where("id", $idlote)->delete();
            $idlote = $docto[0]->idlote;
            DB::table('mc_lotesdocto')->where("id", $iddocto)->delete();
            DB::table('mc_lotesmovtos')->where("iddocto", $iddocto)->delete();            

            $cargados = DB::select("SELECT totalcargados FROM mc_lotes WHERE id = '$idlote'");
            $n = $cargados[0]->totalcargados - 1;
            DB::table('mc_lotes')->where("id", $idlote)->update(['totalcargados' => $n]); 


            $docto = DB::select("SELECT * FROM mc_lotesdocto WHERE id = $iddocto");
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
                $estatus = $request->movtos[$i];
                $codigo = $fecha.$tipodocto.$folio;                
            }else if($request->movtos[$i]['idconce'] == 2){
                $unidad = $request->movtos[$i]['unidad'];            
                $estatus = $request->movtos[$i];
                $litros = $request->movtos[$i]['litros'];
                $codigo = $fecha.$tipodocto.$litros.$unidad;
            }


            $result = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigo'");
            

            if(empty($result)){
                $lote[$i]['estatus'] = "False";                
            }else{                
                $lote[$i]['estatus'] = "True";                
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

        $idlote = DB::select("SELECT id FROM mc_lotes WHERE ISNULL(tipo) LIMIT 1");

        if(empty($idlote)){
           $lote = DB::table('mc_lotes')->insertGetId(['fechadecarga' => $fechac, 'usuario' => $idusuario]);
           $idlote = DB::select("SELECT id FROM mc_lotes WHERE id = '$lote'");
        }
        return $idlote; 
    }

    function ConvertirValor($variable){
        $flag = true;

        $var = $variable;

        $permitidos = "0123456789"; 
       
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

    function RegistrarDoctos(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idlote = $request->IDlotes;
        $tipodocto = $request->tipodocto;
        $codigo = $request->codigo;
        $doctumentos = $request->doctos;
        $span = $request->span;
        

        ConnectDatabase($idempresa);

        $num_movtos = count($request->doctos);
    
        $flag = 0;
        $val4 = "";$val5 = "";$val6 = "";$val7 = "";$val8 = "";$val9 = "";$val10 = "";$val11 = "";$val12 = "";$val13 = "";
        
        $codigolote = "";

        for ($i=0; $i < $num_movtos; $i++) { 
            $fecha = $doctumentos[$i]["fecha"];
            $concepto = $doctumentos[$i]["concepto"];
            $proveedor = $this->ConvertirValor($doctumentos[$i]["proveedor"]);
            $producto = $doctumentos[$i]["producto"]; 

            if($tipodocto == 3){                
                $val4 = $this->ConvertirValor($doctumentos[$i]["folio"]);
                $val5 = $this->ConvertirValor($doctumentos[$i]["serie"]);
                $val6 = $this->ConvertirValor($doctumentos[$i]["subtotal"]);
                $val7 = $this->ConvertirValor($doctumentos[$i]["descuento"]);
                $val8 = $this->ConvertirValor($doctumentos[$i]["iva"]);
                $val9 = $this->ConvertirValor($doctumentos[$i]["total"]);
                
                $codigolote = str_replace("-", "", $fecha).$tipodocto.$val4; 
                          
            }else if($tipodocto == 2){
                $val9 = $this->ConvertirValor($doctumentos[$i]["importe"]);
                $val10 = $this->ConvertirValor($doctumentos[$i]["almacen"]);
                $val11 = $this->ConvertirValor($doctumentos[$i]["litros"]);                
                $val12 = $this->ConvertirValor($doctumentos[$i]["unidad"]);                
                $val13 = $this->ConvertirValor($doctumentos[$i]["horometro"]);
                $val14 = $this->ConvertirValor($doctumentos[$i]["kilometro"]);
                //$val6 = floatval($doctumentos[$i]["importe"]);
                //$val7 = $doctumentos[$i]["unidad"];
                $codigolote = str_replace("-", "", $fecha).$tipodocto.$val11.$val12;
            }           


            //------------VALIDACIONES----------------
            $validaciones = false;
            $valores = explode('-', $fecha);
            if(count($valores) == 3 && checkdate($valores[1], $valores[2], $valores[0])){
                if(is_string($proveedor)){
                    if ($val9 != "" || is_float($val9)) {
                        
                        if($tipodocto == 3){
                            if($val4 != "" && is_float($val4)){
                                //echo is_float($val4);                                
                                if($val6 != "" && is_float($val6) && (is_float($val7) || $val6 == "") && (is_float($val8) || $val8 == "") && is_float($val9)){
                                    $validaciones = true;
                                }else{
                                    if($val6 == "" || !is_float($val6)){
                                        $error = "Neto incorrecto o vacio.";
                                    }elseif (!is_float($val7)) {
                                        $error = "Desc. Incorrecto.";
                                    }elseif (!is_float($val8)) {
                                        $error = "IVA incorrecto.";
                                    }                                
                                }
                            }else{
                                $error = "Folio Incorrecto";                                                             
                            }
                        }elseif ($tipodocto == 2) {
                            if($val10 != "" && is_float($val10)){                                    
                                if($val11 != "" && is_float($val11)){
                                    if($val12 != ""){
                                        if($val13 != "" && is_float($val13)){
                                            if($val14 != "" && is_float($val14)){
                                                $validaciones = true;
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
                        }
                    }else{
                        $error = "Total incorrecto o vacio.";
                    }
                }else{
                    $error = "Error con el proveedor";
                }
            }else{
                $error = "Fecha Incorrecta";
            }
            
            
            if($codigo == $codigolote){       
                //echo $codigo."-".$codigolote.">";
                $lote = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigo'");
                
                if(empty($lote)){

                    if($validaciones == true){
                        DB::table('mc_lotesdocto')->insertGetId(['idlote' => $idlote, 'codigo' => $codigo, 'concepto' => $concepto, 'proveedor' => $proveedor, 'fecha' => $fecha, 'folio' => $val4, 'serie' => $val5, 'subtotal' => $val6, 'descuento' => $val7, 'iva' => $val8, 'total' => $val9, 'campoextra1' => $val11, 'campoextra2' => $val10]);
                        $lote = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigo'");

                        $lote[0]->span = $span;

                        $this->UpdateLote($idempresa, $tipodocto, $idlote, $num_movtos);                        
                            
                        break;
                    }else{                        
                        $lote[0]['error'] = $error;                        
                        $lote[0]['span'] = $span;
                    }
                }
            }
        } 


        return $lote;          


    }

    function UpdateLote($idempresa, $tipodocto, $idlote, $num_doctos){
        //$idempresa = $request->idempresa;
        //$idusuario = $request->idusuario;
        //$tipodocto = $request->tipodocto;
        //$num_doctos = count($request->numero_doc);
        //$idlote = $request->idlote;        

        ConnectDatabase($idempresa);

        $n = DB::select("SELECT count(id) AS reg FROM mc_lotesdocto WHERE idlote = '$idlote'");
        
        DB::table('mc_lotes')->where("id", $idlote)->update(['tipo' => $tipodocto, 'totalregistros' => $num_doctos, 'totalcargados' => $n[0]->reg]);
    }

    function RegistrarMovtos(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idlote = $request->IDlote;
        $iddocto = $request->IDdocto;
        $tipodocto = $request->tipodocto;
        $num_movtos = count($request->movtos);
        $codigo = $request->codigo;
        $movtos = $request->movtos;
        ConnectDatabase($idempresa);        
        
        $cont = 0;
        
        for ($i=0; $i < $num_movtos; $i++) {

            if($tipodocto == 3){
                $folio = $movtos[$i]['folio'];
                $fecha = $movtos[$i]['fecha'];
                $producto = $movtos[$i]['producto'];
                $cantidad = $movtos[$i]['cantidad'];
                $subtotal = floatval($movtos[$i]['subtotal']);
                $descuento = floatval($movtos[$i]['descuento']);
                $iva = floatval($movtos[$i]['iva']);
                $total = floatval($movtos[$i]['total']);

                $codigotemp = str_replace("-", "", $fecha).$tipodocto.$folio;

                if($codigo == $codigotemp){
                    $iddocumento = DB::table('mc_lotesmovtos')->insertGetId(['idlote' => $idlote, 'iddocto' => $iddocto, 'fechamov' => $fecha, 'producto' => $producto, 'cantidad' => $cantidad, 'subtotal' => $subtotal, 'descuento' => $descuento, 'iva' => $iva, 'total' => $total]);
                }
            }elseif($tipodocto == 2){
                $fecha = $movtos[$i]['fecha'];
                $producto = $movtos[$i]['producto'];
                $litros = $movtos[$i]['litros'];
                $almacen = $movtos[$i]['almacen'];
                $kilometro = $movtos[$i]['kilometro'];
                $horometro = $movtos[$i]['horometro'];
                $unidad = $movtos[$i]['unidad'];
                $importe = floatval($movtos[$i]['importe']);

                $codigotemp = str_replace("-", "", $fecha).$tipodocto.$litros.$unidad;

                
                if($codigo == $codigotemp){

                    $iddocumento = DB::table('mc_lotesmovtos')->insertGetId(['idlote' => $idlote, 'iddocto' => $iddocto, 'fechamov' => $fecha, 'producto' => $producto, 'cantidad' => $litros, 'almacen' => $almacen, 'kilometros' => $kilometro, 'horometro' => $horometro, 'unidad' => $unidad, 'total' => $importe]);

                }

            }


        }        
        
        return $iddocumento; 
    }   

    //-----------------//

}
