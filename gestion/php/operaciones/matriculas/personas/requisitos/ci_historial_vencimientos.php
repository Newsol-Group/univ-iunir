<?php
class ci_historial_vencimientos extends guarani_pers_ci
{

	function conf__formulario(toba_ei_formulario $form)
	{
		$dato = toba::memoria()->get_dato('requisito');
		$form->set_datos($dato);
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$pre_vencidas = toba::memoria()->get_dato('pres_vencidas');
		$cuadro->set_datos($pre_vencidas);
	}

}

?>