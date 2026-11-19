<?php
class form_roles extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		
		echo '<fieldset title="Propuestas Formativas"><legend>Propuestas Formativas</legend>';
			$this->generar_html_ef('rol_alumno');
		echo '</fieldset>';
		
		echo '<fieldset title="Cursos"><legend>Cursos</legend>';
			$this->generar_html_ef('rol_alumno_curso');
		echo '</fieldset>';
		
	}

}
?>