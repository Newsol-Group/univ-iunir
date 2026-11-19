<?php

namespace siu\extension_kernel\formularios\elementos;


use SIU\Chulupi\interfaz\componentes\forms\elementos\FormElementoAutocomplete;

class guarani_elemento_autocomplete extends FormElementoAutocomplete
{
	function __construct($nombre, $def)
	{
		parent::__construct($nombre, $def);
	}



	/**
	 * @return guarani_vista_autocompletar
	 */
	function get_vista_helper()
	{
		//Overrideo para devolver el subtipo de vista que se usa
		return parent::get_vista_helper();
	}


}
?>
