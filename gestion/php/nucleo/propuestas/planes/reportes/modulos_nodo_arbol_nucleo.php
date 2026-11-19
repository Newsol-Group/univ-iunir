<?php
class modulos_nodo_arbol_nucleo implements toba_nodo_arbol_form {

	protected $id;
	protected $nombre;
	protected $padre;
	protected $hijos = array();
	protected $nom_abrev;
	protected $subtipo;
	protected $pantalla;
	protected $modulo_revision;
	protected $abierto;
	
	function __construct($id, $modulo_revision,$nombre, $nom_abrev, $subtipo, $pantalla) {
		$this->id = $id;
		$this->modulo_revision = $modulo_revision;
		$this->nombre = $nombre;
		$this->nom_abrev = (!isset($nom_abrev))?$nombre:$nom_abrev;
		$this->subtipo = $subtipo;
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
		return $this->nom_abrev;
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
		switch($this->subtipo) {
			case elemento::subtipo_raiz:
				$iconos[] = array(
				//'imagen' => toba_recurso::imagen_toba( 'edicion.png', false),
				'imagen' => toba_recurso::imagen_proyecto("propuesta/g_univ.png", false),
				'ayuda' => "Modulo Raiz"			);
				break;
			case elemento::subtipo_materia_generica:
				$iconos[] = array(
				//'imagen' => toba_recurso::imagen_toba( 'edicion.png', false),
				'imagen' => toba_recurso::imagen_proyecto("propuesta/g_actividad.png", false),
				'ayuda' => "Materia Generica"			);
				break;
			case elemento::subtipo_compentencia:
			case elemento::subtipo_agrupador_compentencia:
				$iconos[] = array(
				//'imagen' => toba_recurso::imagen_toba( 'edicion.png', false),
				'imagen' => toba_recurso::imagen_proyecto("propuesta/g_certificado.gif", false),
				'ayuda' => "Certificado"			);
				break;
			case elemento::subtipo_modulo_rtf:
				$iconos[] = array(
				//'imagen' => toba_recurso::imagen_toba( 'edicion.png', false),
				'imagen' => toba_recurso::imagen_proyecto("propuesta/g_modulo_rtf.gif", false),
				'ayuda' => "Módulo RTF"			);
				break;
			default:
				$iconos[] = array(
				'imagen' => toba_recurso::imagen_proyecto( 'propuesta/g_modulo.gif', false),
				'ayuda' => "Modulo"
				);
				break;
		}
		return $iconos;
	}
	
	/**
	 * Arreglo de utilerias (similares a los iconos pero secundarios
	 * Formato de nodos y utilerias: array('imagen' => , 'ayuda' => ,  'vinculo' => )
	 */
	function get_utilerias() {
		if ($this->pantalla != 'correlativas') {
			$iconos[] = array(
				'imagen' => toba_recurso::imagen_toba('doc.gif', false),
				'ayuda' => "Ver Actividad",
				'vinculo' => toba::vinculador()->crear_autovinculo($parametros=array('elemento'=>$this->id, 'modulo_revision'=>$this->modulo_revision, 'pantalla'=>$this->pantalla))
			);
			return $iconos;
		}
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