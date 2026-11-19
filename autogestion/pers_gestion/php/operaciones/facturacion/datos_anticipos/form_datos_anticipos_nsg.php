<?php
class form_datos_anticipos_nsg extends guarani_pers_ei_formulario
{

	function generar_layout()
	{
		echo '<table border=0>';
    	echo '<tr>';
    		echo '<td colspan=2>';
    		$this->generar_html_ef('persona');
    		echo '</td>';
    	echo '</tr>';
    	echo '<tr>';
    		echo '<td colspan=2>';
    		$this->generar_html_ef('propuesta');
    		echo '</td>';
    	echo '</tr>';
    	echo '<tr>';
    		echo '<td colspan=2>';
    		$this->generar_html_ef('alumno');
    		echo '</td>';
    	echo '</tr>';

    	echo '<tr>';
	echo '</table>';
		echo "<table border=0 width='100%'>";
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('tipo_beneficio');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('fecha_vencimiento', '120px');
			echo '</td>';
		echo '</tr>';
	echo '</table>';

	}

}
?>