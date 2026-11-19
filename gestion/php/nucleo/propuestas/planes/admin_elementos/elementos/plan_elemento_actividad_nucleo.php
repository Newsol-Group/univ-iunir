<?php
/**
 * Representa un elemento del plan de estudios de tipo ACTIVIDAD
 *
 *
 * @package Propuestas
 * @subpackage Plan
 */
class plan_elemento_actividad_nucleo extends plan_elemento_modulo
{
	protected $cursores_modalidad;

	//----------------------------------------------------------------
	//--  Recuperacion del elemento VISUAL --------------------------------
	//----------------------------------------------------------------

	function get_elemento_gui($id_padre=null)
	{
		$clase_gui = $this->get_clase_gui();
		$nombre_elemento_plan = $this->get_nombre_elemento_plan();		
		$datos_elemento = $this->get_datos_elemento();
		$datos = Array('tipo'=>$this->tipo, 'subtipo'=>$this->subtipo);
		$nombre = "(".$datos_elemento['codigo'].") " . $nombre_elemento_plan;
		if ($this->debug) $nombre .= ' [' . $this->get_id() .']';
		$e_gui = new $clase_gui($this->get_id(), $nombre,$datos ,$id_padre);
		$e_gui->set_ancestro_compartible($this->atributos_padre[$id_padre]['tiene_ancestro_compartible']);
		$e_gui->set_controlador($this);
		return $e_gui;
	}

	//----------------------------------------------------------------
	//-- PERSISTENCIA
	//----------------------------------------------------------------

	function aplicar_eliminacion()
	{
		$this->debug("APLICAR ELIMINACION ACTIVIDAD");
		$this->cursores_modalidad = $this->tabla_modalidad()->get_id_filas();
		$this->tabla_elementos_plan()->eliminar_fila($this->cursor_elemento_plan);
	}

	function sincronizar_eliminacion()
	{
		$this->debug("SINCRONIZAR ELIMINACION ACTIVIDAD");
		$relacion = $this->controlador->dep('elementos');
		$relacion->tabla('modalidad')->sincronizar_filas($this->cursores_modalidad);
		parent::sincronizar_eliminacion();
	}

	function sincronizar_actualizacion()
	{
		$this->debug("SINCRONIZAR ACTUALIZACION ACTIVIDAD");
		$relacion = $this->controlador->dep('elementos');
		$relacion->tabla('modalidad')->resetear_cursor();
		parent::sincronizar_actualizacion();
	}

	//----------------------------------------------------------------
	//-- 
	//----------------------------------------------------------------

	function get_datos($id_padre=null)
	{
		$datos = parent::get_datos();
		$datos_elemento	 = $this->get_datos_elemento();
		$datos['codigo'] = $datos_elemento['codigo'];

		return $datos;
	}


	//----------------------------------------------------------------
	//-- Manejo de MODALIDADES
	//----------------------------------------------------------------

	function get_modalidades()
	{
		$datos = $this->tabla_modalidad()->get_filas();
		$modalidades = Array();
		foreach ($datos as $modalidad) {
			$modalidades[]= $modalidad['modalidad'];
		}
		return $modalidades; 
	}
	
	protected function analizar_datos_modalidad ($datos_form)
	{
		$modalidades_cursadas = toba::consulta_php('co_planes')->get_modalidades_cursadas();
		$modalidades_dt = $this->controlador->dep('elementos')->tabla('modalidad')->get_filas();
		//$elemento_plan = $this->get_clave_elemento();
		$datos_dt[] = array();
		$cursores[] = array();
		foreach ($modalidades_dt as $modalidad_dt) {

			$datos_dt[]=$modalidad_dt['modalidad'];
			$cursores[]= $modalidad_dt['x_dbr_clave'];
		}
		$datos = array();
		foreach ($modalidades_cursadas as $modalidad_cursada) {
			if (in_array($modalidad_cursada['valor'],$datos_form) && !in_array($modalidad_cursada['valor'],$datos_dt)) {
				$datos[] = array('modalidad'=>$modalidad_cursada['valor'],'apex_ei_analisis_fila'=>'A');
			} else {
				if ( ! in_array($modalidad_cursada['valor'],$datos_form) && in_array($modalidad_cursada['valor'],$datos_dt)) {
					$key = array_search($modalidad_cursada['valor'],$datos_dt);
					$datos[] = array('modalidad'=>$modalidad_cursada['valor'],'apex_ei_analisis_fila'=>'B','x_dbr_clave' => $cursores[$key]);
				}
			}
		}
		return $datos;
	}

	function set_modalidades($datos)
	{
		if (!isset($datos) ||  empty($datos)) {
			$this->controlador->controlador()->set_error('dato_faltante',array('Cargar <strong>Modalidades</strong>'));
		}
		if($this->controlador->controlador()->hay_errores()){
			throw new guarani_error($this->controlador->controlador()->get_mensaje_error());
		}
		$modalidades = $this->analizar_datos_modalidad($datos);
		$this->controlador->dep('elementos')->tabla('modalidad')->procesar_filas($modalidades);
	}
		
	function setear_datos_basicos($elemento)
	{
		$this->control_edicion_no_bloqueada();
		$datos = $this->controlador->controlador()->dep('datos')->tabla('modalidad')->get_filas();
		foreach ($datos as $dato) {
			$cursor = $this->controlador->dep('elementos')->tabla('modalidad')->nueva_fila();
			$this->controlador->dep('elementos')->tabla('modalidad')->set_cursor($cursor);
			$this->controlador->dep('elementos')->tabla('modalidad')->set($dato);
		}
		parent::setear_datos_basicos($elemento);
	}

}
?>