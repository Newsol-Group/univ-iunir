<?php

namespace siu\operaciones\copiar_config_form\copia;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\form;
use siu\modelo\util;

class form_copia extends form
{
	function get_id_html()
	{
		return 'form_copia';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('copiar_config_form', 'copiar_configuracion', array('copia'));
	}

	protected function generar_definicion()
	{
		$this->set_campo('tipo_propuesta_origen', array(
			form::label => 'tipo_propuesta_origen',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::multi_options => util::opciones()->tipos_propuestas(),
		));

		$this->set_campo('tipo_propuesta_destino', array(
			form::label => 'tipo_propuesta_destino',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::multi_options => util::opciones()->tipos_propuestas(),
		));
	}

}
?>