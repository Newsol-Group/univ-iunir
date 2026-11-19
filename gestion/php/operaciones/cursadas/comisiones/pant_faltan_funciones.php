<?php
class pant_faltan_funciones extends toba_ei_pantalla
{
	/**
	 * Permite modificar la forma en que se grafica la pantalla, por defecto un componente sobre el otro
	 */
	function generar_layout()
	{
		$html = '<p style="border-bottom: 2px solid black; padding-bottom: 10px;">';
		$html .= guarani_mensajes::get('800_educacion_virtual');
		$html .= '</p>';
		
		$funciones_faltantes_moodle = $this->controlador()->get_funciones_faltantes_moodle();
		
		$html .= '<div style="border: 2px solid black; padding: 10px;">';
		$html .= "<p>".guarani_mensajes::get('800_faltan_funciones_moodle', [implode(', ', $funciones_faltantes_moodle)])."</p>";
		$html .= '</div>';
		
		echo $html;
	}

}

?>