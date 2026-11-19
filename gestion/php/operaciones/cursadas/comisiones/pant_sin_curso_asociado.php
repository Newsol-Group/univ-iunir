<?php
class pant_sin_curso_asociado extends toba_ei_pantalla
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
		$html .= "<p>".$this->controlador()->get_mensaje_nuevo_curso_virtual()."</p>";
		$html .= '</div>';

		$html .= '<div style="margin-top: 15px; text-align: center;">';
		$html .= $this->generar_boton('crear', true, true);
		$html .= $this->generar_boton('vincular', true, true);
		$html .= '</div>';

		echo $html;
	}

}
?>