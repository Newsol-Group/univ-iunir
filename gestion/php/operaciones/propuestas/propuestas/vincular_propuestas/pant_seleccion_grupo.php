<?php
class pant_seleccion_grupo extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		if ($this->controlador()->hay_historial()) {
			
			$parametros = [];
			
			$propuesta = $this->controlador()->get_propuesta();
			$parametros['propuesta'] = $propuesta;
			
			$plan = $this->controlador()->get_plan();
			// El plan puede venir vacío
			if (!empty($plan)) {
				$parametros['plan'] = $plan;
			}
			
			$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '32000037', $parametros, ['celda_memoria' => 'celda_historial']);
			$javascript = "abrir_popup('historial', '$vinculo', {'width':'900','height':'420','scrollbars':'yes'})";
			echo "<div style='text-align: right; font-weight: bold;'><a href='#' onclick=\"$javascript\">Ver historial</a></div>";
		}
		parent::generar_layout();
	}
}