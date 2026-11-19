<?php

namespace siu\operaciones\solicitudes;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_listado extends pagelet
{
	function get_nombre()
	{
		return 'listado';
	}

	function prepare()
	{
		$this->data['msg_saved'] = kernel::request()->getParam('saved', null) != null ? 'constancias.solicitud_guardada' : null;
		$this->data['solicitudes'] = $this->controlador->get_solicitudes();
		$this->data['historicas'] = kernel::request()->getParam('historicas') == 'true' ? true : false;

		$operacion = kernel::ruteador()->get_id_operacion();
		$this->add_var_js('url_listado', kernel::vinculador()->crear($operacion, 'index'));

		if (kernel::sesion()->esta_seteada('solicitud_mensajes')) {
			$this->data['resultado']['mensajes'] = kernel::sesion()->get('solicitud_mensajes');
			kernel::sesion()->borrar('solicitud_mensajes');
		}

                // Si la solicitud se creo bien muestro el mensaje
		if (kernel::sesion()->esta_seteada('nueva_solicitud_ok')) {
			$this->data['nueva_solicitud_ok'] = kernel::sesion()->get('nueva_solicitud_ok');
			kernel::sesion()->borrar('nueva_solicitud_ok');
		}
                
		$this->data['url_nueva_solicitud'] = kernel::vinculador()->crear('solicitudes', 'nueva_solicitud');
		$this->data['target_nva_solicitud'] = '_self';
	}

}