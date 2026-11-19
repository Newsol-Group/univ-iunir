<?php

namespace siu\operaciones\censo\tecnologia;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\modelo\util;
use siu\operaciones\_comun\preinscripcion_form;

class form_tecnologia extends preinscripcion_form
{
	function get_id_html()
	{
		return 'form_tecnologia';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('censo', 'guardar', array('tecnologia'));
	}

	protected function generar_definicion()
	{
		//---- Dispone de PC  --------------------------------------------------
		$this->set_campo('alu_tec_pc_casa', array(
			form::label => 'alu_tec_pc_casa',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_tec_pc_trab', array(
			form::label => 'alu_tec_pc_trab',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_tec_pc_univ', array(
			form::label => 'alu_tec_pc_univ',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_tec_pc_otro', array(
			form::label => 'alu_tec_pc_otro',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		//---- Lugares de acceso a Internet ------------------------------------
		$this->set_campo('alu_tec_int_casa', array(
			form::label => 'alu_tec_int_casa',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_tec_int_trab', array(
			form::label => 'alu_tec_int_trab',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_tec_int_univ', array(
			form::label => 'alu_tec_int_univ',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_tec_int_movil', array(
			form::label => 'alu_tec_int_movil',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_tec_int_otro', array(
			form::label => 'alu_tec_int_otro',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));
	}

}
?>
