<?php
/**
 * Representa una Comisión con sus Subcomsiones
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class cn_ent_generacion_comisiones extends cn_procesos_bk
{
    function cargar_atributos_parametros($propuestas, $planes = null) {
        $operacion = guarani::get_id_operacion ();

        // Se eliminan propuestas repetidas (por estar en más de un plan)
        $propuestas_plano = array();
        $plan_plano = array();
        foreach ($propuestas as $propuesta) {
            if (!in_array ($propuesta, $propuestas_plano)) {
                $propuestas_plano[] = $propuesta;
            }
        }
        foreach ($planes as $plan) {
            if (!in_array ($plan, $plan_plano)) {
                $plan_plano[] = $plan;
            }
        }

        $propuestas = $propuestas_plano;
        $plan = $plan_plano;

        guarani::cache_parametros () -> set_atributos_operacion ($operacion, parametro::nivel_plan, $plan);
        guarani::cache_parametros () -> set_atributos_operacion ($operacion, parametro::nivel_propuesta, $propuestas);
       
    }

    function ejecutar_proceso($datos) {
        $this -> s__proceso = guarani::proceso_bk (pro_generacion_comisiones::nombre);
        $this -> s__proceso -> set_datos ($datos);
        guarani::ejecutar_proceso_bk ($this -> s__proceso);
    }

}
?>