<?php

namespace siu\operaciones\censo\datos_salud;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\modelo\util;
use siu\operaciones\_comun\preinscripcion_form;
use siu\modelo\comunes;

class form_datos_salud extends preinscripcion_form
{
	function get_id_html()
	{
		return 'form_datos_salud';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('censo', 'guardar', ['datos_salud']);
	}

	protected function generar_definicion()
	{
		$this->set_campo('obra_social_alu', array(
			form::label => 'obra_social_alu',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_obra_social(),
			form::valor_default => '',
			form::clase_css => 'span12'
		));
		
		$this->set_campo('es_celiaco', array(
			form::label => 'es_celiaco',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => comunes::si,
			form::unchecked_value => comunes::no,
		));
	}

}
