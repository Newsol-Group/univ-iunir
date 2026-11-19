<?php
namespace siu\operaciones\admin_actas_examen_fc;

use SIU\Chulupi\ErrorKernel;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\modelo\DecoradorEntidad;
use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\util\validador;
use siu\errores\error_guarani_seguridad;
use siu\errores\error_guarani;
use siu\modelo\entidades\parametro;
use siu\errores\error_guarani_nro_acta_fc;

class controlador extends controlador_g3w2
{
	public $id_acta = null;
	public $hash = null;
	public $params = null;

	function get_clase_vista()
	{
		$vista_actas = array('index', 'persistir_datos_acta', 'edicion', 'editar', 'modificar_datos_acta');
		if (in_array($this->accion, $vista_actas)) {
			// se muestra la lista de actas
			return 'vista_actas';
		} else {
			return 'vista_alumnos';
		}
	}

	/**
	 * @return \siu\modelo\transacciones\admin_actas_examen_fc
	 */
	function modelo()
	{
		return guarani::admin_actas_examen_fc();
	}

        /**
	 * @return \siu\modelo\transacciones\zona_examenes
	 */
	function modelo_zona_examenes()
	{
		return guarani::zona_examenes();
	}

	protected function validar_acta_editable()
	{
		// Si el acta NO está abierta NO se permite editarla
		if ($this->params['estado'] != \acta::estado_abierta) {
			$url = kernel::vinculador()->crear('impresion_actas_examen', 'index', array(0 => $this->params[catalogo::id]));
			$this->nuevo_request_url($url, kernel::traductor()->trans('admin_actas_examen_fc.no_podes_editar_acta'));
		}
	}

	function accion__index()
	{
		
	}

	function accion__editar()
	{
		$this->load_params('*');
		$this->validar_acta_editable();
	}

	function accion__modificar_datos_acta()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$this->load_params('*');
		$this->validar_acta_editable();

		$datos = $this->procesar_acta();

		if ($datos) {
			$datos['where']['id_acta'] = $this->id_acta;
			$datos['where']['mesa_examen'] = $this->params['mesa_examen'];
			$datos['where']['llamado_mesa'] = $this->params['llamado_mesa'];

			try {
				$this->modelo()->editar_encabezado($datos);
			} catch (ErrorKernel $e) {
				$pagelet = $this->vista()->pagelet('acta');
				$pagelet->set_estado_info(pagelet_acta::FORM_ERROR_VALIDACION);
				return false;
			}

			$link = $this->generar_link_siguiente($this->id_acta, '0');
			kernel::renderer()->add_to_ajax_response('url_siguiente', $link);
			kernel::renderer()->add_to_ajax_response('guardado_datos_exitoso', kernel::traductor()->trans('admin_actas_examen_fc.guardado_datos_exitoso'));
			kernel::renderer()->add_to_ajax_response('nueva', false);
		}
	}

	function accion__persistir_datos_acta()
	{
		if (!kernel::request()->isPost()) {
			return;
		}
		$datos = $this->procesar_acta();
		if ($datos) {
			try {
				$this->id_acta = $this->modelo()->crear_encabezado($datos);
                                // Limpio la cache de la "Zona de Exámenes" para que se vea reflejado el examen
                                $this->modelo_zona_examenes()->invalidar_examenes();
			} catch (\Exception $e) {
				$pagelet = $this->vista()->pagelet('acta');
				$pagelet->set_estado_info(pagelet_acta::FORM_ERROR_VALIDACION);
				return false;
			}

			$link = $this->generar_link_siguiente($this->id_acta, '1');
			kernel::renderer()->add_to_ajax_response('nueva', true);
			kernel::renderer()->add_to_ajax_response('url_siguiente', $link);
			kernel::renderer()->add_to_ajax_response('hash', $this->get_hash($this->id_acta));
		}
	}

	protected function procesar_acta()
	{
		$pagelet = $this->vista()->pagelet('acta');
		kernel::renderer()->add($pagelet);
		$form_builder_acta = $pagelet->get_form_builder();
		$form = $form_builder_acta->get_formulario();
		try {
			if ($form->procesar()) {
				$datos = $this->validar_datos_acta($form->get_datos());
				$pagelet->set_estado_info(pagelet_acta::FORM_GUARDADO);
				return $datos;
			} else {
				$pagelet->set_estado_info(pagelet_acta::FORM_ERROR_VALIDACION);
				return false;
			}
		} catch (error_guarani_nro_acta_fc $e) {
			$pagelet->set_estado_info(pagelet_acta::FORM_ERROR_NRO_ACTA);
			return false;
		}
	}

	function accion__siguiente()
	{
		$this->load_params();
		$es_nuevo = $this->validate_param('n', 'get', validador::TIPO_INT);
		$pagelet = $this->vista()->pagelet('alumnos');
		$pagelet->add_var_js('nuevo', $es_nuevo); //es solo para pavadas visuales, no es seguro
		kernel::renderer()->add($pagelet);
	}

	function accion__buscar_actividades()
	{
		$json = array();
		$actividades = catalogo::consultar('acta', 'get_actividades_actas_fuera_calendario', array('term' => $this->getAutocompleteTerm(), 'actividad' => null));
		foreach ($actividades as $actividad) {
			$json[] = array(
				'id' => $actividad['elemento'],
				'label' => '(' . $actividad['codigo'] . ')  ' . $actividad['nombre'],
				'escala_nota' => $actividad['escala_nota_examen']
			);
		}
		$this->render_raw_json($json);
	}

	function accion__buscar_docente()
	{
		$json = array();
		$docentes = catalogo::consultar('docente', 'get_docentes_actas_fuera_calendario', array('term' => $this->getAutocompleteTerm(), 'docente' => null));
		foreach ($docentes as $docente) {
			$legajo = !empty($docente['legajo']) ? '(' . $docente['legajo'] . ')  ' : '';
			$json[] = array(
				'id' => $docente['docente'],
				'label' => $legajo . $docente['apellido_nombres'] . " - " . $docente['tipo_nro_documento']
			);
		}
		$this->render_raw_json($json);
	}

	function accion__agregar_docente()
	{
		$docente = $this->validate_param('docente', 'post', validador::TIPO_INT);
		//@todo VALIDAR QUE SEA UN docente VALIDO
		$datos = catalogo::consultar('docente', 'get_docentes_actas_fuera_calendario', array('docente' => $docente, 'term' => null));

		$json = array();
		$json[] = array(
			'docente' => $docente,
			'legajo' => $datos['legajo'],
			'apellido_nombre' => $datos['apellido_nombres'],
			'res' => '1'
		);
		$this->render_raw_json($json);
	}
	
	function accion__mostrar_nro_acta()
	{
		$elemento = $this->validate_param('elemento', 'post', validador::TIPO_INT);
		$propuestas = $this->get_propuestas_actividad($elemento);
		$json = array(
			'mostrar_nro_acta' => parametro::get_valor('exa_carga_manual_nro_acta_examen', $propuestas)
		);
		$this->render_raw_json($json);
	}
	
	function accion__det_instancia_automatica()
	{
		$alumno = $this->validate_param('alumno', 'post', validador::TIPO_INT);
		$datos_alumno = catalogo::consultar('alumno', 'get_datos', array('alumno' => $alumno));
		$propuesta = $datos_alumno['propuesta'];
		$json = array(
			'instancia_automatica' => parametro::get_valor('exa_instancia_automatica', $propuesta)
		);
		$this->render_raw_json($json);
	}

	function accion__buscar_alumnos()
	{
		$hash = $this->validate_param('hash', 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
		$id_acta = null;
		if (!empty($hash)) {
			$params = $this->decodificar_hash($hash, 'siguiente');
			$id_acta = $params['id_acta'];
			$actividad = $params['actividad'];
			$evaluacion = $params['evaluacion'];
		}

		$json = array();
		if ($id_acta) {
			$alumnos = catalogo::consultar('alumno', 'get_alumnos_actas_fuera_calendario', 
                            [
                                'term' => $this->getAutocompleteTerm(),
                                'alumno' => null,
                                'elemento' => $actividad,
                                'evaluacion' => $evaluacion
                            ]
                        );
			foreach ($alumnos as $alumno) {
                                $legajo = !empty($alumno['legajo']) ? "(Legajo: {$alumno['legajo']})" : '';
				$json[] = array(
					'id' => $alumno['alumno'],
                                        'label' => "{$alumno['apellido_nombres_elegido']} ({$alumno['tipo_nro_documento']}) - {$alumno['propuesta_nombre_abreviado']} - {$legajo}",
				);
			}
		}
		$this->render_raw_json($json);
	}

	protected function validar_alumno($datos_alumno, $datos_acta)
	{
		try {
			$this->modelo()->validar_alumno($datos_alumno, $datos_acta);
		} catch (error_guarani $e) {
			$this->finalizar_request_con_notificaciones($e->get_mensaje(), $e->get_mensajes_usuario());
		}
	}

	function accion__agregar_alumno()
	{
		$alumno = $this->validate_param('alumno', 'post', validador::TIPO_INT);
		$datos_alumno = catalogo::consultar('alumno', 'get_datos', array('alumno' => $alumno));

		$hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
		$params = $this->decodificar_hash($hash, 'siguiente');

		$this->validar_alumno($datos_alumno, $params);
		
		$propuesta = $datos_alumno['propuesta'];
		$instancia_automatica = parametro::get_valor('exa_instancia_automatica', $propuesta);
		
		if ($instancia_automatica == 'N') {
			// Instancia seleccionada manualmente
			$instancia = $this->validate_param('instancia', 'post', validador::TIPO_INT);
			$instancias_examenes = catalogo::consultar('acta', 'get_instancias_examen', array('instancia' => $instancia));
			$nombre_instancia = $instancias_examenes['instancia_nombre'];
			
			try {
				list($resultado_controles_instancia, $mensajes_controles_instancia) = $this->modelo()->control__seleccion_instancia($params['actividad'], $params['llamado_mesa'], $instancia, $datos_alumno['alumno'], $datos_alumno['plan_version']);
			} catch (error_guarani $e) {
				$resultado_controles_instancia = \guarani_punto_de_control::rechazado;
				$mensajes_controles_instancia = $e->get_mensajes_usuario();
			}

			try {
				list($resultado_controles, $mensajes_controles) = $this->modelo()->control__agregar_alumno($datos_alumno['persona'], $datos_alumno['alumno'], $datos_alumno['propuesta'], $params['actividad'], $datos_alumno['plan_version'], $params['llamado_mesa']);
				$hubo_advertencias = $resultado_controles == \guarani_punto_de_control::advertido || $resultado_controles_instancia !== \guarani_punto_de_control::autorizado;
				if (!$hubo_advertencias) {
					$json = array(
						'persona' => $datos_alumno['persona'],
						'alumno' => $alumno,
						'legajo' => $datos_alumno['legajo'],
						'apellido_nombre' => $datos_alumno['apellido_nombres'],
						'instancia' => $instancia,
						'instancia_nombre' => $nombre_instancia,
						'identificacion' => $datos_alumno['tipo_nro_documento'],
						'propuesta' => $datos_alumno['propuesta_nombre_abreviado'],
						'plan_version' => $datos_alumno['plan_version'],
						'res' => '1'
					);
					$this->render_json($json);
				} else {
					$e = new error_guarani(kernel::traductor()->trans('error_ejecutando_controles'));
					$e->add_mensajes_usuario($mensajes_controles);
					throw $e;
				}
			} catch (error_guarani $e) {
				$mensajes_controles = array_merge($mensajes_controles_instancia, $e->get_mensajes_usuario());
				$this->finalizar_request_con_notificaciones($e->get_mensaje(), $mensajes_controles);
			}	
		} else {
			// Instancia automática
			try {
				list($instancia, $mensajes_controles_instancia, $resultado_controles_instancia) = $this->modelo()->determinar_instancia($params['actividad'], $params['llamado_mesa'], $datos_alumno['alumno'], $datos_alumno['plan_version']);
			} catch (error_guarani $e) {
				$resultado_controles_instancia = \guarani_punto_de_control::rechazado;
				$mensajes_controles_instancia = $e->get_mensajes_usuario();
			}

			try {
				list($resultado_controles, $mensajes_controles) = $this->modelo()->control__agregar_alumno($datos_alumno['persona'], $datos_alumno['alumno'], $datos_alumno['propuesta'], $params['actividad'], $datos_alumno['plan_version'], $params['llamado_mesa']);
				$hubo_advertencias = $resultado_controles == \guarani_punto_de_control::advertido || $resultado_controles_instancia !== \guarani_punto_de_control::autorizado;
				if (!$hubo_advertencias) {
					$instancias_examenes = catalogo::consultar('acta', 'get_instancias_examen', array('instancia' => $instancia));
					$nombre_instancia = $instancias_examenes['instancia_nombre'];

					$json = array(
						'persona' => $datos_alumno['persona'],
						'alumno' => $alumno,
						'legajo' => $datos_alumno['legajo'],
						'apellido_nombre' => $datos_alumno['apellido_nombres'],
						'instancia' => $instancia,
						'instancia_nombre' => $nombre_instancia,
						'identificacion' => $datos_alumno['tipo_nro_documento'],
						'propuesta' => $datos_alumno['propuesta_nombre_abreviado'],
						'plan_version' => $datos_alumno['plan_version'],
						'res' => '1'
					);
					$this->render_json($json);
				} else {
					$e = new error_guarani(kernel::traductor()->trans('error_ejecutando_controles'));
					$e->add_mensajes_usuario($mensajes_controles);
					throw $e;
				}
			} catch (error_guarani $e) {
				$mensajes_controles = array_merge($mensajes_controles_instancia, $e->get_mensajes_usuario());
				$this->finalizar_request_con_notificaciones($e->get_mensaje(), $mensajes_controles);
			}
		}
	}

	/**
	 * @todo: unificar con la de arriba
	 * @throws error_guarani
	 */
	function accion__agregar_alumno_instancia()
	{
		$alumno = $this->validate_param('alumno', 'post', validador::TIPO_INT);
		$instancia = $this->validate_param('instancia', 'post', validador::TIPO_INT);
		$datos_alumno = catalogo::consultar('alumno', 'get_datos', array('alumno' => $alumno));
		$hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
		$params = $this->decodificar_hash($hash, 'siguiente');
		$this->validar_alumno($datos_alumno, $params);
		$instancias_examenes = catalogo::consultar('acta', 'get_instancias_examen', array('instancia' => $instancia));
		$nombre_instancia = $instancias_examenes['instancia_nombre'];

		try {
			list($resultado_controles_instancia, $mensajes_controles_instancia) = $this->modelo()->control__seleccion_instancia($params['actividad'], $params['llamado_mesa'], $instancia, $datos_alumno['alumno'], $datos_alumno['plan_version']);
		} catch (error_guarani $e) {
			$resultado_controles_instancia = \guarani_punto_de_control::rechazado;
			$mensajes_controles_instancia = $e->get_mensajes_usuario();
		}

		try {
			list($resultado_controles, $mensajes_controles) = $this->modelo()->control__agregar_alumno($datos_alumno['persona'], $datos_alumno['alumno'], $datos_alumno['propuesta'], $params['actividad'], $datos_alumno['plan_version'], $params['llamado_mesa']);
			$hubo_advertencias = $resultado_controles == \guarani_punto_de_control::advertido || $resultado_controles_instancia !== \guarani_punto_de_control::autorizado;
			if (!$hubo_advertencias) {
				$json = array(
					'persona' => $datos_alumno['persona'],
					'alumno' => $alumno,
					'legajo' => $datos_alumno['legajo'],
					'apellido_nombre' => $datos_alumno['apellido_nombres'],
					'instancia' => $instancia,
					'instancia_nombre' => $nombre_instancia,
					'identificacion' => $datos_alumno['tipo_nro_documento'],
					'propuesta' => $datos_alumno['propuesta_nombre_abreviado'],
					'res' => '1'
				);
				$this->render_json($json);
			} else {
				$e = new error_guarani(kernel::traductor()->trans('error_ejecutando_controles'));
				$e->add_mensajes_usuario($mensajes_controles);
				throw $e;
			}
		} catch (error_guarani $e) {
			$mensajes_controles = array_merge($mensajes_controles_instancia, $e->get_mensajes_usuario());
			$this->finalizar_request_con_notificaciones($e->get_mensaje(), $mensajes_controles);
		}
	}

	function accion__guardar_detalle()
	{
		if (!kernel::request()->isPost()) {
			return;
		}
		$this->load_params('siguiente');
		$es_nuevo = $this->validate_param('n', 'get', validador::TIPO_INT);
		$pagelet = $this->vista()->pagelet('alumnos');
		$form = $pagelet->get_form();
		if ($form->procesar()) {
			try {
				$datos = $form->get_datos();
				// decodificar
				$params = $this->params;
				if (!empty($params)) {
					$datos['llamado_mesa'] = $params['llamado_mesa'];
					$datos['mesa_examen'] = $params['mesa_examen'];
					$datos['id_acta'] = $params['id_acta'];
					$datos['fecha'] = $params['fecha'];
					$datos['evaluacion'] = $params['evaluacion'];
					$datos['actividad'] = $params['actividad'];
				}

				if (!empty($datos['alumnos'])) {
					$this->modelo()->editar_eval_detalle_examenes($datos);
				}

				$mesa = array('mesa_examen' => $datos['mesa_examen']);
				$datos = $this->codificador_modelo()->info__link_mesa($mesa);
				if ($es_nuevo) {
					$datos->link('LINK', 'notas_mesa_examen', 'edicion');
				} else {
					$datos->link('LINK', 'impresion_actas_examen', 'index');
				}

				// En el caso que el docente se elimine de la lista de docentes, no se genera link por lo tanto se devuelve link_vacio
				$link = $datos[0]['LINK'];
				if (!$link || $link == DecoradorEntidad::$link_vacio) {
					$pagelet->set_estado_info(pagelet_alumnos::FORM_GUARDADO);
					$volver_inicio = kernel::vinculador()->crear('zona_examenes');
					kernel::renderer()->add_to_ajax_response('no_tiene_derechos', true);
					kernel::renderer()->add_to_ajax_response('url_no_drechos', $volver_inicio);
					$mensajes = array();
					$mensajes[] = guarani::crear_mensaje(guarani::control_advertencia, kernel::traductor()->trans('admin_actas_examen_fc.no_tiene_derechos'));
					$titulo = kernel::traductor()->trans('admin_actas_examen_fc.no_tiene_derechos_titulo');
					kernel::renderer()->set_popup_notificaciones($titulo, $mensajes);
				} else {
					$this->nuevo_request_url($link, kernel::traductor()->trans('admin_actas_examen_fc.guardado_datos_exitoso'));
					$pagelet->set_estado_info(pagelet_alumnos::FORM_GUARDADO);
				}
			} catch (error_kernel $e) {
				$pagelet->set_estado_info(pagelet_alumnos::FORM_ERROR_VALIDACION);
			}
		} else {
			$pagelet->set_estado_info(pagelet_alumnos::FORM_ERROR_VALIDACION);
		}
		kernel::renderer()->add($pagelet);
	}

	function notif($titulo, $not)
	{
		kernel::renderer()->set_popup_notificaciones($titulo, $not);
	}

	function generar_link_siguiente($id_acta, $nuevo)
	{
		/* @var $datos \SIU\Chulupi\util\modelo\ProcesadorVista */
		$acta = array('acta' => $id_acta);
		$datos = $this->codificador_modelo()->info__link_acta($acta);
		$datos->link('link_nueva_acta', 'admin_actas_examen_fc', 'siguiente', array('n' => $nuevo));
		return $datos[0]['link_nueva_acta'];
	}

	function get_hash($id_acta)
	{
		// Se recupera el hash para guardar en la pila del history y que al apretar 
		// volver encuentre este hash en la url, por eso se genera para index
		$acta = array('acta' => $id_acta);
		$datos = $this->codificador_modelo()->info__link_acta($acta);
		$datos->link('link_nueva_acta', 'admin_actas_examen_fc', 'index');
		$fila = $datos->recuperar_fila("id_acta", $id_acta);
		return $fila['__ID__'];
	}

	protected function get_datos_sesion($id_acta_sesion)
	{
		if (!kernel::sesion()->esta_seteada($id_acta_sesion)) {
			throw new error_guarani_seguridad('el id del acta no existe');
		}
		return kernel::sesion()->get($id_acta_sesion);
	}

	function descripcion_actividad($actividad)
	{
		$datos_actividad = catalogo::consultar('unidad_academica', 'datos_actividad', array('actividad' => $actividad));
		return '(' . $datos_actividad['codigo'] . ')  ' . $datos_actividad['nombre'];
	}

	function accion__detalle_escala()
	{
		$id_escala = $this->validate_param('id_escala', 'get', validador::TIPO_ALPHANUM);
		$escala_notas = guarani::carga_notas_mesa_examen()->info__escala($id_escala);
		$this->render_raw_json($escala_notas);
	}

	function get_datos_completos_acta($id_acta)
	{
		$datos = array();
		if (!empty($id_acta)) {
			$datos['datos_acta'] = $this->get_datos_acta($id_acta);
			$datos['instancias'] = $this->modelo()->info__acta_instancias($id_acta);
			$datos['docentes'] = $this->modelo()->info__acta_docentes($id_acta);
			$datos['alumnos'] = $this->modelo()->info__acta_alumnos($id_acta);
		}
		return $datos;
	}

	function get_datos_acta($id_acta)
	{
		if (!empty($id_acta)) {
			$datos = $this->modelo()->get_datos_acta($id_acta);
			$datos['actividad_nombre'] = $this->descripcion_actividad($datos['actividad']);
			return $datos;
		}
	}

	function get_id_actividad($sesid_acta = null)
	{
		if (!empty($sesid_acta)) {
			$datos_sesion = $this->get_datos_sesion($sesid_acta);

			if (!empty($datos_sesion['datos_acta']['actividad'])) {
				return $datos_sesion['datos_acta']['actividad'];
			}

			if (!empty($datos_sesion['id_acta'])) {
				$datos_acta = $this->get_datos_acta($datos_sesion['id_acta']);
				return $datos_acta['actividad'];
			}
		}
		return false;
	}

	/**
	 * Realiza validaciones de los datos de acta. En caso de que para el caso del 
	 * acta a procesar, el parámetro 'exa_carga_manual_nro_acta_examen' indique 'N',
	 * el campo 'nro_acta' se fuerza al valor 'null'
	 * 
	 * @param array $datos
	 * @return array
	 */
	function validar_datos_acta($datos)
	{
		kernel::log()->add_debug('########  VALIDANDO DATOS DEL ACTA  ######', $datos);
		$datos['datos_acta'] = $this->validar_encabezado($datos['datos_acta']);
		$this->validar_instancias($datos['instancias']);
		$this->validar_docentes($datos['docentes']);
		return $datos;
	}

	function validar_encabezado($datos)
	{
		if (empty($this->id_acta)) {
			// Caso: alta de acta
			$propuestas = $this->get_propuestas_actividad($datos['actividad']);
			$mostrar_nro_acta = parametro::get_valor('exa_carga_manual_nro_acta_examen', $propuestas);
			if ($mostrar_nro_acta == 'S') {
				if (empty($datos['nro_acta'])) {
					// Error
					throw new error_guarani_nro_acta_fc('Nro. Acta es obligatorio');
				}
			} else {
				// Si la generación de número de acta es automática, se deja 
				$datos['nro_acta'] = null;
			}
		}
		return $datos;
	}

	function validar_instancias($datos)
	{
		
	}

	function validar_docentes($datos)
	{
		
	}

	protected function load_params($alias = null)
	{
		$this->hash = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$this->params = $this->decodificar_hash($this->hash, $alias);
		$this->id_acta = $this->params['id_acta'];
		$this->add_var('id_acta', $this->id_acta);
		$this->add_var('hash', $this->hash);
	}

	protected function get_propuestas_actividad($elemento)
	{
		$datos = \toba::consulta_php('co_propuestas')->get_propuestas_por_elemento_para_examen($elemento);
		$salida = array();
		foreach ($datos as $dato) {
			if (!in_array($dato['propuesta'], $salida)) {
				$salida[] = $dato['propuesta'];
			}
		}
		return $salida;
	}
	
}