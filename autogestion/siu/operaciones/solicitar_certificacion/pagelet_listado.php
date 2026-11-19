<?php

namespace siu\operaciones\solicitar_certificacion;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_listado extends pagelet
{
	function get_nombre()
    {
        return 'listado';
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
        $certificaciones_no_tramitadas = $this->modelo()->info__certificaciones_no_tramitadas();
		
		// Si no hay certificaciones a tramitar 
		if(empty($certificaciones_no_tramitadas)){
			$this->data['url_nueva_solicitud'] = "#";
			$this->data['habilitar_nueva_solicitud'] = false;
		} //sino
		else
		{
			$operacion = kernel::ruteador()->get_id_operacion();
			$this->data['url_nueva_solicitud'] = kernel::vinculador()->crear($operacion, 'nueva_solicitud');
			$this->data['habilitar_nueva_solicitud'] = true;
		}
		
		
		$certificados = $this->modelo()->info__certificados_tramitados();
		$this->data['certificados'] = $certificados;

		$this->prepare_constante();
		$this->prepare_urls();
		$this->prepare_varibles_js();
		$this->prepare_mensaje();
		$this->data['usa_rdi'] = kernel::proyecto()->usa_rdi();

		$this->add_var_js('guardado_ok', false);
		if(kernel::sesion()->esta_seteada('guardado_ok')){
			kernel::sesion()->borrar('guardado_ok');
			$this->add_var_js('guardado_ok', true);
			$this->add_mensaje_js('guardado_ok_msj', kernel::traductor()->trans('solicitar_certificacion.solicitud_guardada_exito'));
		}
    }

    function prepare_constante()
    {
        $this->data['estado_requisitos_abierto'] = \tramite_certificacion::estado_requisitos_abierto;
        $this->data['estado_requisitos_pendiente'] = \tramite_certificacion::estado_requisitos_pendiente;
        $this->data['estado_requisitos_aceptado'] = \tramite_certificacion::estado_requisitos_aceptado;
    }

    function prepare_urls()
    {
        $operacion = kernel::ruteador()->get_id_operacion();
        $this->data['url_documentacion'] = kernel::vinculador()->crear($operacion, 'documentacion');
        $this->data['url_reabrir_solicitud'] = kernel::vinculador()->crear($operacion, 'reabrir_solicitud');
    }

	function prepare_varibles_js()
	{
		// Mensajes para el prompt de Guardar
		$this->add_mensaje_js('msg_prompt_reabrir', kernel::traductor()->trans('solicitar_certificacion.msg_prompt_reabrir'));
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
}
