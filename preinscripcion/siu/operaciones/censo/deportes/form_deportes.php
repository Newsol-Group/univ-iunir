<?php

namespace siu\operaciones\censo\deportes;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\preinscripcion_form;

class form_deportes extends preinscripcion_form
{
	function get_id_html()
	{
		return 'form_deportes';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('censo', 'guardar', array('deportes'));
	}

	protected function generar_definicion()
	{
		$this->set_campo('practica_deportes', array(
			form::label => 'practica_deportes',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		//---- Lugares de práctica de deportes ---------------------------------
		$this->set_campo('hace_dep_gim_priv', array(
			form::label => 'hace_dep_gim_priv',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('hace_dep_univ', array(
			form::label => 'hace_dep_univ',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('hace_dep_partic', array(
			form::label => 'hace_dep_partic',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('hace_dep_otros', array(
			form::label => 'hace_dep_otros',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		//---- Deportes --------------------------------------------------------
		$this->set_campo('prac_dep_futbol', array(
			form::label => 'prac_dep_futbol',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('prac_dep_basquet', array(
			form::label => 'prac_dep_basquet',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('prac_dep_voley', array(
			form::label => 'prac_dep_voley',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('prac_dep_gimnasia', array(
			form::label => 'prac_dep_gimnasia',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('prac_dep_tenis', array(
			form::label => 'prac_dep_tenis',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('prac_dep_natacion', array(
			form::label => 'prac_dep_natacion',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('prac_dep_handball', array(
			form::label => 'prac_dep_handball',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('prac_dep_otros', array(
			form::label => 'prac_dep_otros',
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
