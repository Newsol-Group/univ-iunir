<?php 
class form_planes extends guarani_pers_ei_formulario
{
	private $lr = 'c';
	
	function generar_layout()
	{
		$this->generar_html_ef('nombre');
		echo "<hr style='clear:both' class='ci-pant-sep-ei'/>\n";
		$this->generar_html_ef('modalidad');
		$this->generar_html_ef('propuesta');
		$this->generar_html_ef('tope_creditos_cursado');
		$this->generar_html_ef('tope_creditos_reg_vigentes');
		$this->generar_html_ef('estado_plan');
		$this->generar_html_ef('documento_plan_desc');
		echo "<hr style='clear:both' class='ci-pant-sep-ei'/>\n";
		$this->generar_html_ef('version');
		$this->generar_html_ef('documento_version_desc');
		$this->generar_html_ef('duracion_teorica');
		$this->generar_html_ef('cnt_materias');
		$this->generar_html_ef('duracion_en_anios');
		$this->generar_html_ef('cnt_optativas');
		$this->generar_html_ef('duracion_en_meses');
		$this->generar_html_ef('cnt_idiomas');
		$this->generar_html_ef('estado_version');
		$this->generar_html_ef('fecha_entrada_vigencia');
	}
	
	function vista_pdf($salida) {
		$efs = $this->get_datos();
		//$fila[] = array("<b>Nombre:</b> ".$efs['nombre'],'');
		$salida->texto("<i>{$efs['nombre']}</i>",9);//,array("justification"=>"center"));
		$salida->separacion();
		$fila1[] = array("<b>Modalidad:</b> ".$efs['modalidad'], "<b>Propuesta:</b> ".$efs['propuesta']);
		$fila1[] = array("<b>Tope créditos cursado:</b> ".$efs['tope_creditos_cursado'],"<b>Tope créditos reg vigentes:</b> ".$efs['tope_creditos_reg_vigentes']);
		$fila1[] = array("<b>Estado:</b> ".$efs['estado_plan'],"<b>Documento alta:</b> ".$efs['documento_plan_desc']);
		
		$fila2[] = array("<b>Versión:</b> ".$efs['version'],"<b>Documento alta:</b> ".$efs['documento_version_desc']);
		$fila2[] = array("<b>Duración teórica:</b> ".$efs['duracion_teorica'],"<b>Cnt materias:</b> ".$efs['cnt_materias']);
		$fila2[] = array("<b>Duración en años:</b> ".$efs['duracion_en_anios'],"<b>Cnt optativas:</b> ".$efs['cnt_optativas']);
		$fila2[] = array("<b>Duración en meses:</b> ".$efs['duracion_en_meses'],"<b>Cnt idiomas:</b> ".$efs['cnt_idiomas']);
		$fila2[] = array("<b>Estado:</b> ".$efs['estado_version'],"<b>Fecha entrada vigencia:</b> ".$efs['fecha_entrada_vigencia']);
		//$salida->texto(print_r($fila,true));
		$datos['datos_tabla'] = $fila1; 
		$salida->tabla($datos,false,8,array("showLines"=>0, "shaded"=>0, "xPos"=>0,"xOrientation"=>"right")); //
		$salida->separacion();
		$datos['datos_tabla'] = $fila2; 
		$salida->tabla($datos,false,8,array("showLines"=>0, "shaded"=>0, "xPos"=>0,"xOrientation"=>"right")); //
	}
	
	protected function generar_html_ef($ef, $ancho_etiqueta=null)
	{
		if (! in_array($ef, $this->_lista_ef_post)) {
			//Si el ef no se encuentra en la lista posibles, es probable que se alla quitado con una restriccion o una desactivacion manual
			return;
		}
		$clase = 'ei-form-fila';
		$estilo_nodo = "";
		$id_ef = $this->_elemento_formulario[$ef]->get_id_form();
		if (! $this->_elemento_formulario[$ef]->esta_expandido()) {
			$clase .= ' ei-form-fila-oculta';
			$estilo_nodo = "display:none";
		}
		if (isset($this->_info_formulario['resaltar_efs_con_estado']) 
				&& $this->_info_formulario['resaltar_efs_con_estado'] && $this->_elemento_formulario[$ef]->seleccionado()) {
			$clase .= ' ei-form-fila-filtrada';
		}		
		switch($this->lr) {
			case 'c':
				$this->lr = 'l';
				break;
			case 'l':
				$estilo_nodo = 'float:left; width:50%';
				$this->lr = 'r';
				break;	
			case 'r':
				$estilo_nodo = 'float:left; clear: none';
				$this->lr = 'l';
				break;
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