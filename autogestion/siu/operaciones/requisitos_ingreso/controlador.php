<?php

namespace siu\operaciones\requisitos_ingreso;

use SIU\Chulupi\util\validador;
use SIU\Chulupi\kernel;
use siu\operaciones\_comun\operaciones\reporte\controlador as controlador_reporte;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\util\ManejadorArchivos;
use siu\modelo\transacciones\solicitar_certificacion;
use siu\modelo\_comun\clienteRDI;
use SIU\Chulupi\nucleo\FinalizadorRequest;

class controlador extends controlador_reporte
{
	protected $requisito;

	/**
	 * Ventana que se ejecuta antes de ejecutar una acción
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
				// Controlo las acciones para el "Estado presentación digital"
				$this->controlar_requisito_estado_presentacion_digital();
			}

		} catch (\Exception $e) {
			// Redirijo para mostrar error
			kernel::sesion()->set('tipo_mensaje', 'alert-error');
			kernel::sesion()->set('mensaje', $e->getMessage());
			$operacion = kernel::ruteador()->get_id_operacion();
			// Redirijo a "Requisitos de ingreso"
			$this->nuevo_request($operacion, 'index');
		}

	}

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

		// Si "Estado presentación digital" es "-- Pendiente de validación --"
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

		// Si "Estado presentación digital" es "-- No cargado --"
		if ($requisito['estado_req_digital'] == solicitar_certificacion::estado_no_presentado) {
			$acciones_permitidas = [
				'guardar_documentacion'
			];
			if (!in_array($accion, $acciones_permitidas)) {
				$error = true;
			}
		}

		// Si "Estado presentación digital" es "-- Presentado --"
		if ($requisito['estado_req_digital'] == solicitar_certificacion::estado_presentado) {
			$acciones_permitidas = [
				'descargar_documentacion'
			];
			if (!in_array($accion, $acciones_permitidas)) {
				$error = true;
			}
		}

		// Si "Estado presentación digital" es "-- Vencido --"
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

		// Si falló el control
		if ($error) {
			throw new \Exception(kernel::traductor()->trans('requisitos.msj_error_generico'));
		}
	}
	
	
	function accion__index()
	{
		// Se resetea la caché con los datos de  requisitos de la persona
		$this->limpiar_cache_consulta_requisitos();
		$this->vista()->vista_reporte();
	}

	function accion__doc_digital()
	{
		$id_hash = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$pagelet = $this->vista()->vista_doc_digital();
		$requisito = $this->buscar_requisito($id_hash);
		$pagelet->set_requisito($requisito);
	}

	protected function buscar_requisito($id_hash)
	{
		$param = [
			'alumno' => kernel::persona()->get_id_alumno_activo(),
			'estado_req_digital' => null
		];
		$requisitos = catalogo::consultar('alumno', 'get_requisitos_ingreso_propuesta', $param);
		
		foreach ($requisitos as $requisito) {
			if ($requisito[catalogo::id] == $id_hash) {
				return $requisito;
			}
		}
		die();
	}

	protected function limpiar_cache_consulta_requisitos()
	{
		$param = [
			'alumno' => kernel::persona()->get_id_alumno_activo(),
			'estado_req_digital' => null
		];
		catalogo::limpiar_cache('alumno', 'get_requisitos_ingreso_propuesta', $param);

		$param = [
			'alumno' => kernel::persona()->get_id_alumno_activo(),
			'estado_req_digital' => [solicitar_certificacion::estado_no_presentado, solicitar_certificacion::estado_vencido]
		];
		catalogo::limpiar_cache('alumno', 'get_requisitos_ingreso_propuesta', $param);
	}

	//--------------------------------------------------------------------------
	//---- RDI - Documentación -------------------------------------------------
	//--------------------------------------------------------------------------

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

		if ($_FILES['archivo']['error'] == UPLOAD_ERR_INI_SIZE || $_FILES['archivo']['error'] == UPLOAD_ERR_FORM_SIZE) {
			kernel::sesion()->set('error_tamanio', true);
		}

		$hubo_error = false;
		$msg_error = '';
		$path_file = '';
		
		try {

			kernel::db()->abrir_transaccion();
			
			$vencido = $this->validate_param('vencido', 'post', validador::TIPO_INT);
			$es_modificacion = $this->validate_param('es_modificacion', 'post', validador::TIPO_INT);
			
			// Valido el documento subido
			list($hubo_error_documento_subido, $msg_error_documento_subido) = clienteRDI::validar_documento_subido($indice_form);

			// Si hubo error en la validación del documento subido
			if ($hubo_error_documento_subido) {
				throw new \Exception($msg_error_documento_subido);
			}
			
			$path_file = clienteRDI::guardar_archivos_subidos($indice_form);
			if (!empty($path_file)) {
				
				$tipo_documento_digital = $requisito['tipo_documento_digital'];
				
				// Si el requisito está vencido se carga como "-- Pendiente de validación --"
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

				// Se resetea la caché con los datos de  requisitos de la persona en pendiente para que pueda traer el
				//requisito que se subio
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
			// Si se guarda con éxito
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

			// $this->descargar_archivo() lanza una excepción del tipo FinalizadorRequest
			if(!$e instanceof FinalizadorRequest) {
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

				// Se resetea la caché con los datos de  requisitos de la persona
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
			// Si se guarda con éxito
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
}