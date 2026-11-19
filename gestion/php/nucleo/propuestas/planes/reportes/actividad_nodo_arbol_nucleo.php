<?php
class actividad_nodo_arbol_nucleo implements toba_nodo_arbol_form {

	protected $id;
	protected $nombre;
	protected $padre;
	protected $hijos = array();
	protected $nom_abrev;
	protected $subtipo;
	protected $pantalla;
	protected $modulo_revision;
	protected $abierto;
	
	function __construct($id, $nombre, $pantalla) {
		$this->id = $id;
		$this->nombre = $nombre;
		$this->pantalla = $pantalla;
	}
	
	function agregar_hijo($e) {
		$e->set_padre($this);
		$this->hijos[] = $e;
	}
	
	function set_padre($padre) {
		$this->padre = $padre;
	}
	
	function es_hoja() {
		return guarani_arreglos::count($this->hijos) == 0;
	}
	
	function get_hijos() {
		return $this->hijos;
	}
	
	function get_padre() {
		return $this->padre;
	}
	
	function tiene_hijos_cargados() {
		return true;
	}
	
	function tiene_propiedades() {
		return null;
	}
	
	function get_id() {
		return $this->id;
	}
	
	function get_nombre_corto() {
		return $this->nombre;
	}
	
	/**
	 * Nombre largo sólo disponible para ayudas o vistas mas directas
	 * @return string 
	 */
	function get_nombre_largo() {
		return $this->nombre;
	}
	
	/**
	 * Información extra contextual a la situación actual del nodo
	 */
	function get_info_extra() {
		return null;
	}
	
	/**
	 * Arreglo de iconos asociados al nodo
	 * Formato de nodos y utilerias: array('imagen' => , 'ayuda' => ,  'vinculo' => )
	 */
	function get_iconos() {
		$iconos[] = array(
				//'imagen' => toba_recurso::imagen_toba( 'edicion.png', false),
				'imagen' => toba_recurso::imagen_proyecto("propuesta/g_actividad.png", false),
				'ayuda' => "Materia Generica"			);
		return $iconos;
	}
	
	/**
	 * Arreglo de utilerias (similares a los iconos pero secundarios
	 * Formato de nodos y utilerias: array('imagen' => , 'ayuda' => ,  'vinculo' => )
	 */
	function get_utilerias() {
		return array();
	}
	
	function get_input($id)
	{
	}
	
	function cargar_estado_post($id)
	{
	}
	
	function set_apertura($abierto) 
	{
		$this->abierto = $abierto;
	}
	
	function get_apertura() 
	{
		return $this->abierto;
	}
}
?>