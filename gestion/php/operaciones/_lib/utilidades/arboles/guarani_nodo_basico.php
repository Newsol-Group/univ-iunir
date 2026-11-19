<?php
/**
*	Nodo básico de árboles de Guaraní.
*/
class guarani_nodo_basico implements toba_nodo_arbol
{
	protected $nivel;
	protected $padre;
	protected $nombre_corto;
	protected $nombre_largo = null;
	protected $id = null;
	protected $id_padre = null;
	protected $iconos = array();
	protected $utilerias = array();
	protected $eventos = array();
	protected $info_extra = null;
	protected $tiene_hijos_cargados = false;
	protected $es_hoja = true;
	protected $hijos = array();
	protected $propiedades = null;
	protected $imagen_invisible;

	protected $clase_css_ul;
	protected $estilo_css_ul;
	protected $clase_css_li;
	protected $estilo_css_li;

	function __construct($id, $padre=null, $nombre='' , $nivel=null, $id_padre=null)
	{
		$this->nivel = $nivel;
		$this->nombre_largo = $nombre;
		$this->nombre_corto =  guarani_texto::acortar_texto($nombre, 35);
		$this->padre = $padre;
		$this->id = $id;
		$this->id_padre = $id_padre;
		$this->imagen_invisible = toba_recurso::imagen_proyecto('acciones/nulo.gif');
	}

	//-- Setters -------------------------------------------------------

	function agregar_icono($imagen, $evento=null, $javascript='', $ayuda='', $confirmacion='')
	{
		$this->iconos[] = array( 'imagen' => $imagen, 'evento' => $evento, 'javascript' => $javascript, 'ayuda' => $ayuda, 'confirmacion' => $confirmacion);
		$this->registrar_evento($evento);
	}

	function agregar_utileria($imagen, $evento=null, $javascript='', $ayuda='', $confirmacion='')
	{
		$this->utilerias[] = array( 'imagen' => $imagen, 'evento' => $evento, 'javascript' => $javascript, 'ayuda' => $ayuda, 'confirmacion' => $confirmacion);
		$this->registrar_evento($evento);
	}

	function desactivar_evento($evento)
	{
		if (! isset($evento) || $evento == '') return;
		$this->eventos[$evento] = false;
	}

	function eliminar_evento($evento)
	{
		$this->desactivar_evento($evento);
	}

	function registrar_evento($evento)
	{
		if (! isset($evento) || $evento == '') return;
		$this->eventos[$evento] = true;
	}

	function activar_evento($evento)
	{
		$this->registrar_evento($evento);
	}

	function set_hijos($hijos)
	{
		$this->hijos = $hijos;
		$this->tiene_hijos_cargados = true;
		$this->es_hoja = false;
	}	
	
	function agregar_hijo($hijo)
	{
		$this->hijos[] = $hijo;
		$this->tiene_hijos_cargados = true;
		$this->es_hoja = false;		
	}
		
	function set_utilerias($utilerias)
	{
		foreach ($utilerias as $utileria) {
			if (!isset($utileria['evento'])) $utileria['evento'] = null;
			if (!isset($utileria['javascript'])) $utileria['javascript'] = null;
			$this->agregar_utileria($utileria['imagen'], $utileria['evento'], $utileria['javascript'], $utileria['ayuda']);
		}
	}

	function set_iconos($iconos)
	{
		foreach ($iconos as $icono) {
			if (!isset($icono['evento'])) $icono['evento'] = null;
			if (!isset($icono['javascript'])) $icono['javascript'] = null;
			$this->agregar_icono($icono['imagen'], isset($icono['evento']) ? $icono['evento']: null, $icono['javascript'], $icono['ayuda']);
		}
	}
	
	function set_padre($padre)
	{
		$this->padre = $padre;
		$this->id_padre = $padre->get_id();
	}
	
	function set_nivel($nivel)
	{
		$this->nivel = $nivel; 
	}

	function set_info_extra($info_extra)
	{
		$this->info_extra = $info_extra;
	}

	//-- Interface -----------------------------------------------------
	
	function get_id()
	{
		return $this->id;
	}
	
	function get_nombre_corto()
	{
		return $this->nombre_corto;
	}
	
	function get_nombre_largo()
	{
		return $this->nombre_largo;
	}
	
	function get_info_extra()
	{
		return $this->info_extra;
	}
	
	function get_iconos()
	{
		foreach ($this->iconos as $accionador => $props) {
			if (! $this->evento_activo($props['evento'])) {
				unset($this->iconos[$accionador]['evento']);
				unset($this->iconos[$accionador]['javascript']);
				$this->iconos[$accionador]['ayuda'] = '';
			}
		}
		return $this->iconos;
	}
	
	function get_utilerias()
	{
		foreach ($this->utilerias as $accionador => $props) {
			if (! $this->evento_activo($props['evento'])) {
				$this->utilerias[$accionador]['imagen'] = $this->imagen_invisible;
				unset($this->utilerias[$accionador]['evento']);
				unset($this->utilerias[$accionador]['javascript']);
				$this->utilerias[$accionador]['ayuda'] = '';
			}
		}
		return $this->utilerias;
	}

	function get_padre()
	{
		return $this->padre;	
	}
	
	function get_id_padre()
	{
		return $this->id_padre;
	}	
	
	function tiene_hijos_cargados()
	{
		return $this->tiene_hijos_cargados;	
	}
	
	function es_hoja()
	{
		return $this->es_hoja;
	}
	
	function get_hijos()
	{
		return $this->hijos;
	}

	function get_nivel()
	{
		return $this->nivel;
	}

	function get_jerarquia()
	{
		return $this->nivel->get_jerarquia();
	}

	function tiene_propiedades()
	{
		return $this->propiedades;
	}

	function get_eventos_activos()
	{
		$activos = array();
		foreach ($this->eventos as $evento => $habilitado) {
			if ($evento) $activos[$evento] = true;
		}
		return $activos;
	}

	function evento_activo($evento)
	{
		if (! isset($evento) || $evento == '' || !isset($this->eventos[$evento])) return true;
		return ($this->eventos[$evento] == true);
	}

	//-----------------------------------------------------------------------
	//-----      CSS          -----------------------------------------------
	//-----------------------------------------------------------------------

	function set_clase_css_ul($css)
	{
		$this->clase_css_ul = $css;
	}

	function set_estilo_css_ul($css)
	{
		$this->estilo_css_ul = $css;
	}

	function set_clase_css_li($css)
	{
		$this->clase_css_li = $css;
	}

	function set_estilo_css_li($css)
	{
		$this->estilo_css_li = $css;
	}

	//-- CSS ---------------------------------------

	function get_clase_css_ul()
	{
		return $this->clase_css_ul;
	}

	function get_estilo_css_ul()
	{
		return $this->estilo_css_ul;
	}

	function get_clase_css_li()
	{
		return $this->clase_css_li;
	}

	function get_estilo_css_li()
	{
		return $this->estilo_css_li;
	}

}

?>