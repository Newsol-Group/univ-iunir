<?php
class form_modulos extends guarani_pers_ei_formulario
{
	
	function generar_layout()
	{
		if($this->_elemento_formulario['nombre']->get_estado() != null) 
		{
			$lstpst = array('nombre', 'forma_aprobacion_nombre', 'origen_creditos', 'creditos_otorgados');
			$this->generar_html_ef('nombre');
			echo "<hr style='clear:both' class='ci-pant-sep-ei'/>\n";
			$this->generar_html_ef('forma_aprobacion_nombre');
			$this->generar_html_ef('valor_aprobacion');	
			echo "<hr style='clear:both' class='ci-pant-sep-ei'/>\n";
			$this->generar_html_ef('origen_creditos');
			$this->generar_html_ef('creditos_otorgados');
			$this->_lista_ef_post = $lstpst;
		} else {
			$this->_lista_ef_post = array();
		}
	}
	
	function vista_pdf($salida) 
	{
		$efs = $this->get_datos();
		$salida->texto("<i>{$efs['nombre']}</i>",9);
		$salida->separacion();
		$fl[] = "<b>Forma aprobacion:</b> ".$efs['forma_aprobacion_nombre'];
		$fila[] = $fl;
		$fila[] = array("<b>Origen créditos:</b> ".$efs['origen_creditos'],"<b>Créditos otorgados:</b> ".$efs['creditos_otorgados']);
		//$salida->texto(print_r($fila,true));
		$datos['datos_tabla'] = $fila; 
		$salida->tabla($datos,false,8,array("showLines"=>0, "shaded"=>0, "xPos"=>0,"xOrientation"=>"right")); //
	}
	
	protected function generar_html_ef($ef, $ancho_etiqueta=null)
	{
		if (! in_array($ef, $this->_lista_ef_post)) {
			//Si el ef no se encuentra en la lista posibles, es probable que se alla quitado con una restriccion o una desactivacion manual
			return;
		}
		$clase = 'ei-form-fila';
		$estilo_nodo = "";//$estilo;
		$id_ef = $this->_elemento_formulario[$ef]->get_id_form();
		if (! $this->_elemento_formulario[$ef]->esta_expandido()) {
			$clase .= ' ei-form-fila-oculta';
			$estilo_nodo .= "display:none";
		}
		if (isset($this->_info_formulario['resaltar_efs_con_estado']) 
				&& $this->_info_formulario['resaltar_efs_con_estado'] && $this->_elemento_formulario[$ef]->seleccionado()) {
			$clase .= ' ei-form-fila-filtrada';
		}
		echo "<div class='$clase' style='$estilo_nodo' id='nodo_$id_ef'>\n";		
		if ($this->_elemento_formulario[$ef]->tiene_etiqueta()) {
			$this->generar_etiqueta_ef($ef, $ancho_etiqueta);
			//--- El margin-left de 0 y el heigth de 1% es para evitar el 'bug de los 3px'  del IE
			$ancho = isset($ancho_etiqueta) ? $ancho_etiqueta : $this->_ancho_etiqueta;
			echo "<div id='cont_$id_ef' style='margin-left:$ancho;_margin-left:0;_height:1%;'>\n";
			$this->generar_input_ef($ef);
			echo "</div>";
			if (isset($this->_info_formulario['expandir_descripcion']) && $this->_info_formulario['expandir_descripcion']) {
				echo '<span class="ei-form-fila-desc">'.$this->_elemento_formulario[$ef]->get_descripcion().'</span>';
			}

		} else {		
			$this->generar_input_ef($ef);
		}
		echo "</div>\n";		
	}
	
	protected function generar_etiqueta_ef($ef, $ancho_etiqueta=null)
	{
		$estilo = $this->_elemento_formulario[$ef]->get_estilo_etiqueta();
		$marca ='';		
		if ($estilo == '') {
	        		if ($this->_elemento_formulario[$ef]->es_obligatorio()) {
	    	        		$estilo = 'ei-form-etiq-oblig';
				$marca = '(*)';
        			} else {
	            		$estilo = 'ei-form-etiq';
    	    		}
		}
		$desc='';
		if (!isset($this->_info_formulario['expandir_descripcion']) || ! $this->_info_formulario['expandir_descripcion']) {
			$desc = $this->_elemento_formulario[$ef]->get_descripcion();		
			if ($desc !=""){
				$desc = toba_parser_ayuda::parsear($desc);
				$desc = toba_recurso::imagen_toba("descripcion.gif",true,null,null,$desc);
			}
		}
		$id_ef = $this->_elemento_formulario[$ef]->get_id_form();					
		$editor = $this->generar_vinculo_editor($ef);
		$etiqueta = $this->_elemento_formulario[$ef]->get_etiqueta();
		//--- El _width es para evitar el 'bug de los 3px'  del IE
		$ancho = isset($ancho_etiqueta) ? $ancho_etiqueta : $this->_ancho_etiqueta;
		echo "<label style='_width:$ancho;font-weight: bold' for='$id_ef' class='$estilo'>$editor $desc $etiqueta $marca</label>\n";
	}
	
}
?>