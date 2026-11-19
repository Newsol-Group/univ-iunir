<?php
namespace siu\operaciones\datos_censales\deportes;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;

use siu\guarani;
use siu\operaciones\datos_censales\_comun\form_censo;

class form_deportes extends form_censo
{
	protected $modelo;
	
	function __construct()
	{
		$this->modelo = guarani::datos_censales();
	}
	
	function get_id_html() 
	{
		return 'form_deportes';
	}
	
	function get_action() 
	{
		return kernel::vinculador()->crear('datos_censales', 'guardar', array('deportes'));
	}
	
	protected function generar_definicion() 
	{
		$this->set_campo('deportes', array(
			form::label				=> 'datos_censales.deportes',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		//---- Lugares de práctica de deportes ---------------------------------
		$this->set_campo('deportes_universidad', array(
			form::label				=> 'datos_censales.deportes_universidad',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('deportes_gimnasio', array(
			form::label				=> 'datos_censales.deportes_gimnasio',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('deportes_particular', array(
			form::label				=> 'datos_censales.deportes_particular',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('deportes_otro_lugar', array(
			form::label				=> 'datos_censales.deportes_otro_lugar',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		//---- Deportes --------------------------------------------------------
		$this->set_campo('deportes_futbol', array(
			form::label				=> 'datos_censales.deportes_futbol',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('deportes_basquet', array(
			form::label				=> 'datos_censales.deportes_basquet',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('deportes_voley', array(
			form::label				=> 'datos_censales.deportes_voley',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('deportes_gimnasia', array(
			form::label				=> 'datos_censales.deportes_gimnasia',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('deportes_tenis', array(
			form::label				=> 'datos_censales.deportes_tenis',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('deportes_natacion', array(
			form::label				=> 'datos_censales.deportes_natacion',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('deportes_handball', array(
			form::label				=> 'datos_censales.deportes_handball',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('deportes_otros', array(
			form::label				=> 'datos_censales.deportes_otros',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
	}

}
?>