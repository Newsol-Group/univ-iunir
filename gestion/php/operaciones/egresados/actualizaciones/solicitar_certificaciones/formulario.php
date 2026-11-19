<?php
class formulario extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('certificado');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('juramento');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('nro_expediente');
					$this->generar_html_ef('nro_expediente_sudocu');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('nro_expediente_externo');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('fecha_inicio_tramite');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
						echo '<fieldset>';
							echo '<table>';
								echo'<tbody>';
									echo '<tr>';
										echo '<td>';
										$this->generar_html_ef('menciones');
										echo '</td>';
									echo '</tr>';
								echo '</tbody>';
							echo '</table>';
						echo '</fieldset>';
						echo '</br>';
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
		$hoy_js = guarani_fecha::get_hoy_js();
		$mensaje_fecha = guarani_mensajes::get('800_control_fechas', array('Inicio Trámite', 'mayor', 'actual'));
		
		$usa_expediente_electronico = toba_js::bool($this->controlador()->usa_expediente_electronico());
		
		echo "
			
		var usa_expediente_electronico = $usa_expediente_electronico;
			
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__nro_expediente__procesar = function(es_inicial)
		{
			if (usa_expediente_electronico) {
				this.ef('nro_expediente').ocultar(false);
				this.ef('nro_expediente_sudocu').mostrar(true, false);
			} else {
				this.ef('nro_expediente_sudocu').ocultar(false);
				this.ef('nro_expediente').mostrar(true, false);
			}
			
		}
		
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__fecha_inicio_tramite__validar = function()
		{
			var ok = true;
			var hoy = new $hoy_js;
			
			if(this.ef('fecha_inicio_tramite').tiene_estado()) {
				if (this.ef('fecha_inicio_tramite').fecha() > hoy) {
					this.ef('fecha_inicio_tramite').set_error('$mensaje_fecha');
					ok = false;
				}
			}
			
			return ok;
		}
		";
	}
	
}