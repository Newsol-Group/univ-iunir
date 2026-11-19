<?php
/**
 *
 *
 * @package 	Tesis
 * @subpackage 
 */
class co_tesis
{
	function get_listado($where='', $mostrar_roles=false)
	{
		if ($where) {
			$where = " WHERE $where ";
		}

		if ($mostrar_roles) {
			$evaluadores = "f_evaluadores_tesis(sga_tesis.tesis, true)";
			$tutores = "f_tutores_tesis(sga_tesis.tesis, true)";
		} else {
			$evaluadores = "f_evaluadores_tesis(sga_tesis.tesis, false)";
			$tutores = "f_tutores_tesis(sga_tesis.tesis, false)";
		}

		$sql = "SELECT		DISTINCT
							sga_tesis.tesis,
							sga_tesis.titulo,
							sga_tesis.fecha_alta,
							sga_tesis.fecha_aprobacion,
							to_char(sga_tesis.fecha_aprobacion, '" . comunes::formato_fecha_visual . "') as fecha_aprobacion_formateada, 
							sga_tesis.fecha_vencimiento,
							to_char(sga_tesis.fecha_vencimiento, '" . comunes::formato_fecha_visual . "') as fecha_vencimiento_formateada, 
							'(' || COALESCE(vw_elementos.codigo, 'Sin código') || ') ' || vw_elementos.nombre AS actividad_descr,
							mce_estados.nombre AS estado_nombre,
							f_alumnos_tesis(sga_tesis.tesis)		AS alumnos_tesis,
							$tutores								AS tutores_tesis,
							$evaluadores							AS evaluadores_tesis
							
				FROM 		sga_tesis
								LEFT JOIN mce_estados		ON sga_tesis.estado_actual = mce_estados.estado
								LEFT JOIN vw_elementos		ON sga_tesis.actividad = vw_elementos.elemento
								LEFT JOIN sga_documentos 	ON sga_tesis.resolucion_tesis = sga_documentos.documento
								-- Se agregan al from sga_propuestas para que se dispare el PD por ese gatillo
								JOIN sga_tesis_alumnos      ON sga_tesis_alumnos.tesis = sga_tesis.tesis
								JOIN sga_alumnos			ON sga_alumnos.alumno = sga_tesis_alumnos.alumno
								JOIN sga_propuestas			ON sga_propuestas.propuesta =  sga_alumnos.propuesta
							$where
				
				ORDER BY	sga_tesis.titulo,
							sga_tesis.fecha_alta
				";

		return guarani_db::consultar($sql);
	}

	function get_listado_con_roles($where)
	{
		$mostrar_roles = true;
		return $this->get_listado($where, $mostrar_roles);
	}
	
	/**
	 * Retorna la cantidad de Tesis en estado no final en las que esta como Tutor una persona dada.
     */
	function get_cantidad_tesis_abiertas_tutor($persona, $tesis)
	{
		$persona = toba::db()->quote($persona);
		$sql = "SELECT 	COUNT(sga_tesis_tutores.tesis) AS cant
				FROM 	sga_tesis_tutores LEFT JOIN sga_tesis ON sga_tesis_tutores.tesis = sga_tesis.tesis
				WHERE 	sga_tesis_tutores.persona = $persona
				AND		EXISTS (SELECT	1
								FROM	mce_caminos
								WHERE	mce_caminos.circuito = sga_tesis.circuito
								AND		mce_caminos.estado_inicial = sga_tesis.estado_actual)";
		
		if (!is_null($tesis)) {
			$tesis = toba::db()->quote($tesis);
			$sql .= " AND sga_tesis_tutores.tesis <> $tesis ";
		}
		
		$res = guarani_db::consultar_fila($sql);
		return $res['cant'];
	}
	
	/**
	 * Valida si una tesis dada ya esta cerrada. Para ello verifica que no exista un camino
	 * de los del circuito cuyo estado inicial es igual al estado actual de la tesis.
     */
	function tesis_en_estado_final($tesis)
	{
		if (is_null($tesis)) {
			return false;
		}
		
		$tesis = toba::db()->quote($tesis);
		$sql = "SELECT 	COUNT(sga_tesis.tesis) AS cant
				FROM 	sga_tesis
				WHERE 	sga_tesis.tesis = $tesis
				AND		EXISTS (SELECT	1
								FROM	mce_caminos
								WHERE	mce_caminos.circuito = sga_tesis.circuito
								AND		mce_caminos.estado_inicial = sga_tesis.estado_actual)";
		
		$res = guarani_db::consultar_fila($sql);
		return $res['cant'] == 0;
	}
	
	/**
	 * Retorna la fecha y hora del último cambio efectuado en una tesis
     */
	function fecha_ult_cambio($tesis)
	{
		if (is_null($tesis)) {
			return false;
		}
		
		$tesis = toba::db()->quote($tesis);
		
		$sql = "SELECT 	fecha_ult_cambio
				FROM 	sga_tesis
				WHERE 	sga_tesis.tesis = $tesis";
		
		$res = guarani_db::consultar_fila($sql);
		return $res['fecha_ult_cambio'];
	}
	
	/**
	 * Valida si un título de tesis se puede usar o no.
     */
	function validar_titulo_tesis($titulo, $tesis)
	{
		if (!isset($titulo)) {
			return false;
		}
		
		$titulo = toba::db()->quote($titulo);
		$sql = 'SELECT	COUNT(sga_tesis.tesis) AS cant
                FROM 	sga_tesis
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_tesis.titulo', $titulo);
		
		if (!is_null($tesis)) {
			$tesis = toba::db()->quote($tesis);
			$sql .= " AND sga_tesis.tesis <> $tesis ";
		}
		
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Opciones para el tipo de tesis -----------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_opciones_tipos_tesis()
	{
			return array(array('valor' => 'A', 'descr' => 'Por Actividad'),
						 array('valor' => 'M', 'descr' => 'Por Modulo'));
	}
	
	//-----------------------------------------------------------------------------------
	//---- Opciones para el tipo de rol de tutores y evaluadores ------------------------
	//-----------------------------------------------------------------------------------
	
	function get_roles_tutor_tipos()
	{
		$sql = 'SELECT 		sga_tesis_roles_tutor.rol_tutor AS valor,
							sga_tesis_roles_tutor.nombre 	AS descr
				FROM  		sga_tesis_roles_tutor
				ORDER BY	sga_tesis_roles_tutor.nombre';
		
		return guarani_db::consultar($sql);
	}
	
	function get_roles_evaluador_tipos()
	{
		$sql = 'SELECT 		sga_tesis_roles_evaluador.rol_evaluador AS valor,
							sga_tesis_roles_evaluador.nombre 		AS descr
				FROM  		sga_tesis_roles_evaluador
				ORDER BY	sga_tesis_roles_evaluador.nombre';
		
		return guarani_db::consultar($sql);
	}

	function get_personas_a_notificar_cambio_estado_tesis($tesis)
	{
		$tesis = toba::db()->quote($tesis);

		$sql = "SELECT DISTINCT mdp_personas.persona,
								COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) AS persona_apellido,
								COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) AS persona_nombres,
								sga_tesis.titulo 		AS tesis_titulo,
								mce_estados.nombre 		AS tesis_estado
				FROM sga_tesis_alumnos
				JOIN sga_tesis 		ON (sga_tesis.tesis = sga_tesis_alumnos.tesis)
				JOIN sga_alumnos 	ON (sga_alumnos.alumno = sga_tesis_alumnos.alumno)
				JOIN mdp_personas 	ON (mdp_personas.persona = sga_alumnos.persona)
				JOIN mce_estados 	ON (mce_estados.estado = sga_tesis.estado_actual)
				WHERE sga_tesis.tesis = $tesis";

		return guarani_db::consultar($sql);
	}
	
	function se_debe_notificar_personas_tesis($tesis)
	{
		$tesis = toba::db()->quote($tesis);

		$sql = "SELECT	mce_caminos.enviar_aviso_alumno
				FROM	sga_tesis
						JOIN sga_tesis_cmb ON (sga_tesis.tesis = sga_tesis_cmb.tesis)
						JOIN mce_caminos   ON (sga_tesis.circuito = mce_caminos.circuito)
				WHERE	sga_tesis.tesis = $tesis
						AND ((sga_tesis_cmb.estado_anterior = mce_caminos.estado_inicial) OR (sga_tesis_cmb.estado_anterior IS NULL AND mce_caminos.estado_inicial IS NULL))
						AND sga_tesis_cmb.estado_nuevo = mce_caminos.estado_final
						AND sga_tesis_cmb.accion =  mce_caminos.accion
				ORDER BY sga_tesis_cmb.fecha DESC
				LIMIT 1";

		$datos_tesis = guarani_db::consultar_fila($sql);
		return ($datos_tesis['enviar_aviso_alumno'] == comunes::si)? true : false;
	}
	
}
?>