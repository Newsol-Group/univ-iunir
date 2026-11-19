<?php

class nodo_arbol_periodos_por_nivel extends toba_nodo_basico
{
	protected $arbol;
	
	function get_utilerias()
	{
		$es_hoja = $this->es_hoja();
		
		if ($es_hoja) {
			$id_arbol = $this->arbol->get_id_objeto_js();
			$id_nodo = $this->get_id();
			$js_editar = "$id_arbol.evento_editar_periodo(\"$id_nodo\");";
			$iconos[] = array(
						'imagen' => toba_recurso::imagen_toba('doc.gif', false),
						'javascript' => "onclick='$js_editar'",
						'ayuda' => 'Editar el Período'
						);
			return $iconos;
		}
	}
	
	function set_arbol(toba_ei_arbol $arbol)
	{
		$this->arbol = $arbol;
	}
}
?>