<?php 
class ct_form_carga extends guarani_pers_ei_formulario_ml
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
        		if($oef = "first_empty_col") {
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
					if ($primera) {
						//$extra = 'colspan="'.($this->_colspan + 1).'"';
						$cspan[$ef]++;
					}
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
/*
	function extender_objeto_js()
	{
		echo "
		//---- Validacion general ----------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
		}
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__elemento__procesar = function(es_inicial, fila)
		{
		}
		
		{$this->objeto_js}.evt__inscripto_cursada__procesar = function(es_inicial, fila)
		{
		}
		
		{$this->objeto_js}.evt__inscripto_cursada_fecha__procesar = function(es_inicial, fila)
		{
		}
		
		{$this->objeto_js}.evt__cursada__procesar = function(es_inicial, fila)
		{
		}
		
		{$this->objeto_js}.evt__cursada_fecha__procesar = function(es_inicial, fila)
		{
		}
		
		{$this->objeto_js}.evt__aprobada__procesar = function(es_inicial, fila)
		{
		}
		
		{$this->objeto_js}.evt__aprobada_fecha__procesar = function(es_inicial, fila)
		{
		}
		
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__elemento__validar = function(fila)
		{
		}
		
		{$this->objeto_js}.evt__inscripto_cursada__validar = function(fila)
		{
		}
		
		{$this->objeto_js}.evt__inscripto_cursada_fecha__validar = function(fila)
		{
		}
		
		{$this->objeto_js}.evt__cursada__validar = function(fila)
		{
		}
		
		{$this->objeto_js}.evt__cursada_fecha__validar = function(fila)
		{
		}
		
		{$this->objeto_js}.evt__aprobada__validar = function(fila)
		{
		}
		
		{$this->objeto_js}.evt__aprobada_fecha__validar = function(fila)
		{
		}
		";
	}*/
}

?>