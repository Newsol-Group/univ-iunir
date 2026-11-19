<?php

namespace siu\operaciones\solicitar_certificacion;

use siu\guarani;
use siu\extension_kernel\controlador_g3w2;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use SIU\Chulupi\nucleo\NuevoRequest;
use siu\modelo\transacciones\solicitar_certificacion;
use siu\modelo\_comun\clienteRDI;

class controlador extends controlador_g3w2
{
	
	/**
	 * @return \siu\modelo\transacciones\solicitar_certificacion
	 */
	function modelo()
	{
		return guarani::solicitar_certificacion();
	}

	/**
	 * Ventana que se ejecuta antes de ejecutar una acción
	 */
	protected function pre_accion()
	{
		parent::pre_accion();

		try {
			$accion = kernel::ruteador()->get_accion();
			kernel::log()->add_debug("=== pre_accion - accion ===", $accion);

			$acciones_post = [
				'reabrir_solicitud',
				'subir_documentacion',
				'eliminar_documentacion',
				'finalizar_solicitud'
			];
			if (in_array($accion, $acciones_post)) {
				// Controlo si viene por POST
				$this->controlar_viene_post();
			}

			$acciones_rdi = [
				'requisito',
				'descargar_documentacion',
				'modificar_documentacion',
				'eliminar_documentacion',
				'subir_documentacion'
			];
			if (in_array($accion, $acciones_rdi)) {
				// Controlo si usa RDI
				$this->controlar_usa_rdi();
			}

			$acciones_estado_abierto = [
				'finalizar_solicitud',
				'requisito',
				'descargar_documentacion',
				'modificar_documentacion',
				'eliminar_documentacion',
				'subir_documentacion'
			];
			if (in_array($accion, $acciones_estado_abierto)) {
				// Controlo que la solicitud tenga estado "Abierto"
				$this->controlar_solicitud_estado(\tramite_certificacion::estado_requisitos_abierto, \tramite_certificacion::estado_requisitos_abierto_descr);
			}

			$acciones_estado_pendiente = [
				'reabrir_solicitud'
			];
			if (in_array($accion, $acciones_estado_pendiente)) {
				// Controlo que la solicitud tenga estado "Pendiente"
				$this->controlar_solicitud_estado(\tramite_certificacion::estado_requisitos_pendiente, \tramite_certificacion::estado_requisitos_pendiente_descr);
			}

			$acciones_requisitos_cargados = [
				'finalizar_solicitud'
			];
			if (in_array($accion, $acciones_requisitos_cargados)) {
				// Controlo que todos los requisitos hayan sido cargados
				$this->controlar_requisitos_cargados();
			}

			$acciones_requisito_estado_presentacion_digital = [
				'descargar_documentacion',
				'modificar_documentacion',
				'eliminar_documentacion',
				'subir_documentacion'
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
			// Redirijo al "Listado de solicitudes de certificaciones"
			$this->nuevo_request($operacion, 'index');
		}

	}

	function controlar_usa_rdi()
	{
		// Si no usa RDI (parámetro 'usa_repositorio_digital')
		if (!kernel::proyecto()->usa_rdi()) {
			throw new \Exception(kernel::traductor()->trans('requisitos.error_usa_repositorio_digital'));
		}
	}

	function controlar_viene_post()
	{
		// Si no es por POST
		if (!kernel::request()->isPost()) {
			throw new \Exception(kernel::traductor()->trans('requisitos.msj_error_generico'));
		}
	}

	function controlar_solicitud_estado($estado, $estado_descr)
	{
		$nro_solicitud = (kernel::request()->isPost())? $this->validate_param('nro_solicitud', 'post', validador::TIPO_INT) : $this->validate_param(0, 'get', validador::TIPO_INT);
		
		$certificados_tramitados = $this->modelo()->info__certificados_tramitados();
		$certificado_tramitado = $certificados_tramitados[$nro_solicitud] ?? null;

		// Si el certificado no existe
		if (is_null($certificado_tramitado)) {
			throw new \Exception(kernel::traductor()->trans('solicitar_certificacion.error_existe_certificado_tramitado'));
		}

		// Si el 'estado_requisitos' de la solicitud NO es el pasado por parámetros
		if ($certificado_tramitado['estado_requisitos'] != $estado) {
			throw new \Exception(kernel::traductor()->trans('solicitar_certificacion.documentacion.msg_estado_invalido', ['%estado%' => $estado_descr]));
		}
	}

	function controlar_requisitos_cargados()
	{
		$nro_solicitud = (kernel::request()->isPost())? $this->validate_param('nro_solicitud', 'post', validador::TIPO_INT) : $this->validate_param(0, 'get', validador::TIPO_INT);

		$certificados_tramitados = $this->modelo()->info__certificados_tramitados();
		$certificado_tramitado = $certificados_tramitados[$nro_solicitud] ?? null;

		// Si el certificado no existe
		if (is_null($certificado_tramitado)) {
			throw new \Exception(kernel::traductor()->trans('solicitar_certificacion.error_existe_certificado_tramitado'));
		}

		$cant_requistos_cargados = $this->modelo()->get_cant_requisitos_cargados_by_solicitud($nro_solicitud);

		// Si no cargó todos los requisitos
		if ($cant_requistos_cargados < $certificado_tramitado['cant_requistos_requeridos']) {
			throw new \Exception(kernel::traductor()->trans('solicitar_certificacion.msg_cargar_requisitos'));
		}
	}

	function controlar_requisito_estado_presentacion_digital()
	{
		$nro_solicitud = (kernel::request()->isPost())? $this->validate_param('nro_solicitud', 'post', validador::TIPO_INT) : $this->validate_param(0, 'get', validador::TIPO_INT);
		$certificados_tramitados = $this->modelo()->info__certificados_tramitados();
		$certificado_tramitado = $certificados_tramitados[$nro_solicitud] ?? null;
		// Si el certificado no existe
		if (is_null($certificado_tramitado)) {
			throw new \Exception(kernel::traductor()->trans('solicitar_certificacion.error_existe_certificado_tramitado'));
		}

		$requisito = (kernel::request()->isPost())? $this->validate_param('requisito', 'post', validador::TIPO_INT) : $this->validate_param(1, 'get', validador::TIPO_INT);
		$requisitos = $this->modelo()->get_requisitos_by_solicitud($nro_solicitud);
		$requisito = $requisitos[$requisito] ?? null;
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
				'subir_documentacion'
			];
			if (!in_array($accion, $acciones_permitidas)) {
				$error = true;
			}
		}

		// Si "Estado presentación digital" es "-- No cargado --"
		if ($requisito['estado_req_digital'] == solicitar_certificacion::estado_no_presentado) {
			$acciones_permitidas = [
				'subir_documentacion'
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
				'subir_documentacion'
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
		$pagelet_listado = $this->vista()->set_pagelet('listado');
	}
	
	function accion__nueva_solicitud()
	{
		$operacion = kernel::ruteador()->get_id_operacion();
		$certificaciones_no_tramitadas = $this->modelo()->info__certificaciones_no_tramitadas();
		// Si no hay certificaciones a tramitar lo redirijo al listado
		if(empty($certificaciones_no_tramitadas)){
			$this->nuevo_request($operacion, 'index');
		}
		
		$pagelet_nueva_solicitud = $this->vista()->set_pagelet('nueva_solicitud');
		$pagelet_nueva_solicitud->data['cuil'] = null;
		$pagelet_nueva_solicitud->data['certificacion'] = null;
		$pagelet_nueva_solicitud->data['colacion'] = null;
		$pagelet_nueva_solicitud->data['juramento'] = null;
		
		$pagelet_nueva_solicitud->data['cuil_invalido'] = false;
		$pagelet_nueva_solicitud->data['certificacion_vacia'] = false;
		
		// Si estoy guardando
		if(kernel::request()->isPost()){
			
			try{
			
				$errores_validacion = false;
				
				$cuil			= $this->validate_param('cuil', 'post', validador::TIPO_TEXTO, array('default' => null));
				$certificacion	= $this->validate_param('certificacion', 'post', validador::TIPO_INT, array('default' => null));
				$colacion		= $this->validate_param('colacion', 'post', validador::TIPO_INT, array('default' => null));
				$juramento		= $this->validate_param('juramento', 'post', validador::TIPO_INT, array('default' => null));
				
				kernel::log()->add_debug('=== cuil ===', $cuil);
				kernel::log()->add_debug('=== certificacion ===', $certificacion);
				kernel::log()->add_debug('=== colacion ===', $colacion);
				kernel::log()->add_debug('=== juramento ===', $juramento);

				$pagelet_nueva_solicitud->add_var_js('error', false);
				
				$pagelet_nueva_solicitud->data['cuil']			= $cuil;
				$pagelet_nueva_solicitud->data['certificacion'] = $certificacion;
				$pagelet_nueva_solicitud->data['colacion']		= $colacion;
				$pagelet_nueva_solicitud->data['juramento']		= $juramento;

				if(is_string($cuil)){
					$cuil = trim($cuil);
					if($cuil == '') $cuil = null;
				}
				
				// Si el CUIL no es vacío
				if(!is_null($cuil)){
					$cuil = str_replace("-", "", $cuil);
					// Si el CUIL es inválido
					if(!\persona::validarCUIT($cuil)){
						$pagelet_nueva_solicitud->data['cuil_invalido'] = true;
						$errores_validacion = true;
					}
				}

				// Vertifico que la certificación no sea vacía
				if(is_null($certificacion)){
					$pagelet_nueva_solicitud->data['certificacion_vacia'] = true;
					$errores_validacion = true;
				}
			
				// Si no hay errores de validación guardo
				if(!$errores_validacion){
					
					$cumple_condiciones = $this->modelo()->evt__confirmar_solicitud_titulo($cuil, $certificacion, $colacion, $juramento);
					
					// Si el alumno cumple con las condiciones para obtener la certificación
					if($cumple_condiciones){
						kernel::sesion()->set('guardado_ok', true);
						$this->nuevo_request($operacion, 'index');
					}
					else{// sino
						$pagelet_nueva_solicitud->add_var_js('error', true);
						$pagelet_nueva_solicitud->add_mensaje_js('error_msj', kernel::traductor()->trans('solicitar_certificacion.no_cumplis_condiciones_certificacion'));
					}
					
				}
				
			}
			catch (\Exception $e)
			{
				// Si es una redirección la relanzo
				if ($e instanceof NuevoRequest) {
					throw $e;
				}
				
				kernel::log()->add_error($e);
				
				$pagelet_nueva_solicitud->add_var_js('error', true);
				$pagelet_nueva_solicitud->add_mensaje_js('error_msj', kernel::traductor()->trans('solicitar_certificacion.error_al_procesar_solicitud'));
			}
			
		}
	}

	function accion__documentacion()
	{
		$hubo_error = false;
		$msg_error = '';

		try {
			$nro_solicitud = $this->validate_param(0, 'get', validador::TIPO_INT);

			$certificados_tramitados = $this->modelo()->info__certificados_tramitados();

			$certificado_tramitado = $certificados_tramitados[$nro_solicitud] ?? null;

			// Si el certificado no existe
			if (is_null($certificado_tramitado)) {
				throw new \Exception(kernel::traductor()->trans('solicitar_certificacion.error_existe_certificado_tramitado'));
			}

		} catch (\Exception $e) {
			$hubo_error = true;
			$msg_error = $e->getMessage();
		}

		// Si hubo error
		if ($hubo_error) {
			kernel::sesion()->set('tipo_mensaje', 'alert-error');
            kernel::sesion()->set('mensaje', $msg_error);
			$operacion = kernel::ruteador()->get_id_operacion();
			// Redirijo al "Listado de solicitudes de certificaciones"
			$this->nuevo_request($operacion, 'index');
		} else {
			$pagelet = $this->vista()->set_pagelet('documentacion');
			$pagelet->data['certificado_tramitado'] = $certificado_tramitado;
		}
	}

	function accion__reabrir_solicitud()
	{
		$hubo_error = false;
		$msg_error = '';

		try {

			$nro_solicitud = $this->validate_param('nro_solicitud', 'post', validador::TIPO_INT);
			list($hubo_error_modelo, $msg_error_modelo) = $this->modelo()->evt__reabrir_solicitud($nro_solicitud);

			// Si hubo error en el modelo
            if ($hubo_error_modelo) {
				throw new \Exception($msg_error_modelo);
            }

		} catch (\Exception $e) {
			$hubo_error = true;
			$msg_error = $e->getMessage();
		}

		$operacion = kernel::ruteador()->get_id_operacion();
		
		// Si hubo error
        if ($hubo_error) {
            kernel::sesion()->set('tipo_mensaje', 'alert-error');
            kernel::sesion()->set('mensaje', $msg_error);
			// Redirijo al "Listado de solicitudes de certificaciones"
			$this->nuevo_request($operacion, 'index');
        // Si se guarda con éxito
        } else {
			kernel::sesion()->set('tipo_mensaje', 'alert-success');
            kernel::sesion()->set('mensaje', kernel::traductor()->trans('solicitar_certificacion.documentacion.msg_reabrir_exito'));
            // Redirijo a "Carga de Documentación de Egreso"
			$url_documentacion = kernel::vinculador()->crear($operacion, 'documentacion', [$nro_solicitud]);
			$this->nuevo_request_url($url_documentacion);
        }

        
	}
	
	function accion__finalizar_solicitud()
	{
		$hubo_error = false;
		$msg_error = '';

		try {

			$nro_solicitud = $this->validate_param('nro_solicitud', 'post', validador::TIPO_INT);
			list($hubo_error_modelo, $msg_error_modelo) = $this->modelo()->evt__finalizar_solicitud($nro_solicitud);

			// Si hubo error en el modelo
            if ($hubo_error_modelo) {
				throw new \Exception($msg_error_modelo);
            }

		} catch (\Exception $e) {
			$hubo_error = true;
			$msg_error = $e->getMessage();
		}

		// Si hubo error
        if ($hubo_error) {
            kernel::sesion()->set('tipo_mensaje', 'alert-error');
            kernel::sesion()->set('mensaje', $msg_error);
        // Si se guarda con éxito
        } else {
            // Redirijo al "Listado de solicitudes de certificaciones"
            kernel::sesion()->set('tipo_mensaje', 'alert-success');
            kernel::sesion()->set('mensaje', kernel::traductor()->trans('solicitar_certificacion.documentacion.msg_finalizar_exito'));
        }

        // Redirijo al "Listado de solicitudes de certificaciones"
        $operacion = kernel::ruteador()->get_id_operacion();
        $this->nuevo_request($operacion, 'index');
	}

	function accion__requisito()
	{
		$hubo_error = false;
		$msg_error = '';

		try {
			$nro_solicitud = $this->validate_param(0, 'get', validador::TIPO_INT);
			$requisito = $this->validate_param(1, 'get', validador::TIPO_INT);

			$certificados_tramitados = $this->modelo()->info__certificados_tramitados();
			$certificado_tramitado = $certificados_tramitados[$nro_solicitud] ?? null;
			// Si el certificado no existe
			if (is_null($certificado_tramitado)) {
				throw new \Exception(kernel::traductor()->trans('solicitar_certificacion.error_existe_certificado_tramitado'));
			}

			$requisitos = $this->modelo()->get_requisitos_by_solicitud($nro_solicitud);
			$requisito = $requisitos[$requisito] ?? null;
			// Si el requisito no existe
			if (is_null($requisito)) {
				throw new \Exception(kernel::traductor()->trans('requisitos.error_existe_requisto'));
			}

		} catch (\Exception $e) {
			$hubo_error = true;
			$msg_error = $e->getMessage();
		}

		$operacion = kernel::ruteador()->get_id_operacion();
		
		// Si hubo error
		if ($hubo_error) {
			kernel::sesion()->set('tipo_mensaje', 'alert-error');
            kernel::sesion()->set('mensaje', $msg_error);
			// Redirijo a "Carga de Documentación de Egreso"
			$url_documentacion = kernel::vinculador()->crear($operacion, 'documentacion', [$nro_solicitud]);
			$this->nuevo_request_url($url_documentacion);
		} else {
			$pagelet = $this->vista()->set_pagelet('requisito');
			$pagelet->data['certificado_tramitado'] = $certificado_tramitado;
			$pagelet->data['requisito'] = $requisito;
		}
	}
	
    function accion__descargar_documentacion()
    {

        $hubo_error = false;
        $msg_error = '';
        $operacion = kernel::ruteador()->get_id_operacion();

        try {

            // Redirijo a "Listado de solicitudes de certificaciones"
            $url_redirect = kernel::vinculador()->crear($operacion, 'index');

            $nro_solicitud = $this->validate_param(0, 'get', validador::TIPO_INT);
			$requisito = $this->validate_param(1, 'get', validador::TIPO_INT);

            // Redirijo al requisito
            $url_redirect = kernel::vinculador()->crear($operacion, 'requisito', [$nro_solicitud, $requisito]);

            list($hubo_error_modelo, $msg_error_modelo, $path, $nombre_descarga) = $this->modelo()->evt__descargar_requisito_egreso($nro_solicitud, $requisito);

            // Si hubo error en el modelo
            if ($hubo_error_modelo) {
                throw new \Exception($msg_error_modelo);
            }

            // Descargo el documento al browser
            $this->descargar_archivo($path, $nombre_descarga, true);

        } catch (\Exception $e) {
            $hubo_error = true;
            $msg_error = $e->getMessage();
        }

        // Si hubo error
        if ($hubo_error) {
            kernel::sesion()->set('tipo_mensaje', 'alert-error');
            kernel::sesion()->set('mensaje', $msg_error);
            $this->nuevo_request_url($url_redirect);
        }
    }

	function accion__eliminar_documentacion()
	{
		$hubo_error = false;
		$msg_error = '';
		$operacion = kernel::ruteador()->get_id_operacion();

		try {

			// Redirijo a "Listado de solicitudes de certificaciones"
            $url_redirect = kernel::vinculador()->crear($operacion, 'index');

            $nro_solicitud = $this->validate_param('nro_solicitud', 'post', validador::TIPO_INT);
			$requisito = $this->validate_param('requisito', 'post', validador::TIPO_INT);

            // Redirijo al requisito
            $url_redirect = kernel::vinculador()->crear($operacion, 'requisito', [$nro_solicitud, $requisito]);

			list($hubo_error_modelo, $msg_error_modelo) = $this->modelo()->evt__eliminar_requisito_egreso($nro_solicitud, $requisito);

			// Si hubo error en el modelo
			if ($hubo_error_modelo) {
				throw new \Exception($msg_error_modelo);
			}

		} catch (\Exception $e) {
			$hubo_error = true;
			$msg_error = $e->getMessage();
		}

		// Si hubo error
		if ($hubo_error) {
			kernel::sesion()->set('tipo_mensaje', 'alert-error');
			kernel::sesion()->set('mensaje', $msg_error);
		// Si se guarda con éxito
		} else {
			kernel::sesion()->set('tipo_mensaje', 'alert-success');
			kernel::sesion()->set('mensaje', kernel::traductor()->trans('requisitos.msg_guardar_exito'));
		}

		$this->nuevo_request_url($url_redirect);
	}

	function accion__subir_documentacion()
	{
		$hubo_error = false;
		$msg_error = '';
		$operacion = kernel::ruteador()->get_id_operacion();

		try {

			// Redirijo a "Listado de solicitudes de certificaciones"
            $url_redirect = kernel::vinculador()->crear($operacion, 'index');

            $nro_solicitud = $this->validate_param('nro_solicitud', 'post', validador::TIPO_INT);
			$requisito = $this->validate_param('requisito', 'post', validador::TIPO_INT);
			$es_modificacion = $this->validate_param('es_modificacion', 'post', validador::TIPO_INT);
			$vencido = $this->validate_param('vencido', 'post', validador::TIPO_INT);

            // Redirijo al requisito
            $url_redirect = kernel::vinculador()->crear($operacion, 'requisito', [$nro_solicitud, $requisito]);

			$indice_file = 'archivo';

			// Valido el documento subido
			list($hubo_error_documento_subido, $msg_error_documento_subido) = clienteRDI::validar_documento_subido($indice_file);

			// Si hubo error en la validación del documento subido
			if ($hubo_error_documento_subido) {
				throw new \Exception($msg_error_documento_subido);
			}

			// Guardo el documento subido al filesystem
			$archivo_subido = clienteRDI::guardar_archivos_subidos($indice_file);
			list($hubo_error_modelo, $msg_error_modelo) = $this->modelo()->evt__subir_requisito_egreso($nro_solicitud, $requisito, $archivo_subido, $vencido);

			// Si hubo error en el modelo
			if ($hubo_error_modelo) {
				throw new \Exception($msg_error_modelo);
			}

		} catch (\Exception $e) {
			$hubo_error = true;
			$msg_error = $e->getMessage();
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

		// Redirijo
		$this->nuevo_request_url($url_redirect);
	}

	function accion__modificar_documentacion()
	{
		$hubo_error = false;
		$msg_error = '';
		$operacion = kernel::ruteador()->get_id_operacion();

		try {
			// Redirijo a "Listado de solicitudes de certificaciones"
            $url_redirect = kernel::vinculador()->crear($operacion, 'index');

            $nro_solicitud = $this->validate_param(0, 'get', validador::TIPO_INT);
			$requisito = $this->validate_param(1, 'get', validador::TIPO_INT);

            // Redirijo al requisito
            $url_redirect = kernel::vinculador()->crear($operacion, 'requisito', [$nro_solicitud, $requisito]);

			$certificados_tramitados = $this->modelo()->info__certificados_tramitados();
			$certificado_tramitado = $certificados_tramitados[$nro_solicitud] ?? null;
			// Si el certificado no existe
			if (is_null($certificado_tramitado)) {
				throw new \Exception(kernel::traductor()->trans('solicitar_certificacion.error_existe_certificado_tramitado'));
			}

			$requisitos = $this->modelo()->get_requisitos_by_solicitud($nro_solicitud);
			$requisito = $requisitos[$requisito] ?? null;
			// Si el requisito no existe
			if (is_null($requisito)) {
				throw new \Exception(kernel::traductor()->trans('requisitos.error_existe_requisto'));
			}

		} catch (\Exception $e) {
			$hubo_error = true;
			$msg_error = $e->getMessage();
		}

		// Si hubo error
		if ($hubo_error) {
			kernel::sesion()->set('tipo_mensaje', 'alert-error');
			kernel::sesion()->set('mensaje', $msg_error);
			// Redirijo
			$this->nuevo_request_url($url_redirect);
		} else {
			$pagelet = $this->vista()->set_pagelet('requisito');
			$pagelet->data['certificado_tramitado'] = $certificado_tramitado;
			$pagelet->data['requisito'] = $requisito;
			kernel::sesion()->set('es_modificacion', 1);
		}
	}
}
