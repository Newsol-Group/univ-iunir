<?php
/**
 * 
 *
 *
 * @package Propuestas
 * @subpackage ValidadorPlanes
 */
class constructor_planes_validacion_nucleo extends guarani_constructor_arbol
{
	protected $metodo_constructor;
	
	function set_metodo_constructor($clase, $metodo)
	{
		$this->metodo_constructor = array('clase'=>$clase, 'metodo'=>$metodo);
	}

	function hay_niveles()
	{
		return (isset($this->niveles) && $this->niveles);
	}

	function construir_nodo($nodo,$id=null,$descripcion=null,$padre=null)
	{
		if ($this->nodo(null,$nodo['id_nodo'])) {
			$n = $this->nodo(null,$nodo['id_nodo']);
		} elseif (isset($this->metodo_constructor['clase']) && method_exists($this->metodo_constructor['clase'], $this->metodo_constructor['metodo'])) {
			$clase = $this->metodo_constructor['clase'];
			$metodo = $this->metodo_constructor['metodo'];
			$n = $clase->$metodo($nodo);
			$this->nodos[$nodo['id_nodo']] = $n;
		} else {
			$n = parent::construir_nodo($nodo);
		}
		return $n;
	}
	
	function nodo($nivel=null,$id_nodo=null)
	{
		return isset($this->nodos) && array_key_exists($id_nodo, $this->nodos)?$this->nodos[$id_nodo]:null;
	}
	
	protected function construir_grafo_nodos()
	{
		if ($this->hay_niveles()) return $this->construir_grafo_nodos_niveles();
		$nodos = $this->info_nodos;
		foreach ($nodos as $nodo) {
			$n = $this->construir_nodo($nodo);
			if ( ! isset($nodo['nodo_padre']) || $nodo['nodo_padre'] == null) {
				$nodo_raiz = $n;
			} else {
					$padre = $this->nodo(null,$nodo['nodo_padre']);
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
}
?>