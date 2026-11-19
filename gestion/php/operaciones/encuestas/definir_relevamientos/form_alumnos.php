<?php
/**
 * 
 * @package Encuestas
 */
class form_alumnos extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table width='100%'><tr><td colspan=3>";
		$this->generar_html_ef('inscriptos_comision');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('cursada_aprobada');
		echo '</td><td>';
		$this->generar_html_ef('cursada_promocionada', '70px');
		echo '</td><td>';
		$this->generar_html_ef('cursada_desaprobada', '110px');
		echo '</td></tr></table>';
		echo "<table width='100%'><tr><td colspan=2>";
		$this->generar_html_ef('label_alumnos');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('anio_academico', '110px');
		echo '</td><td>';
		$this->generar_html_ef('periodo_lectivo');
		echo '</td></tr></table>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$inscriptos_comision_todos = encuesta::inscriptos_comision_todos;
		$inscriptos_comision_algunos = encuesta::inscriptos_comision_algunos;

		$msj_falta_resultado = guarani_mensajes::get('800ENC_falta_resultado');

		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__inscriptos_comision__procesar = function(es_inicial)
		{
			if (this.ef('inscriptos_comision').get_estado() == '$inscriptos_comision_todos') {
				this.ef('cursada_aprobada').ocultar(true);
				this.ef('cursada_promocionada').ocultar(true);
				this.ef('cursada_desaprobada').ocultar(true);
			} else {
				this.ef('cursada_aprobada').mostrar();
				this.ef('cursada_promocionada').mostrar();
				this.ef('cursada_desaprobada').mostrar();
			}
		}

		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			if (this.ef('inscriptos_comision').get_estado() == '$inscriptos_comision_algunos' &&
				!this.ef('cursada_aprobada').chequeado() &&
				!this.ef('cursada_promocionada').chequeado() &&
				!this.ef('cursada_desaprobada').chequeado()) {
				notificacion.agregar('$msj_falta_resultado');
				return false;
			}
			return true;
		}
		";
	}

}
?>