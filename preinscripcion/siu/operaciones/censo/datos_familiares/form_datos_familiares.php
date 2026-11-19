<?php

namespace siu\operaciones\censo\datos_familiares;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\modelo\util;
use siu\operaciones\_comun\preinscripcion_form;

class form_datos_familiares extends preinscripcion_form
{
	function get_id_html()
	{
		return 'form_datos_familiares';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('censo', 'guardar', array('datos_familiares'));
	}

	protected function generar_definicion()
	{
		//---- Situación familiar ----------------------------------------------
		$this->set_campo('estado_civil', array(
			form::label => 'estado_civil',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_estados_civiles(),
			form::valor_default => ''
		));

		$this->set_campo('alu_est_civil_uh', array(
			form::label => 'alu_est_civil_uh',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('cant_hijos_alum', array(
			form::label => 'cant_hijos_alum',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_cant_hijos_o_fam_a_cargo(),
		));

		$this->set_campo('cant_fami_cargo', array(
			form::label => 'cant_fami_cargo',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_cant_hijos_o_fam_a_cargo(),
		));

		//---- Datos del padre -------------------------------------------------
		$this->set_campo('apellido_padre', array(
			form::label => 'apellido_padre',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));

		$this->set_campo('nombres_padre', array(
			form::label => 'nombres_padre',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));

		$this->set_campo('padre_vive', array(
			form::label => 'padre_vive',
			form::obligatorio => false,
			form::filtro => validador::TIPO_ALPHA,
			form::filtro_params => array('allowempty' => true),
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_si_no_desconoce(true)
		));

		$this->set_campo('ult_est_cur_padre', array(
			form::label => 'ult_est_cur_padre',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_niveles_estudio(),
			form::valor_default => ''
		));

		$this->set_campo('sit_laboral_padre', array(
			form::label => 'sit_laboral_padre',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_sit_laboral_padres(),
			form::valor_default => '',
			form::clase_css => 'combo_largo'
		));

		$this->set_campo('padre_trab_hace', array(
			form::label => 'padre_trab_hace',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_padres_trab_hace(),
			form::valor_default => ''
		));

		$this->set_campo('padre_trab_ocup', array(
			form::label => 'padre_trab_ocup',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_trab_ocup(true),
			form::valor_default => ''
		));

		$this->set_campo('padre_trab_tarea', array(
			form::label => 'padre_trab_tarea',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 255,
			form::clase_css => 'input_largo',
		));

		$this->set_campo('padre_trab_otro', array(
			form::label => 'padre_trab_otro',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_otra_ocupacion(),
			form::valor_default => ''
		));

		//---- Datos de la madre -----------------------------------------------
		$this->set_campo('apellido_madre', array(
			form::label => 'apellido_madre',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));

		$this->set_campo('nombres_madre', array(
			form::label => 'nombres_madre',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));

		$this->set_campo('madre_vive', array(
			form::label => 'madre_vive',
			form::obligatorio => false,
			form::filtro => validador::TIPO_ALPHA,
			form::filtro_params => array('allowempty' => true),
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_si_no_desconoce(true)
		));

		$this->set_campo('ult_est_cur_madre', array(
			form::label => 'ult_est_cur_madre',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_niveles_estudio(),
			form::valor_default => ''
		));

		$this->set_campo('sit_laboral_madre', array(
			form::label => 'sit_laboral_madre',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_sit_laboral_padres(),
			form::valor_default => '',
			form::clase_css => 'combo_largo'
		));

		$this->set_campo('madre_trab_hace', array(
			form::label => 'madre_trab_hace',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_padres_trab_hace(),
			form::valor_default => ''
		));

		$this->set_campo('madre_trab_ocup', array(
			form::label => 'madre_trab_ocup',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_trab_ocup(true),
			form::valor_default => '',
		));

		$this->set_campo('madre_trab_tarea', array(
			form::label => 'madre_trab_tarea',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 255,
			form::clase_css => 'input_largo',
		));

		$this->set_campo('madre_trab_otro', array(
			form::label => 'madre_trab_otro',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_otra_ocupacion(),
			form::valor_default => ''
		));
	}

}
?>