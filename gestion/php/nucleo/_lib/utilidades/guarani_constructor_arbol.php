<?php

class guarani_constructor_arbol
{
	protected $nodo_raiz=null;
	protected $nodos_raices=array();
	protected $info_nodos;
	protected $niveles=array(); // guarani_arbol_nivel
	protected $nodos;
	protected $metodo_configurador;
	protected $objeto_js;
	protected $eventos_activos;
	
	function __construct()
	{
	}

	function set_objeto_js($objeto_js)
	{
		$this->objeto_js = $objeto_js;
	}

	function set_datos($datos)
	{
		$this->info_nodos = $datos;
	}

	function set_estructura($estructura)
	{
		$this->info_estructura = $estructura;
	}

	/*
	 * Configura un nivel jerárquico del árbol.
	 * La jerarquía se establece por el orden en que se seteen los niveles.
	 */
	function set_nivel($nivel) 
	{
		$nivel->set_jerarquia($this->get_profundidad());
		if ($this->get_profundidad() > 0) {
			$nivel->set_padre($this->niveles[$this->get_profundidad() - 1]);
		}
		$this->niveles[] = $nivel;
	}

	function set_nivel_raiz($nivel,$id,$descripcion)
	{
		$this->set_nivel($nivel);
		$this->set_nodo_raiz($id,$descripcion);
	}


	function hay_niveles()
	{
		return isset($this->niveles);
	}

	function get_nodos()
	{
		return $this->construir_grafo_nodos();
	}

	function set_metodo_configurador($clase, $metodo)
	{
		$this->metodo_configurador = array( 'clase' => $clase, 'metodo' => $metodo);
	}

	/**
	*	Construye el grafo y devuelve el nodo raiz
	*/	
	protected function construir_grafo_nodos()
	{
		if ($this->hay_niveles()) return $this->construir_grafo_nodos_niveles();
		$nodos = $this->info_nodos;
		foreach ($nodos as $nodo) {
			$n = $this->construir_nodo($nodo);
			if ( ! isset($nodo['nodo_padre']) ) {
				$nodo_raiz = $n;
			} else {
					$padre = $this->nodo($nodo['nodo_padre']);
					if (isset($nodo['orden'])) {
						$padre->agregar_hijo($n,$nodo['orden']);
					} else {
						$padre->agregar_hijo($n);
					}
			}
			// Configuracion del nodo desde el consumidor
			$this->configurar_nodo($n);
		}
		return $nodo_raiz;
	}

	/**
	*	Construye el grafo y devuelve el/los nodos raices a partir de la estructurada preestablecida.
	*/
	protected function construir_grafo_nodos_niveles()
	{
		$nodos = $this->info_nodos;
		if ($nodos == array() && isset($this->nodo_raiz)) {
			$nodos[] = array($this->niveles[0]->get_col_id() => $this->nodo_raiz->get_id(), $this->niveles[0]->get_col_desc() => $this->nodo_raiz->get_nombre_corto());
		}

		//Por cada registro de datos verifico qué
		//nuevos nodos se están definiendo en cada nivel
		foreach ($nodos as $key => $nodo) {
			//Si hay un nodo raíz único
			if (isset($this->nodo_raiz)) {
				$nodo[$this->niveles[0]->get_col_id()] = $this->nodo_raiz->get_id();
				$nodo[$this->niveles[0]->get_col_desc()] = $this->nodo_raiz->get_nombre_corto();
			}
			//Por cada nivel
			foreach ($this->niveles as $nivel) {
				if (!isset($nodo[$nivel->get_col_id()])) continue;
				$id = $nodo[$nivel->get_col_id()];
				//Si es nuevo
				if (isset($id) && ! $this->existe_nodo($nivel->get_jerarquia(),$id)) {
					$info_nodo = $nodo;
					$n = $this->construir_nodo($nivel,$id,$info_nodo[$nivel->get_col_desc()]);
					//Si es raíz
					if ($nivel->get_jerarquia() == 0) {
						$this->nodos_raices[] = $n;
					} else {
						$nivel_padre = $nivel->get_padre();
						$padre = $this->nodo($nivel_padre->get_jerarquia(), $nodo[$nivel_padre->get_col_id()]);
						$n->set_padre($padre);
						$padre->agregar_hijo($n);
					}
					$iconos = $this->armar_eventos_js($nivel->get_id(), $nivel->get_iconos(),$id);
					$n->set_iconos($iconos);
					$utilerias = $this->armar_eventos_js($nivel->get_id(), $nivel->get_utilerias(),$id);
					$n->set_utilerias($utilerias);

					$this->configurar_nodo_de_nivel($n);
					$this->configurar_nodo($n);

					$this->eventos_activos[$nivel->get_id()][$n->get_id()] = $n->get_eventos_activos();
				}
			}
		}
		return $this->nodos_raices;
	}

	function set_nodo_raiz($id,$descripcion)
	{
		$n = new guarani_nodo_basico($id,null,$descripcion);
		$this->nodo_raiz = $n;
		return $n;
	}

	protected function construir_nodo($nivel,$id=null,$descripcion=null,$padre=null)
	{
		$n = new guarani_nodo_basico($id,$padre,$descripcion,$nivel);
		$this->nodos[$nivel->get_jerarquia()][$id] = $n;
		return $n;
	}	

	protected function configurar_nodo($nodo)
	{
		if (isset($this->metodo_configurador['clase'])) {
			$clase = $this->metodo_configurador['clase'];
			$metodo = $this->metodo_configurador['metodo'];
			if (method_exists($clase, $metodo)) {
				$clase->$metodo($nodo);
			}
		}
	}

	protected function configurar_nodo_de_nivel($nodo)
	{
		$nivel = $nodo->get_nivel();
		$metodo_configurador = $nivel->get_metodo_configurador();
		if (isset($metodo_configurador['clase'])) {
			$clase = $metodo_configurador['clase'];
			$metodo = $metodo_configurador['metodo'];
			if (method_exists($clase, $metodo)) {
				$clase->$metodo($nodo);
			}
		}
	}

	protected function nodo($nivel=null,$id_nodo=null)
	{
		return $this->nodos[$nivel][$id_nodo];
	}

	protected function existe_nodo($nivel,$id_nodo)
	{
		return isset($this->nodos[$nivel][$id_nodo]);
	}

	function get_profundidad()
	{
		return guarani_arreglos::count($this->niveles);
	}

	function armar_eventos_js($id_nivel, $utilerias, $id_nodo, $confirmacion='')
	{
		foreach ($utilerias as $key => $utileria) {
			if (isset($utileria['evento']) && $utileria['evento'] != '') {
				$js = "{$this->objeto_js}.accion_nodo('$id_nivel','{$utileria['evento']}','$id_nodo','{$utileria['confirmacion']}');";
				if (! isset($utilerias[$key]['javascript'])) {
					$utilerias[$key]['javascript'] = "onclick=\"$js\"";
				} else {
					if (strpos($utilerias[$key]['javascript'],"onclick=") !== false) {
						$utilerias[$key]['javascript'] = preg_replace('/onclick=(\"|\')([\.]*)/', "onclick=$1$js$2", $utilerias[$key]['javascript']);
					} else {
						$utilerias[$key]['javascript'] = " onclick=\"$js\"".$utilerias[$key]['javascript'];
					}
				}
			}
		}
		return $utilerias;
	}

	function validar($nivel, $evento, $id_nodo)
	{
		//ei_arbol( $this->eventos_activos[$nivel][$id_nodo],$evento );
		return (isset($this->eventos_activos[$nivel][$id_nodo][$evento]) && $this->eventos_activos[$nivel][$id_nodo][$evento]);
	}

	function get_eventos_activos()
	{
		return $this->eventos_activos;
	}

}
?>