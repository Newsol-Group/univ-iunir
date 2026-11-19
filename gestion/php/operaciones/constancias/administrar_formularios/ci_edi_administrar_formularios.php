<?php
/**
 *
 * 
 * @package 	Constancias y Certificados
 * 
 */
class ci_edi_administrar_formularios extends ci_edicion_simple
{
	// Si la entidad no es nueva (se está en edicion), el campo objeto no es editable.
	function conf__formulario(toba_ei_formulario $form)
	{
		$form->ef('objeto')->set_expreg(comunes::er_numero_entero);
		if (!$this->cn()->entidad_es_nueva()) {
			$form->ef('objeto')->set_solo_lectura(true);	
		}
		parent::conf__formulario($form);
	}
}
?>