<?php

namespace siu\extension_kernel\formularios\elementos;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use siu\extension_kernel\formularios\guarani_form_elemento;
use SIU\Chulupi\util\validador;

class guarani_elemento_timepicker extends guarani_form_elemento
{
	private $intervalo = 15;
	
	function __construct($nombre, $def)
	{
		// Seteos por defecto del timepicker
		$def_timepicker = array(
			FormElementoConfig::valor_default => null,
			FormElementoConfig::filtro		=>  validador::TIPO_TIME,
			FormElementoConfig::filtro_params => array('format' => 'H:i',
														 'allowempty' => true),
			FormElementoConfig::elemento		=> array('tipo' => 'select'),
			FormElementoConfig::validar_select => true,
			FormElementoConfig::clase_css		=> 'timepicker',
		);
		$def = array_merge($def_timepicker, $def);
		parent::__construct($nombre, $def);
	}
		
	function get_multi_options()
	{
		return $this->get_campo_def(FormElementoConfig::multi_options, $this->get_horas_minutos($this->nombre, $this->nombre, $this->get_intervalo()));
	}
	
	static function get_horas_minutos($campo_clave, $campo_valor, $intervalo = null, $con_no_seleccion=true)
	{
		$datos = array();
		for($i = 0; $i < 24; $i++){
			for($j = 0; $j < 60; $j=$j+$intervalo){
				$hora = ($i < 10) ? "0".($i) : ($i);
				$min = ($j < 10) ? "0".($j) : ($j);
				$datos[$hora.":".$min] = array($campo_clave => $hora.":".$min, $campo_valor => $hora.":".$min);
			}
		}
		return static::armar_combo_opciones($datos, $campo_clave, $campo_valor, $con_no_seleccion, true, "hh:mm");
	}
	
	function set_intervalo ($intervalo)
	{
		$this->intervalo = $intervalo;
	}
	
	private function get_intervalo ()
	{
		return $this->intervalo;
	}
}
?>