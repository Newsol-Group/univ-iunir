<?php
class pant_curso_asociado extends toba_ei_pantalla
{
	
	/**
	 * Permite modificar la forma en que se grafica la pantalla, por defecto un componente sobre el otro
	 */
	function generar_layout()
	{
		
		$html = '<p style="border-bottom: 2px solid black; padding-bottom: 10px;">';
		$html .= guarani_mensajes::get('800_educacion_virtual');
		$html .= '</p>';
		
		try {
			
			$info_curso = $this->controlador()->get_info_curso_virtual();
			$coinciden_alumnos_moodle = $this->controlador()->coinciden_alumnos_moodle();
			$coinciden_docentes_moodle = $this->controlador()->coinciden_docentes_moodle();
		
		} catch (Exception $e) {
			
			$html .= '<div style="border: 2px solid black; padding: 10px;">';
			$html .= "<p>".guarani_mensajes::get('800_error_comunicacion_moodle')."</p>";
			$html .= '</div>';
			echo $html;
			return false;
			
		}
		
		// Si falló la comunicación con Moodle
		if ($info_curso == -1) {
			
			$html .= '<div style="border: 2px solid black; padding: 10px;">';
			$html .= "<p>".guarani_mensajes::get('800_error_comunicacion_moodle')."</p>";
			$html .= '</div>';
			echo $html;
			return false;
			
		} else {
			
			$fecha_inicio = null;
			// Si se definió la Fecha de Inicio del curso en Moodle
			if (!empty($info_curso['startdate'])) {
				$startdate = new DateTime();
				$startdate->setTimestamp($info_curso['startdate']);
				$fecha_inicio = $startdate->format('d/m/Y');
			}
			
			$fecha_fin = null;
			// Si se definió la Fecha de finalización del curso en Moodle
			if (!empty($info_curso['enddate'])) {
				$enddate = new DateTime();
				$enddate->setTimestamp($info_curso['enddate']);
				$fecha_fin = $enddate->format('d/m/Y');
			}
			
			$html .= '<div style="border: 2px solid black; padding: 10px;">';
			$html .= "<p>{$info_curso['fullname']}</p>";
			if (!empty($info_curso['summary'])) $html .= $info_curso['summary'];
			
			if (!is_null($fecha_inicio) && !is_null($fecha_fin)) {
				$html .= "<p>".guarani_mensajes::get('800_curso_moodle_fecha_inicio_fin', array($fecha_inicio, $fecha_fin))."</p>";
			} elseif(!is_null($fecha_inicio)) {
				$html .= "<p>".guarani_mensajes::get('800_curso_moodle_fecha_inicio', array($fecha_inicio))."</p>";
			}
			
			$html .= '</div>';
			
			$html .= '<div style="margin-top: 10px; text-align: right;">';
			$html .= $this->generar_boton('desvincular', true, true);
			$html .= '</div>';
			
			$html .= '<div style="margin-top: 10px; border-top: 2px solid black; padding: 10px 0;">';
			
			if ($coinciden_docentes_moodle) {
				$html .= "<p>".$this->controlador()->get_mensaje_docentes_coinciden_moodle()."</p>";
			} else {
				$html .= "<p>".$this->controlador()->get_mensaje_docentes_no_coinciden_moodle()."</p>";
				$html .= '<br>';
				$html .= $this->generar_boton('actualizar_docentes', true, true);
			}
			
			$html .= '</div>';
		
			$html .= '<div style="margin-top: 10px; border-top: 2px solid black; padding: 10px 0;">';
			
			if ($coinciden_alumnos_moodle) {
				$html .= "<p>".$this->controlador()->get_mensaje_alumnos_coinciden_moodle()."</p>";
			} else {
				$html .= "<p>".$this->controlador()->get_mensaje_alumnos_no_coinciden_moodle()."</p>";
				$html .= '<br>';
				$html .= $this->generar_boton('actualizar_alumnos', true, true);
			}
			
			$html .= '</div>';
		
		}
		
		echo $html;
	}

}
?>