<?php
namespace siu\operaciones\administrar_cursos_virtuales;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_faltan_funciones extends pagelet
{
	
	function get_nombre()
	{
		return 'faltan_funciones';
	}

	function prepare()
	{
		
		$this->data = [];
		
		$this->data['funciones_faltantes'] = $this->controlador->modelo()->get_funciones_faltantes_moodle();
		
		$this->data['mensajes'] = [];
		// Si hay mensajes a mostrar
		if (kernel::sesion()->esta_seteada('mensajes_faltan_funciones')) {
			$this->data['mensajes'] = kernel::sesion()->get('mensajes_faltan_funciones');
			kernel::sesion()->borrar('mensajes_faltan_funciones');
		}

		// Si se trata de una subcomisión
		$this->data['es_subcomision'] = $this->controlador->es_subcomision();
	}
}
