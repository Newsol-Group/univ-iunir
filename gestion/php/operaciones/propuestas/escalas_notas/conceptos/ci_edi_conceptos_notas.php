<?php 
/**
 *
 * @package Propuestas
 * @subpackage Escalas de Notas
 */
class ci_edi_conceptos_notas extends ci_edicion_simple
{
	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos();
		}
		$datos['activo'] = (isset($datos['estado']) && ($datos['estado'] == comunes::estado_activo)) ? comunes::si : comunes::no;
		$form->set_datos($datos);
	}
}