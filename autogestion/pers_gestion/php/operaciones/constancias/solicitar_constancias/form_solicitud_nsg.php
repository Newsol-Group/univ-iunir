<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Solicitar Constancias
 * 
 */
//Newsol - Se agregó el campo cobrable para reflejar si esa constancia se cobra o no
class form_solicitud_nsg extends form_solicitud
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td colspan>';
					$this->generar_html_ef('constancia');
					echo '</td>';
					echo '<td colspan>';
					$this->generar_html_ef('cobrable');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('cant_copias_solicitadas', '200px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('presentar_a');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('observaciones');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}	
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
			
		var mostrar_constancia = false;
			
		//---- Validación de EFs -----------------------------------
	
		// Cuando se valida el campo de la constancia seleccionada se dispara un submit para agregar el ci correspondiente a la misma.		 
		{$this->objeto_js}.evt__constancia__validar = function()
		{
			var constancia = this.ef('constancia').get_estado();
			this.submit(); 

			if ((constancia == 112) || (constancia == 118)){
			}

			return true;
		} 

			
		";
	}
}
?>