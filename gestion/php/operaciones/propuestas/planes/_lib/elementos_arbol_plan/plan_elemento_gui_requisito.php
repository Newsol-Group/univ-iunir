<?php

class plan_elemento_gui_requisito extends plan_elemento_gui_elemento 
{

	function get_iconos()
	{
		$icono_img = $this->get_icono_requisito();
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		$iconos[] = array(
				'imagen' => toba_recurso::imagen_proyecto($icono_img, false),
				'ayuda' => $this->nombre_largo
			);
		if ($this->es_seleccionable()) {
			$iconos[0]['javascript'] = "onclick=\"{$id_objeto_js}.seleccionar_requisito(this.parentNode)\"";
		}
		return $iconos;
	}	
		
	function get_icono_requisito()
	{
		if (isset($this->datos['requisito'])) {
			$requisito = $this->datos['requisito'];
		} else {
			// Para el reporte de planes el identificador del requisito está "camuflado" en la componente 'elemento' de los datos.
			$clave_requisito = explode('r_', $this->datos['elemento']);
			$requisito = $clave_requisito[1];
		}
		$icono = 'propuesta/g_requisito.gif';
		if (isset($requisito)) {
			$datos_requisito = current(guarani::co('co_requisitos')->get_datos_requisito($requisito));
			if (isset($datos_requisito['icono'])) {
				$icono = $datos_requisito['icono'];
			}
		}
		return $icono;
	}

	function get_input($id)
	{
		if (!$this->mostrar_input) {
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
	
	function get_grafico()
	{
		$texto = 'entidad_' . $this->get_rand_id().' [label="Requisito: '.$this->nombre_largo.'" shape=rect style=rounded];'; 
		return $texto;
	}

}
	
?>