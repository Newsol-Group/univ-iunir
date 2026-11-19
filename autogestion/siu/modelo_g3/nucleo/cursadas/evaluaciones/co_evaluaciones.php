<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Evaluaciones
 */
class co_evaluaciones
{
	function get_estado_evaluacion($estado)
	{
		$estado = toba::db()->quote($estado);
		
		$sql = "SELECT 		nombre	as estado_nombre
				FROM 		sga_evaluaciones_estados
				WHERE		sga_evaluaciones_estados.estado = $estado
				";
		
		return guarani_db::consultar($sql);
	}
	
	function get_listado($where='')
	{		
		if ($where) {
			$where = "WHERE $where";
		}
		
		$sql = "SELECT 		sga_evaluaciones.evaluacion,
							sga_evaluaciones.nombre,
							sga_evaluaciones.descripcion,
							sga_evaluaciones.entidad,
							sga_evaluaciones.evaluacion_tipo,
							sga_evaluaciones.fecha,
							sga_evaluaciones.hora_inicio,
							sga_evaluaciones.hora_fin,
							sga_evaluaciones.escala_nota,
                            sga_escalas_notas.nombre AS escala_nota_nombre,
							sga_evaluaciones.estado,
				            sga_evaluaciones_tipos.nombre AS tipo_eval_nombre,
				            sga_evaluaciones.nombre || ' (' || sga_evaluaciones_tipos.nombre || ')' AS nombre_y_tipo_eval
				FROM 		sga_evaluaciones
							JOIN sga_evaluaciones_tipos ON sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo
                			LEFT JOIN sga_escalas_notas ON sga_evaluaciones.escala_nota = sga_escalas_notas.escala_nota			
				$where
				ORDER BY	sga_evaluaciones.fecha
		";

		return guarani_db::consultar($sql);
	}

	/**
     *  Obtiene una lista de tipos de evaluaciones según el filtro recibido.
     */
	function get_listado_tipos($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}
		
		$sql = "SELECT 		sga_evaluaciones_tipos.evaluacion_tipo,
							sga_evaluaciones_tipos.nombre,
							sga_evaluaciones_tipos.descripcion,
							sga_evaluaciones_tipos.aplica_a,
							sga_evaluaciones_tipos.esencial,
							sga_evaluaciones_tipos.automatica
				FROM 		sga_evaluaciones_tipos
				$where
				ORDER BY	sga_evaluaciones_tipos.nombre
		";

		return guarani_db::consultar($sql);
	}

	
	/**
     *  Obtiene una lista de los tipos de evaluaciones existentes junto con sus nombres, según el filtro recibido.
     */
	function get_tipos_evaluaciones($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}

		$sql = "SELECT 		sga_evaluaciones_tipos.evaluacion_tipo,
							sga_evaluaciones_tipos.nombre as tipo_eval_nombre,
							sga_evaluaciones_tipos.automatica as tipo_eval_automatica
				FROM 		sga_evaluaciones_tipos
				$where
				ORDER BY	sga_evaluaciones_tipos.nombre
				;";

		return guarani_db::consultar($sql);	
	}

	/**
     *  Retorna los datos básicos de una evaluación y su comisión asociada.
     */
	function get_datos_evaluacion_comision($evaluacion)
	{
		$evaluacion = toba::db()->quote($evaluacion);
		$sql = "SELECT		$evaluacion as evaluacion,
							sga_evaluaciones.entidad,
							sga_comisiones.comision,
							sga_comisiones.nombre as nombre_comision,
							sga_turnos_cursadas.nombre as nombre_turno,
							f_modalidad_cursada_comision(sga_comisiones.comision) as nombre_modalidad,
							sga_comisiones.periodo_lectivo,
							sga_periodos.nombre	as nombre_periodo,
							sga_periodos.anio_academico,
							sga_periodos.anio_academico	as anio_academico_periodo,
							sga_comisiones.elemento,
							get_nombre_actividad_comision(sga_comisiones.comision, 1) as codigo_y_nombre_actividad,
							sga_ubicaciones.nombre as nombre_ubicacion,
							sga_evaluaciones.nombre as nombre_evaluacion,
							sga_evaluaciones.fecha,
							to_char(sga_evaluaciones.fecha, '".comunes::formato_fecha_visual."') as fecha_visual,
							sga_evaluaciones.estado,
							sga_evaluaciones.escala_nota,
							sga_escalas_notas.nombre as nombre_escala_nota
				FROM 		sga_evaluaciones
							JOIN sga_comisiones ON sga_evaluaciones.entidad = sga_comisiones.entidad
							JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
							JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
							JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							LEFT JOIN sga_escalas_notas ON sga_evaluaciones.escala_nota = sga_escalas_notas.escala_nota
							LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				WHERE		sga_evaluaciones.evaluacion = $evaluacion
				ORDER BY	sga_evaluaciones.fecha
		";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna las evaluaciones no automáticas para Comisiones, donde el alumno dado tenga cargado un 
	 * resultado diferente de ausente (es decir se haya presentado a rendir algún parcial), según el filtro dado.
	 *
	 * @param integer	$alumno	id del alumno para el cual se desean recuperar las evaluaciones en la comisión.
	 * @param str		$where	filtro sobre las evaluaciones a recuperar (comisiones, actividades, año académico, período lectivo).
	 * 
	 */
	function get_evaluaciones_comision_alumno($alumno, $where)
	{
		$alumno	  = toba::db()->quote($alumno);
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT		sga_evaluaciones.evaluacion,
							sga_evaluaciones.nombre,
							sga_evaluaciones.descripcion,
							sga_evaluaciones.evaluacion_tipo,
							sga_evaluaciones_tipos.nombre as nombre_tipo,
							sga_evaluaciones.fecha,
							to_char(sga_evaluaciones.fecha, '".comunes::formato_fecha_visual."') as fecha_visual,
							sga_evaluaciones.estado,
							sga_comisiones.comision,
							sga_comisiones.nombre as nombre_comision,
							sga_comisiones.elemento,
							'(' || COALESCE(sga_elementos.codigo, 'Sin Código') || ') ' || get_nombre_actividad_comision(sga_comisiones.comision, 1) as actividad_descr
				FROM 		sga_comisiones
							JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
							JOIN vw_periodos_lectivos ON sga_comisiones.periodo_lectivo = vw_periodos_lectivos.periodo_lectivo
							JOIN sga_evaluaciones ON sga_comisiones.entidad = sga_evaluaciones.entidad
							JOIN sga_evaluaciones_tipos ON sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo
							JOIN sga_eval_detalle ON sga_evaluaciones.evaluacion = sga_eval_detalle.evaluacion
				WHERE		sga_evaluaciones_tipos.automatica = '" . comunes::no . "' AND
							sga_eval_detalle.alumno = $alumno AND
							(sga_eval_detalle.resultado <> '" . escalas_notas::resultado_ausente . "' OR sga_eval_detalle.resultado IS NULL)
							$where
				ORDER BY	sga_evaluaciones.fecha DESC
		";
		return guarani_db::consultar($sql);
	}
	
	function get_datos_evaluaciones($evaluacion)
	{
		if (is_array($evaluacion)) {
			$filtro = guarani_sql::armar_condicion_in($evaluacion, 'sga_evaluaciones', 'evaluacion');
			$datos = $this->get_listado($filtro);
			return $datos;
		}
		$evaluacion = toba::db()->quote($evaluacion);
		$datos = $this->get_listado("sga_evaluaciones.evaluacion = $evaluacion");
		if (!empty($datos)) {
			return current($datos);
		}
	}

	/**
 	  * Retorna los datos correspondientes a un Tipo de Evaluación en formato RecordSet, en caso de que el parámetro sea un 
 	  * array retorna los datos de los datos de los Tipos de Evaluaciones correspondientes según la condición del array 
 	  * (para la carga masiva).
      *	@param integer/array tipo_eval id o arreglo de ids del o los tipos de evaluacion para los que se consultan los datos.
    */
	function get_datos_tipos_evaluaciones($tipo_eval)
	{
		if (is_array($tipo_eval)) {
			$filtro = guarani_sql::armar_condicion_in($tipo_eval, 'sga_evaluaciones_tipos', 'evaluacion_tipo');
			$datos = $this->get_tipos_evaluaciones($filtro);
			return $datos;
		} 
		$tipo_eval = toba::db()->quote($tipo_eval);
		$datos = $this->get_tipos_evaluaciones("sga_evaluaciones_tipos.evaluacion_tipo = $tipo_eval");
		if (!empty($datos)) {
			return current($datos);
		}
	}

	/**
     *  Obtiene una lista de tipos de evaluaciones no automáticas.
     */
	function get_tipos_no_automatica($where='')
	{
		$filtro = empty($where) ? 'true' : $where;
		$filtro .= " AND sga_evaluaciones_tipos.automatica = '".comunes::no."'";
		return $this->get_listado_tipos($filtro);
	}
	
	/**
     *  Obtiene una lista de tipos de evaluaciones no automáticas que aplican a comisión.
     */
	function get_tipos_no_automatica_comision($where='')
	{
		$filtro = empty($where) ? 'true' : $where;
		$filtro .= " AND sga_evaluaciones_tipos.aplica_a = 'C'";
		return $this->get_tipos_no_automatica($filtro);
	}

	/**
     *  Obtiene una lista de tipos de evaluaciones no automáticas que aplican a Llamados de Mesas de Exámen.
     */
	function get_tipos_no_automatica_llamado_mesa($where='')
	{
		$filtro = empty($where) ? 'true' : $where;
		$filtro .= " AND sga_evaluaciones_tipos.aplica_a = '" . mesa_de_examen::aplica_mesa . "'";
		return $this->get_tipos_no_automatica($filtro);
	}
	
	function get_evaluaciones_por_entidad($entidad)
	{
		$entidad = toba::db()->quote($entidad);
		
		$sql = "SELECT 		sga_evaluaciones.evaluacion,
							sga_evaluaciones.nombre,
							sga_evaluaciones.descripcion,
							sga_evaluaciones.entidad,
							sga_evaluaciones.evaluacion_tipo,
							sga_evaluaciones.fecha,
							sga_evaluaciones.hora_inicio,
							sga_evaluaciones.hora_fin,
							sga_evaluaciones.escala_nota,
							sga_evaluaciones.estado,
				            sga_evaluaciones_tipos.nombre as tipo_eval_nombre
				FROM 		sga_evaluaciones
							JOIN sga_evaluaciones_tipos ON sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo
				WHERE		sga_evaluaciones_tipos.automatica = '".comunes::no."' AND
							sga_evaluaciones.entidad = $entidad
				ORDER BY	sga_evaluaciones.fecha
		";

		return guarani_db::consultar($sql);
	}

	//Retorna la cantidad de evaluaciones registradas para la comisión dada, según el filtro dado.
	function get_cant_evaluaciones_para_comision($comision, $where='')
	{
		$filtro = '';
		if (!is_null($comision)) {
			$comision = toba::db()->quote($comision);
			$filtro .= " AND sga_comisiones.comision = $comision ";
		}
		if ($where) {
			$filtro .= ' AND ' . $where;
		}
		
		$sql = "SELECT 		count(sga_evaluaciones.evaluacion) as cant_evaluaciones
				FROM 		sga_evaluaciones,
							sga_evaluaciones_tipos,
							sga_comisiones
				WHERE		sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo AND
							sga_evaluaciones.entidad = sga_comisiones.entidad
							$filtro
				;";
				
		$datos = guarani_db::consultar_fila($sql, null, false);
		return $datos['cant_evaluaciones'];
	}
	
	//Retorna la cantidad de actas creadas para alguna evaluación para la comisión dada, según el filtro dado.
	function get_cant_actas_evaluaciones_para_comision($comision=null, $where='')
	{
		$filtro = '';
		if (!is_null($comision)) {
			$comision = toba::db()->quote($comision);
			$filtro .= " AND sga_comisiones.comision = $comision ";
		}
		if ($where) {
			$filtro .= ' AND ' . $where;
		}
		
		$sql = "SELECT 		count(sga_actas.id_acta) as cant_actas
				FROM 		sga_evaluaciones,
							sga_actas,
							sga_comisiones
				WHERE		sga_evaluaciones.entidad = sga_comisiones.entidad AND
							sga_evaluaciones.evaluacion = sga_actas.evaluacion
							$filtro
				;";
				
		$datos = guarani_db::consultar_fila($sql, null, false);
		return $datos['cant_actas'];
	}
	
	//Retorna la cantidad de actas creadas para alguna evaluación de los llamados asociados a la mesa de exámen dada, según el filtro dado.
	function get_cant_actas_evaluaciones_para_llamados_mesa($mesa_examen=null, $where='')
	{
		$filtro = '';
		if (!is_null($mesa_examen)) {
			$mesa_examen = toba::db()->quote($mesa_examen);
			$filtro .= " AND sga_mesas_examen.mesa_examen = $mesa_examen ";
		}
		if ($where) {
			$filtro .= ' AND ' . $where;
		}
		
		$sql = "SELECT 		count(sga_actas.id_acta) as cant_actas
				FROM 		sga_evaluaciones,
							sga_actas,
							sga_mesas_examen,
							sga_llamados_mesa
				WHERE		sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen AND
							sga_evaluaciones.entidad = sga_llamados_mesa.entidad AND
							sga_evaluaciones.evaluacion = sga_actas.evaluacion
							$filtro
				;";
				
		$datos = guarani_db::consultar_fila($sql);
		return $datos['cant_actas'];
	}

	/**
	 * Retorna la cantidad de detalles de evaluaciones para una mesa de examen dada.
	 *
	 * @param integer $mesa_examen
	 * @return integer
	 */
	function get_cant_evaluaciones_para_llamados_mesa($mesa_examen, $where='')
	{
		$mesa_examen = toba::db()->quote($mesa_examen);
		$filtro = "sga_llamados_mesa.mesa_examen = $mesa_examen";

		if ($where) {
			$filtro .= " AND $where";
		}
		
		$sql = "SELECT  COUNT(sga_eval_detalle_examenes.evaluacion) AS cant
				FROM	sga_eval_detalle_examenes,
						sga_evaluaciones,
						sga_llamados_mesa
				WHERE	sga_eval_detalle_examenes.evaluacion = sga_evaluaciones.evaluacion AND
						sga_evaluaciones.entidad = sga_llamados_mesa.entidad AND
						$filtro
				";

		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'];
	}
	
	function get_evaluaciones_relacionadas($entidad, $evaluacion=null, $where='')
	{
		$entidad = toba::db()->quote($entidad);
		
		if (!empty($evaluacion)) {
			$where .= " AND sga_evaluaciones.evaluacion <> $evaluacion";
		}

		$sql = "SELECT 		sga_evaluaciones.evaluacion,
							sga_evaluaciones.nombre,
							sga_evaluaciones.escala_nota
				FROM 		sga_evaluaciones
							JOIN sga_evaluaciones_tipos ON sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo
				WHERE		sga_evaluaciones.entidad = $entidad AND
							sga_evaluaciones_tipos.automatica = '".comunes::no."'
							$where
				ORDER BY	sga_evaluaciones.fecha";
		
		return guarani_db::consultar($sql);
	}

	function es_padre_de_evaluacion($evaluacion)
	{
		$evaluacion = toba::db()->quote($evaluacion);
		
		$sql = "SELECT  sga_evaluaciones_relacion.evaluacion_padre,
						sga_evaluaciones_relacion.evaluacion_hijo
				FROM	sga_evaluaciones_relacion,
						sga_evaluaciones
				WHERE	sga_evaluaciones_relacion.evaluacion_hijo = sga_evaluaciones.evaluacion	AND
						sga_evaluaciones_relacion.evaluacion_padre = $evaluacion";

		$res = guarani_db::consultar($sql);
		return guarani_arreglos::count($res) > 0;
	}
	
	function es_hijo_de_evaluacion($evaluacion)
	{
		$evaluacion = toba::db()->quote($evaluacion);
		
		$sql = "SELECT  sga_evaluaciones_relacion.evaluacion_padre,
						sga_evaluaciones_relacion.evaluacion_hijo
				FROM	sga_evaluaciones_relacion,
						sga_evaluaciones
				WHERE	sga_evaluaciones_relacion.evaluacion_hijo = sga_evaluaciones.evaluacion	AND
						sga_evaluaciones_relacion.evaluacion_hijo = $evaluacion";

		$res = guarani_db::consultar($sql);
		return guarani_arreglos::count($res) > 0;
	}
	
	function get_evaluaciones_padre($evaluacion)
	{
		$evaluacion = toba::db()->quote($evaluacion);
		
		$sql = "SELECT  sga_evaluaciones_relacion.evaluacion_padre as valor,
						sga_evaluaciones.nombre as descr
				FROM	sga_evaluaciones_relacion,
						sga_evaluaciones
				WHERE	sga_evaluaciones_relacion.evaluacion_padre = sga_evaluaciones.evaluacion AND
						sga_evaluaciones_relacion.evaluacion_hijo = $evaluacion";

		return guarani_db::consultar($sql);
	}
	
	function tiene_notas_cargadas($evaluacion)
	{
		$sql = "SELECT 	*
				FROM 	sga_eval_detalle
				WHERE	sga_eval_detalle.evaluacion = $evaluacion
						AND NOT sga_eval_detalle.nota is null";
		
		$res = guarani_db::consultar($sql);
		return guarani_arreglos::count($res) > 0;
	}

	function tiene_alumnos($evaluacion)
	{
		$sql = "SELECT 	*
				FROM 	sga_eval_detalle
				WHERE	sga_eval_detalle.evaluacion = $evaluacion";
		
		$res = guarani_db::consultar($sql);
		return guarani_arreglos::count($res) > 0;
	}
	
	function get_detalles_evaluaciones_relacionadas($evaluacion, $where='')
	{
		$evaluacion = toba::db()->quote($evaluacion);
		
		if ($where) {
			$where = " AND $where";
		}
		
		$sql = "SELECT 		-- Corte de control
							sga_eval_detalle.evaluacion,
							'Evaluacion: ' || sga_evaluaciones.nombre || ' - Fecha: ' || to_char(sga_evaluaciones.fecha, '".comunes::formato_fecha_visual."') || ' - Tipo: ' || sga_evaluaciones_tipos.nombre || ' - Escala de Notas: ' || sga_escalas_notas.nombre AS evaluacion_datos,
							-- Datos a exhibir
							vw_personas.apellido_nombres AS apellido_y_nombres,
							vw_personas.tipo_nro_documento AS identificacion,
							sga_eval_detalle.nota,
							sga_escalas_notas_resultado.descripcion AS resultado_descr,
							sga_eval_detalle.observaciones
				FROM 		sga_eval_detalle,
							sga_evaluaciones,
							sga_evaluaciones_tipos,
							sga_evaluaciones_relacion,
							sga_alumnos,
							vw_personas,
							sga_escalas_notas_resultado,
							sga_escalas_notas
				WHERE 		sga_eval_detalle.evaluacion = sga_evaluaciones.evaluacion
							AND sga_evaluaciones.evaluacion = sga_evaluaciones_relacion.evaluacion_padre
							AND sga_evaluaciones_relacion.evaluacion_hijo = $evaluacion
							AND sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
							AND sga_eval_detalle.alumno = sga_alumnos.alumno
							AND sga_alumnos.persona = vw_personas.persona
							AND sga_escalas_notas_resultado.resultado = sga_eval_detalle.resultado
							AND sga_escalas_notas.escala_nota = sga_evaluaciones.escala_nota
							$where
				ORDER BY 	sga_eval_detalle.evaluacion,
							vw_personas.apellido,
							vw_personas.nombres
			";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos de los detalles de evaluaciones que pueden ser asociados a un acta.
	 *
	 * @param integer	$evaluacion	código de evaluación para la cual se desean recuperar los detalles.
	 * @param integer	$acta		código de acta a la cual se quieren agregar detalles, puede ser -1 (acta::id_acta_ficticia) cuando todavia no existe o null.
	 * @param integer	$instancia	código de instancia o null en caso de que deban considerarse ambas (cursar y promcionar).
	 * @param str		$where		filtro sobre los detalles a retornar.
	 */
	function get_detalles_acta($evaluacion, $acta=null, $instancia=null, $where='')
	{
		$evaluacion = toba::db()->quote($evaluacion);

		$filtro_acta_instancia = '';
		if (!empty($acta) && !empty($instancia)) {
			if ($instancia == comision::inst_cursar) {
				$campo_instancia = 'instancia_cursada';
				$campo_acta = 'id_acta_cursada';
				$campo_acta_cerrada = 'acta_cursada_cerrada';
				$origen = toba::db()->quote(acta::origen_regulares);
			} else {
				$campo_instancia = 'instancia_promocion';
				$campo_acta = 'id_acta_promocion';
				$campo_acta_cerrada = 'acta_promocion_cerrada';
				$origen = toba::db()->quote(acta::origen_promociones);
			}
			$acta = toba::db()->quote($acta);
			$filtro_acta_instancia = "
								AND sga_eval_detalle_cursadas.$campo_instancia = 1
								AND (sga_eval_detalle_cursadas.$campo_acta IS NULL OR sga_eval_detalle_cursadas.$campo_acta = $acta)
								AND sga_alumnos.alumno NOT IN (	SELECT	sga_actas_detalle.alumno
																FROM	sga_actas
																JOIN	sga_actas_detalle ON sga_actas.id_acta = sga_actas_detalle.id_acta
              													WHERE	sga_actas.evaluacion = $evaluacion 
																  AND	sga_actas.id_acta <> $acta 
																  AND	sga_actas.origen = $origen 
																  AND	sga_actas.estado = '".acta::estado_cerrada."' 
																  AND	sga_actas_detalle.estado = '".acta::detalle_estado_activo."' 
																  AND	sga_actas_detalle.rectificado = '".comunes::no."')";
		}

		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT		sga_eval_detalle_cursadas.evaluacion,
							sga_eval_detalle_cursadas.alumno,
							sga_alumnos.legajo,
							vw_personas.apellido_nombres AS apellido_y_nombre,
							vw_personas.tipo_nro_documento AS documento_principal,
							sga_propuestas.nombre_abreviado	AS propuesta_nombre_abreviado,
							sga_insc_cursada.inscripcion,
				        	CASE
				        		WHEN sga_insc_cursada.inscripcion IS NOT NULL THEN '".comunes::si_desc."'
					            ELSE '".comunes::no_desc."'
				    	    END AS inscripto

				FROM		sga_evaluaciones
							JOIN sga_comisiones ON sga_evaluaciones.entidad = sga_comisiones.entidad
							JOIN sga_eval_detalle_cursadas ON sga_evaluaciones.evaluacion = sga_eval_detalle_cursadas.evaluacion
							JOIN sga_alumnos ON sga_eval_detalle_cursadas.alumno = sga_alumnos.alumno
							JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
							JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
							LEFT JOIN sga_insc_cursada ON (sga_insc_cursada.alumno = sga_eval_detalle_cursadas.alumno AND sga_insc_cursada.comision = sga_comisiones.comision)

				WHERE		sga_evaluaciones.evaluacion = $evaluacion AND
							(sga_insc_cursada.estado IS NULL OR sga_insc_cursada.estado = '".inscripcion_cursada::estado_aceptada."') AND
							sga_alumnos.calidad = '".alumno::calidad_activo."'
							$filtro_acta_instancia
							$where

				ORDER BY	apellido_y_nombre
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Valida si un nombre de Tipo de Evaluación se puede usar o no.
     * @param string	$nombre Nombre del Tipo de Evaluación a validar.
     * @param integer	$tipo_evaluacion Código del Tipo de Evaluación para excluir en la validación, si es distinto de nulo.
     * @return boolean	Si se puede usar ese nombre porque no todavía no existe, o no.
    */
	function validar_nombre_tipo_evaluacion($nombre, $tipo_evaluacion)
	{
		if (!isset($nombre)) {
			return false;
		}

		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT	count(sga_evaluaciones_tipos.evaluacion_tipo) as cant
                FROM 	sga_evaluaciones_tipos
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_evaluaciones_tipos.nombre', $nombre).'
		';

		if (!is_null($tipo_evaluacion)) {
			$tipo_evaluacion = toba::db()->quote($tipo_evaluacion);
			$sql .= " AND sga_evaluaciones_tipos.evaluacion_tipo <> $tipo_evaluacion ";
		}

		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	function evaluacion_tipo_es_esencial($evaluacion_tipo)
	{
		$evaluacion_tipo = toba::db()->quote($evaluacion_tipo);

		$sql = "SELECT 	sga_evaluaciones_tipos.evaluacion_tipo,
				  		sga_evaluaciones_tipos.esencial
				FROM 	sga_evaluaciones_tipos
				WHERE	sga_evaluaciones_tipos.evaluacion_tipo = $evaluacion_tipo";

		$res = guarani_db::consultar_fila($sql);
		return ($res['esencial'] == comunes::si);
	}
	
	function validar_nombre_evaluacion($nombre, $evaluacion, $entidad)
	{
		if (!isset($nombre)) {
			return false;
		}

		$nombre = toba::db()->quote($nombre);
		$entidad = toba::db()->quote($entidad);
		
		$sql = 'SELECT	count(sga_evaluaciones.evaluacion) as cant
                FROM 	sga_evaluaciones
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_evaluaciones.nombre', $nombre)."
						AND sga_evaluaciones.entidad = $entidad
		";

		if (!is_null($evaluacion)) {
			$evaluacion = toba::db()->quote($evaluacion);
			$sql .= " AND sga_evaluaciones.evaluacion <> $evaluacion ";
		}

		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
	 * Retorna el listado de evaluaciones no automáticas de la misma comisión y con fecha anterior a una evaluación dada.
	 *
	 * @param integer $evaluacion
	 * @return array
	 */
	function get_evaluaciones_anteriores($evaluacion)
	{
		$datos_evaluacion = $this->get_datos_evaluaciones($evaluacion);
		$sql = "SELECT	sga_evaluaciones.evaluacion,
						sga_evaluaciones.nombre
				FROM	sga_evaluaciones,
						sga_evaluaciones_tipos
				WHERE	sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo AND
						sga_evaluaciones_tipos.automatica = '".comunes::no."' AND
						sga_evaluaciones.entidad = {$datos_evaluacion['entidad']} AND
						sga_evaluaciones.fecha < '{$datos_evaluacion['fecha']}'
		";
		return guarani_db::consultar($sql);
	}

	//------------ Examenes -----------------------

	/**
	 * Retorna los alumnos en evaluacion automatica pasada como parametro que no estén en un acta abierta de la mesa
	 *
	 * @param <type> $entidad
	 * @param string $where
	 * @return <type>
	 */
	function get_detalle_evaluacion_examen($evaluacion, $where='')
	{

		if ($where) {
			$where = ' AND '.$where;
		}
		$evaluacion = toba::db()->quote($evaluacion);
		$sql ="SELECT	vw_personas.apellido_nombres AS apellido_y_nombre,
						vw_personas.tipo_nro_documento AS documento_principal,
						sga_alumnos.alumno,
						sga_alumnos.legajo,
						sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
						sga_eval_detalle_examenes.instancia,
						sga_instancias.nombre AS instancia_nombre,
						".guarani_sql::get_case_si_no('sga_eval_detalle_examenes', 'inscripto', 'inscripto').",
						sga_actas.nro_acta
	
				FROM	sga_eval_detalle_examenes
						JOIN sga_evaluaciones				ON (sga_evaluaciones.evaluacion				= sga_eval_detalle_examenes.evaluacion)
						JOIN sga_evaluaciones_tipos			ON (sga_evaluaciones.evaluacion_tipo		= sga_evaluaciones_tipos.evaluacion_tipo)
						JOIN sga_llamados_mesa				ON (sga_llamados_mesa.entidad				= sga_evaluaciones.entidad)
						JOIN sga_alumnos					ON (sga_alumnos.alumno						= sga_eval_detalle_examenes.alumno)
						JOIN vw_personas					ON (vw_personas.persona						= sga_alumnos.persona)
						JOIN sga_propuestas					ON (sga_propuestas.propuesta				= sga_alumnos.propuesta)
						JOIN sga_instancias					ON (sga_instancias.instancia				= sga_eval_detalle_examenes.instancia)
						LEFT JOIN sga_insc_examen			ON (sga_insc_examen.alumno					= sga_eval_detalle_examenes.alumno AND
																sga_insc_examen.llamado_mesa			= sga_llamados_mesa.llamado_mesa)
						LEFT JOIN sga_actas					ON (sga_actas.id_acta						= sga_eval_detalle_examenes.id_acta)

				WHERE sga_evaluaciones.evaluacion	= $evaluacion
				  AND (sga_insc_examen.estado IS NULL OR sga_insc_examen.estado = '".inscripcion_examen::estado_aceptada."')
				  AND sga_alumnos.calidad = '".alumno::calidad_activo."'
				$where

				ORDER BY vw_personas.apellido, vw_personas.nombres
				;";
		$datos = guarani_db::consultar($sql);
		return $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Carga notas a evaluaciones ---------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_cant_evaluaciones_para_llamado_mesa_entidad($llamado_mesa_entidad)
	{
		$llamado_mesa_entidad = toba::db()->quote($llamado_mesa_entidad);
		
		$sql = "SELECT  COUNT(*) AS cant
				FROM	sga_evaluaciones,
						sga_evaluaciones_tipos
				WHERE	sga_evaluaciones.entidad = $llamado_mesa_entidad
				AND		sga_evaluaciones.evaluacion_tipo  = sga_evaluaciones_tipos.evaluacion_tipo
				AND		sga_evaluaciones_tipos.automatica = '".comunes::si."'
				AND		sga_evaluaciones_tipos.aplica_a	  = '".mesa_de_examen::aplica_mesa."'
  				";
		
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'];
	}
	
	function get_resultados_evaluaciones_alumno_comision($alumno, $comision)
	{
		$alumno = toba::db()->quote($alumno);
		$comision = toba::db()->quote($comision);

		$sql = "SELECT		sga_evaluaciones.fecha,
							to_char(sga_evaluaciones.fecha, '".comunes::formato_fecha_visual."') as fecha_display,
							sga_evaluaciones.nombre as evaluacion_nombre,
							sga_evaluaciones_tipos.nombre as evaluacion_tipo_nombre,
							sga_eval_detalle.nota || ' (' || sga_escalas_notas_det.descripcion || ')' as nota_descr,
							sga_escalas_notas_resultado.descripcion as resultado_descr,
							f_instancias_evaluacion(sga_evaluaciones.evaluacion) as instancias_evaluacion
				FROM		sga_eval_detalle
				JOIN		sga_evaluaciones ON sga_eval_detalle.evaluacion = sga_evaluaciones.evaluacion
				JOIN		sga_comisiones ON sga_comisiones.entidad = sga_evaluaciones.entidad
				JOIN		sga_escalas_notas_det ON (sga_escalas_notas_det.escala_nota = sga_eval_detalle.escala_nota AND 
				                                      sga_escalas_notas_det.nota = sga_eval_detalle.nota)
				JOIN		sga_escalas_notas_resultado ON sga_eval_detalle.resultado = sga_escalas_notas_resultado.resultado
				JOIN		sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
				WHERE		sga_eval_detalle.alumno = $alumno AND
							sga_comisiones.comision = $comision
				ORDER BY 	sga_evaluaciones.fecha
		";

		return guarani_db::consultar($sql);
	}
	
	
	//--------------------------------------------------------------------------------------
	//---- Reporte de Evaluaciones Parciales -------------------------------------------------------------
	//--------------------------------------------------------------------------------------

	/*
	 * Retorna listado de evaluaciones parciales segun filtro ingresado.
	 *
	 * @param string $where	// Condicion de filtro
	 * @return array		// Listado de evaluaciones parciales
	*/
	function get_reporte_evaluaciones_parciales($where=null)
	{
		$fecha_display = toba::db()->quote(comunes::formato_fecha_visual);
		$no = toba::db()->quote(comunes::no);
		
		if ($where) {
			$where = ' AND '.$where;
		}
		$sql = "SELECT	
                            vw_comisiones.anio_academico ||  ' - ' || vw_comisiones.periodo_lectivo_nombre as anio_periodo,
                            vw_comisiones.elemento_nombre ||  ' (' || vw_comisiones.elemento_codigo || ')' as actividad,
                            'Comisión: ' || vw_comisiones.comision_nombre || '<br>Ubicación: ' || sga_ubicaciones.nombre as comision_ubi,
                            vw_comisiones.anio_academico as anio_academico,
                            vw_comisiones.comision_nombre,
                            vw_comisiones.periodo_lectivo_nombre as periodo_lectivo_nombre,
                            sga_ubicaciones.nombre as ubicacion_nombre,
                            vw_comisiones.fecha_inicio as fecha_inico,
                            vw_comisiones.elemento_nombre as actividad_nombre,
                            sga_evaluaciones.evaluacion as evaluacion,
                            sga_evaluaciones.fecha as fecha_orden,
                            to_char(sga_evaluaciones.fecha, $fecha_display) as fecha_display,
                            sga_evaluaciones.nombre as evaluacion_nombre,
                            sga_evaluaciones_tipos.nombre as evaluacion_tipo_nombre,
                            sga_evaluaciones.estado as estado,
                            sga_actas_estados.nombre as estado_desc
                        FROM	sga_evaluaciones 
                        JOIN	sga_actas_estados ON sga_actas_estados.estado = sga_evaluaciones.estado 				
                        JOIN	vw_comisiones ON vw_comisiones.entidad = sga_evaluaciones.entidad
                        JOIN	sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
                        JOIN	sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_comisiones.ubicacion
                        WHERE	sga_evaluaciones_tipos.automatica = $no
                        $where
                        ORDER BY anio_academico, periodo_lectivo_nombre, fecha_inicio, actividad_nombre, comision_nombre, fecha_orden
		";
		return guarani_db::consultar($sql);
	}


//--------------------------------------------------------------------------------------
	//---- constancias de Evaluaciones parciales_docentes -------------------------------------------------------------
	//--------------------------------------------------------------------------------------

	/*
	 * Retorna listado de evaluaciones parciales y docentes de la comision segun filtro ingresado.
	 *
	 * @param string $where	// Condicion de filtro
	 * @return array		// Listado de evaluaciones parciales
	*/
	function get_evaluaciones_parciales_docentes($where=null)
	{
		$fecha_display = toba::db()->quote(comunes::formato_fecha_visual);
		$no = toba::db()->quote(comunes::no);
			
		if ($where) {
			$where = ' AND '.$where;
		}
		$sql = "SELECT	
                            vw_comisiones.anio_academico ||  ' - ' || vw_comisiones.periodo_lectivo_nombre as anio_periodo,
                            vw_comisiones.elemento_nombre ||  ' (' || vw_comisiones.elemento_codigo || ')' as actividad,
                            'Comisión: ' || vw_comisiones.comision_nombre || '<br>Ubicación: ' || sga_ubicaciones.nombre as comision_ubi,
                            vw_comisiones.anio_academico as anio_academico,
                            vw_comisiones.comision_nombre,
                            vw_comisiones.periodo_lectivo_nombre as periodo_lectivo_nombre,
                            sga_ubicaciones.nombre as ubicacion_nombre,
                            vw_comisiones.fecha_inicio as fecha_inico,
                            vw_comisiones.elemento_nombre as actividad_nombre,
                            sga_evaluaciones.evaluacion as evaluacion,
                            sga_evaluaciones.fecha as fecha_orden,
                            to_char(sga_evaluaciones.fecha, $fecha_display) as fecha_display,
                            sga_evaluaciones.nombre as evaluacion_nombre,
                            sga_evaluaciones_tipos.nombre as evaluacion_tipo_nombre,
                            sga_evaluaciones.estado as estado,
                            sga_actas_estados.nombre as estado_desc,
							f_docentes_comision(vw_comisiones.comision, TRUE) as docentes
                        FROM	sga_evaluaciones 
                        JOIN	sga_actas_estados ON sga_actas_estados.estado = sga_evaluaciones.estado 				
                        JOIN	vw_comisiones ON vw_comisiones.entidad = sga_evaluaciones.entidad
                        JOIN	sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
                        JOIN	sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_comisiones.ubicacion
                        WHERE	sga_evaluaciones_tipos.automatica = $no
                        $where
                        ORDER BY anio_academico, periodo_lectivo_nombre, fecha_inicio, actividad_nombre, comision_nombre, fecha_orden
		";
		return guarani_db::consultar($sql);
	}



	/*
	/*
	 * Retorna los alumnos y sus notas de una evaluacion parcial.
	 *
	 * @param integer $evaluacion
	 * @return array  // Datos de la evaluacion
	*/
	function get_alumnos_evaluacion($evaluacion)
	{
		$evaluacion = toba::db()->quote($evaluacion);
		$sql = "SELECT		vw_personas.apellido_nombres as apellido_nombres,
							vw_personas.tipo_nro_documento as identificacion,
							sga_alumnos.legajo as legajo,
							sga_eval_detalle.nota || ' (' || sga_escalas_notas_det.descripcion || ')' as nota_descr,
							sga_escalas_notas_resultado.descripcion as resultado_descr,
							sga_eval_detalle.corregido_por as corregido_por,
							sga_eval_detalle.observaciones as observaciones,
							sga_eval_detalle.estado as estado
				FROM		sga_eval_detalle
							JOIN sga_alumnos ON sga_alumnos.alumno = sga_eval_detalle.alumno
							JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
							JOIN sga_escalas_notas_resultado ON sga_eval_detalle.resultado = sga_escalas_notas_resultado.resultado
							LEFT JOIN sga_escalas_notas_det ON (sga_escalas_notas_det.escala_nota = sga_eval_detalle.escala_nota AND sga_escalas_notas_det.nota = sga_eval_detalle.nota)
				WHERE		sga_eval_detalle.evaluacion = $evaluacion
				ORDER BY	vw_personas.apellido_nombres
		";
		return guarani_db::consultar($sql);
	}

	//--------------------------------------------------------------------------------------
	//---- Cálculo de promedio -------------------------------------------------------------
	//--------------------------------------------------------------------------------------

	/**
	 * Retorna los datos de la escala de notas correspondiente a la comisión e instancia dadas.
	 *
	 * @param integer $comision
	 * @param integer $instancia
	 * @return array
	 */
	function get_datos_escala_nota_comision($comision, $instancia)
	{
		$comision = toba::db()->quote($comision);
		$instancia = toba::db()->quote($instancia);
		$sql = "SELECT	sga_comisiones_instancias.escala_nota,
						sga_escalas_notas.es_numerica
				FROM 	sga_comisiones,
						sga_comisiones_instancias,
						sga_escalas_notas
				WHERE 	sga_comisiones.comision = sga_comisiones_instancias.comision AND
						sga_comisiones_instancias.escala_nota = sga_escalas_notas.escala_nota AND
						sga_comisiones.comision = $comision AND
						sga_comisiones_instancias.instancia = $instancia
		";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Devuelve si la comisión tiene evaluaciones promediables con escalas compatibles 
	 *
	 * @param integer $comision
	 * @param integer $instancia
	 * @return boolean
	 */
	function tiene_evaluaciones_escala_compatible($comision, $instancia)
	{
		//Obtengo tipos de escalas de notas de las evaluaciones de la comision
		$sql1= "SELECT 	sga_evaluaciones.evaluacion, sga_escalas_notas.es_numerica
				FROM 	sga_evaluaciones
				JOIN	sga_comisiones ON sga_evaluaciones.entidad = sga_comisiones.entidad
				JOIN	sga_evaluaciones_tipos ON sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo
				JOIN	sga_escalas_notas ON sga_evaluaciones.escala_nota = sga_escalas_notas.escala_nota
				JOIN	sga_evaluaciones_instancias ON (sga_evaluaciones_instancias.evaluacion = sga_evaluaciones.evaluacion AND sga_evaluaciones_instancias.instancia = $instancia )
				
				WHERE sga_comisiones.comision = $comision
				  AND sga_evaluaciones_tipos.automatica = '".comunes::no."'	-- Evaluaciones no automáticas
				  AND sga_evaluaciones.promediable = '".comunes::si."'		-- Evaluaciones promediables	
				";
			$escalas	= guarani_db::consultar($sql1);

			if (empty($escalas)) //no tiene evaluaciones
			{
				return -4;
			}
			
			foreach($escalas as $escala)
			{
				if ($escala['es_numerica']== comunes::no) {
					return false;
				}			
			}
			
		
		// Obtengo las evaluaciones de la comision
		$sql = "SELECT 	sga_evaluaciones.evaluacion,
						CASE
							WHEN (sga_escalas_notas.tipo = '".escalas_notas::tipo_continua."') THEN sga_escalas_notas.nota_inicial
							ELSE (	SELECT 	MIN(sga_escalas_notas_det.valor_numerico) as nota_inicial
									FROM 	sga_escalas_notas_det
									WHERE 	sga_escalas_notas_det.escala_nota = sga_escalas_notas.escala_nota)
						END as nota_inicial,
						CASE
							WHEN (sga_escalas_notas.tipo = '".escalas_notas::tipo_continua."') THEN sga_escalas_notas.nota_final
							ELSE (	SELECT 	MAX(sga_escalas_notas_det.valor_numerico) as nota_final
									FROM	sga_escalas_notas_det
									WHERE	sga_escalas_notas_det.escala_nota = sga_escalas_notas.escala_nota)
						END as nota_final
				FROM 	sga_evaluaciones,
						sga_comisiones,
						sga_evaluaciones_tipos,
						sga_escalas_notas,
						sga_evaluaciones_instancias
				WHERE 	sga_evaluaciones.entidad = sga_comisiones.entidad AND
						sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo AND
						sga_evaluaciones.escala_nota = sga_escalas_notas.escala_nota AND
						sga_evaluaciones.evaluacion = sga_evaluaciones_instancias.evaluacion AND
						sga_comisiones.comision = $comision AND
						sga_evaluaciones_instancias.instancia = $instancia AND

						-- Evaluaciones no automáticas.
						sga_evaluaciones_tipos.automatica = '".comunes::no."' AND
						-- Evaluaciones promediables.
						sga_evaluaciones.promediable = '".comunes::si."' AND
						-- Escalas de notas numéricas.
				        sga_escalas_notas.es_numerica = '".comunes::si."'
		";
		$evaluaciones	= guarani_db::consultar($sql);
		if (empty($evaluaciones))
		{
			return -4;
		}

		$valor_inicial	= $evaluaciones[0]['nota_inicial'];
		$valor_final	= $evaluaciones[0]['nota_final'];
		$son_compatibles= true;
		// Son compatibles?

		foreach($evaluaciones as $evaluacion)
		{
			if ($evaluacion['nota_inicial'] != $valor_inicial || $evaluacion['nota_final'] != $valor_final) {
				$son_compatibles = false;
			}			
		}

		return $son_compatibles;

	}

	/**
	 * Retorna el conjunto de evaluaciones no automáticas, promediables y numéricas cuya escala de notas es coherente con las notas inicial y final dadas.
	 *
	 * @param integer $comision
	 * @param integer $instancia
	 * @param float $nota_inicial_escala_comision
	 * @param float $nota_final_escala_comision
	 * @return array
	 */
	function get_evaluaciones_coherentes_promediables($comision, $instancia, $nota_inicial_escala_comision, $nota_final_escala_comision)
	{
		$sql = "SELECT 	sga_evaluaciones.evaluacion,
						CASE
							WHEN (sga_escalas_notas.tipo = '".escalas_notas::tipo_continua."') THEN sga_escalas_notas.nota_inicial
							ELSE (	SELECT 	MIN(sga_escalas_notas_det.valor_numerico) AS nota_inicial
									FROM 	sga_escalas_notas_det
									WHERE 	sga_escalas_notas_det.escala_nota = sga_escalas_notas.escala_nota)
						END as nota_inicial,
						CASE
							WHEN (sga_escalas_notas.tipo = '".escalas_notas::tipo_continua."') THEN sga_escalas_notas.nota_final
							ELSE (	SELECT 	MAX(sga_escalas_notas_det.valor_numerico) AS nota_final
									FROM	sga_escalas_notas_det
									WHERE	sga_escalas_notas_det.escala_nota = sga_escalas_notas.escala_nota)
						END as nota_final
				FROM 	sga_evaluaciones
						JOIN sga_comisiones ON sga_evaluaciones.entidad = sga_comisiones.entidad
						JOIN sga_evaluaciones_tipos ON sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo
						JOIN sga_escalas_notas ON sga_evaluaciones.escala_nota = sga_escalas_notas.escala_nota
						JOIN sga_evaluaciones_instancias ON sga_evaluaciones.evaluacion = sga_evaluaciones_instancias.evaluacion
				WHERE 	sga_comisiones.comision = $comision AND
						sga_evaluaciones_instancias.instancia = $instancia AND
						-- Evaluaciones no anuladas.
						sga_evaluaciones.estado <> '".evaluacion::estado_anulada."' AND
						-- Evaluaciones no automáticas.
						sga_evaluaciones_tipos.automatica = '".comunes::no."' AND
						-- Evaluaciones promediables.
						sga_evaluaciones.promediable = '".comunes::si."' AND
						-- Escalas de notas numéricas.
				        sga_escalas_notas.es_numerica = '".comunes::si."'
		";

		$evaluaciones = guarani_db::consultar($sql);
		foreach ($evaluaciones as $clave => $valor) {
			if ($valor['nota_inicial'] != $nota_inicial_escala_comision || $valor['nota_final'] != $nota_final_escala_comision) {
				unset($evaluaciones[$clave]);
			}
		}
		return $evaluaciones;
	}

	/**
	 * Realiza el cálculo del promedio teniendo en cuenta sólo los parciales.
	 *
	 * @param int 	$alumno
	 * @param array $evaluaciones conjunto de evaluaciones a considerar en el cálculo de promedios.
	 * @return numeric
	 */
	function calcular_promedio_parciales($alumno, $evaluaciones)
	{
		$alumno = toba::db()->quote($alumno);
		$filtro_evaluaciones = guarani_sql::armar_condicion_in($evaluaciones, 'sga_evaluaciones', 'evaluacion', 'evaluacion');
		$sql = "SELECT	-- La nota es cero cuando no existe detalle para el alumno (luego del cierre de la evaluación), el resultado es ausente o no hay resultado cargado.
						AVG(COALESCE(sga_escalas_notas_det.valor_numerico, 0)) as promedio
				FROM 	sga_evaluaciones
						LEFT JOIN sga_eval_detalle ON (sga_evaluaciones.evaluacion = sga_eval_detalle.evaluacion AND sga_eval_detalle.alumno = $alumno)
						LEFT JOIN sga_escalas_notas_det ON (sga_eval_detalle.escala_nota = sga_escalas_notas_det.escala_nota AND sga_eval_detalle.nota = sga_escalas_notas_det.nota)
				WHERE	-- Evaluaciones de tipo PARCIAL.
						sga_evaluaciones.evaluacion_tipo = '".evaluacion::parcial."' AND
						$filtro_evaluaciones
		";
		$res = guarani_db::consultar_fila($sql);
		return $res['promedio'];
	}
	
	/**
	 * Realiza el cálculo del promedio teniendo en cuenta TODAS las evaluaciones rendidas(aprobadas y desaprobadas).
	 * Se excuyen las evaluaciones donde estuvo ausente o no se le cargó un resultado.
	 *
	 * @param int 	$alumno
	 * @param array $evaluaciones conjunto de evaluaciones a considerar en el cálculo de promedios.
	 * @return numeric
	 */
	function calcular_promedio_evaluaciones_rendidas($alumno, $evaluaciones)
	{
		$alumno = toba::db()->quote($alumno);
		$filtro_evaluaciones = guarani_sql::armar_condicion_in($evaluaciones, 'sga_eval_detalle', 'evaluacion', 'evaluacion');
		$sql = "SELECT  AVG(sga_escalas_notas_det.valor_numerico) as promedio
				FROM 	sga_eval_detalle
						JOIN sga_escalas_notas_det ON sga_eval_detalle.escala_nota = sga_escalas_notas_det.escala_nota AND sga_eval_detalle.nota = sga_escalas_notas_det.nota
				WHERE	sga_eval_detalle.alumno = $alumno AND
						sga_eval_detalle.resultado <> '".escalas_notas::resultado_ausente."' AND
						$filtro_evaluaciones
		";
		$res = guarani_db::consultar_fila($sql);
		return $res['promedio'];
	}
	
	/**
	 * Realiza el cálculo del promedio teniendo en cuenta sólo la última nota obtenida (por fecha) en cada evaluación tomada.
	 * Siempre (para una evaluación en particular) se deberá tomar la última nota para la evaluación sin importar el tipo. 
	 * Esto es, de una evaluación padre y sus hijas se toma la nota de la última de ellas.
	 *
	 * @param int 	$alumno
	 * @param array $evaluaciones conjunto de evaluaciones a considerar en el cálculo de promedios.
	 * @return numeric
	 */
	function calcular_promedio_ultimas_notas($alumno, $evaluaciones)
	{
		$alumno = toba::db()->quote($alumno);
		$filtro_evaluaciones = guarani_sql::armar_condicion_in($evaluaciones, 'sga_evaluaciones', 'evaluacion', 'evaluacion');
		$sql = "SELECT		sga_evaluaciones.evaluacion,
							sga_evaluaciones_tipos.esencial,
							-- La nota es cero cuando no existe detalle para el alumno (luego del cierre de la evaluación), el resultado es ausente o no hay resultado cargado.
							COALESCE(sga_escalas_notas_det.valor_numerico, 0) as valor_numerico,
							COALESCE(sga_escalas_notas_det.resultado, '".escalas_notas::resultado_ausente."') as resultado,
							COALESCE(sga_evaluaciones_relacion.evaluacion_padre, sga_evaluaciones.evaluacion) as evaluacion_padre
				FROM		sga_evaluaciones
							JOIN sga_evaluaciones_tipos ON sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo
							LEFT JOIN sga_eval_detalle ON (sga_evaluaciones.evaluacion = sga_eval_detalle.evaluacion AND sga_eval_detalle.alumno = $alumno)
							LEFT JOIN sga_escalas_notas_det ON (sga_eval_detalle.escala_nota = sga_escalas_notas_det.escala_nota AND sga_eval_detalle.nota = sga_escalas_notas_det.nota)
							LEFT JOIN sga_evaluaciones_relacion ON sga_evaluaciones.evaluacion = sga_evaluaciones_relacion.evaluacion_hijo
				WHERE		$filtro_evaluaciones
				ORDER BY	evaluacion_padre,
							sga_evaluaciones.fecha DESC";
		$resultados = guarani_db::consultar($sql);

		if (empty($resultados)) {
			return null;
		} else {
			$evaluacion_padre = null;
			$suma = 0;
			$cant = 0;
			// Se realiza el cálculo del promedio teniendo en cuenta sólo la última nota de cada evaluación tomada.
			foreach ($resultados as $rs) {
				if ($evaluacion_padre != $rs['evaluacion_padre']) {
					if ($rs['resultado'] != escalas_notas::resultado_ausente || $rs['esencial'] == comunes::si) {
						$evaluacion_padre = $rs['evaluacion_padre'];
						$suma += $rs['valor_numerico'];
						$cant += 1;
					}
				}
			}
			return $suma/$cant;
		}
	}

	/**
	 * Realiza el cálculo del promedio teniendo en cuenta sólo la mejor nota obtenida en cada evaluación tomada.
	 * Siempre (para una evaluación en particular) se deberá tomar la mejor nota para la evaluación sin importar el tipo. 
	 * Esto es, de una evaluación padre y sus hijas se toma la nota de la mejor de ellas.
	 *
	 * @param int 	$alumno
	 * @param array $evaluaciones conjunto de evaluaciones a considerar en el cálculo de promedios.
	 * @return numeric
	 */
	function calcular_promedio_mejores_notas($alumno, $evaluaciones)
	{
		$alumno = toba::db()->quote($alumno);
		$filtro_evaluaciones = guarani_sql::armar_condicion_in($evaluaciones, 'sga_evaluaciones', 'evaluacion', 'evaluacion');
		$sql = "SELECT		sga_evaluaciones.evaluacion,
							-- La nota es cero cuando no existe detalle para el alumno (luego del cierre de la evaluación), el resultado es ausente o no hay resultado cargado.
							COALESCE(sga_escalas_notas_det.valor_numerico, 0) as valor_numerico,
        					COALESCE(sga_evaluaciones_relacion.evaluacion_padre, sga_evaluaciones.evaluacion) as evaluacion_padre
				FROM		sga_evaluaciones
							LEFT JOIN sga_eval_detalle ON (sga_evaluaciones.evaluacion = sga_eval_detalle.evaluacion AND sga_eval_detalle.alumno = $alumno)
							LEFT JOIN sga_escalas_notas_det ON (sga_eval_detalle.escala_nota = sga_escalas_notas_det.escala_nota AND sga_eval_detalle.nota = sga_escalas_notas_det.nota)
        					LEFT JOIN sga_evaluaciones_relacion ON sga_evaluaciones.evaluacion = sga_evaluaciones_relacion.evaluacion_hijo
				WHERE		$filtro_evaluaciones 
				ORDER BY	evaluacion_padre,
							valor_numerico DESC";
		$resultados = guarani_db::consultar($sql);

		if (empty($resultados)) {
			return null;
		} else {
			$evaluacion_padre = null;
			$suma = 0;
			$cant = 0;
			$mejor_nota = 0;
			foreach ($resultados as $rs) {
				if (!empty($evaluacion_padre) && $evaluacion_padre != $rs['evaluacion_padre']) {
					$suma += $mejor_nota;
					$cant += 1;
					$mejor_nota = 0;
				}
				$evaluacion_padre = $rs['evaluacion_padre'];
				$mejor_nota = ($rs['valor_numerico'] > $mejor_nota) ? $rs['valor_numerico'] : $mejor_nota;
			}
			if (!empty($evaluacion_padre)) {
				$suma += $mejor_nota;
				$cant += 1;
			}
			return $suma/$cant;
		}
	}

	//--------------------------------------------------------------------------------------
	//---- FIN métodos Cálculo de promedio -------------------------------------------------------------
	//--------------------------------------------------------------------------------------

	
	/**
	 * Retorna los datos del detalle de una cursada según evaluación y alumno.
	 *
	 * @param integer $evaluacion
	 * @param integer $alumno
	 * @return array
	 */
	function get_detalle_evaluacion_cursada_by_evaluacion_alumno($evaluacion, $alumno)
	{
		$evaluacion = toba::db()->quote($evaluacion);
		$alumno = toba::db()->quote($alumno);

		$sql = "SELECT * 
				FROM sga_eval_detalle_cursadas
				WHERE sga_eval_detalle_cursadas.evaluacion = $evaluacion
				AND sga_eval_detalle_cursadas.alumno = $alumno";

		return guarani_db::consultar_fila($sql);
	}
}

?>