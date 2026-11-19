<?php
class form_ml_inscripciones extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$validado_con_renaper = persona::validado_con_renaper;
		$descr_renaper = persona::descr_renaper;
		echo "
		agregar_tooltip_td_renaper();
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__procesar__procesar = function(es_inicial, fila)
		{
				var procesar = this.ef('procesar').ir_a_fila(fila).chequeado();
				if (procesar) {
					this.ef('plan_version').ir_a_fila(fila).activar();
					
					this.ef('anio_periodo_insc').ir_a_fila(fila).activar();
					
					this.ef('tipo_ingreso').ir_a_fila(fila).activar();
				} else {
					this.ef('plan_version').ir_a_fila(fila).resetear_estado();
					this.ef('plan_version').ir_a_fila(fila).desactivar();
					
					this.ef('anio_periodo_insc').ir_a_fila(fila).resetear_estado();
					this.ef('anio_periodo_insc').ir_a_fila(fila).desactivar();
					
					this.ef('tipo_ingreso').ir_a_fila(fila).desactivar();
				}
		}
		function agregar_tooltip_td_renaper()
		{
			//Esto es una magia para que pueda recuperar la etiqueta de validado con renaper
			var aTags = document.getElementsByTagName('strong');
			var searchText = '$validado_con_renaper';
			for (var i = 0; i < aTags.length; i++) {
				if (aTags[i].textContent == searchText) {
					aTags[i].setAttribute('id','id_renaper_strong');
					aTags[i].setAttribute('title', '$descr_renaper');
					aTags[i].style.cursor = 'pointer';										
					break;
				}
			}
		}
		
		";
	}
}
?>