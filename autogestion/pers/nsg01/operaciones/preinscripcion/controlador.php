<?php

namespace nsg01\operaciones\preinscripcion;

use IntlBreakIterator;
use nsg01\modelo\facturacion\nsg_facturacion;
use kernel\error_kernel;
use SIU\Chulupi\util\validador;
use SIU\Chulupi\kernel;
use siu\guarani;
use siu\extension_kernel\controlador_g3w2;
use siu\operaciones\datos_censales\_comun\pagelet_con_edicion;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\util\ManejadorArchivos;
use siu\modelo\_comun\clienteRDI;
use siu\modelo\transacciones\solicitar_certificacion;
use SIU\Chulupi\nucleo\FinalizadorRequest;
use PlusPagos\AESEncrypter;
use PlusPagos\SHA256Encript;
use nsg01\modelo\facturacion\nsg_g3w3pre_facturacion;

class controlador extends controlador_g3w2
{
	protected $mcp_comercio;
	protected $mcp_key;
	protected $datos_preinscripciones;
	/**
	 * Ventana que se ejecuta antes de ejecutar una acci�n
	 */

	protected function pre_accion()
	{
		parent::pre_accion();

		try {
			$accion = kernel::ruteador()->get_accion();
			kernel::log()->add_debug("=== pre_accion - accion ===", $accion);

			$acciones_requisito_estado_presentacion_digital = [
				'descargar_documentacion',
				'modificar_documentacion',
				'eliminar_documentacion',
				'guardar_documentacion'
			];
			if (in_array($accion, $acciones_requisito_estado_presentacion_digital)) {
				// Controlo las acciones para el "Estado presentaci�n digital"
				$this->controlar_requisito_estado_presentacion_digital();
			}
		} catch (\Exception $e) {
			// Redirijo para mostrar error
			kernel::sesion()->set('tipo_mensaje', 'alert-error');
			kernel::sesion()->set('mensaje', $e->getMessage());
			$operacion = kernel::ruteador()->get_id_operacion();
			// Redirijo a "Requisitos de ingreso"
			$this->nuevo_request($operacion, 'requisitos');
		}
	}

	// function accion__hook()
	// {
	//     // 1) Solo POST
	//     if (!kernel::request()->isPost()) {
	//         http_response_code(405);
	//         header('Allow: POST');
	//         echo 'Method Not Allowed';
	//         return;
	//     }

	//     // 2) Leer cuerpo
	//     $raw = file_get_contents('php://input') ?: '';
	//     $sig = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
	//     $fromHdr = $_SERVER['HTTP_X_FROM'] ?? '';

	//     // 3) Validar firma HMAC
	//     $shared_secret = 'CAMBIA-ESTO-POR-UN-SECRETO-LARGO-Y-ROTADO'; // MISMO QUE EN EL EMISOR
	//     $calc = hash_hmac('sha256', $raw, $shared_secret);

	//     // Comparación segura (tiempo constante)
	//     if (!hash_equals($calc, $sig)) {
	//         http_response_code(401);
	//         kernel::log()->add_error("hook: firma inválida. from={$fromHdr}");
	//         echo 'Unauthorized';
	//         return;
	//     }

	//     // 4) (Opcional) Whitelist de IPs
	//     $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	//     $allowed = ['AAA.BBB.CCC.DDD', 'EEE.FFF.GGG.HHH']; // si querés
	//     if (!empty($allowed) && !in_array($remoteIp, $allowed, true)) {
	//         http_response_code(403);
	//         kernel::log()->add_error("hook: IP no permitida {$remoteIp}");
	//         echo 'Forbidden';
	//         return;
	//     }

	//     // 5) Decodificar JSON
	//     $data = json_decode($raw, true);
	//     if (!is_array($data)) {
	//         http_response_code(400);
	//         echo 'Bad Request';
	//         return;
	//     }

	//     // 6) Procesar según la plataforma de origen
	//     try {
	//         $source = $data['source'] ?? 'unknown';
	//         $payload = $data['data'] ?? [];

	//         // TODO: aplicá tu lógica de negocio.
	//         // Por ejemplo, actualizar estado de una preinscripción luego de pago:
	//         // $this->modelo()->finalizar_propuesta_preinsc($id);
	//         // O encolar/registrar auditoría: kernel::log()->add_debug('hook payload', $data);

	//         kernel::log()->add_debug("hook OK source={$source}", $data);

	//         // 7) Responder sin cuerpo
	//         http_response_code(204);
	//     } catch (\Throwable $e) {
	//         kernel::log()->add_error($e);
	//         http_response_code(500);
	//         echo 'Internal Error';
	//     }
	// }



	function controlar_requisito_estado_presentacion_digital()
	{
		$id_hash = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$requisito = $this->buscar_requisito($id_hash);

		// Si el requisito no existe
		if (is_null($requisito)) {
			throw new \Exception(kernel::traductor()->trans('requisitos.error_existe_requisto'));
		}

		$error = false;
		$accion = kernel::ruteador()->get_accion();

		// Si "Estado presentaci�n digital" es "-- Pendiente de validaci�n --"
		if ($requisito['estado_req_digital'] == solicitar_certificacion::estado_pendiente_validacion) {
			$acciones_permitidas = [
				'descargar_documentacion',
				'modificar_documentacion',
				'eliminar_documentacion',
				'guardar_documentacion'
			];
			if (!in_array($accion, $acciones_permitidas)) {
				$error = true;
			}
		}

		// Si "Estado presentaci�n digital" es "-- No cargado --"
		if ($requisito['estado_req_digital'] == solicitar_certificacion::estado_no_presentado) {
			$acciones_permitidas = [
				'guardar_documentacion'
			];
			if (!in_array($accion, $acciones_permitidas)) {
				$error = true;
			}
		}

		// Si "Estado presentaci�n digital" es "-- Presentado --"
		if ($requisito['estado_req_digital'] == solicitar_certificacion::estado_presentado) {
			$acciones_permitidas = [
				'descargar_documentacion'
			];
			if (!in_array($accion, $acciones_permitidas)) {
				$error = true;
			}
		}

		// Si "Estado presentaci�n digital" es "-- Vencido --"
		if ($requisito['estado_req_digital'] == solicitar_certificacion::estado_vencido) {
			$acciones_permitidas = [
				'descargar_documentacion',
				'modificar_documentacion',
				'guardar_documentacion'
			];
			if (!in_array($accion, $acciones_permitidas)) {
				$error = true;
			}
		}

		// Si fall� el control
		if ($error) {
			throw new \Exception(kernel::traductor()->trans('requisitos.msj_error_generico'));
		}
	}

	function modelo()
	{
		return guarani::preinscripcion();
	}

	function accion__index()
	{
		$formulario = $this->load_form_activo();
		kernel::renderer()->add($formulario);
	}

	protected function load_form_activo()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array(
			'default' => 'propuesta'
		));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);
		return $pagelet;
	}

	function accion__guardar_propuesta()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$pagelet = $this->vista()->pagelet($formulario);
		$form = $pagelet->get_form_propuesta();
		if ($form->procesar()) {
			$datos = $form->get_datos();
			$this->modelo()->agregar_propuesta_preinsc($datos);

			$turnos = $this->modelo()->get_listado_turnos();
			// Si no hay turnos de presentaci�n redirijo a la solapa 'Preinscripciones'
			if (empty($turnos)) {
				$this->nuevo_request('preinscripcion', 'propuesta');
			} else { // Si hay turnos de presentaci�n redirijo a la solapa 'Turnos'
				$this->accion__seleccionar_turno();
				$this->nuevo_request('preinscripcion', 'turno_presentacion');
			}
		}
	}

	function accion__eliminar_preinscripcion()
	{
		$id = $this->get_param('id', 'post');
		//kernel::log()->add_debug("Eliminar preinscripcion id", $id);

		$this->modelo()->eliminar_propuesta_preinsc($id);
	}

	function accion__cambiar_estado_preinscripcion()
	{
		$preinsc = $this->get_param('preinsc', 'post');	
		$re_abrir = $this->get_param('re_abrir', 'post');	
		if (!$re_abrir && !$this->puede_finalizar_preinscripcion($preinsc)) {
			$msj = kernel::traductor()->trans('preinscripcion.error_pago_incompleto');
			$this->render_raw_json(['estado' => 'error', 'mensaje' => $msj]);
			return;
		} else {

			$id = $this->get_param('id', 'post');
			$this->modelo()->finalizar_propuesta_preinsc($id);
			$pagelet = $this->vista()->pagelet('propuesta');
			$this->vista()->set_form_activo($pagelet);
			kernel::renderer()->add($pagelet);
		}
	}
	function get_preinscripcion($datos_preinscripciones, $preinsc)
	{
		foreach ($datos_preinscripciones as $p) {
			if ($p['preinscripcion'] == $preinsc) {
				return $p;
			}
		}
		return null;
	}
	function puede_finalizar_preinscripcion($preinscripcion)
	{
		// if (kernel::persona()->get_id() == 2) {
		// 	return true;
		// }

		$facturacion = new nsg_g3w3pre_facturacion(true);
		$propuestas_aux = $facturacion->get_propuestas_sin_arancel_insc();

		$datos_preinscripcion = $this->modelo()->get_datos_preinscripciones();
		$preinsc = $this->get_preinscripcion($datos_preinscripcion, $preinscripcion);
		if (in_array($preinsc['propuesta'], $propuestas_aux)) {
			return true;
		}

		$datos_pagos = $facturacion->get_referenciasPagos([
			'preinscripcion' => $preinscripcion
		]);
		
		$estado_pago = $facturacion->get_estado_pago($datos_pagos, $preinscripcion);
		return ($estado_pago[0] == 3); 
	}

	function accion__pagar_mcp()
	{
		$codfor = $this->get_param('codfor', 'post');
		$preinscripcion = $this->get_param('id', 'post');
		$nrofor = $this->get_param('nrofor', 'post');

		$facturacion = new nsg_g3w3pre_facturacion(true);

		$trans_id = $facturacion->get_mcpTransId(['codfor' => $codfor, 'nrofor' => $nrofor]);

		//kernel::log()->add_debug("Pagar MCP - trans_id", $trans_id);
		
		$ret_value = [
			'trans_id' => $trans_id,
			'preinscripcion' => $preinscripcion
		];
		$this->render_raw_json($ret_value);
	}

	function accion__pagar_mp() {		
	}

	//--------------------------------------------------------------------------
	//---- CASCADAS: Selector de propuestas ------------------------------------
	//--------------------------------------------------------------------------

	function accion__cascada_propuestas()
	{
		$ra = $this->get_param('ra', 'post');
		$datos = $this->modelo()->get_propuestas_disponibles_preinscripcion($ra);
		$this->render_raw_json($datos);
	}

	function accion__cascada_ubicaciones()
	{
		$propuesta = $this->get_param('propuesta', 'post');
		$datos = $this->modelo()->get_ubicaciones_propuesta($propuesta);
		$this->render_raw_json($datos);
	}

	function accion__cascada_modalidades()
	{
		$propuesta = $this->get_param('propuesta', 'post');
		$ubicacion = $this->get_param('ubicacion', 'post');
		$datos = $this->modelo()->get_modalidades_propuesta($propuesta, $ubicacion);
		$this->render_raw_json($datos);
	}

	//--------------------------------------------------------------------------
	//---- Turno
	//--------------------------------------------------------------------------

	function accion__devolver_turno()
	{
		$ra = $this->decodificar_ra_turno();
		$this->modelo()->devolver_turno($ra);
		$pagelet = $this->vista()->pagelet('turno_presentacion');
		$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_LISTADO);
		$this->vista()->set_form_activo($pagelet);
		kernel::renderer()->add($pagelet);
	}

	function accion__seleccionar_turno()
	{
		$pagelet = $this->vista()->pagelet('turno_presentacion');
		$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_EDICION);
		$this->vista()->set_form_activo($pagelet);
		kernel::renderer()->add($pagelet);
	}

	function accion__volver_seleccionar_turno()
	{
		$pagelet = $this->vista()->pagelet('turno_presentacion');
		$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_LISTADO);
		$this->vista()->set_form_activo($pagelet);
		kernel::renderer()->add($pagelet);
	}

	function get_fecha_minima_turno()
	{
		$ra = $this->decodificar_ra_turno();
		return $this->modelo()->get_fecha_minima($ra);
	}

	function get_fecha_maxima_turno()
	{
		$ra = $this->decodificar_ra_turno();
		return $this->modelo()->get_fecha_maxima($ra);
	}

	function get_fechas_turnos_disponibles()
	{
		$ra = $this->decodificar_ra_turno();
		return $this->modelo()->get_fechas_turnos_disponibles($ra);
	}

	function accion__cascada_franjas_turnos()
	{
		$fecha = $this->get_param('fecha', 'post');
		$ra = $this->decodificar_ra_turno($this->get_param('ra', 'post'));
		$datos = $this->modelo()->get_franjas_horarias_disponibles($fecha, $ra);
		$this->render_raw_json($datos);
	}

	function get_ra_turno()
	{
		$ra = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		return $ra;
	}

	protected function decodificar_ra_turno($ra = null)
	{
		if (is_null($ra)) {
			$ra = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		}
		$datos = $this->modelo()->get_responsables_academicas();
		foreach ($datos as $key => $dato) {
			if ($ra == catalogo::generar_id($key)) {
				return $key;
			}
		}
		return null;
	}

	function accion__guardar_turno()
	{
		if (!kernel::request()->isPost()) {
			return false;
		}

		$pagelet = $this->vista()->pagelet('turno_presentacion');
		$form = $pagelet->get_form();

		if ($form->procesar()) {
			$ra = $this->decodificar_ra_turno();
			$datos = $form->get_datos();
			try {

				if ($this->modelo()->reservar_turno($ra, $datos['fecha'], $datos['franja'])) {
					//Dejo el comentario por si en un futuro queremos mostrar el turo asignado								
					// $pagelet->set_estado_info(pagelet_con_edicion::FORM_GUARDADO);
					// $this->vista()->set_form_activo($pagelet);
					// $pagelet->set_pantalla(pagelet_con_edicion::ESTADO_LISTADO);
					$this->nuevo_request('preinscripcion', 'turno_presentacion');
				} else {
					$pagelet->set_estado_info(pagelet_con_edicion::FORM_ERROR_VALIDACION);
					$this->vista()->set_form_activo($pagelet);
					$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_EDICION);
				}
			} catch (error_kernel $e) {
				$pagelet->set_mensaje_error($e->get_mensaje());
				$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_EDICION);
			}
		} else {
			$pagelet->set_estado_info(pagelet_con_edicion::FORM_ERROR_VALIDACION);
			$this->vista()->set_form_activo($pagelet);
		}
		kernel::renderer()->add($pagelet);
	}

	//--------------------------------------------------------------------------
	//---- RDI - Documentaci�n -------------------------------------------------
	//--------------------------------------------------------------------------
	function accion__doc_digital()
	{
		$id_hash = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$pagelet = $this->vista()->vista_doc_digital();
		$requisito = $this->buscar_requisito($id_hash);
		$pagelet->set_requisito($requisito);
	}

	function buscar_requisito($id_hash)
	{

		$preinscripciones = catalogo::consultar('preinscripcion', 'get_propuestas_preinscripcion', array('persona' => kernel::persona()->get_id()));
		foreach ($preinscripciones as $preinscripcion) {
			$parametros = array(
				'persona' => kernel::persona()->get_id(),
				'propuesta' => $preinscripcion['propuesta'],
				'estado_req_digital' => null
			);
			$requisitos = catalogo::consultar('preinscripcion', 'get_requisitos_persona_propuesta', $parametros);
			foreach ($requisitos as $requisito) {
				if ($requisito[catalogo::id] == $id_hash) {
					return $requisito;
				}
			}
		}
		die();
	}

	function extensiones_permitidas()
	{
		return \toba::consulta_php('co_configuracion', true)->get_rdi_extensiones_permitidas();
	}

	function accion__guardar_documentacion()
	{
		$id_hash = $this->validate_param(0, 'get', validador::TIPO_TEXTO);

		$pagelet = $this->vista()->vista_doc_digital();


		$requisito = $this->buscar_requisito($id_hash);

		$pagelet->set_requisito($requisito);

		$indice_form = 'archivo';

		if ($_FILES[$indice_form]['error'] == UPLOAD_ERR_INI_SIZE || $_FILES[$indice_form]['error'] == UPLOAD_ERR_FORM_SIZE) {
			kernel::sesion()->set('error_tamanio', true);
		}

		$hubo_error = false;
		$msg_error = '';

		try {

			kernel::db()->abrir_transaccion();

			$vencido = $this->validate_param('vencido', 'post', validador::TIPO_INT);
			$es_modificacion = $this->validate_param('es_modificacion', 'post', validador::TIPO_INT);

			// Valido el documento subido
			list($hubo_error_documento_subido, $msg_error_documento_subido) = clienteRDI::validar_documento_subido($indice_form);

			// Si hubo error en la validaci�n del documento subido
			if ($hubo_error_documento_subido) {
				throw new \Exception($msg_error_documento_subido);
			}

			$path_file = clienteRDI::guardar_archivos_subidos($indice_form);

			if (!empty($path_file)) {

				$tipo_documento_digital = $requisito['tipo_documento_digital'];

				// Si el requisito est� vencido se carga como "-- Pendiente de validaci�n --"
				if ($vencido) {
					$requisito['id_documento'] = null;
				}

				// Crea o modifica el recurso en el RDI
				$id_recurso = clienteRDI::enviar_repositorio($path_file, $requisito['tipo_documento_digital'], $requisito['id_documento']);

				// Si se crea nuevo guardo en la DB
				if (is_null($requisito['id_documento'])) {
					$parametros = [
						'persona' => kernel::persona()->get_id(),
						'requisito' => $requisito['requisito'],
						'id_documento' => $id_recurso
					];
					catalogo::consultar('alumno', 'guardar_requisito_draft', $parametros);
				} else {
					$parametros = [
						'claves' => ['requisito_digital' => $requisito['requisito_digital']],
						'datos' => ['fecha_carga' => 'CURRENT_TIMESTAMP']
					];

					// Pongo 'fecha_carga' a la fecha actual
					catalogo::consultar('alumno', 'actualizar_requisito_draft', $parametros);
				}

				$parametros = [
					'persona' => kernel::persona()->get_id(),
					'requisito' => $requisito['requisito']
				];
				// Elimino las observaciones del draft
				catalogo::consultar('alumno', 'eliminar_sga_requisitos_digitales_draft_detalle', $parametros);

				// Se resetea la cach� con los datos de  requisitos de la persona
				$this->limpiar_cache_consulta_requisitos();

				$this->accion__doc_digital();
			}
			kernel::db()->cerrar_transaccion();
		} catch (ErrorKernel $e) {
			$hubo_error = true;
			$msg_error = $e->getMessage();
			kernel::log()->add_error($e);
			kernel::db()->abortar_transaccion();
		} catch (RDIExcepcion $e) {
			$hubo_error = true;
			$msg_error = kernel::traductor()->trans('requisitos.msj_error_rdi');
			kernel::log()->add_error($e);
			kernel::db()->abortar_transaccion();
		} catch (\Exception $e) {
			$hubo_error = true;
			$msg_error = $e->getMessage();
			kernel::log()->add_error($e);
			kernel::db()->abortar_transaccion();
			// Entra ya sea que salga todo bien o falle
		} finally {

			// Elimino el archivo temporal
			if (file_exists($path_file)) {
				unlink($path_file);
			}

			// Si hubo error
			if ($hubo_error) {
				kernel::sesion()->set('tipo_mensaje', 'alert-error');
				kernel::sesion()->set('mensaje', $msg_error);
				kernel::sesion()->set('es_modificacion', $es_modificacion);
				// Si se guarda con �xito
			} else {
				kernel::sesion()->set('tipo_mensaje', 'alert-success');
				kernel::sesion()->set('mensaje', kernel::traductor()->trans('requisitos.msg_guardar_exito'));
			}

			$operacion = kernel::ruteador()->get_id_operacion();
			// Redirijo al requisito
			$url_redirect = kernel::vinculador()->crear($operacion, 'doc_digital', [$id_hash]);
			$this->nuevo_request_url($url_redirect);
		}
	}

	function accion__modificar_documentacion()
	{
		$id_hash = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$pagelet = $this->vista()->vista_doc_digital();
		$requisito = $this->buscar_requisito($id_hash);
		$pagelet->set_requisito($requisito);
		if (!empty($requisito['id_documento'])) {
			kernel::sesion()->set('es_modificacion', 1);
			kernel::sesion()->set('requisito_' . $requisito[catalogo::id], 1);
		}
	}

	function accion__descargar_documentacion()
	{

		$hubo_error = false;
		$msg_error = '';
		$pagelet = $this->vista()->vista_doc_digital();

		try {
			$id_hash = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
			$requisito = $this->buscar_requisito($id_hash);
			$pagelet->set_requisito($requisito);

			// Descargo el documento desde RDI
			$srv = kernel::proyecto()->rdi()->servicio($requisito['tipo_documento_digital']);
			$content = $srv->recuperarContenido($requisito['id_documento']);

			// Path de descarga
			$path = kernel::proyecto()->get_www_temp() . '/' . $requisito['id_documento'];
			file_put_contents($path, $content);

			// Nombre de descarga
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime_type = finfo_file($finfo, $path);
			finfo_close($finfo);
			$ext = ManejadorArchivos::mime2ext($mime_type);
			$nombre_descarga = "{$requisito['requisito_nombre']}.{$ext}";

			// Descargo el documento al browser
			$this->descargar_archivo($path, $nombre_descarga, true);
		} catch (\Exception $e) {

			// $this->descargar_archivo() lanza una excepci�n del tipo FinalizadorRequest
			if (!$e instanceof FinalizadorRequest) {
				kernel::log()->add_error($e);
				$hubo_error = true;
				$msg_error = kernel::traductor()->trans('requisitos.msj_error_rdi');
			}

			// Entra ya sea que salga todo bien o falle
		} finally {

			// Si hubo error
			if ($hubo_error) {
				kernel::sesion()->set('tipo_mensaje', 'alert-error');
				kernel::sesion()->set('mensaje', $msg_error);
				$operacion = kernel::ruteador()->get_id_operacion();
				// Redirijo al requisito
				$url_redirect = kernel::vinculador()->crear($operacion, 'doc_digital', [$id_hash]);
				$this->nuevo_request_url($url_redirect);
			}
		}
	}

	function accion__eliminar_documentacion()
	{

		$pagelet = $this->vista()->vista_doc_digital();

		$hubo_error = false;
		$msg_error = '';

		try {
			$id_hash = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
			$requisito = $this->buscar_requisito($id_hash);
			$pagelet->set_requisito($requisito);

			if (!empty($requisito['id_documento'])) {

				kernel::db()->abrir_transaccion();

				// Eliminar registro en BD
				$parametros = [
					'persona' => kernel::persona()->get_id(),
					'requisito' => $requisito['requisito'],
					'id_documento' => $requisito['id_documento']
				];
				catalogo::consultar('alumno', 'eliminar_requisito_draft', $parametros);

				unset($parametros['id_documento']);
				// Elimino las observaciones del draft
				catalogo::consultar('alumno', 'eliminar_sga_requisitos_digitales_draft_detalle', $parametros);

				// Eliminar en repositorio digital
				clienteRDI::eliminar_repositorio($requisito['tipo_documento_digital'], $requisito['id_documento']);

				kernel::db()->cerrar_transaccion();

				// Se resetea la cach� con los datos de  requisitos de la persona
				$this->limpiar_cache_consulta_requisitos();
				$this->accion__doc_digital();
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
			// Entra ya sea que salga todo bien o falle
		} finally {

			// Si hubo error
			if ($hubo_error) {
				kernel::sesion()->set('tipo_mensaje', 'alert-error');
				kernel::sesion()->set('mensaje', $msg_error);
				// Si se guarda con �xito
			} else {
				kernel::sesion()->set('tipo_mensaje', 'alert-success');
				kernel::sesion()->set('mensaje', kernel::traductor()->trans('requisitos.msg_guardar_exito'));
			}

			$operacion = kernel::ruteador()->get_id_operacion();
			// Redirijo al requisito
			$url_redirect = kernel::vinculador()->crear($operacion, 'doc_digital', [$id_hash]);
			$this->nuevo_request_url($url_redirect);
		}
	}

	function limpiar_cache_consulta_requisitos()
	{
		$preinscripciones = catalogo::consultar('preinscripcion', 'get_propuestas_preinscripcion', array('persona' => kernel::persona()->get_id()));
		foreach ($preinscripciones as $preinscripcion) {
			$parametros = array(
				'persona' => kernel::persona()->get_id(),
				'propuesta' => $preinscripcion['propuesta'],
				'estado_req_digital' => null
			);
			catalogo::limpiar_cache('preinscripcion', 'get_requisitos_persona_propuesta', $parametros);
		}
	}

	function accion__generar_pdf()
	{
		$persona = guarani::persona()->get_id();
		$pdf = new \guarani_ficha_preinscripcion($persona, '', \preinscripcion::origen_autogestion);
		$pdf->get_comprobante();
	}
}
