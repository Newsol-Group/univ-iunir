<?php
namespace siu\operaciones\administrar_cursos_virtuales;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_vincular extends pagelet
{
	
	function get_nombre()
	{
		return 'vincular';
	}

	function prepare()
	{
		
		$this->data = [];
		
		$operacion = kernel::ruteador()->get_id_operacion();
		$hash_comision = $this->controlador->get_hash_comision();
		$this->data['url_vincular'] = kernel::vinculador()->crear($operacion, 'vincular', $hash_comision);
		$this->add_var_js('url_volver', kernel::vinculador()->crear($operacion, 'home', $hash_comision));
		
		$params_comision = $this->controlador->get_params_comision();
		$comision = $params_comision['comision'];
		$this->controlador->modelo()->cargar_contexto_comision($comision);
		$datos_plataforma = $this->controlador->modelo()->get_datos_plataforma();
		
		$this->data['cursos'] = $this->controlador->modelo()->get_cursos_moodle_de_docentes();
		
		$this->add_mensaje_js('confirmacion_salir', kernel::traductor()->trans('moodle.confirmacion_salir'));
		$this->add_mensaje_js('aceptar', kernel::traductor()->trans('aceptar'));
		$this->add_mensaje_js('cancelar', kernel::traductor()->trans('cancelar'));
		
		$this->data['mensajes'] = [];
		// Si hay mensajes a mostrar
		if (kernel::sesion()->esta_seteada('mensajes_vincular')) {
			$this->data['mensajes'] = kernel::sesion()->get('mensajes_vincular');
			kernel::sesion()->borrar('mensajes_vincular');
		}
		
		$this->data['valores'] = [];
		// Si hay valores a repopular
		if (kernel::sesion()->esta_seteada('valores_campos')) {
			$this->data['valores'] = kernel::sesion()->get('valores_campos');
			kernel::sesion()->borrar('valores_campos');
		}
		
		$this->data['errores'] = [];
		// Si hay errores a mostrar
		if (kernel::sesion()->esta_seteada('errores_campos')) {
			$this->data['errores'] = kernel::sesion()->get('errores_campos');
			kernel::sesion()->borrar('errores_campos');
		}

		// Si se trata de una subcomisión
		$this->data['es_subcomision'] = $this->controlador->es_subcomision();
	}
}