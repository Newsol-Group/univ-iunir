<?php

namespace siu\operaciones\censo;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;

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

		if ($this->controlador->vista()->control_faltantes_activado()) {
			$faltantes = $this->controlador->vista()->get_campos_faltantes();
		} else {
			$faltantes = array();
		}

		foreach ($forms as $item) {
			$menu_con_urls[] = array(
				'nombre' => $item,
				'url' => kernel::vinculador()->crear($operacion, 'index', $item),
				'activo' => $this->controlador->vista()->get_id_form_activo() == $item,
				'con_faltantes' => isset($faltantes[$item])
			);
		}

		return $menu_con_urls;
	}

	function prepare()
	{
		$this->data['form_actual'] = $this->controlador->get_var('form_actual');
		if (kernel::persona()->datos()->hay_inscripcion_propuesta()) {
			$this->data['mensaje_lateral'] = 'mensaje_carga_datos';
			$this->data['mostrar_menu'] = true;
		} else {
			$this->data['mensaje_lateral'] = 'mensaje_elegir_propuesta';
			$this->data['mostrar_menu'] = false;
		}
	}

}
?>