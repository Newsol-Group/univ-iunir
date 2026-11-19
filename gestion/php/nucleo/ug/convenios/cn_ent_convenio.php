<?php 
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Convenios
 */

class cn_ent_convenio extends cn_entidad
{
	function set_datos_convenios($datos)
	{
		$this->dep('datos')->sincronizar();
	}
	
	function get_datos_convenios()
	{
		return $this->tabla('convenios')->get();
	}	

	function set_datos_princ_conv($datos)
	{
		$this->validar_datos($datos);
		$this->tabla('convenios')->set($datos);
	}
	
	function get_datos_princ_conv()
	{
		return $this->tabla('convenios')->get();
	}	
	
	function set_datos_instit_part($datos)
	{
		$this->tabla('conv_instit')->nueva_fila($datos);
	}
	
	function get_datos_instit_part($clave=null)
	{
		if (is_null($clave)) {
			$datos = $this->tabla('conv_instit')->get_filas();
		} else {
			$datos = $this->tabla('conv_instit')->get_fila($clave);			
		}
		return $datos;
	}

	function eliminar_datos_instit_part($datos)
	{
		if ($this->puede_eliminar_instit($datos['institucion'])) {
			$this->tabla('conv_instit')->eliminar_fila($datos);
		} else {
			$mensaje = guarani_mensajes::get('800_dato_no_eliminable', array("institución \"{$datos['nombre_institucion']}\"", 'se encuentra asociada a una responsable académica o propuesta seleccionada en otra pestaña'));
			throw new guarani_error($mensaje);
		}
	}
	
	function set_datos_ra($datos)
	{
		$this->tabla('conv_ra')->nueva_fila($datos);
	}
	
	function get_datos_ra()
	{
		return $this->tabla('conv_ra')->get_filas();
	}	

	function eliminar_datos_ra($clave)
	{
		return $this->tabla('conv_ra')->eliminar_fila($clave);
	}	

	function set_datos_prop_curr($datos)
	{
		$this->tabla('conv_prop')->nueva_fila($datos);
	}

	function get_datos_propuestas($condiciones=null)
	{
		return $this->tabla('conv_prop')->get_filas($condiciones);
	}

	function get_datos_propuesta($id)
	{
		return $this->tabla('conv_prop')->get_fila($id);
	}

	function eliminar_datos_prop_curr($datos)
	{
		if ($datos['es_externa'] == comunes::si || $this->puede_eliminar_propuesta($datos['propuesta'])) {
			$this->tabla('conv_prop')->eliminar_fila($datos);
		} else {
			$mensaje = guarani_mensajes::get('800_dato_no_eliminable', array("propuesta \"{$datos['propuesta_nombre']}\"", 'se encuentra asociada a una actividad seleccionada en otra pestaña'));
			throw new guarani_error($mensaje);
		}
	}
	
	function set_datos_act($datos)
	{
		$this->tabla('conv_act')->nueva_fila($datos);
	}
	
	function get_datos_act()
	{
		return $this->tabla('conv_act')->get_filas();
	}	

	function eliminar_datos_act($clave)
	{
		return $this->tabla('conv_act')->eliminar_fila($clave);
	}	

	/**
	 * Chequea si hay alguna RA o propuesta asociada a la institución a eliminar.
	 * 
	 * @param integer $instit
	 * @return boolean
	 */
	function puede_eliminar_instit($instit)
	{
		// Chequeo si existe alguna responsable académica asociada a la institución.
		$ras = $this->get_datos_ra();
		foreach ($ras as $ra) {
			if ($ra['institucion'] == $instit) {
				return false;
			}
		}

		// Chequeo si existe alguna propuesta asociada a la institución.
		$props = $this->get_datos_propuestas();
		foreach ($props as $prop) {
			if ($prop['es_externa'] == comunes::si && $prop['institucion_ext'] == $instit) {
				return false;
			} elseif ($prop['es_externa'] == comunes::no) {
				$instituciones = toba::consulta_php('co_propuestas')->get_instituciones_propuesta($prop['propuesta']);
				if (in_array($instit, aplanar_matriz($instituciones))) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Chequea si hay alguna actividad asociada a la propuesta a eliminar.
	 * 
	 * @param integer $propuesta
	 * @return boolean
	 */
	function puede_eliminar_propuesta($propuesta)
	{
		return !toba::consulta_php('co_elementos')->propuesta_incluye_actividades($propuesta, $this->get_datos_act());
	}

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$convenio = $this->tabla('convenios')->get_columna('convenio');
		$this->validar_nombre_convenio($datos['nombre'], $convenio);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	// Determinar si el nombre del Convenio ya existe.
	function validar_nombre_convenio($nombre, $convenio) 
	{
		if (! toba::consulta_php('co_convenios')->validar_nombre_convenio($nombre, $convenio)) {
			$this->set_error('dato_existente', array('nombre', 'Convenio'));
		}
	}	
	
}
?>