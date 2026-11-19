<?php
class ci_info_ultima_inscripcion_pf extends guarani_pers_ci
{
	function conf__formulario(toba_ei_formulario $form)
	{
		$dato = toba::memoria()->get_dato('info_propuesta');
		$fila = toba::memoria()->get_parametro('info_propuesta');
		$datos = toba::consulta_php('co_periodos_de_inscripcion')->get_datos_periodo_insc($dato[$fila]['propuesta_aspira']);
		$datos = array_merge($dato[$fila],$datos);
		$form->set_datos($datos);

	}




	
}

?>