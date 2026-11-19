<?php
namespace siu\operaciones\administrar_cursos_virtuales;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_sin_curso extends pagelet
{
	
	function get_nombre()
	{
		return 'sin_curso';
	}

	function prepare()
	{
		$this->data = [];
		$operacion = kernel::ruteador()->get_id_operacion();
		$hash_comision = $this->controlador->get_hash_comision();
		
		$this->data['url_crear'] = kernel::vinculador()->crear($operacion, 'crear', $hash_comision);
		$this->data['url_vincular'] = kernel::vinculador()->crear($operacion, 'vincular', $hash_comision);
		
		$this->data['mensajes'] = [];
		// Si hay mensajes a mostrar
		if (kernel::sesion()->esta_seteada('mensajes_sin_curso')) {
			$this->data['mensajes'] = kernel::sesion()->get('mensajes_sin_curso');
			kernel::sesion()->borrar('mensajes_sin_curso');
		}

		// Si se trata de una subcomisión
		$this->data['es_subcomision'] = $this->controlador->es_subcomision();
	}
}
