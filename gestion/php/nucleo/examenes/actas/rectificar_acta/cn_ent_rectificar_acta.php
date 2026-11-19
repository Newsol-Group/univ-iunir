<?php
/**
 *
 * 
 * @package Examenes
 * @subpackage Actas
 * 
 */
class cn_ent_rectificar_acta extends cn_ent_acta_examen
{
	// Variable para mantener los alumnos a los cuales se les agregó detalle_eval_examen
	protected $s__alumnos_agregados;

	function get_listado($filtro)
	{
		$filtro_actas = " sga_actas.estado    =  '" . acta::estado_cerrada . "' AND";
		$filtro_actas .= " sga_actas.tipo_acta =  '" . acta::tipo_normal . "' ";
		$filtro = $filtro ? "$filtro AND $filtro_actas" : $filtro_actas;
		$filtro .= " AND vw_mesas_examen.llamado_mesa_estado = '" . mesa_de_examen::mesa_estado_activo . "'";
		return toba::consulta_php('co_actas')->get_listado_actas_examen_con_sin_mesas($filtro);
	}

	function get_alumnos_acta_examen($id_acta = null, $estado = null)
	{
		$params_sistema = array('exa_orden_detalle_actas' => guarani::parametros()->get_valor('exa_orden_detalle_actas'));
		return toba::consulta_php('co_actas')->get_alumnos_acta_examen($id_acta, $estado, $params_sistema);
	}

	//-----------------------------------------------------------------------------------
	//---- Tabla de Detalle de Evaluaciones de Alumnos en Exámen ------------------------
	//-----------------------------------------------------------------------------------

	function agregar_alumnos_mesa_llamado($alumnos, $llamado_mesa)
	{
		if (!empty($alumnos)) {
			foreach ($alumnos as $alumno) {
				$this->agregar_alumno_mesa_llamado($alumno, $llamado_mesa);
			}
		}
	}

	/**
	 * Agrega alumnos de la mesa a la Rectificativa que se está creando, se marcan estos
	 * alumnos con true en el campo agregado.
	 * Los datos deben ser los del detalle del Alumno en el Acta Original cerrada (para alumnos del acta).
	 * Al momento de generar el acta se asigna al campo id_acta por el correspondiente del Acta Rectificativa generada.
	 * Los alumnos pueden estar o no en el detalle ya que se seleccionan del grupo de alumnos del acta, sus rectificativas y 
	 * aquellos de la mesa que no están en ningún acta.
	 *
	 * @param array $alumnos Alumnos a agregar al acta rectificativa.
	 */
	function agregar_alumno_mesa_llamado($alumno, $llamado_mesa)
	{
		// Verifica si es un alumno del acta cerrada que se está rectificando, en cuyo caso debe agregarse el detalle.
		if ($this->es_alumno_acta($alumno)) {
			//Obtiene los datos del detalle del alumno para el último acta válida del acta seleccionada y sus rectificativas.
			$datos_detalle_original = toba::consulta_php('co_actas')->get_detalle_alumno_llamado_mesa_examen($alumno['alumno'], $llamado_mesa);
			$datos_detalle_original['inscripto'] = toba::consulta_php('co_alumnos')->tiene_inscripciones_examen($alumno['alumno'], "vw_insc_examen.llamado_mesa = $llamado_mesa") ? comunes::si : comunes::no;
			$datos_detalle_original['agregado'] = true;
			$this->agregar_detalle($datos_detalle_original);
			$this->s__alumnos_agregados[$alumno['alumno']] = true;
		} else {
			parent::agregar_alumno_mesa($alumno);
		}
	}

	/**
	 * Elimina el Alumno dado de los Alumnos asociados al Acta Rectificativa. Elimina el detalle si este fue agregado
	 * durante esta operación. En caso contrario solo desasocia al alumno del Acta.
	 */
	function eliminar_alumno($id_eval_detalle_axamen)
	{
		$detalle = $this->get_detalle($id_eval_detalle_axamen);
		if (isset($this->s__alumnos_agregados[$detalle['alumno']])) {
			if (!$this->es_alumno_acta($detalle)) {
				// si se agregaron las evaluaciones y el alumno no estaba en el acta rectificada
				// elimino detalle de evaluacion manual
				$this->eliminar_detalle_de_eval_manuales($detalle['alumno']);
			}
			// si se agregaron las evaluaciones elimino las evaluaciones automaticas
			$this->eliminar_detalle_de_eval_automatica($id_eval_detalle_axamen);
			unset($this->s__alumnos_agregados[$detalle['alumno']]);
		} else {
			// si no se agregaron las evaluaciones , o sea ya existian solo las modifico.
			$this->modificar_fila_columna_detalle($id_eval_detalle_axamen, 'agregado', false);
		}
	}

	/**
	 * Redefine el método utilizado desde el selector para recuperar los alumnos de la mesa 
	 * posibles a asociar a la Rectificativa.
	 */
	function get_metodo_alumnos_mesa_disponibles()
	{
		$array['clase_co'] = 'co_actas';
		$array['metodo'] = 'get_alumnos_acta_examen_rectificada';
		$array['parametros'] = array($this->get_evaluacion(), $this->id_acta_ficticia, $this->get_acta());
		return $array;
	}

	function filtro_alumnos_otros_disponibles()
	{
		// El texto [EVALUACION] será reemplazado donde se use esta query por el id de evaluacion de la mesa de examen.
		$filtro = "AND	sga_alumnos.alumno NOT IN (
					-- Alumnos que no estén en actas cerradas (esto es no anuladas ya que las abiertas estan en eval_detalle)
					SELECT sga_actas_detalle.alumno
					  FROM sga_actas
					  JOIN sga_actas_detalle ON sga_actas_detalle.id_acta = sga_actas.id_acta
					 WHERE sga_actas.evaluacion			= [EVALUACION]
					   AND sga_actas.estado				= '" . acta::estado_cerrada . "'
					   AND sga_actas_detalle.rectificado	= '" . comunes::no . "'
					   AND sga_actas_detalle.estado		= '" . acta::detalle_estado_activo . "'
					UNION
					-- Y que no esten en actas abiertas
					SELECT sga_eval_detalle_examenes.alumno
					  FROM sga_eval_detalle_examenes
					 WHERE sga_eval_detalle_examenes.evaluacion = [EVALUACION]
					   AND sga_eval_detalle_examenes.id_acta IS NOT NULL
					)";
		return $filtro;
	}

	/**
	 * Verifica si el alumno pertenece al acta que se está rectificando o a un alumno de la mesa.
	 */
	function es_alumno_acta($alumno)
	{
		if (!$this->existe_detalle(array('alumno' => $alumno['alumno']))) {
			return true;
		} else {
			$detalles = $this->get_detalle($this->get_id_detalle(array('alumno' => $alumno['alumno'])));
			if (isset($detalles['id_acta'])) {
				return $detalles['id_acta'] == $this->get_acta();
			} else {
				return false;
			}
		}

		/**
		 * Es suficiente con verificar si existe el detalle para el alumno, entonces es de la mesa y no del Acta, 
		 * de lo contrario es del acta, ya que está si está en el acta cerrada que se está rectificando no está en 
		 * el detalle de evaluaciones de examánes (si no en el detalle del acta).
		 */
		return !$this->existe_detalle($alumno);
	}

	//-----------------------------------------------------------------------------------
	//---- Proceso de Sincronización y actualización de la Versión del Acta -------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Genera una nueva acta de exámen rectificativa para la mesa, inserta una nueva cabecera y asocia a la misma en 
	 * los detalles de evaluaciones correspondientes el id de la rectificativa.
	 *
	 * @param	int		$nro_acta		nro de acta de la rectificativa que se está generando.
	 * @param	int 	$documento		documento respaldatorio asociado a la rectificativa que se está generando.
	 * @param	str 	$observaciones	observaciones del acta rectificativa que se está generando.
	 */
	function generar_rectificativa($nro_acta, $documento = null, $observaciones)
	{
		try {
			$this->desactivar_transacciones_anidadas();
			toba::db()->abrir_transaccion();
			$id_acta = $this->generar_cabecera_rectificativa($nro_acta, $documento, $observaciones);
			$this->asociar_detalles_rectificativa($id_acta);
			$this->generar_instancias_rectificativa($id_acta);
			$this->sincronizar();
			toba::db()->cerrar_transaccion();
			$this->activar_transacciones_anidadas();
			return $id_acta;
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}

	/**
	 * Inserta una nueva cabecera de una Rectificativa para un Acta de Exámen cerrada seleccionada, con los datos
	 * dados como parámetro.
	 *
	 * @param	int		$nro_acta		nro de acta de la rectificativa que se está generando.
	 * @param	int 	$documento		documento respaldatorio asociado a la rectificativa que se está generando.
	 * @param	str 	$observaciones	observaciones del acta rectificativa que se está generando.
	 */
	function generar_cabecera_rectificativa($nro_acta, $documento = null, $observaciones)
	{
		// Datos por defecto para la creación de un acta.
		$evaluacion = $this->get_evaluacion();
		$llamado_mesa = $this->get_llamado_mesa();
		$acta_original = $this->get_acta();
		$lineas_folio = guarani::parametros()->get_valor('exa_lineas_folio_acta_examen');
		$digital = guarani::parametros()->get_valor('exa_utiliza_acta_digital');
		$origen = acta::origen_examen;
		$tipo_acta = acta::tipo_rectificativa;
		// Se inserta el nuevo acta.
		return guarani::act('act_actas')->insertar_acta_examen($nro_acta, $origen, $evaluacion, $llamado_mesa, $lineas_folio, $tipo_acta, $observaciones, $acta_original, $documento, $digital);
	}

	/**
	 * Le inserta en en los Alumnos asociados 
	 * el id del Acta Rectificativa recientemente generada
	 * @param int $id_acta id del Acta Rectificativa generada a asociar a los detalles de alumnos.
	 */
	function asociar_detalles_rectificativa($id_acta)
	{
		$condiciones = array('agregado' => true);
		$ids = $this->get_ids_detalle($condiciones);
		$this->modificar_filas_columna_detalle($ids, 'id_acta', $id_acta);
	}

	/**
	 * Inserta las Instancias Acta Rectificativa recientemente generada.
	 * @param int $id_acta id del Acta Rectificativa generada.
	 */
	function generar_instancias_rectificativa($id_acta)
	{
		$acta_original = $this->get_acta();
		guarani::act('act_actas')->insertar_rectificativa_instancias_acta_origen($id_acta, $acta_original);
	}

	/**
	 * Determina si la entidad tuvo cambios (sea asociaron alumnos)
	 * @return <type>
	 */
	function hay_cambios_seleccion_alumnos()
	{
		$datos = $this->get_ids_detalle(array('agregado' => true));
		return !empty($datos);
	}
}
?>