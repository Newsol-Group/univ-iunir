<?php
namespace siu\operaciones\datos_censales\_comun\selector_localidad;

use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\guarani;
use siu\operaciones\datos_censales\_comun\form_edicion;

class form_cp extends form_edicion
{
	protected $modelo;
	
	function __construct()
	{
		$this->modelo = guarani::datos_censales();
	}
	
	function get_id_html() 
	{
		return 'form_cp';
	}
	
	function get_action() 
	{
	}
	
	protected function generar_definicion() 
	{
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