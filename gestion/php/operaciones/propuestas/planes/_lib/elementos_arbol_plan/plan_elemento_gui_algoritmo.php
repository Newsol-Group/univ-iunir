<?php

class plan_elemento_gui_algoritmo extends plan_elemento_gui 
{
	function inicializar()
	{
		$this->nombre_largo = $this->nombre_corto;
	}
	
	function get_iconos()
	{
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		$iconos[] = array(
				'imagen' => toba_recurso::imagen_toba( 'funcion.png', false),
				'ayuda' => "Columnas",
				'javascript' => "onclick=\"{$id_objeto_js}.elemento_seleccionar(this)\""
			);			
		return $iconos;
	}

	function get_input($id)
	{
		if(!$this->mostrar_input)
		{
			if(!isset($this->datos['condicion']) || !$this->datos['condicion']) 
			{
				return;
			}
			return "<span>".$this->datos['condicion']."</span>";
		}
		//-- Eliminar el requisito -------------------
		$html = '';
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		$id_input = $id.'_oculto';
		$img = toba_recurso::imagen_proyecto('eliminar.gif', false);
		$html .= "<img src='$img' id='".$id_input."_img' title='Eliminar requisito' onclick='{$id_objeto_js}.eliminar_requisito(\"$id_input\")' />";
		$html .= "<input type='hidden' value='0' id='$id_input' name='$id_input' />";		
		return $html;
	}
	
	function cargar_estado_post($id)
	{
		// Controlo la eliminacion
		$id_input = $id.'_oculto';
		if (isset($_POST[$id_input])) {
			if ($_POST[$id_input]) {
				$this->controlador()->eliminar_requisito($this->valor);
				return;
			}
		}
	}
	
	function get_grafico()
	{
		$texto = 'entidad_' . $this->get_rand_id().' [label="'.$this->nombre_largo.'" shape=diamond style=rounded];'; 
		return $texto;
	}
	
	function get_restriccion($elem_id=false, $color = false, $arbol=false, $reverso=false) {
		$texto = '';
		if($arbol) {
			$this_rands = $arbol->get_rand_x_id($this->get_id());
			$elem_rands = $arbol->get_rand_x_id($elem_id);
		} else {
			$this_rands = array($this->get_rand_id());
			$elem_rands = $this->get_rand_x_id($elem_id);
		}
		foreach($this_rands as $this_rand) {
			foreach($elem_rands as $elem_rand) {
				$texto .= 'entidad_' . $this_rand.' -> entidad_' . $elem_rand .' [style=dashed label = "'.$this->datos['condicion'].'"';
				if ($color){
						$texto.=' color="'.$color.'"';
				} 
				$texto .= " lhead=cluster_entidad_".$elem_rand."];\n";
			}
		}
		return $texto;
	}
	

}
	
?>