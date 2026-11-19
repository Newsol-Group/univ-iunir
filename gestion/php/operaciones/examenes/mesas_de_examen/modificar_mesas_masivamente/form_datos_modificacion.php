<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Modificar Mesas Masivamente
 */
class form_datos_modificacion extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$mje_suspension_insc = guarani_mensajes::get('800EXA_modificar_mesas_insc_suspension');
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="60%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('tipo_modificacion');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('motivo', '100px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					echo "<div id='suspension'>
							<legend>
								$mje_suspension_insc
							</legend>
					 </div>";
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('fecha');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('hora_inicio');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('hora_finalizacion', '100px');
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
		$current_date = guarani_fecha::get_hoy_js();

		$cambio_fecha = mesa_de_examen::cambio_fecha;
		$cambio_hora = mesa_de_examen::cambio_horario;
		
		$mensaje_fecha = guarani_mensajes::get('800_control_fechas', array('nueva del LLamado Mesa', 'menor', 'actual'));
		$mensaje_fecha_oblig = guarani_mensajes::get('800_dato_dep_obligatorio', array('Fecha', 'Tipo de Modificación'));
		$mensaje_hora_oblig = guarani_mensajes::get('800_dato_dep_obligatorio', array('Hora Inicio', 'Tipo de Modificación'));
				
		echo "
		
		//---- Procesamiento de EFs ------------------------------
		
		{$this->objeto_js}.evt__tipo_modificacion__procesar = function(es_inicial)
		{
			var mostrar_fecha = false;
			var mostrar_hora = false;
			$('#suspension').hide();
			if (this.ef('tipo_modificacion').tiene_estado() && this.ef('tipo_modificacion').get_estado()!= apex_ef_no_seteado) {
				// Según el tipo de modificacion se resetean los campos de fecha y horas.
				var tipo = this.ef('tipo_modificacion').get_estado();
				if (tipo == '$cambio_fecha') {
					mostrar_fecha = true;
				} else if (tipo == '$cambio_hora') {
					mostrar_hora = true;
				} else {
					// Si se eligió suspensión se muestra el msj sobre las inscripciones.
					$('#suspension').show();
				}
			}
			// Se muestran o se ocultan y se resetean los estados de cada campo, según corresponda.
			this.ef('fecha').mostrar(mostrar_fecha, !mostrar_fecha);
			this.ef('hora_inicio').mostrar(mostrar_hora, !mostrar_hora);
			this.ef('hora_finalizacion').mostrar(mostrar_hora, !mostrar_hora);
		}
		
		//---- Validacion de EFs -----------------------------------

		{$this->objeto_js}.evt__fecha__validar = function()
		{
			var hoy = new $current_date;
			if (this.ef('fecha').tiene_estado()) {
				if (this.ef('fecha').fecha() < hoy) {
					this.ef('fecha').set_error('$mensaje_fecha');
					return false;
				}
			}
			return true;
		}

		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			if (this.ef('tipo_modificacion').tiene_estado() && this.ef('tipo_modificacion').get_estado()!= apex_ef_no_seteado) {
				var cambio = this.ef('tipo_modificacion').get_estado();
				if (cambio == '$cambio_fecha') {
					if (!this.ef('fecha').tiene_estado()) {
						notificacion.agregar('$mensaje_fecha_oblig');
						return false;
					}
				} else if (cambio == '$cambio_hora') {
					if (!this.ef('hora_inicio').tiene_estado()) {
						notificacion.agregar('$mensaje_hora_oblig');
						return false;
					}
				}
			}
			return true;
		}
	
		";
	}	
}

?>