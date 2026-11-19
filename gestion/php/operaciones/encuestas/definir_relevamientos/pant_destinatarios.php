<?php
/**
 * 
 * @package Encuestas
 */
class pant_destinatarios extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$datos_habilitacion = $this->controlador()->cn()->entidad()->get_datos_habilitacion();
		if ($datos_habilitacion['tipo'] == encuesta::tipo_egresados) {
			$this->dep('form_egresados')->generar_html();
		} else {
			$etiqueta_ml_ubicaciones = 'Aplicable a alumnos de las ubicaciones:';
			$etiqueta_ml_modalidades = 'Aplicable a alumnos de las modalidades:';
			if ($datos_habilitacion['tipo'] == encuesta::tipo_generica) {
				if ($datos_habilitacion['tipo_relevamiento'] == encuesta::tipo_relevamiento_becas) {
					$this->dep('form_alumnos_beca')->generar_html();
				} elseif ($datos_habilitacion['encuestado'] == encuesta::encuestado_alumno) {
					$this->dep('form_alumnos_generica')->generar_html();
				} else {
					$this->dep('form_destinatarios_docentes')->generar_html();
					$etiqueta_ml_ubicaciones = 'Aplicable a comisiones de las ubicaciones:';
					$etiqueta_ml_modalidades = 'Aplicable a comisiones de las modalidades:';
				}
			} elseif ($datos_habilitacion['tipo'] == encuesta::tipo_pares) {
				$this->dep('form_pares')->generar_html();
			} else {
				if ($datos_habilitacion['encuestado'] == encuesta::encuestado_alumno) {
					$this->dep('form_alumnos')->generar_html();
				} else {
					$this->dep('form_destinatarios_docentes')->generar_html();
					$etiqueta_ml_ubicaciones = 'Aplicable a comisiones de las ubicaciones:';
					$etiqueta_ml_modalidades = 'Aplicable a comisiones de las modalidades:';
				}
			}
			echo "<table width=100%><tr><td valign='top'><fieldset title='$etiqueta_ml_ubicaciones'><legend>$etiqueta_ml_ubicaciones</legend>";
			$this->dep('form_ml_ubicaciones')->generar_html();
			echo "</fieldset></td><td valign='top'><fieldset title='$etiqueta_ml_modalidades'><legend>$etiqueta_ml_modalidades</legend>";
			$this->dep('form_ml_modalidades')->generar_html();
			echo '</fieldset></td></tr></table>';
		}
	}

}
?>