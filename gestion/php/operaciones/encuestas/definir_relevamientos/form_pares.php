<?php
/**
 * 
 * @package Encuestas
 */
class form_pares extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table width='100%'><tr><td>";
		$this->generar_html_ef('pares_destinatario');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('pares_responsable');
		echo '</td></tr></table>';
		echo "<table width='100%'><tr><td colspan=2>";
		$this->generar_html_ef('label_comisiones');
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
		$pares_todos = encuesta::pares_todos;

		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__pares_destinatario__procesar = function(es_inicial)
		{
			if (this.ef('pares_destinatario').get_estado() == '$pares_todos') {
				this.ef('pares_responsable').ocultar(true);
			} else {
				this.ef('pares_responsable').mostrar();
			}
		}
		";
	}
}
?>