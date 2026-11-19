<?php
namespace siu\extension_kernel\formularios\elementos;

use SIU\Chulupi\interfaz\componentes\forms\FormAccion;
use SIU\Chulupi\interfaz\componentes\forms\VistaAccion;

class guarani_accion_boton implements VistaAccion
{


	public function render(FormAccion $a)
	{

		$html = "<button id='$a->id' type='$a->type' class='btn btn-small'";

		if (!empty($a->name)) {
			$html .= " name='$a->name'";
		}

		$html .= "> $a->innerHtml</button>";

		return $html;
	}

}