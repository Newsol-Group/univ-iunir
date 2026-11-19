<?php
/**
 *
 *
 * @package 	Tesis
 * @subpackage	-
 * 
 */
class cn_ent_tesis extends cn_entidad
{
	protected $s__enviar_aviso;
	
	function cargar_atributos_parametros()
	{
		$this->resetear_atributos_parametros();
		$operacion = guarani::get_id_operacion();

		$alumnos = $this->get_datos_alumnos_asociados();
		
		if (!empty($alumnos)) {
			$propuestas = array();
			foreach ($alumnos as $alumno) {
				$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($alumno['alumno']);
				if (!in_array($datos_alumno['propuesta'], $propuestas)) {
					$propuestas[] = $datos_alumno['propuesta'];
				}
			}
			guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);
		}
	}
	
	function cargar($id)
	{
		parent::cargar($id);
		$this->cargar_atributos_parametros();
	}

	//--------------------------------------------------------------------------
	//---- Tesis
	//--------------------------------------------------------------------------

	function set_datos($datos, $tabla = null, $validar = true)
	{
		if (isset($datos['titulo'])) {
			$this->validar_datos($datos);
		}
		$this->tabla('tesis')->set($datos);
		$this->analizar_envio_aviso($datos);
	}

	function get_datos($tabla = null)
	{
		return $this->tabla('tesis')->get();
	}

	function get_estado_actual()
	{
		return $this->tabla('tesis')->get_columna('estado_actual');
	}

	//--------------------------------------------------------------------------
	//---- Cambios de estados de tesis
	//--------------------------------------------------------------------------

	function set_datos_cambio_estado($datos)
	{
		if ($datos['estado_anterior'] == '') {
			unset($datos['estado_anterior']);
		}
		$this->tabla('tesis_cambios_estados')->set($datos);
	}

	function get_datos_cambios_estados()
	{
		$busqueda = $this->tabla('tesis_cambios_estados')->nueva_busqueda();
		$busqueda->set_columnas_orden(array('fecha' => SORT_ASC));
		return $busqueda->buscar_filas();
	}

	//--------------------------------------------------------------------------
	//---- Alumnos de la tesis
	//--------------------------------------------------------------------------

	function set_datos_alumnos($alumnos)
	{
		$this->validar_datos_alumnos($alumnos);
		foreach ($alumnos as $alumno) {
			$this->tabla('tesis_alumnos')->nueva_fila($alumno);
		}
		$this->cargar_atributos_parametros();
	}

	function get_datos_alumnos_asociados()
	{
		return $this->tabla('tesis_alumnos')->get_filas();
	}

	function eliminar_datos_alumno_asociado($clave)
	{
		$salida = $this->tabla('tesis_alumnos')->eliminar_fila($clave);
		$this->cargar_atributos_parametros();
		return $salida;
	}

	//--------------------------------------------------------------------------
	//---- Tutores de la tesis
	//--------------------------------------------------------------------------

	function set_datos_tutores($tutores)
	{
		foreach ($tutores as $clave => $valor) {
			$tutores[$clave][apex_ei_analisis_fila] = 'M';
		}

		$this->tabla('tesis_tutores')->procesar_filas($tutores);
	}

	function agregar_tutores($tutores)
	{
		$this->validar_datos_tutores($tutores);
		foreach ($tutores as $tutor) {
			$this->tabla('tesis_tutores')->nueva_fila($tutor);
		}
	}

	function get_datos_tutores_asociados()
	{
		return $this->tabla('tesis_tutores')->get_filas();
	}

	function eliminar_datos_tutor_asociado($clave)
	{
		return $this->tabla('tesis_tutores')->eliminar_fila($clave);
	}

	//--------------------------------------------------------------------------
	//---- Evaluadores de la tesis
	//--------------------------------------------------------------------------

	function set_datos_evaluadores($evaluadores)
	{
		foreach ($evaluadores as $clave => $valor) {
			$evaluadores[$clave][apex_ei_analisis_fila] = 'M';
		}

		$this->tabla('tesis_evaluadores')->procesar_filas($evaluadores);
	}

	function agregar_evaluadores($evaluadores)
	{
		$this->validar_datos_evaluadores($evaluadores);
		foreach ($evaluadores as $evaluador) {
			$this->tabla('tesis_evaluadores')->nueva_fila($evaluador);
		}
	}

	function get_datos_evaluadores_asociados()
	{
		return $this->tabla('tesis_evaluadores')->get_filas();
	}

	function eliminar_datos_evaluador_asociado($clave)
	{
		return $this->tabla('tesis_evaluadores')->eliminar_fila($clave);
	}

	//--------------------------------------------------------------------------
	//---- Operaciones auxiliares
	//--------------------------------------------------------------------------

	function analizar_envio_aviso($datos)
	{
		if (isset($datos['circuito']) && isset($datos['estado_actual']) && isset($datos['accion'])) {
			$camino = current(toba::consulta_php('co_circuitos_tramite')->get_camino_por_circuito_estado_accion($datos['circuito'], $datos['estado_actual'], $datos['accion']));
			$this->s__enviar_aviso = $camino['enviar_aviso_alumno'] == comunes::si;
		}
	}

	function enviar_aviso_alumno()
	{
		return (!empty($this->s__enviar_aviso)) ? $this->s__enviar_aviso : false;
	}

	//--------------------------------------------------------------------------
	//---- Validaciones
	//--------------------------------------------------------------------------
	
	/**
	 * Valida que no se ingrese una tesis con el título repetido y que
	 * las fechas de aprobación y vencimiento sean mayores a la actual
	 *
	 * @param array $datos Datos de la tesis a validar.
	 */
	function validar_datos($datos)
	{
		$datos['tesis'] = $this->tabla('tesis')->get_columna('tesis');
		$this->validar_titulo_tesis($datos['titulo'], $datos['tesis']);
		$this->validar_fechas($datos);

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_titulo_tesis($titulo, $tesis)
	{
		if (!toba::consulta_php('co_tesis')->validar_titulo_tesis($titulo, $tesis)) {
			$this->set_error('dato_duplicado', array('titulo de la tesis'));
		}
	}

	function validar_fechas($datos)
	{	
        //El estado se controla para que no valide mas las fechas una vez aprobada la tesis
        $estado = $this->get_estado_actual();
        if($estado != tesis_nucleo::estado_tesis_aprobada){
            
            // Fecha Aprobación debe ser mayor o igual a Fecha Actual
            /*if (isset($datos['fecha_aprobacion']) && guarani_fecha::es_mayor(guarani_fecha::get_hoy(), $datos['fecha_aprobacion'])) {
                $this->set_error('800_control_fechas', array('aprobación', 'menor', 'actual'));
            }*/

            // Fecha Vencimiento debe ser mayor o igual a Fecha Actual
            if (isset($datos['fecha_vencimiento']) && guarani_fecha::es_mayor(guarani_fecha::get_hoy(), $datos['fecha_vencimiento'])) {
                $this->set_error('800_control_fechas', array('vencimiento', 'menor', 'actual'));
            }
        }		
	}

	/**
	 * Valida que no se cargue como alumno a un tutor o evaluador de la tesis.
	 *
	 * @param array $alumnos Alumnos de la tesis a validar.
	 */
	function validar_datos_alumnos($alumnos)
	{
		$this->validar_alumnos_vs_tutores($alumnos, null);
		$this->validar_alumnos_vs_evaluadores($alumnos, null);

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	/**
	 * Valida que no se cargue como tutor a un alumno que se encuentra dando la tesis.
	 * Emite un mensaje de advertencia en caso de que un tutor sea también evaluador.
	 *
	 * @param array $tutores Tutores de la tesis a validar.
	 */
	function validar_datos_tutores($tutores)
	{
		$this->validar_alumnos_vs_tutores(null, $tutores);
		$this->validar_tutores_vs_evaluadores($tutores, null);

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	/**
	 * Valida que no se cargue como evaluador a un alumno que se encuentra dando la tesis.
	 * Emite un mensaje de advertencia en caso de que un evaluador sea también tutor.
	 *
	 * @param array $evaluadores Evaluadores de la tesis a validar.
	 */
	function validar_datos_evaluadores($evaluadores)
	{
		$this->validar_tutores_vs_evaluadores(null, $evaluadores);
		$this->validar_alumnos_vs_evaluadores(null, $evaluadores);

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_alumnos_vs_tutores($alumnos = null, $tutores = null)
	{
		$alumnos = is_null($alumnos) ? $this->get_datos_alumnos_asociados() : $alumnos;
		$tutores = aplanar_matriz(is_null($tutores) ? $this->get_datos_tutores_asociados() : $tutores, 'persona');

		foreach ($alumnos as $alumno) {
			if (in_array($alumno['persona'], $tutores)) {
				$this->set_error('800TES_alumno_no_tutor', array($alumno['apellido_y_nombre']));
			}
		}
	}

	function validar_tutores_vs_evaluadores($tutores = null, $evaluadores = null)
	{
		$tutores = is_null($tutores) ? $this->get_datos_tutores_asociados() : $tutores;
		$evaluadores = aplanar_matriz(is_null($evaluadores) ? $this->get_datos_evaluadores_asociados() : $evaluadores, 'persona');

		foreach ($tutores as $tutor) {
			if (in_array($tutor['persona'], $evaluadores)) {
				$mensaje = $this->get_mensaje('800TES_persona_evaluador_tutor', array(isset($tutor['apellido_nombres']) ? $tutor['apellido_nombres'] : $tutor['nombre_completo']));
				toba::notificacion()->agregar($mensaje, 'info');
			}
		}
	}

	function validar_alumnos_vs_evaluadores($alumnos = null, $evaluadores = null)
	{
		$alumnos = is_null($alumnos) ? $this->get_datos_alumnos_asociados() : $alumnos;
		$evaluadores = aplanar_matriz(is_null($evaluadores) ? $this->get_datos_evaluadores_asociados() : $evaluadores, 'persona');

		foreach ($alumnos as $alumno) {
			if (in_array($alumno['persona'], $evaluadores)) {
				$this->set_error('800TES_alumno_no_evaluador', array($alumno['apellido_y_nombre']));
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- Validaciones generales
	//--------------------------------------------------------------------------
	
	/**
	 * Validaciones generales antes de guardar.
	 */
	function validar_pre_guardar($datos_cambio_estado)
	{
		$datos = $this->get_datos();
		$this->validar_alumnos();
		$this->validar_accion($datos_cambio_estado);
		$this->validar_cantidad_maxima_tesis_tutor();
		$this->validar_actividad_tesis($datos['actividad']);

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	/**
	 * Se valida que haya al menos un alumno cargado
	 */
	function validar_alumnos()
	{
		$alumnos = $this->get_datos_alumnos_asociados();
		if (empty($alumnos)) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un alumno'));
			throw guarani::error($mje);
		}
	}

	/**
	 * Valida que a toda tesis creada se le ejecute una acción sobre ella, de
	 * las que permita el circuito seleccionado, y asi setearle su estado actual.
	 */
	function validar_accion($datos_cambio_estado)
	{
		if ($this->es_nueva() && !isset($datos_cambio_estado)) {
			$this->set_error('dato_faltante', array('Acción'));
		}
	}

	/**
	 * Si la tesis no se encuentra en un estado final y existe un límite establecido respecto de
	 * la cantidad de veces que un tutor puede actuar como tal en una tesis, entonces se verifica
	 * que los tutores de la misma no sean tutores de más tesis abiertas que las permitidas.
	 */
	function validar_cantidad_maxima_tesis_tutor()
	{
		$tesis = $this->tabla('tesis')->get_columna('tesis');
		if (!toba::consulta_php('co_tesis')->tesis_en_estado_final($tesis)) {
			$cant_max_tesis_tutor = guarani::parametros()->get_valor('tesis_cant_maxima_tutor');
			if ($cant_max_tesis_tutor > 0) {
				foreach ($this->get_datos_tutores_asociados() as $tutor) {
					$cant_tesis_tutor = toba::consulta_php('co_tesis')->get_cantidad_tesis_abiertas_tutor($tutor['persona'], $tesis);
					if ($cant_tesis_tutor >= $cant_max_tesis_tutor) {
						$this->set_error('800TES_tutor_no_disponible', array($tutor['nombre_completo']));
					}
				}
			}
		}
	}

	/**
	 * Valida que la actividad para la que se hace la tesis este
	 * dentro de la Propuesta - Plan de cada uno de los alumnos.
	 */
	function validar_actividad_tesis($actividad)
	{
		foreach ($this->get_datos_alumnos_asociados() as $alumno) {
			if (!toba::consulta_php('co_alumnos')->validar_actividad_en_propuesta_plan_alumno($alumno['alumno'], $actividad)) {
				$this->set_error('800TES_actividad_no_propuesta', array('La actividad seleccionada'));
				break;
			}
		}
	}

}
?>