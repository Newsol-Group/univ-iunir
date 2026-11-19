<?php
namespace siu\operaciones\datos_censales\idiomas;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;

use siu\guarani;
use siu\operaciones\datos_censales\_comun\form_censo;

class form_idiomas extends form_censo
{
	protected $modelo;
	
	function __construct()
	{
		$this->modelo = guarani::datos_censales();
	}
	
	function get_id_html() 
	{
		return 'form_idiomas';
	}
	
	function get_action() 
	{
		return kernel::vinculador()->crear('datos_censales', 'guardar', array('idiomas'));
	}
	
	protected function generar_definicion() 
	{
		$this->set_campo('nivel_idioma_ingles', array(
			form::label			=> 'datos_censales.nivel_idioma_ingles',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__niveles_idiomas(),
		));
		
		$this->set_campo('nivel_idioma_aleman', array(
			form::label			=> 'datos_censales.nivel_idioma_aleman',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__niveles_idiomas(),
		));
		
		$this->set_campo('nivel_idioma_frances', array(
			form::label			=> 'datos_censales.nivel_idioma_frances',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__niveles_idiomas(),
		));
		
		$this->set_campo('nivel_idioma_italiano', array(
			form::label			=> 'datos_censales.nivel_idioma_italiano',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__niveles_idiomas(),
		));
		
		$this->set_campo('nivel_idioma_portugues', array(
			form::label			=> 'datos_censales.nivel_idioma_portugues',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__niveles_idiomas(),
		));
		
		$this->set_campo('nivel_idioma_chino', array(
			form::label			=> 'datos_censales.nivel_idioma_chino',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__niveles_idiomas(),
		));
		
		$this->set_campo('idioma_otro', array(
			form::label			=> 'datos_censales.idioma_otro',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 50
		));
		
		$this->set_campo('nivel_idioma_otro', array(
			form::label			=> 'datos_censales.nivel_idioma_otro',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__niveles_idiomas(),
		));
	}

}

?>
