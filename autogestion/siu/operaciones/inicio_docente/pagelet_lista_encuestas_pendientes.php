<?php

namespace siu\operaciones\inicio_docente;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;

class pagelet_lista_encuestas_pendientes extends pagelet
{
	function get_nombre()
	{
		return 'lista_encuestas_pendientes';
	}

	function prepare()
	{
		$pendientes = $this->controlador->info_encuestas_pendientes();

		foreach ($pendientes as $pendiente) {

			// No listo las encuestas para Becas
			if ($pendiente['tipo_relevamiento'] == \encuesta::tipo_relevamiento_becas)
				continue;

			$this->data['links_encuesta'][] = array(
				'nombre' => $pendiente['titulo'],
				'href' => kernel::vinculador()->crear('encuestas_kolla', 'index', $pendiente['id']),
				'id' => "kl_" . $pendiente['id'], //id para eliminar el html al contestar
			);
		}

		kernel::log()->add_debug('encuestas_pendientes', $this->data);
	}

}
?>