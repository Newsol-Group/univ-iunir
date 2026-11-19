<?php
class pant_info extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<center><table width="100%"><tr><td align="center">';
		$this->dep('cuadro_docentes')->generar_html();
		echo '</td><td align="center">';
		//@TODO Habilitar cuando se defina el modelo de datos
		//$this->dep('cuadro_horarios')->generar_html();
		echo '</td></tr></table></center>';
	}

}
?>