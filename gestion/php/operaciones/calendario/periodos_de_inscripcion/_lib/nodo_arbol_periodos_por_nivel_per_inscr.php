<?php

class nodo_arbol_periodos_por_nivel_per_inscr extends toba_nodo_basico
{
	protected $arbol;
	protected $vista;
	
	function __construct($nombre, $padre=null, $id=null, $id_padre=null, $vista=null)
	{
		$this->vista = $vista;
		parent::__construct($nombre, $padre, $id, $id_padre);		
	}
	
	function get_utilerias()
	{
		$es_hoja = $this->es_hoja();
		$vista = $this->vista;
		
		if (($es_hoja && $vista == 'E') || (!$es_hoja && $vista == 'F')) {
			$id_arbol = $this->arbol->get_id_objeto_js();
			$id_nodo = $this->get_id();
			$js_editar = "$id_arbol.evento_editar_periodo(\"$id_nodo\");";
			$js_borrar = "$id_arbol.evento_borrar_periodo(\"$id_nodo\");";
			$iconos[] = array(
						'imagen' => toba_recurso::imagen_toba('doc.gif', false),
						'javascript' => "onclick='$js_editar'",
						'ayuda' => 'Editar el Período'
						);			
			return $iconos;
		}
	}
	
	function set_arbol(toba_ei_arbol $arbol) {
		$this->arbol = $arbol;
	}	
}
?>