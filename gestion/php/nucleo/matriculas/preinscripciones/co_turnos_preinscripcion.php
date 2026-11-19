<?php
class co_turnos_preinscripcion
{
	/**
	 * Obtiene un listado de configuraciones de turnos de Preinscripción
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_listado_turnos_config($where = '')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		$sql = "SELECT	DISTINCT pre_turnos_config.turno_config,
						pre_turnos_config.fecha_inicio,
						pre_turnos_config.fecha_fin,
						pre_turnos_config.cant_operadores,
						pre_turnos_config.descripcion,
						f_responsables_academicas_turnos_preinsc(pre_turnos_config.turno_config) AS responsables_academicas,
						f_ubicaciones_turnos_preinsc(pre_turnos_config.turno_config) AS ubicaciones,
						(	SELECT	COUNT(1)
							FROM	pre_turnos
							WHERE	pre_turnos.turno_config = pre_turnos_config.turno_config) as cant_turnos,
						(	SELECT	COUNT(1)
							FROM	pre_turnos
							WHERE	pre_turnos.turno_config = pre_turnos_config.turno_config AND
									(id_preinscripcion IS NOT NULL OR persona IS NOT NULL)) as cant_turnos_ocupados,
						(	SELECT	COUNT(1)
							FROM	pre_turnos
							WHERE	pre_turnos.turno_config = pre_turnos_config.turno_config AND
									(pre_turnos.id_preinscripcion IS NULL AND pre_turnos.persona IS NULL) AND
									-- Turnos no vencidos
									(pre_turnos.fecha > CURRENT_DATE OR (pre_turnos.fecha = CURRENT_DATE AND pre_turnos.hora >= CURRENT_TIME))) as cant_turnos_disponibles
				FROM	pre_turnos_config
				$where
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Determina si algún turno creado a partir de una configuración dada ya fue 
	 * tomado por algún aspirante
	 * 
	 * @param int $turno_config
	 * @return boolean
	 */
	function hay_turnos_asignados($turno_config)
	{
		$turno_config = toba::db()->quote($turno_config);

		$sql = "SELECT 	COUNT(1) AS cant
				FROM 	pre_turnos
				WHERE 	turno_config = $turno_config
						AND (id_preinscripcion IS NOT NULL OR persona IS NOT NULL)";

		$res = guarani_db::consultar_fila($sql);
		return $res['cant'] > 0;
	}

	//Campo es persona o id_preinscripcion
	function get_turno($campo, $id, $ra)
	{
		$sql = "
                SELECT 
					pre_turnos.pre_turno,
					pre_turnos.turno_config,
					pre_turnos.id_preinscripcion,
					pre_turnos.persona,
					pre_turnos.responsable_academica,
					pre_turnos.operador,
					to_char(fecha, '" . comunes::formato_fecha_visual . "') as fecha,
					to_char(hora, '" . comunes::formato_hora_visual . "') as hora,
					pre_turnos.recordatorio_enviado
				FROM 
					pre_turnos
				WHERE
					pre_turnos.$campo = $id
					AND pre_turnos.responsable_academica = $ra
                ";
		return guarani_db::consultar_fila($sql, null, false);
	}

	function rep_listado_turnos_config($where = '')
	{
		if ($where)
			$where = 'WHERE (pre_turnos.persona IS NOT NULL OR pre_turnos.id_preinscripcion IS NOT NULL) AND' . $where;
		else
			$where = 'WHERE (pre_turnos.persona IS NOT NULL OR pre_turnos.id_preinscripcion IS NOT NULL)';

		$sql = "SELECT DISTINCT
					pre_turnos_config.turno_config,
					pre_turnos.fecha,
					pre_turnos.hora,					
					pre_turnos.fecha as fecha_sort,
					pre_turnos.id_preinscripcion,
					'OP ' || pre_turnos.operador AS operador,
					CASE 
					 WHEN (pre_turnos.persona IS NULL) THEN '' ELSE COALESCE( persona_aspirante.apellido_elegido, persona_aspirante.apellido ) || ', ' || COALESCE( persona_aspirante.nombres_elegido, persona_aspirante.nombres )
					END AS aspirante
				   FROM pre_turnos
					JOIN pre_turnos_config ON pre_turnos_config.turno_config = pre_turnos.turno_config
					LEFT JOIN mdp_personas as persona_aspirante ON persona_aspirante.persona = pre_turnos.persona
					JOIN pre_turnos_config_ra ON pre_turnos_config_ra.turno_config = pre_turnos_config.turno_config
					JOIN pre_turnos_config_modalidad ON pre_turnos_config_modalidad.turno_config = pre_turnos_config.turno_config
					JOIN pre_turnos_config_ubicacion ON pre_turnos_config_ubicacion.turno_config = pre_turnos_config.turno_config
					$where
					 ORDER by fecha_sort asc, hora asc
					 ;";
		return guarani_db::consultar($sql);
	}
	function rep_listado_turnos_pre_y_autogestion($datos = '')
	{
		$pre_3w = [];
		if (isset($datos)) {
			foreach ($datos as $valor) {
				//Guardo los que vienen de preinscripcion donde el aspirante es vacio
				if (empty($valor['aspirante'])) {
					$aspirante = current($this->get_datos_reporte_desde_preinscripcion($valor['id_preinscripcion']));
					if (!empty($aspirante)) {
						$valor['aspirante'] = $aspirante['aspirante'];
						array_push($pre_3w, $valor);
					}
				} else {
					//Guardo los que vienen de autogestion
					array_push($pre_3w, $valor);
				}
			}
		}
		return $pre_3w;
	}

	protected function get_datos_reporte_desde_preinscripcion($id_preinscripcion = '')
	{
		$datos = [];
		if(isset($id_preinscripcion)){
		// Se obtienen datos desde Preinscripción
			$sql = "SELECT 
						 COALESCE(sga_preinscripcion.apellido_elegido, sga_preinscripcion.apellido) || ', ' || COALESCE(sga_preinscripcion.nombres_elegido, sga_preinscripcion.nombres) AS aspirante
					FROM sga_preinscripcion					
					WHERE
						sga_preinscripcion.id_preinscripcion = $id_preinscripcion";
			
			$fuentes = toba::consulta_php('co_preinscripciones')->get_fuentes_datos_preinscripcion();
			
			foreach ($fuentes as $fuente) {
				$aux = current(guarani_db::consultar($sql, null, false, false, '', $fuente['fuente_de_datos']));
				if(!empty($aux)){
					array_push($datos, $aux);
				}				
			}
			
		}
		
		return $datos;
	
	}
	
	function get_combo_configuraciones()
	{
		$sql = "
                SELECT DISTINCT pre_turnos_config.turno_config AS valor, 
                pre_turnos_config.descripcion  || ' (' || to_char(pre_turnos_config.fecha_inicio,'DD/MM/YYYY') || ' al ' || to_char(pre_turnos_config.fecha_fin,'DD/MM/YYYY') || ') ' AS descr,
				pre_turnos_config.fecha_inicio
                FROM  pre_turnos_config
                JOIN pre_turnos_config_ra ON pre_turnos_config_ra.turno_config = pre_turnos_config.turno_config
				JOIN pre_turnos_config_modalidad ON pre_turnos_config_modalidad.turno_config = pre_turnos_config.turno_config
				JOIN pre_turnos_config_ubicacion ON pre_turnos_config_ubicacion.turno_config = pre_turnos_config.turno_config
				JOIN pre_turnos_config_intervalos ON pre_turnos_config_intervalos.turno_config = pre_turnos_config.turno_config
                ORDER BY fecha_inicio DESC
                ";

		return guarani_db::consultar($sql);		
	}

	/**
	 * Obtiene un listado de configuraciones de turnos de Preinscripción
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_listado_turnos_admistrador($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT DISTINCT
					pre_turnos.pre_turno,
					pre_turnos.fecha,
					pre_turnos.hora,
					'OP ' || pre_turnos.operador AS operador,
					pre_turnos_config.turno_config,
					pre_turnos_config.descripcion  || ' (' || to_char(pre_turnos_config.fecha_inicio,'DD/MM/YYYY') || ' al ' || to_char(pre_turnos_config.fecha_fin,'DD/MM/YYYY') || ') ' AS configuracion,
					CASE 
						WHEN (pre_turnos.fecha >= CURRENT_DATE OR (pre_turnos.fecha = CURRENT_DATE AND pre_turnos.hora >= CURRENT_TIME)) AND (pre_turnos.id_preinscripcion IS NULL AND pre_turnos.persona IS NULL) THEN '0' ELSE '1'
					END AS disponible
				FROM 
					pre_turnos
					JOIN pre_turnos_config ON pre_turnos_config.turno_config = pre_turnos.turno_config
				WHERE
					pre_turnos.fecha >= CURRENT_DATE $where
				ORDER by fecha,hora
					
  				;";

		return guarani_db::consultar($sql);
	}

	function get_pre_turnos_config_ubicacion($turno_config)
	{
		$turno_config = toba::db()->quote($turno_config);
		$sql = "SELECT	pre_turnos_config_ubicacion.ubicacion
				FROM	pre_turnos_config_ubicacion
				WHERE	pre_turnos_config_ubicacion.turno_config = $turno_config
		";
		return guarani_db::consultar($sql);
	}
}
