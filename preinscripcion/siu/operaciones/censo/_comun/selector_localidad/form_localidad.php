<?php

namespace siu\operaciones\censo\_comun\selector_localidad;

use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\form;
use siu\modelo\opciones;
use siu\modelo\util;

class form_localidad extends form
{
	function get_id_html()
	{
		return 'form_localidad';
	}

	function get_action()
	{
		
	}

	protected function generar_definicion()
	{
		$this->set_campo('pais', array(
			form::label => 'pais',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'select'),
			form::multi_options => util::opciones()->get_paises(),
			form::validar_select => true,
			form::valor_default => opciones::NO_SELECCION,
		));

		$this->set_campo('provincia', array(
			form::label => 'provincia',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'select'),
			form::multi_options => array(), // Las opciones se cargan por AJAX en cascada
			form::validar_select => true,
			form::valor_default => opciones::NO_SELECCION,
		));

		$this->set_campo('departamento', array(
			form::label => 'departamento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'select'),
			form::multi_options => array(), // Las opciones se cargan por AJAX en cascada
			form::validar_select => true,
			form::valor_default => opciones::NO_SELECCION,
		));

		$this->set_campo('localidad', array(
			form::label => 'localidad',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'select'),
			form::multi_options => array(), // Las opciones se cargan por AJAX en cascada
			form::validar_select => true,
			form::valor_default => opciones::NO_SELECCION,
		));

		$this->set_campo('codigo_postal', array(
			form::label => 'codigo_postal',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::multi_options => array(), // Las opciones se cargan por AJAX en cascada
			form::validar_select => true,
			form::valor_default => opciones::NO_SELECCION,
		));
	}

}
?>