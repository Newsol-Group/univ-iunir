<?php

class plan_elemento_gui_grupo extends plan_elemento_gui 
{

	function get_iconos()
	{
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		$iconos[] = array(
				'imagen' => toba_recurso::imagen_proyecto( 'correlativas/opcion.gif', false),
				'ayuda' => "Columnas",
				'javascript' => "onclick=\"{$id_objeto_js}.elemento_seleccionar(this)\""
			);		
		return $iconos;
	}
	
	function get_input($id)
	{
		if(!$this->mostrar_input) {
			return;
		}
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		$html = '';
		
		if ($this->controlador()->get_modo() == 'estructura') {
			//-- Adjuntar el requisito -------------------
			$id_input = $id.'_agregar_requisitos';
			$img = toba_recurso::imagen_proyecto('adjunto.png', false);
			$html .= "<img src='$img' id='".$id_input."_img' title='Agregar requisitos' onclick='{$id_objeto_js}.agregar_requisitos(\"$id_input\")' />";
			$html .= "<input type='hidden' value='0' id='$id_input' name='$id_input' />";		
			
			//-- Eliminar el grupo -------------------
			$id_input = $id.'_eliminar_grupo';
			$img = toba_recurso::imagen_proyecto('eliminar.gif', false);
			$html .= "<img src='$img' id='".$id_input."_img' title='Eliminar Opción' onclick='{$id_objeto_js}.eliminar_grupo(\"$id_input\")' />";
			$html .= "<input type='hidden' value='0' id='$id_input' name='$id_input' />";
			$html .= "&nbsp;";	
		}
		return $html;
	}
	
	function cargar_estado_post($id)
	{
		// controlo el agregado de requisitos
		$id_input = $id.'_agregar_requisitos';
		if (isset($_POST[$id_input])) {
			if ($_POST[$id_input]) {
				$requisitos = explode(',',$_POST[$id_input]);
				if(!isset($this->datos)) {	//El grupo no existe realmente
					$accion = $this->padre->get_dato('condicion_tipo');
					$this->controlador()->agregar_requisito_grupo_nuevo($accion, $requisitos);
				} else {
					$this->controlador()->agregar_requisito($this->get_dato('indice'), $requisitos );
				}
			}
		}
		
		// controlo por eliminacion de grupo
		if (isset($_POST[$id.'_eliminar_grupo'])) {
				if ($_POST[$id.'_eliminar_grupo']) {
					$this->controlador()->eliminar_grupo($this->get_dato('indice'));
			}
		}
	}
	
	function get_grafico()
	{
		$texto = '';
		foreach($this->get_hijos() as $hijo) {
			$texto .= $hijo->get_grafico();
		}
		return $texto;
	}

	function get_restriccion($elem_id=false, $color = false, $arbol=false, $reverso=false)
	{
		$texto = null;
		foreach($this->get_hijos() as $hijo) {
			$texto .= $hijo->get_restriccion($elem_id, $color, $arbol, $reverso);
		}
		return $texto;
	}
}
	
?>