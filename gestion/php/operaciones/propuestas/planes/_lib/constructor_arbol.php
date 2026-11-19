<?php

class constructor_arbol
{	
	protected $info_elementos;
	protected $elementos;
	protected $metodo_configuracion_nodos;
	
	function __construct($info_elementos)
	{
		$this->info_elementos = $info_elementos;	
	}
	
	function get_elementos()
	{
		return $this->construir_grafo_elementos($this->info_elementos);
	}

	function set_metodo_configurador($objeto, $metodo)
	{
		$this->metodo_configuracion_nodos = array($objeto, $metodo);	
	}

	/**
	*	Construye el grafo y devuelve el nodo raiz
	*/	
	protected function construir_grafo_elementos($elementos)
	{
		foreach ($elementos as $elemento) {
			$e = $this->construir_elemento($elemento['elemento_clase_gui'], $elemento);
			if ( ! isset($elemento['elemento_padre']) ) {
				$nodo_raiz = $e;
			} else {
					$padre = $this->elemento($elemento['elemento_padre']);
					if (isset($elemento['orden'])) {
						$padre->agregar_hijo($e, $elemento['orden']);
					} else {
						$padre->agregar_hijo($e);
					}
			}
			// Configuracion del nodo desde el consumidor
			if ( isset($this->metodo_configuracion_nodos) ) {
				$objeto = $this->metodo_configuracion_nodos[0];
				$metodo = $this->metodo_configuracion_nodos[1];
				$objeto->$metodo($e, $elemento);
			}
		}
		return $nodo_raiz;
	}

	protected function construir_elemento($clase, $info) {}
	
	protected function elemento($elemento)
	{
		return $this->elementos[$elemento];
	}
}
?>