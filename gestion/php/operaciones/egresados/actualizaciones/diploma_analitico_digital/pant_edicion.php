<?php
class pant_edicion extends toba_ei_pantalla
{
	function generar_layout()
	{
		echo '<style>';
		echo '#cont_ef_form_14000197_filtro_sidcersolicitud {margin-left: 270px !important;}';
		echo '</style>';
        $this->controlador()->ev_info()->generar_html();
        echo "<hr />";
		$this->dep('editor')->generar_html();
	}

}
?>