<?php
class pant_edicion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_acta()->generar_html();		
        echo "<hr />"; 
		$this->dep('form_acta')->generar_html();
		echo '<hr />';
		$this->dep('alumnos')->generar_html();
	}
}
?>