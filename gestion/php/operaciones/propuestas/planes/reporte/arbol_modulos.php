<?php
class arbol_modulos extends guarani_pers_ei_arbol
{
	function extender_objeto_js()
	{
		echo "

		var nodo_seleccionado = null;
		var style_previo = null;
		
		{$this->objeto_js}.seleccionar_modulo = function(nodo) {
			if (nodo_seleccionado) {
				nodo_seleccionado.style.border = style_previo;
			}
			if (nodo_seleccionado == nodo) {
				revision_seleccionada = null;
				nodo_seleccionado = null;
				style_previo = null;
			} else {
				style_previo = nodo.style.border;
				nodo.style.border = '1px dotted #ee5500';
				nodo_seleccionado = nodo;
				this.controlador.ajax_html('get_form_data',[nodo.getAttribute('id_nodo'), 'get_modulo_detalle', 'formulario_modulos', 'modulo_revision'], this.controlador.dep('formulario_modulos').cuerpo());
				this.controlador.ajax_html('get_cuadro_data',[nodo.getAttribute('id_nodo'), 'get_actividades', 'cuadro_modulos', 'modulo_revision'],document.getElementById(this.controlador.dep('cuadro_modulos')._input_submit).parentNode);
			}
		};
		";
	}

}
?>