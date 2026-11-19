<?php

namespace siu\operaciones\test_mail\form_test;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;

class form_prueba extends form
{
	function get_id_html()
	{
		return 'form_prueba';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('test_mail', 'probar_mail', array('form_test'));
	}

	protected function generar_definicion()
	{
		$this->set_campo('e_mail', array(
			form::label => 'prueba_mail',
			form::obligatorio => true,
			form::filtro => validador::TIPO_MAIL,
			form::filtro_params => array('allowempty' => true),
			form::elemento => array('tipo' => 'text'),
			form::largo => 50
		));
	}

}
?>
