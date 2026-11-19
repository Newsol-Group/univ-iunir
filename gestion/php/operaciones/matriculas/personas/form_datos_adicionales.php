<?php
class form_datos_adicionales extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<fieldset title="Situación familiar"><legend>Turno de cursada</legend>';
				$this->generar_html_ef('turno_preferido', '125px');
		echo '</fieldset>';
		echo '<p>&nbsp;</p>';
		
		echo '<fieldset title="Domicilio durante el Período Lectivo"><legend>Domicilio durante el Período Lectivo</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td colspan="4">';
					$this->generar_html_ef('vive_con', '220px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="4">';
					$this->generar_html_ef('tipo_vivienda', '220px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="4">';
					$this->generar_html_ef('periodo_lectivo_calle');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('periodo_lectivo_numero');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('periodo_lectivo_piso');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('periodo_lectivo_departamento');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('periodo_lectivo_unidad');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="4">';
					$this->generar_html_ef('periodo_lectivo_barrio');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="3">';
					$this->generar_html_ef('periodo_lectivo_localidad');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('periodo_lectivo_codigo_postal');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '</fieldset>';
		echo '<p>&nbsp;</p>';
		echo '<fieldset title="Domicilio de Procedencia (fuera del período de clases)"><legend>Domicilio de Procedencia (fuera del período de clases)</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td colspan="4">';
					$this->generar_html_ef('procedencia_calle');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="4">';
					$this->generar_html_ef('procedencia_barrio');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('procedencia_numero');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('procedencia_piso');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('procedencia_departamento');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('procedencia_unidad');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="3">';
					$this->generar_html_ef('procedencia_localidad');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('procedencia_codigo_postal');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '</fieldset>';
		echo '<p>&nbsp;</p>';
	}
	
	//--------------------------------------------------------------------------
	//---- JAVASCRIPT ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------------------
		
		{$this->objeto_js}.evt__periodo_lectivo_localidad__procesar = function(es_inicial)
		{
			//	Separa el id de localidad que viene como localidad:codigo_postal del buscador, 
			//	para mostrar el valor correspondiente en el ef de codigo_postal.
			
			if (! es_inicial) {
				if (this.ef('periodo_lectivo_localidad').tiene_estado()) {
					var localidad = this.ef('periodo_lectivo_localidad').get_estado().toString();
					var index = localidad.indexOf(':');
					if (index != -1 && index < localidad.length) {	//Si esta el separador : en el string de localidad y el string continua despues del mismo
						var cp = localidad.substring(index + 1, localidad.length);
						this.ef('periodo_lectivo_codigo_postal').set_estado(cp);
					} else {										//La localidad no tiene cp, entonces dejo el estado de cp en vacio
						this.ef('periodo_lectivo_codigo_postal').resetear_estado();	
					}
				} else {
					//cubre el caso en que se limpia el valor del popup de localidad.
					this.ef('periodo_lectivo_codigo_postal').resetear_estado();
				}
			}
		}
		
		{$this->objeto_js}.evt__procedencia_localidad__procesar = function(es_inicial)
		{
			//	Separa el id de localidad que viene como localidad:codigo_postal del buscador, 
			//	para mostrar el valor correspondiente en el ef de codigo_postal.
			
			if (! es_inicial) {
				if (this.ef('procedencia_localidad').tiene_estado()) {
					var localidad = this.ef('procedencia_localidad').get_estado().toString();
					var index = localidad.indexOf(':');
					if (index != -1 && index < localidad.length) {	//Si esta el separador : en el string de localidad y el string continua despues del mismo
						var cp = localidad.substring(localidad.indexOf(':') + 1, localidad.length);
						this.ef('procedencia_codigo_postal').set_estado(cp);
					} else {										//La localidad no tiene cp, entonces dejo el estado de cp en vacio
						this.ef('procedencia_codigo_postal').resetear_estado();	
					}
				} else {
					//cubre el caso en que se limpia el valor del popup de localidad.
					this.ef('procedencia_codigo_postal').resetear_estado();
				}
			}
		}
		";
	}
	
}
?>