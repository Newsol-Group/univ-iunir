<?php 

class ci_edi_condiciones extends guarani_pers_ci
{
	protected $s__modo = 'estructura';
	protected $s__tipo_elementos_seleccionables_tipo;
    protected $condiciones_asignadas_ids;
	protected $grupos_creados_ids;
    protected $requisitos_asignados_ids;
    protected $s__requisitos;

	function conf() 
	{
		if ($this->cn()->entidad()->get_guardado() || (! $this->cn()->entidad()->es_plan_version_editable())) {
			$this->controlador()->evento('cancelar')->set_etiqueta('&Volver');
			//$this->controlador()->evento('guardar')->desactivar();
		}
		if ($this->cn()->entidad()->dep('datos')->hay_cambios()) {
			$this->controlador()->controlador()->set_hubo_cambios();
		}
	}
	
	function set_tipo_elementos_seleccionables($tipo) 
	{
		$this->s__tipo_elementos_seleccionables_tipo = $tipo;
	}

	function set_modo($modo) {
		$this->s__modo = $modo;
	}	
		
	function get_modo() {
		return $this->s__modo;
	}		
		
	//--- Arbol de restricciones ---------------------

	function construir_arbol($condiciones)
	{
        $i = 0;
        $this->s__requisitos = array();
        foreach ($condiciones as $condicion){
            $i=$i+1;
            $a = new plan_elemento_gui_accion($i,$condicion['nombre'],array('condicion_tipo'=>$condicion['condicion_tipo']));
            $a->set_apertura(true);
            $a->set_modo_correlativas();
            $a->set_mostrar_input($this->cn()->es_editable());
			$tiene_grupos = false;
			if (isset($condiciones[$condicion['condicion_tipo']]['grupos'])) {
				$tiene_grupos = true;
				$grupos = $condiciones[$condicion['condicion_tipo']]['grupos'];
			}
            $raices[]= $a;
            $opcion = 0;
            if ($tiene_grupos) {
                $this->condiciones_asignadas_ids[] = $condicion['condicion_tipo'];
	            foreach ($grupos as $grupo){
	            	$this->grupos_creados_ids[] = $grupo['indice'];
	                $opcion = $opcion + 1;
	                $i=$i+1;
	                $g = new plan_elemento_gui_grupo($i, 'Opción '.$opcion, array('indice' => $grupo['indice']));
	                $g->set_modo_correlativas();
	                $a->agregar_hijo($g);
	                if (isset($grupo['requisitos'])) {
			            $requisitos = $grupo['requisitos'];
		                $g->set_apertura(true);
		                $g->set_mostrar_input($this->cn()->es_editable());
			            foreach ($requisitos as $requisito){
			                $i=$i+1;
			                //$requisitos_asignados_ids[] = $requisito['indice'];
			                $this->s__requisitos[$i] = $requisito['indice'];
							$requisito['requisito_tipo'] = 'R';
			                if (isset($requisito['entidad'])) {
			                	$requisito['requisito_tipo'] = 'E';
			                } 
			                switch ($requisito['requisito_tipo']) {
	    						case 'E':
	    							$datos_entidad = toba::consulta_php('co_g3entidades')->get_datos_elemento_plan($requisito['entidad'], $this->cn()->get_id_plan_version());
									//Instanciar la clase que corresponda segun el tipo (Actividad, mat Gen, modulo)
	    							$clase = g3entidad::get_clase_gui_subtipo($datos_entidad['entidad_subtipo']);
	    							$requisito['tipo'] = $datos_entidad['entidad_tipo'];
	    							$requisito['subtipo'] = $datos_entidad['entidad_subtipo'];
									
									$descripcion = $datos_entidad['nombre'];
									// Se definió que para los Módulos de cualquier subtipo también se antepone el código al nombre, como para las Actividades.
									if (in_array($datos_entidad['entidad_tipo'], [elemento::tipo_actividad, elemento::tipo_modulo]) && isset($datos_entidad['codigo'])) {
										$descripcion = "({$datos_entidad['codigo']}) {$datos_entidad['nombre']}";
									}
									
									$r = new $clase($i, $descripcion, $requisito, $g->get_dato('indice'));
									break;
	    						case 'R':
	    							$nombre_requisito = toba::consulta_php('co_requisitos')->get_requisito($requisito['requisito']);
									$parametros = toba::consulta_php('co_requisitos')->get_requisito_regla_params_def($requisito['requisito']);
									$r = new plan_elemento_gui_requisito($i, $nombre_requisito, $requisito);
									if ($parametros == array()) {
										$r->inhabilitar_seleccion();
									}
	                				break;
	       	    			}
			                $r->set_apertura(true);
			                //Si está seleccionado le pinto el fondo
			                if ($this->get_modo() == 'propiedades' && $this->dep('propiedades')->get_edicion_nodo() == $r->get_dato('indice')) {
			                	$r->set_estilo_css_li('font-weight: bold; background: rgb(158, 187, 217)');
			            	}
			                $r->set_mostrar_input($this->cn()->es_editable());
			                $r->set_modo_correlativas();
			                $g->agregar_hijo($r);
			            }
	                }
	            }
            }
        }
        return $raices;
	}
	
	//--- Listados de Requisitos --------------------
	
	function evt__editar_estructura()
	{	
		//Seteo estado de edición de estructura
		$this->set_modo('estructura');
	}

	function evt__seleccion_nodo($nodo)
	{	
		//Editar propiedades del módulo
		//Seteo estado de edición de propiedades
		$this->set_modo('propiedades');
		$this->dep('propiedades')->set_edicion_nodo($this->s__requisitos[$nodo]);
	}	

	function evt__cancelar()
	{
		$this->set_modo('estructura');
		unset($this->s__tipo_elementos_seleccionables_tipo);
	}
	
	//---------------------------------------------------------------------
	//-- Eventos de los arboles
	//---------------------------------------------------------------------

	function agregar_grupo($condicion)
	{
		//existe grupo vacio?
		// si existe excepcion
		return $this->cn()->entidad()->agregar_grupo($condicion);
	}

	function agregar_requisito_grupo_nuevo($condicion, $requisitos)
	{	
		if(!$this->cn()->entidad()->existe_condicion($condicion)) {
			$this->cn()->entidad()->agregar_condicion($condicion);
		}
		foreach ($requisitos as $requisito){
        	    toba::memoria()->en_array_operacion('requisitos_disponibles', $requisito);
		}
		$grupo = $this->cn()->entidad()->agregar_grupo($condicion);
        foreach ($requisitos as $requisito){
        		$id_requisito = $this->cn()->entidad()->agregar_requisito($grupo, $requisito, $this->s__tipo_elementos_seleccionables_tipo);
       	}
	}
	
	function agregar_requisito($grupo, $requisitos)
	{
		$tipo = $this->s__tipo_elementos_seleccionables_tipo;
		foreach ($requisitos as $requisito) {
			toba::memoria()->en_array_operacion('requisitos_disponibles', $requisito);
			$id_requisito = $this->cn()->entidad()->agregar_requisito($grupo, $requisito, $tipo);
		}
	}

	function eliminar_requisito($requisito)
	{
		$this->cn()->entidad()->eliminar_requisito_ctrl_grupo($requisito);
	}
	
	function eliminar_grupo($grupo)
	{
		$this->cn()->entidad()->eliminar_grupo_ctrl_condicion($grupo);
	}
	
	function modificar_requisito($requisito, $datos)
	{
		$this->cn()->entidad()->modificar_requisito($requisito,$datos);
	}
	
	function get_elementos_disponibles($tipo, $filtro)
	{	
		if ($tipo == 'A') {
			$clave = 'id';
			if ($filtro) $filtro .= ' AND ';
			$filtro .= ' sga_planes_versiones.plan_version = '.$this->cn()->entidad()->get_id_plan_version().' ';
			$datos = toba::consulta_php('co_elementos')->get_actividades_y_materias_genericas_condiciones($filtro);
		} elseif ($tipo == 'P') {
			//Modulos incluyendo módulos raíz
			//Se incluyen módulos RTF
			$clave = 'id';
			if ($filtro) $filtro .= ' AND ';
			$filtro .= ' sga_planes_versiones.plan_version = '.$this->cn()->entidad()->get_id_plan_version().' ';
			$datos = toba::consulta_php('co_elementos')->get_modulos($filtro,true, true);
		} elseif ($tipo == 'M') {
			//Tener en cuenta que al recuperar los Módulos se incluyen también los módulos RTF ya asociados al Plan.
			$clave = 'id';
			if ($filtro) $filtro .= ' AND ';
			$filtro .= ' sga_planes_versiones.plan_version = '.$this->cn()->entidad()->get_id_plan_version().' ';
			$datos = toba::consulta_php('co_elementos')->get_modulos_correlativas($filtro, false, true);
		} elseif ($tipo == 'MG') {
			$clave = 'id';
			$datos = toba::consulta_php('co_elementos')->get_materias_genericas($filtro);
		} elseif ($tipo == 'R') {
			$clave = 'id';
			$datos = toba::consulta_php('co_requisitos')->get_requisitos_correlativas($filtro);			
		} 
		$mem = array();
		foreach(array_keys($datos) as $id) {
			$mem[] = $datos[$id][$clave];
			$url = toba_recurso::url_proyecto() . "/img/" . $datos[$id]['icono'];
			if ($tipo == 'R') {
				$ayuda = 'Tipo: ' . 'Requisito';
			} else {
				$ayuda = 'Tipo: ' . $datos[$id]['subtipo_nombre'];
			}
			$img = "<img title='$ayuda' src='$url'/>";			
			$datos[$id]['icono'] = $img;
		}
		toba::memoria()->set_array_operacion('requisitos_disponibles', $mem);
		return $datos;
	}
	
}

?>