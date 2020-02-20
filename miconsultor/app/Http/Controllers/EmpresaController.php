<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    function listaEmpresasUsuario(Request $request)
    {
        $valida = verificaUsuario($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $usuario = $valida[0]['usuario'];
            $iduser = $usuario[0]->idusuario;
            $array["usuario"] = $valida[0]['usuario'];
            $empresas = DB::connection("General")->select("SELECT mc1000.* FROM mc1002 m02 
                                                    INNER JOIN mc1000 on m02.idempresa=mc1000.idempresa 
                                                    WHERE m02.idusuario=$iduser AND mc1000.status=1");
            for ($i=0; $i < count($empresas); $i++) { 
                $empresaBD = $empresas[$i]->rutaempresa;        
                ConnectaEmpresaDatabase($empresaBD);

                $perfil = DB::select('select nombre from mc_userprofile INNER JOIN mc_profiles 
                                where idusuario = ?', [$iduser]);
                $empresas[$i]->perfil = $perfil[0]->nombre;

                $sucursales = DB::select('select * from mc_catsucursales');

                $empresas[$i]->sucursales = $sucursales;
            }
            
            $array["empresas"] = $empresas;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
