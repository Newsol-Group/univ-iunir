<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Constancia
 * 
 * La idea de esta clase es que extiendan de la misma los CIs utilizados para obtener datos y parametros para 
 * la generacion de constancias 
*/

class ci_constancia extends guarani_pers_ci
{

	// Datos a pasar al controlador correspondiente a los parametros a grabar
	/*
	 * El formato del arreglo de datos debe ser:
	 *
	 * 		datos => ( 	campo => valor, campo => valor, campo => valor 	)
	 *
	 *			donde 'campo' debe ser un campo valido registrado en la tabla mce_campos
	*/ 
	protected $s__datos;
	
	// Datos auxiliares
	protected $s__datos_iniciales;

	/**
	 * Se ejecuta al inicio de todos los request en donde participa el componente
	 */
	function ini()
	{
		$this->s__datos_iniciales = $this->controlador()->get_params_form_dinamico();	
	}

	//-----------------------------------------------------------------------------------
	//---- Funciones Auxiliares ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Obtiene del ci controlador el alumno seleccionado para el cual se está dando de alta 
	 * o editando la Solicitud de Constancia.
	 *
	 */
	function get_alumno_solicitud()
	{
		return $this->controlador()->get_alumno_seleccionado();	
	}
	
	function get_datos()
	{
		return $this->s__datos;
	}

	function validar_datos()
	{
		/*
		 * El formato del arreglo de datos debe ser:
		 *
		 * 		datos => ( 	campo => valor, campo => valor, campo => valor 	)
		 *
		 *			donde 'campo' debe ser un campo valido registrado en la tabla mce_campos
		 */ 		
		// Valido que los valores dados por el arreglo a grabar sean campos existentes y con valores validos
		foreach ($this->s__datos as $campo => $valor) {
			campo::validar_campo_valor($campo, $valor);
		}
		
		return true;
	}

	function set_datos($datos)
	{
		/*
		 * El formato del arreglo de datos debe ser:
		 *
		 * 		datos => ( 	campo => valor, campo => valor, campo => valor 	)
		 *
		 *			donde 'campo' debe ser un campo valido registrado en la tabla mce_campos
		 */ 		
		// Seteo los datos
		$this->s__datos = $datos;
	}

	function set_datos_iniciales($datos)
	{
		$this->s__datos_iniciales = $datos;
	}
	
	function limpiar_datos()
	{
		$this->s__datos = array();
		unset($this->s__datos_iniciales);
	}
	
}
?>