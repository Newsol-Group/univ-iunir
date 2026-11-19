<?php
namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\guarani;
use siu\errores\error_guarani;
use siu\errores\error_guarani_seguridad;
use siu\errores\error_guarani_control;
use siu\modelo\datos\estrategias;
use siu\modelo\datos\catalogo;
use siu\modelo\interfaces\lista_materias;
use siu\modelo\entidades\parametro;
use siu\modelo\controles\proceso_control;
use siu\modelo\datos\util;
use siu\modelo\_comun\comunes;

class inscripcion_cursada extends transaccion_alumno implements lista_materias
{
	protected $operacion = '800SIUCUR001';
	protected $operacion_baja = '800SIUCUR005';
	protected $evento = '2';
	protected $estrategia_acceso_datos;
	// Cache request
	protected $lista_materias;   // Evaluar si tiene sentido
	protected $opcion_materia = array();
	protected $opcion_comision = array();
	protected $opcion_subcomision = array();
	protected $opcion_inscripcion = array();
	static protected $ejecutar_controles;
	
	function datos()
	{
		if (!isset($this->estrategia_acceso_datos)) {
			$this->estrategia_acceso_datos = new estrategias\inscripcion_cursada();
		}
		return $this->estrategia_acceso_datos;
	}

	//--------------------------------------------------------------------------
	//---- INFO
	//--------------------------------------------------------------------------

	function info__lista_materias()
	{
		if (!isset($this->lista_materias)) {
			$this->lista_materias = $this->datos()->info__lista_materias($this->propuesta, $this->alumno, $this->persona, $this->plan, $this->plan_version, $this->ubicacion);
		}
		return $this->lista_materias;
	}

	function info__lista_comisiones_baja()
	{
		return $this->datos()->info__lista_comisiones_baja();
	}

	function info__lista_comisiones($url_actividad)
	{
		$actividad = $this->get_opcion_enviada_materia($url_actividad);
		return $this->datos()->info__lista_comisiones($this->alumno, $actividad, $this->propuesta, $this->plan, $this->plan_version, $this->ubicacion, guarani::persona()->get_inicial_apellido(), $this->division);
	}

	function existe_inscripcion_materia($url_materia)
	{
		$materia = $this->get_opcion_enviada_materia($url_materia);
		$comision = guarani::persona()->cursadas()->existe_inscripcion_materia($materia);
		if ($comision !== false) {
			$comision = catalogo::generar_id($comision);
		}
		return $comision;
	}

	function info__inscripciones($url_actividad = null)
	{
		if (isset($url_actividad)) {
			$actividad = $this->get_opcion_enviada_materia($url_actividad);
			$datos = guarani::persona()->cursadas()->get_inscripciones($this->propuesta, $actividad);
		} else {
			$datos = guarani::persona()->cursadas()->get_inscripciones($this->propuesta, null);
		}
		return $datos;
	}

	function info__inscripcion($url_materia, $url_comision)
	{
		$comision = $this->get_opcion_enviada_comision($url_materia, $url_comision);
		return guarani::persona()->cursadas()->info__inscripcion($comision);
	}

	function info__cupo_comisiones($comisiones)
	{
		$comisiones_str = implode(",", $comisiones);
		$datos = catalogo::consultar('inscripcion_cursada', 'info_cupo_comisiones', array(
					'persona' => $this->persona,
					'comisiones_str' => $comisiones_str,
					'param_cur_controla_cupo_por' => parametro::get_valor('cur_controlar_cupo_por', array($this->propuesta)))
		);
		return $datos;
	}

	function info__comprobante($url_materia, $url_inscripcion)
	{
		$inscripcion = $this->get_opcion_enviada_inscripcion($url_inscripcion, $url_materia);
		return catalogo::consultar('inscripcion_cursada', 'info_comprobante_inscripcion', array('inscripcion' => $inscripcion));
	}

	function info__comisiones_por_inscripcion_prioridad($alumno, $nro_transaccion)
	{
		return catalogo::consultar('inscripcion_cursada', 'info_comisiones_por_inscripcion_prioridad', array('alumno' => $alumno, 'nro_transaccion' => $nro_transaccion));
	}

	/**
	 * Retorna el comprobante de baja. Hack para meter el comprobante de baja provisionalmente
	 */
	function info__comprobante_baja($url_inscripcion)
	{
		$inscripcion = $this->get_opcion_enviada_cert_baja($url_inscripcion);
		return catalogo::consultar('inscripcion_cursada', 'info_comprobante_baja', array('inscripcion' => $inscripcion));
	}

	function info__materia($url_materia)
	{
		$materia = $this->get_opcion_enviada_materia($url_materia);
		$materias = $this->info__lista_materias();
		foreach ($materias as $m) {
			if ($m['id'] == $materia) {
				return $m;
			}
		}
		throw new error_guarani('Materia inexistente');
	}

	function info__dias_semana()
	{
		return catalogo::consultar('sistema', 'get_dias_semana');
	}

	function info__turnos_cursadas()
	{
		return catalogo::consultar('sistema', 'get_turnos_cursadas');
	}

	function info__ubicaciones()
	{
		return catalogo::consultar('sistema', 'get_ubicaciones');
	}

	//--------------------------------------------------------------------------
	//---- CONTROL ENVÍO PARAMETROS
	//--------------------------------------------------------------------------

	function get_opcion_enviada_materia($url_materia)
	{
		if (!isset($this->opcion_materia[$url_materia])) {
			$materias = $this->info__lista_materias();
			foreach ($materias as $mat) {
				if ($mat[catalogo::id] == $url_materia) {
					$materia = $mat['id'];
				}
			}
			if (!isset($materia)) {
				throw new error_guarani_seguridad('OPCIONES ENVIADAS: No existe la materia');
			}
			$this->opcion_materia[$url_materia] = $materia;
		}
		return $this->opcion_materia[$url_materia];
	}

	function get_opcion_enviada_comision($url_materia, $url_comision)
	{
		if (!isset($this->opcion_comision[$url_comision])) {
			$comision = null;
			$comisiones = $this->info__lista_comisiones($url_materia);
			foreach ($comisiones as $com) {
				if ($com[catalogo::id] == $url_comision) {
					$comision = $com['comision'];
				}
			}
			if (!isset($comision)) {
				throw new error_guarani_seguridad('OPCIONES ENVIADAS: No existe la comision');
			}
			$this->opcion_comision[$url_comision] = $comision;
		}
		return $this->opcion_comision[$url_comision];
	}

	function get_opciones_enviada_subcomision($url_materia, $comision, $url_subcomisiones)
	{
		$subcom_ok = array();
		$comisiones = $this->info__lista_comisiones($url_materia);
		if (!isset($comisiones[$comision]['subcomisiones'])) {
			throw new error_guarani_seguridad('OPCIONES ENVIADAS: No existen subcomisiones para la comision ' . $comision);
		}
		foreach ($url_subcomisiones as $tipo_clase => $url_subcom) {
			$encontrada = false;
			$subs = $comisiones[$comision]['subcomisiones'][$tipo_clase];
			foreach ($subs as $sub) {
				if ($sub[catalogo::id] == $url_subcom) {
					$subcom_ok[$tipo_clase] = $sub['subcomision'];
					$encontrada = true;
					break;
				}
			}
			if (!$encontrada) {
				throw new error_guarani_seguridad("OPCIONES ENVIADAS existe COM: $comision - SUBCOM: $tipo_clase / $url_subcom");
			}
		}
		return $subcom_ok;
	}

	/**
	 * Se usa para la lista del medio, hay que obtener la comisión desde otro 
	 * listado. Las materias pueden tener un período de baja distinto del de alta
	 * @param type $url_comision 
	 */
	function get_opcion_enviada_comision_baja($url_comision)
	{
		if (!isset($this->opcion_comision_baja[$url_comision])) {
			$comision = null;
			$comisiones = $this->info__lista_comisiones_baja();
			foreach ($comisiones as $com) {
				if ($com['url_comision'] == $url_comision) {
					$comision = $com;
				}
			}
			if (!isset($comision)) {
				throw new error_guarani_seguridad('OPCIONES ENVIADAS: No existe la comision para dar de baja');
			}
			$this->opcion_comision[$url_comision] = $comision;
		}
		return $this->opcion_comision[$url_comision];
	}

	function get_opcion_enviada_inscripcion($url_inscripcion, $url_materia = null)
	{
		if (!isset($this->opcion_inscripcion[$url_inscripcion])) {
			if (isset($url_materia)) {
				$inscripciones = $this->info__inscripciones($url_materia);
			} else {
				$inscripciones = $this->info__inscripciones();
			}
			foreach ($inscripciones as $insc) {
				if ($insc[catalogo::id] == $url_inscripcion) {
					$inscripcion = $insc['inscripcion'];
				}
			}
			if (!isset($inscripcion)) {
				throw new error_guarani_seguridad('OPCIONES ENVIADAS: No existe la inscripción');
			}

			$this->opcion_inscripcion[$url_inscripcion] = $inscripcion;
		}

		return $this->opcion_inscripcion[$url_inscripcion];
	}

	function get_opcion_enviada_cert_baja($url_inscripcion)
	{
		$clave = "id_cert_baja_$url_inscripcion";
		if (!kernel::sesion()->esta_seteada($clave)) {
			throw new error_guarani_seguridad('el id de baja no existe');
		}
		return kernel::sesion()->get($clave);
	}

	function controlar_instancias($actividad, $instancia_mandatoria, $instancias)
	{
		kernel::log()->add_debug('############ INSTANCIAS A VERIFICAR #############', $instancias);
		$instancias_a_inscribir = array();
		$acumulacion_mensajes = array();
		$peor_resultado = \guarani_punto_de_control::autorizado;
		foreach ($instancias as $instancia) {
			$texto_a_agregar = "Instancia {$instancia['instancia_nombre']}:";
			try {
				list($resultado_controles, $mensajes_controles) = $this->control__seleccion_instancia($actividad, $instancia['instancia']);
				$peor_resultado = proceso_control::get_peor_resultado(array($peor_resultado, $resultado_controles));
				$acumulacion_mensajes = array_merge($acumulacion_mensajes, guarani::agregar_texto_inicio_mensajes($mensajes_controles, $texto_a_agregar));
				$instancias_a_inscribir[] = $instancia['instancia'];
			} catch (error_guarani $error_instancia) {
				if (!isset($e)) {
					//@todo: Pasar a mensajes 800_inscripcion_fallo_ctrl
					$e = new error_guarani('No podés inscribirte a ninguna instancia');
				}
				$e->add_mensajes_usuario(guarani::agregar_texto_inicio_mensajes($error_instancia->get_mensajes_usuario(), $texto_a_agregar));
				if ($instancia['instancia'] == $instancia_mandatoria) {
					kernel::log()->add_debug('############ LA INSTANCIA MANDATORIA NO PASO LOS CONTROLES #############', $acumulacion_mensajes);
					throw $e;
				}
				$mensajes = guarani::agregar_texto_inicio_mensajes($error_instancia->get_mensajes_usuario(), $texto_a_agregar);
				$acumulacion_mensajes = array_merge($acumulacion_mensajes, $mensajes);
			}
		}

		kernel::log()->add_debug('############ INSTANCIAS A INSCRIBIR #############', $instancias_a_inscribir);

		if (empty($instancias_a_inscribir)) {
			kernel::log()->add_debug('############ NINGUNA INSTANCIA PASO LOS CONTROLES #############', $acumulacion_mensajes);
			throw $e;
		} else {
			return array($peor_resultado, $instancias_a_inscribir, $acumulacion_mensajes);
		}
	}

	//--------------------------------------------------------------------------
	//---- EVENTOS
	//--------------------------------------------------------------------------

	function evt__inscripcion_prioridad($comisiones_encriptadas)
	{
		// La cantidad de comisiones elegidas no puede superar el valor del atributo 
		// 'sga_elementos_atrib.prioridad_cant_maxima' para la actividad.
		$this->validar_cant_maxima_inscripciones($comisiones_encriptadas);

		$comisiones = array();
		$periodo_lectivo = null;
		foreach ($comisiones_encriptadas as $clave => $comision_enc) {
			$comision = $this->get_opcion_enviada_comision($comision_enc['materia'], $comision_enc['comision']);
			$info_comision = $this->datos()->info__comision($comision, $this->propuesta, $this->plan, $this->plan_version);
			
			if ($info_comision['inscripcion_cerrada'] == \comunes::si) {
				static::validar_codigo_inscripcion_cerrada($info_comision['inscripcion_cerrada_codigo'], $comision_enc['codigo_inscripcion_cerrada'], $info_comision['comision_nombre']);
			}

			$hay_subcomisiones = (is_array($comision_enc['subcomisiones']) && \guarani_arreglos::count($comision_enc['subcomisiones']) > 0);
			$info_comision['subcomisiones'] = $hay_subcomisiones ? $this->get_opciones_enviada_subcomision($comision_enc['materia'], $comision, $comision_enc['subcomisiones']) : array();
			$info_comision['prioridad'] = $clave + 1;

			// Todas las comisiones seleccionadas deben pertenecer al mismo período lectivo.
			if (!isset($periodo_lectivo)) {
				$periodo_lectivo = $info_comision['periodo_lectivo'];
			} elseif ($info_comision['periodo_lectivo'] != $periodo_lectivo) {
				$e = new error_guarani(kernel::traductor()->trans('800SIU_insc_cursada_error'));
				$mensaje_inscripcion = kernel::traductor()->trans('800CUR_comisiones_igual_periodo_lectivo');
				$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, $mensaje_inscripcion));
				throw $e;
			}

			list($info_comision['estado'], $info_comision['instancias_a_inscribir'], $info_comision['mensajes_controles']) = $this->validar_inscripcion($info_comision, \inscripcion_cursada::tipo_preinscripcion);

			$comisiones[] = $info_comision;
		}

		try {
			kernel::db()->abrir_transaccion();
			$nro_transaccion = \toba::consulta_php('co_inscripciones_cursadas')->get_nuevo_nro_transaccion();
			foreach ($comisiones as $comision) {
				if ($comision['prioridad'] == 1) {
					$resultado_prioridad_1 = $this->inscribir($comision, \inscripcion_cursada::tipo_preinscripcion, $nro_transaccion);
				} else {
					$this->inscribir($comision, 'P', $nro_transaccion);
				}
			}
			kernel::db()->cerrar_transaccion();
		} catch (error_guarani $e) {
			kernel::db()->abortar_transaccion();
			throw $e;
		}

		return $resultado_prioridad_1;
	}

	
//Se agrega $codigo_inscripcion_cerrada_recibido como parametro, debe agregarse si se quiere corrobar el codigo en caso de que la inscripción sea cerrada, 
//Si no se envía va a aparecer un mensaje diciendo que no se puede mandar un código vacío.
	function evt__inscripcion($url_materia, $url_comision, $url_subcomisiones, $codigo_inscripcion_cerrada_recibido = '')
	{
		$comision = $this->get_opcion_enviada_comision($url_materia, $url_comision);
		$info_comision = $this->datos()->info__comision($comision, $this->propuesta, $this->plan, $this->plan_version);
		if($info_comision['inscripcion_cerrada'] == \comunes::si){
			static::validar_codigo_inscripcion_cerrada($info_comision['inscripcion_cerrada_codigo'], $codigo_inscripcion_cerrada_recibido);
		}	
		$hay_subcomisiones = (is_array($url_subcomisiones) && \guarani_arreglos::count($url_subcomisiones) > 0);
		$info_comision['subcomisiones'] = $hay_subcomisiones ? $this->get_opciones_enviada_subcomision($url_materia, $comision, $url_subcomisiones) : array();
		$info_comision['prioridad'] = 1;
		
		list($info_comision['estado'], $info_comision['instancias_a_inscribir'], $info_comision['mensajes_controles']) = $this->validar_inscripcion($info_comision);

		return $this->inscribir($info_comision);
	}

	function validar_cant_maxima_inscripciones($comisiones_encriptadas)
	{
		$comision_enc = current($comisiones_encriptadas);
		$materia = $this->info__materia($comision_enc['materia']);
		if (\guarani_arreglos::count($comisiones_encriptadas) > $materia['prioridad_cantidad_maxima']) {
			$e = new error_guarani(kernel::traductor()->trans('800SIU_insc_cursada_error'));
			$mensaje_inscripcion = kernel::traductor()->trans('800CUR_max_inscripcion_prioridad_excedida', array('%1%' => $materia['prioridad_cantidad_maxima']));
			$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, $mensaje_inscripcion));
			throw $e;
		}
	}

	function validar_inscripcion($info_comision, $tipo = \inscripcion_cursada::tipo_inscripcion)
	{
		$estado = \inscripcion_cursada::estado_pendiente;
		if ($info_comision['prioridad'] == 1) {
			// Cantidad máxima de inscripciones por actividad y período lectivo.
			$this->validar_inscripciones_actividad_periodo_lectivo($info_comision['actividad'], $info_comision['actividad_codigo'], $info_comision['actividad_nombre'], $info_comision['periodo_lectivo']);
		}

		// Instancias.
		$instancia_mandatoria = parametro::get_valor('cur_instancia_mandatoria', array($this->propuesta));
		$instancias = explode(',', $info_comision['instancias_id']); // todas las instancias de la comisión
		$instancias_nombre = explode('/', $info_comision['instancias']); // todas las instancias de la comisión
		foreach ($instancias as $key => $instancia) {
			$res_instancias[$key]['instancia'] = filter_var($instancia, FILTER_SANITIZE_NUMBER_INT);
			$res_instancias[$key]['instancia_nombre'] = $instancias_nombre[$key];
		}
		list($resultado_controles_instancias, $instancias_a_inscribir, $mensajes_controles_instancia) = $this->controlar_instancias($info_comision['actividad'], $instancia_mandatoria, $res_instancias);

		// Controles.
		list($resultado_controles, $mensajes_controles) = $this->control__inscripcion($info_comision['comision'], $info_comision['subcomisiones']);

		$mensajes_controles = array_merge($mensajes_controles_instancia, $mensajes_controles);
		$hubo_advertencias = $resultado_controles == \guarani_punto_de_control::advertido || $resultado_controles_instancias == \guarani_punto_de_control::advertido;

		// Estado de la inscripción.
		if ($tipo == \inscripcion_cursada::tipo_inscripcion && inscripcion_cursada::ejecutar_controles() && !$hubo_advertencias) {
			$estado = \inscripcion_cursada::estado_aceptada;
		}

		return array($estado, $instancias_a_inscribir, $mensajes_controles);
	}

	function validar_inscripciones_actividad_periodo_lectivo($actividad, $actividad_codigo, $actividad_nombre, $periodo_lectivo)
	{
		$cant_max_inscripciones = parametro::get_valor('cur_cant_max_insc_igual_actividad_periodo_lectivo', array($this->propuesta));
		$cant_inscripciones = \toba::consulta_php('co_inscripciones_cursadas')->get_cant_inscripciones_actividad_periodo_lectivo($this->alumno, $actividad, $periodo_lectivo);
		if ($cant_inscripciones >= $cant_max_inscripciones) {
			$nombre_actividad = "($actividad_codigo) - $actividad_nombre ";
			$e = new error_guarani(kernel::traductor()->trans('800SIU_insc_cursada_error'));
			$mensaje_inscripcion = kernel::traductor()->trans('800CUR_max_inscripcion_excedida', array('%1%' => $nombre_actividad));
			$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, $mensaje_inscripcion));
			throw $e;
		}
	}

	function inscribir($info_comision, $tipo = \inscripcion_cursada::tipo_inscripcion, $nro_transaccion = null)
	{
		// Ejecutar DB.
		$parametros = array(
			'comision' => $info_comision['comision'],
			'alumno' => $this->alumno,
			'persona' => $this->persona,
			'plan' => $this->plan,
			'plan_version' => $this->plan_version,
			'prioridad' => $info_comision['prioridad'],
			'estado' => $info_comision['estado'],
			'tipo' => $tipo,
			'estado_preinscripcion' => 'P',
			'fuera_de_termino' => 'N',
			'nro_transaccion' => $nro_transaccion,
			'instancias' => $info_comision['instancias_a_inscribir'],
			'subcomisiones' => $info_comision['subcomisiones'],
			'periodo_lectivo' => $info_comision['periodo_lectivo'],
			'param_cur_controlar_cupo' => parametro::get_valor('cur_controlar_cupo', array($this->propuesta)),
			'param_cur_controlar_cupo_por' => parametro::get_valor('cur_controlar_cupo_por', array($this->propuesta)),
			'param_cur_insc_siempre_pendiente' => parametro::get_valor('cur_insc_siempre_pendiente', array($this->plan))
		);
		$resultado = catalogo::consultar('inscripcion_cursada', 'inscripcion_comision', $parametros);
		kernel::log()->add_debug('RESULTADO INSCRIPCION -- COM: ' . $info_comision['comision'] .
				' - SUBCOM: ' . implode('-', $info_comision['subcomisiones']) .
				' - STATUS: ' . $info_comision['estado'], $resultado);

		$parametros_mensaje = util::parsear_parametros_mensajes($resultado['mensaje_param']);
		$mensaje_inscripcion = kernel::traductor()->trans($resultado['mensaje_indice'], $parametros_mensaje);
		if ($resultado['resultado'] > 0) {
			guarani::persona()->cursadas()->reset();

			if (parametro::get_valor('cur_notificar_estado_inscripcion') == comunes::si) {
				
				$datos_alumno = current(\toba::consulta_php('co_alumnos')->get_datos_alumno($this->alumno));

				$parametros['estado']			= $resultado['estado'];
				$parametros['nombre_comision']	= $info_comision['comision_nombre'];
				$parametros['nombre_materia']	= $info_comision['actividad_nombre'];
				$parametros['nombre_alumno']	= $datos_alumno['nombres'];
				$parametros['persona']			= $this->persona;
				$parametros['email']			= \toba::consulta_php('co_personas')->get_emails_personas(array($this->persona));

				\guarani::notificador()->enviar(\notificador::inscribir_a_cursadas_3w, $parametros);
			}

			return array(	'mensaje_inscripcion'	=> $mensaje_inscripcion,
							'mensajes_controles'	=> $info_comision['mensajes_controles']
						);
		} else {
			// hubo un error en la inscripción, se formatea para que se muestre como un error de control y además se lo 
			// concatena con los posibles warnings e infos provenientes de control__inscripcion
			// aceptamos sugerencias...
			$e = new error_guarani(kernel::traductor()->trans('800SIU_insc_cursada_error'));
			$e->add_mensajes_usuario($info_comision['mensajes_controles']);
			$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, $mensaje_inscripcion));
			throw $e;
		}
	}

	/**
	 * Elimina una inscripción a una cursada aunque no tenga un período de alta
	 * activo. Tiene que tener activo el período de baja.
	 */
	function evt__eliminar_inscripcion_periodo_alta_cerrado($url_inscripcion)
	{
		$inscripcion = $this->get_opcion_enviada_inscripcion($url_inscripcion);
		return $this->eliminar_inscripcion($inscripcion);
	}

	function evt__eliminar_inscripcion($url_actividad, $url_inscripcion)
	{
		$inscripcion = $this->get_opcion_enviada_inscripcion($url_inscripcion, $url_actividad);
		return $this->eliminar_inscripcion($inscripcion);
	}

	protected function eliminar_inscripcion($inscripcion)
	{
		$datos_inscripcion = \toba::consulta_php('co_inscripciones_cursadas')->get_insc_cursada_por_id($inscripcion);
		$comision = $datos_inscripcion['comision'];
		
		kernel::log()->add_debug('ELIMINAR INSCRIPCION', $inscripcion);
		$info_inscripcion = $this->datos()->info__inscripcion($inscripcion);
		$actividad = $info_inscripcion['actividad'];

		list($resultado_controles, $mensajes_controles) = $this->control__eliminar_inscripcion($actividad);
		$resultado = catalogo::consultar('inscripcion_cursada', 'eliminar_inscripcion_comision', array('inscripcion' => $inscripcion));

		kernel::log()->add_debug('RESULTADO BAJA INSCRIPCION -- INSCRIPCION: ' . $inscripcion, $resultado);

		$parametros_mensaje = util::parsear_parametros_mensajes($resultado['mensaje_param']);
		$mensaje_baja = kernel::traductor()->trans($resultado['mensaje_indice'], $parametros_mensaje);

		if (($resultado['resultado'] > 0)) {
			$transaccion = $resultado['nro_transaccion'];
			
			if(parametro::get_valor('cur_notificar_estado_inscripcion') == comunes::si) {
				$datos_comision = \toba::consulta_php('co_comisiones')->get_datos_comision($comision);

				$parametros['nombre_comision'] = $datos_comision[0]['comision_nombre'];
				
				$nombre_materia = $datos_comision[0]['actividad_desc'];
				$parte_nombre = explode("(", $nombre_materia);
				$parametros['nombre_materia'] = $parte_nombre[0];

				$datos_alumno = \toba::consulta_php('co_alumnos')->get_datos_alumno($this->alumno);
				$parametros['nombre_alumno'] = $datos_alumno[0]['nombres'];

				$parametros['persona'] = $this->persona;
				$parametros['email'] = \toba::consulta_php('co_personas')->get_emails_personas( Array($this->persona) );

				$parametros['estado'] = \comunes::estado_baja;

				\guarani::notificador()->enviar(\notificador::inscribir_a_cursadas_3w, $parametros);
				
			}
			
			
			kernel::log()->add_debug('TRANSACCION', $transaccion);
			guarani::persona()->cursadas()->reset();
			return array($mensaje_baja, $transaccion, $inscripcion, 'advertencias' => $mensajes_controles);
		} else {
			$e = new error_guarani_control('Error en la inscripción a cursada');
			$e->add_mensajes_usuario($mensajes_controles);
			$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, $mensaje_baja));
			throw $e;
		}
	}

	//--------------------------------------------------------------------------
	//---- CONTROLES
	//--------------------------------------------------------------------------
	
	/**
	 * Determina si en la inscripción a cursada se ejecutan o no los controles en los puntos de control.
	 * 
	 * @return boolean 
	 */
	function ejecutar_controles()
	{
		if (!isset(self::$ejecutar_controles)) {
			self::$ejecutar_controles = (parametro::get_valor('cur_ejecuta_controles', array($this->propuesta)) == comunes::si);
		}
		return self::$ejecutar_controles;
	}

	function control__seleccion_materia($url_actividad)
	{
		$mensajes = array();
		if (inscripcion_cursada::ejecutar_controles()) {
			// Se hacen aca los controles iniciales para permitir eliminar en la pagina inicial
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_inicial();
			$actividad = $this->get_opcion_enviada_materia($url_actividad);
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_actividad($actividad, $puntos_de_control, $parametros);
			$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
			$proceso->ejecutar();
			$mensajes = $proceso->get_mensajes();
		}
		return $mensajes;
	}

	protected function control__inscripcion($comision, $subcomisiones)
	{
		// 1) Se repiten controles anteriores por seguridad y para conocer las advertencias
		// (No hay ninguna primitiva del kernel que garantice la secuencia de requests para
		// permitir multiples hilos paralelos), por eso esto es necesario.
		// el costo es minimo porque esta todo en el cache
		if (inscripcion_cursada::ejecutar_controles()) {
			$info_comision = $this->datos()->info__comision($comision, $this->propuesta, $this->plan, $this->plan_version);
			$actividad = $info_comision['actividad'];
			$comision = $info_comision['comision'];
			$anio_academico = $info_comision['anio_academico'];

			// 1) Contoles Anteriores
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_inicial();
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_actividad($actividad, $puntos_de_control, $parametros);
			$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
			$resultado_controles_anteriores = $proceso->ejecutar();

			// 2) Control año académico
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_anio_academico($anio_academico, $puntos_de_control, $parametros);
			$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
			$resultado_controles_anio_academico = $proceso->ejecutar();
			$mensajes_controles_anio_academico = $proceso->get_mensajes();

			// 3) Control comisiones
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_comision($actividad, $comision);
			$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
			$resultado_controles_comision = $proceso->ejecutar();
			$mensajes_controles_comision = $proceso->get_mensajes();

			// 3) Control subcomisiones
			$resultado_controles_subcomisiones = \guarani_punto_de_control::autorizado;
			$mensajes_controles_subcomisiones = array();
			$hay_subcomisiones = (is_array($subcomisiones) && \guarani_arreglos::count($subcomisiones) > 0);
			if ($hay_subcomisiones) {
				foreach ($subcomisiones as $subcomision) {
					list($puntos_de_control, $parametros) = $this->agregar_puntos_control_subcomision($actividad, $comision, $subcomision);
					$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
					$resultado_controles_subcomisiones = proceso_control::get_peor_resultado(array($resultado_controles_subcomisiones, $proceso->ejecutar()));
					$mensajes_controles_subcomisiones = array_merge($mensajes_controles_subcomisiones, $proceso->get_mensajes());
				}
			}

			$resultado_controles = proceso_control::get_peor_resultado(array($resultado_controles_anteriores, $resultado_controles_anio_academico, $resultado_controles_comision, $resultado_controles_subcomisiones));
			$mensajes_controles = array_merge($mensajes_controles_comision, $mensajes_controles_subcomisiones);
			return array($resultado_controles, $mensajes_controles);
		}
		return array(\guarani_punto_de_control::autorizado, array());
	}

	function control__eliminar_inscripcion($actividad)
	{
		// 1) Se repiten controles anteriores por seguridad y para conocer las advertencias
		// (No hay ninguna primitiva del kernel que garantice la secuencia de requests para
		// permitir multiples hilos paralelos), por eso esto es necesario.
		// el costo es minimo porque esta todo en el cache
		if (inscripcion_cursada::ejecutar_controles()) {
			// Contoles Anteriores
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_inicial();
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_actividad($actividad, $puntos_de_control, $parametros);
			$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros, $this->operacion_baja);
			$resultado_controles_anteriores = $proceso->ejecutar(); //Los mensajes estos no importan, ya se mostraron antes
			// Control Baja
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_baja();
			$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros, $this->operacion_baja);
			$resultado_controles = $proceso->ejecutar();
			$mensajes_controles = $proceso->get_mensajes();
			$resultado_controles = proceso_control::get_peor_resultado(array($resultado_controles_anteriores, $resultado_controles));
			return array($resultado_controles, $mensajes_controles);
		}
		return array(\guarani_punto_de_control::autorizado, array());
	}

	function control__seleccion_instancia($actividad, $instancia)
	{
		list($puntos_de_control, $parametros) = $this->agregar_puntos_control_instancia($actividad, $instancia);
		$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
		$resultado_controles = $proceso->ejecutar();
		$mensajes_controles = $proceso->get_mensajes();
		return array($resultado_controles, $mensajes_controles);
	}

	//--------------------------------------------------------------------------
	//---- Puntos y Parámetros    
	//--------------------------------------------------------------------------

	function agregar_puntos_control_instancia($actividad, $instancia, $puntos_de_control = array(), $parametros = array())
	{
		// (4: Instancia Regularizar) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a4:InstanciaRegularizar
		// (5: Instancia Promocionar) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a5:InstanciaPromocionar
		$puntos_de_control_instancia = \toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia);
		$puntos_de_control = array_merge($puntos_de_control_instancia, $puntos_de_control);
		$parametros = array_merge(array('fecha' => \guarani_fecha::get_hoy(),
			'alumno' => $this->alumno,
			'plan_version' => $this->plan_version,
			'elemento' => $actividad), $parametros
		);
		return array($puntos_de_control, $parametros);
	}

	function agregar_puntos_control_inicial($puntos_de_control = array(), $parametros = array())
	{
		//(0 Alumno - Entidad) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a0:Alumno-Entidad
		//(1 Persona - Operación) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a1:Persona-Operaci%C3%B3n
		//(2 Alumno  - Operación) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a2:Alumno-Operaci%C3%B3n
		//(18 Persona Propuesta) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a18:Persona-Propuesta
		$puntos_de_control = array_merge(
				array(
			\guarani_punto_de_control::alumno_entidad,
			\guarani_punto_de_control::persona_operacion,
			\guarani_punto_de_control::alumno_operacion,
			\guarani_punto_de_control::persona_propuesta
				), $puntos_de_control
		);

		$parametros = array_merge(
				array(
			'alumno' => $this->alumno,
			'persona' => $this->persona,
			'propuesta' => $this->propuesta,
			'plan_version' => $this->plan_version,
			'fecha' => \guarani_fecha::get_hoy()
				), $parametros
		);

		return array($puntos_de_control, $parametros);
	}

	function agregar_puntos_control_actividad($actividad, $puntos_de_control = array(), $parametros = array())
	{
		//(3 Elegir Actividad )
		$puntos_de_control = array_merge(array(\guarani_punto_de_control::alumno_actividad), $puntos_de_control);
		$parametros = array_merge(
				array(
			'alumno' => $this->alumno,
			'elemento' => $actividad,
			'fecha' => \guarani_fecha::get_hoy(),
			'plan_version' => $this->plan_version
				), $parametros
		);
		return array($puntos_de_control, $parametros);
	}

	function agregar_puntos_control_anio_academico($anio_academico, $puntos_de_control = array(), $parametros = array())
	{
		//(8: Alumno - Año Académico) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a8:Alumno-AñoAcadémico
		$puntos_de_control = array_merge(array(\guarani_punto_de_control::alumno_anio_academico), $puntos_de_control);
		$parametros = array_merge(
				array(
			'alumno' => $this->alumno,
			'anio_academico' => $anio_academico,
			'fecha' => \guarani_fecha::get_hoy(),
			'plan_version' => $this->plan_version
				), $parametros
		);
		return array($puntos_de_control, $parametros);
	}

	function agregar_puntos_control_comision($actividad, $comision, $puntos_de_control = array(), $parametros = array())
	{
		// 6: Elegir Comisión
		$puntos_de_control = array_merge(array(\guarani_punto_de_control::alumno_comision), 	$puntos_de_control);
		$parametros = array_merge(array(
				'alumno' => $this->alumno,
				'elemento' => $actividad,
				'fecha' => \guarani_fecha::get_hoy(),
				'comision' => $comision,
				'plan_version' => $this->plan_version
			), $parametros
		);
		return array($puntos_de_control, $parametros);
	}

	function agregar_puntos_control_subcomision($actividad, $comision, $subcomision, $puntos_de_control = array(), $parametros = array())
	{
		$puntos_de_control = array_merge(array(\guarani_punto_de_control::alumno_subcomision), $puntos_de_control);

		$parametros = array_merge(array(
				'alumno' => $this->alumno,
				'elemento' => $actividad,
				'fecha' => \guarani_fecha::get_hoy(),
				'comision' => $comision,
				'subcomision' => $subcomision,
				'plan_version' => $this->plan_version
			), $parametros
		);

		return array($puntos_de_control, $parametros);
	}

	function agregar_puntos_control_baja()
	{
		$puntos_de_control = array(
			\guarani_punto_de_control::persona_operacion,
			\guarani_punto_de_control::alumno_operacion
		);

		$parametros = array(
			'fecha' => \guarani_fecha::get_hoy(),
			'persona' => $this->persona,
			'alumno' => $this->alumno,
			'plan_version' => $this->plan_version
		);

		return array($puntos_de_control, $parametros);
	}
	
	public static function validar_codigo_inscripcion_cerrada($codigo_inscripcion_esperado, $codigo_inscripcion_recibido, $nombre_comision = null)
	{

        $msg_error = "";

		// Si es correcto (si se desea insensible a mayúsculas y minúsculas usar strcasecmp)
		if(strcmp($codigo_inscripcion_recibido, $codigo_inscripcion_esperado) == 0) {
			
			return true;
			
		} else {//Si es incorrecto
			
			$msg_error = kernel::traductor()->trans('inscripcion_cursos.codigo_inscripcion_incorrecto');
			if (!is_null($nombre_comision)){
				$msg_error = kernel::traductor()->trans('inscripcion_cursos.codigo_inscripcion_comision', ['%comision%' => $nombre_comision]).$msg_error;
			}

			$e = new error_guarani("");
			$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, $msg_error));
			throw $e;
		}
    }

}
