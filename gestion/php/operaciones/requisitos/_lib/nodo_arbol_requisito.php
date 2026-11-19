<?php
/**
 *
 *
 * @package 	Requisitos
 * 
 */
class nodo_arbol_requisito extends toba_nodo_basico
{
	
	protected $arbol;
	protected $mostrar_utilerias=true;
	protected $mostrar_eliminar=true;

	function ocultar_utilerias()
	{
		$this->mostrar_utilerias = false;
	}
	
	function mostrar_utilerias()
	{
		$this->mostrar_utilerias = true;
	}
	
	function ocultar_eliminar()
	{
		$this->mostrar_eliminar = false;
	}
	
	function mostrar_eliminar()
	{
		$this->mostrar_eliminar = true;
	}
	
	function get_utilerias()
	{
		//Las utilerías para asociarle los eventos al nodo, se retornan solo en caso de que el nodo sea raíz del árbol, 
		//ya que este es el único caso en que puede editarse y eliminarse nodos en el àrbol por requisitos.
		if ($this->mostrar_utilerias) {
			//El objeto arbol correspondiente nodo actual se mantiene en la variable $this->arbol y se setea cuando se lo 
			//construye. 
			$id_arbol = $this->arbol->get_id_objeto_js();
			//El id del objeto nodo actual se obtiene a partir de la funcion get_id de la clase toba_nodo_basico.
			$id_nodo = $this->get_id();
			$js_eliminar = "$id_arbol.evento_eliminar(\"$id_nodo\");";
			$js_seleccionar = "$id_arbol.evento_seleccionar(\"$id_nodo\");";
			$utilerías[] = array(
					'imagen' => toba_recurso::imagen_toba('doc.gif', false),
					'javascript' => "onclick='$js_seleccionar'",
					'ayuda' => 'Editar el requisito',
				);
			if ($this->mostrar_eliminar) {	
				$utilerías[] = 	array(
								'imagen' => toba_recurso::imagen_toba('borrar.gif', false),
								'javascript' => "onclick='$js_eliminar'",
								'ayuda' => 'Eliminar el requisito',
								);
			}	
			return $utilerías;
		}
	}
	
	/**
	 * Permite setear la variable de clase que identifica a qué árbol pertenece el objeto nodo actual.
	 * @param toba_ei_arbol $arbol
	 */
	function set_arbol(toba_ei_arbol $arbol) 
	{
		$this->arbol = $arbol;
	}
	
	/**
	 * Determina si el objeto nodo actual es el nodo raíz del árbol (es decir si tiene padre nulo).
	 */
	function es_nodo_raiz()
	{
		$padre = $this->get_padre();
		return is_null($padre);
	}
	
}
?>