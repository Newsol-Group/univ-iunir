<?php
/**
 * Representa una Sucomisión de una Comisión de una actividad
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class cn_ent_subcomision extends cn_entidad
{
	function verificar_editabilidad()
	{
		// Verifica si la entidad Subcomisión actualmente cargada puede ser editada, 
		// de otro modo llama a la función de la clase padre que bloquea la edición de la misma.
		$id_comision = $this->tabla('subcomisiones')->get_columna('comision');
		// No es editable si existen actas ya cerradas para la comisión padre de la subcomisión en cuestión.
		$filtro = " sga_actas.estado = '" . acta::estado_cerrada . "'";
		$actas_cerradas = toba::consulta_php('co_evaluaciones')->get_cant_actas_evaluaciones_para_comision($id_comision, $filtro);
		if ($actas_cerradas > 0) {
			$this->bloquear_edicion();
		} else {
			$this->habilitar_edicion();
		}
	}

	function hay_cambios()
	{
		return $this->dep('datos')->hay_cambios();
	}

	/**
	 * Utilizada cuando se crea la Subcomisión asociándola a la Comisión padre (sin validar datos).
	 */
	function set_datos_iniciales($datos)
	{
		$this->tabla('subcomisiones')->set($datos);
	}

	//--------------------------------------------------------------------------

	function get_datos_generales()
	{
		return $this->tabla('subcomisiones')->get();
	}

	function set_datos_generales($datos)
	{
		$this->validar_datos_generales($datos);
		$this->tabla('subcomisiones')->set($datos);
	}

	//--------------------------------------------------------------------------

	function get_datos_docentes_subcomision()
	{
		return $this->tabla('docentes_subcomision')->get_filas();
	}

	function set_datos_docentes_subcomision($datos)
	{
		$comision = $this->get_comision_subcomision();
		$datos_periodo = toba::consulta_php('co_comisiones')->get_datos_periodo_comision($comision);
		$this->validar_datos_docentes($datos, $datos_periodo);
		$this->tabla('docentes_subcomision')->procesar_filas($datos);
	}

	function eliminar_docente_subcomision($id_fila_docente)
	{
		if (!is_null($id_fila_docente)) {
			$this->tabla('docentes_subcomision')->eliminar_fila($id_fila_docente);
		}
	}

	/**
	 * Se crea un nuevo docente asociado a la subcomisión a partir del array recibido con el id del docente.
	 */
	function agregar_docente_subcomision($datos_docente)
	{
		/**
		 * Cada nuevo docente asociado se crea con fechas desde y hasta por defecto para su asignación, tomando los valores 
		 * de las fechas inicio y fin seteadas para el mismo en la asignación a la comisión. De no existir estas fechas 
		 * se toman inicio y fin del dictado del período lectivo asociado a la comisión padre de esta subcomisión. 
		 * Si la fecha de inicio es menor a la actual, entonces tomar como fecha desde la fecha actual.
		 */
		
		//Asignación docente para el docente y la comisión.
		$comision = $this->get_comision_subcomision();
		$docente = toba::db()->quote($datos_docente['docente']);
		$filtro = "sga_docentes_comision.docente = $docente";
		$asign_docente = current(toba::consulta_php('co_comisiones')->get_docentes_comision($comision, $filtro));
		if (isset($asign_docente['fecha_desde']) && isset($asign_docente['fecha_hasta'])) {
			$datos_docente['fecha_desde'] = $asign_docente['fecha_desde'];
			$datos_docente['fecha_hasta'] = $asign_docente['fecha_hasta'];
		} else {
			$fecha_actual = guarani_fecha::get_hoy();
			$datos_periodo = toba::consulta_php('co_comisiones')->get_datos_periodo_comision($comision);
			if (guarani_fecha::es_menor($datos_periodo['fecha_inicio_dictado'], $fecha_actual)) {
				$datos_docente['fecha_desde'] = $fecha_actual;
			} else {
				$datos_docente['fecha_desde'] = $datos_periodo['fecha_inicio_dictado'];
			}
			$datos_docente['fecha_hasta'] = $datos_periodo['fecha_fin_dictado'];
		}
		if (isset($asign_docente['responsabilidad_nombre'])) {
			$datos_docente['responsabilidad_nombre'] = $asign_docente['responsabilidad_nombre'];
		}
		$this->tabla('docentes_subcomision')->nueva_fila($datos_docente);
	}

	//--------------------------------------------------------------------------

	function get_datos_bandas_horarias()
	{
        $datos = $this->tabla('bandas_horarias')->get_filas();
        foreach (array_keys($datos) as $key) {
            // Miércoles y Sábado llevan acento
            switch (guarani_texto::limpiar_acentos($datos[$key]['dia_semana_nombre'])) {
                case comunes::lunes:
                    $datos[$key]['id_dia'] = 1;
                break;
                case comunes::martes:
                    $datos[$key]['id_dia'] = 2;
                break;
                case comunes::miercoles:
                    $datos[$key]['id_dia'] = 3;
                break;
                case comunes::jueves:
                    $datos[$key]['id_dia'] = 4;
                break;
                case comunes::viernes:
                    $datos[$key]['id_dia'] = 5;
                break;
                case comunes::sabado:
                    $datos[$key]['id_dia'] = 6;
                break;
                case comunes::domingo:
                    $datos[$key]['id_dia'] = 7;
                break;
            }
        }
        $datos = rs_ordenar_por_columnas($datos, ['id_dia', 'hora_inicio_visual']);
        return $datos;
	}

	function eliminar_banda_horaria($id_fila)
	{
		$this->tabla('bandas_horarias')->eliminar_fila($id_fila);
	}

	function agregar_banda_horaria($datos)
	{
		$this->tabla('bandas_horarias')->nueva_fila($datos);
	}

	//--------------------------------------------------------------------------
	
	/**
	 * Obtiene la comisión a la que está asociada la subcomisión actualmente cargada en la relación.
	 */
	function get_comision_subcomision()
	{
		$datos_subcom = $this->get_datos_generales();
		return $datos_subcom['comision'];
	}

	//----------------------------------------------------------------------------

	function validar_datos_generales($datos)
	{
		$comision = $this->get_comision_subcomision();
		//Si se está editando una comisión se obtiene el id de la misma, si se está agregando este será null.
		$datos['subcomision'] = $this->tabla('subcomisiones')->get_columna('subcomision');
		// Se valida que la combinación nombre y tipo de la subcomisión sea única para la comisión a la que se agrega.
		$this->validar_nombre_tipo_subcomision($comision, $datos['nombre'], $datos['tipo_clase'], $datos['subcomision']);
		// Si se definió cupo para la Subcomisión y también para la Comisión asociada, se verifica que el primero no sea mayor al último.
		$datos_comision = toba::consulta_php('co_comisiones')->get_datos_basicos_comision($comision);
		if (isset($datos['cupo']) && isset($datos_comision['cupo'])) {
			$this->validar_cupo($datos['cupo'], $datos_comision['cupo']);
		}
		// Se verifica que se hayan ingresado bien los valores para el rango de letras de la subcomisión.
		$this->validar_ingreso_letras_rango($datos);
		// Se verifica si el rango de letras ingresados para cubrir por la subcomisión es válido, si es que fue definido.
		if (isset($datos['letra_desde']) && isset($datos['letra_hasta'])) {
			$this->validar_rango_letras_subcomision($datos['letra_desde'], $datos['letra_hasta']);
			// Si el rango de letras para la Comisión asociada está definido, se verifica que el de la subcomisión esté dentro de este.
			if (isset($datos_comision['letra_desde']) && isset($datos_comision['letra_hasta'])) {
				$this->validar_rango_letras_subcomision_comision($datos['letra_desde'], $datos['letra_hasta'], $datos_comision['letra_desde'], $datos_comision['letra_hasta']);
			}
		}
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
	 * Verifica que el cupo establecido para la Subcomisión no supere al establecido para la Comisión padre.
	 */
	function validar_nombre_tipo_subcomision($comision, $nombre, $tipo, $subcomision = null)
	{
		if (!toba::consulta_php('co_comisiones')->validar_nombre_tipo_subcomision($comision, $nombre, $tipo, $subcomision)) {
			$datos_tipo = current(toba::consulta_php('co_clases')->get_nombre_tipo_clase($tipo));
			$this->set_error('800CUR_subco_nombretipo_duplicada', array($datos_tipo['tipo_clase_nombre'], $nombre));
		}
	}

	/**
	 * Verifica que el cupo establecido para la Subcomisión no supere al establecido para la Comisión padre.
	 */
	function validar_cupo($cupo_subc, $cupo_com)
	{
		if ($cupo_subc > $cupo_com) {
			$this->set_error('800CUR_error_cupo_subcomision');
		}
	}

	/**
	 * Verifica que se hayan ingresado ambas letras del rango de la comisión o ninguna de ellas.
	 */
	function validar_ingreso_letras_rango($datos)
	{
		if ((isset($datos['letra_desde']) && !isset($datos['letra_hasta'])) ||
				!isset($datos['letra_desde']) && isset($datos['letra_hasta'])) {
			$this->set_error('800CUR_completar_letras', array('Subcomisión'));
		}
	}

	/**
	 * Verifica si el rango de letras ingresado es válido.
	 * 
	 * @param string 	desde	letra inicial del rango.
	 * @param string	hasta	letra final del rango.
	 */
	function validar_rango_letras_subcomision($desde, $hasta)
	{
		$letra_desde = strtolower($desde);
		$letra_hasta = strtolower($hasta);
		if ($letra_desde > $letra_hasta) {
			$this->set_error('800CUR_error_letras');
		}
	}

	/**
	 * Verifica si el rango de letras ingresado para la Subcomisión es válido respecto al ingresado 
	 * para la Comisión padre asociada. 
	 */
	function validar_rango_letras_subcomision_comision($desde_sub, $hasta_sub, $desde_com, $hasta_com)
	{
		$letra_desde_sub = strtolower($desde_sub);
		$letra_hasta_sub = strtolower($hasta_sub);
		$letra_desde_com = strtolower($desde_com);
		$letra_hasta_com = strtolower($hasta_com);
		if (!(($letra_desde_sub >= $letra_desde_com) && ($letra_hasta_sub <= $letra_hasta_com))) {
			$this->set_error('800CUR_error_letras_subcomision');
		}
	}

	//----------------------------------------------------------------------------

	function validar_datos_docentes($datos, $datos_periodo)
	{
		// Se verifican los datos para cada docente que se asoció a la subcomisión.
		foreach ($datos as $docente) {
			$this->validar_existencia_fechas($docente);
			if (isset($docente['fecha_desde']) && isset($docente['fecha_hasta'])) {
				$this->validar_consistencia_fechas($docente['fecha_desde'], $docente['fecha_hasta'], $docente['nombre_persona']);
				if (!$this->hay_errores()) {
					$this->validar_fechas_en_periodo_lectivo($datos_periodo['periodo_fecha_inicio'], $datos_periodo['periodo_fecha_fin'], $docente['fecha_desde'], $docente['fecha_hasta'], $docente['nombre_persona']);
				}
			}
		}

		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function validar_existencia_fechas($docente)
	{
		if ((isset($docente['fecha_desde']) && (!isset($docente['fecha_hasta']))) || (isset($docente['fecha_hasta']) && (!isset($docente['fecha_desde'])))) {
			$this->set_error('800CUR_completar_fechas', array($docente['nombre_persona']));
		}
	}

	function validar_consistencia_fechas($fecha_desde, $fecha_hasta, $nombre_fila_docente)
	{
		$fechas_consistentes = guarani_fecha::es_menor($fecha_desde, $fecha_hasta);
		if (!$fechas_consistentes) {
			$this->set_error('800_control_fechas', array('Hasta', 'menor o igual', 'Desde para el docente ' . $nombre_fila_docente));
		}
	}

	function validar_fechas_en_periodo_lectivo($fecha_inicio_per_lect, $fecha_fin_per_lect, $fecha_desde, $fecha_hasta, $nombre_fila_docente)
	{
		$incluido_en_periodo = guarani_fecha::rango_incluido_en_rango($fecha_desde, $fecha_hasta, $fecha_inicio_per_lect, $fecha_fin_per_lect);
		if (!$incluido_en_periodo) {
			$this->set_error('800CUR_fechas_fuera_rango', array($nombre_fila_docente));
		}
	}

}