<?php

class plan_elemento_gui_modulo_mg extends plan_elemento_gui_modulo
{
	function get_utilerias()
	{
		if ($this->es_modo_correlativas()) return array();
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		$iconos = array();
		if( $this->mostrar_utilerias_edicion ) {
			if( $this->mostrar_utileria_eliminacion ) {
				$iconos[] = array(
					'imagen' => toba_recurso::imagen_proyecto("propuesta/borrar.gif", false),
					'ayuda' => "Quitar la Materia Genérica",
					'javascript' => "onclick=\"{$id_objeto_js}.eliminar_elemento('{$this->id}','{$this->id_padre}','{$this->nombre_corto}')\""
				);
			}
		}
		return $iconos;
	}
	
	function get_iconos()
	{
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		if ($this->seleccionable) {
			$javascript = "onclick=\"{$id_objeto_js}.seleccionar_modulo(this.parentNode)\"";
		} else {
			$javascript = null;
		}	
		$iconos[] = array(
				'imagen' => toba_recurso::imagen_proyecto( 'propuesta/g_materia_generica.png', false),
				'ayuda' => "Materia Genérica",
				'javascript' => $javascript
			);			
		return $iconos;
	}		

	function get_input($id)
	{
		if (!$this->mostrar_input) 
		{
			return;
		}
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		$html = '';
		$html .= toba_form::select($id . '_combo_operador_not',$this->datos['operador_not'],$this->opciones_operador_not,"ef-combo","onchange={$id_objeto_js}.cambiar_botones();");
		if ($this->controlador()->get_modo() == 'estructura') {
			//-- Eliminar el requisito -------------------
			$id_input = $id.'_eliminar_requisito';
			$img = toba_recurso::imagen_proyecto('eliminar.gif', false);
			$html .= "<img src='$img' id='".$id_input."_img' title='Eliminar requisito' onclick='{$id_objeto_js}.eliminar_requisito(\"$id_input\")' />";
			$html .= "<input type='hidden' value='0' id='$id_input' name='$id_input' />";
		}	
		return $html;
	}
}
	
?>