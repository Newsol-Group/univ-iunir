<?php

namespace siu\extension_kernel\formularios\elementos;


use SIU\Chulupi\interfaz\componentes\forms\FormElemento;
use SIU\Chulupi\interfaz\componentes\forms\VistaElemento;

class guarani_vista_elemento_fijo implements VistaElemento {


	public function render(FormElemento $elemento){
		$attribs = array();
		foreach ($elemento->get_data_html5() as $key => $valor) {
			$attribs[] = "data-$key=$valor";
		}
		$string_attribs = implode(' ', $attribs);
		
		$html = "<div class='control-label'>";
		$html .=	"<dt id='{$elemento->get_id_html()}-label'>";
		$html .=		"<label for='{$elemento->get_id_html()}'>{$elemento->get_label()}</label>";
		$html .=	"</dt>";
		$html .= "</div>";
		$html .= "<div class='controls'>";
		$html .=	"<label id='{$elemento->get_id_html()}' $string_attribs>{$elemento->get_valor()}</label>";
		$html .= "</div>";
		return $html;

	}


	/**
	 * @return boolean Dice si tambien dibuja la etiqueta. Si no la dibuja, se la dibuja
	 * la vista.
	 */
	public function renderiza_label()
	{
		return true;
	}
}