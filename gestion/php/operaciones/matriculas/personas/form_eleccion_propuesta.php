<?php
class form_eleccion_propuesta extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<fieldset>
				<legend>
					<strong>Motivos de mayor peso en la elección de Propuesta</strong>
				</legend>";
				$this->generar_html_ef('mot_vocacion');
				$this->generar_html_ef('mot_sugerencia_familiar');
				$this->generar_html_ef('mot_reconocimiento_social');
				$this->generar_html_ef('mot_insercion_laboral');
				$this->generar_html_ef('mot_perfil_profesional');
				$this->generar_html_ef('mot_mejora_economica');
				$this->generar_html_ef('mot_util_sociedad');
				$this->generar_html_ef('mot_otros_p');
		echo "</fieldset>";
	}

}
?>