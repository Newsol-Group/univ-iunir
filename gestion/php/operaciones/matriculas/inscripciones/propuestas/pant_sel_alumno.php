<?php
class pant_sel_alumno extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('persona_cuadro')->generar_botones();
		$this->dep('persona_cuadro')->evento('agregar_persona')->set_en_botonera(false);
		$this->dep('persona_filtro')->generar_html();
		echo '<hr />';
		$this->dep('persona_cuadro')->generar_html();
	}

}
?>