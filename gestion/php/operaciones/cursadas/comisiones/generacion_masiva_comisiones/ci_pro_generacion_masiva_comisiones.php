<?php
class ci_pro_generacion_masiva_comisiones extends ci_nav_procesos_bk
{
	function set_nombre_proceso()
	{
		$this->s__nombre_proceso = 'pro_generacion_comisiones';
	}

	function conf__form_totales(toba_ei_formulario $form)
	{
		parent::conf__form_totales($form);

		if ($form->ef('actividades_con_comisiones')->get_estado() == 0 && $form->existe_evento('rep_actividades')) {
			$form->evento('rep_actividades')->desactivar();
		}
		if ($form->ef('comisiones_con_obs')->get_estado() == 0 && $form->existe_evento('rep_comisiones_obs')) {
			$form->evento('rep_comisiones_obs')->desactivar();
		}
	}

}
?>