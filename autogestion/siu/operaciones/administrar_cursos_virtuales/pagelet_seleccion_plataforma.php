<?php
namespace siu\operaciones\administrar_cursos_virtuales;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_seleccion_plataforma extends pagelet
{
	
	function get_nombre()
	{
		return 'seleccion_plataforma';
	}

	function prepare()
	{
		
		$this->data = [];
		
		$operacion = kernel::ruteador()->get_id_operacion();
		$hash_comision = $this->controlador->get_hash_comision();
		$this->data['url_seleccionar_plataforma'] = kernel::vinculador()->crear($operacion, 'seleccionar_plataforma', $hash_comision);
		
		$params_comision = $this->controlador->get_params_comision();
		$comision = $params_comision['comision'];
		$this->controlador->modelo()->set_comision($comision);
		$this->data['plataformas'] = $this->controlador->modelo()->get_plataformas();
		
		$this->data['mensajes'] = [];
		// Si hay mensajes a mostrar
		if (kernel::sesion()->esta_seteada('mensajes_seleccionar_plataforma')) {
			$this->data['mensajes'] = kernel::sesion()->get('mensajes_seleccionar_plataforma');
			kernel::sesion()->borrar('mensajes_seleccionar_plataforma');
		}
		
		if (kernel::sesion()->esta_seteada('mensajes_sin_curso')) {
			$this->data['mensajes'] = kernel::sesion()->get('mensajes_sin_curso');
			kernel::sesion()->borrar('mensajes_sin_curso');
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