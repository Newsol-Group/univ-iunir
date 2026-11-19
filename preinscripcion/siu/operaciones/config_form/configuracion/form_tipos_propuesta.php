<?php

namespace siu\operaciones\config_form\configuracion;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\modelo\util;

class form_tipos_propuesta extends form
{
	function get_id_html()
	{
		return 'form_tipos_propuesta';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('config_form', 'seleccionar_tipo_propuesta', array('configuracion'));
	}

	protected function generar_definicion()
	{
		$this->set_campo('tipo_de_propuesta', array(
			form::label => 'tipo_de_propuesta',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => false),
			form::elemento => array('tipo' => 'select'),
			form::multi_options => util::opciones()->tipos_propuestas(),
		));
	}

}
?>
