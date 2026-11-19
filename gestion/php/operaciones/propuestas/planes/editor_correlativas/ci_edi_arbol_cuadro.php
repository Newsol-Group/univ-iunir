<?php
class ci_edi_arbol_cuadro extends ci_navegacion
{
	
	protected $s__pantalla = null;
		
	function ini()
	{
		if (guarani::parametros()->get_valor('pro_ver_optativas_en_estructura_plan') == comunes::no) {
			$this->s__pantalla = 'cuadro_plan';
		}
		if ($this->s__pantalla){
			$this->set_pantalla($this->s__pantalla);
		}
	}

	function conf()
	{
		if (guarani::parametros()->get_valor('pro_ver_optativas_en_estructura_plan') == comunes::no) {
			$this->pantalla()->tab('arbol')->anular();
		}
	}
	
	//------ Arbol ----------------------	
	function conf__cuadro_plan()
	{
		$this->s__pantalla = 'cuadro_plan';
	}
	
	function conf__arbol()
	{
		$this->s__pantalla = 'arbol';
	}
	
	
	function conf__arbol_plan(toba_ei_arbol $arbol)
	{
		$elementos = $this->cn()->get_correlativas_plan_version();
		$constructor = new constructor_arbol_plan( $elementos );
		$constructor->set_metodo_configurador($this, 'configurar_nodo');
		$elementos = $constructor->get_elementos();
		$arbol->set_datos(array($elementos));
		$arbol->set_nivel_apertura(8);
	}

	function configurar_nodo($nodo, $info)
	{
		if ($info['cond_cursada']) {
			$imagen_cursada = 'correlativas/cursada.png';
			$ayuda_cursada = 'Posee Correlativas';
		} else {
			$imagen_cursada = 'correlativas/cursada_off.png';
			$ayuda_cursada = 'No posee Correlativas';
		}
		if ($info['cond_aprobada']) {
			$imagen_aprobada = 'correlativas/aprobada.png';
			$ayuda_aprobada = 'Posee Correlativas';
		} else {
			$imagen_aprobada = 'correlativas/aprobada_off.png';
			$ayuda_aprobada = 'No posee Correlativas';
		}
		$nodo->mostrar_utilerias_edicion(false);
		if	( ! $nodo instanceof plan_elemento_gui_modulo ) {
			$cursada_ok = array(	'imagen' => toba_recurso::imagen_proyecto($imagen_cursada, false),
									'ayuda' => $ayuda_cursada);
			$aprobada_ok = array(	'imagen' => toba_recurso::imagen_proyecto($imagen_aprobada, false),
									'ayuda' => $ayuda_aprobada);
			$nodo->set_utilerias(array($cursada_ok,$aprobada_ok));
		} else {
			$nodo->habilitar_seleccion(false);
		}
	}
	
	function evt__copia_correlativas($elemento)
	{
		$filtro = 'sga_elementos.elemento = ' . toba::db()->quote($elemento);
		$datos_elemento = current($this->cn()->get_correlativas_plan_version($filtro));
		
		/** No se puede aplicar la Copia de Correlativas si el no es editable el Plan (cn->es_editable)
		 *  o la Actividad a la que se le quieren copiar las correlativas no es una optativa de genérica 
		 *  sin mesas ni comisiones, en dicho Plan Versión.
		 */
		if (!$this->cn()->es_editable() &&
			!$this->cn()->es_actividad_editable($datos_elemento['entidad'])) {
			throw guarani::error('No se pueden copiar correlativas porque el plan y la actividad no son editables');
		}
		
		if (($datos_elemento['cond_cursada'] + $datos_elemento['cond_aprobada']) > 0) {
			throw guarani::error(guarani_mensajes::get('800PRO_error_copiar_correlatividades'));
		}
		
		$info_elemento = toba::consulta_php('co_elementos')->get_info_elemento($elemento);
		$datos_elem['elemento'] = $info_elemento['id'];
		$datos_elem['entidad'] = $info_elemento['entidad'];
		$this->controlador()->dep('editor_copia')->set_seleccion($datos_elem);
		$this->controlador->set_pantalla('copia');
	}
	
	//------ Cuadro ----------------------
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->cn()->seleccionar_elemento($seleccion);
		$this->controlador->set_pantalla('edicion');
	}

	function evt__seleccion_nodo($elemento)
	{
		$info_elemento = toba::consulta_php('co_elementos')->get_info_elemento($elemento);
		$datos_elem['elemento'] = $info_elemento['id'];
		$datos_elem['entidad'] = $info_elemento['entidad'];
		$this->cn()->seleccionar_elemento($datos_elem);
		$this->controlador->set_pantalla('edicion');
	}
	
	function conf__cuadro(toba_ei_cuadro $cuadro) 
	{
		$elementos = $this->cn()->get_correlativas_plan_version($this->s__filtro);
		$orden = Array();
		foreach ($elementos as $key => $elemento) {
			if ($elemento['entidad_tipo'] == elemento::tipo_modulo || isset($esta[$elemento['elemento']] )) {
				unset($elementos[$key]);
			} else {
				$orden[] = guarani_texto::simplificar_caracteres($elemento['nombre']);	
				if ($elemento['cond_aprobada'] == 0) {
					$elementos[$key]['imagen_aprobada'] = toba_recurso::imagen_proyecto('correlativas/aprobada_off.png', true);
				} else {
					$elementos[$key]['imagen_aprobada'] = toba_recurso::imagen_proyecto('correlativas/aprobada.png', true);
				}
				if ($elemento['cond_cursada'] == 0) {
					$elementos[$key]['imagen_cursada'] = toba_recurso::imagen_proyecto('correlativas/cursada_off.png', true);
				} else {
					$elementos[$key]['imagen_cursada'] = toba_recurso::imagen_proyecto('correlativas/cursada.png', true);
				}
			}
			if (!isset($esta[$elemento['elemento']])){
				$esta[$elemento['elemento']] = true;	
			}
		}
		array_multisort($orden, $elementos);
		$cuadro->set_datos($elementos);    
	}
	
	function evt__cuadro__copiar_correlativas($seleccion)
	{
		 $this->controlador()->dep('editor_copia')->set_seleccion($seleccion);
		 $this->controlador->set_pantalla('copia');
	}

	function conf_evt__cuadro__copiar_correlativas(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
		$datos_fila = $datos[$fila];
		$cantidad_correlativas = $datos_fila['cond_aprobada'] + $datos_fila['cond_cursada'];
		// Si el plan versión está en estado editable o la actividad es optativa de genérica sin mesas ni comisiones,
		// y no tiene correlativas de cursada ni de examen ya asociadas, se habilita el evento de "Copiar Correlativas"
		if ((!$this->cn()->es_editable() && !$this->cn()->es_actividad_editable($datos_fila['entidad'])) 
			|| ($cantidad_correlativas > 0)) {
			$evento->anular();
		}
	}
	
	
	function evt__cuadro_plan__salida()
	{
		unset($this->s__datos);
		$this->s__filtro = "";
	}

}
?>