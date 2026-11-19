<?php 
class arbol_plan extends guarani_pers_ei_arbol
{
	function extender_objeto_js()
	{
		echo "
			var nodo_seleccionado = null;
			var style_previo = null;
			
			{$this->objeto_js}.seleccionar_actividad = function(nodo) {
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
					/*this.controlador.ajax_html('get_form_data',[nodo.getAttribute('id_nodo'), 'get_actividad_detalle', 'formulario_correl', 'act_correlativa'], this.controlador.dep('formulario_correl').cuerpo());
					this.controlador.ajax_html('get_arbol_data',[nodo.getAttribute('id_nodo'), 'get_restricciones', 'arbol_requisitos', 'act_correlativa'],this.controlador.dep('arbol_requisitos').cuerpo().parentNode);
					this.controlador.ajax_html('get_esquema_data',[nodo.getAttribute('id_nodo'), 'get_restricciones', 'esquema_correl', 'act_correlativa'],this.controlador.dep('esquema_correl').cuerpo().parentNode);*/
					this.controlador.ajax('set_var', [nodo.getAttribute('id_nodo'), 'act_correlativa'], this, this.reload);
					//this.controlador.ajax('get_arbol_data',[nodo.getAttribute('id_nodo'), 'get_restricciones', 'arbol_requisitos'],this, this.procesar_arbol);
					//this.abrir_arbol(this.controlador.dep('arbol_requisitos'));
				}
			};
			
			{$this->objeto_js}.reload = function () {
				document.location.href = vinculador.crear_autovinculo();
			};
			
			{$this->objeto_js}.seleccionar_modulo = function () {
			};
			
			{$this->objeto_js}.procesar_arbol = function(datos) {
				this.controlador.dep('arbol_requisitos').cuerpo().parentNode.innerHTML = datos;
			};
			/*{$this->objeto_js}.abrir_arbol = function (arbol, nodo) {
				if(!nodo){
					nodo = arbol.get_nodo_raiz();
				}
				alert(arbol.get_nodo_raiz);
				arbol.abrir_nodo(nodo);
				var hijos = arbol.get_nodos_hijos(nodo);
				for (var i = 0; i<hijos.length; i++) {
					this.abrir_arbol(arbol, hijos[i]);
				}
			} */
		";
	}
}

?>