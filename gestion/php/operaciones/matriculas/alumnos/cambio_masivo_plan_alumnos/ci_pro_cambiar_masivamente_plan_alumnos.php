<?php
/**
 *
 *
 * @package		Matrícula
 * @subpackage 	Alumnos
 * 
 */
class ci_pro_cambiar_masivamente_plan_alumnos extends ci_nav_procesos_bk
{
    function set_nombre_proceso() 
    {
        $this->s__nombre_proceso = 'pro_cambiar_masivamente_plan_alumnos';
    }
    
    function conf__form_totales(toba_ei_formulario $form)
	{
		$form->set_solo_lectura();
		if (isset($this->s__datos_resultados)) {
			$form->set_datos($this->s__datos_resultados);
			if ($this->modo_visualizacion()){
				foreach (array_keys($this->s__datos_resultados) as $ef) {
					if ($form->existe_ef($ef) && $form->existe_evento('rep_'.$ef) && $form->ef($ef)->get_estado() == 0)
						$form->evento('rep_'.$ef)->desactivar();
				}
                if ($this->s__datos_resultados['cant_equiv_otorgadas'] == 0) {
                    if ($form->existe_evento('rep_equiv_por_alumno')) {
                        $form->evento('rep_equiv_por_alumno')->desactivar();
                    }
                    if ($form->existe_evento('rep_equiv_por_actividad')) {
                        $form->evento('rep_equiv_por_actividad')->desactivar();
                    }
                }
			}
		}
	}

}
?>