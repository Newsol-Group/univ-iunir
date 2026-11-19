<?php
/**
 * Representa un Llamado de un Turno que se asocia a una Mesa de Exámen definida para un Turno y para una Actividad.
 *
 *
 * @package		Examenes
 * @subpackage	Mesas de Examen
 * 
 */
class cn_ent_llamado_mesa_de_examen extends cn_entidad
{
	function cargar($id)
	{
		$this->tabla('docentes')->persistidor()->activar_perfil_de_datos();
		parent::cargar($id);
	}

	function verificar_editabilidad()
	{
		// Desde la operación "Modificar Mesas de Examen" se permite editar el llamado aún cuando existen actas cerradas afectadas.
		if (guarani::get_id_operacion() == mesa_de_examen::cod_op_modificar_mesas) {
			$this->habilitar_edicion();
		} else {
			// Verifica si la entidad Llamado actualmente cargada puede ser editada, de otro modo llama a la función de 
			// la clase padre que bloquea la edición de la misma.
			$datos_llamado = $this->get_datos_llamado();
			
			// No es editable si existen actas ya cerradas para el llamado.
			$llamado = toba::db()->quote($datos_llamado['llamado_mesa']);
			$filtro = " sga_llamados_mesa.llamado_mesa = $llamado AND sga_actas.estado =  '" . acta::estado_cerrada . "'";
			$actas_cerradas = toba::consulta_php('co_evaluaciones')->get_cant_actas_evaluaciones_para_llamados_mesa($datos_llamado['mesa_examen'], $filtro);
			if ($actas_cerradas > 0) {
				$this->bloquear_edicion();
			} else {
				$this->habilitar_edicion();
			}
		}
	}

	//--------------------------------------------------------------------------

	function get_datos_llamado()
	{
		return $this->tabla('llamados_mesa')->get();
	}

	function set_datos_llamado($datos)
	{
		$this->validar_datos_llamado($datos);
		$this->tabla('llamados_mesa')->set($datos);
	}

	function validar_datos_llamado($datos)
	{
		$this->validar_fecha_llamado($datos);
		$this->validar_horas_inicio_fin_llamado($datos);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function validar_fecha_llamado($datos)
	{
		$datos_llamado = $this->get_datos_llamado();
		// La fecha del examen debería estar dentro del rango de inicio y fin del llamado (período asociado).
		$tope_inferior_valido = guarani_fecha::es_mayor_igual($datos['fecha'], $datos_llamado['fecha_inicio']);
		$tope_superior_valido = guarani_fecha::es_menor_igual($datos['fecha'], $datos_llamado['fecha_fin']);
		if (!$tope_inferior_valido || !$tope_superior_valido) {
			$this->set_notificacion('800EXA_examen_fuera_rango', array($datos['nombre']));
		}
		// La fecha de examen no puede coincidir con un día no laboral.
		$datos_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_mesa($datos_llamado['mesa_examen']);
		$es_dia_no_laboral = toba::consulta_php('co_dias_no_laborables')->es_dia_no_laborable_ubicacion($datos['fecha'], $datos_mesa['ubicacion']);
		if ($es_dia_no_laboral) {
			$this->set_notificacion('800_dia_no_laboral');
		}
	}

	function validar_horas_inicio_fin_llamado($datos)
	{
		// La hora de inicio es de ingreso obligatorio.
		if (!isset($datos['hora_inicio'])) {
			$this->set_error('800_dato_dep_obligatorio', array('hora de inicio', "asociación del llamado {$datos['nombre']}."));
		}
		// Se valida que la hora de inicio no sea mayor que la hora de finalización.
		if (isset($datos['hora_inicio']) && isset($datos['hora_finalizacion']) && $datos['hora_inicio'] >= $datos['hora_finalizacion']) {
			$this->set_error('800EXA_rango_horas_llamado', $datos['nombre']);
		}
	}

	//--------------------------------------------------------------------------

	function get_datos_docentes_llamado()
	{
		return $this->tabla('docentes')->get_filas();
	}

	function set_datos_docentes_llamado($datos)
	{
		$this->tabla('docentes')->procesar_filas($datos);
	}

	function eliminar_docente_llamado($id_fila_docente)
	{
		if (!is_null($id_fila_docente)) {
			$this->tabla('docentes')->eliminar_fila($id_fila_docente);
		}
	}

	/**
	 * Se crea un nuevo docente asociado al llamado a partir del array recibido con el id del mismo.
	 */
	function agregar_docente_llamado($datos_docente)
	{
		$this->tabla('docentes')->nueva_fila($datos_docente);
	}

	/**
	 * Se aplica el Tribunal dado al Llamado. Esto implica que se eliminan todos los docentes actualmente asociados al mismo
	 * y agregarse al llamado todos los docentes activos del Tribunal dado con sus respectivos roles.
	 */
	function asociar_docentes_tribunal_llamado($id_tribunal)
	{
		$doc = $this->get_datos_docentes_llamado();
		$this->tabla('docentes')->eliminar_filas();
		$docentes_activos_tribunal = toba::consulta_php('co_tribunales')->get_docentes_activos_tribunal($id_tribunal);
		foreach ($docentes_activos_tribunal as $datos_docente) {
			$this->agregar_docente_llamado($datos_docente);
		}
		$doc = $this->get_datos_docentes_llamado();
	}

	//--------------------------------------------------------------------------

	function get_datos_evaluaciones()
	{
		return $this->tabla('evaluaciones')->get_filas();
	}

	/**
	 * Graba los datos modificados o dados de alta en la evaluación actualmente seleccionada.
	 */
	function set_datos_evaluacion($datos)
	{
		if ($this->hay_evaluacion_seleccionada()) {
			$this->validar_datos_evaluacion($datos);
			$this->tabla('evaluaciones')->set($datos);
		}
	}

	/**
	 * Posiciona el cursor en una evaluación dada seleccionada para editar (con la clave interna del Dt).
	 */
	function seleccionar_evaluacion($id_fila_eval)
	{
		$this->tabla('evaluaciones')->set_cursor($id_fila_eval);
	}

	/**
	 * Resetea el cursor de la tabla de evaluaciones.
	 */
	function deseleccionar_evaluacion()
	{
		$this->tabla('evaluaciones')->resetear_cursor();
	}

	/**
	 * Obtiene los datos de la evaluación correspondiente a la fila donde actualmente 
	 * se encuentra posicionado el cursor.
	 */
	function get_datos_evaluacion_seleccionada()
	{
		if ($this->hay_evaluacion_seleccionada()) {
			return $this->tabla('evaluaciones')->get();
		}
	}

	/**
	 * Determina si hay el cursor está seteado en la tabla de evaluaciones.
	 */
	function hay_evaluacion_seleccionada()
	{
		return $this->tabla('evaluaciones')->hay_cursor();
	}

	/**
	 * Elimina la evaluación correspondiente a la fila donde se encuentra actualmente posicionado el cursor.
	 */
	function eliminar_evaluacion_seleccionada()
	{
		if ($this->hay_evaluacion_seleccionada()) {
			$id_fila = $this->tabla('evaluaciones')->get_cursor();
			$this->tabla('evaluaciones')->eliminar_fila($id_fila);
		}
	}

	/**
	 * Agrega una nueva evaluación para el Llamado, y setea el cursor en la fila recién creada.
	 */
	function nueva_evaluacion($datos)
	{
		// Por defecto la evaluación se guarda con generación 'M' correspondiente a manual, y con 
		// el tipo de evaluación automática en 'N' correspondiente a que no es de tipo automática.
		$id = $this->tabla('evaluaciones')->nueva_fila($datos);
		$this->seleccionar_evaluacion($id);
	}

	//--------------------------------------------------------------------------

	/**
	 * Se obtienen los datos de las instancias para la evaluación actualmente seleccionada en la tabla padre.
	 */
	function get_datos_instancias_evaluacion()
	{
		if ($this->hay_evaluacion_seleccionada()) {
			return $this->tabla('eval_instancias')->get_filas();
		}
	}

	/**
	 * Se setean los datos de las instancias asociadas a la evaluación padre actualmente seleccionada
	 * en la tabla de evaluaciones.
	 */
	function set_datos_instancias_evaluacion($datos)
	{
		if ($this->hay_evaluacion_seleccionada()) {
			// Elimina las instancias asociadas a la evaluación actualmente seleccionada.
			$this->tabla('eval_instancias')->eliminar_filas();
			foreach ($datos as $instancia) {
				$eval = $this->tabla('evaluaciones')->get_cursor();
				$this->tabla('eval_instancias')->nueva_fila(array('instancia' => $instancia));
			}
		}
	}

	//--------------------------------------------------------------------------

	function get_datos_periodos_insc_excep()
	{
		return $this->tabla('excep_periodos')->get_filas();
	}

	/**
	 * Posiciona el cursor en la fila de la tabla de excepciones de períodos dada.
	 */
	function seleccionar_per_insc_excep($id_excep)
	{
		$this->tabla('excep_periodos')->set_cursor($id_excep);
	}

	/**
	 * Determina si hay el cursor está seteado en la tabla de evaluaciones.
	 */
	function hay_per_insc_excep_seleccionada()
	{
		return $this->tabla('excep_periodos')->hay_cursor();
	}

	/**
	 * Obtiene los datos de la excepción correspondiente a la fila donde actualmente se encuentra posicionado el cursor.
	 */
	function get_datos_per_insc_excep_seleccionada()
	{
		if ($this->hay_per_insc_excep_seleccionada()) {
			return $this->tabla('excep_periodos')->get();
		}
	}

	/**
	 * Setea los datos de la excepción al periodo de inscripción fecha apuntado actualmente por el cursor.
	 */
	function set_datos_per_insc_excep($datos)
	{
		$this->validar_datos_excep_periodo($datos);
		$this->tabla('excep_periodos')->set($datos);
	}

	function validar_datos_excep_periodo($datos)
	{
		if (isset($datos['fecha_inicio']) && isset($datos['fecha_fin'])) {
			$this->validar_fechas_inicio_fin($datos['fecha_inicio'], $datos['fecha_fin']);
		}

		if (isset($datos['fecha_inicio']) || isset($datos['fecha_fin'])) {
			$this->validar_fechas_inicio_fin_mesa($datos['fecha_inicio'], $datos['fecha_fin']);
		}
		if (isset($datos['fecha_tope_bajas'])) {
			$this->validar_fecha_tope($datos['fecha_inicio'], $datos['fecha_fin'], $datos['fecha_tope_bajas']);
		}

		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
	 * Determina que las fechas de inicio y fin de la excepción sean válidas.
	 * 
	 * @param date	fecha_inicio
	 * @param date	fecha_fin
	 */
	function validar_fechas_inicio_fin($fecha_inicio, $fecha_fin)
	{
		if (guarani_fecha::es_mayor_timestamp($fecha_inicio, $fecha_fin)) {
			$this->set_error('800_control_fechas', array('inicio', 'mayor', 'fin'));
		}
	}

	/**
	 * Determina si las fechas de inicio y fin son mayores que la fecha de la mesa.
	 * 
	 * @param date	fecha_inicio
	 * @param date	fecha_fin
	 */
	function validar_fechas_inicio_fin_mesa($fecha_inicio, $fecha_fin)
	{
		$datos_llamado = $this->get_datos_llamado();
		if ((guarani_fecha::es_mayor_timestamp($fecha_inicio, $datos_llamado['fecha'])) || (guarani_fecha::es_mayor_timestamp($fecha_fin, $datos_llamado['fecha']))) {
			$this->set_error('800_control_fechas', array('inicio/fin', 'mayor', 'de la mesa'));
		}
	}

	function validar_fecha_tope($fecha_inicio, $fecha_fin, $fecha_tope)
	{
		$datos_llamado = $this->get_datos_llamado();		
		if (guarani_fecha::es_mayor_timestamp($fecha_fin, $fecha_tope)) {
			$this->set_error('800_control_fechas', array('fin', 'mayor', ' de tope'));
		}
		if (guarani_fecha::es_mayor_timestamp($fecha_tope, $datos_llamado['fecha'])) {
			$this->set_error('800_control_fechas', array('tope de bajas', 'mayor', 'de la mesa'));
		}
	}
	
	/**
	 * Resetea el cursor de la tabla de Excepciones a Períodos Inscripción Fechas del Llamado.
	 */
	function deseleccionar_per_insc_excep()
	{
		$this->tabla('excep_periodos')->resetear_cursor();
	}

	/**
	 * Busca una fila de la tabla de excepciones de períodos asociadas al Llamado, cuyo periodo_insc_fecha sea igual al 
	 * dado. Si esta existe retorna el id interno de esta fila, caso contrario se retorna null. 
	 *
	 * @param integer $per_fecha id del periodo_inscripcion_fecha a buscar.
	 */
	function existe_per_insc_excep($per_insc_fecha)
	{
		$id_periodo = $this->tabla('excep_periodos')->get_id_fila_condicion(array('periodo_insc' => $per_insc_fecha));
		if (!empty($id_periodo)) {
			return current($id_periodo);
		} else {
			return null;
		}
	}

	/**
	 * Elimina la excepción correspondiente a la fila donde se encuentra actualmente posicionado el cursor.
	 */
	function eliminar_per_insc_excep()
	{
		if ($this->hay_per_insc_excep_seleccionada()) {
			$id_per = $this->tabla('excep_periodos')->get_cursor();
			if (!is_null($id_per)) {
				$this->tabla('excep_periodos')->eliminar_fila($id_per);
			}
		}
	}

	//--------------------------------------------------------------------------

	function validar_datos_evaluacion($datos)
	{
		// Se verifica si la fecha seteada para la evaluación es mayor o igual a la fecha de exámen del Llamado de Mesa 
		// actualmente en edición, para el cual se agregó la validación.
		$datos_llamado = $this->get_datos_llamado();
		if (isset($datos['fecha'])) {
			$this->validar_fecha_evaluacion($datos['fecha'], $datos_llamado['fecha']);
		}
		if (isset($datos['hora_inicio']) && isset($datos['hora_fin'])) {
			$this->validar_horas_inicio_fin_evaluacion($datos['hora_inicio'], $datos['hora_fin']);
		}
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
	 * Determinar si la fecha de la evaluación es válida.
	 * 
	 * @param date fecha_eval	fecha seteada para la Evaluación.
	 * @param date	fecha_llam	fecha definida para el Llamado en edición.
	 */
	function validar_fecha_evaluacion($fecha_eval, $fecha_llam)
	{
		if (guarani_fecha::es_menor($fecha_eval, $fecha_llam)) {
			$this->set_error('800EXA_fecha_eval_examen');
		}
	}

	/**
	 * Determina que las horas inicio y fin de la evalución sean válidas.
	 *
	 * @param hr_inicio 	integer		hora de inicio de la evaluacion.
	 * @param hr_fin		integer		hora de fin de la evalucion.
	 */
	function validar_horas_inicio_fin_evaluacion($hr_inicio, $hr_fin)
	{
		// Se valida que la hora de inicio no sea mayor que la hora de finalización.
		if ($hr_inicio >= $hr_fin) {
			$this->set_error('800_error_rango_horas');
		}
	}

	function desvincular_curso_virtual()
	{
		$datos_llamado = $this->get_datos_llamado();
		$llamado_mesa = $datos_llamado['llamado_mesa'];
		return guarani::act('act_comisiones')->desvincular_curso_virtual_by_comision($comision);
	}
}
?>