<?php

namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\guarani;
use siu\errores\error_guarani;
use siu\errores\error_guarani_seguridad;
use siu\modelo\datos\catalogo;
use siu\modelo\interfaces\lista_materias;
use siu\modelo\entidades\parametro;
use siu\modelo\_comun\comunes;
use siu\modelo\datos\util;
use siu\modelo\controles\proceso_control;

class inscripcion_examen extends transaccion_alumno implements lista_materias
{
	protected $operacion = '800SIUEXA003';
	protected $operacion_baja = '800SIUEXA004';
	protected $evento = '4';
	protected $estrategia_acceso_datos;
	protected $opcion_materia = array();
	protected $opcion_mesa = array();
	static protected $ejecutar_controles;

	/**
	 * 
	 * @return \siu\modelo\datos\estrategias\inscripcion_examen
	 */
	function datos()
	{
		if (!isset($this->estrategia_acceso_datos)) {
			$this->estrategia_acceso_datos = kernel::localizador()->instanciar('modelo\\datos\\estrategias\\inscripcion_examen');
		}
		return $this->estrategia_acceso_datos;
	}

	//--------------------------------------------------------------------------
	//---- INFO
	//--------------------------------------------------------------------------

	function set_propiedades_curso()
	{
		$this->propuesta = (kernel::sesion()->esta_seteada('__propuesta')) ? kernel::sesion()->get('__propuesta') : '1';
		$this->plan = (kernel::sesion()->esta_seteada('__plan')) ? kernel::sesion()->get('__plan'): '1';
		$this->plan_version = (kernel::sesion()->esta_seteada('__plan_version')) ? kernel::sesion()->get('__plan_version') : '1';
		$this->alumno = (kernel::sesion()->esta_seteada('__alumno')) ? kernel::sesion()->get('__alumno'): '1';
		$this->ubicacion = (kernel::sesion()->esta_seteada('__ubicacion')) ? kernel::sesion()->get('__ubicacion'): '1';
		$this->modalidad = (kernel::sesion()->esta_seteada('__modalidad')) ? kernel::sesion()->get('__modalidad'): '1';
		$this->division = (kernel::sesion()->esta_seteada('__division')) ? kernel::sesion()->get('__division'): '1';
	}

	function info__lista_materias()
	{
//		if (kernel::persona()->es_perfil_cursos()) {
//			$this->es_curso();
//		}
		return $this->datos()->info__lista_materias($this->propuesta, $this->alumno, $this->persona, $this->plan, $this->plan_version, $this->ubicacion);
	}

	function info__lista_mesas($url_materia)
	{
//		if (kernel::persona()->es_perfil_cursos()) {
//			$this->es_curso();
//		}
		$info = $this->info__materia($url_materia);
		$this->plan = $info['plan']; 
		$this->plan_version = $info['plan_version']; 
		$this->tipo_plan = $info['tipo_plan']; 
		$actividad = $this->get_opcion_enviada_materia($url_materia);
		return $this->datos()->info__lista_mesas($this->propuesta, $this->alumno, $this->plan, $this->plan_version, $actividad, $this->ubicacion, $this->modalidad, $this->division, $this->tipo_plan);
	}

	/**
	 * Retorna el comprobante de baja. 
	 */
	function info__comprobante_baja($url_inscripcion)
	{
		$inscripcion = $this->get_opcion_enviada_cert_baja($url_inscripcion);
		return catalogo::consultar('inscripcion_examen', 'info_comprobante_baja', array('inscripcion' => $inscripcion));
	}

	function info__materia($url_materia)
	{
		$actividad = $this->get_opcion_enviada_materia($url_materia);
		$actividades = $this->info__lista_materias();
		foreach ($actividades as $m) {
			if ($m['id'] == $actividad) {
				return $m;
			}
		}
		throw new error_guarani('Materia inexistente');
	}

	function existe_inscripcion_a_examen($url_materia)
	{
//		if (kernel::persona()->es_perfil_cursos()) {
//			$this->es_curso();
//		}
		$materia = $this->get_opcion_enviada_materia($url_materia);
		return guarani::persona()->examenes()->existe_inscripcion_a_examen($materia, $this->alumno);
	}

	function info__inscripcion($mesa)
	{
		return guarani::persona()->examenes()->info__inscripcion($mesa);
	}

	function info__comprobante($url_actividad, $url_inscripcion)
	{
		$inscripcion = $this->get_opcion_enviada_inscripcion($url_inscripcion, $url_actividad);
		return catalogo::consultar('inscripcion_examen', 'info_comprobante_inscripcion', array('inscripcion' => $inscripcion));
	}

	//--------------------------------------------------------------------------
	//---- CONTROL ENVIO PARAMETROS
	//--------------------------------------------------------------------------

	function get_opcion_enviada_materia($url_materia)
	{
		if (!isset($this->opcion_materia[$url_materia])) {
			$materia = null;
			$materias = $this->info__lista_materias();
			foreach ($materias as $mat) {
				if ($mat[catalogo::id] == $url_materia) {
					$materia = $mat['id'];
				}
			}
			if (!isset($materia)) {
				throw new error_guarani_seguridad('OPCIONES ENVIADAS: No existe la actividad');
			}
			$this->opcion_materia[$url_materia] = $materia;
		}
		return $this->opcion_materia[$url_materia];
	}

	function get_opcion_enviada_mesa($url_materia, $url_llamado_mesa, $instancia = null)
	{
		if (!isset($this->opcion_mesa[$url_llamado_mesa])) {
			$llamdo_mesa = null;
			$mesas = $this->info__lista_mesas($url_materia);
			foreach ($mesas as $mes) {
				if ($mes[catalogo::id] == $url_llamado_mesa) {
					$llamdo_mesa = $mes['llamado_mesa'];
					if (isset($instancia)) {
						// Se valida la instancia seleccionada
						$instancia_seleccionada = false;
						foreach ($mes['instancias'] as $inst) {
							if ($inst['instancia'] == $instancia) {
								$instancia_seleccionada = true;
							}
						}
						if (!$instancia_seleccionada) {
							throw new error_guarani_seguridad('OPCIONES ENVIADAS: No existe la instancia');
						}
					}
				}
			}
			if (!isset($llamdo_mesa)) {
				throw new error_guarani_seguridad('OPCIONES ENVIADAS: No existe la mesa');
			}
			$this->opcion_mesa[$url_llamado_mesa] = $llamdo_mesa;
		}
		return $this->opcion_mesa[$url_llamado_mesa];
	}

	function get_opcion_enviada_anio_academico($url_materia, $url_llamado_mesa)
	{
		$anio_academico = null;
		$mesas = $this->info__lista_mesas($url_materia);
		foreach ($mesas as $mesa) {
			if ($mesa[catalogo::id] == $url_llamado_mesa) {
				$anio_academico = $mesa['anio_academico'];
			}
		}
		return $anio_academico;
	}

	function info__inscripciones($url_actividad = null)
	{
		if (isset($url_actividad)) {
			$actividad = $this->get_opcion_enviada_materia($url_actividad);
			$datos = guarani::persona()->examenes()->get_inscripciones($actividad);
		} else {
			$datos = guarani::persona()->examenes()->get_inscripciones();
		}
		return $datos;
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

	/*
	 * Determina automáticamente la instancia a inscribirse
	 */
	protected function determinar_instancia($actividad, $llamado_mesa)
	{
		$instancias = catalogo::consultar('inscripcion_examen', 'get_instancias_llamado_mesa', array('llamado_mesa' => $llamado_mesa));
		$acumulacion_mensajes = array();
		foreach ($instancias as $instancia) {
			$texto_a_agregar = "Instancia {$instancia['instancia_nombre']}:";
			try {
				// Para determinar la instancia, se ejecutan los controles aún cuando el parámetro exa_ejecuta_controles este seteado en N
				list($resultado_controles, $mensajes_controles) = $this->control__seleccion_instancia($actividad, $llamado_mesa, $instancia['instancia']);
				$acumulacion_mensajes = array_merge($acumulacion_mensajes, guarani::agregar_texto_inicio_mensajes($mensajes_controles, $texto_a_agregar));
				kernel::log()->add_debug('############ SE DETERMINO LA INSTANCIA #############', $instancia);
				return array($instancia['instancia'], $acumulacion_mensajes, $resultado_controles);
			} catch (error_guarani $error_instancia) {
				if (!isset($e)) {
					//@todo Pasar a mensajes 800_inscripcion_fallo_ctrl
					$e = new error_guarani('No podés inscribirte a ninguna instancia');
				}
				$e->add_mensajes_usuario(guarani::agregar_texto_inicio_mensajes($error_instancia->get_mensajes_usuario(), $texto_a_agregar));
				$mensajes = guarani::agregar_texto_inicio_mensajes($error_instancia->get_mensajes_usuario(), $texto_a_agregar);
				$acumulacion_mensajes = array_merge($acumulacion_mensajes, $mensajes);
			}
		}
		kernel::log()->add_debug('############ NINGUNA INSTANCIA PASO LOS CONTROLES #############', $acumulacion_mensajes);
		throw $e;
	}

	function la_seleccion_de_instancia_es_automatica()
	{
//		if (kernel::persona()->es_perfil_cursos()) {
//			$this->es_curso();
//		}

		$atributos = array($this->propuesta);
		$exa_instancia_automatica = parametro::get_valor('exa_instancia_automatica', $atributos);
		return ($exa_instancia_automatica == comunes::si);
	}

	//--------------------------------------------------------------------------
	//---- EVENTOS
	//--------------------------------------------------------------------------

	function evt__inscripcion($url_actividad, $url_llamado_mesa, $instancia = null)
	{		
		$llamado_mesa = $this->get_opcion_enviada_mesa($url_actividad, $url_llamado_mesa, $instancia);
		$anio_academico = $this->get_opcion_enviada_anio_academico($url_actividad, $url_llamado_mesa);
		$actividad = $this->get_opcion_enviada_materia($url_actividad);

		if ($this->la_seleccion_de_instancia_es_automatica()) {
			list($instancia, $mensajes_controles_instancia, $resultado_controles_instancia) = $this->determinar_instancia($actividad, $llamado_mesa);
		} else {
			$mensajes_controles_instancia = array();
			if (inscripcion_examen::ejecutar_controles()) {
				list($resultado_controles_instancia, $mensajes_controles_instancia) = $this->control__seleccion_instancia($actividad, $llamado_mesa, $instancia);
			}
		}

		kernel::log()->add_debug('INSCRIPCION - MESA: ' . $llamado_mesa, array('actividad' => $actividad,
			'llamado_mesa' => $llamado_mesa,
			'instancia' => $instancia));

		list($resultado_controles, $mensajes_controles) = $this->control__inscripcion($actividad, $llamado_mesa, $instancia, $anio_academico);

		$mensajes_controles = array_merge($mensajes_controles_instancia, $mensajes_controles);

		$hubo_advertencias = $resultado_controles == \guarani_punto_de_control::advertido || $resultado_controles_instancia == \guarani_punto_de_control::advertido;

		// Estado de la inscripcion
		// si hubo advertencias o no se ejecutan controles
		// @todo usar constantes
		$estado = $hubo_advertencias || !inscripcion_examen::ejecutar_controles() ? 'P' : 'A';
//		if (kernel::persona()->es_perfil_cursos()) {
//			$this->es_curso();
//		}
		$atributos = array($this->propuesta);
		$resultado = catalogo::consultar('inscripcion_examen', 'inscripcion_mesa_examen', array(
					'persona' => $this->persona,
					'alumno' => $this->alumno,
					'actividad' => $actividad,
					'llamado_mesa' => $llamado_mesa,
					'instancia' => $instancia,
					'plan_version' => $this->plan_version,
					'estado' => $estado,
					'param_exa_insc_unico_llamado' => parametro::get_valor('exa_insc_unico_llamado', $atributos),
					'param_exa_insc_cant_max_una_actividad_por_llamado' => parametro::get_valor('exa_insc_cant_max_una_actividad_por_llamado', array($this->plan)),
					'param_exa_insc_cant_max_una_actividad_por_turno' => parametro::get_valor('exa_insc_cant_max_una_actividad_por_turno', array($this->plan)),
					'param_exa_insc_siempre_pendiente' => parametro::get_valor('exa_insc_siempre_pendiente', array($this->plan)),
						)
		);

		kernel::log()->add_debug('RESULTADO INSCRIPCION EXAMEN -- INSCRIPCION: ' . $resultado['inscripcion'], $resultado);

		$parametros_mensaje = util::parsear_parametros_mensajes($resultado['mensaje_param']);
		$mensaje_inscripcion = kernel::traductor()->trans($resultado['mensaje_indice'], $parametros_mensaje);

		$propuestas = catalogo::consultar('atributos_params', 'get_propuestas_llamado_mesa', array('llamado_mesa' => $llamado_mesa));
		$ras = catalogo::consultar('atributos_params', 'get_propuestas_ras', array('propuestas' => $propuestas));

		if(parametro::get_valor('exa_notificar_estado_inscripcion', $ras) == comunes::si) {
			$parametros['estado'] = $resultado['estado'];
			$datos_llamado = \toba::consulta_php('co_mesas_de_examen')->get_datos_llamado_mesa($llamado_mesa);
			$parametros['fecha_mesa'] = $datos_llamado['fecha_formato_visual'];
			$parametros['nombre_actividad'] = $datos_llamado['actividad_nombre'];
			$datos_alumno = \toba::consulta_php('co_alumnos')->get_datos_alumno($this->alumno);
			$parametros['nombre_alumno'] = $datos_alumno[0]['nombres'];
			$parametros['persona'] = $this->persona;
			$parametros['email'] = \toba::consulta_php('co_personas')->get_emails_personas([$this->persona]);
			\guarani::notificador()->enviar(\notificador::inscribir_a_examenes_3w, $parametros);
		}
		
		if (($resultado['resultado'] > 0)) {
			guarani::persona()->examenes()->reset();
			return array(
				'mensaje_inscripcion' => $mensaje_inscripcion,
				'mensajes_controles' => $mensajes_controles
			);
		} else {
			$e = new error_guarani('Error en la inscripción a examen');
			$e->add_mensajes_usuario($mensajes_controles);
			$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, $mensaje_inscripcion));
			throw $e;
		}
	}

	function evt__eliminar_inscripcion_inicial($url_inscripcion)
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
		$datos_inscripcion = \toba::consulta_php('co_inscripciones_examenes')->get_listado_inscripciones('sga_insc_examen.inscripcion = ' . $inscripcion);	
		kernel::log()->add_debug('ELIMINAR INSCRIPCION EXAMEN', $inscripcion);

		$info_inscripcion = $this->datos()->info__inscripcion($inscripcion);
		$actividad = $info_inscripcion['actividad'];

		list($resultado_controles, $mensajes_controles) = $this->control__eliminar_inscripcion($actividad);

		$resultado = catalogo::consultar('inscripcion_examen', 'eliminar_inscripcion_mesa', array('inscripcion' => $inscripcion
		));

		kernel::log()->add_debug('RESULTADO BAJA INSCRIPCION EXAMEN -- INSCRIPCION: ' . $inscripcion, $resultado);

		$parametros_mensaje = util::parsear_parametros_mensajes($resultado['mensaje_param']);
		$mensaje_baja = kernel::traductor()->trans($resultado['mensaje_indice'], $parametros_mensaje);

		if (($resultado['resultado'] > 0)) {
			$propuestas = catalogo::consultar('atributos_params', 'get_propuestas_llamado_mesa', array('llamado_mesa' => $datos_inscripcion[0]['llamado_mesa']));
			$ras = catalogo::consultar('atributos_params', 'get_propuestas_ras', array('propuestas' => $propuestas));
			if(parametro::get_valor('exa_notificar_estado_inscripcion', $ras) == comunes::si) {
				$parametros['estado'] = \comunes::estado_baja;
				$fecha_entera = explode(' ', $datos_inscripcion[0]['fecha_examen_visual']);
				$parametros['fecha_mesa'] = $fecha_entera[0];
				$datos_elemento = \toba::consulta_php('co_elementos')->get_elemento_nombre($datos_inscripcion[0]['elemento'] );	
				$parametros['nombre_actividad'] = $datos_elemento[0]['nombre'];
				$datos_alumno = \toba::consulta_php('co_alumnos')->get_datos_alumno($this->alumno);
				$parametros['nombre_alumno'] = $datos_alumno[0]['nombres'];
				$parametros['persona'] = $this->persona;
				$parametros['email'] = \toba::consulta_php('co_personas')->get_emails_personas([$this->persona]);
				\guarani::notificador()->enviar(\notificador::inscribir_a_examenes_3w, $parametros);
			}
			
			$transaccion = $resultado['nro_transaccion'];
			kernel::log()->add_debug('TRANSACCION', $transaccion);
			guarani::persona()->examenes()->reset();
			return array(
				'mensaje_baja' => $mensaje_baja,
				'inscripcion' => $inscripcion,
				'mensajes_controles' => $mensajes_controles
			);
		} else {
			$e = new error_guarani('Error en la baja de la inscripción a examen');
			$e->add_mensajes_usuario($mensajes_controles);
			$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, $mensaje_baja));
			throw $e;
		}
	}

	//--------------------------------------------------------------------------
	//---- CONTROLES
	//--------------------------------------------------------------------------
	/**
	 * Determina si en la inscripción a examen se ejecutan o no los controles en 
	 * los puntos de control.
	 * 
	 * @return boolean 
	 */
	function ejecutar_controles()
	{
		if (!isset(self::$ejecutar_controles)) {

//			if (kernel::persona()->es_perfil_cursos()) {
//				$this->es_curso();
//			}


			$atributos = array($this->propuesta);
			$ejecuta_controles = parametro::get_valor('exa_ejecuta_controles', $atributos);
			self::$ejecutar_controles = ($ejecuta_controles == comunes::si);
		}
		return self::$ejecutar_controles;
	}

	function control__seleccion_materia($url_actividad)
	{
		$mensajes = array();
		if (inscripcion_examen::ejecutar_controles()) {
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_inicial(); // Se hacen aca los controles iniciales para permitir eliminar en la pagina inicial
			$actividad = $this->get_opcion_enviada_materia($url_actividad);
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_actividad($actividad, $puntos_de_control, $parametros);
			$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
			$proceso->ejecutar();
			$mensajes = $proceso->get_mensajes();
		}
		return $mensajes;
	}

	function control__seleccion_instancia($actividad, $llamado_mesa, $instancia)
	{
		list($puntos_de_control, $parametros) = $this->agregar_puntos_control_instancia($actividad, $llamado_mesa, $instancia);
		$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
		$resultado_controles = $proceso->ejecutar();
		$mensajes_controles = $proceso->get_mensajes();
		kernel::log()->add_debug('resultado control instancia', array($resultado_controles, $mensajes_controles));
		return array($resultado_controles, $mensajes_controles);
	}

	protected function control__inscripcion($actividad, $llamado_mesa, $instancia, $anio_academico)
	{
		// Se repiten controles anteriores por seguridad y para conocer las advertencias
		// (No hay ninguna primitiva del kernel que garantice la secuencia de requests para
		// permitir multiples hilos paralelos), por eso esto es necesario.
		// el costo es minimo porque esta todo en el cache

		if (inscripcion_examen::ejecutar_controles()) {
			// 1) Contoles Anteriores
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_inicial();
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_actividad($actividad, $puntos_de_control, $parametros);
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_instancia($actividad, $llamado_mesa, $instancia, $puntos_de_control, $parametros);
			$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
			$resultado_controles_anteriores = $proceso->ejecutar();

			// 2) Contoles Mesa
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_llamado_mesa($actividad, $llamado_mesa, $anio_academico);
			$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
			$resultado_controles = $proceso->ejecutar();
			$mensajes_controles = $proceso->get_mensajes();
			$resultado_controles = proceso_control::get_peor_resultado(array($resultado_controles_anteriores, $resultado_controles));
			return array($resultado_controles, $mensajes_controles);
		}
		return array(\guarani_punto_de_control::autorizado, array());
	}

	function control__eliminar_inscripcion($actividad)
	{
		// Se repiten controles anteriores por seguridad y para conocer las advertencias
		// (No hay ninguna primitiva del kernel que garantice la secuencia de requests para
		// permitir multiples hilos paralelos), por eso esto es necesario.
		// el costo es minimo porque esta todo en el cache

		if (inscripcion_examen::ejecutar_controles()) {
			// Contoles Anteriores
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_inicial();
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_actividad($actividad, $puntos_de_control, $parametros);
			$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros, $this->operacion_baja);
			$resultado_controles_anteriores = $proceso->ejecutar();

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

	//--------------------------------------------------------------------------
	//---- Puntos y Parámetros
	//--------------------------------------------------------------------------

	function agregar_puntos_control_inicial($puntos_de_control = array(), $parametros = array())
	{
		//(0 Alumno - Entidad) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a0:Alumno-Entidad
		//(1 Persona - Operación) - Una Persona elige una operación   http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a1:Persona-Operaci%C3%B3n
		//(2 Alumno  - Operación) - Un Alumno elige una operación     http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a2:Alumno-Operaci%C3%B3n
		//(18 Persona Propuesta) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a18:Persona-Propuesta
//		if (kernel::persona()->es_perfil_cursos()) {
//			$this->es_curso();
//		}


		$puntos_de_control = array_merge(array(
			\guarani_punto_de_control::alumno_entidad,
			\guarani_punto_de_control::persona_operacion,
			\guarani_punto_de_control::alumno_operacion,
			\guarani_punto_de_control::persona_propuesta
				), $puntos_de_control
		);

		$parametros = array_merge(array(
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
		//(17 Elegir Actividad Examen  http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a17:ElegirActividadExamen)
		$puntos_de_control = array_merge(array(\guarani_punto_de_control::elegir_actividad_examen), $puntos_de_control);
//		if (kernel::persona()->es_perfil_cursos()) {
//			$this->es_curso();
//		}
		$parametros = array_merge(array(
			'alumno' => $this->alumno,
			'elemento' => $actividad,
			'fecha' => \guarani_fecha::get_hoy(),
			'plan_version' => $this->plan_version
				), $parametros
		);

		return array($puntos_de_control, $parametros);
	}

	function agregar_puntos_control_llamado_mesa($actividad, $llamado_mesa, $anio_academico, $puntos_de_control = array(), $parametros = array())
	{
		//(8: Alumno - Año Académico) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a8:Alumno-AñoAcadémico
		//(9  Alumno-Actividad-Mesa de Examen http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a9:Elegirmesadeexamen)
		$puntos_de_control = array_merge(array(
			\guarani_punto_de_control::alumno_mesa_examen,
			\guarani_punto_de_control::alumno_anio_academico
				), $puntos_de_control
		);
//		if (kernel::persona()->es_perfil_cursos()) {
//			$this->es_curso();
//		}

		$parametros = array_merge(array(
			'alumno' => $this->alumno,
			'elemento' => $actividad,
			'fecha' => \guarani_fecha::get_hoy(),
			'llamado_mesa' => $llamado_mesa,
			'anio_academico' => $anio_academico,
			'plan_version' => $this->plan_version
				), $parametros
		);

		return array($puntos_de_control, $parametros);
	}

	function agregar_puntos_control_instancia($actividad, $llamado_mesa, $instancia, $puntos_de_control = array(), $parametros = array())
	{
		$puntos_de_control_instancia = \toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia);

		$puntos_de_control = array_merge($puntos_de_control_instancia, $puntos_de_control);

		kernel::log()->add_debug('puntos instancias', $puntos_de_control);
//		if (kernel::persona()->es_perfil_cursos()) {
//			$this->es_curso();
//		}

		$parametros = array_merge(array(
			'fecha' => \guarani_fecha::get_hoy(),
			'alumno' => $this->alumno,
			'plan_version' => $this->plan_version,
			'elemento' => $actividad,
			'llamado_mesa' => $llamado_mesa
				), $parametros
		);

		return array($puntos_de_control, $parametros);
	}

	function agregar_puntos_control_baja()
	{
		//(1 Persona - Operación) - Una Persona elige una operación   http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a1:Persona-Operaci%C3%B3n
		//(2 Alumno  - Operación) - Un Alumno elige una operación     http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a2:Alumno-Operaci%C3%B3n
		$puntos_de_control = array(
			\guarani_punto_de_control::persona_operacion,
			\guarani_punto_de_control::alumno_operacion
		);
//		if (kernel::persona()->es_perfil_cursos()) {
//			$this->es_curso();
//		}

		$parametros = array(
			'fecha' => \guarani_fecha::get_hoy(),
			'persona' => $this->persona,
			'alumno' => $this->alumno,
			'plan_version' => $this->plan_version
		);

		return array($puntos_de_control, $parametros);
	}

	function info__propuestas_administran_cursos_persona()
	{
		$propuestas_administran_cursos = catalogo::consultar('cursos', 'propuestas_administran_cursos', array());
		$inscripciones_persona = \toba::consulta_php('co_propuestas')->get_propuestas_alumno($this->persona);
		if ($inscripciones_persona) {
			foreach ($propuestas_administran_cursos as $key => $propuesta_administran_cursos) {
				$propuesta = $propuesta_administran_cursos ['valor'];
				foreach ($inscripciones_persona as $key => $inscripcion_persona) {
					if ($propuesta == $inscripcion_persona['valor']) {
						$propuesta_administran_curso_cursada[] = $propuesta_administran_cursos;
					}
				}
			}
		}
		return $propuesta_administran_curso_cursada;
	}

	function info__actividades_propuesta_curso_por_persona($propuesta_curso)
	{
//		if (kernel::persona()->es_perfil_cursos()) {
//			$this->es_curso();
//		}

		return $this->datos()->info__lista_materias($this->propuesta, $this->alumno, $this->persona, $this->plan, $this->plan_version, $this->ubicacion);
	}

}