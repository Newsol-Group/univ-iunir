<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Alumnos
 */
class form_fechas extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" width="100%">';
			echo '<tr>';
				echo '<td colspan="3">';
				$this->generar_html_ef('tipo_filtro');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td colspan="3">';
				$this->generar_html_ef('aplicacion');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('vencimiento', '245px');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('fecha_desde', '20px');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('fecha_hasta', '30px');
				echo '</td>';
			echo '</tr>';
		echo '</table>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$filtro = $this->controlador()->dep('filtro')->get_objeto_js();
		$cuadro = $this->controlador()->dep('cuadro')->get_objeto_js();

		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__tipo_filtro__procesar = function(es_inicial)
		{
			if (this.ef('tipo_filtro').get_estado() == 'F') {
				this.ef('aplicacion').ocultar(true);
				this.ef('vencimiento').mostrar();
				$filtro.ocultar();
				$cuadro.ocultar();
			} else {
				this.ef('aplicacion').mostrar();
				this.evt__aplicacion__procesar();
				$filtro.mostrar();
				$cuadro.mostrar();
			}
		}

		{$this->objeto_js}.evt__aplicacion__procesar = function(es_inicial)
		{
			if (this.ef('tipo_filtro').get_estado() == 'F' || this.ef('aplicacion').tiene_estado() && this.ef('aplicacion').get_estado() == 'con_venc') {
				this.ef('vencimiento').mostrar();
			} else {
				this.ef('vencimiento').ocultar(true);
				this.ef('fecha_desde').ocultar(true);
				this.ef('fecha_hasta').ocultar(true);
			}
		}

		{$this->objeto_js}.evt__vencimiento__procesar = function(es_inicial)
		{
			if (this.ef('vencimiento').tiene_estado()) {
				this.ef('fecha_desde').mostrar();
				if (this.ef('vencimiento').get_estado() == 'E') {
					this.ef('fecha_hasta').mostrar();
				} else {
					this.ef('fecha_hasta').ocultar(true);
				}
			} else {
				this.ef('fecha_desde').ocultar(true);
				this.ef('fecha_hasta').ocultar(true);
			}
		}
		";
	}
}
?>