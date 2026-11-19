<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Operación
 * 
 */
class form_autocompletar extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '	<table width=100%>
			<tr>
				<td>'; $this->generar_html_ef('campo_a_completar'); echo '</td>
				<td>'; $this->generar_html_ef('actua_como'); echo '</td>
				<td>'; $this->generar_html_ef('estado'); echo '</td>
				<td>'; $this->generar_html_ef('interfaz', '30px'); echo '</td>
				<td>'; $this->generar_botones(); echo '</td>
				<td>
			</tr>
		</table>';
		 
		$this->evento('completar')->set_en_botonera(false);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$campo_actua_como 	= requisito::autocompletar_campo_comportamiento;
		$campo_estado 		= requisito::autocompletar_campo_estado;
		$para_todos			= requisito::autocompletar_para_todos;
		$para_gestion		= requisito::autocompletar_para_gestion;
		$para_autogestion	= requisito::autocompletar_para_autogestion;

		echo "

		{$this->objeto_js}.hay_cambios = function(ef_actual) {
			return false;
		}
		
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__campo_a_completar__procesar = function(es_inicial)
		{
			var completar = this.ef('campo_a_completar').get_estado();
			
			if (completar == '$campo_actua_como') {
				this.ef('actua_como').mostrar(true);
				this.ef('estado').ocultar(true);
			} else if (completar == '$campo_estado') {
				this.ef('actua_como').ocultar(true);
				this.ef('estado').mostrar(true);
			} else {
				this.ef('actua_como').ocultar(true);
				this.ef('estado').ocultar(true);
			}
		}
		
		//---- Eventos ---------------------------------------------

		{$this->objeto_js}.evt__completar = function()
		{
			var form_ml = this.controlador.dep('form_ml_conf_requisitos');
			var filas = form_ml.filas();

			var criterio = this.ef('interfaz').get_estado();
			var campo	= this.ef('campo_a_completar').get_estado();
			
			for (id_fila in filas) {
				var datos = form_ml.get_datos();
				var interfaz_fila = form_ml.ef('interfaz').ir_a_fila(filas[id_fila]).get_estado();
				if (criterio == '$para_todos') {
					if (campo == '$campo_actua_como' && this.ef('actua_como').tiene_estado()) {
						form_ml.ef('actua_como').ir_a_fila(filas[id_fila]).set_estado(this.ef('actua_como').get_estado());
					} else if (campo == '$campo_estado' && this.ef('estado').tiene_estado()) {
						form_ml.ef('activo').ir_a_fila(filas[id_fila]).set_estado(this.ef('estado').get_estado());
					}
				} else if (criterio == '$para_gestion' && interfaz_fila == '$para_gestion') {
					if (campo == '$campo_actua_como' && this.ef('actua_como').tiene_estado()) {
						form_ml.ef('actua_como').ir_a_fila(filas[id_fila]).set_estado(this.ef('actua_como').get_estado());
					} else if (campo == '$campo_estado' && this.ef('estado').tiene_estado()) {
						form_ml.ef('activo').ir_a_fila(filas[id_fila]).set_estado(this.ef('estado').get_estado());
					}
			    } else if (criterio == '$para_autogestion' && interfaz_fila == '$para_autogestion') {
			    	if (campo == '$campo_actua_como' && this.ef('actua_como').tiene_estado()) {
						form_ml.ef('actua_como').ir_a_fila(filas[id_fila]).set_estado(this.ef('actua_como').get_estado());
					} else if (campo == '$campo_estado' && this.ef('estado').tiene_estado()) {
						form_ml.ef('activo').ir_a_fila(filas[id_fila]).set_estado(this.ef('estado').get_estado());
					}
			    }
			}
			return false;
		}
		
		";	
	}
}
?>