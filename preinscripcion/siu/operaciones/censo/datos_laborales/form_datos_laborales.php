<?php

namespace siu\operaciones\censo\datos_laborales;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\modelo\util;
use siu\operaciones\_comun\preinscripcion_form;

class form_datos_laborales extends preinscripcion_form
{
	function get_id_html()
	{
		return 'form_datos_laborales';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('censo', 'guardar', array('datos_laborales'));
	}

	protected function generar_definicion()
	{
		//---- Datos Personales ------------------------------------------------

		$this->set_campo('existe_trab_alum', array(
			form::label => 'existe_trab_alum',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_existe_trab_alu(),
			form::valor_default => '',
			form::clase_css => 'combo_largo',
		));

		$this->set_campo('alu_trab_hace', array(
			form::label => 'alu_trab_hace',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_alu_trab_hace(),
			form::valor_default => '',
			form::clase_css => 'combo_largo',
		));

		$this->set_campo('alu_trab_ocup', array(
			form::label => 'alu_trab_ocup',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_trab_ocup(),
			form::valor_default => '',
			form::clase_css => 'combo_largo',
		));

		$this->set_campo('hora_sem_trab_alum', array(
			form::label => 'hora_sem_trab_alum',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_hora_sem_trab_alum(),
			form::valor_default => ''
		));

		$this->set_campo('rel_trab_propuesta', array(
			form::label => 'rel_trab_propuesta',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_rel_trab_propuesta(),
			form::valor_default => ''
		));

		$this->set_campo('alu_trab_tarea', array(
			form::label => 'alu_trab_tarea',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 255,
			form::clase_css => 'input_largo',
		));

		//---- Datos adicionales -----------------------------------------------

		$this->set_campo('alu_trab_desjub', array(
			form::label => 'alu_trab_desjub',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('alu_trab_fami', array(
			form::label => 'alu_trab_fami',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('remuneracion', array(
			form::label => 'remuneracion',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		//----------------------------------------------------------------------
	}

}