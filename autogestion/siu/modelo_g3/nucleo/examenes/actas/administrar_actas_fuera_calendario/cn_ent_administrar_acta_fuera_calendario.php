<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Actas
 * 
 */
class cn_ent_administrar_acta_fuera_calendario extends cn_entidad
{
	protected $nro_acta_ficticio = acta::nro_acta_ficticio;

	/**
	 * Redefinición del método de carga de una entidad. Se utiliza para poder requerir que se utilice la evaluación
	 * definida para el llamado mesa asociado al acta seleccionada, que sea de tipo automática (existe sólo una que
	 * se inserta por trigger al haber generado el llamado mesa).
	 */
	function cargar($id)
	{
		$id_acta = current($id);

		// Se preparan los wheres para las tablas del datos relación
		$where_acta = 'sga_actas.id_acta = ' . $id_acta;
		$where_evaluacion = "sga_evaluaciones.evaluacion_tipo IN (
																	SELECT sga_evaluaciones_tipos.evaluacion_tipo 
																	FROM sga_evaluaciones_tipos 
																	WHERE sga_evaluaciones_tipos.automatica = '" . comunes::si . "')";

		// Se asocia cada where con su respectiva tabla
		$wheres['acta'] = $where_acta;
		$wheres['evaluacion'] = $where_evaluacion;

		// Se carga el datos relación a partir de los wheres
		$this->dep('datos')->persistidor()->cargar_con_wheres($wheres);
		
		// Carga de etiquetas
		$this->cargar_etiquetas();
	}

	function deshacer_registro_insercion_filas($tabla)
	{
		$this->tabla($tabla)->forzar_insercion(false, array(0));
	}

	//--------------------------------------------------------------------------
	//---- Acceso a la estructura de persistencia ------------------------------
	//--------------------------------------------------------------------------

	function get_datos_acta()
	{
		return $this->tabla('acta')->get();
	}

	function set_datos_acta($datos)
	{
		if ($this->es_nueva()) {
			// Datos por defecto para la creación de un acta.
			$datos['renglones_folio'] = guarani::parametros()->get_valor('exa_lineas_folio_acta_examen');
			$datos['digital'] = guarani::parametros()->get_valor('exa_utiliza_acta_digital');
			$datos['origen'] = acta::origen_examen;
			// En caso de que el nro de acta no esté seteado, es decir sea de generación automática ya que en otro caso
			// es obligatorio, se da de alta el acta con un nro ficticio. El nro generado automáticamente necesita como 
			// parámetro el llamado mesa, y por lo tanto se podrá obtener y setear luego de haber Guardado.
			$nro_acta = $this->tabla('acta')->get_columna('nro_acta');
			if (!isset($datos['nro_acta']) && !isset($nro_acta)) {
				$datos['nro_acta'] = $this->nro_acta_ficticio;
			}
		}
		$this->tabla('acta')->set($datos);
	}

	function get_id_acta()
	{
		$datos_acta = $this->get_datos_acta();
		if (isset($datos_acta['id_acta'])) {
			return $datos_acta['id_acta'];
		}
	}

	function set_id_eval_automatica_acta()
	{
		$datos_eval_automatica = $this->get_datos_evaluacion();
		if (isset($datos_eval_automatica['evaluacion'])) {
			$this->tabla('acta')->set_columna_valor('evaluacion', $datos_eval_automatica['evaluacion']);
		}
	}

	function set_nro_acta($nro_acta)
	{
		$this->tabla('acta')->set_columna_valor('nro_acta', $nro_acta);
	}

	function sincronizar_acta()
	{
		$this->tabla('acta')->sincronizar();
		return $this->tabla('acta')->get_clave_valor(0);
	}

	function deshacer_registro_insercion_filas_acta()
	{
		$this->deshacer_registro_insercion_filas('acta');
	}

	//--------------------------------------------------------------------------

	function get_datos_llamado_mesa()
	{
		return $this->tabla('llamado_mesa')->get();
	}

	function set_datos_llamado_mesa($datos)
	{
		$this->tabla('llamado_mesa')->set($datos);
	}

	function get_llamado_mesa()
	{
		return $this->tabla('llamado_mesa')->get_columna('llamado_mesa');
	}

	function get_fecha_llamado_mesa()
	{
		return $this->tabla('llamado_mesa')->get_columna('fecha');
	}

	function sincronizar_llamado_mesa()
	{
		$this->tabla('llamado_mesa')->sincronizar();
		return $this->tabla('llamado_mesa')->get_clave_valor(0);
	}

	function deshacer_registro_insercion_filas_llamado_mesa()
	{
		$this->deshacer_registro_insercion_filas('llamado_mesa');
	}

	//--------------------------------------------------------------------------

	function get_datos_mesa()
	{
		return $this->tabla('mesa')->get();
	}

	function set_datos_mesa($datos)
	{
		if ($this->es_nueva()) {
			$datos['mesa_en_turno_examen'] = comunes::no;
		}
		$this->tabla('mesa')->set($datos);
	}

	function get_mesa_examen()
	{
		$datos_mesa = $this->get_datos_mesa();
		if (isset($datos_mesa['mesa_examen'])) {
			return $datos_mesa['mesa_examen'];
		}
	}

	function sincronizar_mesa()
	{
		$this->tabla('mesa')->sincronizar();
		return $this->tabla('mesa')->get_clave_valor(0);
	}

	function deshacer_registro_insercion_filas_mesa()
	{
		$this->deshacer_registro_insercion_filas('mesa');
	}

	//--------------------------------------------------------------------------

	function get_datos_mesa_propuestas()
	{
		return $this->tabla('mesa_propuestas')->get_filas();
	}

	function set_datos_mesa_propuestas($datos)
	{
		$this->tabla('mesa_propuestas')->nueva_fila($datos);
	}

	/**
	 * Verifica si existen Alumnos agregados al Acta (eval_detalles) para la Propuesta-Plan del Acta dado, ya que
	 * en ese caso no se permitirá desasociar la Propuesta-Plan de la Mesa asociada al Acta.
	 * Se recibe el arreglo $alumnos para el caso del Alta ya que el mismo se mantiene en memoria y no en el DT.
	 */
	function hay_alumnos_propuesta($propuesta, $plan, $alumnos = null)
	{
		if (empty($alumnos)) {
			$alumnos = $this->get_detalles();
		}
		foreach ($alumnos as $alumno) {
			$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($alumno['alumno']);
			if ($datos_alumno['propuesta'] == $propuesta && $datos_alumno['plan'] == $plan) {
				return true;
			}
		}
		return false;
	}

	//--------------------------------------------------------------------------

	function get_datos_acta_instancias()
	{
		return $this->tabla('acta_instancias')->get_filas();
	}

	function set_datos_acta_instancias($datos)
	{
		$this->validar_datos_instancias($datos);
		$this->tabla('acta_instancias')->procesar_filas($datos);
	}

	/**
	 * Verifica si existen Alumnos agregados al Acta (eval_detalles) para la Instancia del Acta dada, ya que
	 * en ese caso no se permitirá desasociar la Instancia del Acta ni modificar su Escala de Notas.
	 *
	 */
	function hay_alumnos_instancia($instancia, $alumnos = null)
	{
		if (empty($alumnos)) {
			$alumnos = $this->get_detalles();
		}
		foreach ($alumnos as $alumno) {
			if ($alumno['instancia'] == $instancia) {
				return true;
			}
		}
		return false;
	}

	//--------------------------------------------------------------------------

	function get_datos_mesa_instancias()
	{
		return $this->tabla('mesa_instancias')->get_filas();
	}

	function set_datos_mesa_instancias($datos)
	{
		$this->validar_datos_instancias($datos);
		foreach ($datos as $fila) {
			$accion = $fila[apex_ei_analisis_fila];
			$id = current($this->tabla('mesa_instancias')->get_id_fila_condicion(array('instancia' => $fila['instancia']), false));
			switch ($accion) {
				case 'B':
					$this->tabla('mesa_instancias')->eliminar_fila($id);
					break;
				case 'M':
					$this->tabla('mesa_instancias')->modificar_fila($id, $fila);
					break;
				case 'A':
					$this->tabla('mesa_instancias')->nueva_fila($fila);
			}
		}
	}

	//--------------------------------------------------------------------------

	function get_datos_evaluacion()
	{
		return $this->tabla('evaluacion')->get();
	}

	function set_datos_evaluacion($datos)
	{
		$this->tabla('evaluacion')->set($datos);
	}

	function get_evaluacion()
	{
		$datos_evaluacion = $this->get_datos_evaluacion();
		if (isset($datos_evaluacion['evaluacion'])) {
			return $datos_evaluacion['evaluacion'];
		}
	}

	//--------------------------------------------------------------------------

	function get_datos_docentes_llamado()
	{
		return $this->tabla('llamado_docentes')->get_filas();
	}

	function set_datos_docentes_llamado($datos)
	{
		$this->validar_datos_docentes($datos);
		$this->tabla('llamado_docentes')->procesar_filas($datos);
	}

	function eliminar_docente_llamado($id_fila_docente)
	{
		if (!is_null($id_fila_docente)) {
			$this->tabla('llamado_docentes')->eliminar_fila($id_fila_docente);
		}
	}

	/**
	 * Se crea un nuevo docente asociado al llamado a partir del array recibido con el id del mismo.
	 */
	function agregar_docente_llamado($datos_docente)
	{
		$this->tabla('llamado_docentes')->nueva_fila($datos_docente);
	}

	/**
	 * Se aplica el Tribunal dado al Llamado. Esto implica que se eliminan todos los docentes actualmente 
	 * asociados al mismo y agregarse al llamado todos los docentes activos del Tribunal dado con sus respectivos roles.
	 */
	function asociar_docentes_tribunal_llamado($id_tribunal)
	{
		$doc = $this->get_datos_docentes_llamado();
		$this->tabla('llamado_docentes')->eliminar_filas();
		$docentes_activos_tribunal = toba::consulta_php('co_tribunales')->get_docentes_activos_tribunal($id_tribunal);
		foreach ($docentes_activos_tribunal as $datos_docente) {
			$this->agregar_docente_llamado($datos_docente);
		}
		$doc = $this->get_datos_docentes_llamado();
	}

	//--------------------------------------------------------------------------
	//---- DT eval_detalle_examenes --------------------------------------------
	//--------------------------------------------------------------------------

	function get_detalles($condiciones = null)
	{
		$datos = $this->tabla('eval_detalles')->get_filas($condiciones, false, false);
		return rs_ordenar_por_columna($datos, 'apellido_y_nombre');
	}

	function eliminar_detalle($datos)
	{
		$id_fila = $this->tabla('eval_detalles')->get_id_fila_condicion($datos);
		$this->tabla('eval_detalles')->eliminar_fila(current($id_fila));
	}

	function agregar_detalle($datos)
	{
		$this->tabla('eval_detalles')->nueva_fila($datos);
	}

	//--------------------------------------------------------------------------
	//---- Auxiliares ----------------------------------------------------------
	//--------------------------------------------------------------------------

	/**
	 * Agrega alumnos que estan disponibles, a la seleccion, para ser asociados al acta a generar.
	 *
	 * @param array $alumno
	 * @param integer $instancia
	 */
	function agregar_alumno($datos_alumno, $instancia)
	{
		// Validaciones para agregar el alumno al acta.
		$this->validar_alumno($datos_alumno, $instancia);
		$this->insertar_detalle_de_eval_automatica($datos_alumno, $instancia);
	}

	/**
	 * Inserta los detalles corresponientes a las evaluaciones automaticas
	 *
	 * @param <type> $alumno
	 * @param <type> $instancia
	 */
	function insertar_detalle_de_eval_automatica($alumno, $instancia)
	{

		// Se completa el acta con el valor ficticio.
		$detalle = $alumno;
		$detalle['id_acta'] = $this->get_id_acta();
		$detalle['inscripto'] = comunes::no;
		$detalle['instancia'] = $instancia;
		$id_fila_acta_instancia = $this->tabla('acta_instancias')->get_id_fila_condicion(array('instancia' => $instancia), false);
		$detalle['escala_nota'] = $this->tabla('acta_instancias')->get_fila_columna(current($id_fila_acta_instancia), 'escala_nota');
		$detalle['evaluacion'] = $this->get_evaluacion();
		$detalle['fecha'] = $this->get_fecha_llamado_mesa();
		$this->agregar_detalle($detalle);
	}

	//--------------------------------------------------------------------------
	//---- Validaciones --------------------------------------------------------
	//--------------------------------------------------------------------------

	function validar_datos_docentes($datos)
	{
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
	 * Validar que todas las instancias asociadas al Acta tengan escala de notas asignada.
	 */
	function validar_datos_instancias($datos)
	{
		$this->validar_escala_notas_instancias($datos);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function validar_escala_notas_instancias($datos)
	{
		foreach ($datos as $instancia) {
			if ($instancia[apex_ei_analisis_fila] != 'B' && !isset($instancia['escala_nota'])) {
				$this->set_error('800CUR_error_escala_faltante', array($instancia['instancia_nombre']));
				break;
			}
		}
	}

	function hay_seleccion_automatica_de_instancia($propuesta)
	{
		$sel_auto = guarani::parametros()->get_valor('exa_instancia_automatica', $propuesta);
		return ($sel_auto == comunes::si);
	}

	function determinar_instancia($datos_alumno)
	{
		$datos_mesa = $this->get_datos_mesa();
		$instancias_mesa = $this->get_datos_mesa_instancias();
		foreach ($instancias_mesa as $instancia) {
			try {
				$retorno = $this->validar_instancia($datos_alumno, $datos_mesa['elemento'], $instancia['instancia'], false);
			} catch (guarani_error $e) {
				$retorno = guarani_punto_de_control::rechazado;
			}
			if ($retorno != guarani_punto_de_control::rechazado) {
				return $instancia['instancia'];
			}
		}
		throw new guarani_error(null, null, guarani_mensajes::get('800_error_alumno_control_acta'));
	}

	function validar_alumno($datos_alumno, $instancia)
	{
		$this->validar_alumno_ptosctrl($datos_alumno);
		$datos_mesa = $this->get_datos_mesa();
		$this->validar_actividad($datos_alumno, $datos_mesa['elemento']);
		$this->validar_mesa($datos_alumno, $datos_mesa['elemento'], $datos_mesa['anio_academico']);
		$this->validar_instancia($datos_alumno, $datos_mesa['elemento'], $instancia, true);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	protected function validar_alumno_ptosctrl($datos_alumno)
	{
		$ptos_control_a_validar = array(guarani_punto_de_control::alumno_entidad,
										guarani_punto_de_control::persona_operacion,
										guarani_punto_de_control::alumno_operacion,
										guarani_punto_de_control::persona_propuesta);
		$params = array('alumno' => $datos_alumno['alumno'],
			'persona' => $datos_alumno['persona'],
			'plan_version' => $datos_alumno['plan_version'],
			'fecha' => guarani_fecha::get_hoy(),
			'propuesta' => $datos_alumno['propuesta']);
		$retorno = $this->validar_puntos_de_control($ptos_control_a_validar, $params);

		if ($retorno == guarani_punto_de_control::rechazado) {
			$this->set_error('800_error_control_acta');
		}
	}

	protected function validar_actividad($datos_alumno, $actividad)
	{
		$pto_control_a_validar = guarani_punto_de_control::elegir_actividad_examen;
		$params = array('alumno' => $datos_alumno['alumno'],
			'elemento' => $actividad,
			'fecha' => guarani_fecha::get_hoy(),
			'plan_version' => $datos_alumno['plan_version']);
		$retorno = $this->validar_puntos_de_control($pto_control_a_validar, $params);

		if ($retorno == guarani_punto_de_control::rechazado) {
			$this->set_error('800_error_control_acta');
		}
	}

	protected function validar_mesa($datos_alumno, $actividad, $anio_acad)
	{
		$pto_control_a_validar = array(guarani_punto_de_control::alumno_mesa_examen);
		$params = array('alumno' => $datos_alumno['alumno'],
			'elemento' => $actividad,
			'fecha' => guarani_fecha::get_hoy(),
			'llamado_mesa' => $this->get_llamado_mesa(),
			'anio_academico' => $anio_acad,
			'plan_version' => $datos_alumno['plan_version']);
		$retorno = $this->validar_puntos_de_control($pto_control_a_validar, $params);

		if ($retorno == guarani_punto_de_control::rechazado) {
			$this->set_error('800_error_control_acta');
		}
	}

	protected function validar_instancia($datos_alumno, $actividad, $instancia, $notificar = true)
	{
		$params = array('alumno' => $datos_alumno['alumno'],
			'elemento' => $actividad,
			'fecha' => guarani_fecha::get_hoy(),
			'llamado_mesa' => $this->get_llamado_mesa(),
			'plan_version' => $datos_alumno['plan_version']);
		$ptos_control = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia);
		$retorno = $this->validar_puntos_de_control($ptos_control, $params);

		if ($retorno == guarani_punto_de_control::rechazado && $notificar) {
			$this->set_error('800_error_control_acta');
			throw new guarani_error($this->get_mensaje_error());
		}

		return $retorno;
	}

	//--------------------------------------------------------------------------

	function hay_cambios_relacion()
	{
		return $this->dep('datos')->hay_cambios();
	}

	//--------------------------------------------------------------------------

	protected function validar_puntos_de_control($pto_control_a_validar, $params)
	{
		$retorno = guarani::validador_puntos_de_control($pto_control_a_validar, $params)->controlar();
		guarani::validador_puntos_de_control()->notificar();
		return $retorno;
	}

	//--------------------------------------------------------------------------

	function pre_sincronizar()
	{
		$datos_docentes = $this->get_datos_docentes_llamado();
		$rol_duplicado = false;
		foreach ($datos_docentes as $docente) {
			$rol = $docente['rol'];
			$id_fila = $docente['x_dbr_clave'];
			foreach ($datos_docentes as $colega) {
				if ($colega['x_dbr_clave'] <> $id_fila && $colega['rol'] == $rol) {
					$rol_duplicado = true;
					break;
				}
			}
		}
		if ($rol_duplicado) {
			$this->set_error('800EXA_roles_docentes_acta_sin_mesa');
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function hay_cambios()
	{
		if ($this->tabla('mesa')->existe_cambio_en_campo('nombre') ||
				$this->tabla('mesa')->existe_cambio_en_campo('anio_academico') ||
				$this->tabla('mesa')->existe_cambio_en_campo('ubicacion') ||
				$this->tabla('llamado_mesa')->existe_cambio_en_campo('fecha') ||
				$this->tabla('acta')->existe_cambio_en_campo('nro_acta') ||
				$this->tabla('acta')->existe_cambio_en_campo('observaciones') ||
				$this->tabla('llamado_docentes')->hay_cambios() ||
				$this->tabla('acta_instancias')->hay_cambios() ||
				$this->tabla('eval_detalles')->hay_cambios()) {
			return true;
		}
		return false;
	}

}
?>