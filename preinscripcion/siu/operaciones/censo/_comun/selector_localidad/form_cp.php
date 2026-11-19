<?php

namespace siu\operaciones\censo\_comun\selector_localidad;

use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\form;
use siu\modelo\opciones;

class form_cp extends form
{
	function get_id_html()
	{
		return 'form_cp';
	}

	function get_action()
	{
		
	}

	protected function generar_definicion()
	{
		$this->set_campo('codigo_postal', array(
			form::label => 'codigo_postal',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::multi_options => array(), // Las opciones se cargan por AJAX en cascada
			form::validar_select => true,
			form::valor_default => opciones::NO_SELECCION,
		));
	}

}
?>