<?php
/**
 *
 * @package 	Propuestas 
 * @subpackage 	Actualizaciones
 * 
 */
class pant_detalle extends guarani_pers_ei_pantalla
{
	protected $longitud_maxima_str = 43;
	
	function generar_layout()
	{
		$this->generar_detalle();
		echo '<hr />';
		$this->dep('cuadro')->generar_html();		
	}
	
	function generar_detalle()
	{
		$datos = $this->controlador()->get_datos_detalle();
		
		echo "<div style='padding-left: 5px;padding-right:5px;'>";
		echo "<table width='100%'>";
		// Nombre
		echo '<tr><td>';
		echo "<strong><span class='ei-form-etiq-oblig'>Nombre: </span></strong>";
		echo '</td><td>';
		$this->get_html_dato($datos['nombre']);
		echo '</td></tr>';
		// Descripción
		echo '<tr><td>';
		echo "<strong><span class='ei-form-etiq-oblig'>Descripción: </span></strong>";
		echo '</td><td>';
		$this->get_html_dato($datos['descripcion']);
		echo '</td></tr>';
		echo '</table></div>';
	}
	
	function get_html_dato($dato)
	{
		if (strlen($dato) > $this->longitud_maxima_str) {
			echo "<span title='{$dato}'>";
			echo substr($dato, 0, $this->longitud_maxima_str)."...";
			echo '</span>';
		} else {
			echo '<span>'.$dato.'</span>';
		}
	}
}

?>