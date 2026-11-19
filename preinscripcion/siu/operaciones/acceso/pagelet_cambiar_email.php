<?php

namespace siu\operaciones\acceso;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;

class pagelet_cambiar_email extends pagelet
{
    const ERROR = 'ERROR';
    const INICIAL = 'inicial';
    const EXITO = 'EXITO';
    const CAMBIAR_EMAIL = 'CAMBIAR_EMAIL';
	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'cambiar_email';
	}


	function prepare()
	{
		$this->data['url_volver'] = kernel::vinculador()->crear('acceso', 'index');
		$this->data['action_validar'] = kernel::vinculador()->crear('acceso', 'validar');
		$this->data['action_nuevo_email'] = kernel::vinculador()->crear('acceso', 'nuevo_email');
		$this->data['datos_ayuda'] =  kernel::proyecto()->get_parametro('datos_ayuda');
		
		$pregunta_secreta= $this->controlador->get_pregunta_secreta();
		$this->data['combo_propuestas'] = $this->controlador->get_combo_propuestas_preinscriptas();
		$this->data['pregunta_secreta']=  kernel::traductor()->trans($pregunta_secreta);
		if ($this->controlador->hay_var('mensajes')) {
            $this->data['mensajes'] = $this->controlador->get_var('mensajes');
        }
	}

}