<?php
class pant_actas_cerradas extends toba_ei_pantalla
{
	/**
	 * Permite modificar la forma en que se grafica la pantalla, por defecto un componente sobre el otro
	 */
	function generar_layout()
	{
		$html = '<p style="border-bottom: 2px solid black; padding-bottom: 10px;">';
		$html .= guarani_mensajes::get('800_educacion_virtual');
		$html .= '</p>';
		
		$html .= '<div style="border: 2px solid black; padding: 10px;">';
		$html .= "<p>".$this->controlador()->get_mensaje_actas_cerrada_virtual()."</p>";
		$html .= '</div>';

		echo $html;
	}
}
?>