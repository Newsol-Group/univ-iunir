<?php
namespace siu\operaciones\datos_censales\_comun\selector_localidad;

use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\guarani;
use siu\operaciones\datos_censales\_comun\form_edicion;

class form_localidad extends form_edicion
{
	protected $modelo;
	
	function __construct()
	{
		$this->modelo = guarani::datos_censales();
	}
	
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
			form::label			=> 'datos_censales.selector.pais',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::elemento		=> array('tipo' => 'select'),
			form::multi_options => $this->modelo->opciones__paises(),
			form::validar_select => true,
			form::valor_default => self::NO_SELECCION,
		));
		
		$this->set_campo('provincia', array(
			form::label			=> 'datos_censales.selector.provincia',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::elemento		=> array('tipo' => 'select'),
			form::multi_options => array(), // Las opciones se cargan por AJAX en cascada
			form::validar_select => true,
			form::valor_default => self::NO_SELECCION,
		));
		
		$this->set_campo('departamento', array(
			form::label			=> 'datos_censales.selector.departamento',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::elemento		=> array('tipo' => 'select'),
			form::multi_options => array(), // Las opciones se cargan por AJAX en cascada
			form::validar_select => true,
			form::valor_default => self::NO_SELECCION,
		));
		
		$this->set_campo('localidad', array(
			form::label			=> 'datos_censales.selector.localidad',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::elemento		=> array('tipo' => 'select'),
			form::multi_options => array(), // Las opciones se cargan por AJAX en cascada
			form::validar_select => true,
			form::valor_default => self::NO_SELECCION,
		));
		
		$this->set_campo('codigo_postal', array(
			form::label			=> 'datos_censales.selector.codigo_postal',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::multi_options => array(), // Las opciones se cargan por AJAX en cascada
			form::validar_select => true,
			form::valor_default => self::NO_SELECCION,
		));
	}
}
?>