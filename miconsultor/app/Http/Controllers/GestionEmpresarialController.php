<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use ZipArchive;

class GestionEmpresarialController extends Controller
{
    public function getPryProyectos(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $proyectos = DB::select("SELECT mc_pry_proyectos.*,
            (
            CASE
                WHEN mc_pry_proyectos.Estatus = 1 THEN 'Pendiente'
                WHEN mc_pry_proyectos.Estatus = 2 THEN 'En proceso'
                WHEN mc_pry_proyectos.Estatus = 3 THEN 'Terminado'
                ELSE 'Cerrado'
            END
            ) AS nombreEstatus,
            mc_pry_agentes.Agente,
            (SELECT COUNT(mc_pry_proyactividades.id) FROM mc_pry_proyactividades WHERE mc_pry_proyactividades.IdProyecto = mc_pry_proyectos.id) AS numActividades,
            (SELECT COUNT(mc_pry_proyacciones.id) FROM mc_pry_proyacciones WHERE mc_pry_proyacciones.idproyecto = mc_pry_proyectos.id) AS numAcciones,
            (SELECT COUNT(mc_pry_proydocumentos.id) FROM mc_pry_proydocumentos WHERE mc_pry_proydocumentos.idproyecto = mc_pry_proyectos.id) AS numDocumentos,
            (SELECT COUNT(mc_pry_proyplanes.id) FROM mc_pry_proyplanes WHERE mc_pry_proyplanes.idproyecto = mc_pry_proyectos.id) AS numPlanes
            FROM mc_pry_proyectos 
            INNER JOIN mc_pry_agentes ON mc_pry_proyectos.idAgente = mc_pry_agentes.id 
            ORDER BY mc_pry_proyectos.FecUltAccion DESC");
            $array["proyectos"] = $proyectos;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarPryProyecto(Request $request)
    {
        $idProyecto = $request->idProyecto;
        $Proyecto = $request->Proyecto;
        $Codigo = $request->Codigo;
        $IdClas1 = $request->IdClas1;
        $IdClas2 = $request->IdClas2;
        $IdClas3 = $request->IdClas3;
        $IdClas4 = $request->IdClas4;
        $idAgente = $request->idAgente;
        $Estatus = $request->Estatus;
        $FecIni = $request->FecIni;
        $FecFin = $request->FecFin;
        $Avance = $request->Avance;
        $FecUltAccion = $request->FecUltAccion;
        $accion = $request->accion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            if($accion === 1) {
                DB::table('mc_pry_proyectos')->insert(['Proyecto' => $Proyecto, 'Codigo' => $Codigo, 'IdClas1' => $IdClas1, 'IdClas2' => $IdClas2, 'IdClas3' => $IdClas3, 'IdClas4' => $IdClas4, 'idAgente' => $idAgente, 'Estatus' => $Estatus, 'FecIni' => $FecIni, 'FecFin' => $FecFin, 'Avance' => $Avance, 'FecUltAccion' => $FecUltAccion]);
            }
            else {
                DB::table('mc_pry_proyectos')->where("id", $idProyecto)->update(['Proyecto' => $Proyecto, 'Codigo' => $Codigo, 'IdClas1' => $IdClas1, 'IdClas2' => $IdClas2, 'IdClas3' => $IdClas3, 'IdClas4' => $IdClas4, 'idAgente' => $idAgente, 'Estatus' => $Estatus, 'FecIni' => $FecIni, 'FecFin' => $FecFin, 'Avance' => $Avance, 'FecUltAccion' => $FecUltAccion]);
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function borrarPryProyecto(Request $request)
    {
        $idProyecto = $request->idProyecto;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            DB::table('mc_pry_proyectos')->where("id", $idProyecto)->delete();
            DB::table('mc_pry_proyactividades')->where("IdProyecto", $idProyecto)->delete();
            DB::table('mc_pry_proyacciones')->where("idproyecto", $idProyecto)->delete();
            DB::table('mc_pry_proycatagentes')->where("IDProyecto", $idProyecto)->delete();
            DB::table('mc_pry_proypersonas')->where("idproyecto", $idProyecto)->delete();
            DB::table('mc_pry_proyplanes')->where("idproyecto", $idProyecto)->delete();
            DB::table('mc_pry_proydocumentos')->where("idproyecto", $idProyecto)->delete(); //aqui falta eliminar los documentos en NextCloud.
            //Definir que pasara despues de eliminar un proyecto con los agentes, personas, actividades y acciones que esten relacionados con el proyecto eliminado.
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function getPryAgentesPersonas(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $agentesPersonas = DB::select('SELECT * FROM mc_pry_agentes');
            $array["agentesPersonas"] = $agentesPersonas;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarPryAgentePersona(Request $request)
    {
        $idAgentePersona = $request->idAgentePersona;
        $Agente = $request->Agente;
        $Estatus = $request->Estatus;
        $tipo = $request->tipo;
        $accion = $request->accion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            if($accion === 1) {
                DB::table('mc_pry_agentes')->insert(['Agente' => $Agente, 'Estatus' => $Estatus, 'tipo' => $tipo]);
            }
            else {
                DB::table('mc_pry_agentes')->where("id", $idAgentePersona)->update(['Agente' => $Agente, 'Estatus' => $Estatus, 'tipo' => $tipo]);
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function cambiarEstatusPryAgentePersona(Request $request)
    {
        $idAgentePersona = $request->idAgentePersona;
        $Estatus = $request->Estatus;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $agentesPersonasActivas = [];
            if($Estatus == 0) {
                $agentesPersonasActivas = DB::select('SELECT mc_pry_proyactividades.idAgente FROM mc_pry_proyactividades WHERE mc_pry_proyactividades.idAgente = ?
                UNION
                SELECT mc_pry_proycatagentes.IDPersona FROM mc_pry_proycatagentes WHERE mc_pry_proycatagentes.IDPersona = ?
                UNION
                SELECT mc_pry_proyectos.idAgente FROM mc_pry_proyectos WHERE mc_pry_proyectos.idAgente = ?
                UNION
                SELECT mc_pry_proypersonas.idpersona FROM mc_pry_proypersonas WHERE mc_pry_proypersonas.idpersona = ?
                UNION
                SELECT mc_pry_proyplanes.idagente FROM mc_pry_proyplanes WHERE mc_pry_proyplanes.idagente = ?', [$idAgentePersona, $idAgentePersona, $idAgentePersona, $idAgentePersona, $idAgentePersona]);
            }
            
            if(count($agentesPersonasActivas) == 0) {
                DB::table('mc_pry_agentes')->where("id", $idAgentePersona)->update(['Estatus' => $Estatus]);
            }
            else {
                $array["error"] = 59;
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function borrarPryAgentePersona(Request $request)
    {
        $idAgentePersona = $request->idAgentePersona;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $agentesPersonasActivas = DB::select('SELECT mc_pry_proyactividades.idAgente FROM mc_pry_proyactividades WHERE mc_pry_proyactividades.idAgente = ?
            UNION
            SELECT mc_pry_proycatagentes.IDPersona FROM mc_pry_proycatagentes WHERE mc_pry_proycatagentes.IDPersona = ?
            UNION
            SELECT mc_pry_proyectos.idAgente FROM mc_pry_proyectos WHERE mc_pry_proyectos.idAgente = ?
            UNION
            SELECT mc_pry_proypersonas.idpersona FROM mc_pry_proypersonas WHERE mc_pry_proypersonas.idpersona = ?
            UNION
            SELECT mc_pry_proyplanes.idagente FROM mc_pry_proyplanes WHERE mc_pry_proyplanes.idagente = ?', [$idAgentePersona, $idAgentePersona, $idAgentePersona, $idAgentePersona, $idAgentePersona]);
            if(count($agentesPersonasActivas) == 0) {
                DB::table('mc_pry_agentes')->where("id", $idAgentePersona)->delete();
            }
            else {
                $array["error"] = 59;
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function getTodosPryProyCatAgentes(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            /* $proycatagentes = DB::select('SELECT mc_pry_proyectos.id AS idProyecto, mc_pry_proyectos.Proyecto,
            mc_pry_agentes.id AS idPersona, mc_pry_agentes.Agente FROM mc_pry_proycatagentes
            INNER JOIN mc_pry_proyectos ON mc_pry_proycatagentes.IDProyecto = mc_pry_proyectos.id
            INNER JOIN mc_pry_agentes ON mc_pry_proycatagentes.IDPersona = mc_pry_agentes.id'); */
            $proycatagentes = DB::select('SELECT mc_pry_agentes.*, IF(ISNULL(mc_pry_proycatagentes.IDProyecto), 0, mc_pry_proycatagentes.IDProyecto) AS idProyecto FROM mc_pry_agentes 
            LEFT JOIN mc_pry_proycatagentes ON mc_pry_agentes.id = mc_pry_proycatagentes.IDPersona
            WHERE mc_pry_agentes.tipo = ?', [2]);
            $array["proycatagentes"] = $proycatagentes;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function getPryProyCatAgentes(Request $request)
    {
        $IDProyecto = $request->IDProyecto;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $proycatagentes = DB::select('SELECT * FROM mc_pry_proycatagentes WHERE IDProyecto = ?', [$IDProyecto]);
            $array["proycatagentes"] = $proycatagentes;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarPryProyCatAgentes(Request $request)
    {
        $idProyCatAgente = $request->idProyCatAgente;
        $IDPersona = $request->IDPersona;
        $IDProyecto = $request->IDProyecto;
        $accion = $request->accion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            if($accion === 1) {
                DB::table('mc_pry_proycatagentes')->insert(['IDPersona' => $IDPersona, 'IDProyecto' => $IDProyecto]);
            }
            else {
                DB::table('mc_pry_proycatagentes')->where("id", $idProyCatAgente)->update(['IDPersona' => $IDPersona, 'IDProyecto' => $IDProyecto]);
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function borrarPryProyCatAgente(Request $request)
    {
        $idProyCatAgente = $request->idProyCatAgente;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            DB::table('mc_pry_proycatagentes')->where("id", $idProyCatAgente)->delete();
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function getPryProyActividadesInfo(Request $request)
    {
        $IdProyecto = $request->IdProyecto;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $proyectos = DB::select('SELECT mc_pry_proyectos.*, mc_pry_agentes.Agente FROM mc_pry_proyectos INNER JOIN mc_pry_agentes ON mc_pry_proyectos.idAgente = mc_pry_agentes.id WHERE mc_pry_proyectos.id = ? ORDER BY mc_pry_proyectos.FecUltAccion DESC', [$IdProyecto]);
            for($x=0 ; $x<count($proyectos) ; $x++) {
                $proyactividades = DB::select('SELECT * FROM mc_pry_proyactividades WHERE IdProyecto = ? ORDER BY Pos', [$proyectos[$x]->id]);
                $proyectos[$x]->Actividades = $proyactividades;
                $proyectos[$x]->Acciones = DB::select('SELECT mc_pry_proyacciones.*, mc_pry_proyactividades.Actividad FROM mc_pry_proyacciones
                LEFT JOIN mc_pry_proyactividades ON mc_pry_proyacciones.idactividad = mc_pry_proyactividades.id WHERE mc_pry_proyacciones.idproyecto = ? ORDER BY mc_pry_proyacciones.fecha DESC', [$proyectos[$x]->id]);
                $proyectos[$x]->Documentos = DB::select("SELECT mc_pry_proydocumentos.*, 
                IF(ISNULL(mc_pry_proyactividades.Actividad),'Sin Actividad', mc_pry_proyactividades.Actividad) AS Actividad,
                IF(ISNULL(mc_pry_proyacciones.fecha),'Sin Acción', mc_pry_proyacciones.fecha) AS Accion FROM mc_pry_proydocumentos
                LEFT JOIN mc_pry_proyactividades ON mc_pry_proydocumentos.idactividad = mc_pry_proyactividades.id 
                LEFT JOIN mc_pry_proyacciones ON mc_pry_proydocumentos.idaccion = mc_pry_proyacciones.id
                WHERE mc_pry_proydocumentos.idproyecto = ?", [$proyectos[$x]->id]);
                $proyectos[$x]->Planes = DB::select("SELECT mc_pry_proyplanes.*, mc_pry_agentes.Agente,
                IF(ISNULL(mc_pry_proyactividades.Actividad),'Sin Actividad', mc_pry_proyactividades.Actividad) AS Actividad FROM mc_pry_proyplanes
                INNER JOIN mc_pry_agentes ON mc_pry_proyplanes.idagente = mc_pry_agentes.id
                LEFT JOIN mc_pry_proyactividades ON mc_pry_proyplanes.idactividades = mc_pry_proyactividades.id  WHERE mc_pry_proyplanes.idproyecto = ?", [$proyectos[$x]->id]);
                /* $proyectos[$x]->AgentesPersonas = DB::select('SELECT mc_pry_proyectos.id AS idProyecto, mc_pry_proyectos.Proyecto,
                mc_pry_agentes.id AS idPersona, mc_pry_agentes.Agente FROM mc_pry_proycatagentes
                INNER JOIN mc_pry_proyectos ON mc_pry_proycatagentes.IDProyecto = mc_pry_proyectos.id
                INNER JOIN mc_pry_agentes ON mc_pry_proycatagentes.IDPersona = mc_pry_agentes.id WHERE mc_pry_proycatagentes.IDProyecto = ?', [$proyectos[$x]->id]); */
                $proyectos[$x]->AgentesPersonas = DB::select('SELECT mc_pry_agentes.*, 0 AS idActividad, 0 AS idAccion FROM mc_pry_agentes INNER JOIN mc_pry_proyectos
                ON mc_pry_agentes.id = mc_pry_proyectos.idAgente
                WHERE mc_pry_proyectos.id = ?
                UNION 
                SELECT mc_pry_agentes.*, mc_pry_proyactividades.id AS idActividad, 0 AS idAccion FROM mc_pry_agentes 
                INNER JOIN mc_pry_proyactividades ON mc_pry_agentes.id = mc_pry_proyactividades.idAgente 
                WHERE mc_pry_proyactividades.IdProyecto = ?
                UNION 
                SELECT mc_pry_agentes.*, mc_pry_proypersonas.idactividad AS idActividad, mc_pry_proypersonas.idaccion AS idAccion FROM mc_pry_agentes 
                INNER JOIN mc_pry_proypersonas ON mc_pry_agentes.id = mc_pry_proypersonas.idpersona 
                WHERE mc_pry_proypersonas.Idproyecto = ?
                UNION 
                SELECT mc_pry_agentes.*, 0 AS idActividad, 0 AS idAccion FROM mc_pry_agentes 
                INNER JOIN mc_pry_proycatagentes ON mc_pry_agentes.id = mc_pry_proycatagentes.IDPersona 
                WHERE mc_pry_proycatagentes.IDProyecto = ?', [$proyectos[$x]->id, $proyectos[$x]->id, $proyectos[$x]->id, $proyectos[$x]->id]);
            }

            $array["actividadesInfo"] = $proyectos;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarPryProyActividad(Request $request)
    {
        $idProyActividad = $request->idProyActividad;
        $IdProyecto = $request->IdProyecto;
        /* $Pos = $request->Pos;
        $Nivel = $request->Nivel; */
        $Actividad = $request->Actividad;
        $FecIni = $request->FecIni;
        $FecFin = $request->FecFin;
        $idAgente = $request->idAgente;
        $Avance = $request->Avance;
        $Estatus = $request->Estatus;
        $FecUltAccion = $request->FecUltAccion;
        $idActividadSelected = $request->idActividadSelected;
        $posicionActividadNueva = $request->posicionActividadNueva;
        $nivelActividadNueva = $request->nivelActividadNueva;
        $accion = $request->accion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            if($accion === 1) {
                if($idActividadSelected != 0) {
                    DB::table('mc_pry_proyactividades')->where("Pos", ">=" ,$posicionActividadNueva)->update(['Pos' => DB::raw('Pos + 1')]);
                    $Pos = $posicionActividadNueva;
                }
                else {
                    $posSiguiente = DB::select('SELECT Pos + 1 AS PosSiguiente FROM mc_pry_proyactividades ORDER BY Pos DESC LIMIT 1');
                    $Pos = count($posSiguiente) > 0 ? $posSiguiente[0]->PosSiguiente : 0;
                }
                DB::table('mc_pry_proyactividades')->insert(['IdProyecto' => $IdProyecto, 'Pos' => $Pos, 'Nivel' => $nivelActividadNueva, 'Actividad' => $Actividad, 'FecIni' => $FecIni, 'FecFin' => $FecFin, 'idAgente' => $idAgente, 'Avance' => $Avance, 'Estatus' => $Estatus, 'FecUltAccion' => $FecUltAccion]);
            }
            else {
                DB::table('mc_pry_proyactividades')->where("id", $idProyActividad)->update(['IdProyecto' => $IdProyecto/* , 'Pos' => $Pos, 'Nivel' => $Nivel */, 'Actividad' => $Actividad, 'FecIni' => $FecIni, 'FecFin' => $FecFin, 'idAgente' => $idAgente, 'Avance' => $Avance, 'Estatus' => $Estatus, 'FecUltAccion' => $FecUltAccion]);
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function modificarInfoPryProyActividad(Request $request)
    {
        $idProyecto = $request->idProyecto;
        $idProyActividad = $request->idProyActividad;
        $FecUltAccion = $request->FecUltAccion;
        $accion = $request->accion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            if($accion === 1) {
                $PosActual = $request->PosActual;
                $PosNueva = $request->PosNueva;
                DB::table('mc_pry_proyactividades')->where("IdProyecto", $idProyecto)->where("Pos", $PosNueva)->update(['Pos' => $PosActual, 'FecUltAccion' => $FecUltAccion]);
                DB::table('mc_pry_proyactividades')->where("id", $idProyActividad)->update(['Pos' => $PosNueva, 'FecUltAccion' => $FecUltAccion]);
            }
            else {
                $Nivel = $request->Nivel;
                DB::table('mc_pry_proyactividades')->where("id", $idProyActividad)->update(['Nivel' => $Nivel, 'FecUltAccion' => $FecUltAccion]);
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function borrarPryProyActividad(Request $request)
    {
        $idProyActividad = $request->idProyActividad;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            DB::table('mc_pry_proyactividades')->where("id", $idProyActividad)->delete();
            DB::table('mc_pry_proyacciones')->where("idactividad", $idProyActividad)->delete();
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarPryProyAccion(Request $request)
    {
        $idProyAccion = $request->idProyAccion;
        $idproyecto = $request->idproyecto;
        $idactividad = $request->idactividad;
        $nombre = $request->nombre;
        $fecha = $request->fecha;
        $Avance = $request->Avance;
        $estatus = $request->estatus;
        $agentesPersonas = $request->agentesPersonas;
        $accion = $request->accion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            if($accion === 1) {
                $idaccion = DB::table('mc_pry_proyacciones')->insertGetId(['idproyecto' => $idproyecto, 'idactividad' => $idactividad, 'nombre' => $nombre, 'fecha' => $fecha, 'Avance' => $Avance, 'estatus' => $estatus]);
                for($x=0 ; $x<count($agentesPersonas) ; $x++) {
                    DB::table('mc_pry_proypersonas')->insert(['idproyecto' => $idproyecto, 'idactividad' => $idactividad, 'idaccion' => $idaccion, 'idpersona' => $agentesPersonas[$x]]);
                    $busquedaPersonaProyecto = DB::select("SELECT * FROM mc_pry_proycatagentes WHERE IDPersona = ? AND IDProyecto = ?", [$agentesPersonas[$x], $idproyecto]);
                    if(count($busquedaPersonaProyecto) === 0) {
                        DB::table('mc_pry_proycatagentes')->insert(['IDPersona' => $agentesPersonas[$x], 'IDProyecto' => $idproyecto]);
                    }
                }
            }
            else {
                DB::table('mc_pry_proyacciones')->where("id", $idProyAccion)->update(['idproyecto' => $idproyecto, 'idactividad' => $idactividad, 'nombre' => $nombre, 'fecha' => $fecha, 'Avance' => $Avance, 'estatus' => $estatus]);
                $nuevaAccion = DB::select("SELECT mc_pry_proyacciones.*,  IF(ISNULL(mc_pry_proyactividades.Actividad),'Sin Actividad', mc_pry_proyactividades.Actividad) AS Actividad
                FROM mc_pry_proyacciones LEFT JOIN mc_pry_proyactividades ON mc_pry_proyacciones.idactividad = mc_pry_proyactividades.id 
                WHERE mc_pry_proyacciones.id = ?", [$idProyAccion]);
                $array["nuevaAccion"] = $nuevaAccion;
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function borrarPryProyAccion(Request $request)
    {
        $idProyAccion = $request->idProyAccion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            DB::table('mc_pry_proyacciones')->where("id", $idProyAccion)->delete();
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarPryProyPersonas(Request $request)
    {
        $idproyecto = $request->idproyecto;
        /* $idactividad = $request->idactividad;
        $idaccion = $request->idaccion; */
        $idsAgentespersonas = $request->idsAgentespersonas;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            for($x=0 ; $x<count($idsAgentespersonas) ; $x++) {
                /* DB::table('mc_pry_proypersonas')->insert(['idproyecto' => $idproyecto, 'idactividad' => $idactividad, 'idaccion' => $idaccion, 'idpersona' => $idsAgentespersonas[$x]]); */
                DB::table('mc_pry_proycatagentes')->insert(['IDPersona' => $idsAgentespersonas[$x], 'IDProyecto' => $idproyecto]);
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarPryProyDocumento(Request $request)
    {
        $idmenu = $request->idmenu;
        $idmodulo = $request->idmodulo;
        $idUsuario = $request->idUsuario;
        $codigoArchivo = $request->codigoArchivo;
        $idproyecto = $request->idproyecto;
        $idactividad = $request->idactividad;
        $idaccion = $request->idaccion;
        $documentos = $request->file();
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $validaCarpetas = getExisteCarpeta($idmodulo, $idmenu, $request->idsubmenu);
            $array["error"] = $validaCarpetas[0]["error"];
            if ($validaCarpetas[0]['error'] == 0) {
                $carpetamodulo = $validaCarpetas[0]['carpetamodulo'];
                $carpetamenu = $validaCarpetas[0]['carpetamenu'];
                $carpetasubmenu = $validaCarpetas[0]['carpetasubmenu'];
                $x=0;
                $y=0;
                $servidor = getServidorNextcloud();
                $datosempresa = DB::connection("General")->select("SELECT usuario_storage, password_storage FROM mc1000 WHERE RFC = '$request->rfc'");
                $u_storage = $datosempresa[0]->usuario_storage;
                $p_storage = $datosempresa[0]->password_storage;
                foreach ($documentos as $key => $file) {
                    $archivo = $file->getClientOriginalName();

                    $codigoarchivo = /* $request->rfc . "_" .  */$codigoArchivo . "_" . $idUsuario . "_";

                    $resultado = subirArchivoNextcloud($archivo, $file, $request->rfc, $servidor, $u_storage, $p_storage, $carpetamodulo, $carpetamenu, $carpetasubmenu, $codigoarchivo, $x);
                    if ($resultado["archivo"]["error"] == 0) {
                        $codigodocumento = $codigoarchivo . $x;
                        $type = explode(".", $archivo);
                        $directorio = $request->rfc . '/' . $carpetamodulo . '/' . $carpetamenu . '/' . $carpetasubmenu;
                        $target_path = $directorio . '/' . $codigodocumento . "." . $type[count($type) - 1];
                        $link = GetLinkArchivo($target_path, $servidor, $u_storage, $p_storage);
                        DB::table('mc_pry_proydocumentos')->insert(['idproyecto' => $idproyecto, 'idactividad' => $idactividad, 'idaccion' => $idaccion, 'NombreDocumento' => $codigodocumento, 'ExtencionDocumento' => ".".$type[count($type) - 1], 'rutaDocumento' => $directorio, 'LinkDocumento' => $link]);
                        $array["archivo"][$y] = $archivo;
                        $array["statusDocumentos"][$y] = $link != "" ? 1 : 0;
                        $y++;
                    }
                    $x++;
                }
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function modificarActividadAccionPryProyDocumento(Request $request)
    {
        $iddocumento = $request->iddocumento;
        $idactividad = $request->idactividad;
        $idaccion = $request->idaccion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            DB::table('mc_pry_proydocumentos')->where("id", $iddocumento)->update(['idactividad' => $idactividad, 'idaccion' => $idaccion]);
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function descargarDocumentosPryProyDocumento(Request $request)
    {
        $idmodulo = $request->idmodulo;
        $idmenu = $request->idmenu;
        $idusuario = $request->idusuario;
        $iddocumentos = $request->iddocumentos;
        $fechaActual = $request->fechaActual;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $carpetadestino = $_SERVER['DOCUMENT_ROOT'] . '/public/archivostemp/';
            mkdir($carpetadestino . "DOCSPlanes_" . $request->rfc . "_" . $idusuario ."_". $fechaActual, 0700);
            $carpetadestino = $carpetadestino . "DOCSPlanes_" . $request->rfc . "_" . $idusuario ."_". $fechaActual . "/";
            for($x=0 ; $x<count($iddocumentos) ; $x++) {
                $documentos = DB::select('SELECT * FROM mc_pry_proydocumentos WHERE id = ?', [$iddocumentos[$x]]);
                $urldestino = $carpetadestino . "Doc_".($x + 1).$documentos[0]->ExtencionDocumento;
                $archivo = fopen($documentos[0]->LinkDocumento."/download", "rb");
                if ($archivo) {
                    $nuevoArchivo = fopen($urldestino, "a");
                    if ($nuevoArchivo) {
                        while (!feof($archivo)) {
                            fwrite($nuevoArchivo, fread($archivo, 1024 * 8), 1024 * 8);
                        }
                        fclose($nuevoArchivo);
                    }
                }
                fclose($archivo);
            }

            $zip = new ZipArchive();
            $zipname = "layouts.zip";
            $zip->open($zipname, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $da = opendir($carpetadestino);
            $archibosaborrar = [];
            $y = 0;
            while (($archivo = readdir($da)) !== false) {
                if (is_file($carpetadestino . $archivo) && $archivo != "." && $archivo != ".." && $archivo != $zipname) {
                    $zip->addFile($carpetadestino . $archivo, $archivo);
                    $archibosaborrar[$y] = $carpetadestino . $archivo;
                    $y++;
                }
            }
            closedir($da);
            $zip->close();
            $rutaFinal = $carpetadestino;
            rename($zipname, "$rutaFinal/$zipname");

            $validaCarpetas = getExisteCarpeta($idmodulo, $idmenu, $request->idsubmenu);
            $array["error"] = $validaCarpetas[0]["error"];
            if ($validaCarpetas[0]['error'] == 0) {
                $carpetamodulo = $validaCarpetas[0]['carpetamodulo'];
                $carpetamenu = $validaCarpetas[0]['carpetamenu'];
                $carpetasubmenu = $validaCarpetas[0]['carpetasubmenu'];

                $servidor = getServidorNextcloud();
                $datosempresa = DB::connection("General")->select("SELECT usuario_storage, password_storage FROM mc1000 WHERE RFC = '$request->rfc'");
                $u_storage = $datosempresa[0]->usuario_storage;
                $p_storage = $datosempresa[0]->password_storage;

                $codigoarchivo = $fechaActual . $idusuario;
                $consecutivo = "";
                $resultado = subirArchivoNextcloud($zipname, "$rutaFinal/$zipname", $request->rfc, $servidor, $u_storage, $p_storage, $carpetamodulo, $carpetamenu, $carpetasubmenu, $codigoarchivo, $consecutivo);
                /* $array["resultado"] = $resultado; */
                if ($resultado["archivo"]["error"] == 0) {
                    $codigodocumento = $codigoarchivo . $consecutivo;
                    $directorio = $request->rfc . '/' . $carpetamodulo . '/' . $carpetamenu . '/' . $carpetasubmenu;
                    $target_path = $directorio . '/' . $codigodocumento . ".zip";
                    /* $array["target_path"] = $target_path; */
                    $link = GetLinkArchivo($target_path, $servidor, $u_storage, $p_storage);
                    $array["link"] = $link;
                    unlink($carpetadestino . $zipname);
                }

                for ($x = 0; $x < count($archibosaborrar); $x++) {
                    unlink($archibosaborrar[$x]);
                }
            }

            $urlcarpetaaborrar = substr($carpetadestino, 0, -1);
            rmdir($urlcarpetaaborrar);
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function borrarPryProyDocumento(Request $request) {
        $idsPryProyDocumento = $request->idsPryProyDocumento;
        $rutasDocumentos = $request->rutasDocumentos;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $servidor = getServidorNextcloud();
            $DatosEmpresa = DB::connection("General")->select("SELECT usuario_storage, password_storage FROM mc1000 WHERE RFC = '$request->rfc'");
            $usuariostorage = $DatosEmpresa[0]->usuario_storage;
            $passwordstorage = $DatosEmpresa[0]->password_storage;
            for($x=0 ; $x<count($idsPryProyDocumento) ; $x++) {
                $datosEliminacionDocumentoPublicacion = eliminaArchivoNextcloud($servidor, $usuariostorage, $passwordstorage, $rutasDocumentos[$x]);
                $array["datosEliminacionDocumentoPublicacion"][$x] = $datosEliminacionDocumentoPublicacion;
                DB::table('mc_pry_proydocumentos')->where("id", $idsPryProyDocumento[$x])->delete();
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarPryProyPlan(Request $request)
    {
        $idProyPlan = $request->idProyPlan;
        $idproyecto = $request->idproyecto;
        $idactividades = $request->idactividades;
        $nombre = $request->nombre;
        $fecini = $request->fecini;
        $fecfin = $request->fecfin;
        $idagente = $request->idagente;
        $accion = $request->accion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            if($accion === 1) {
                DB::table('mc_pry_proyplanes')->insert(['idproyecto' => $idproyecto, 'idactividades' => $idactividades, 'nombre' => $nombre, 'fecini' => $fecini, 'fecfin' => $fecfin, 'idagente' => $idagente]);
            }
            else {
                DB::table('mc_pry_proyplanes')->where("id", $idProyPlan)->update(['idproyecto' => $idproyecto, 'idactividades' => $idactividades, 'nombre' => $nombre, 'fecini' => $fecini, 'fecfin' => $fecfin, 'idagente' => $idagente]);
                $nuevoPlan = DB::select("SELECT mc_pry_proyplanes.*,  IF(ISNULL(mc_pry_proyactividades.Actividad),'Sin Actividad', mc_pry_proyactividades.Actividad) AS Actividad
                FROM mc_pry_proyplanes LEFT JOIN mc_pry_proyactividades ON mc_pry_proyplanes.idactividades = mc_pry_proyactividades.id WHERE mc_pry_proyplanes.id = ?", [$idProyPlan]);
                $array["nuevoPlan"] = $nuevoPlan;
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function borrarPryProyPlan(Request $request)
    {
        $idProyPlan = $request->idProyPlan;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            DB::table('mc_pry_proyplanes')->where("id", $idProyPlan)->delete();
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function getPryProyDocumentos(Request $request)
    {
        $IdProyecto = $request->IdProyecto;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $proyectosDocumentos = DB::select('SELECT * FROM mc_pry_proyectos ORDER BY id');
            for($x=0 ; $x<count($proyectosDocumentos) ; $x++) {
                $proyectosDocumentos[$x]->documentos = DB::select('SELECT mc_pry_proydocumentos.*, mc_pry_proyectos.Proyecto
                FROM mc_pry_proydocumentos INNER JOIN mc_pry_proyectos ON mc_pry_proydocumentos.idproyecto = mc_pry_proyectos.id WHERE mc_pry_proydocumentos.idproyecto = ? ORDER BY mc_pry_proydocumentos.idproyecto', [$proyectosDocumentos[$x]->id]);
            }

            $array["proyectosDocumentos"] = $proyectosDocumentos;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}