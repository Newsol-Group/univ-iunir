<?php

namespace nsg01\operaciones\solicitudes;

use SIU\Chulupi\ErrorKernelValidacion;
use \SIU\Chulupi\kernel;
use \SIU\Chulupi\util\validador;
use \siu\modelo\datos\catalogo;
use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use \siu\modelo_g3\constancia;
use siu\modelo\_comun\clienteRDI;
use RDITipos;

class controlador extends \siu\operaciones\solicitudes\controlador
{
	function guardar_constancia_documento($cid, $pid)
	{
		$path_file = '';
		$hubo_error = false;
		$msg_error = '';
		$constancia_documento_seq = -1;

		try {
			if ($_FILES['archivo']['error'] == UPLOAD_ERR_INI_SIZE || $_FILES['archivo']['error'] == UPLOAD_ERR_FORM_SIZE) {
				kernel::sesion()->set('error_tamanio', true);
				kernel::log()->add_debug("=== accion__guardar_documentacion ERROR ===", null);
			}

			list($hubo_error_documento_subido, $msg_error_documento_subido) = clienteRDI::validar_documento_subido('archivo');

			kernel::log()->add_debug("=== accion__guardar_documentacion ===", $msg_error_documento_subido);

			if ($hubo_error_documento_subido) {
				// throw new \Exception($msg_error_documento_subido);
				$hubo_error = true;
				$msg_error = $msg_error_documento_subido;
			} else {
				$path_file = clienteRDI::guardar_archivos_subidos('archivo');
				if (empty($path_file)) throw new \Exception(kernel::traductor()->trans('constancias_certificados.error_interno_guardar_archivo'));

				$did = clienteRDI::enviar_repositorio($path_file, RDITipos::RECURSOPERSONAL);
				if (empty($did)) throw new \Exception(kernel::traductor()->trans('constancias_certificados.error_interno_enviar_repositorio'));

				kernel::log()->add_debug("=== accion__guardar_documentacion did===", $did);

				\guarani::act('act_constancias')->insert_constancia_documento($pid, $cid, $did);
				$constancia_documento_seq = \toba::consulta_php('co_constancias')->get_constancia_documento_seq()['constancia_documento'];
			}
		} catch (RDIExcepcion $e) {
			$hubo_error = true;
			$msg_error = kernel::traductor()->trans('requisitos.msj_error_rdi');
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
		} catch (\Exception $e) {
			$hubo_error = true;
			$msg_error = $e->getMessage();
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
		} finally {
			if (!empty($path_file)) {
				unlink($path_file);
			}
			return ['hubo_error' => $hubo_error, 'msg_error' => $msg_error, 'constancia_documento_seq' => $constancia_documento_seq];
		}
	}

	function accion__nueva_solicitud()
	{

		$operacion = kernel::ruteador()->get_id_operacion();

		// Si no es POST es que se carga el formulario pero no se envi�
		if (!kernel::request()->isPost()) {

			$resultado = $this->modelo()->evt__controla_nueva_solicitud();
			$hay_error = $this->hay_error_controles($resultado);

			if ($hay_error) {
				kernel::sesion()->set('solicitud_mensajes', $resultado['mensajes']);
				// Redirijo a "Listado de solicitudes"
				$this->nuevo_request($operacion, 'index');
			}

			$this->vista()->pagelet('pagelet')->data['resultado'] = $resultado;
			return false;
		}

		$resultado = ['ok' => true, 'mensajes' => []];
		$campo_error = "";

		try {

			$campo_error = "Constancia";
			$constancia = $this->validate_param('constancia', 'post', validador::TIPO_INT);
			$campo_error = "Copias";
			$copias = $this->validate_param('copias', 'post', validador::TIPO_INT);
			$campo_error = "Presentar a";
			$presentar = $this->validate_param('presentar', 'post', validador::TIPO_TEXTO);
			$campo_error = "Observaciones";
			$observaciones = $this->validate_param('observaciones', 'post', validador::TIPO_TEXTO);

			$formulario = $this->validate_param('formulario', 'post', validador::TIPO_TEXTO);
			$formulario_campo = $this->validate_param('formulario_campo', 'post', validador::TIPO_TEXTO);
			$formulario_valor = $this->validate_param('formulario_valor', 'post', validador::TIPO_TEXTO);

			$campo_error = "Motivo Baja";
			$motivo_baja = $this->validate_param('motivo_baja', 'post', validador::TIPO_INT, array('default' => null));

			$campo_error = "Tipo Beneficio";
			$tipo_beneficio = $this->validate_param('tipo_beneficio', 'post', validador::TIPO_INT, array('default' => null));

			$campo_error = "Materia";
			$comision = $this->validate_param('comision', 'post', validador::TIPO_INT, array('default' => null));

			if (is_null($motivo_baja))
				$motivo_baja = 0;

			if (is_null($tipo_beneficio))
				$tipo_beneficio = 0;

			if (is_null($comision))
				$comision = 0;

			$this->modelo()->setConstancia($constancia);
			$resultado_controles = $this->modelo()->evt__controla_cant_max_solicitudes_aa();
			$hay_error = $this->hay_error_controles($resultado_controles);

			//valido si tiene formulario dentro del array el formulario_campo y formulario_valor no pueden ser vacios
			$array_formularios = ['34000236', '37000562', '14000079', '14000020', '14000017'];
			if (in_array($formulario, $array_formularios) && !empty($formulario_campo) && ($formulario_valor == 'no_hay_datos' || empty($formulario_valor))) {
				$hay_error = true;
				$resultado_controles['mensajes'][] = array('mtipo' => 'E', 'mcuerpo' => kernel::traductor()->trans('solicitud_constancias_faltantes_datos'));
			}

			if ($hay_error) {
				kernel::sesion()->set('solicitud_mensajes', $resultado_controles['mensajes']);
				// Redirijo a "Listado de solicitudes"
				$this->nuevo_request($operacion, 'index');
			}

			$req_doc_info = \toba::consulta_php('co_constancias')->get_constancia_requiere_documento_info(['constancia' => $constancia]);

			if ($req_doc_info['requiere_documento'] == 'S') {
				$pid = kernel::persona()->get_id();
				$guardar_doc_result = $this->guardar_constancia_documento($constancia, $pid);

				if (!$guardar_doc_result['hubo_error']) {
					$constancia_documento_seq = $guardar_doc_result['constancia_documento_seq'];
					$resultado = $this->modelo()->evt__nueva_solicitud($constancia, $copias, $presentar, $observaciones, $motivo_baja, $tipo_beneficio, $comision, $formulario, $formulario_campo, $formulario_valor, $req_doc_info, $constancia_documento_seq);
					$resultado['mensajes'] = array_merge($resultado_controles['mensajes'], $resultado['mensajes']);
				} else {
					$resultado['ok'] = false;
					$guardar_doc_result_msj['mensajes'][] = ['mtipo' => 'E', 'mcuerpo' => $guardar_doc_result['msg_error']];
					$resultado['mensajes'] = array_merge($resultado_controles['mensajes'], $guardar_doc_result_msj['mensajes']);
				}
			} else {
				$resultado = $this->modelo()->evt__nueva_solicitud($constancia, $copias, $presentar, $observaciones, $motivo_baja, $tipo_beneficio, $comision, $formulario, $formulario_campo, $formulario_valor, $req_doc_info, null);
				$resultado['mensajes'] = array_merge($resultado_controles['mensajes'], $resultado['mensajes']);
			}

			// $resultado = $this->modelo()->evt__nueva_solicitud($constancia, $copias, $presentar, $observaciones, $motivo_baja, $tipo_beneficio, $comision, $formulario, $formulario_campo, $formulario_valor);
			// $resultado['mensajes'] = array_merge($resultado_controles['mensajes'], $resultado['mensajes']);
	
		} catch (ErrorKernelValidacion $e) {

			$resultado['ok'] = false;
			$resultado['mensajes'][] = array('mtipo' => 'E', 'mcuerpo' => kernel::traductor()->trans('constancias.error_en_campo_x', array(
				'%campo%' => $campo_error
			)));
		}

		kernel::renderer()->add_to_ajax_response('ok', $resultado['ok']);

		// Si sali� todo OK
		if ($resultado['ok']) {
			kernel::sesion()->set('nueva_solicitud_ok', kernel::traductor()->trans('nueva_solicitud_ok'));
			// Redirijo a "Listado de solicitudes"
			$this->nuevo_request($operacion, 'index');
		}

		$this->vista()->pagelet('pagelet')->data['resultado'] = $resultado;
	}

	/**
	 * 23/12/2025
	 * Newsol - Joaquin
	 * get_constancias
	 * Modificacion para obtener si las constancias requieren documento o no
	 */
	function get_constancias()
	{
		$constancias = [];
		$propuesta = kernel::persona()->get_id_propuesta_activa();
		$constancias_activas = \toba::consulta_php('co_constancias')->get_constancias_activas_autogestion($propuesta);

		foreach ($constancias_activas as $const) {
			$constancias[$const['constancia']]['id'] = $const['constancia'];
			$constancias[$const['constancia']]['nombre'] = $const['nombre'];
			$constancias[$const['constancia']]['formulario'] = $const['objeto'];
			$constancias[$const['constancia']]['req_doc'] = $const['requiere_documento'];
			$constancias[$const['constancia']]['req_doc_desc'] = $const['requiere_documento_desc'];
		}

		return $constancias;
	}


	/**
	 * 23/12/2025
	 * Newsol - Joaquin
	 * accion__nueva_solicitud
	 * Modificacion para guardar documento si la constancia lo requiere
	 */
	function accion__get_info_solicitud()
	{
		$const = kernel::request()->getParam('constancia');
		$req_doc_info = \toba::consulta_php('co_constancias')->get_constancia_requiere_documento_info(['constancia' => $const]);
		$this->render_raw_json(array(
			'cod' => 1,
			'copias' => catalogo::consultar('solicitudes', 'get_max_cant_copias', array(
				'constancia' => $const
			)),
			'req_doc' => $req_doc_info['requiere_documento'],
			'req_doc_desc' => $req_doc_info['requiere_documento_desc']
		));
	}
}
