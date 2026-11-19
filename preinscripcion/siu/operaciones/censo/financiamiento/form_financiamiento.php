<?php

namespace siu\operaciones\censo\financiamiento;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\preinscripcion_form;

class form_financiamiento extends preinscripcion_form
{
	function get_id_html()
	{
		return 'form_financiamiento';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('censo', 'guardar', array('financiamiento'));
	}

	protected function generar_definicion()
	{
		//---- Fuentes de financiamiento ---------------------------------------

		$this->set_campo('alu_cos_est_ap_fam', array(
			form::label => 'alu_cos_est_ap_fam',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_cos_est_plsoc', array(
			form::label => 'alu_cos_est_plsoc',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_cos_est_trab', array(
			form::label => 'alu_cos_est_trab',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_cos_est_beca', array(
			form::label => 'alu_cos_est_beca',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_cos_est_otra', array(
			form::label => 'alu_cos_est_otra',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_cos_est_espec', array(
			form::label => 'alu_cos_est_espec',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 255
		));

		//---- Fuentes de la beca ----------------------------------------------

		$this->set_campo('tiene_beca', array(
			form::label => 'tiene_beca',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'hidden'),
			form::valor_default => ''
		));

		$this->set_campo('tiene_beca_univ', array(
			form::label => 'tiene_beca_univ',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('tiene_beca_inter', array(
			form::label => 'tiene_beca_inter',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('tiene_beca_nacio', array(
			form::label => 'tiene_beca_nacio',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_beca_prov', array(
			form::label => 'alu_beca_prov',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_beca_muni', array(
			form::label => 'alu_beca_muni',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_beca_otra', array(
			form::label => 'alu_beca_otra',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		//---- Tipos de beca ---------------------------------------------------

		$this->set_campo('alu_beca_tipo_eco', array(
			form::label => 'alu_beca_tipo_eco',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_beca_tipo_ser', array(
			form::label => 'alu_beca_tipo_ser',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_beca_tipo_inv', array(
			form::label => 'alu_beca_tipo_inv',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		//---- Tipos de ayuda económica ----------------------------------------

		$this->set_campo('alu_beca_eco_tran', array(
			form::label => 'alu_beca_eco_tran',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_beca_eco_efec', array(
			form::label => 'alu_beca_eco_efec',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_beca_eco_foto', array(
			form::label => 'alu_beca_eco_foto',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_beca_eco_habi', array(
			form::label => 'alu_beca_eco_habi',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_beca_eco_come', array(
			form::label => 'alu_beca_eco_come',
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
