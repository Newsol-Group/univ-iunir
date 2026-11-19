<?php

namespace siu\operaciones\censo\turno_presentacion;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\form;
use siu\operaciones\_comun\preinscripcion_form;

class form_turno extends preinscripcion_form
{
	protected $controlador;
	
	function get_id_html()
	{
		return 'form_turno';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('censo', 'guardar_turno', array($this->controlador->get_ra_turno()));
	}
	
	function set_controlador($controlador)
	{
		$this->controlador = $controlador;
	}

	protected function generar_definicion()
	{
		$this->set_campo('fecha', array(
			form::label => 'sel_turno.fecha',
			form::filtro => validador::TIPO_DATE,
			form::filtro_params => array('format' => 'd/m/Y',
				'allowempty' => true),
			form::clase_css => 'date input-small cursor-pointer',
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::readonly => 'readonly'
		));

		$this->set_campo('franja', array(
			form::label => 'sel_turno.franja',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => false,
			form::multi_options => array(), // Las opciones se cargan por AJAX en cascada
			form::readonly => 'readonly',
		));
	}

}
?>