<?php
class cn_man_inscripciones_examenes extends cn_manejador_entidad
{
	protected $s__fuera_de_termino = false;
	protected $s__estado = 'NUEVA';  // Estados internos posibles de la Inscripción: NUEVA | CONFIRMADA | ERROR
	protected $s__retornos_ptos_control = array();
	protected $s__nro_transaccion;
	protected $s__fecha_transaccion;
	protected $s__alumno;
	protected $s__alumno_datos;
	protected $s__actividad;
	protected $s__actividad_datos;
	protected $s__tipo_plan;
	protected $s__actividad_plan_version;
	protected $s__mesa;
	protected $s__mesa_datos;
	protected $s__llamado_mesa;
	protected $s__instancia;
	protected $s__instancia_datos;
	protected $s__mesas_disponibles;
	protected $s__instancias_disponibles;
	protected $s__autorizacion;
	protected $s__estado_comp = inscripcion_examen::estado_pendiente;
	protected $s__atrib_propuestas; // Propuestas utilizadas como atributos para resolver parámetros
	// Parámetros
	protected $s__param_exa_ejec = true;

	function cargar_atributos_parametros($propuesta, $plan)
	{
		$operacion = guarani::get_id_operacion();

		$this->s__atrib_propuestas = array($propuesta);
		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($this->s__atrib_propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $this->s__atrib_propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_plan, array($plan));
	}

	function resetear_atributos_parametros($nivel = null)
	{
		unset($this->s__atrib_propuestas);
		parent::resetear_atributos_parametros($nivel);
	}

	//--------------------------------------------------------------------------
	//---- SETTERS 
	//--------------------------------------------------------------------------

	/*
	 * Setea la inscripción como fuera de término
	 */
	function set_fuera_de_termino($estado = true)
	{
		$this->s__fuera_de_termino = $estado;
	}

	/*
	 * Setea el alumno que se inscribirá a cursar.
	 */
	function set_alumno($alumno, $datos = null)
	{
		$this->s__alumno = $alumno;
		if (!$datos) {
			$datos = guarani::co('co_alumnos')->get_datos_basicos_alumno($this->s__alumno);
		}
		$this->s__alumno_datos = $datos;
		$this->validar_alumno($datos);
	}

	/*
	 * Setea la actividad a la cual se inscribirá a cursar.
	 */
	function set_actividad($actividad, $datos = null)
	{
		$this->validar_actividad($actividad);
		$this->s__mesas_disponibles = null;
		$mesas_disponibles = $this->get_mesas_disponibles($actividad);
		if ($this->get_cant_mesas_disponibles() == 0) {
			throw guarani::error('No hay mesas disponibles para el alumno y actividad seleccionados.');
		}
		$this->s__actividad = $actividad['id'];
		$this->s__tipo_plan =$actividad['tipo_plan'];
		$this->s__actividad_plan_version =$actividad['plan_version'];
		if (!$datos) {
			$datos = guarani::co('co_elementos')->get_info_elemento_plan($actividad['plan_version'], $this->s__actividad);
		}
		$this->s__actividad_datos = $datos;
	}

	/*
	 * Setea las mesas disponibles para inscribirse para el alumno y actividad elegidas
	 */
	protected function set_mesas_disponibles($mesas)
	{
		$this->s__mesas_disponibles = $mesas;
	}

	/*
	 * Setea la mesa elegida para inscribirse para el alumno y actividad elegidas
	 */
	function set_mesa($mesa, $llamado_mesa, $datos = null)
	{
		if (!$datos) {
			$m = toba::db()->quote($mesa);
			$llamado = toba::db()->quote($llamado_mesa);
			$datos = guarani::co('co_mesas_de_examen')->get_listado("sga_mesas_examen.mesa_examen = $m AND sga_llamados_mesa.llamado_mesa = $llamado");
			$datos = $datos[0];
		}
		$this->validar_mesa($mesa, $llamado_mesa, $datos['anio_academico']);
		$this->s__mesa = $mesa;
		$this->s__llamado_mesa = $llamado_mesa;

		if ($this->hay_seleccion_automatica_de_instancia()) {
			if (!inscripcion_examen::ejecutar_controles($this->s__atrib_propuestas)) {
				$this->s__param_exa_ejec = false;
			}
			$this->determinar_instancia();
		}
		$this->s__mesa_datos = $datos;
	}

	/*
	 * Determina automáticamente la instancia a inscribirse
	 */
	protected function determinar_instancia()
	{
		$mensajes = '';
		foreach ($this->get_instancias_mesa() as $key => $instancia) {
			try {
				$retorno = $this->validar_instancia($instancia['instancia'], false);
			} catch (guarani_error $e) {
				$retorno = guarani_punto_de_control::rechazado;
				$mensajes .= '\n' . $e->get_mensaje();
			}
			if ($retorno != guarani_punto_de_control::rechazado) {
				$this->s__instancia = $instancia['instancia'];
				$this->set_instancia_datos($instancia);
				return;
			}
		}
		throw new guarani_error(null, null, guarani_mensajes::get('800_inscripcion_fallo_ctrl'));
	}

	/*
	 * Setea la instancia de la mesa elegida para inscribirse 
	 */
	function set_instancia($instancia, $validar = true)
	{
		$this->validar_instancia($instancia);
		$this->s__instancia = $instancia;
	}

	function set_instancia_datos($instancia_datos = null)
	{
		if (!$instancia_datos) {
			foreach ($this->get_instancias_mesa() as $key => $instancia) {
				if ($instancia['instancia'] == $this->get_instancia()) {
					$instancia_datos = $instancia;
					break;
				}
			}
		}
		$this->s__instancia_datos = $instancia_datos;
	}

	/*
	 * Setea el texto de la autorización para las isncripciones fuera de término.
	 */
	function set_autorizacion($autorizacion)
	{
		$this->s__autorizacion = $autorizacion;
	}

	function deseleccionar_alumno()
	{
		$this->s__alumno = null;
		$this->s__alumno_datos = null;
		$this->deseleccionar_actividad();
		unset($this->s__retornos_ptos_control['alumno']);
	}

	function deseleccionar_actividad()
	{
		$this->s__actividad = null;
		$this->s__actividad_datos = null;
		$this->s__mesas_disponibles = null;
		$this->deseleccionar_mesa();
		unset($this->s__retornos_ptos_control['actividad']);
	}

	function deseleccionar_mesa()
	{
		$this->s__mesa = null;
		$this->s__mesa_datos = null;
		$this->s__llamado_mesa = null;
		$this->s__instancias_disponibles = null;
		$this->s__instancia = null;
		$this->s__instancia_datos = null;
		$this->s__autorizacion = null;
		$this->deseleccionar_final();
		unset($this->s__retornos_ptos_control['mesa']);
	}

	function deseleccionar_final()
	{
		unset($this->s__retornos_ptos_control['instancia']);
	}

	function deseleccionar_todo()
	{
		$this->deseleccionar_alumno();
	}

	//--------------------------------------------------------------------------
	//---- GETTERS 
	//--------------------------------------------------------------------------

	function entidad($id = null)
	{
		return $this;
	}

	function etiquetas($concepto = null)
	{
		if (!isset($this->etiquetas[$concepto])) {
			$this->etiquetas[$concepto] = guarani_etiquetas::get_concepto($concepto);
		}
		return $this->etiquetas[$concepto];
	}

	function es_fuera_de_termino()
	{
		return ($this->get_fuera_de_termino() == comunes::si);
	}

	function get_indice_instancia()
	{
		return 'inscripcion_examen';
	}

	function get_clase_consulta()
	{
		return 'co_inscripciones_examenes';
	}

	function get_estado()
	{
		return $this->s__estado;
	}

	function get_alumno()
	{
		return $this->s__alumno;
	}

	function get_persona()
	{
		return $this->s__alumno_datos['persona'];
	}

	function get_alumno_datos($dato = null)
	{
		if ($dato) {
			return $this->s__alumno_datos[$dato];
		} else {
			return $this->s__alumno_datos;
		}
	}

	function get_actividad()
	{
		return $this->s__actividad;
	}

	function get_actividad_datos($dato = null)
	{
		if ($dato) {
			return $this->s__actividad_datos[$dato];
		} else {
			return $this->s__actividad_datos;
		}
	}

	function get_fuera_de_termino()
	{
		if ($this->s__fuera_de_termino) {
			return comunes::si;
		} else {
			return comunes::no;
		}
	}

	function get_mesa()
	{
		return $this->s__mesa;
	}

	function get_mesa_datos($dato = null)
	{
		if ($dato) {
			return $this->s__mesa_datos[$dato];
		} else {
			return $this->s__mesa_datos;
		}
	}

	function get_llamado_mesa()
	{
		return $this->s__llamado_mesa;
	}

	function get_instancia()
	{
		return $this->s__instancia;
	}

	function get_instancia_nombre()
	{
		return $this->s__instancia_datos['instancia_nombre'];
	}

	function get_fecha_de_inscripcion()
	{
		return $this->s__fecha_transaccion;
	}

	/*
	 * Devuelve los alumnos activos con inscripcion a propuesta en estado aceptada o pendiente
	 */
	function get_alumnos_activos($filtro = null)
	{
		$where = " sga_planes_versiones.estado IN ('" . planes::estado_vigente . "','" . planes::estado_activo . "')
					";
		if ($filtro) {
			$where .= " AND $filtro";
		}
		return guarani::co('co_alumnos')->get_alumnos_activos_con_insc_aceptada_pendiente($where);
	}

	/*
	 * Retorna el legajo del alumno pasado como parámetro.
	 */
	function get_legajo_alumno($alumno)
	{
		$datos = guarani::co('co_alumnos')->get_datos_basicos_alumno($alumno);
		return $datos['legajo'];
	}

	/*
	 * Devuelve las actividades disponibles para que se inscriba el alumno.
	 */
	function get_actividades_disponibles($filtro = '')
	{
		$alumno = $this->get_alumno();
		$propuesta = $this->s__alumno_datos['propuesta'];
		$plan = $this->s__alumno_datos['plan'];
		$plan_version = $this->s__alumno_datos['plan_version'];
		$ubicacion = $this->s__alumno_datos['ubicacion'];
		$fuera_de_termino = $this->s__fuera_de_termino;

		return $this->co()->get_actividades_disponibles_inscripcion(guarani::parametros()->get_valor('exa_filtro_actividades_con_mesas'), guarani::parametros()->get_valor('exa_rinde_en_cualquier_ubicacion'), guarani::parametros()->get_valor('exa_rendir_regularizada_plan_anterior'), guarani::parametros()->get_valor('exa_multiples_actas_examen'), $propuesta, $plan, $alumno, $plan_version, $ubicacion, $fuera_de_termino, $filtro);
	}

	/*
	 * Devuelve las mesas disponibles para inscribirse para el alumno y actividad elegidas
	 */
	function get_mesas_disponibles($actividad = null)
	{
		if ($this->s__mesas_disponibles) {
			return $this->s__mesas_disponibles;
		}
		$alumno = $this->get_alumno();
		$elemento = ($actividad) ? $actividad['id'] : $this->s__actividad;
		$propuesta = $this->s__alumno_datos['propuesta'];
		$plan = $actividad['plan'];
		$plan_version = $actividad['plan_version'];
		$ubicacion = $this->s__alumno_datos['ubicacion'];
		$inicial_apellido = $this->s__alumno_datos['inicial_apellido'];
		$fuera_de_termino = $this->s__fuera_de_termino;
		$modalidad = $this->s__alumno_datos['modalidad'];
		$tipo_plan = $actividad['tipo_plan'];
		
		$mesas = guarani::co('co_mesas_de_examen')->get_mesas_disponibles_para_inscripcion(guarani::parametros()->get_valor('exa_mostrar_mesas_todas_modalidades'), guarani::parametros()->get_valor('exa_rinde_en_cualquier_ubicacion'), guarani::parametros()->get_valor('exa_multiples_actas_examen'), guarani::parametros()->get_valor('cur_division_estricta'), $alumno, $elemento, $propuesta, $plan, $plan_version, $ubicacion, $inicial_apellido, $modalidad, $fuera_de_termino, $tipo_plan);
		$this->set_mesas_disponibles($mesas);
		return $mesas;
	}

	/*
	 * Devuelve la cantidad de mesas disponibles para el alumno/actividad seteados
	 */
	function get_cant_mesas_disponibles()
	{
		return guarani_arreglos::count($this->s__mesas_disponibles);
	}

	function hay_seleccion_automatica_de_instancia()
	{
		$sel_auto = guarani::parametros()->get_valor('exa_instancia_automatica');
		return ($sel_auto == comunes::si);
	}

	function get_instancias_mesa()
	{		
        $mesa = toba::db()->quote($this->get_mesa());
        $filtro = "sga_mesas_examen.mesa_examen = $mesa";
        if ($this->s__tipo_plan == 'PLAN_ANTERIOR') {
			// Solo mesas con instancia regular e instancia reválida de cursada.
            $filtro .= " AND sga_instancias.instancia IN ( ".mesa_de_examen::inst_exam_regular.",".mesa_de_examen::inst_exam_revalida_cursada.")";
        }
        $this->s__instancias_disponibles = guarani::co('co_mesas_de_examen')->get_instancias_mesas_examen($filtro);
        $this->s__instancias_disponibles = rs_ordenar_por_columna($this->s__instancias_disponibles, 'instancia_orden');
		return $this->s__instancias_disponibles;
	}

	function get_docentes_mesa()
	{
		return guarani::co('co_mesas_de_examen')->get_docentes_mesa($this->get_llamado_mesa());
	}

	/*
	 * Devuelve el texto de la autorización para las inscripciones fuera de término.
	 */
	function get_autorizacion()
	{
		return $this->s__autorizacion;
	}

	/*
	 * Devuelve los puntos de control a validar para la instancia recibida.
	 */
	function get_puntos_de_control_instancia($instancia)
	{
		return toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia);
	}

	/*
	 * Devuleve el Nro. de Transacción correspondiente al id de la inscricpión recibida.
	 */
	function get_nuevo_nro_transaccion()
	{
		return $this->co()->get_nuevo_nro_transaccion();
	}

	function get_nro_transaccion()
	{
		return $this->s__nro_transaccion;
	}

	protected function get_params()
	{
		$params = array_merge(array('alumno' => $this->s__alumno, 'elemento' => $this->s__actividad), $this->s__alumno_datos);
		return $params;
	}

	function get_datos_comprobante()
	{
		$datos_impresion = array();
		$datos_impresion[] = array('etiqueta' => 'Alumno', 'valor' => $this->get_alumno_datos('apellido_y_nombre'));
		$datos_impresion[] = array('etiqueta' => 'Identificación', 'valor' => $this->get_alumno_datos('documento_principal'));

		$legajo = (!isset($this->s__alumno_datos['legajo'])) ? '  ' : $this->s__alumno_datos['legajo'];
		$datos_impresion[] = array('etiqueta' => 'Legajo', 'valor' => $legajo);

		$datos_impresion[] = array('etiqueta' => 'Propuesta', 'valor' => $this->get_alumno_datos('propuesta_nombre'));
		$datos_impresion[] = array('etiqueta' => 'Actividad', 'valor' => $this->get_actividad_datos('descr'));
		$datos_impresion[] = array('etiqueta' => 'Fecha de inscripción', 'valor' => $this->get_fecha_de_inscripcion());

		$datos_impresion[] = array('etiqueta' => 'Año académico', 'valor' => $this->get_mesa_datos('anio_academico'));
		$datos_impresion[] = array('etiqueta' => 'Turno', 'valor' => $this->get_mesa_datos('nombre_turno_examen'));
		$datos_impresion[] = array('etiqueta' => 'Llamado', 'valor' => $this->get_mesa_datos('nombre_llamado'));
		$datos_impresion[] = array('etiqueta' => 'Mesa de Examen', 'valor' => $this->get_mesa_datos('nombre'));
		$datos_impresion[] = array('etiqueta' => 'Fecha', 'valor' => $this->get_mesa_datos('fecha_formato_visual'));
		
		$datos_impresion[] = array('etiqueta' => 'Horario', 'valor' => $this->get_mesa_datos('horario_examen'));
		$datos_impresion[] = array('etiqueta' => 'Inscripto para Rendir', 'valor' => $this->get_instancia_nombre());
		$datos_impresion[] = array('etiqueta' => 'Cátedra', 'valor' => $this->get_mesa_datos('catedra_nombre'));
		$datos_impresion[] = array('etiqueta' => 'Ubicación', 'valor' => $this->get_mesa_datos('nombre_ubicacion'));

		if ($this->s__estado_comp == inscripcion_examen::estado_pendiente) {
			$estado = 'Pendiente';
		} else {
			$estado = 'Aceptada';
		}
		$datos_impresion[] = array('etiqueta' => 'Estado', 'valor' => $estado);

		return $datos_impresion;
	}

	//--------------------------------------------------------------------------
	//---- VALIDACIONES
	//--------------------------------------------------------------------------

	protected function validar_alumno($datos_alumno)
	{
		$ptos_control_a_validar = array(
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::persona_operacion,
			guarani_punto_de_control::alumno_operacion,
			guarani_punto_de_control::persona_propuesta
		);
		$params = array(
			'alumno' => $datos_alumno['alumno'],
			'persona' => $datos_alumno['persona'],
			'plan_version' => $datos_alumno['plan_version'],
			'fecha' => guarani_fecha::get_hoy(),
			'propuesta' => $datos_alumno['propuesta']
		);
		$retorno = $this->validar_puntos_de_control($ptos_control_a_validar, $params);

		if ($retorno == guarani_punto_de_control::rechazado) {
			$this->deseleccionar_alumno();
			throw new guarani_error(null, null, guarani_mensajes::get('800_inscripcion_fallo_ctrl'));
		}

		$this->guardar_retorno_ptos_control('alumno', $retorno);
	}

	protected function validar_actividad($actividad)
	{
		if (!$this->s__alumno) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('algun Alumno'));
			throw guarani::error($mje);
		}

		$pto_control_a_validar = guarani_punto_de_control::elegir_actividad_examen;
		$params = array(
			'alumno' => $this->s__alumno_datos['alumno'],
			'elemento' => $actividad['id'],
			'fecha' => guarani_fecha::get_hoy(),
			'plan_version' => $actividad['plan_version']
		);
		$retorno = $this->validar_puntos_de_control($pto_control_a_validar, $params);

		if ($retorno == guarani_punto_de_control::rechazado) {
			throw new guarani_error(null, null, guarani_mensajes::get('800_inscripcion_fallo_ctrl'));
		}

		$this->guardar_retorno_ptos_control('actividad', $retorno);
	}

	protected function validar_mesa($mesa, $llamado_mesa, $anio_acad)
	{
		if (!$this->s__actividad) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('alguna Actividad'));
			throw guarani::error($mje);
		}

		$pto_control_a_validar = array(guarani_punto_de_control::alumno_mesa_examen, guarani_punto_de_control::alumno_anio_academico);
		$params = array(
			'alumno' => $this->s__alumno_datos['alumno'],
			'elemento' => $this->s__actividad,
			'fecha' => guarani_fecha::get_hoy(),
			'llamado_mesa' => $llamado_mesa,
			'anio_academico' => $anio_acad,
			'plan_version' => $this->s__actividad_plan_version
		);
		$retorno = $this->validar_puntos_de_control($pto_control_a_validar, $params);

		if ($retorno == guarani_punto_de_control::rechazado) {
			throw new guarani_error(null, null, guarani_mensajes::get('800_inscripcion_fallo_ctrl'));
		}

		$this->guardar_retorno_ptos_control('mesa', $retorno);
	}

	protected function validar_instancia($instancia, $notificar = true)
	{
		$params = array(
			'alumno' => $this->s__alumno,
			'elemento' => $this->s__actividad,
			'fecha' => guarani_fecha::get_hoy(),
			'llamado_mesa' => $this->s__llamado_mesa,
			'plan_version' => $this->s__actividad_plan_version
		);
		$ptos_control = $this->get_puntos_de_control_instancia($instancia);
		$retorno = $this->validar_puntos_de_control($ptos_control, $params);

		if ($retorno == guarani_punto_de_control::rechazado && $notificar) {
			throw new guarani_error(null, null, guarani_mensajes::get('800_inscripcion_fallo_ctrl'));
		}

		$this->guardar_retorno_ptos_control('instancia', $retorno);
		return $retorno;
	}

	//--------------------------------------------------------------------------
	//---- EVENTOS
	//--------------------------------------------------------------------------

	protected function validar_puntos_de_control($pto_control_a_validar, $params)
	{
		if (!inscripcion_examen::ejecutar_controles($this->s__atrib_propuestas)) {
			$retorno = guarani_punto_de_control::advertido;
		} else {
			$retorno = guarani::validador_puntos_de_control($pto_control_a_validar, $params)->controlar();
			guarani::validador_puntos_de_control()->notificar();
		}
		return $retorno;
	}

	protected function guardar_retorno_ptos_control($momento_orden, $retorno)
	{
		$this->s__retornos_ptos_control[$momento_orden] = $retorno;
	}

	protected function get_peor_estado_retorno_ptos_control()
	{
		foreach ($this->s__retornos_ptos_control as $retorno) {
			if ($retorno == guarani_punto_de_control::advertido) {
				return guarani_punto_de_control::advertido;
			}
		}
		return guarani_punto_de_control::autorizado;
	}

	/*
	 * Impacta en la base de datos la inscripción correspondiente del alumno en la mesa de exámen/instancia
	 */
	function guardar()
	{
		if (!$this->s__param_exa_ejec) {
			$mje = guarani_mensajes::get('800exa_inscripcion_error_inst');
			throw guarani::error($mje);
		}
		if ($this->s__estado != 'NUEVA') {
			throw guarani::error('La inscripción ya fue guardada.');
		}

		try {
			$this->desactivar_transacciones_anidadas();
			toba::db('guarani')->abrir_transaccion();

			$datos_inscripcion = array();
			$datos_inscripcion['alumno'] = $this->get_alumno();
			$datos_inscripcion['plan_version'] = $this->s__actividad_plan_version;
			$datos_inscripcion['llamado_mesa'] = $this->get_llamado_mesa();
			$datos_inscripcion['instancia'] = $this->get_instancia();
			$datos_inscripcion['estado'] = inscripcion_examen::get_estado_equivalente($this->get_peor_estado_retorno_ptos_control());
			$datos_inscripcion['fuera_de_termino'] = $this->get_fuera_de_termino();
			$datos_inscripcion['autorizacion'] = $this->get_autorizacion();
			$datos_inscripcion['actividad'] = $this->get_actividad();
			$datos_inscripcion['plan'] = $this->get_alumno_datos('plan');
			
			$params_sistema = array();
			$params_sistema['exa_insc_unico_llamado'] = guarani::parametros()->get_valor('exa_insc_unico_llamado');
			$params_sistema['exa_insc_cant_max_una_actividad_por_llamado'] = guarani::parametros()->get_valor('exa_insc_cant_max_una_actividad_por_llamado');
			$params_sistema['exa_insc_cant_max_una_actividad_por_turno'] = guarani::parametros()->get_valor('exa_insc_cant_max_una_actividad_por_turno');
			$params_sistema['exa_insc_siempre_pendiente'] = guarani::parametros()->get_valor('exa_insc_siempre_pendiente');

			$resultado = guarani::act('act_inscripcion_examenes')->inscribir_a_examen($datos_inscripcion, $params_sistema);
			$this->s__estado_comp = ($resultado['estado'] ?? null);
			// Controlar el valor devuelto por la funcion para ver si salio todo ok.
			if ($resultado['res'] < 1) {
				$mje = guarani_mensajes::get($resultado['msg_indice'], $resultado['pars']);
				throw guarani::error($mje);
			}

			if (guarani::parametros()->get_valor(inscripcion_examen::exa_notificar_estado_inscripcion) == comunes::si) {
				$datos_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_llamado_mesa($this->get_llamado_mesa());
				$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_alumno($this->get_alumno());
				$parametros['estado'] = $resultado['estado'];
				$parametros['nombre_actividad'] = $datos_mesa['actividad_nombre'];
				$parametros['fecha_mesa'] = $datos_mesa['fecha_formato_visual'];
				$parametros['persona'] = $datos_alumno[0]['persona'];
				$parametros['email'] = toba::consulta_php('co_personas')->get_emails_personas(Array($datos_alumno[0]['persona']));
				$parametros['nombre_alumno'] = $datos_alumno[0]['nombres'];
				guarani::notificador()->enviar(notificador::inscribir_a_examenes, $parametros);
			}

			toba::db('guarani')->cerrar_transaccion();
			$this->activar_transacciones_anidadas();

			$nro_transaccion = toba::db()->recuperar_secuencia('aud_nro_transaccion_seq');
			$this->s__nro_transaccion = $nro_transaccion;
			$datos_inscripcion = $this->co()->get_datos_inscripcion($nro_transaccion);
			$this->s__fecha_transaccion = $datos_inscripcion['fecha_inscripcion_visual'];
			$this->s__estado = 'CONFIRMADA';

			$mje = guarani_mensajes::get('800_inscripcion_ok', array($datos_inscripcion['estado_nombre'], $this->s__nro_transaccion));
			toba::notificacion()->agregar($mje, comunes::error_informacion);
		} catch (guarani_error $e) {
			$this->s__estado = 'ERROR';
			toba::db('guarani')->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			toba::notificacion()->agregar($e->get_mensaje(), comunes::error_critico);
			$mje = guarani_mensajes::get('800_alta_inscripcion_error');
			throw guarani::error($mje);
		} catch (toba_error $e) {
			$this->s__estado = 'ERROR';
			toba::db('guarani')->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			toba::notificacion()->agregar($e->get_mensaje_log(), comunes::error_critico);
			$mje = guarani_mensajes::get('800_alta_inscripcion_error');
			throw guarani::error($mje);
		}
	}

	/*
	 * Se reinicializa la clase de negocio para quedar preparada para una nueva inscripción
	 */
	function resetear()
	{
		$this->deseleccionar_todo();
		$this->s__retornos_ptos_control = array();
		$this->s__nro_transaccion = null;
		$this->s__fecha_transaccion = null;
		$this->s__estado = 'NUEVA';
	}

}
