<?php
class form_seleccion_regla extends guarani_pers_ei_formulario
{

	function get_input_ef($ef)
	{
		ob_start();
		if ($this->controlador()->usar_cabecera_reducida() && $ef == 'regla') {
			echo '</td></tr><tr><td>';
			echo '<div style="margin-left: 12px;margin-top: 5px;margin-bottom: 10px;">';
			$this->generar_input_ef($ef, true);
			echo '</div>';
		} else {
			$this->generar_input_ef($ef, true);
		}
		return ob_get_clean();
	}

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		var g_regla = null;
		
		{$this->objeto_js}.evt__regla__procesar = function(es_inicial)
		{	
			if (es_inicial) g_regla = null;
			regla = this.ef('regla').get_estado();
			if ((! g_regla) || (g_regla != regla)) {
				g_regla = regla;
				this.ocultar_efs(es_inicial);
				if (g_regla == 'nopar') {
					this.controlador.ajax('limpiar_regla', regla, this, this.set_efs_regla);
					return;
				}
                var params = {regla: regla, toba_qs_zona: 1};
				if (! es_inicial) {
					this.controlador.ajax('setear_regla', params, this, this.set_efs_regla);
				} else {
					this.controlador.ajax('cambiar_regla', params, this, this.set_efs_regla);
				}
			}
			if (regla != ".regla::por_puntaje_aprobadas." && regla != ".regla::por_puntaje_cursadas.") {
				this.mostrar_columna_puntaje(false);							
			} else {
				this.mostrar_columna_puntaje(true);
			}
		}
		
		{$this->objeto_js}.set_efs_regla = function(efs)
		{
			if (efs == undefined || efs == '') return;
			for (var ef in efs) {
				if ((this.ef(efs[ef]['ef']) != undefined) || (this.ef(ef) != undefined)) {
					if (ef == 'regla') {
						if (this.ef(ef).activo()) {
							this.ef(ef).set_estado(efs[ef]);
						}	
					} else {
						this.ef(efs[ef]['ef'])._etiqueta = efs[ef]['etiqueta'];
						nodo = this.ef(efs[ef]['ef']).nodo(); 
						nodo_id = nodo.getAttribute('id');
						nodo_id = nodo_id.substring(5);
						nodo = document.getElementById(nodo_id);
						label = get_label(nodo);
						label.innerHTML = '<B>'+efs[ef]['etiqueta']+' (*)</B>';
						if (efs[ef]['valor']) {
							this.ef(efs[ef]['ef']).set_estado(efs[ef]['valor']);
							this._estado_inicial[efs[ef]['ef']] = this.ef(efs[ef]['ef']).get_estado();
						}	
						this.ef(efs[ef]['ef']).mostrar(true,true);
					}	
				}	
			}
		}	
		
		{$this->objeto_js}.mostrar_columna_puntaje = function(mostrar)
		{
			var form = this.controlador.controlador.dep('form_orden');
			if(form) {
				form.mostrar_puntaje(mostrar);
			}
		}
		
		function get_label(inputElem)
		{
			if(inputElem.parentNode){
				if(inputElem.parentNode.tagName=='label'){
					return inputElem.parentNode;
				}
			}
		
			var labels=document.getElementsByTagName('label'),i;
			
			for( i=0; i<labels.length;i++ ){
				if(labels[i].htmlFor==inputElem.id){
					return labels[i];
				}
			}
			return false;
		}
		
		{$this->objeto_js}.ocultar_efs = function(es_inicial)
		{
			for (id_ef in this.efs()) {
				if (id_ef !== 'regla') {
					if (! es_inicial) {
						this.ef(id_ef).resetear_estado();
					}
					this.ef(id_ef).ocultar();
				}
			}
		}
		";
	}

}

?>
