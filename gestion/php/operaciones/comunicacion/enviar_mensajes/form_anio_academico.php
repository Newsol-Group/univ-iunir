<?php
class form_anio_academico extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table><tr><td>";
		echo $this->generar_html_ef('anio_academico');
		echo "</td></tr></table>";
		//<tr><td><span class='ei-form-etiq'><strong>Inscripciones: </strong></span></td><td>";
		echo '<fieldset><legend>Inscripciones</legend><table width=100%><tr><td>';
		echo $this->generar_html_ef('insc_aceptadas', '70px');
		echo "</td><td>";
		echo $this->generar_html_ef('insc_pendientes', '70px');
		echo "</td><td>";
		echo $this->generar_html_ef('insc_rechazadas', '70px');
		echo "</td></tr></table>";
	}
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_inscripciones = guarani_mensajes::get('800_campos_sin_seleccion', array('Inscripciones')); 
		echo "
		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;
			if (!this.ef('insc_aceptadas').chequeado() && !this.ef('insc_pendientes').chequeado() && !this.ef('insc_rechazadas').chequeado()) {
				notificacion.agregar('$msj_inscripciones');
				ok = false;
			}
			return ok; 
		}
		";
	}
}
?>