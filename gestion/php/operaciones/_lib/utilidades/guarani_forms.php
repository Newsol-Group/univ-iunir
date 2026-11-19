<?php

/**
 * Utilidades Formularios
 * 
 * @package Centrales
 * @subpackage Utilidades
 */

class guarani_forms
{
	/*
	 * Devuelve un array o un string con la definición del array de los datos definidos como obligatorios
	 */

	static function get_datos_obligatorios(toba_ei_formulario $form,$return_txt=false)
	{
		$efs = array();
		$datos_obligatorios = array();
		foreach($form->get_nombres_ef() as $ef) {
			if ($form->ef($ef)->es_obligatorio()) {
				$efs[] = "'".$form->ef($ef)->get_id()."' => '".$form->ef($ef)->get_etiqueta()."'";
				$datos_obligatorios[$form->ef($ef)->get_id()] = $form->ef($ef)->get_etiqueta();
			}
		}
		$datos_obligatorios_txt = ' array('.implode(', ',$efs).') ';
		return $return_txt ? $datos_obligatorios_txt : $datos_obligatorios;
	}
	
}

?>