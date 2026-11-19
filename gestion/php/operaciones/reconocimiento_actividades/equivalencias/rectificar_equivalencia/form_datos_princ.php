<?php
/**
 *
 *
 * @package		Reconocimiento de Actividades
 * @subpackage	Equivalencias
 * 
 */
class form_datos_princ extends guarani_pers_ei_formulario
{
	/**
	 * Permite modificar la forma en que se grafica el formulario, por defecto un ef sobre el otro
	 */
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
		echo '	<tbody>';
		echo '		<tr>';
		echo '			<td>';
							$this->generar_html_ef('fecha', '140px');
		echo '			</td>';
		echo '			<td>';
							$this->generar_html_ef('documento', '110px');
		echo '			</td>';
		echo '		</tr>';
		echo '		<tr>';
		echo '			<td colspan="2">';
							$this->generar_html_ef('observaciones', '140px');
		echo '			</td>';
		echo '		</tr>';
		echo '	</tbody>';
		echo '</table>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{

		$date = guarani_fecha::get_hoy_js();
		$msj_fecha = guarani_mensajes::get('800_control_fechas', array('de Rectificación', 'mayor', 'actual'));

		echo "
		var hoy = new $date;
				
		//---- Validar -----------------------------------------------
		
		{$this->objeto_js}.evt__fecha__validar = function()
		{
			var ok = true;
			
			if(this.ef('fecha').tiene_estado()) {
				var fecha_otorgamiento = this.ef('fecha').fecha();
				if (fecha_otorgamiento > hoy) {
					this.ef('fecha').set_error('$msj_fecha');
					ok = false;
				}
			}
			return ok;
		}
		";
	}

}
?>