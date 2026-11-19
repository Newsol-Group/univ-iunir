<?php
namespace siu\operaciones\datos_censales\datos_salud;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;

use siu\guarani;
use siu\operaciones\datos_censales\_comun\form_censo;

class form_datos_salud extends form_censo
{
	protected $modelo;
	
	function __construct()
	{
		$this->modelo = guarani::datos_censales();
	}
	
	function get_id_html() 
	{
		return 'form_datos_salud';
	}
	
	function get_action() 
	{
		return kernel::vinculador()->crear('datos_censales', 'guardar_datos_salud', array('datos_salud'));
	}
	
	protected function generar_definicion() 
	{
		$this->set_campo('cobertura_salud', array(
			form::label			=> 'datos_censales.cobertura_salud',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__cobertura_salud(),
			form::valor_default => '',
			form::clase_css		=> 'combo_medio'
		));
		
		$this->set_campo('es_celiaco', array(
			form::label => 'datos_censales.es_celiaco',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => \comunes::si,
			form::unchecked_value => \comunes::no,
		));
	}
}