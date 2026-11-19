<?php
namespace siu\operaciones\administrar_cursos_virtuales;

use SIU\Chulupi\interfaz\pagelet;

class pagelet_actas_cerradas extends pagelet
{
	
	function get_nombre()
	{
		return 'actas_cerradas';
	}

	function prepare()
	{
		
		$this->data = [];
		
		// Si se trata de una subcomisión
		$this->data['es_subcomision'] = $this->controlador->es_subcomision();
	}
}

