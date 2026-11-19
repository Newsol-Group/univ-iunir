<?php
class form_copiar_plan extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table><tr><td colspan=2>';
		$this->generar_html_ef('propuesta');
		echo '</td></tr><tr><td colspan=2>';
		$this->generar_html_ef('persona');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('ubicacion');
		echo '</td><td>';
		$this->generar_html_ef('modalidad');
		if ($this->existe_ef('convenio')) {
			echo '</td></tr><tr><td colspan=2>';
			$this->generar_html_ef('convenio');
		}
		echo '</td></tr></table>';
		echo '<fieldset><legend>Período de Inscripción</legend><table width=100%><tr><td>';
		$this->generar_html_ef('anio_academico', '120px');
		echo '</td><td>';
		$this->generar_html_ef('periodo_insc', '170px');
		echo '</td></tr></table>';
		echo "</fieldset>";
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_periodo = guarani_mensajes::get('800MAT_sin_pi_preinscripcion');

		echo "
		
		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			if (this.ef('propuesta').tiene_estado() && this.ef('modalidad').tiene_estado() &&
				this.ef('ubicacion').tiene_estado() && this.ef('anio_academico').tiene_estado() &&
				!this.ef('periodo_insc').tiene_estado()) {
				notificacion.agregar('$msj_periodo');
				return false;
			}
			return true;
		}

		//---- Validación --------------------------------		
		
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