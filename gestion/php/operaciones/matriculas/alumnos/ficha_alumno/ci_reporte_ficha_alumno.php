<?php

abstract class ci_reporte_ficha_alumno extends ci_reporte
{
	function datos_encabezado() 
	{
		$datos = toba::consulta_php('co_personas')->get_datos_persona($this->controlador()->controlador()->get_persona_seleccionada());
		
		$persona = array(array('apellido' => array('etiqueta' => 'APELLIDO Y NOMBRE ', 'valor' => $datos['nombre_completo'])),
						 array('documento' => array('etiqueta' => 'TIPO Y NRO. DE IDENTIFICACIÓN ', 'valor' => $datos['documento']))
					);
		return $persona;
	}
	
	function ancho_etiquetas_encabezado()
	{
		return 200;
	}
	
	function get_tabla_principal()
	{
		return 'sga_personas';
	}
}