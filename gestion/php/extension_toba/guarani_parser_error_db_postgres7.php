<?php

class guarani_parser_error_db_postgres7 extends toba_parser_error_db_postgres7
{
	function parsear($sql, $sqlstate, $mensaje)
	{
		// Si no hay un error definido seteo uno generico
		$mensaje = parent::parsear($sql, $sqlstate, $mensaje);
		if (! isset($mensaje)) {
			$mensaje = guarani_mensajes::get('error_grabando_base');
		}
		return $mensaje;
	}

	//-------------------------------------------------------------------------------------
	//----- PRIMARY KEY  (sqlstate 23505)
	//-------------------------------------------------------------------------------------
	
	function get_mensaje_pk($accion, $pk)
	{
		$datos = $this->get_datos_pk($pk);
		$concepto_tabla = guarani_etiquetas::get_tabla($datos['nombre_tabla'],false);
		if (! isset($datos['nombre_tabla'])) {
			$mensaje = "Error $accion. ";
		} else {
			$mensaje = "Error $accion {$concepto_tabla->get(true)}. ";
		}
		if (! isset($datos['campos']) || empty($datos['campos'])) {
			$mensaje .= "Ya existe {$concepto_tabla->get_enf('una')} con la misma clave o descripción.";
		} else {
			$datos['campos_etiquetas'] = array();
			foreach ($datos['campos'] as $key => $campo){
				$datos['campo_etiqueta'] = guarani_etiquetas::get_campo($campo,false);
				array_push($datos['campos_etiquetas'],$datos['campo_etiqueta']->get_enf('la'));
			}
			$mensaje .= "Ya existe {$concepto_tabla->get_enf('una')} con ";
			$mensaje .= (guarani_arreglos::count($datos['campos']) == 1 ) ?
							"el mismo valor en " :
							"los mismos valores en: ";
			$mensaje .= guarani_texto::armar_lista($datos['campos_etiquetas'],', ',' y ').'.';
		}
		return $mensaje;
	}

	//-------------------------------------------------------------------------------------
	//----- FOREIGN KEY (sqlstate 23503)
	//-------------------------------------------------------------------------------------	
	
	function get_mensaje_fk($es_alta, $accion, $tabla_local, $fk, $tabla_foranea=null)
	{
		/* Para testear 
		$es_alta = true;
		$accion = 'Agregando';
		$tabla_local = 'sga_elementos';
		$fk = 'fk_sga_elementos_sga_g3entidades_subtipos';
		$tabla_foranea = 'sga_g3entidades_subtipos';
		*/
		$concepto_tabla_local = guarani_etiquetas::get_tabla($tabla_local,false);
		$concepto_tabla_foranea = guarani_etiquetas::get_tabla($tabla_foranea,false);
		$datos = $this->get_datos_fk($tabla_local, $fk, $tabla_foranea);
		if (! $concepto_tabla_local->es_valida('entidad')) {
			$mensaje = "Error $accion. ";
		} else {
			$mensaje = "Error $accion {$concepto_tabla_local->get_enf()}. ";
		}
		if (! isset($datos['campos_locales']) || empty($datos['campos_locales'])) {
			if ($es_alta) {
				$mensaje .= 'El registro tiene valores que no están presentes en las tablas referenciadas.';
			} else {
				$mensaje .= "Al menos {$concepto_tabla_local->get_enf('una')} continúa siendo utilizado por {$concepto_tabla_foranea->get_enf('una')}.";
			}
		} else {
			$datos['campos_locales_etiquetas'] = array();
			foreach ($datos['campos_locales'] as $key => $campo){
				$datos['campo_local_etiqueta'] = guarani_etiquetas::get_campo($campo,false);
				array_push($datos['campos_locales_etiquetas'],$datos['campo_local_etiqueta']->get_enf('la'));
			}
			$mensaje .= (guarani_arreglos::count($datos['campos_locales']) == 1 ) ?
							"El valor ".$datos['campo_local_etiqueta']->get_contraccion().' '.$datos['campo_local_etiqueta']->get_enf().' ' :
							"Los valores de: ".guarani_texto::armar_lista($datos['campos_locales_etiquetas'],', ',' y ').' ';
			if ($es_alta) {
				$mensaje .= (guarani_arreglos::count($datos['campos_locales']) == 1 ) ?
								"no está presente en " :
								"no están presentes en ";
			} else {
				$mensaje .= (guarani_arreglos::count($datos['campos_locales']) == 1 ) ?
								"aún sigue siendo utilizad{$datos['campo_local_etiqueta']->get_terminacion('o')} en " :
								"aún siguen siendo utilizados en ";
			}
			if (!$concepto_tabla_foranea) {
				$mensaje .= ' la entidad referenciada.';
			} else {
				$mensaje .= $concepto_tabla_foranea->get_enf('las').'.';
			}
		}
		return $mensaje;
	}

	//-------------------------------------------------------------------------------------
	//----- NOT NULL  (sqlstate 23502)
	//-------------------------------------------------------------------------------------		
	
	function get_mensaje_not_null($accion, $campo)
	{
		$mensaje = "Error $accion. ";
		$concepto = guarani_etiquetas::get_campo($campo,false);
		$mensaje .= $concepto->get_enf('el')." no debe quedar vací{$concepto->get_terminacion('o')}.";
		return $mensaje;
	}
}
?>