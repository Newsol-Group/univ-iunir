<?php

namespace siu\operaciones\solicitar_certificacion;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use SIU\Chulupi\util\img;
use siu\modelo\transacciones\solicitar_certificacion;

class pagelet_requisito extends pagelet
{

    function get_nombre()
    {
        return 'requisito';
    }

	/**
     * Shortcut al modelo
     * @return \siu\modelo\transacciones\solicitar_certificacion
     */
	function modelo()
	{
		return $this->controlador->modelo();
	}

    function prepare()
    {
        $this->prepare_urls();
        $this->prepare_mensaje();
        $this->prepare_modificacion();
        $this->prepare_extensiones_tamanio_permitidos();
		$this->recuperar_documento_rdi();
		$this->prepare_varibles_js();
		$this->prepare_constantes();
    }

    function prepare_urls()
    {
        $operacion = kernel::ruteador()->get_id_operacion();
        $this->data['url_volver'] = kernel::vinculador()->crear($operacion, 'documentacion', [$this->data['certificado_tramitado']['nro_solicitud']]);
		$this->data['url_subir'] = kernel::vinculador()->crear($operacion, 'subir_documentacion');
		$this->data['url_eliminar'] = kernel::vinculador()->crear($operacion, 'eliminar_documentacion');
		$this->data['url_modificar'] = kernel::vinculador()->crear($operacion, 'modificar_documentacion', [$this->data['certificado_tramitado']['nro_solicitud'], $this->data['requisito']['requisito']]);
		$this->data['url_descargar'] = kernel::vinculador()->crear($operacion, 'descargar_documentacion', [$this->data['certificado_tramitado']['nro_solicitud'], $this->data['requisito']['requisito']]);
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

	function prepare_extensiones_tamanio_permitidos()
    {
        // Extensiones permitidas de RDI
        $extensiones = \toba::consulta_php('co_configuracion', true)->get_rdi_extensiones_permitidas();
        $this->data['extensiones'] = implode(", ", $extensiones);

        // Tamaña máximo permitido RDI
        $upload_max_filesize = u::return_bytes(ini_get('upload_max_filesize'));
        $post_max_size = u::return_bytes(ini_get('post_max_size'));

        // https://stackoverflow.com/questions/7754133/php-post-max-size-overrides-upload-max-filesize
        $tamanio_max = ($upload_max_filesize < $post_max_size)? $upload_max_filesize : $post_max_size;

        $this->data['tamanio_maximo_numero'] = $tamanio_max;
        $this->data['tamanio_maximo'] = u::formatBytes($tamanio_max);
        $this->add_var_js('tamanio_maximo', $tamanio_max);
    }

	function prepare_varibles_js()
	{
		// Mensajes para el prompt de Guardar
		$this->add_mensaje_js('msg_prompt_no_poseo_guardar', kernel::traductor()->trans('requisitos.msg_prompt_no_poseo_guardar'));
		$this->add_mensaje_js('aceptar', kernel::traductor()->trans('aceptar'));
		$this->add_mensaje_js('cancelar', kernel::traductor()->trans('cancelar'));
	}

	function recuperar_documento_rdi()
    {
        $this->data['error_cnx_rdi'] = false;

        try {
            // Si tiene documento subido al RDI
            if (!empty($this->data['requisito']['id_documento'])) {

                // Recupero documento del RDI
                $srv = kernel::proyecto()->rdi()->servicio($this->data['requisito']['tipo_documento_digital']);
                $content = $srv->recuperarContenido($this->data['requisito']['id_documento']);

                // Si se trata de una imagen
                if (img::check_base64_image($content)) {
                    $url = img::url_imagen($content);
                    $this->data['requisito']['es_imagen'] = true;
                    $this->data['requisito']['url_img_vista_previa_documento'] = $url;
                } else {// Si no se trata de una imagen
					$this->data['requisito']['es_imagen'] = false;
                    $this->data['requisito']['url_img_vista_previa_documento'] = kernel::vinculador()->vinculo_img('img_recurso.png');
                }
            }
        } catch (\Exception $e) {
            kernel::log()->add_error($e);
            $this->data['error_cnx_rdi'] = true;
        }
    }

	function prepare_constantes()
    {
        $this->data['estado_no_presentado'] = solicitar_certificacion::estado_no_presentado;
        $this->data['estado_pendiente_validacion'] = solicitar_certificacion::estado_pendiente_validacion;
        $this->data['estado_presentado'] = solicitar_certificacion::estado_presentado;
        $this->data['estado_vencido'] = solicitar_certificacion::estado_vencido;
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
}