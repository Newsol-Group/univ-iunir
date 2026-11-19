<?php
class cn_ent_llamado_de_turno_de_examen extends cn_entidad
{

	function cargar($id)
	{
		$this->dep('datos')->persistidor()->cargar_con_wheres(array(
					"periodo" => "periodo.periodo = (SELECT sga_llamados_turno.periodo FROM sga_llamados_turno WHERE sga_llamados_turno.llamado = ".$id['llamado'].")"
				));
		$this->cargar_etiquetas();
		$this->verificar_editabilidad();
	}

	function verificar_editabilidad()
	{
		$fecha_fin = $this->tabla('periodo')->get_columna('fecha_fin');
		//Si ya finalizó entonces no se puede editar ni agregar llamados
		if (guarani_fecha::es_menor($fecha_fin, guarani_fecha::get_hoy())) {
			$this->bloquear_edicion();
		} else {
			$this->habilitar_edicion();
		}
	}

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_editabilidad();
		if ($validar) $this->validar_datos($datos);
		$datos['periodo_generico'] = periodo_generico::llamado;
		$this->tabla('periodo')->set($datos);
		$this->tabla('llamado')->set($datos);
	}

	function get_datos($tabla=null)
	{
		$datos_periodo = $this->tabla('periodo')->get();
		$datos_llamado = $this->tabla('llamado')->get();
		if (!$datos_periodo) $datos_periodo = array();
		if (!$datos_llamado) $datos_llamado = array();
		return array_merge($datos_llamado,$datos_periodo);
	}

	function get_turno_examen_id()
	{
		return $this->tabla('llamado')->get_columna('turno_examen');
	}

	function get_llamado_id()
	{
		return $this->tabla('llamado')->get_columna('llamado');
	}

	function get_datos_turno_examen()
	{
		return $this->co()->get_datos_turno($this->get_turno_examen_id());
	}

	function get_llamados_asociados()
	{		
		$llamados = aplanar_matriz($this->tabla('relacion')->get_filas(), 'llamado');
		$condicion = guarani_sql::armar_condicion_in($llamados, 'vw_turnos_examen', 'llamado', 'llamado');
		//Armo la condicion in para que me traiga los datos para visualizar en el cuadro de seleccionados		
		return $this->co()->get_datos_turnos_llamados($condicion);
	}
	
	function get_llamados_disponibles($filtro)
	{
		$llamados = aplanar_matriz($this->tabla('relacion')->get_filas(), 'llamado');
		/*
		 * Traigo los llamados que cumplen la condición y que además no son los asociados
		 */
		$asociados = guarani_sql::armar_condicion_in($llamados, 'vw_turnos_examen', 'llamado', 'llamado', true);
		$datos = $this->get_datos();
		$fecha = " llamado_fecha_fin < ".quote($datos['fecha_inicio']);
		$and = '';
		if ($filtro != '') {
			$and = ' AND ';
		}
		$filtro = $filtro.$and.$asociados. ' AND '.$fecha;
		return $this->co()->get_datos_turnos_llamados($filtro);
	}
	
	function agregar_vinculados($llamados)
	{
		/*
		 * Por cada valor seleccionado del cuadro lo agrego como una nueva fila.
		 */
		foreach($llamados as $llamado) {
			$this->tabla('relacion')->nueva_fila($llamado);
		}
	}
	
	function eliminar_asociado($datos)
	{		
		$condicion = array('llamado' => $datos['llamado']);
		/*
		 * Traigo la fila que cumple con la condicion de tener el llamado_hijo
		 * y luego que se la fila la elimino
		 */
		$fila = $this->tabla('relacion')->get_id_fila_condicion($condicion, true);		
		$this->tabla('relacion')->eliminar_fila($fila[0]);
		$this->dep('datos')->sincronizar();
	}
	
	function validar_datos($datos)
	{
		$this->set_datos_obligatorios(array('nombre' => 'Nombre',
											'fecha_inicio' => 'Fecha de inicio',
											'fecha_fin' => 'Fecha de fin'));
		//Verificar datos obligatorios faltantes
		$this->validar_datos_obligatorios($datos);

		if (!isset($datos['anio_academico'])) $datos['anio_academico'] = $this->tabla('periodo')->get_columna('anio_academico');

		if (guarani_fecha::es_menor($datos['fecha_fin'], guarani_fecha::get_hoy())) {
			$this->set_error('800_control_fechas', array('Finalización', 'anterior', 'de hoy'));
		}

		//Que el inicio sea anterior al comienzo
		if (guarani_fecha::es_mayor($datos['fecha_inicio'], $datos['fecha_fin'])) {
			$this->set_error('800_control_fechas',array('Inicio','posterior','Finalización'));
		}

		//Que esté dentro del período del Turno
		$datos_turno = $this->get_datos_turno_examen();

		$llamado = $this->tabla('llamado')->get_columna('llamado');
		$this->validar_nombre_llamado($datos['nombre'], $datos_turno['turno_examen'], $llamado);
		
		if (guarani_fecha::es_menor($datos['fecha_inicio'], $datos_turno['fecha_inicio'])) {
			$this->set_error('800_control_fechas',array('Inicio del Llamado','anterior','Inicio del Turno'));
		}

		if (guarani_fecha::es_mayor($datos['fecha_inicio'], $datos_turno['fecha_fin'])) {
			$this->set_error('800_control_fechas',array('Inicio del Llamado','posterior','Finalización del Turno'));
		}

		if (guarani_fecha::es_menor($datos['fecha_fin'], $datos_turno['fecha_inicio'])) {
			$this->set_error('800_control_fechas',array('Finalización del Llamado','anterior','Inicio del Turno'));
		}

		if (guarani_fecha::es_mayor($datos['fecha_fin'], $datos_turno['fecha_fin'])) {
			$this->set_error('800_control_fechas',array('Finalización del Llamado','posterior','Finalización del Turno'));
		}

		//El rango de fechas de los llamados no se pueden superponer.
		if ($this->co()->hay_superposicion_llamados($this->get_turno_examen_id(),$this->get_llamado_id(),$datos['fecha_inicio'],$datos['fecha_fin'])) {
			$this->set_error('Hay superposición de Llamados');
		}

		if ($this->get_llamado_id()) {
			//La fecha tope de bajas debe ser mayor o igual a la de fin de inscripcion.
		//	if ($this->co()->validar_fecha_tope_llamado($this->get_llamado_id(),$datos['fecha_fin'])) {
		//		$this->set_error('Hay superposición de Llamados');
		//	}
		}

		//Verificar Mesas fuera de rango
		$llamado_id = $this->get_llamado_id();
		if ($llamado_id) {
			$this->validar_mesas_en_rango($llamado_id,$datos['fecha_inicio'],$datos['fecha_fin']);
		}
				
		// Que los períodos de inscripción creados previamente estén contenidos en el rango
		$periodo = $this->tabla('llamado')->get_columna('periodo');
		if ($periodo) {
			$this->validar_periodos_en_rango($periodo, $datos['fecha_inicio'], $datos['fecha_fin']);
		}
		
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_nombre_llamado($nombre, $turno_examen, $llamado)
	{
		if (!toba::consulta_php('co_periodos_de_inscripcion')->validar_nombre_llamado($nombre, $turno_examen, $llamado)) {
			$this->set_error('dato_duplicado', array('nombre', 'Llamado'));
		}
	}

	function validar_mesas_en_rango($llamado,$fecha_inicio,$fecha_fin)
	{
		if (!$this->co()->validar_mesas_en_rango_llamado($llamado,$fecha_inicio,$fecha_fin)) {
			$this->set_notificacion('800EXA_Mesas_Fuera_Rango');
		}
	}
	
	function validar_periodos_en_rango($periodo, $fecha_inicio, $fecha_fin)
	{
		if (!guarani::co('co_periodos_de_inscripcion')->validar_periodos_en_rango($periodo, $fecha_inicio, $fecha_fin)) {
			$this->set_error('Existen períodos de inscripción previamente creados fuera del rango de fechas definido para el llamado.');
		}
	}
	
	function validar_llamados_vinculados()
	{
		$datos = $this->get_datos();
		$asociados = $this->get_llamados_asociados();
		foreach ($asociados as $asociado) {
			if (guarani_fecha::es_mayor($asociado['llamado_fecha_fin'], $datos['fecha_inicio'])) {
				$this->set_error('La fecha de inicio del Llamado no puede ser menor a la fecha de fin del llamado '.$asociado['llamado_nombre']);		
			}
		}
		
		/*
		 * La fecha de fin es menor a la de su vinculado, esta validacion se hace siempre y cuando
		 * ya se tenga seteado el campo llamado.
		 */
		if (isset($datos['llamado'])) {
			$filtro = "llamado IN (SELECT llamado_vinculado FROM sga_llamados_turno_relacion WHERE llamado = {$datos['llamado']})";
			$vinculados = $this->co()->get_datos_turnos_llamados($filtro);		
			foreach ($vinculados as $vinculado) {
				if (guarani_fecha::es_menor($vinculado['llamado_fecha_inicio'], $datos['fecha_fin'])) {
					$this->set_error('La fecha de fin del Llamado no puede ser mayor a la fecha de fin del llamado '.$vinculado['llamado_nombre']);
				}
			}
		}
		
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
		
	}
}

?>