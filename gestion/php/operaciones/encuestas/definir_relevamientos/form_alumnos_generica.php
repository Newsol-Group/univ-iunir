<?php
/**
 * 
 * @package Encuestas
 */
class form_alumnos_generica extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table width='100%'><tr><td colspan=2>";
		$this->generar_html_ef('alumnos');
		echo '</td></tr><tr><td colspan=2>';
		$this->generar_html_ef('label_responder');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('alumnos_inscriptos', '70px');
		echo '</td><td>';
		$this->generar_html_ef('alumnos_reinscriptos', '70px');
		echo '</td></tr><tr><td colspan=2>';
		$this->generar_html_ef('anios_academicos', '160px');
		echo '</td></tr><tr><td colspan=2>';
		$this->generar_html_ef('tesis_estados', '115px');
		echo '</td></tr></table>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$alumnos_todos = encuesta::alumnos_todos;
		$alumnos_algunos = encuesta::alumnos_algunos;
		$alumnos_estado_tesis = encuesta::alumnos_estado_tesis;

		$msj_inscriptos_reinscriptos = guarani_mensajes::get('800ENC_inscriptos_reinscriptos');

		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__alumnos__procesar = function(es_inicial)
		{
			switch (this.ef('alumnos').get_estado()) {
				case '$alumnos_todos':
					this.ef('label_responder').ocultar();
					this.ef('alumnos_inscriptos').ocultar(true);
					this.ef('alumnos_reinscriptos').ocultar(true);
					this.ef('anios_academicos').ocultar(true);
					this.ef('tesis_estados').ocultar(true);
					break;
				case '$alumnos_algunos':
					this.ef('label_responder').mostrar();
					this.ef('alumnos_inscriptos').mostrar();
					this.ef('alumnos_reinscriptos').mostrar();
					this.ef('anios_academicos').mostrar();
					this.ef('tesis_estados').ocultar(true);
					break;
				case '$alumnos_estado_tesis':
					this.ef('label_responder').ocultar(true);
					this.ef('alumnos_inscriptos').ocultar(true);
					this.ef('alumnos_reinscriptos').ocultar(true);
					this.ef('anios_academicos').ocultar(true);
					this.ef('tesis_estados').mostrar();
					break;
			}
		}

		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			if (this.ef('alumnos').get_estado() == '$alumnos_algunos' &&
				!this.ef('alumnos_inscriptos').chequeado() &&
				!this.ef('alumnos_reinscriptos').chequeado()) {
				notificacion.agregar('$msj_inscriptos_reinscriptos');
				return false;
			}
			return true;
		}
		";
	}

}
?>