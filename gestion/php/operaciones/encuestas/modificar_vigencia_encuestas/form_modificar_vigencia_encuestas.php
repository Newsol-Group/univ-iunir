<?php
/**
 * 
 * @package Encuestas
 */
class form_modificar_vigencia_encuestas extends form_habilitacion
{
	function generar_layout()
	{
		echo '<table><tr><td>';
		$this->generar_html_ef('titulo');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('kolla_unidad_gestion');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('tipo');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('alcance');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('descripcion');
		echo "</td></tr></table><table><tr><td>";
		$this->generar_html_ef('fecha_desde');
		echo '</td><td>';
		$this->generar_html_ef('fecha_hasta', '80px');
		echo '</td></tr></table>';
		echo '<fieldset><legend>Características</legend><table width=100%><tr><td>';
		$this->generar_html_ef('obligatoria', '120px');
		echo '</td><td>';
		$this->generar_html_ef('anonima', '108px');
		echo '</td><td>';
		$this->generar_html_ef('activo', '40px');
		echo '</td></tr></table>';
		echo "</fieldset>";
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$hoy_js = guarani_fecha::get_hoy_js();
		$mje_fecha_hoy = guarani_mensajes::get('800_control_fechas', array('', 'anterior', 'actual.'));
		$mje_fechas = guarani_mensajes::get('800_control_fechas', array('desde (de validez)', 'mayor o igual', 'hasta.'));

		echo "
		//---- Validación de EFs -----------------------------------

		{$this->objeto_js}.evt__fecha_desde__validar = function()
		{
			var hoy = new $hoy_js;
			if (this.ef('fecha_desde').activo() && this.ef('fecha_desde').fecha() < hoy) {
				this.ef('fecha_desde').set_error('$mje_fecha_hoy');
				return false;
			}
			return true;
		}

		{$this->objeto_js}.evt__fecha_hasta__validar = function()
		{
			var hoy = new $hoy_js;
			if (this.ef('fecha_hasta').fecha() < hoy) {
				this.ef('fecha_hasta').set_error('$mje_fecha_hoy');
				return false;
			}
			return true;
		}

		//---- Validación general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			if (this.ef('fecha_desde').fecha() >= this.ef('fecha_hasta').fecha()) {
				notificacion.agregar('$mje_fechas');
				return false;
			}
			return true;
		}
		";
	}

}
?>