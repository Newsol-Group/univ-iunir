<?php
class form_motivos_de_cambio_de_calidad extends guarani_pers_ei_formulario
{

	function generar_layout()
	{
		echo '<table border=0>';
    	echo '<tr>';
	    	echo '<td colspan=2>';    
			$this->generar_html_ef('nombre');
			echo '</td>';
		echo '</tr>';
		
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('calidad');
			echo '</td>';
			echo '<td>';
			$this->generar_html_ef('estado');
			echo '</td>';
		echo '</tr>';
		
		echo '<tr>';
			echo '<td colspan=2>';
			$this->generar_html_ef('descripcion');
			echo '</td>';
		echo '</tr>';
		echo '</table>';
	}

}
?>