<?php
class pant_inicial extends toba_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_generica()->generar_html();
        echo '<br>';
        $this->controlador()->generar_boton('verificar');
        echo '<br><br>';
        $this->dep('cuadro_asignadas')->generar_html();
		$this->dep('filtro')->generar_html();
		$this->dep('cuadro_disponibles')->generar_html();
	}

}
?>