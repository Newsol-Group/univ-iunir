<?php
class form_datos_princ extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$this->generar_html_ef('nombre');
		$this->generar_html_ef('anio_academico');
		$this->generar_html_ef('periodo_generico');
		$this->generar_html_ef('descripcion');
		$this->generar_html_ef('fecha_publicacion_comisiones', '250px');
		$this->generar_html_ef('fecha_publicacion_comisiones_docente', '250px');

		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
		echo'<tbody>';
		echo '<tr>';
		echo '<td>';
		echo '<fieldset title="Duración"><legend>Duración</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
		echo'<tbody>';
		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('fecha_inicio', '120px');
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('fecha_fin', '120px');
		echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
		echo '</td>';
		echo '<td>';
		echo '</fieldset> <fieldset title="Dictado"><legend>Dictado</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
		echo '<tbody>';
		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('fecha_inicio_dictado', '120px');
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('fecha_fin_dictado', '120px');
		echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
		echo '</td>';
		echo '<td>';
		echo '</fieldset> <fieldset title="Generales"><legend>Generales</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
		echo '<tbody>';
		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('fecha_tope_movimientos', '150px');
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('fecha_inactivacion', '150px');
		echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
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
		$mje_fecha_fin = guarani_mensajes::get('800_control_fechas', array('Inicio Período', 'mayor', 'Fin Período'));
		$mje_fecha_fin_dict = guarani_mensajes::get('800_control_fechas', array('Inicio Dictado', 'mayor', 'Fin Dictado'));
		$mje_fecha_tope_mov_menor = guarani_mensajes::get('800_control_fechas', array('Tope Movimientos', 'menor', 'Inicio Período'));
		$mje_fecha_tope_mov_mayor = guarani_mensajes::get('800_control_fechas', array('Tope Movimientos', 'mayor', 'Fin Período'));
		$mje_fecha_inactiv_menor = guarani_mensajes::get('800_control_fechas', array('Inactivación', 'menor', 'Fin Dictado'));
		$mje_fecha_ini_menor_alumnos = guarani_mensajes::get('800_control_fechas', array('Inicio Dictado', 'menor', 'de publicación de alumnos'));
		$mje_fecha_ini_menor_docentes = guarani_mensajes::get('800_control_fechas', array('Inicio Dictado', 'menor', 'de publicación de docentes'));
		$msj_confirmar = guarani_mensajes::get('800CAL_rango_fuera_rango');

		echo "
		
		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;

			var fecha_ini = this.ef('fecha_inicio').fecha();
			var fecha_fin = this.ef('fecha_fin').fecha();
			var fecha_ini_dictado = this.ef('fecha_inicio_dictado').fecha();
			var fecha_fin_dictado = this.ef('fecha_fin_dictado').fecha();
			var fecha_tope_mov = this.ef('fecha_tope_movimientos').fecha();
			var fecha_inactivacion = this.ef('fecha_inactivacion').fecha();
			var fecha_publicacion_alumnos = this.ef('fecha_publicacion_comisiones').fecha();
			var fecha_publicacion_docentes = this.ef('fecha_publicacion_comisiones_docente').fecha();

			if (!(fecha_ini <= fecha_ini_dictado && fecha_fin >= fecha_fin_dictado)) {
				resp = confirm('$msj_confirmar');
				if (!resp) {
					return false;
				}
			}
		
			if (this.ef('fecha_inicio').tiene_estado() && this.ef('fecha_fin').tiene_estado() && fecha_ini > fecha_fin) {
				notificacion.agregar('$mje_fecha_fin');
				ok = false;
			}

			if (this.ef('fecha_inicio_dictado').tiene_estado() && this.ef('fecha_fin_dictado').tiene_estado() && fecha_ini_dictado > fecha_fin_dictado) {
				notificacion.agregar('$mje_fecha_fin_dict');
				ok = false;
			}

			if (this.ef('fecha_inicio').tiene_estado() && this.ef('fecha_tope_movimientos').tiene_estado() && fecha_tope_mov < fecha_ini) {
				notificacion.agregar('$mje_fecha_tope_mov_menor');
				ok = false;
			}

			if (this.ef('fecha_fin').tiene_estado() && this.ef('fecha_tope_movimientos').tiene_estado() && fecha_tope_mov > fecha_fin) {
				notificacion.agregar('$mje_fecha_tope_mov_mayor');
				ok = false;
			}

			if (this.ef('fecha_fin_dictado').tiene_estado() && this.ef('fecha_inactivacion').tiene_estado() && fecha_inactivacion < fecha_fin_dictado) {
				notificacion.agregar('$mje_fecha_inactiv_menor');
				ok = false;
			}
			if (this.ef('fecha_inicio_dictado').tiene_estado()  && this.ef('fecha_publicacion_comisiones').tiene_estado() && fecha_ini_dictado < fecha_publicacion_alumnos) {
				notificacion.agregar('$mje_fecha_ini_menor_alumnos');
				ok = false;
			}
			if (this.ef('fecha_inicio_dictado').tiene_estado() && this.ef('fecha_publicacion_comisiones_docente').tiene_estado() && fecha_ini_dictado < fecha_publicacion_docentes) {
				notificacion.agregar('$mje_fecha_ini_menor_docentes');
				ok = false;
			}
			return ok;
		}
		";
	}

}
?>