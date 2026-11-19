<?php
class form_sit_familiar extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
						$this->generar_html_ef('estado_civil', '125px');
					echo '</td>';
					echo '<td>';
						$this->generar_html_ef('unido_hecho', '190px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
						$this->generar_html_ef('cantidad_hijos', '125px');
					echo '</td>';
					echo '<td>';
						$this->generar_html_ef('cantidad_familia', '190px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
						$this->generar_html_ef('situacion_padre', '125px');
					echo '</td>';
					echo '<td>';
						$this->generar_html_ef('situacion_madre', '190px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="2">';
						$this->generar_html_ef('cobertura_salud', '125px');
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
		$casado = persona::casado;
		 
		echo "
		//---- Procesamiento de EFs --------------------------------------------
		
		{$this->objeto_js}.evt__estado_civil__procesar = function(es_inicial)
		{
			var estado_civil = this.ef('estado_civil').get_estado();
			
			var mostrar_unido_hecho = true;
			
			if (estado_civil == $casado){
				mostrar_unido_hecho = false;
			} 
			
			this.ef('unido_hecho').mostrar(mostrar_unido_hecho, !mostrar_unido_hecho);
		} 
		";
	}
}
?>