<?php 
class form_certif extends guarani_pers_ei_formulario
{
	private $lr = 'l';
	
	function generar_layout()
	{
		if($this->_elemento_formulario['nombre']->get_estado()) {
			$lstpst = array('nombre');
			$this->generar_html_ef('nombre');
			echo "<hr style='clear:both' class='ci-pant-sep-ei'/>\n";
			$this->lr = 'l';
			if($this->_elemento_formulario['cert_tipo']->get_estado()) {
				$this->generar_html_ef('cert_tipo');
				$lstpst[] = 'cert_tipo';
			}
			if($this->_elemento_formulario['responsable_academica']->get_estado()) {
			$this->generar_html_ef('responsable_academica');
				$lstpst[] = 'responsable_academica';
			}
			if($this->_elemento_formulario['disciplina']->get_estado()) {
			$this->generar_html_ef('disciplina');
							$lstpst[] = 'disciplina';
			}
			echo "<hr style='clear:both' class='ci-pant-sep-ei'/>\n";
			$this->lr = 'l';
			if($this->_elemento_formulario['titulo_nivel']->get_estado()) {
			$this->generar_html_ef('titulo_nivel');
							$lstpst[] = 'titulo_nivel';
			}
			if($this->_elemento_formulario['duracion_en_anios']->get_estado()) {
			$this->generar_html_ef('duracion_en_anios');
							$lstpst[] = 'duracion_en_anios';
			}
			if($this->_elemento_formulario['duracion_en_meses']->get_estado()) {
			$this->generar_html_ef('duracion_en_meses');
							$lstpst[] = 'duracion_en_meses';
			}
			echo "<hr style='clear:both' class='ci-pant-sep-ei'/>\n";
			$this->lr = 'l';
			if($this->_elemento_formulario['cert_tipo']->get_estado()) {
			$this->generar_html_ef('acreditado_coneau');
							$lstpst[] = 'cert_tipo';
			}
			if($this->_elemento_formulario['categ_coneau']->get_estado()) {
			$this->generar_html_ef('categ_coneau');
							$lstpst[] = 'categ_coneau';
			}
			if($this->_elemento_formulario['titulo_araucano']->get_estado()) {
			$this->generar_html_ef('titulo_araucano');
							$lstpst[] = 'titulo_araucano';
			}
			echo "<hr style='clear:both' class='ci-pant-sep-ei'/>\n";
			$this->lr = 'l';
			if($this->_elemento_formulario['nro_resolucion_ministerio']->get_estado()) {
			$this->generar_html_ef('nro_resolucion_ministerio');
							$lstpst[] = 'nro_resolucion_ministerio';
			}
			if($this->_elemento_formulario['fecha_resolucion_ministerio']->get_estado()) {
			$this->generar_html_ef('fecha_resolucion_ministerio');
							$lstpst[] = 'fecha_resolucion_ministerio';
			}
			/*if($this->_elemento_formulario['certificado_tipo']->get_estado() == 'T') {
				echo "<hr style='clear:both' class='ci-pant-sep-ei'/>\n";
				$this->lr = 'l';
				$this->generar_html_ef('titulo_nivel');
				$lstpst[] = 'titulo_nivel';
			}*/
			$this->_lista_ef_post = $lstpst;
		} else {
			$this->_lista_ef_post = array();
		}
	}
	
	function vista_pdf($salida) {
		$efs = $this->get_datos();
		//$fila[] = array("<b>Nombre:</b> ".$efs['nombre'],'');
		$salida->texto("<i>{$efs['nombre']}</i>",9);//,array("justification"=>"center"));
		$salida->separacion();
		$fl[] = "<b>Tipo certificado:</b> ".$efs['tipo_certificado'];
		if($efs['certificado_tipo'] == 'T') {
				$fl[] = "<b>Título nivel:</b> ".$efs['titulo_nivel'];
		}
		$fila[] = $fl;
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
				$estilo_nodo = 'float:left; clear: none; width:41%';
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