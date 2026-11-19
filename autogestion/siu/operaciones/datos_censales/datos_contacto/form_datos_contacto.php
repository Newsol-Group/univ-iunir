<?php
namespace siu\operaciones\datos_censales\datos_contacto;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use siu\operaciones\datos_censales\_comun\form_edicion;
use SIU\Chulupi\util\validador;
use siu\guarani;
use siu\modelo\datos\catalogo;

class form_datos_contacto extends form_edicion
{
	protected $modelo;
	protected $controlador;
	
	function __construct($controlador)
	{
		$this->modelo = guarani::datos_censales();
		$this->controlador = $controlador;
	}
	
	function get_id_html() 
	{
		return 'form_datos_contacto';
	}
	
	function get_action() 
	{
		return kernel::vinculador()->crear('datos_censales', 'guardar_edicion', array('datos_contacto'));
	}
	
	protected function generar_definicion() 
	{
		$this->set_campo(catalogo::id, array(
			form::label			=> 'id',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'hidden'),
			form::valor_default => ''
		));
		
		$this->set_campo('contacto_tipo', array(
			form::label			=> 'datos_censales.dc.contacto_tipo',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->controlador->get_opciones_contacto_tipo()
		));
		
		$this->set_campo('otros_contactos', array(
			form::label			=> 'datos_censales.dc.otros_contactos',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'js-input',
			form::largo			=> 200
		));
		
		$this->set_campo('email', array(
			form::label			=> 'datos_censales.dc.email',
			form::filtro		=> validador::TIPO_MAIL,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'js-input',
			form::largo			=> 255
		));
		
		$this->set_campo('telefono_codigo_area', array(
			form::label			=> 'datos_censales.dc.telefono_codigo_area',
			form::filtro		=> validador::TIPO_INT,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'input-mini',
			form::largo			=> 6
		));
		
		$this->set_campo('telefono_numero', array(
			form::label			=> 'datos_censales.dc.telefono_numero',
			form::filtro		=> validador::TIPO_INT,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> '',
			form::largo			=> 20
		));
	}
}
?>