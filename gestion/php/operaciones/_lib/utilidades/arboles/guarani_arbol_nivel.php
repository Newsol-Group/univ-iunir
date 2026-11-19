<?php
class guarani_arbol_nivel
{
	protected $id;
	protected $col_id;
	protected $col_desc;
	protected $nombre;
	protected $padre;
	protected $iconos=array();		// array( array('imagen' => xxxx, 'javascript' => xxxx, 'ayuda' => xxxx))
	protected $utilerias=array();	// array( array('imagen' => xxxx, 'javascript' => xxxx, 'ayuda' => xxxx))
	protected $metodo_configurador; //array( 'clase' => xxxx, 'metodo' => xxxx)
	protected $arbol;
	protected $jerarquia;

	function __construct($id, $columna_id, $columna_desc, $nombre, $nivel_padre=null)
	{
		$this->id = $id;
		$this->col_id	= $columna_id;
		$this->col_desc	= $columna_desc;
		$this->nombre	= $nombre;
		$this->padre	= $nivel_padre;
	}

	//-- Configuración ----------------------------------------

	function get_id()
	{
		return $this->id;
	}

	function set_padre($padre)
	{
		$this->padre = $padre;
	}

	function agregar_icono($imagen, $evento=null, $ayuda=null, $confirmacion='', $javascript=null)
	{
		$this->iconos[] = $this->formatear_disparador($imagen, $evento, $ayuda, $confirmacion, $javascript);
	}	

	function agregar_utileria($imagen, $evento=null, $ayuda=null, $confirmacion='', $javascript=null)
	{
		$this->utilerias[] = $this->formatear_disparador($imagen, $evento, $ayuda, $confirmacion, $javascript);
	}	

	function formatear_disparador($imagen, $evento=null, $ayuda=null, $confirmacion='', $javascript=null)
	{
		$def['confirmacion'] = $confirmacion;
		$def['imagen'] = $imagen;
		if(isset($evento)) {
			$def['evento'] = $evento;
		}
		if (isset($javascript)) {
			$def['javascript'] = $javascript;
		}
		if(isset($ayuda)) {
			$def['ayuda'] = $ayuda;
		} else {
			$def['ayuda'] = '';
		}
		return $def;
	}


	function set_metodo_configurador($clase, $metodo)
	{
		$this->metodo_configurador = array( 'clase' => $clase, 'metodo' => $metodo);
	}

	function set_jerarquia($jerarquia)
	{
		$this->jerarquia = $jerarquia;
	}

	//-- Consultas ----------------------------------------

	function get_arbol()
	{
		return $this->arbol;
	}

	function get_padre()
	{
		return $this->padre;
	}

	function get_col_id()
	{
		return $this->col_id;
	}

	function get_col_desc()
	{
		return $this->col_desc;
	}

	function get_iconos()
	{
		return $this->iconos;
	}

	function get_utilerias()
	{
		return $this->utilerias;
	}

	function get_metodo_configurador()
	{
		return $this->metodo_configurador;
	}

	function get_jerarquia()
	{
		return $this->jerarquia;
	}
}
?>