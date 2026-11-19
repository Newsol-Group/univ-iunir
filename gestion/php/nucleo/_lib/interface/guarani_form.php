<?php

/**
 * Clase estática con métodos que encapsulan los tags de un formulario HTML
 * Estos métodos son de bajisimo nivel y son solo shorcuts para evitar escribir html directo
 * 
 * @package SalidaGrafica
 */
class guarani_form {

	/**
	 * Genera un dropdown similar al de Bootstrap.
	 * 
	 * @see https://github.com/soundasleep/jquery-dropdown/tree/2.0.3	jQuery Bootstrap-style Dropdowns 2.0.3.
	 * @see https://labs.abeautifulsite.net/jquery-dropdown/			jQuery Bootstrap-style Dropdowns 2.0.3.
	 * 
	 * @param string	$id					Id del dropdown.
	 * @param string	$nombre				Nombre del input que contiene el valor del ítem seleccionado.
	 * @param string	$etiqueta			Etiqueta que al hacerle click despliega el dropdown.
	 * @param array		$datos				Items del dropdown.
	 * @param string	$valor_actual		Valor actual seleccionado.
	 * @param string	$on_change			Código JS a ejecutarse al cambiar de ítem.
	 * @param boolean	$desplegar_derecha	true para que depliegue hacia la derecha, por defecto despliega hacia la izquierda (false).
	 * @param string	$class_etiqueta		Clase CSS de la etiqueta (para dar estilos).
	 * 
	 * @return string HTML y JS del dropdown.
	 */
	static function jq_dropdown($id, $nombre, $etiqueta, $datos, $valor_actual, $on_change = null, $desplegar_derecha = false, $class_etiqueta = '') {
		//Combo STANDART. recibe el listado en un array asociativo
		if (!is_array($datos)) {
			//Si datos no es un array, no puedo seguir
			$datos = array();
		}
		
		$dropdown = "";
		
		$clase_desplegar_derecha = ($desplegar_derecha)? 'jq-dropdown-anchor-right' : '';
		
		$tiene_on_change = 'false';
		// Si tiene evento onchange
		if(!is_null($on_change)){
			$tiene_on_change = 'true';
			$on_change_function_name = "{$id}_on_change_func";
			
			$dropdown .= "<script>\n";
			$dropdown .= "	function $on_change_function_name(){\n";
			$dropdown .= "		$on_change\n";
			$dropdown .= "	}\n";
			$dropdown .= "</script>\n";
		}
		
		$dropdown .= "<div class='jq-dropdown-text $class_etiqueta' data-jq-dropdown='#$id'>$etiqueta <i class='fa fa-caret-down fa-lg'></i></div>\n";
		$dropdown .= "<div id='$id' class='jq-dropdown jq-dropdown-tip $clase_desplegar_derecha' data-tiene-on-change='$tiene_on_change'>\n";
		$dropdown .= "	<input type='hidden' name='$nombre' value='$valor_actual'>\n";
		$dropdown .= "	<ul class='jq-dropdown-menu'>\n";
		
		foreach ($datos as $id => $desc) {
			// Si es el item seleccionado le pongo un check
			$selected	= ($id == $valor_actual)? "<i class='fa fa-check fa-lg'></i> " : "&nbsp;&nbsp;&nbsp;&nbsp;";
			$id			= texto_plano($id);
			$desc		= texto_plano($desc);
			$dropdown .= "<li><a href='#' class='jq-dropdown-item' data-value='$id'>{$selected}{$desc}</a></li>\n";
		}

		$dropdown .= "	</ul>\n";
		$dropdown .= "</div>\n";
		
		return $dropdown;
	}

}
