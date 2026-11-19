<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Alumnos
 * 
 */
class cn_ent_licencia extends cn_entidad
{
	//-----------------------------------------------------------------------------------
	//---- sga_licencias ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$propuestas = $datos['propuestas'];
		unset($datos['propuestas']);

		$this->validar_datos($datos);
		$this->tabla('licencias')->set($datos);
		$this->set_datos_propuestas($propuestas);
	}

	function get_datos($tabla=null)
	{
		$datos = $this->tabla('licencias')->get();
		if (!empty($datos)) {
			$propuestas = $this->tabla('propuestas')->get_filas();
			$datos['propuestas'] = aplanar_matriz($propuestas, 'propuesta');
		}
		return $datos;
	}

	function validar_datos($datos)
	{
		// Si Licencia financiera es 'Sí' entonces 'Alcance Licencia' debe ser 'Rango de Fechas'
		if ($datos['es_licencia_financiera'] == comunes::si && $datos['alcance'] != licencia::alcance_rango_fechas) {
			$this->set_error('800_dato_invalido', ['<b>Alcance Licencia</b>', $datos['alcance']]);
		}

		if ($datos['alcance'] == licencia::alcance_periodos_lectivos && empty($datos['periodo_lectivo_desde'])) {
			$this->set_error('dato_faltante', array('Período Lectivo Desde'));
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->get_mensaje_error());
		}
	}

	/**
	 * Valida que la fecha desde sea anterior a la fecha hasta.
	 *
	 * @param date $fecha_desde
	 * @param date $fecha_hasta
	 */
	function validar_fechas_desde_hasta($fecha_desde, $fecha_hasta)
	{
		if (guarani_fecha::es_mayor($fecha_desde, $fecha_hasta)) {
			$this->set_error('800_control_fechas', array('Desde', 'mayor', 'Hasta'));
		}
	}

	/**
	 * Valida que no haya superposición de licencias para una misma persona.
	 *
	 * @param date $fecha_desde
	 * @param date $fecha_hasta
	 * @param integer $persona
	 * @param integer $licencia
	 */
	function validar_superposicion_licencias($fecha_desde, $fecha_hasta, $persona, $licencia=null)
	{
		$filtro = '';
		if (! is_null($licencia)) {
			$licencia = toba::db()->quote($licencia);
			$filtro = " sga_licencias.licencia <> $licencia
						AND sga_licencias.estado <> '".licencia::estado_rechazado."'";
		}
		$licencias_persona = toba::consulta_php('co_licencias')->get_fechas_licencia_persona($persona, $filtro);

		foreach ($licencias_persona as $licencia) {
			if (!is_null($licencia['fecha_desde']) && !is_null($licencia['fecha_hasta']) && guarani_fecha::rango_solapado_en_rango($fecha_desde, $fecha_hasta, $licencia['fecha_desde'], $licencia['fecha_hasta'])) {
				$this->set_notificacion('800MAT_licencia_superpuesta');
				break;
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- sga_licencias_doc_digital ----------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function set_datos_doc($datos)
	{
		if (isset($datos['nombre_archivo'])) {
			$handle = fopen($datos['nombre_archivo']['tmp_name'], 'rb');

			$archivo = array();
			$archivo['nombre_archivo'] = $datos['nombre_archivo']['name'];
			$archivo['fecha_upload'] = 'now()';
			
			$this->tabla('doc_digital')->set($archivo);
			$this->tabla('doc_digital')->set_blob('archivo', $handle);
		}
	}
 	
	function get_datos_doc()
	{
		$datos = $this->tabla('doc_digital')->get();
		if (!empty ($datos)) {
			$datos['blob'] = $this->tabla('doc_digital')->get_blob('archivo');
		}
		return $datos;
	}

	function eliminar_datos_doc()
	{
		// La tabla tiene una única fila.
		$this->tabla('doc_digital')->eliminar_filas();	
	}
	
	//-----------------------------------------------------------------------------------
	//---- sga_licencias_propuestas -----------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function set_datos_propuestas($datos)
	{
		$propuestas = $this->tabla('propuestas')->get_filas();
		
		$propuestas_aplanadas = aplanar_matriz($propuestas, 'propuesta');
		$propuestas_a_borrar = array_diff($propuestas_aplanadas, $datos);
		$propuestas_a_insertar = array_diff($datos, $propuestas_aplanadas);

		$propuestas_asociativas = rs_convertir_asociativo($propuestas, array('propuesta'), apex_datos_clave_fila);
		
		foreach ($propuestas_a_borrar as $propuesta) {
			$this->tabla('propuestas')->eliminar_fila($propuestas_asociativas[$propuesta]);
		}

		foreach ($propuestas_a_insertar as $propuesta) {
			$this->tabla('propuestas')->nueva_fila(array('propuesta' => $propuesta));
		}
	}

	//-----------------------------------------------------------------------------------

	/**
	 * Valida que el rango de fechas de la licencia sea válido.
	 *
	 */
	function pre_sincronizar()
	{
		$datos = $this->get_datos();
		if ($datos['estado'] != licencia::estado_rechazado) {
			// Se obtienen las fechas de inicio y fin de la licencia de acuerdo al tipo.
			switch ($datos['alcance']) {
				case licencia::alcance_periodos_lectivos:
					$datos_periodo_lectivo = guarani::co('co_periodos_lectivos')->get_datos_periodo_lectivo($datos['periodo_lectivo_desde']);
					$fecha_desde = $datos_periodo_lectivo['periodo_fecha_inicio'];
					$fecha_hasta = guarani::co('co_licencias')->get_fecha_hasta_lic_periodo($datos['anio_academico'], $datos_periodo_lectivo['periodo_fecha_inicio'], $datos['periodo_lectivo_tipo'], $datos['cant_periodos_lectivos']);
					break;
				case licencia::alcance_rango_fechas:
					$fecha_desde = $datos['fecha_desde'];
					$fecha_hasta = $datos['fecha_hasta'];
					break;
			}
			$this->validar_fechas_desde_hasta($fecha_desde, $fecha_hasta);

			$licencia = isset($datos['licencia']) ? $datos['licencia'] : null;
			if (!is_null($fecha_desde) && !is_null($fecha_hasta)) {
				$this->validar_superposicion_licencias($fecha_desde, $fecha_hasta, $datos['persona'], $licencia);
			}

			if ($this->hay_errores()) {
				throw guarani::error($this->get_mensaje_error());
			}
		}
	}
}