<?php

namespace siu\extension_kernel\formularios\elementos;


use SIU\Chulupi\interfaz\componentes\forms\FormElemento;
use SIU\Chulupi\interfaz\componentes\forms\VistaElemento;

class guarani_vista_botonera implements VistaElemento {


	public function render(FormElemento $elemento){
		 $elem = $elemento->get_zend_element();

		 return "<div class='botonera btn-group' id='{$elem->getId()}'>".$elem->renderViewHelper() ."</div>"; //$elemento->get_zend_element()->renderViewHelper();
	}


	/**
	 * @return boolean Dice si tambien dibuja la etiqueta. Si no la dibuja, se la dibuja
	 * la vista.
	 */
	public function renderiza_label()
	{
		return false;
	}
}