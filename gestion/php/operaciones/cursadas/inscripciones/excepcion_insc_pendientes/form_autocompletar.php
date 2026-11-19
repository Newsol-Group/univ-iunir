<?php
class form_autocompletar extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '	<table width=100$>
					<tr>
						<td>'; $this->generar_html_ef('motivo'); echo '</td>
						<td>'; $this->generar_botones(); echo '</td>
					</tr>
				</table>';

		$this->evento('completar')->set_en_botonera(false);
	}

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.hay_cambios = function(ef_actual) {
			return false;
		}";

		echo "
		{$this->objeto_js}.evt__completar = function()
		{
			var motivo = this.ef('motivo').get_estado();

			var form_ml = this.controlador.dep('form_ml_pendientes');
			var filas = form_ml.filas();

			for (id_fila in filas) {
				form_ml.ef('motivo_excepcion').ir_a_fila(filas[id_fila]).set_estado(motivo);
			}
			return false;
		}";
	}

}
?>