<?php
namespace siu\modelo\datos\db;

use siu\modelo\_comun\comunes;
use SIU\Chulupi\kernel;

class zona_examenes
{
	/**
	 *
	 * parametros: persona, filtrar_por, fecha_examen, fecha_examen_desde, fecha_examen_hasta
	 * param_null: filtrar_por, fecha_examen, fecha_examen_desde, fecha_examen_hasta
	 * no_quote: filtrar_por, fecha_examen, fecha_examen_desde, fecha_examen_hasta
	 * cache: memoria
	 * filas: n
	 */
	function lista_mesas_examen_docente($parametros)
	{

		$filtro_fecha = "AND vw_mesas_examen.turno_examen_fecha_inactivacion >= CURRENT_DATE";

		if ($parametros['filtrar_por'] == 'f') {
			if (!is_null($parametros['fecha_examen'])) {
				$filtro_fecha = "AND vw_mesas_examen.mesa_examen_fecha = " . kernel::db()->quote($parametros['fecha_examen']);
			}
		} elseif ($parametros['filtrar_por'] == 'r') {

			if (!is_null($parametros['fecha_examen_desde']) && !is_null($parametros['fecha_examen_hasta'])) {
				$filtro_fecha = "AND vw_mesas_examen.mesa_examen_fecha BETWEEN " . kernel::db()->quote($parametros['fecha_examen_desde']) . " AND " . kernel::db()->quote($parametros['fecha_examen_hasta']);
			} elseif (!is_null($parametros['fecha_examen_desde'])) {
				$filtro_fecha = "AND vw_mesas_examen.mesa_examen_fecha >= " . kernel::db()->quote($parametros['fecha_examen_desde']);
			} elseif (!is_null($parametros['fecha_examen_hasta'])) {
				$filtro_fecha = "AND vw_mesas_examen.mesa_examen_fecha <= " . kernel::db()->quote($parametros['fecha_examen_hasta']);
			}
		}

		// Lista mesas de examen donde se encuentra el docente.
		// Mesas de examen dentro y fuera de calendario
		$sql = "SELECT		vw_mesas_examen.mesa_examen_nombre as mesa_examen_nombre,
							vw_mesas_examen.mesa_examen as mesa_examen,
							vw_mesas_examen.llamado_mesa as llamado_mesa,
							vw_mesas_examen.mesa_examen_elemento as actividad,
							vw_actividades.codigo as actividad_codigo,
							vw_actividades.nombre as actividad_nombre,
                            vw_actividades.nombre || ' (' || vw_actividades.codigo || ')' AS actividad_descr,
							vw_mesas_examen.mesa_examen_ubicacion as ubicacion,
							sga_ubicaciones.nombre as ubicacion_nombre,
							vw_mesas_examen.turno_examen_nombre as turno_examen_nombre,
							vw_mesas_examen.llamado_nombre as llamado_nombre,
							vw_mesas_examen.mesa_examen_fecha as fecha,
							vw_mesas_examen.mesa_examen_hora_inicio as hora_inicio,
							to_char(vw_mesas_examen.mesa_examen_fecha,'" . comunes::formato_fecha_visual . "') || ' ' || to_char(cast(vw_mesas_examen.mesa_examen_hora_inicio as time), '" . comunes::formato_hora_visual . "') as fecha_formato_visual,
							f_espacios_llamado_mesa(vw_mesas_examen.llamado_mesa, 1) as espacio_nombre, 
							sga_evaluaciones.evaluacion as evaluacion,
							0 as solicitar_libro,
							(vw_mesas_examen.mesa_examen_fecha || ' ' || vw_mesas_examen.mesa_examen_hora_inicio)::timestamp as fecha_con_hora,
                                                        vw_mesas_examen.mesa_examen_en_turno as mesa_examen_en_turno
				FROM 		sga_docentes
							JOIN sga_docentes_mesa_llamado ON sga_docentes_mesa_llamado.docente = sga_docentes.docente
							JOIN vw_mesas_examen ON sga_docentes_mesa_llamado.llamado_mesa = vw_mesas_examen.llamado_mesa
							JOIN sga_evaluaciones ON sga_evaluaciones.entidad = vw_mesas_examen.llamado_mesa_entidad
							JOIN sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
							JOIN vw_actividades ON vw_actividades.elemento = vw_mesas_examen.mesa_examen_elemento
							JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_mesas_examen.mesa_examen_ubicacion
				WHERE 	    sga_evaluaciones_tipos.automatica = '".\comunes::si."'
							AND vw_mesas_examen.llamado_mesa_estado = '".\mesa_de_examen::mesa_estado_activo."'
							AND sga_docentes.persona = {$parametros['persona']} -- Mesas del docente
							$filtro_fecha
				ORDER BY	actividad_nombre, 
							vw_mesas_examen.mesa_examen_fecha DESC,
							vw_mesas_examen.mesa_examen_hora_inicio DESC
				";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

}