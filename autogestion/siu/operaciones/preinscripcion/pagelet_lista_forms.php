<?php

namespace siu\operaciones\preinscripcion;

use \SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_lista_forms extends pagelet
{
	function get_nombre()
	{
		return 'lista_forms';
	}

	function get_menu()
	{
		$operacion = kernel::ruteador()->get_id_operacion();
		$menu_con_urls = array();
		$forms = $this->controlador->vista()->get_forms();
		$propuesta = $this->controlador->modelo()->get_datos_preinscripciones();

		$insc_solicitud_informacion =\preinscripcion::insc_solicitud_informacion;
		$insc_nueva = \preinscripcion::insc_nueva;
		$estados_propuesta = array_column($propuesta, 'estado');
                $propuesta_insc_insc_nueva = in_array(\preinscripcion::insc_nueva, $estados_propuesta);

		foreach ($forms as $item) {
			if ((($item=='propuesta'))or ($item!=='propuesta')  && ($propuesta_insc_insc_nueva)) {
				$menu_con_urls[] = array(
					'nombre' => 'preinscripcion.' . $item,
					'url' => kernel::vinculador()->crear($operacion, 'index', $item),
					'activo' => $this->controlador->vista()->get_id_form_activo() == $item,
				);
			}
		}

		return $menu_con_urls;
	}

	function prepare()
	{
		
	}

}
?>