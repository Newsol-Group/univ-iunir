<?php

class plan_elemento_gui extends toba_serializar_propiedades implements toba_nodo_arbol_form
{
	protected $ei_arbol;
	protected $padre;
	protected $id_padre;
	protected $nombre_corto;
	protected $nombre_largo = null;
	protected $tipo = null;
	protected $subtipo = null;
	protected $orden;
	protected $id = null;
	protected $rand_id = null;
	protected $iconos = array();
	protected $utilerias = array();
	protected $info_extra = null;
	protected $tiene_hijos_cargados = false;
	protected $es_hoja = true;
	protected $hijos = array();
	protected $propiedades = false;
	protected $tiene_ancestro_compartible=false;
	protected $imagen;
	protected $imagen_origen;
	protected $nivel;
	protected $carpeta = false;
	protected $camino;
	protected $proyecto;
	protected $abierto = true;
	//-----------------
	protected $controlador;
	//-----------------
	protected $datos;
	//-------------------
	protected $mostrar_utilerias_edicion = true;
	protected $mostrar_utileria_eliminacion = true;
	protected $mostrar_input = false;
	protected $seleccionable = true;
	//-------------------
	protected $get_clase_css_ul = null;
	protected $get_estilo_css_ul = null;
	protected $get_clase_css_li = null;
	protected $get_estilo_css_li = null;
	//-------------------
	protected $proximo_hijo = 0;
	protected $orden_hijos = array();	
	protected $modo = 'P';// 'P' Planes | 'C' Correlativas

	function __construct($id, $nombre, $datos)
	{
		$this->id = $id;
		$this->rand_id = $this->id.'_'.rand(0,500);
		if (isset($datos['tipo'])) 
		{
			$this->tipo = $datos['tipo'];
		}
		if (isset($datos['subtipo'])) 
		{
			$this->subtipo = $datos['subtipo'];
		}

                // Elimino comillas
                $nombre = str_replace(['"',"'"], "", $nombre);

		$this->nombre_corto = $nombre;
		$this->nombre_largo = $this->nombre_corto;
		$this->datos = $datos;
		$this->inicializar();
	}

	function get_datos()
	{
		return $this->datos;
	}

	function get_modo()
	{
		return $this->modo;
	}

	function es_modo_correlativas() 
	{
		return ($this->get_modo() == 'C');
	}

	function es_modo_planes() 
	{
		return ($this->get_modo() == 'P');
	}

	function set_modo($modo)
	{
		return $this->modo = $modo;
	}

	function set_modo_correlativas() 
	{
		$this->set_modo('C');
	}

	function get_dato($dato)
	{
		return $this->datos[$dato];
	}

	function controlador()
	{
		return $this->ei_arbol->controlador();	
	}

	function set_ei_arbol($arbol)
	{
		$this->ei_arbol = $arbol;	
	}
	
	function es_raiz()
	{
		return isset($this->id_padre) && $this->id_padre == $this->id;
	}

	function inicializar(){}
	
	//-- Configuracin VISUAL -------------------------------------------------------

	function mostrar_utilerias_edicion($estado=true)
	{
		$this->mostrar_utilerias_edicion = $estado;	
	}

	function mostrar_utileria_eliminacion($estado=true)
	{
		$this->mostrar_utileria_eliminacion = $estado;	
	}
	
	function habilitar_seleccion($estado=true)
	{
		$this->seleccionable = $estado;	
	}

	function inhabilitar_seleccion()
	{
		$this->habilitar_seleccion(false);
	}

	protected function get_icono_elemento()
	{
		return guarani::catalogo_elementos_plan()->get_icono($this->subtipo);	
	}

	function get_utilerias()
	{
		if ($this->es_modo_correlativas()) return array();
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		$iconos = array();
		if( $this->mostrar_utilerias_edicion ) {
			if( $this->mostrar_utileria_eliminacion ) {
				if ($this->tipo == elemento::tipo_modulo) {
					switch ($this->subtipo) {
					case elemento::subtipo_materia_generica:
						$ayuda = 'Quitar la materia genérica';
					break;
					case elemento::subtipo_orientacion:
						$ayuda = 'Quitar la orientación';
					break;
					default:
						$ayuda = 'Quitar el módulo';
					}
				} else {
					$ayuda =  "Quitar la actividad";
				}

                                // Escapo las comillas
                                $nombre = addslashes($this->nombre_corto);

				$iconos[] = array(
					'imagen' => toba_recurso::imagen_proyecto("propuesta/borrar.gif", false),
					'ayuda' => $ayuda,
					'javascript' => "onclick=\"{$id_objeto_js}.eliminar_elemento('{$this->id}', '{$this->id_padre}', '{$nombre}')\""
				);
			}
		}
		return array_merge($iconos, $this->utilerias);
	}
	
	//-- Setters -------------------------------------------------------

	function agregar_utileria($utileria)
	{
		$this->utilerias[] = $utileria;
	}
	
	function agregar_icono($icono)	
	{
		$this->iconos[] = $icono;	
	}

	function agregar_hijo($hijo, $orden=null)
	{
		$this->hijos[$this->proximo_hijo] = $hijo;
		$this->tiene_hijos_cargados = true;
		$this->es_hoja = false;
		$hijo->set_padre($this);
		if ($orden) $this->orden_hijos[$this->proximo_hijo]=$orden;
		$this->proximo_hijo++;
	}
	
	function eliminar_hijo($id_hijo)
	{
		foreach($this->hijos as $id => $hijo) {
			if( $id_hijo == $hijo->get_id() ) {
				$this->hijos[$id]->eliminar();
				unset($this->hijos[$id]);
				toba::logger()->debug("elimino al hijo $id");
			}
		}
		if(guarani_arreglos::count($this->hijos)==0) {
			$this->tiene_hijos_cargados = false;
			$this->es_hoja = true;
		}
	}
	
	function set_hijos($hijos)
	{
		$this->hijos = $hijos;
		$this->tiene_hijos_cargados = true;
		$this->es_hoja = false;
	}	
		
	function set_utilerias($utilerias)
	{
		$this->utilerias = $utilerias;
	}
	
	function set_iconos($iconos)	
	{
		$this->iconos = $iconos;	
	}
	
	function set_padre($padre)
	{
		$this->padre = $padre;
	}
	
	function set_nivel($nivel)
	{
		$this->nivel = $nivel;
	}
	
	function set_camino($camino)
	{
		$this->camino = $camino;
	}
	
	function set_mostrar_input($estado=true)
	{
		$this->mostrar_input = $estado;
	}
	
	function set_apertura($abierto) 
	{
		$this->abierto = $abierto;
	}

	function set_ancestro_compartible($tiene=true)
	{
		$this->tiene_ancestro_compartible = $tiene;
	}

	//-- Interface -----------------------------------------------------
	
	function marcar_abiertos()
	{
		$nodo = $this->get_padre();
		while ($nodo != null && !$nodo->es_raiz() && !$nodo->get_apertura()) {
			$nodo->set_apertura(true);
			$nodo = $nodo->get_padre();
		}
	}
	
	function get_id()
	{
		return $this->id;
	}

	function get_rand_id() {
		return $this->rand_id;
	}
	
	function get_controlador()
	{
		return $this->controlador;
	}
	
	function get_tipo()
	{
		return $this->tipo;
	}
	
	function get_subtipo()
	{
		return $this->subtipo;
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
	
	function get_imagen()
	{
		if (isset($this->imagen) && ($this->imagen != '') && ($this->imagen_origen != '')) {
			if ($this->imagen_origen == 'apex') {
				$imagen = toba_recurso::imagen_toba($this->imagen, false);	
			} else {
				$imagen = toba_recurso::url_proyecto($this->proyecto).'/img/'.$this->imagen;
			}
		}
		if (!isset($imagen)) {
			$imagen = toba_recurso::imagen_toba($this->icono, false);
		}
		return $imagen;
	}
	
	function get_iconos()
	{
		return $this->iconos;
	}
	
	/*function get_utilerias()
	{
		return array();
	}*/
	
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

	function es_seleccionable()
	{
		return $this->seleccionable;
	}

	function get_hijos()
	{
		if ($this->orden_hijos) {
			asort($this->orden_hijos);
			$hijos = array();
			foreach (array_keys($this->orden_hijos) as $id) {
				$hijos[] = $this->hijos[$id];
			}
			return $hijos;
		} else {
			return $this->hijos;
		}
	}

	function tiene_propiedades()
	{
		return $this->propiedades;
	}

	function get_input($id)
	{
	}
	
	function get_apertura() 
	{
		return $this->abierto;
	}

	//-----------------------------------------------------------------------
	//-----      CSS          -----------------------------------------------
	//-----------------------------------------------------------------------

	function set_clase_css_ul($css)
	{
		$this->get_clase_css_ul = $css;
	}
	
	function set_estilo_css_ul($css)
	{
		$this->get_estilo_css_ul = $css;
	}

	function set_clase_css_li($css)
	{
		$this->get_clase_css_li = $css;
	}
	
	function set_estilo_css_li($css)
	{
		$this->get_estilo_css_li = $css;
	}

	//-- CSS ---------------------------------------	
		
	function get_clase_css_ul()
	{
		return $this->get_clase_css_ul;
	}
	
	function get_estilo_css_ul()
	{
		return $this->get_estilo_css_ul;
	}

	function get_clase_css_li()
	{
		return $this->get_clase_css_li;
	}
	
	function get_estilo_css_li()
	{
		return $this->get_estilo_css_li;
	}

	//-----------------------------------------------------------------------
	//-----      POST            ---------------------------------------
	//-----------------------------------------------------------------------

	/*function get_input($id)
	{
	}*/
	
	function cargar_estado_post($id)
	{
	}
	
	/*function set_apertura($abierto) 
	{
		$this->abierto = $abierto;
	}*/
	
	/*function get_apertura() 
	{
		return $this->abierto;
	}*/
	
	function set_controlador($controlador) {
		$this->controlador = $controlador;
	}
	
	
	function get_grafico()
	{
		$texto = 'entidad_'.$this->get_rand_id().' [label = "'.$this->get_nombre_largo().'"];'."\n";
		return $texto;
	}
	
	function get_restriccion($elem_id=false, $color = false, $arbol=false, $reverso=false) {
		$texto = '';
		if($arbol) {
			$this_rands = $arbol->get_rand_x_id($this->get_id());
			$elem_rands = $arbol->get_rand_x_id($elem_id);
			if(!$this_rands) {
				$this_rands = $this->get_rand_x_id($this->get_id());
			}
			if(!$elem_rands) {
				$elem_rands = $this->get_rand_x_id($elem_id);
			}
		} else {
			$this_rands = array($this->get_rand_id());
			$elem_rands = $this->get_rand_x_id($elem_id);
		}
		foreach($this_rands as $this_rand) {
			foreach($elem_rands as $elem_rand) {
				if($reverso) {
					$texto .= '"'.$this->datos['nombre'].'" -> entidad_' . $this_rand.' [label = "'.$this->datos['condicion'].'"';
					$texto .= " ltail=cluster_entidad_".$elem_rand.", lhead=cluster_entidad_".$this_rand;
				} else {
					$texto .= '"'.$this->datos['nombre'].'" -> entidad_' . $elem_rand .' [label = "'.$this->datos['condicion'].'"';
					$texto .= " ltail=cluster_entidad_".$this_rand.", lhead=cluster_entidad_".$elem_rand;
				}
				if ($color){
						$texto.=' color="'.$color.'"';
				} 
				$texto .= "];\n";
			}
		}
		return $texto;
	}
	
	function get_condicion() {
		return $this->datos['condicion'];
	}
	
	function get_rand_x_id($id, $frompadre=false, $fromhijo=false) {
		$rands = array();
		if($this->get_id() == $id) {
			$rands[] = $this->get_rand_id();
		}
		foreach($this->get_hijos() as $hijo){
			if((!$fromhijo || $fromhijo != $hijo->get_rand_id()) && $rand = $hijo->get_rand_x_id($id,true)) {
				$rands = array_merge($rands, $rand);
			}
		}
		if(!$frompadre && $padre = $this->get_padre()) {
			if($rand = $padre->get_rand_x_id($id, false, $this->get_rand_id())) {
				$rands = array_merge($rands, $rand);
			}
		}
		return $rands;
	}
}