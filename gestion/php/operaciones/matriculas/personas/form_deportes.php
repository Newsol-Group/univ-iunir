<?php
class form_deportes extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		
	echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
		echo '<tbody>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('deportes', '140px');
				echo '</td>';
			echo '</tr>';
		echo '</tbody>';
	echo '</table>';
	echo '<div id="agrupador_deportes">';
	echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
		echo '<tbody>';
			echo '<tr>';
				echo '<td>';
					echo '<fieldset title="¿Dónde practica?"><legend>¿Dónde practica?</legend>';
					echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
						echo '<tbody>';
							echo '<tr>';
								echo '<td>';
								$this->generar_html_ef('deportes_universidad', '140px');
								echo '</td>';
							echo '</tr>';
							echo '<tr>';
								echo '<td>';
								$this->generar_html_ef('deportes_gimnasio', '140px');
								echo '</td>';
							echo '</tr>';
							echo '<tr>';
								echo '<td>';
								$this->generar_html_ef('deportes_particular', '140px');
								echo '</td>';
							echo '</tr>';
							echo '<tr>';
								echo '<td>';
								$this->generar_html_ef('deportes_otro_lugar', '140px');
								echo '</td>';
							echo '</tr>';
						echo '</tbody>';
					echo '</table>';
					echo '</fieldset>';
				echo '</td>';
				echo '<td>';
					echo '<fieldset title="¿Qué practica?"><legend>¿Qué practica?</legend>';
					echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
						echo '<tbody>';
							echo '<tr>';
								echo '<td>';
								$this->generar_html_ef('deportes_basquet', '60px');
								echo '</td>';
								echo '<td>';
								$this->generar_html_ef('deportes_natacion', '60px');
								echo '</td>';
							echo '</tr>';
							echo '<tr>';
								echo '<td>';
								$this->generar_html_ef('deportes_futbol', '60px');
								echo '</td>';
								echo '<td>';
								$this->generar_html_ef('deportes_tenis', '60px');
								echo '</td>';
							echo '</tr>';
							echo '<tr>';
								echo '<td>';
								$this->generar_html_ef('deportes_gimnasia', '60px');
								echo '</td>';
								echo '<td>';
								$this->generar_html_ef('deportes_voley', '60px');
								echo '</td>';
							echo '</tr>';
							echo '<tr>';
								echo '<td>';
								$this->generar_html_ef('deportes_handball', '60px');
								echo '</td>';
								echo '<td>';
								$this->generar_html_ef('deportes_otros', '60px');
								echo '</td>';
							echo '</tr>';
						echo '</tbody>';
					echo '</table>';
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
		//---- Procesamiento de EFs --------------------------------------------
		
		{$this->objeto_js}.evt__deportes__procesar = function(es_inicial)
		{
			var mostrar_campos_deportes = false;	
			var deportes = this.ef('deportes').chequeado();
	
			if (deportes){
				mostrar_campos_deportes = true;
			}

			// Los campos que no se muestran, es decir se ocultan, se resetean.
			this.ef('deportes_universidad').mostrar(mostrar_campos_deportes,!mostrar_campos_deportes);
			this.ef('deportes_gimnasio').mostrar(mostrar_campos_deportes,!mostrar_campos_deportes);
			this.ef('deportes_particular').mostrar(mostrar_campos_deportes,!mostrar_campos_deportes);
			this.ef('deportes_otro_lugar').mostrar(mostrar_campos_deportes,!mostrar_campos_deportes);
			this.ef('deportes_basquet').mostrar(mostrar_campos_deportes,!mostrar_campos_deportes);
			this.ef('deportes_futbol').mostrar(mostrar_campos_deportes,!mostrar_campos_deportes);
			this.ef('deportes_gimnasia').mostrar(mostrar_campos_deportes,!mostrar_campos_deportes);
			this.ef('deportes_handball').mostrar(mostrar_campos_deportes,!mostrar_campos_deportes);
			this.ef('deportes_natacion').mostrar(mostrar_campos_deportes,!mostrar_campos_deportes);
			this.ef('deportes_tenis').mostrar(mostrar_campos_deportes,!mostrar_campos_deportes);
			this.ef('deportes_voley').mostrar(mostrar_campos_deportes,!mostrar_campos_deportes);
			this.ef('deportes_otros').mostrar(mostrar_campos_deportes,!mostrar_campos_deportes);
			
			if (mostrar_campos_deportes) {
				$('#agrupador_deportes').show();
			} else {
				$('#agrupador_deportes').hide();
			}
			
		}
		";
	}
	
}
?>