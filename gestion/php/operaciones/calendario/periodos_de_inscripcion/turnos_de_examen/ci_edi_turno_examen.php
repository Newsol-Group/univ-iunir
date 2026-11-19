<?php
class ci_edi_turno_examen extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- turno_info -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form) 
	{
		$form->set_datos_defecto(array('turno_tipo'=>  turno_de_examen_nucleo::tipo_normal));
		$turno_examen = $this->controlador->get_turno_examen();
		if ($turno_examen && toba::consulta_php('co_mesas_de_examen')->existe_mesa_en_turno($turno_examen)) {
			$form->set_solo_lectura('anio_academico'); 
		}
		parent::conf__formulario($form);
	}
}
?>