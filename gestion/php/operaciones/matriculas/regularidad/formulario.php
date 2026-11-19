<?php
class formulario extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$this->generar_html_ef('anio_academico');
		echo '<fieldset><legend>Rango a Verificar</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('fecha_control_desde');
					echo '</td><td>';
					$this->generar_html_ef('fecha_control_hasta');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '</fieldset>';
		$this->generar_html_ef('nro_resolucion');
		$this->generar_html_ef('alumnos_a_verificar');
		$this->generar_html_ef('fecha_venc_readmision', '280px');
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$mje_fechas = guarani_mensajes::get('800_control_fechas', array('desde (del rango a verificar)', 'mayor o igual', 'hasta.'));
		echo "
		//---- Procesamiento de EFs --------------------------------
		{$this->objeto_js}.evt__anio_academico__procesar = function(es_inicial)
		{
			if (! es_inicial && this.ef('anio_academico').tiene_estado()) {
				var anio = this.ef('anio_academico').get_estado();
				var fecha_desde = '01/04/' + (parseInt(anio) - 1);
				var fecha_hasta = '31/03/' + anio;
				var fecha_venc_readmision = '31/03/' + (parseInt(anio) + 1);
				this.ef('fecha_control_desde').cambiar_valor(fecha_desde);
				this.ef('fecha_control_hasta').cambiar_valor(fecha_hasta);
				this.ef('fecha_venc_readmision').cambiar_valor(fecha_venc_readmision);
			} 
		}
		{$this->objeto_js}.evt__alumnos_a_verificar__procesar = function(es_inicial)
		{
			if (! es_inicial && this.ef('alumnos_a_verificar').tiene_estado()) {
				var alumnos_a_verificar = this.ef('alumnos_a_verificar').get_estado();
				if (alumnos_a_verificar != '".vencimiento_regularidad::alumnos_no_regulares."') {
					this.ef('fecha_venc_readmision').mostrar();
					if (this.ef('anio_academico').tiene_estado()) {
						var anio = this.ef('anio_academico').get_estado();
						var fecha_venc_readmision = '31/03/' + (parseInt(anio) + 1);
						this.ef('fecha_venc_readmision').cambiar_valor(fecha_venc_readmision);
					}
				} else {
					this.ef('fecha_venc_readmision').ocultar();
					this.ef('fecha_venc_readmision').resetear_estado();
				}
			}
		}
		//---- Validacion general ----------------------------------
		{$this->objeto_js}.evt__validar_datos = function()
		{
			if (this.ef('fecha_control_desde').fecha() >= this.ef('fecha_control_hasta').fecha()) {
				notificacion.agregar('$mje_fechas');
				return false;
			}
			return true;
		}
		";
	}
}
?>