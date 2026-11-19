<?php
class form_ml_resultados_x_area extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
	{$this->objeto_js}.elem_rechk = function() {
			if(this.elem_op) {
				for(var j in this.elem_op) {
					if(this.elem_op[j].f !== false) {
						var in_array = false;
						for(var i in this.filas()) {
							if(this.elem_op[j].f === this.filas()[i]) {
								in_array = true;
							} 
						}
						if(!in_array) {
							this.elem_op[j].f = false;
						}
					}
				}

				for(var i in this.filas()) {
					var ef = this.ef('resultado').ir_a_fila(this.filas()[i]);
					var ti = ef.input();
					ti.options.length = 0;
					for(var j in this.elem_op) {
						if (this.elem_op[j].f === false|| this.elem_op[j].f === this.filas()[i]) {
							ti.options[ti.options.length] = new Option(this.elem_op[j].i,this.elem_op[j].v);
						}
						if (this.elem_op[j].f === this.filas()[i]) {
							ti.options[ti.options.length-1].selected = true;
						}
					}
				}
			}
		}
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__resultado__procesar = function(es_inicial, fila)
		{
			tef = {$this->objeto_js}.ef('resultado').ir_a_fila(fila);
			var ti = tef.input();
			if(!{$this->objeto_js}.elem_op) {
				{$this->objeto_js}.elem_op = [];
				for (var i = 0; i<ti.options.length; i++) {
					{$this->objeto_js}.elem_op[i] = {v: ti.options[i].value, i: ti.options[i].innerHTML,f: false}; 
				}
			}
			for(var i in {$this->objeto_js}.elem_op) {
				if(ti.options[ti.selectedIndex].value != apex_ef_no_seteado && {$this->objeto_js}.elem_op[i].v == ti.options[ti.selectedIndex].value && {$this->objeto_js}.elem_op[i].f === false) {
					{$this->objeto_js}.elem_op[i].f = fila;
				} else if ({$this->objeto_js}.elem_op[i].v != ti.options[ti.selectedIndex].value && {$this->objeto_js}.elem_op[i].f === fila) {
					{$this->objeto_js}.elem_op[i].f = false;
				} 
			}
			var of = {$this->objeto_js}.filas();
			if(!es_inicial || fila === of[of.length-1] ) {
				{$this->objeto_js}.elem_rechk();
			} 
			
		}
		";
	}

}

?>