<?php
/**
 * Representa un elemento del plan de estudios de tipo MATERIA GENÉRICA
 *		La unica diferencia que tiene con un modulo comun es que posee atributos en activ_plan
 *
 * @package Propuestas
 * @subpackage Plan
 */
class plan_elemento_modulo_mg_nucleo extends plan_elemento_modulo
{
	protected $cursor_actividad;

	function get_elemento_gui($id_padre=null)
	{
		$clase_gui = $this->get_clase_gui();
		$datos = Array('tipo'=>$this->tipo, 'subtipo'=>$this->subtipo);
		$nombre_elemento_plan = $this->get_nombre_elemento_plan();		
		$datos_elemento = $this->get_datos_elemento();
		$nombre = "(".$datos_elemento['codigo'].") " . $nombre_elemento_plan;		
		if ($this->debug) $nombre .= ' [' . $this->get_id() .']';
		$e_gui = new $clase_gui($this->get_id(), $nombre,$datos ,$id_padre,$this->get_id());
		$e_gui->set_ancestro_compartible($this->tiene_ancestro_compartible());
		$e_gui->set_controlador($this);
		if ($this->controlador->ver_optativas()) {
			foreach ($this->get_orden_hijos() as $id) {
				$hijo = $this->hijos[$id];
				if(!$hijo['eliminado']) {
					$e = $hijo['instancia'];
					$e_hijo_gui = $e->get_elemento_gui($this->get_id());
					$e_gui->agregar_hijo($e_hijo_gui);
				}
			}
		}
		return $e_gui;
	}

	function set_cursor_actividad($elemento)
	{
		$this->cursor_actividad = $elemento;
		toba::logger()->debug("-- Se asocio la ACTIVIDAD de la MG" . $this->get_id() . " con el cursor " . $this->cursor_elemento);
	}

	function get_clave_actividad()
	{
		if(!$this->es_nuevo()) {
			$tabla = $this->controlador->dep('actividades');
			$clave = $tabla->get_clave_valor($this->cursor_elemento);
			return $clave['elemento'];		
		} else {
			throw new guarani_error('No es posible recuperar el ID del elemento porque la ACTIVIDAD no esta sincronizada.');
		}
	}	

	//----------------------------------------------------------------
	//-- Persistencia
	//----------------------------------------------------------------
	
	function aplicar_eliminacion()
	{
		$this->debug("APLICAR ELIMINACION MG");
		$this->tabla_elementos_plan()->eliminar_fila($this->cursor_elemento_plan);
	}

	//----------------------------------------------------------------
	//-- Modificar Estado de la actividad ---------------------------------
	//----------------------------------------------------------------

	// La edicion normal de modulos queda sin efecto
	
	protected function set_datos_elemento($datos){}
	function set_datos_revision($datos){}

	//-- API de bloque -----------------------------------------------------

	function get_datos($id_padre=null)
	{
		$datos = parent::get_datos();
		$datos_elemento	 = $this->get_datos_elemento();
		$datos['codigo'] = $datos_elemento['codigo'];

		return $datos;
	}


	//----------------------------------------------------------------
	//--  Controles  -------------------------------------------------
	//----------------------------------------------------------------
	
	protected function control_eliminar_hijo()
	{
		//throw new guarani_error("No es posible eliminar los elementos de una materia generica");
	}


}
?>