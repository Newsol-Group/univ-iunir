<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Comisiones
 * 
 */
class form_compartir_banda_horaria extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table width="70%">';
			echo '<tr>';
				echo '<td colspan=2>';
				$this->generar_html_ef('banda_horaria');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('dia_semana');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('tipo_clase');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('fecha_desde');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('hora_inicio');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('fecha_hasta');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('hora_finalizacion');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('cantidad_horas');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('periodicidad');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td colspan=2>';
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
		parent::extender_objeto_js();
		
		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__banda_horaria__procesar = function(es_inicial)
		{
			if (! es_inicial) {
				if (this.ef('banda_horaria').tiene_estado()) {
			 		var banda_horaria = this.ef('banda_horaria').get_estado();
					var params = [banda_horaria];
					this.controlador.ajax('datos_banda_horaria', params, this, this.respuesta_datos_banda_horaria);
				}
			}
		}

		{$this->objeto_js}.respuesta_datos_banda_horaria = function(respuesta)
		{
			if (typeof respuesta == 'object') {
				this.ef('dia_semana').set_estado(respuesta['dia_semana_nombre']);
				this.ef('hora_inicio').set_estado(respuesta['hora_inicio_visual']);
				this.ef('hora_finalizacion').set_estado(respuesta['hora_finalizacion_visual']);
				this.ef('fecha_desde').set_estado(respuesta['fecha_desde_visual']);
				this.ef('fecha_hasta').set_estado(respuesta['fecha_hasta_visual']);
				this.ef('cantidad_horas').set_estado(respuesta['cantidad_horas']);
				this.ef('periodicidad').set_estado(respuesta['periodicidad']);
				this.ef('observaciones').set_estado(respuesta['observaciones']);
			}
		}
		";
	}
}
?>