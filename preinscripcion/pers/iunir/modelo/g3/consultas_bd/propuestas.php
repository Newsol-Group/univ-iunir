<?php

namespace iunir\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\g3\interface_propuestas;
use siu\modelo\util;
use siu\modelo\propuesta;
use siu\modelo\opciones;
use siu\modelo\planes;
use siu\modelo\periodo_de_inscripcion;
use siu\modelo\comunes;
use siu\modelo\g3\consultas_bd\propuestas as propuestas_base;

class propuestas extends propuestas_base
{
	function get_propuestas_disponibles_preinscripcion($ra, $excluir = array())
	{
		$si = kernel::db()->quote(comunes::si);
		$ra = kernel::db()->quote($ra);
		
		if (!empty($excluir)) {
			$where_excluir = ' AND sga_propuestas.propuesta NOT IN (' . implode(',', $excluir) . ')';
		} else {
			$where_excluir = '';
		}
		
		$filtro_perfil_datos = $this->filtro_perfil_datos();
		if (!empty($filtro_perfil_datos)) {
			$where_perfil_datos = ' AND ' . $filtro_perfil_datos;
		} else {
			$where_perfil_datos = '';
		}
		
		$sql = "SELECT	DISTINCT 
					sga_planes.propuesta,
					(case sga_propuestas.propuesta when 2 then '1° Ciclo Lic. en Instrumentación Quirúrgica' 
					when 32 then '1° Ciclo Lic. en Enfermería' 
					when 1 then '2° Ciclo Lic. en Enfermería' 
					when 35 then '2° Ciclo Lic. en Instrumentación Quirúrgica' else sga_propuestas.nombre end) as nombre
				FROM 	sga_propuestas_ra
				JOIN 	sga_propuestas		ON (sga_propuestas.propuesta = sga_propuestas_ra.propuesta)
				JOIN	sga_planes		ON (sga_planes.propuesta = sga_propuestas.propuesta)
				JOIN	sga_planes_versiones	ON (sga_planes_versiones.plan = sga_planes.plan)
				JOIN	sga_periodos_inscripcion_aplanado 	ON (sga_periodos_inscripcion_aplanado.plan_version = sga_planes_versiones.plan_version)
				JOIN	sga_periodos_inscripcion_fechas	ON (sga_periodos_inscripcion_fechas.periodo_insc = sga_periodos_inscripcion_aplanado.periodo_insc)
				JOIN	sga_periodos_inscripcion		ON (sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion)
				JOIN	sga_periodos_inscripcion_propuesta	ON (sga_periodos_inscripcion_propuesta.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion)

				WHERE	sga_propuestas_ra.responsable_academica = $ra
				AND sga_periodos_inscripcion_fechas.habilitado = 'S'
				AND sga_periodos_inscripcion_fechas.habilitado_interfaz IN (0,3)
				AND CURRENT_DATE BETWEEN sga_periodos_inscripcion_fechas.fecha_inicio AND sga_periodos_inscripcion_fechas.fecha_fin
				AND sga_periodos_inscripcion_propuesta.periodo_insc_tipo IN ('3', '4') -- Períodos de tipo: 'Todos' y 'Preinscripcion'
				AND sga_propuestas.propuesta_tipo <> '" . propuesta::tipo_CURSOS . "'
				AND sga_planes.inscripcion_habilitada = $si
				$where_excluir
				$where_perfil_datos
				";
		
		$datos = $this->consultar($sql);
		
		$salida = util::opciones()->armar_combo_opciones($datos, 'propuesta', 'nombre', true, false);
		if (empty($salida)) {
			$salida[opciones::NO_SELECCION] = kernel::traductor()->trans('sin_propuestas');
		}
		return $salida;
	}	
	
}
