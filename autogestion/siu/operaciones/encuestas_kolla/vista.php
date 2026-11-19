<?php
namespace siu\operaciones\encuestas_kolla;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
    function ini()
    {
		$pendientes = kernel::persona()->get_encuestas_pendientes();
		
		if (!empty($pendientes))
		{
			
			$mostrar_listado = true;

			// Trato de obtener la encuesta seleccionada
			$pendiente_id = kernel::request()->getParam('__a', null);
			$pedida = $this->validar_esta_pendiente($pendiente_id, $pendientes);
			
			// Si hay una encuesta seleccionada y es del tipo beca, no muestro el listado
			if (!is_null($pedida) && ($pedida['tipo_relevamiento'] == \encuesta::tipo_relevamiento_becas)) {
				$mostrar_listado = false;
			}

			if ($mostrar_listado) {
				$clase = 'operaciones\encuestas_kolla\pagelet_listado_encuestas';
				$pl = kernel::localizador()->instanciar($clase, 'listado_encuestas');
				$this->add_pagelet($pl);
				$this->set_template('template');
			} else {
				$this->set_template('template_solo_encuesta');
			}

			$clase = 'operaciones\encuestas_kolla\pagelet_encuesta';
			$pl = kernel::localizador()->instanciar($clase, 'encuesta');
			$this->add_pagelet($pl);

		} else {
			$this->set_template('template_vacio');
		}

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_encuestas_kolla"));	
	
    }

	/**
	 * Busco en la lista de pendientes si hay una con el id seleccionado
	 * @return mixed la fila si existe, sino null.
	 */
	private function validar_esta_pendiente($pendiente_id, $pendientes){
		foreach ($pendientes as $key => $encuesta){	
			if($encuesta['id'] == $pendiente_id	) 
			{
				return $pendientes[$key];
			}
		}
		return null;
	}
}
?>