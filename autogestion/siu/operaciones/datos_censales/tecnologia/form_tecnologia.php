<?php
namespace siu\operaciones\datos_censales\tecnologia;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;

use siu\guarani;
use siu\operaciones\datos_censales\_comun\form_censo;

class form_tecnologia extends form_censo
{
	protected $modelo;
	
	function __construct()
	{
		$this->modelo = guarani::datos_censales();
	}
	
	function get_id_html() 
	{
		return 'form_tecnologia';
	}
	
	function get_action() 
	{
		return kernel::vinculador()->crear('datos_censales', 'guardar', array('tecnologia'));
	}
	
	protected function generar_definicion() 
	{
		//---- Dispone de PC  --------------------------------------------------
		$this->set_campo('tecnologia_pc_casa', array(
			form::label				=> 'datos_censales.tecnologia_pc_casa',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('tecnologia_pc_trabajo', array(
			form::label				=> 'datos_censales.tecnologia_pc_trabajo',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('tecnologia_pc_universidad', array(
			form::label				=> 'datos_censales.tecnologia_pc_universidad',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('tecnologia_pc_otro_lugar', array(
			form::label				=> 'datos_censales.tecnologia_pc_otro_lugar',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		//---- Lugares de acceso a Internet ------------------------------------
		$this->set_campo('tecnologia_int_casa', array(
			form::label				=> 'datos_censales.tecnologia_int_casa',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('tecnologia_int_trabajo', array(
			form::label				=> 'datos_censales.tecnologia_int_trabajo',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('tecnologia_int_universidad', array(
			form::label				=> 'datos_censales.tecnologia_int_universidad',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('tecnologia_int_movil', array(
			form::label				=> 'datos_censales.tecnologia_int_movil',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('tecnologia_int_otro_lugar', array(
			form::label				=> 'datos_censales.tecnologia_int_otro_lugar',
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