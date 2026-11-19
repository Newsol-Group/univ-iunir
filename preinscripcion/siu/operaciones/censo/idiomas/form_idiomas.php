<?php

namespace siu\operaciones\censo\idiomas;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\modelo\util;
use siu\operaciones\_comun\preinscripcion_form;

class form_idiomas extends preinscripcion_form
{
	function get_id_html()
	{
		return 'form_idiomas';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('censo', 'guardar', array('idiomas'));
	}

	protected function generar_definicion()
	{
		$this->set_campo('alu_idioma_ingl', array(
			form::label => 'alu_idioma_ingl',
			form::obligatorio => false,
			form::filtro => validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_niveles_idiomas()
		));

		$this->set_campo('alu_idioma_alem', array(
			form::label => 'alu_idioma_alem',
			form::obligatorio => false,
			form::filtro => validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_niveles_idiomas()
		));

		$this->set_campo('alu_idioma_fran', array(
			form::label => 'alu_idioma_fran',
			form::obligatorio => false,
			form::filtro => validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_niveles_idiomas()
		));

		$this->set_campo('alu_idioma_ital', array(
			form::label => 'alu_idioma_ital',
			form::obligatorio => false,
			form::filtro => validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_niveles_idiomas()
		));

		$this->set_campo('alu_idioma_port', array(
			form::label => 'alu_idioma_port',
			form::obligatorio => false,
			form::filtro => validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_niveles_idiomas()
		));

		$this->set_campo('alu_idioma_chino', array(
			form::label => 'alu_idioma_chino',
			form::obligatorio => false,
			form::filtro => validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_niveles_idiomas()
		));

		$this->set_campo('alu_idioma_otro', array(
			form::label => 'alu_idioma_otro',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 20
		));
		
		$this->set_campo('alu_idioma_otro_nivel', array(
			form::label => 'alu_idioma_otro_nivel',
			form::obligatorio => false,
			form::filtro => validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_niveles_idiomas()
		));
	}

}
?>
