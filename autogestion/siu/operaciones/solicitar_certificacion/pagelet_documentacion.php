<?php

namespace siu\operaciones\solicitar_certificacion;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\transacciones\solicitar_certificacion;

class pagelet_documentacion extends pagelet
{

    function get_nombre()
    {
        return 'documentacion';
    }

    /**
     * Shortcut al modelo
     *
     * @return \siu\modelo\transacciones\solicitar_certificacion
     */
    protected function modelo()
    {
        return $this->controlador->modelo();
    }

	function prepare()
	{
		$this->prepare_encabezado();
		$this->prepare_urls();
		$this->prepare_varibles_js();
		$this->prepare_mensaje();
		$this->prepare_constantes();
		$this->data['usa_rdi'] = kernel::proyecto()->usa_rdi();
		$this->data['documentos'] = $this->modelo()->get_requisitos_by_solicitud($this->data['certificado_tramitado']['nro_solicitud']);
		$this->data['cant_requistos_cargados'] = $this->modelo()->get_cant_requisitos_cargados_by_solicitud($this->data['certificado_tramitado']['nro_solicitud']);
	}

	function prepare_encabezado()
	{
		$this->data['encabezado']['apellido_nombre'] = kernel::persona()->get_apellido_elegido() . ', ' . kernel::persona()->get_nombres_elegido();
		$this->data['encabezado']['fecha_nacimiento'] = kernel::persona()->get_fecha_nacimiento();
		$this->data['encabezado']['tipo_nro_documento'] = kernel::persona()->get_tipo_documento_desc_abreviada() . ' ' .  kernel::persona()->get_nro_documento();
		$this->data['encabezado']['certificado_nombre'] = $this->data['certificado_tramitado']['certificado_nombre'];
	}

	function prepare_urls()
    {
        $operacion = kernel::ruteador()->get_id_operacion();
        $this->data['url_volver'] = kernel::vinculador()->crear($operacion, 'index');
        $this->data['url_finalizar_solicitud'] = kernel::vinculador()->crear($operacion, 'finalizar_solicitud');
        $this->data['url_requisito'] = kernel::vinculador()->crear($operacion, 'requisito', [$this->data['certificado_tramitado']['nro_solicitud']]);
    }

	function prepare_varibles_js()
	{
		// Mensajes para el prompt de Guardar
		$this->add_mensaje_js('msg_prompt_finalizar', kernel::traductor()->trans('solicitar_certificacion.msg_prompt_finalizar'));
		$this->add_mensaje_js('aceptar', kernel::traductor()->trans('aceptar'));
		$this->add_mensaje_js('cancelar', kernel::traductor()->trans('cancelar'));
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

	function prepare_constantes()
	{
		$this->data['estado_no_presentado'] = solicitar_certificacion::estado_no_presentado;
		$this->data['estado_pendiente_validacion'] = solicitar_certificacion::estado_pendiente_validacion;
		$this->data['estado_presentado'] = solicitar_certificacion::estado_presentado;
		$this->data['estado_vencido'] = solicitar_certificacion::estado_vencido;
	}
}