<?php
class form_excepcion extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		
		$msj_fecha_fin_mayor_per_lect = guarani_mensajes::get('800_control_fechas', array('fin', 'mayor', 'fin del período lectivo'));
		$msj_fecha_fin_menor_inicio = guarani_mensajes::get('800_control_fechas', array('fin', 'menor', 'inicio'));
		$msj_fecha_tope_baja_mayor_per_lect = guarani_mensajes::get('800_control_fechas', array('tope de bajas', 'mayor', 'fin del período lectivo'));
		$msj_fecha_tope_baja_menor_fin = guarani_mensajes::get('800_control_fechas', array('tope de bajas', 'menor', 'fin'));
		$fecha_fin_periodo_lectivo = $this->controlador()->get_fecha_fin_periodo_lectivo();
		
		$dia = guarani_fecha::get_parte($fecha_fin_periodo_lectivo, 'dia');
		$mes = guarani_fecha::get_parte($fecha_fin_periodo_lectivo, 'mes') - 1;
		$anio = guarani_fecha::get_parte($fecha_fin_periodo_lectivo, 'año');
		
		echo "
		
		var fecha_fin_periodo_lectivo = new Date($anio, $mes, $dia);
		
		//---- Validacion general ----------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;
			var fecha_inicio = this.ef('fecha_inicio').get_fecha_hora();
			var fecha_fin = this.ef('fecha_fin').get_fecha_hora();
			var fecha_tope_bajas = this.ef('fecha_tope_bajas').get_fecha_hora();
			
			if (this.ef('fecha_inicio').tiene_estado() && this.ef('fecha_fin').tiene_estado() && fecha_inicio > fecha_fin) {
				notificacion.agregar('$msj_fecha_fin_menor_inicio');
				ok = false;
			}
			
			if (this.ef('fecha_fin').tiene_estado() && this.ef('fecha_tope_bajas').tiene_estado() && fecha_fin > fecha_tope_bajas) {
				notificacion.agregar('$msj_fecha_tope_baja_menor_fin');
				ok = false;
			}

			return ok;
		}
		
		//---- Validacion de EFs -----------------------------------

		/**
		 * Verifica que sea menor o igual que la fecha fin del Período Lectivo
		 */
		{$this->objeto_js}.evt__fecha_fin__validar = function()
		{
			if (this.ef('fecha_fin').tiene_estado() && this.ef('fecha_fin').fecha() > fecha_fin_periodo_lectivo) {
				this.ef('fecha_fin').set_error('$msj_fecha_fin_mayor_per_lect');
				return false;
			}
			return true;
		}
		
		/**
		 * Verifica que sea menor o igual que la fecha fin del Período Lectivo
		 */
		{$this->objeto_js}.evt__fecha_tope_bajas__validar = function()
		{
			if (this.ef('fecha_tope_bajas').tiene_estado() && this.ef('fecha_tope_bajas').fecha() > fecha_fin_periodo_lectivo) {
				this.ef('fecha_tope_bajas').set_error('$msj_fecha_tope_baja_mayor_per_lect');
				return false;
			}
			return true;
		}
		";
	}
}
?>