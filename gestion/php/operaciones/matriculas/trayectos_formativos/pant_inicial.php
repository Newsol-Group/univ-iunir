<?php
class pant_inicial extends toba_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('filtro')->generar_html();
		if ($this->existe_dependencia('cuadro')) {
			if ($this->controlador()->get_valor_filtro() != '') {
				echo '<fieldset><legend>Datos Personales</legend>';
					$this->controlador()->ev_info()->generar_html();
				echo '</fieldset>';
				echo '<fieldset><legend>Datos Académicos</legend><br>';				
					$this->dep('cuadro')->generar_html();
				echo '</fieldset>';
			} else {
				$this->dep('cuadro')->generar_html();
			}
		}
	}

}
?>