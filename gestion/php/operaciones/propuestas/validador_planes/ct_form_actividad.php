<?php 
class ct_form_actividad extends form_ml_asignaciones
{
	
	/*function generar_layout()
	{
		$this->generar_html_ef('elemento');
		$this->generar_html_ef('inscripto_cursada');
		$this->generar_html_ef('inscripto_cursada_fecha');
		$this->generar_html_ef('cursada');
		$this->generar_html_ef('cursada_fecha');
		$this->generar_html_ef('aprobada');
		$this->generar_html_ef('aprobada_fecha');
	}*/
	
	function generar_formulario_encabezado() {
	//¿Algún EF tiene etiqueta?
		$alguno_tiene_etiqueta = false;
		$cspan = array();
		$oef = null;
		foreach ($this->_lista_ef_post as $ef) {
			if ($this->_elemento_formulario[$ef]->get_etiqueta() != '') {
        		$alguno_tiene_etiqueta = true;
        		if($oef == "first_empty_col") {
        			$cspan[$ef] = $cspan[$oef]+1;
        		} else {
        			$cspan[$ef] = 1;
        		}
        		$oef = $ef;
			} else {
				if($oef == null) {
					$oef = "first_empty_col";
					$cspan[$oef] = 0;
				}
				$cspan[$oef]++;
			}
		}
		if ($alguno_tiene_etiqueta) {
			echo "<thead id='cabecera_{$this->objeto_js}'>\n";		
			//------ TITULOS -----	
			echo "<tr>\n";
			$primera = true;
			foreach ($this->_lista_ef_post	as	$ef){
				if(isset($cspan[$ef])) {
					$id_form = $this->_elemento_formulario[$ef]->get_id_form_orig();	
					$extra = '';
					/*if ($primera) {
						//$extra = 'colspan="'.($this->_colspan + 1).'"';
						$cspan[$ef]++;
					}*/
					echo "<th colspan='$cspan[$ef]' id='nodo_$id_form' class='ei-ml-columna' style='text-align:center'>\n";
					if ($this->_elemento_formulario[$ef]->get_toggle()) {
						$this->_hay_toggle = true;
						$id_form_toggle = 'toggle_'.$id_form;
						echo "<input id='$id_form_toggle' type='checkbox' class='ef-checkbox' onclick='{$this->objeto_js}.toggle_checkbox(\"$ef\")' />";
					}
					$this->generar_etiqueta_columna($ef);
					echo "</th>\n";
					$primera = false;
				}
			}
			if ($this->_info_formulario['filas_ordenar'] && $this->_ordenar_en_linea) {
				echo "<th class='ei-ml-columna'>&nbsp;\n";
				echo "</th>\n";
			}		
	        //-- Eventos sobre fila
			if($this->cant_eventos_sobre_fila() > 0){
				foreach ($this->get_eventos_sobre_fila() as $evento) {
					echo "<th class='ei-ml-columna ei-ml-columna-extra'>&nbsp;\n";
					if (toba_editor::modo_prueba()) {
						echo toba_editor::get_vinculo_evento($this->_id, $this->_info['clase_editor_item'], $evento->get_id())."\n";
					}
		            echo "</th>\n";
				}
			}		
			if ($this->_info_formulario['filas_agregar'] && $this->_borrar_en_linea) {
				echo "<th class='ei-ml-columna'>&nbsp;\n";
				echo "</th>\n";				
			}
			echo "</tr>\n";
			echo "</thead>\n";
		}
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Validacion general ----------------------------------
		/*
		{$this->objeto_js}.evt__validar_datos = function()
		{
		}
		*/
		/*{$this->objeto_js}.elem_rechk = function() {
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
					var ef = this.ef('elemento').ir_a_fila(this.filas()[i]);
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
		
		{$this->objeto_js}.evt__elemento__procesar = function(es_inicial, fila)
		{
			tef = {$this->objeto_js}.ef('elemento').ir_a_fila(fila);
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
			
		}*/
		
		{$this->objeto_js}.evt__inscripto_cursada__procesar = function(es_inicial, fila)
		{
			var tef = {$this->objeto_js}.ef('inscripto_cursada').ir_a_fila(fila);
			var nxt = {$this->objeto_js}.ef('inscripto_cursada_fecha').ir_a_fila(fila);
			if(tef.get_estado() == 'N') {
				nxt.desactivar();
				nxt.resetear_estado('');
			} else {
				nxt.activar();
			} 
		}
		
		{$this->objeto_js}.evt__inscripto_cursada_fecha__procesar = function(es_inicial, fila)
		{
			var tef = {$this->objeto_js}.ef('inscripto_cursada_fecha').ir_a_fila(fila);
			var nxt = {$this->objeto_js}.ef('cursada').ir_a_fila(fila);
			var nxf = {$this->objeto_js}.ef('cursada_fecha').ir_a_fila(fila);
			var xxf = {$this->objeto_js}.ef('aprobada_fecha').ir_a_fila(fila);
			var fecha = tef.fecha();
			if(!tef.activo() || !tef.tiene_estado()) {
				nxt.desactivar();
				nxt.set_estado('N');
			} else {
				nxt.activar();
				/*if(nxf.activo()) {
					{$this->objeto_js}.validar_fila_ef(fila, 'cursada_fecha', true);
					if (nxf.tiene_estado() && nxf.fecha()) {
						fecha = nxf.fecha();
					}
				}
				xxf._rango_valido = [fecha];*/
			}
			/*if(xxf.activo()) {
				{$this->objeto_js}.validar_fila_ef(fila, 'aprobada_fecha', true);
			}*/
		}
		
		{$this->objeto_js}.evt__cursada__procesar = function(es_inicial, fila)
		{
			tef = {$this->objeto_js}.ef('cursada').ir_a_fila(fila);
			var nxt = {$this->objeto_js}.ef('cursada_fecha').ir_a_fila(fila);
			if(tef.get_estado() == 'N') {
				nxt.desactivar();
				nxt.resetear_estado('');
			} else {
				nxt.activar();
			}
		}
		
		/*{$this->objeto_js}.evt__cursada_fecha__procesar = function(es_inicial, fila)
		{
			var tef = {$this->objeto_js}.ef('cursada_fecha').ir_a_fila(fila);
			var nxt = {$this->objeto_js}.ef('aprobada').ir_a_fila(fila);
			var nxf = {$this->objeto_js}.ef('aprobada_fecha').ir_a_fila(fila);
			var prf = {$this->objeto_js}.ef('inscripto_cursada_fecha').ir_a_fila(fila);
			var fecha = tef.fecha();
			if(!fecha && prf.activo()) {
				fecha = prf.fecha();
			} 
			if(fecha) {
				nxf._rango_valido = [fecha];
			} else {
				nxf._rango_valido = null;
			}
			if(nxf.activo()) {
				{$this->objeto_js}.validar_fila_ef(fila, 'aprobada_fecha', true);
			}
		
		}*/
		
		{$this->objeto_js}.evt__aprobada__procesar = function(es_inicial, fila)
		{
			tef = {$this->objeto_js}.ef('aprobada').ir_a_fila(fila);
			var nxt = {$this->objeto_js}.ef('aprobada_fecha').ir_a_fila(fila);
			if(tef.get_estado() == 'N') {
				nxt.desactivar();
				nxt.resetear_estado('');
			} else {
				nxt.activar();
			}
		}
		
		{$this->objeto_js}.evt__aprobada_fecha__procesar = function(es_inicial, fila)
		{
		}
		
		//---- Validacion de EFs -----------------------------------
		
		/*{$this->objeto_js}.evt__elemento__validar = function(fila)
		{
		}
		
		{$this->objeto_js}.evt__inscripto_cursada__validar = function(fila)
		{
		}
		*/
		{$this->objeto_js}.evt__inscripto_cursada_fecha__validar = function(fila)
		{
			var p = {$this->objeto_js}.ef('inscripto_cursada').ir_a_fila(fila);
			var a = {$this->objeto_js}.ef('inscripto_cursada_fecha').ir_a_fila(fila);
			if (p.get_estado() == 'N' || (p.get_estado() == 'S' && a.get_estado() != '')) 
			{
				return true;
			}
			a._error = 'Debe ingresar fecha de inscripto en cursada.';
			return false;
		}
		/*
		{$this->objeto_js}.evt__cursada__validar = function(fila)
		{
		}
		*/
		{$this->objeto_js}.evt__cursada_fecha__validar = function(fila)
		{
			var p = {$this->objeto_js}.ef('cursada').ir_a_fila(fila);
			var a = {$this->objeto_js}.ef('cursada_fecha').ir_a_fila(fila);
			var b = {$this->objeto_js}.ef('inscripto_cursada_fecha').ir_a_fila(fila);
			if (p.get_estado() == 'S' && a.get_estado() == '') 
			{
				a._error = 'Debe ingresar fecha de cursada.';
				return false;
			}
			if (p.get_estado() == 'S' && a.get_estado() != '' && b.get_estado() != '' && b.get_estado() > a.get_estado())
			{
				a._error = 'Fecha de cursada debe ser mayor o igual a '+b.get_estado()+'.';
				return false;
			}
			return true;
		}
		/*
		{$this->objeto_js}.evt__aprobada__validar = function(fila)
		{
		}
		*/
		{$this->objeto_js}.evt__aprobada_fecha__validar = function(fila)
		{
			var p = {$this->objeto_js}.ef('aprobada').ir_a_fila(fila);
			var a = {$this->objeto_js}.ef('aprobada_fecha').ir_a_fila(fila);
			var b = {$this->objeto_js}.ef('cursada_fecha').ir_a_fila(fila);
			var c = {$this->objeto_js}.ef('inscripto_cursada_fecha').ir_a_fila(fila);
			if (p.get_estado() == 'S' && a.get_estado() == '') 
			{
				a._error = 'Debe ingresar fecha de cursada.';
				return false;
			}
			if (p.get_estado() == 'S' && a.get_estado() != '' && (b.get_estado() != '' || c.get_estado() != '') && (b.get_estado() > a.get_estado() || c.get_estado() > a.get_estado()) )
			{
				a._error = 'Fecha de aprobada debe ser mayor o igual a '+((b.get_estado()>c.get_estado())?b.get_estado():c.get_estado())+'.';
				return false;
			}
			return true;
		}
		";
	}
}

?>