<?php
class pant_popup_actividades extends toba_ei_pantalla
{
	function generar_layout()
	{
        $this->controlador()->ev_info()->generar_html();
		$this->dep('filtro')->generar_html();
		$this->dep('cuadro')->generar_html();
	}

}
?>