<?php
namespace siu\operaciones\administrar_cursos_virtuales;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_sin_plataforma extends pagelet
{
	
	function get_nombre()
	{
		return 'sin_plataforma';
	}

	function prepare()
	{
		
		$this->data = [];
		
		$this->data['mensajes'] = [];
		// Si hay mensajes a mostrar
		if (kernel::sesion()->esta_seteada('mensajes_sin_plataforma')) {
			$this->data['mensajes'] = kernel::sesion()->get('mensajes_sin_plataforma');
			kernel::sesion()->borrar('mensajes_sin_plataforma');
		}

		// Si se trata de una subcomisión
		$this->data['es_subcomision'] = $this->controlador->es_subcomision();
	}
}