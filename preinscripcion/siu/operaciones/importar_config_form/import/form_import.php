<?php

namespace siu\operaciones\importar_config_form\import;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\preinscripcion_form;

class form_import extends preinscripcion_form
{
	function get_id_html()
	{
		return 'form_import';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('importar_config_form', 'importar_configuracion', array('import'));
	}

	protected function generar_definicion()
	{
		$this->set_campo('dbname', array(
			form::label => 'import.db.dbname',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
		));

		$this->set_campo('host', array(
			form::label => 'import.db.host',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
		));

		$this->set_campo('port', array(
			form::label => 'import.db.port',
			form::filtro => validador::TIPO_INT,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
		));

		$this->set_campo('pdo_user', array(
			form::label => 'import.db.pdo_user',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
		));

		$this->set_campo('pdo_passwd', array(
			form::label => 'import.db.pdo_passwd',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'password'),
		));
	}

}
?>