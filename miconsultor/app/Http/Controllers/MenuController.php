<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function menuWeb(Request $request)
    {
        $valida = verificaUsuario($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $modulos = DB::connection("General")->select("SELECT * FROM mc1003");
            for ($i=0; $i < count($modulos); $i++) {
                $idmodulo =  $modulos[$i]->idmodulo;
                $menus = DB::connection("General")->select("SELECT * FROM mc1004 
                                            WHERE idmodulo=$idmodulo");
                $modulos[$i]->menus = $menus;
                for ($x=0; $x < count($menus); $x++) {
                    $idmenu = $menus[$x]->idmenu;
                    $submenus = DB::connection("General")->select("SELECT * FROM mc1005 
                                    WHERE idmenu=$idmenu");
                    $menus[$x]->submenus = $submenus;
                }
                $array["modulos"][$i] = $modulos[$i];
            }
            
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
