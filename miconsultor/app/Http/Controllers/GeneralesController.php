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

    //RECEPCION POR LOTES
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

    function RegistrarDoctos(Request $request){
        $idempresa = $request->idempresa;
        $idusuario = $request->idusuario;
        $idlote = $request->IDlotes;
        $tipodocto = $request->tipodocto;
        $codigo = $request->codigo;
        $doctumentos = $request->doctos;
        
        ConnectDatabase($idempresa);

        $num_movtos = count($request->doctos);
        
        for ($i=0; $i < $num_movtos; $i++) { 
            $fecha = $doctumentos[$i]["fecha"];
            $concepto = $doctumentos[$i]["concepto"];
            $proveedor = $doctumentos[$i]["proveedor"];
            $producto = $doctumentos[$i]["producto"];           

            if($tipodocto == 3){                
                $val4 = $doctumentos[$i]["folio"];
                $val5 = $doctumentos[$i]["serie"];
                $val6 = floatval($doctumentos[$i]["subtotal"]);
                $val7 = floatval($doctumentos[$i]["descuento"]);
                $val8 = floatval($doctumentos[$i]["iva"]);
                $val9 = floatval($doctumentos[$i]["total"]);  
                $codigolote = str_replace("-", "", $fecha).$tipodocto.$val4;            
            }else{
                $val4 = $doctumentos[$i]["almacen"];
                $val5 = $doctumentos[$i]["litros"];
                $val6 = floatval($doctumentos[$i]["importe"]);
                $val7 = $doctumentos[$i]["unidad"];
                $codigolote = str_replace("-", "", $fecha).$tipodocto.$val5.$val7;
            }           
            
            
            //echo $codigolote;
            if($codigo == $codigolote){
             
                $lote = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigo'");
                
                if(empty($lote)){
                    if($tipodocto == 3){
                        DB::table('mc_lotesdocto')->insertGetId(['idlote' => $idlote, 'codigo' => $codigo, 'concepto' => $concepto, 'proveedor' => $proveedor, 'fecha' => $fecha, 'folio' => $val4, 'serie' => $val5, 'subtotal' => $val6, 'descuento' => $val7, 'iva' => $val8, 'total' => $val9]); 

                    }elseif($tipodocto == 2){
                        DB::table('mc_lotesdocto')->insertGetId(['idlote' => $idlote, 'codigo' => $codigo, 'concepto' => $concepto, 'proveedor' => $proveedor, 'fecha' => $fecha, 'campoextra1' => $val5, 'campoextra2' => $val4, 'total' => $val6]);
                    }

                    DB::table('mc_lotes')->where("id", $idlote)->update(['fechadecarga' => now(),'usuario' => $idusuario, 'tipo' => $tipodocto]);                    


                    $lote = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigo'");
                }else{
                    //DB::table('mc_lotesdocto')->where("iddocto", $lote[0]->iddocto)->update(['ultimacarga' => $now]);
                }            
            }

        }   


        return $lote;
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

                
                //$iddocumento = DB::select("SELECT id FROM mc_lotesmovtos WHERE iddocto  = '$iddocto'");
                //$iddocumento = DB::select("SELECT mc_lotesmovtos.id FROM mc_lotesdocto, mc_lotesmovtos WHERE mc_lotesdocto.codigo = '$codigo'");
                
                if($codigo == $codigotemp){

                    $iddocumento = DB::table('mc_lotesmovtos')->insertGetId(['idlote' => $idlote, 'iddocto' => $iddocto, 'fechamov' => $fecha, 'producto' => $producto, 'cantidad' => $litros, 'almacen' => $almacen, 'kilometros' => $kilometro, 'horometro' => $horometro, 'unidad' => $unidad, 'total' => $importe]);
                }

            }


        }
        
        
        return $iddocumento; 
    }   

    //-----------------//

}
