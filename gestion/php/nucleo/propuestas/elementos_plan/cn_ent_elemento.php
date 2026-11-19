<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class cn_ent_elemento extends cn_entidad
{

	//---------- paquete ELEMENTOS -------------

	function set_datos_elemento($datos, $estoy_duplicando)
	{
		$this->validar_datos_elementos($datos, $estoy_duplicando);
		$this->tabla('elemento')->set($datos);
	}
	
	function set_datos_revision($datos)
	{
		$this->tabla('revision')->set($datos);
	}

	function get_datos_elemento()
	{
		return $this->tabla('elemento')->get();
	}

	function get_datos_revision()
	{
		return $this->tabla('revision')->get();
	}

	//---------- Informacion -------------------------------------

	/*
		estas funcriones deberian ser preguntas a funciones SQL recursivas
		que devuelvan informacion de la actividad:
			- modulos_revision a los que esta asociada
			- los plan_version a los que pertenece
	*/

	function pertenece_a_modulo()
	{
	}
	
	function pertenece_a_plan_activo()
	{
		//TODO solo se testea por elementos tipo actividad y (modulo mat generica) se deberia testear tambien 
		//  por modulos? 
		$elemento = $this->get_datos_elemento();
		if (isset($elemento['elemento'])) {
			$planes = toba::consulta_php('co_planes')->get_planes_elemento($elemento['elemento']);
			foreach ($planes as $plan){
				if($plan['estado'] <> 'N'){
					return true;
				}
			}
		} 
		return false;
		
	}

	function pertenece_a_plan()
	{
		//TODO solo se testea por elementos tipo actividad y (modulo mat generica) se deberia testear tambien 
		//  por modulos? 
		$elemento = $this->get_datos_elemento();
		if (isset($elemento['elemento'])) {
			$planes = toba::consulta_php('co_planes')->get_planes_elemento($elemento['elemento']);
			if (isset($planes[0]))	{
				return true;
			}
		} 
		return false;
		
	}
	
	//--------- VALIDACIONES ---------------------------------------------------------------------
	
	function validar_datos_elementos($datos)
	{
		$elemento = $this->tabla('elemento')->get_columna('elemento');
		$this->validar_codigo_nombre_actividad($datos['codigo'], $datos['nombre'], $elemento);
	}

	function validar_codigo_nombre_actividad($codigo, $nombre, $elemento)
	{
		if (!toba::consulta_php('co_elementos')->validar_codigo_nombre_materia($codigo, $nombre, $elemento)) {
			$this->set_error('dato_duplicado', array('código y nombre', 'actividad'));
			throw guarani::error($this->errores);
		}
	}
}

?>