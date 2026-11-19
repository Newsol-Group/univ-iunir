<?php
class form_alumnos extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<b><u>Parámetros para los Alumnos</u></b>';
		echo '<br><br/>';
		echo '<fieldset><legend>Condición del Alumno</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" width="100%">';
			echo '<tr>';
				echo '<td colspan="4">';
				$this->generar_html_ef('regulares', '65px');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td colspan="4">';
				$this->generar_html_ef('no_regulares', '85px');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('considerar_perdida_reg', '0px');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('condicion', '280px');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('desde', '115px');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('hasta', '25px');
				echo '</td>';
			echo '</tr>';
		echo '</table>';
		echo '</fieldset>';
		echo '<fieldset><legend>Reinscripción</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" width="100%">';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('considerar_reinscriptos', '0px');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('anio_reinscripcion', '420px');
				echo '</td>';
			echo '</tr>';
		echo '</table>';
		echo '</fieldset>';
		echo '<br/>';
		echo '<b><u>Cambio de Calidad</u></b>';
		echo '<br><br/>';
		echo '<table cellspacing="1" cellpadding="1" border="0" width="100%">';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('motivo_calidad');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('documento');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('observaciones');
				echo '</td>';
			echo '</tr>';
		echo '</table>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_error_condicion_reg = guarani_mensajes::get('dato_faltante', array('Condición de pérdida de regularidad (desde, hasta, entre)'));
		$msj_error_anio_reinsc = guarani_mensajes::get('dato_faltante', array('Año Reinscripción'));
		$msj_error_desde = guarani_mensajes::get('dato_faltante', array('Año Académico Desde'));
		$msj_error_hasta = guarani_mensajes::get('dato_faltante', array('Año Académico Hasta'));
		$msj_error_anio = guarani_mensajes::get('800_control_anio', array('Hasta', 'menor', 'Desde para la condición de pérdida de regularidad'));

		echo "
		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			ok = true;
			if (this.ef('considerar_perdida_reg').chequeado()) {
				if (! this.ef('condicion').tiene_estado()) {
					notificacion.agregar('$msj_error_condicion_reg');
					ok = false;
				} else {
					if (! this.ef('desde').tiene_estado()) {
						notificacion.agregar('$msj_error_desde');
						ok = false;
					}
					if (this.ef('condicion').get_estado() == 'E') {
						if (! this.ef('hasta').tiene_estado()) {
							notificacion.agregar('$msj_error_hasta');
							ok = false;
						}
						if (this.ef('desde').tiene_estado() && this.ef('hasta').tiene_estado() &&
							this.ef('hasta').get_estado() < this.ef('desde').get_estado()) {
								notificacion.agregar('$msj_error_anio');
								ok = false;
						}
					}
				}
			}

			if (this.ef('considerar_reinscriptos').chequeado() && !this.ef('anio_reinscripcion').tiene_estado()) {
				notificacion.agregar('$msj_error_anio_reinsc');
				ok = false;
			}

			return ok;
		}

		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__no_regulares__procesar = function(es_inicial)
		{
			if (this.ef('no_regulares').chequeado()) {
				this.ef('considerar_perdida_reg').activar();
			} else {
				this.ef('considerar_perdida_reg').resetear_estado();
				this.ef('considerar_perdida_reg').desactivar();
			}
		}

		{$this->objeto_js}.evt__considerar_perdida_reg__procesar = function(es_inicial)
		{
			if (this.ef('considerar_perdida_reg').chequeado()) {
				this.ef('condicion').activar();
				this.ef('desde').activar();
				this.ef('hasta').activar();
			} else {
				this.ef('condicion').resetear_estado();
				this.ef('condicion').desactivar();
				this.ef('desde').resetear_estado();
				this.ef('desde').desactivar();
				this.ef('hasta').resetear_estado();
				this.ef('hasta').desactivar();
			}
		}

		{$this->objeto_js}.evt__condicion__procesar = function(es_inicial)
		{
			if (!this.ef('condicion').tiene_estado()) {
				this.ef('desde').resetear_estado();
				this.ef('desde').desactivar();
				this.ef('hasta').resetear_estado();
				this.ef('hasta').desactivar();
			}
			
			if (this.ef('condicion').get_estado() == 'E') {
				this.ef('desde').activar();
				this.ef('hasta').activar();
			}
			
			if ((this.ef('condicion').get_estado() == 'D') || (this.ef('condicion').get_estado() == 'H')) {
				this.ef('desde').activar();
				this.ef('hasta').resetear_estado();
				this.ef('hasta').desactivar();
			}
		}

		{$this->objeto_js}.evt__considerar_reinscriptos__procesar = function(es_inicial)
		{
			if (this.ef('considerar_reinscriptos').chequeado()) {
				this.ef('anio_reinscripcion').activar();
			} else {
				this.ef('anio_reinscripcion').resetear_estado();
				this.ef('anio_reinscripcion').desactivar();
			}
		}
		";
	}
}
?>