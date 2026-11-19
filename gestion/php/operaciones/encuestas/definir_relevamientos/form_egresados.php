<?php
/**
 * 
 * @package Encuestas
 */
class form_egresados extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table width='100%'><tr><td>";
		$this->generar_html_ef('egresados');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('certificados');
		echo '</td></tr></table><table><tr><td>';
		$this->generar_html_ef('fecha_egreso_desde', '140px');
		echo '</td><td>';
		$this->generar_html_ef('fecha_egreso_hasta', '50px');
		echo '</td></tr></table>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$mensaje_fecha_invalida = guarani_mensajes::get('800_control_fechas', array(' de egreso hasta', 'menor', ' desde.'));
		$egresados_todos = encuesta::egresados_todos;

		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__egresados__procesar = function(es_inicial)
		{
			if (this.ef('egresados').get_estado() == '$egresados_todos') {
				this.ef('certificados').ocultar(true);
			} else {
				this.ef('certificados').mostrar();
			}
		}

		//---- Validacion general ----------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;
			if (this.ef('fecha_egreso_hasta').tiene_estado()) {
				if (this.ef('fecha_egreso_hasta').fecha() < this.ef('fecha_egreso_desde').fecha()) {
					notificacion.agregar('$mensaje_fecha_invalida');				
					ok = false;
				}
			}
			return ok;
		} 
		";
	}
}
?>