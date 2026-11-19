<?php

namespace siu\extension_kernel\formularios\elementos;


use SIU\Chulupi\interfaz\componentes\forms\elementos\FormElementoMl;

class guarani_elemento_ml extends FormElementoMl
{

	function __construct($nombre)
	{
		parent::__construct($nombre);
	}



	/**
	 * @return guarani_vista_ml
	 */
	function get_vista_helper()
	{
		//Overrideo para devolver el subtipo de vista que se usa
		return parent::get_vista_helper();
	}


}
?>
