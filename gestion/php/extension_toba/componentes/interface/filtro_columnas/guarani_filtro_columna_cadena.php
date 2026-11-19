<?php

/**
 * @package Componentes
 * @subpackage Filtro
 */
class guarani_filtro_columna_cadena extends toba_filtro_columna_cadena
{

	function ini()
	{
		//--- Parámetros ef
		$parametros = $this->_datos;
		if (! isset($parametros['edit_tamano'])) {
			$parametros['edit_tamano'] = 18;
		}
		if (! isset($parametros['edit_maximo'])) {
			$parametros['edit_maximo'] = 255;
		}
		$obligatorio = array($this->_datos['obligatorio'], false);		
		$this->_ef = new toba_ef_editable($this, null, $this->_datos['nombre'], $this->_datos['etiqueta'],
											null, null, $obligatorio, $parametros);

		//--- Condiciones
		$this->agregar_condicion('contiene', 		new guarani_filtro_condicion($this, 'contiene',	 	'ILIKE', 	'%', 	'%', 	'::varchar', 	''));
		$this->agregar_condicion('no_contiene', 	new guarani_filtro_condicion_negativa($this, 'no contiene', 	'NOT ILIKE','%', 	'%', 	'::varchar', 	''));
		$this->agregar_condicion('comienza_con', 	new guarani_filtro_condicion($this, 'comienza con', 	'ILIKE', 	'', 	'%', 	'::varchar', 	''));
		$this->agregar_condicion('termina_con', 	new guarani_filtro_condicion($this, 'termina con', 	'ILIKE', 	'%', 	'', 	'::varchar', 	''));
		$this->agregar_condicion('es_igual_a', 		new guarani_filtro_condicion($this, 'es igual a',		'=', 		'', 	'', 	'::varchar', 	''));
		$this->agregar_condicion('es_distinto_de', 	new guarani_filtro_condicion_negativa($this, 'es distinto de', '!=', 		'', 	'', 	'::varchar', 	''));
	}

	function controlador()
	{
		return $this->_padre;
	}

	function get_html_diferenciar()
	{
		$html = "<div align='left'>";
		$checked_cs = "";
		if (isset($this->_estado['condicion_cs']) && $this->_estado['condicion_cs']) {
			$checked_cs = "checked='checked'";
		}
		$checked_as = "";
		if (isset($this->_estado['condicion_as']) && $this->_estado['condicion_as']) {
			$checked_as = "checked='checked'";
		}
		if ($this->controlador()->con_sensitividad_mayusculas()) {
			$html .= "<input type='checkbox' id='{$this->_id_form_cond}_cs' name='{$this->_id_form_cond}_cs' ".$checked_cs."/>&nbsp;may/min&nbsp;&nbsp;";
		}
		if ($this->controlador()->con_sensitividad_tildes()) {
			$html .= "<input type='checkbox' id='{$this->_id_form_cond}_as' name='{$this->_id_form_cond}_as' ".$checked_as."/>&nbsp;tildes";
		}
		$html .= "</div>";
		return $html;
		
	}	
	
	function cargar_estado_post()
	{
		parent::cargar_estado_post();
		
		if (isset($_POST[$this->_id_form_cond.'_cs'])) {
			$condicion_cs = $_POST[$this->_id_form_cond.'_cs'];
			$this->_estado['condicion_cs'] = $condicion_cs;	
		} else {
			$this->_estado['condicion_cs'] = false;	
		}

		if (isset($_POST[$this->_id_form_cond.'_as'])) {
			$condicion_as = $_POST[$this->_id_form_cond.'_as'];
			$this->_estado['condicion_as'] = $condicion_as;	
		} else {
			$this->_estado['condicion_as'] = false;	
		}
		
	}

	function get_expresion()
	{
		$expresion = $this->_datos['expresion'];
		$schema_toba = toba::instancia()->get_db()->get_schema();
		return str_replace("apex_", $schema_toba.".apex_", $expresion);
	}
	
	function get_sql_where()
	{
		if (isset($this->_estado)) {
			$id = $this->_estado['condicion'];
			return $this->_condiciones[$id]->get_sql_sensitivo($this->get_expresion(), $this->_estado['valor'],$this->_estado['condicion_cs'],$this->_estado['condicion_as']);
		}
	}

}