<?php

namespace siu\operaciones\censo\_comun;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\pagelet_con_form;

abstract class pagelet_con_edicion extends pagelet_con_form
{
	const ESTADO_LISTADO = 'listado';
	const ESTADO_EDICION = 'edicion';

	protected $pantalla = 'listado';

	function set_pantalla($id)
	{
		$this->pantalla = $id;
	}

	function get_accion_edicion()
	{
		return kernel::vinculador()->crear('censo', 'edicion', array($this->controlador->vista()->get_id_form_activo()));
	}

	function get_accion_volver_edicion()
	{
		return kernel::vinculador()->crear('censo', 'volver_edicion', array($this->controlador->vista()->get_id_form_activo()));
	}

}
?>