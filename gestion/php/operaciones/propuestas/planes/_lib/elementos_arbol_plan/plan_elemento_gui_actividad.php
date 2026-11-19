<?php

class plan_elemento_gui_actividad extends plan_elemento_gui_elemento 
{
	protected $id_padre;
			
	function __construct($id, $nombre, $datos, $id_padre=null)
	{
		if (!isset($datos['subtipo'])) {
			$datos['subtipo'] = elemento::subtipo_materia;
		}
		if (! isset($datos['estado'])) $datos['estado'] = apex_ef_no_seteado;
		$estados = g3entidad::get_subtipo_estados($datos['subtipo']);
		$this->estados[apex_ef_no_seteado] = 'Seleccione estado';
		foreach( $estados as $estado ) {
			$this->estados[$estado['valor']] = $estado['descr'];
		}
		$this->id_padre = $id_padre;
		parent::__construct($id, $nombre, $datos);
	}	
		
	function get_iconos()
	{
		$icono_img = $this->get_icono_elemento();
		$icono_finfo = pathinfo($icono_img);
		if ($this->tiene_ancestro_compartible) {
			$icono_img = $icono_finfo['dirname'].'/'.$icono_finfo['filename'].'_comp_desc.'.$icono_finfo['extension'];
		}
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		if ($this->seleccionable && ! $this->es_modo_correlativas()) {
			$javascript = "onclick=\"{$id_objeto_js}.seleccionar_actividad(this.parentNode)\"";
		} else {
			$javascript = null;
		}		
		$iconos[] = array(
				'imagen' => toba_recurso::imagen_proyecto( $icono_img , false),
				'ayuda' => $this->nombre_largo,
				'javascript' => $javascript
			);
		
		/**
		 * El ícono de copia de copia de correlatividades sólo se encuentra disponible para la operación
		 * 3558 - Asignación de Correlativas
		 */
		$item_solicitado = toba::memoria()->get_item_solicitado();
		if ($item_solicitado[1] == '3558' && ! $this->es_modo_correlativas()) {
			// Se modifica el tooltip del ícono de edición.
			$iconos[0]['ayuda'] = 'Editar correlativas';
			
			// Se agrega el evento de Copia de correlatividades.
			$javascript = "onclick=\"{$id_objeto_js}.copiar_correlativas(this.parentNode)\"";
			$iconos[] = array(
					'imagen' => toba_recurso::imagen_proyecto('correlativas/copiar_correlativas.png' , false),
					'ayuda' => 'Copiar correlativas de otra actividad',
					'javascript' => $javascript
				);
			
		}
		return $iconos;
	}		

	function get_utilerias()
	{
		if ($this->es_modo_correlativas()) return array();
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		$iconos = array();
		if ($this->controlador && $this->controlador->get_tiene_ancestro_compartible($this->id_padre)) {
			$this->mostrar_utileria_eliminacion = false;
		}
		return array_merge($iconos, parent::get_utilerias());
	}
	
	function get_input($id)
	{
		if (!$this->mostrar_input) {
				$html = '';
				if ($this->datos['operador_not'] != apex_ef_no_seteado) {
					$html .= '('.$this->opciones_operador_not[$this->datos['operador_not']].')&nbsp;&nbsp;';
				}
				if ($this->datos['estado'] != apex_ef_no_seteado) {
					$html .= '<b>'.$this->estados[$this->datos['estado']].'</b>&nbsp;&nbsp;&nbsp;';
				}
				
			if(!isset($this->datos['condicion']) || !$this->datos['condicion']) {
				return $html;
			}
			return '<span>'.$this->datos['condicion'].' '.$html.'</span>';
		}
		
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		//-- Combo con los estados -------------------
		
		$html = '';
		$html .= toba_form::select($id . '_combo_estados',$this->datos['estado'],$this->estados,"ef-combo","onchange={$id_objeto_js}.cambiar_botones();");
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
		$texto = parent::get_grafico();
		if(!$this->es_hoja()) {
			foreach($this->get_hijos() as $h) {
				$texto .= $h->get_grafico();
			}
		}
		return $texto;
	}

	function get_restriccion($elem_id=false, $color = false, $arbol=false, $reverso=false)
	{
		$texto='';
		if(!$this->es_hoja()) {
			foreach($this->get_hijos() as $h) {
				$texto .= $h->get_restriccion($this->get_id());
			}
		} else {
			$texto = parent::get_restriccion($elem_id, $color, $arbol, $reverso);
		}
		return $texto;
	}
}
	
?>