<?php
class arbol_certif extends guarani_pers_ei_arbol
{
function extender_objeto_js()
	{
		echo "

		var nodo_seleccionado = null;
		var style_previo = null;
		
		{$this->objeto_js}.elemento_seleccionar = function(nodo) {
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
				this.controlador.ajax_html('get_form_data',[nodo.getAttribute('id_nodo'), 'get_certificado_detalle', 'formulario_certif', 'modulo_certif'], this.controlador.dep('formulario_certif').cuerpo());
				this.controlador.ajax_html('get_cuadro_data',[nodo.getAttribute('id_nodo'), 'get_actividades', 'cuadro_activ_certif', 'act_modulo_certif'],document.getElementById(this.controlador.dep('cuadro_activ_certif')._input_submit).parentNode);
			}
		};
		
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
				var nodo_cert = this.get_nodo_cert(nodo);
				this.controlador.ajax_html('get_form_data',[nodo_cert.getAttribute('id_nodo'), 'get_certificado_detalle', 'formulario_certif', 'modulo_certif'], this.controlador.dep('formulario_certif').cuerpo());
				this.controlador.ajax_html('get_cuadro_data',[nodo.getAttribute('id_nodo'), 'get_actividades', 'cuadro_activ_certif', 'act_modulo_certif'],document.getElementById(this.controlador.dep('cuadro_activ_certif')._input_submit).parentNode);
			}
		}
		
		{$this->objeto_js}.get_nodo_cert = function(nodo) {
			if(nodo.parentNode.tagName.toLowerCase() == 'ul' && nodo.parentNode.className == 'ei-arbol-raiz') {
				return nodo;
			} else if(nodo.parentNode.parentNode.tagName.toLowerCase() == 'li') {
				return this.get_nodo_cert(nodo.parentNode.parentNode);
			}
			return false;
			
		}
		";
	}
}

?>