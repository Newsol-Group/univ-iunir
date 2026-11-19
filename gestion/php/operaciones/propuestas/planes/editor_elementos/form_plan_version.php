<?php
class form_plan_version extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table border="0">';

		echo '<tr>';
		echo '<td colspan="2">';
		$this->generar_html_ef('version');
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td colspan="2">';
		$this->generar_html_ef('nombre');
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td colspan="2">';
		$this->generar_html_ef('version_estado_descripcion');
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('documento_alta');
		echo '</td>';
		echo '<td>';
		$this->generar_html_ef('fecha_entrada_vigencia');
		echo '</td>';
		echo '<td>';
		echo ' ';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('documento_baja');
		echo '</td>';
		echo '<td>';
		$this->generar_html_ef('fecha_baja');
		echo '</td>';
		echo '</tr>';
		
		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('cnt_materias');
		echo '</td>';
		echo '<td>';
		$this->generar_html_ef('cnt_optativas');
		echo '</td>';
		echo '</tr>';
		
		echo '<tr>';
		echo '<td colspan="2">';
		$this->generar_html_ef('cnt_idiomas');
		echo '</td>';
		echo '</tr>';

		echo '</table>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$date = guarani_fecha::get_hoy_js();
		$msj_fecha_baja_hoy = guarani_mensajes::get('800_control_fechas', array('de baja', 'mayor', 'actual'));
		$msj_fecha_baja_vigencia = guarani_mensajes::get('800_control_fechas', array('de baja', 'menor', 'de entrada en vigencia'));
		$msj_datos_baja = guarani_mensajes::get('800PRO_error_datos_baja');

		echo "
		//---- Validacion de EFs -----------------------------------

		{$this->objeto_js}.evt__fecha_baja__validar = function()
		{
			var hoy = new $date;
			if (this.ef('fecha_baja').tiene_estado()) {
				var fecha_baja = this.ef('fecha_baja').fecha();
				if (fecha_baja > hoy) {
					this.ef('fecha_baja').set_error('$msj_fecha_baja_hoy');
					return false;
				}
				if (this.ef('fecha_entrada_vigencia').tiene_estado() && fecha_baja < this.ef('fecha_entrada_vigencia').fecha()) {
					this.ef('fecha_baja').set_error('$msj_fecha_baja_vigencia');
					return false;
				}
			}
			return true;
		}

		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;
			if ((this.ef('documento_baja').tiene_estado() && !this.ef('fecha_baja').tiene_estado()) ||
				(!this.ef('documento_baja').tiene_estado() && this.ef('fecha_baja').tiene_estado())) {
				notificacion.agregar('$msj_datos_baja');
				ok = false;
			}
			return ok; 
		}
		
		{$this->objeto_js}.validar = function()
		{	
			if (!this.controlador.get_validar_todo()) {
				return true;
			}
			var ok = true;
			var validacion_particular = 'evt__validar_datos';
			if(this._evento && this._evento.validar) {
				 if (existe_funcion(this, validacion_particular)) {
					 ok = this[validacion_particular]();		
				 }
				 for (id_ef in this._efs) {
					 ok = this.validar_ef(id_ef) && ok;
				 }
			} else {
			  this.resetear_errores();
		    }

			if (!ok) {
				 this.reset_evento();
			}
			return ok;
		 };	 
		";
	}

}
?>