<?php

class plan_elemento_gui_modulo extends plan_elemento_gui_elemento
{
	protected $id_elemento;
	protected $id_padre;
	protected $mostrar_utileria_asociacion = false;
	
	function mostrar_utileria_asociacion($estado=true)
	{
		$this->mostrar_utileria_asociacion = $estado;
	}

	function __construct($id, $nombre, $datos, $id_padre=null, $id_revision = false)
	{
		if(!isset($datos['subtipo'])) {
			$datos['subtipo'] = elemento::subtipo_agrupador_modulos;
		}
		if (! isset($datos['estado'])) $datos['estado'] = apex_ef_no_seteado;
		$estados = g3entidad::get_subtipo_estados($datos['subtipo']);
		$this->estados[apex_ef_no_seteado] = 'Seleccione estado';
		foreach( $estados as $estado ) {
			$this->estados[$estado['valor']] = $estado['descr'];
		}
		$this->id_elemento = $id;
		$this->id_padre = $id_padre;
		parent::__construct(($id_revision)?$id_revision:$id, $nombre, $datos);
	}

	function get_iconos()
	{
		$icono_img = $this->get_icono_elemento();
		if (isset($this->controlador) && $this->controlador->es_compartible()) {
			$icono_finfo = pathinfo($icono_img);
			$icono_img = $icono_finfo['dirname'].'/'.$icono_finfo['filename'].'_comp.'.$icono_finfo['extension'];
		}
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		if ($this->seleccionable) {
			$javascript = "onclick=\"{$id_objeto_js}.seleccionar_modulo(this.parentNode)\"";
		} else {
			$javascript = null;
		}
		$iconos[] = array(
				'imagen' => toba_recurso::imagen_proyecto( $icono_img , false),
				'ayuda' => $this->nombre_largo,
				'javascript' => $javascript 
			);		
		return $iconos;
	}
	
	function get_input($id)
	{
		if (!$this->mostrar_input) 
		{
			if(!isset($this->datos['condicion']) || !$this->datos['condicion']) 
			{
				return;
			}
			return "<span>".$this->datos['condicion']."</span>";
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
	
	function get_utilerias()
	{
		if ($this->es_modo_correlativas()) return array();
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		$iconos = array();
		if (! ((isset($this->controlador) && $this->get_controlador()->es_compartible()) || $this->tiene_ancestro_compartible)) {
			if( $this->mostrar_utilerias_edicion ) {
				if ($this->mostrar_utileria_asociacion) {
					$iconos[] = array(
						'imagen' => toba_recurso::imagen_proyecto("adjunto.png", false),
						'ayuda' => "Asociar actividades al módulo",
						'javascript' => "onclick=\"{$id_objeto_js}.modulo_asociar_elementos('{$this->id}')\""
					);
				}
				$iconos[] = array(
					'imagen' => toba_recurso::imagen_proyecto("propuesta/agregar.gif", false),
					'ayuda' => "Agregar un módulo a la propuesta",
					'javascript' => "onclick=\"{$id_objeto_js}.modulo_agregar('{$this->id}')\""
				);
			}
		}
		return array_merge($iconos, parent::get_utilerias());
	}
	
	function get_grafico()
	{
		if (!$this->es_hoja() && $this->tiene_hijos_cargados())
		{
			$texto= 'subgraph cluster_entidad_' . $this->get_rand_id() .' {'; 
			/*if (in_array($this->get_id(), $cert_hids)){
				$texto.=' style="filled" fillcolor="#adc2ff" ';
			}*/
			$texto.= 'label="'. $this->get_nombre_largo().'";';
			foreach($this->get_hijos() as $hijo) {
				$texto .= $hijo->get_grafico();	
			}
			$texto.='entidad_'.$this->get_rand_id().' [style=invis, height=0.0, width=0.0, margin=0.0, penwidth=0.0];}';
		} 
		elseif ($this->get_subtipo() == elemento::subtipo_materia_generica)
		{
			$texto ='entidad_' . $this->get_rand_id() . '[label="'.$this->get_nombre_largo().'"';
			/*if (in_array($this->get_id(), $cert_hids)){
				$texto.=' style="filled" fillcolor="#adc2ff"';
			}*/
	  		$texto.='];'; 
		}
		return $texto;
	}
	
	/*function get_restriccion($elem_id, $color = false, $tipo_elem='actividad') {
		
		$texto = 'entidad_' . $this->get_id().' -> entidad_' . $elem_id .' [label = "'.$this->datos['condicion'].'"';	
		
		if ($color){
			$texto.=' color="'.$color.'"';
		} 
		
		return $texto.']';
	}*/
}
	
?>