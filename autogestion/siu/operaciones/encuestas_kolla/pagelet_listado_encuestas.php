<?php
namespace siu\operaciones\encuestas_kolla;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_listado_encuestas extends pagelet
{
	
	public function get_nombre()
	{
        return 'listado_encuestas';
    }

	function prepare() 
	{
		$this->data['links_encuesta'] = [];
		
		$pendientes = $this->controlador->get_var('encuestas_pendientes');
		$hash_encuesta_seleccionada = $this->controlador->get_var('hash_encuesta_seleccionada');

		foreach ($pendientes as $pendiente) {
			
			// No listo las encuestas para Becas, excepto si fue seleccionada
			if (($pendiente['tipo_relevamiento'] == \encuesta::tipo_relevamiento_becas) && ($pendiente['id'] != $hash_encuesta_seleccionada)) {
				continue;
			}
			
			$this->data['links_encuesta'][] = array(
				'nombre' => $pendiente['titulo'],
				'href'   => kernel::vinculador()->crear('encuestas_kolla', 'index', $pendiente['id']),
				'id' => "kl_".$pendiente['id'], //id para eliminar el html al contestar
			);
		}
	}
}