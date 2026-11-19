<?php
namespace siu\operaciones\datos_censales\financiamiento;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;

use siu\guarani;
use siu\operaciones\datos_censales\_comun\form_censo;

class form_financiamiento extends form_censo
{
	protected $modelo;
	
	function __construct()
	{
		$this->modelo = guarani::datos_censales();
	}
	
	function get_id_html() 
	{
		return 'form_financiamiento';
	}
	
	function get_action() 
	{
		return kernel::vinculador()->crear('datos_censales', 'guardar', array('financiamiento'));
	}
	
	protected function generar_definicion() 
	{
		//---- Fuentes de financiamiento ---------------------------------------
		$this->set_campo('costeo_estudios_familiar', array(
			form::label				=> 'datos_censales.costeo_estudios_familiar',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('costeo_estudios_plan_social', array(
			form::label				=> 'datos_censales.costeo_estudios_plan_social',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('costeo_estudios_trabajo', array(
			form::label				=> 'datos_censales.costeo_estudios_trabajo',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('costeo_estudios_beca', array(
			form::label				=> 'datos_censales.costeo_estudios_beca',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('costeo_estudios_otro', array(
			form::label				=> 'datos_censales.costeo_estudios_otro',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('costeo_estudios_descripcion', array(
			form::label			=> 'datos_censales.costeo_estudios_descripcion',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 255,
			form::clase_css		=> 'input-xxlarge'
		));
		
		//---- Fuentes de la beca ----------------------------------------------
		$this->set_campo('beca', array( 
			form::label			=> 'datos_censales.beca',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'hidden'),
			form::largo			=> 1,
			form::valor_default => ''
		));
		
		$this->set_campo('beca_fuente_universidad', array(
			form::label				=> 'datos_censales.beca_fuente_universidad',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('beca_fuente_nacional', array(
			form::label				=> 'datos_censales.beca_fuente_nacional',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('beca_fuente_provincial', array(
			form::label				=> 'datos_censales.beca_fuente_provincial',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('beca_fuente_municipal', array(
			form::label				=> 'datos_censales.beca_fuente_municipal',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('beca_fuente_internacional', array(
			form::label				=> 'datos_censales.beca_fuente_internacional',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('beca_fuente_otra', array(
			form::label				=> 'datos_censales.beca_fuente_otra',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
	
		//---- Tipos de beca ---------------------------------------------------
		$this->set_campo('beca_tipo_economica', array(
			form::label				=> 'datos_censales.beca_tipo_economica',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('beca_tipo_servicios', array(
			form::label				=> 'datos_censales.beca_tipo_servicios',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('beca_tipo_investigacion', array(
			form::label				=> 'datos_censales.beca_tipo_investigacion',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		//---- Tipos de ayuda económica ----------------------------------------
		$this->set_campo('beca_economica_transporte', array(
			form::label				=> 'datos_censales.beca_economica_transporte',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('beca_economica_efectivo', array(
			form::label				=> 'datos_censales.beca_economica_efectivo',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('beca_economica_fotocopias', array(
			form::label				=> 'datos_censales.beca_economica_fotocopias',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('beca_economica_habitacional', array(
			form::label				=> 'datos_censales.beca_economica_habitacional',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('beca_economica_comedor', array(
			form::label				=> 'datos_censales.beca_economica_comedor',
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