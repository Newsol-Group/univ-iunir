<?php
class pant_info extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<fieldset title="Datos de la Comisión:"><legend>Datos de la Comision:</legend>';
			$this->controlador()->ev_datos_generales()->generar_html();
		echo '</fieldset>';
		echo '<fieldset title="Instancias:"><legend>Instancias:</legend>';
			$this->dep('cuadro_instancias')->generar_html();
		echo '</fieldset>';
		echo '<fieldset title="Docentes:"><legend>Docentes:</legend>';
			$this->dep('cuadro_docentes')->generar_html();
		echo '</fieldset>';
		echo '<fieldset title="Horarios:"><legend>Horarios:</legend>';
			$this->dep('cuadro_horarios')->generar_html();
		echo '</fieldset>';
	}
}
?>