<?php

namespace siu\operaciones\requisitos_ingreso;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\util\u;
use SIU\Chulupi\util\img;
use siu\modelo\transacciones\solicitar_certificacion;

class pagelet_doc_digital extends pagelet
{
	protected $requisito;

	function get_nombre()
	{
		return 'doc_digital';
	}

	function set_requisito($requisito)
	{
		$this->requisito = $requisito;
	}

	function prepare()
	{
		if (!kernel::proyecto()->usa_rdi()) {
			die();
		}

		$this->data['usa_repositorio_digital'] = kernel::proyecto()->usa_rdi();

		$operacion = kernel::ruteador()->get_id_operacion();
		$this->data['url_volver'] = kernel::vinculador()->crear($operacion);

		//----------------------------------------------------------------------

		$this->add_var_js('msj_confirmar_eliminar', kernel::traductor()->trans('requisitos.msj_confirmar_eliminar'));

		$this->prepare_extensiones_tamanio_permitidos();
		$this->prepare_varibles_js();
		$this->prepare_constantes();
		$this->prepare_mensaje();
		$this->prepare_modificacion();
		
		$this->add_var_js('error_tamanio_maximo', kernel::traductor()->trans('requisitos.msj_error_tamanio'));

		if (kernel::sesion()->esta_seteada('error_extension')) {
			$this->data['error_extension'] = true;
			kernel::sesion()->borrar('error_extension');
		}

		if (kernel::sesion()->esta_seteada('error_tamanio')) {
			$this->data['error_tamanio'] = true;
			kernel::sesion()->borrar('error_tamanio');
		}

		if (isset($this->mensaje_error)) {
			$this->data['error'] = $this->mensaje_error;
		}

		// Datos para el manejo del documento digital		
		if (isset($this->requisito['f_tope_presentacion']) && !empty($this->requisito['f_tope_presentacion'])) {
			$this->requisito['header'] = $this->requisito['requisito_nombre'] . ' - ' . kernel::traductor()->trans('requisitos.fecha_limite_presentacion') . ': ' . $this->requisito['f_tope_presentacion'];
		} else {
			$this->requisito['header'] = $this->requisito['requisito_nombre'];
		}

		$this->requisito['url_subir'] = kernel::vinculador()->crear($operacion, 'guardar_documentacion', array($this->requisito[catalogo::id]));
		$this->requisito['url_eliminar'] = kernel::vinculador()->crear($operacion, 'eliminar_documentacion', array($this->requisito[catalogo::id]));
		$this->requisito['url_modificar'] = kernel::vinculador()->crear($operacion, 'modificar_documentacion', array($this->requisito[catalogo::id]));
		$this->requisito['url_descargar'] = kernel::vinculador()->crear($operacion, 'descargar_documentacion', array($this->requisito[catalogo::id]));

		$this->requisito['id_form'] = 'form_' . $this->requisito[catalogo::id];

		$error_cnx_rdi = false;
		try {
			if (!empty($this->requisito['id_documento'])) {

				$srv = kernel::proyecto()->rdi()->servicio($this->requisito['tipo_documento_digital']);
				$content = $srv->recuperarContenido($this->requisito['id_documento']);

				$clave_modificacion = 'requisito_' . $this->requisito[catalogo::id];
				if (kernel::sesion()->esta_seteada($clave_modificacion)) {
					kernel::sesion()->borrar($clave_modificacion);
				} else {
					// Si se trata de una imagen
					if (img::check_base64_image($content)) {
						$this->requisito['imagen'] = img::url_imagen($content);
						$this->requisito['es_imagen'] = true;
					} else {// Si no se trata de una imagen
						$this->requisito['imagen'] = kernel::vinculador()->vinculo_img('img_recurso.png');
						$this->requisito['es_imagen'] = false;
					}
				}
			}
		} catch (\Exception $e) {
			kernel::log()->add_error($e);
			$error_cnx_rdi = true;
		}

		$this->data['requisito'] = $this->requisito;
		$this->data['error_cnx_rdi'] = $error_cnx_rdi;
	}

	protected function vinculo_recurso($path_relativo, $ns = false)
	{
		$ns = ($ns === false) ? kernel::localizador()->get_nombre_namespace_original() : $ns;
		$alias = kernel::proyecto()->get_alias();
		return "$alias/$path_relativo";
	}

	function prepare_extensiones_tamanio_permitidos()
	{
		// Extensiones permitidas de RDI
		$extensiones = \toba::consulta_php('co_configuracion', true)->get_rdi_extensiones_permitidas();
		$this->data['extensiones'] = implode(", ", $extensiones);
		
		// Tamaña máximo permitido RDI
		$upload_max_filesize = u::return_bytes(ini_get('upload_max_filesize'));
		$post_max_size = u::return_bytes(ini_get('post_max_size'));
		// https://stackoverflow.com/questions/7754133/php-post-max-size-overrides-upload-max-filesize
		$tamanio_max = ($upload_max_filesize < $post_max_size) ? $upload_max_filesize : $post_max_size;
		$this->data['tamanio_maximo_numero'] = $tamanio_max;
		$this->data['tamanio_maximo'] = u::formatBytes($tamanio_max);
		$this->add_var_js('tamanio_maximo', $tamanio_max);
	}

	function get_css_files()
	{
		$archivos = parent::get_css_files();
		$archivos[] = kernel::vinculador()->vinculo_recurso("css/styling_file_inputs.css");
		return $archivos;
	}

	function get_js_files()
	{
		$archivos = parent::get_js_files();
		$archivos[] = kernel::vinculador()->vinculo_recurso("js/styling_file_inputs.js");
		return $archivos;
	}

	function prepare_varibles_js()
	{
		// Mensajes para el prompt de Guardar
		$this->add_mensaje_js('msg_prompt_no_poseo_guardar', kernel::traductor()->trans('requisitos.msg_prompt_no_poseo_guardar'));
		$this->add_mensaje_js('aceptar', kernel::traductor()->trans('aceptar'));
		$this->add_mensaje_js('cancelar', kernel::traductor()->trans('cancelar'));
	}

	function prepare_constantes()
	{
		$this->data['estado_no_presentado'] = solicitar_certificacion::estado_no_presentado;
		$this->data['estado_pendiente_validacion'] = solicitar_certificacion::estado_pendiente_validacion;
		$this->data['estado_presentado'] = solicitar_certificacion::estado_presentado;
		$this->data['estado_vencido'] = solicitar_certificacion::estado_vencido;
	}

	function prepare_mensaje()
	{
		$this->data['tipo_mensaje'] = '';
		$this->data['mensaje'] = '';
		// Mensaje
		if (kernel::sesion()->esta_seteada('mensaje')) {
			$this->data['tipo_mensaje'] = kernel::sesion()->get('tipo_mensaje');
			$this->data['mensaje'] = kernel::sesion()->get('mensaje');
			kernel::sesion()->borrar('tipo_mensaje');
			kernel::sesion()->borrar('mensaje');
		}
	}

	function prepare_modificacion()
	{
		$this->data['es_modificacion'] = 0;
		if (kernel::sesion()->esta_seteada('es_modificacion')) {
			$this->data['es_modificacion'] = kernel::sesion()->get('es_modificacion');
			kernel::sesion()->borrar('es_modificacion');
		}
	}

}