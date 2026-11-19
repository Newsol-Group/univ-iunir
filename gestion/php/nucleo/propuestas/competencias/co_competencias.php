<?php
class co_competencias
{
	/**
	 * Obtiene el listado de competencias según filtro especificado
	 *
	 */
	function get_listado($where=' ')
	{
		if (trim($where) != '' ) {
			$where = ' AND '.$where.' ';
		}

		$sql = "SELECT 		sga_competencias_ingreso.competencia,
							sga_competencias_ingreso.nombre,
				            sga_competencias_ingreso_tipos.nombre	AS tipo_nombre
				FROM 		sga_competencias_ingreso,
							sga_competencias_ingreso_tipos
				WHERE		sga_competencias_ingreso.tipo = sga_competencias_ingreso_tipos.tipo
							$where
				ORDER BY	sga_competencias_ingreso_tipos.nombre,
							sga_competencias_ingreso.nombre
				"; 
  				 
		return guarani_db::consultar($sql);
	}
	
	function get_competencia_nombre_tipo($competencia)
	{
		$competencia = toba::db()->quote($competencia);
		
		$sql = "SELECT 		sga_competencias_ingreso.competencia,
							sga_competencias_ingreso.nombre,
				            sga_competencias_ingreso_tipos.nombre	AS tipo_nombre
				FROM 		sga_competencias_ingreso,
							sga_competencias_ingreso_tipos
				WHERE		sga_competencias_ingreso.tipo = sga_competencias_ingreso_tipos.tipo
							AND sga_competencias_ingreso.competencia = $competencia
				ORDER BY	sga_competencias_ingreso.nombre						
				"; 
  				 
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene el listado de tipos de competencias
	 * de la tabla sga_competencias_ingreso_tipos
	 */
	function get_listado_tipos_competencias()
	{
		$sql = 'SELECT 		sga_competencias_ingreso_tipos.tipo		AS valor,
							sga_competencias_ingreso_tipos.nombre	AS descr
				FROM 		sga_competencias_ingreso_tipos
				ORDER BY	sga_competencias_ingreso_tipos.nombre
				'; 
  				 
		return guarani_db::consultar($sql);
	}
	
	
	/**
	 * Obtiene el listado de tipos de competencias
	 * de la tabla sga_competencias_ingreso_tipos
	 */
	function get_listado_resultado_alcance()
	{
		$sql = 'SELECT 		sga_resultados_alcance.alcance		AS valor,
							sga_resultados_alcance.nombre	AS descr
				FROM 		sga_resultados_alcance
				ORDER BY	sga_resultados_alcance.nombre
				'; 
  				 
		return guarani_db::consultar($sql);
	}
	
	/**
     *  Valida si un nombre de competencia se puede usar o no.
     */
	function validar_nombre_competencia($nombre, $competencia)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);		
		$sql = 'SELECT	count(sga_competencias_ingreso.competencia) as cant
                FROM 	sga_competencias_ingreso
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_competencias_ingreso.nombre', $nombre).'
				';
		
		if (!is_null($competencia)) {
			$competencia = toba::db()->quote($competencia);
			$sql .= " AND sga_competencias_ingreso.competencia <> $competencia ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);		
	}
	
	function get_resultados_area($filtro=false)
	{
		$sql = "
			select	resultado_campo,
					resultado,
					ambito,
					campo_disciplinar,
					orden_listado
			from	sga_resultados_x_campo
		";
		if($filtro) {
			$sql .= " where ".$filtro;
		}
		$sql .= " order by orden_listado";
		return guarani_db::consultar($sql);
	}
	
	function get_actividades($plan_version)
	{
		$sql = "select 	distinct
						get_plan_version_contenido.elemento,
						get_plan_version_contenido.nombre
					from get_plan_version_contenido(".toba::db()->quote($plan_version).", false)
					where 	(get_plan_version_contenido.entidad_tipo = ".elemento::tipo_actividad." or
						(get_plan_version_contenido.entidad_tipo = ".elemento::tipo_modulo." and get_plan_version_contenido.entidad_subtipo = ".elemento::subtipo_materia_generica." ))";
		
		return guarani_db::consultar($sql);
	}
	
	function get_resultados_actividades($actividad=false)
	{
		$sql = "select 	elemento,
						resultado,
						porc_alcanzado
				from	sga_resultados_actividades";
		if($actividad)
		{
			$sql .= " where elemento = ".toba::db()->quote($actividad);
		}
		
		return guarani_db::consultar($sql);
	}
	
	function get_certificados($plan_version=false)
	{
		$sql = "select 	distinct
						sga_certificados.certificado,
						sga_certificados.nombre
					from sga_certificados";
		if($plan_version)
		{
			$sql .= ", sga_planes_certificados 
			where 	sga_planes_certificados.certificado = sga_certificados.certificado 
					and sga_planes_certificados.plan_version = ".toba::db()->quote($plan_version);
		}
		
		return guarani_db::consultar($sql);
	}
	
	function get_resultados_certificados($certificado=false)
	{
		$sql = "select 	certificado,
						resultado
				from	sga_resultados_certificados";
		if($certificado)
		{
			$sql .= " where certificado = ".toba::db()->quote($certificado);
		}
		
		return guarani_db::consultar($sql);
	}
	
	function get_competencias_x_actividad($filtro=false)
	{
		$sql = "SELECT 	sga_elementos.elemento,
						'('||sga_elementos.codigo||') '||sga_elementos.nombre as nombre, 
        				sga_resultados_x_campo.orden_listado,
						sga_resultados_aprendizaje.nombre as resultado_alcanzado, 
						sga_resultados_actividades.porc_alcanzado, 
						sga_resultados_tipos.descripcion, 
						sga_resultados_ambitos.ambito as ambito_id,
						sga_resultados_ambitos.nombre as ambito, 
						sga_campo_disciplinar.campo_disciplinar as area_tematica_id,
        				sga_campo_disciplinar.nombre as area_tematica 

				FROM 	sga_elementos, 
						sga_resultados_aprendizaje, 
				        sga_resultados_actividades, 
				        sga_resultados_tipos, 
				        sga_resultados_ambitos, 
				        sga_resultados_x_campo, 
				        sga_campo_disciplinar

				WHERE 	sga_elementos.elemento = sga_resultados_actividades.elemento 
						and sga_resultados_aprendizaje.resultado = sga_resultados_actividades.resultado 
				        and sga_resultados_aprendizaje.resultado_tipo = sga_resultados_tipos.resultado_tipo 
				        and sga_resultados_x_campo.resultado = sga_resultados_aprendizaje.resultado
				        and sga_resultados_x_campo.ambito = sga_resultados_ambitos.ambito
				        and sga_campo_disciplinar.campo_disciplinar = sga_resultados_x_campo.campo_disciplinar
			";
		
		if($filtro)
		{
			$sql .= ' and '.$filtro;
		}
		
		$sql .= ' ORDER BY	sga_elementos.elemento, 
							sga_resultados_ambitos.ambito, 
				            sga_campo_disciplinar.nombre,				            
				            sga_resultados_x_campo.orden_listado';

		return guarani_db::consultar($sql);
	}
	
	function get_competencias_otorgadas($where='')
	{
		if ($where) {
			$where = 'AND '.$where;
		}
		
		$sql = "SELECT 	-- Corte de control: Propuesta
						sga_propuestas.propuesta,
						'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre_abreviado as propuesta_descr,
						-- Corte de control: Plan / Versión
				        sga_competencias_x_actividad.plan_version,
				    	'Plan: (' || sga_planes.codigo || ') ' || sga_planes.nombre || ' - Estado: ' || sga_planes_estados.descripcion || ' - Versión: ' || sga_planes_versiones.nombre || ' - Estado Versión: ' || estado_version.descripcion as plan_descr,
				    	-- Elementos del reporte
				        sga_elementos.nombre as actividad_descr,
				        sga_resultados_aprendizaje.nombre as resultado_descr,
				    	sga_escalas_cumplimiento_det.descripcion as nivel_descr
				FROM 	sga_competencias_x_actividad,
						sga_planes_versiones,
				        sga_planes,
				        sga_planes_estados,
				        sga_planes_estados as estado_version,
				        sga_propuestas,
				        sga_elementos,
				        sga_resultados_aprendizaje,
				        sga_escalas_cumplimiento_det
				WHERE	sga_competencias_x_actividad.plan_version = sga_planes_versiones.plan_version
						AND sga_planes_versiones.plan = sga_planes.plan
				        AND sga_planes.estado = sga_planes_estados.estado
				        AND sga_planes_versiones.estado = estado_version.estado
				        AND sga_planes.propuesta = sga_propuestas.propuesta
				        AND sga_competencias_x_actividad.elemento = sga_elementos.elemento 
				        AND sga_competencias_x_actividad.resultado = sga_resultados_aprendizaje.resultado
				        AND sga_competencias_x_actividad.nivel_escala = sga_escalas_cumplimiento_det.nivel_escala
				        $where
				ORDER BY
						sga_propuestas.propuesta,
				        sga_competencias_x_actividad.plan_version,
				        sga_elementos.nombre
				";
		
		return guarani_db::consultar($sql);
	}
}
?>