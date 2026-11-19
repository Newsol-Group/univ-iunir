<?php
class formulario extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table> 
				<tr>
					<td colspan="2">';
						$this->generar_html_ef('plan_version');
		echo '		</td>
				</tr>
				<tr>
					<td colspan="2">';
						$this->generar_html_ef('accion');
		echo '		</td>
				</tr>
				<tr>
					<td colspan="2">';
						$this->generar_html_ef('requisito');
		echo '		</td>
				</tr>
				<tr>
					<td>';
						$this->generar_html_ef('fecha_desde');
		echo '		</td>
					<td>';
						$this->generar_html_ef('fecha_hasta');
		echo '		</td>
				</tr>
				<tr>
					<td colspan="2">';
						$this->generar_html_ef('elemento');
		echo '		</td>
				</tr>
				<tr>
					<td colspan="2">';
						$this->generar_html_ef('observaciones');
		echo '		</td>
				</tr>
			  </table>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$hoy_js = guarani_fecha::get_hoy_js();
		$mje_fecha_hoy = guarani_mensajes::get('800_control_fechas', array('desde', 'anterior', 'actual'));
		$mje_fechas = guarani_mensajes::get('800_control_fechas', array('desde', 'mayor o igual', 'hasta')); 

		echo "
		//---- Validacion de EFs -----------------------------------

		{$this->objeto_js}.evt__fecha_desde__validar = function()
		{
			var hoy = new $hoy_js;
			if (this.ef('fecha_desde').activo() && this.ef('fecha_desde').fecha() < hoy) {
				this.ef('fecha_desde').set_error('$mje_fecha_hoy');
				return false;
			}
			return true;
		}

		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			if (this.ef('fecha_desde').tiene_estado() && this.ef('fecha_hasta').tiene_estado()) {
				if (this.ef('fecha_desde').fecha() >= this.ef('fecha_hasta').fecha()) {
					notificacion.agregar('$mje_fechas');
					return false;
				}
			}
			return true;
		}
		";
	}
}
?>