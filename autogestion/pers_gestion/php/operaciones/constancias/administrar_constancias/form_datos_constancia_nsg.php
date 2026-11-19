<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Constancias
 * 
 */
//Newsol - Se agregan los campos Cobrable y Concepto de Facturaci�n para poder reflejar si se cobra y bajo qu� concepto, respectivamente
class form_datos_constancia_nsg extends form_datos_constancia
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td colspan=3>';
					$this->generar_html_ef('nombre');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=3>';
					$this->generar_html_ef('descripcion');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('activo');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('max_cant_solicitudes', '270px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('max_cant_copias', '220px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('plantilla');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('objeto');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('permite_descargar', '170px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('vigencia');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('aplica_a_cursos', '170px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('exige_aprobacion', '140px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
                   			echo '<td>';
					$this->generar_html_ef('disponible_autogestion', '170px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('cobrable', '100px');
					echo '</td>';
                   	echo '<td>';
					$this->generar_html_ef('concepto', '170px');
					echo '</td>';
              	echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
						$this->generar_html_ef('requiere_documento', '170px');
					echo '</td>';
					echo '<td>';
						$this->generar_html_ef('requiere_documento_desc', '140px');
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
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__permite_descargar__procesar = function(es_inicial) {
			if (this.ef('permite_descargar').get_estado() == 1){
				this.ef('vigencia').mostrar(true);
			}else{
				this.ef('vigencia').ocultar(true);
			}
		}
		
		{$this->objeto_js}.evt__aplica_a_cursos__procesar = function(es_inicial) {			
			if (this.ef('aplica_a_cursos').get_estado() == 'S'){
				this.ef('exige_aprobacion').mostrar();
			}else{
				this.ef('exige_aprobacion').ocultar();
			}
		}

		{$this->objeto_js}.evt__cobrable__procesar = function(es_inicial) {
			if (this.ef('cobrable').get_estado() == 'S'){
				this.ef('concepto').mostrar(true);
				this.ef('concepto').set_obligatorio(true);
			}else{
				this.ef('concepto').ocultar(true);
				this.ef('concepto').set_obligatorio(false);
			}
		}

		";
	}
}
?>