<?php

namespace siu\operaciones\requisitos_becas;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\img;
use SIU\Chulupi\util\u;
use siu\modelo\transacciones\becas;

class pagelet_edicion extends pagelet
{

    /**
     * Shortcut al modelo
     * @return \siu\modelo\transacciones\becas
     */
    function modelo()
    {
        return $this->controlador->modelo();
    }

    function get_nombre()
    {
        return 'edicion';
    }

    function prepare()
    {
        $this->prepare_constante();
        $this->prepare_urls();
        $this->prepare_extensiones_tamanio_permitidos();
        $this->recuperar_documento_rdi();
        $this->prepare_varibles_js();
        
        $habilitacion = $this->data['requisito']['habilitacion'];
        $this->data['info_habilitacion'] = $this->modelo()->info__habilitacion($habilitacion);

        // true cuando viene del botón "Modificar Datos", false cuando viene del botón "Aplicar"
        $es_reapertura = kernel::sesion()->esta_seteada(becas::CLAVE_REAPERTURA_SESION);
        $this->data['es_reapertura'] = $es_reapertura;

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

    function prepare_constante()
    {
        $this->data['si'] = \comunes::si;
        $this->data['no'] = \comunes::no;
    }

    function prepare_urls()
    {
        $operacion = kernel::ruteador()->get_id_operacion();
        $this->data['url_listado'] = kernel::vinculador()->crear($operacion, 'index');
        $this->data['url_descargar'] = kernel::vinculador()->crear($operacion, 'descargar');
        $this->data['url_eliminar'] = kernel::vinculador()->crear($operacion, 'eliminar');
        $this->data['url_subir'] = kernel::vinculador()->crear($operacion, 'subir');
        $this->data['url_modificar'] = kernel::vinculador()->crear($operacion, 'modificar');
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
        $this->data['error_recuperar_documento'] = false;
        $this->data['url_img_vista_previa_documento'] = null;
        $this->data['es_imagen'] = false;

        try {
            // Si tiene documento subido al RDI
            if ($this->data['requisito']['presentado'] == \comunes::si) {

                // Recupero documento del RDI
                $srv = kernel::proyecto()->rdi()->servicio($this->data['requisito']['tipo_documento_digital']);
                $content = $srv->recuperarContenido($this->data['requisito']['id_documento']);

                // Si se trata de una imagen
                if (img::check_base64_image($content)) {
                    $url = img::url_imagen($content);
                    $this->data['url_img_vista_previa_documento'] = $url;
                    $this->data['es_imagen'] = true;
                } else {// Si no se trata de una imagen
                    $this->data['url_img_vista_previa_documento'] = kernel::vinculador()->vinculo_img('img_recurso.png');
                }
            }
        } catch (\Exception $e) {
            kernel::log()->add_error($e);
            $this->data['error_recuperar_documento'] = true;
        }
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