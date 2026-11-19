<?php
class pant_edicion_llamado extends toba_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('formulario')->generar_html();
		echo '<hr />';
		echo '<fieldset><legend>Vinculado a</legend><br>';		
		$this->dep('asociados')->generar_html();
		$this->dep('filtro')->generar_html();
		$this->dep('disponible')->generar_botones();
		$this->dep('disponible')->evento('agregar')->set_en_botonera(false);
		$this->dep('disponible')->generar_html();
		echo '</fieldset>';
	}

}
?>
