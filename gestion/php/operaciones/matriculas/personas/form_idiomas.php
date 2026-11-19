<?php
class form_idiomas extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td colspan="2">';
					$this->generar_html_ef('nivel_idioma_ingles', '60px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="2">';
					$this->generar_html_ef('nivel_idioma_aleman', '60px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="2">';
					$this->generar_html_ef('nivel_idioma_frances', '60px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="2">';
					$this->generar_html_ef('nivel_idioma_italiano', '60px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="2">';
					$this->generar_html_ef('nivel_idioma_portugues', '60px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="2">';
					$this->generar_html_ef('nivel_idioma_chino', '60px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('idioma_otro', '60px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('nivel_idioma_otro', '60px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}
	
	//--------------------------------------------------------------------------
	//---- JAVASCRIPT ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "		
		{$this->objeto_js}.evt__idioma_otro__procesar = function(es_inicial)
		{
			var mostrar_nivel_idioma_otro = false;	
			if (this.ef('idioma_otro').tiene_estado()){
				mostrar_nivel_idioma_otro = true;
			}
			this.ef('nivel_idioma_otro').mostrar(mostrar_nivel_idioma_otro,!mostrar_nivel_idioma_otro);
		} 
		";
	}
	
}
?>