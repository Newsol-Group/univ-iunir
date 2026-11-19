<?php
class form_datos_bonif_nsg extends guarani_pers_ei_formulario
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
			$this->generar_html_ef('tipo_beneficio', '150px');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('institucion');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('bonificacion_matricula', '120px');
			echo '</td>';
			echo '<td>';
			$this->generar_html_ef('bonificacion_cuota', '100px');
			echo '</td>';
		echo '</tr>';
	echo '</table>';

	}


	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__tipo_beneficio__procesar = function(es_inicial)
		{
			if (this.ef('tipo_beneficio').tiene_estado()) {
				var tipo_beneficio = this.ef('tipo_beneficio').get_estado();
				if (tipo_beneficio == '3') {
					this.ef('institucion').mostrar();
				} else {
					this.ef('institucion').ocultar(true);
				}
			} else {
				this.ef('institucion').ocultar(true);
			}
		}
		";
	}



}
?>