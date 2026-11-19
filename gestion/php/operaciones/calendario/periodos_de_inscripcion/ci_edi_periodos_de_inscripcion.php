<?php
class ci_edi_periodos_de_inscripcion extends ci_edi_datos_periodos_de_inscripcion
{
	//Tipos de vistas de árboles
	const vista_por_entidades = 'E';
	const vista_por_fechas = 'F';
	
	protected $s__nodo_seleccionado;
	protected $s__datos_ml;
	protected $s__vista;
	protected $s__tipo;
	protected $s__datos_filtro;
	protected $s__filtro;
	protected $datos_temp;
	protected $raiz_a_reusar;
	protected $nivel;
	protected $filtrando = false;

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION PERIODO ---------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- arbol_x_nivel ----------------------------------------------------------------
	
	function conf__arbol_x_nivel(toba_ei_arbol $arbol)
	{
		if (!isset($this->s__vista) ||
			(isset($this->s__vista) && ($this->s__vista == self::vista_por_fechas))) {
			$this->construir_arbol_por_fechas($arbol);
		}
		if (isset($this->s__vista) && ($this->s__vista == self::vista_por_entidades)) {
			$this->construir_arbol_por_nivel($arbol);
		}
	}
	
	function construir_arbol_por_nivel($arbol)
	{
		$raices 	= array();
		$formateo 	= new toba_formateo('html');
		$fechas 	= $this->cn()->entidad()->get_datos_per_inscr_fechas();
		
		foreach ($fechas as $clave_fecha => $valor_fecha) {
			$this->cn()->entidad()->set_cursor_periodo($clave_fecha);
			
			//Se formatean las Fechas de Inicio y Fin a visualizar:
			//Se las pasa al formato dd/mm/aaaa y se les quita la hora y zona horaria
			
			$fecha_ini_format = $formateo->formato_fecha($valor_fecha['fecha_inicio']);
			$fecha_fin_format = $formateo->formato_fecha($valor_fecha['fecha_fin']);
			
			$etiqueta_hijo = 'Inicio: '.$fecha_ini_format.' - Fin: '.$fecha_fin_format;
			switch ($valor_fecha['tipo']) {
				case g3entidad::nivel_aplicacion_tipo_subtipo:
					$subtipos = $this->cn()->entidad()->get_datos_per_inscr_subtipos();
					foreach ($subtipos as $clave_subtipo => $valor_subtipo) {
						if (!$this->tiene_raiz(g3entidad::nivel_aplicacion_tipo_subtipo, $valor_subtipo, $raices)) {
							$subtipo = toba::consulta_php('co_g3entidades')->get_datos_subtipo($valor_subtipo['entidad_subtipo']);
							$clave_padre = 'S_'.$valor_subtipo['entidad_subtipo'];
							$padre = new nodo_arbol_periodos_por_nivel_per_inscr($subtipo['nombre'], null, $clave_padre, null, self::vista_por_entidades);
							$padre->set_arbol($arbol);
							$raices[] = $padre;
						} else {
							$padre = $raices[$this->raiz_a_reusar];
							$clave_padre = 'S_'.$valor_subtipo['entidad_subtipo'];
						}
						$this->agregar_nodo_hijo($clave_fecha, $etiqueta_hijo, $padre, $clave_padre, $arbol, self::vista_por_entidades);
					}
					break;
				case g3entidad::nivel_aplicacion_tipo_entidad:
					$entidades = $this->cn()->entidad()->get_datos_per_inscr_entidad();
					foreach ($entidades as $clave_entidad => $valor_entidad) {
						if (!$this->tiene_raiz(g3entidad::nivel_aplicacion_tipo_entidad, $valor_entidad, $raices)) {
							$entidad = toba::consulta_php('co_g3entidades')->get_datos_entidad($valor_entidad['entidad']);
							$clave_padre = 'E_'.$valor_entidad['entidad'];
							$etiqueta_entidad = toba::consulta_php('co_periodos_de_inscripcion')->get_descripcion_entidad($valor_entidad['entidad'], $valor_fecha['entidad_tipo']);							
							$etiqueta_padre = ($valor_fecha['entidad_tipo'] == g3entidad::tipo_institucion ) ? 
											  $etiqueta_entidad['entidad_descripcion'].' (Para Toda la Institución)' : $etiqueta_entidad['entidad_descripcion'];
							$padre = new nodo_arbol_periodos_por_nivel_per_inscr($etiqueta_padre, null, $clave_padre, null, self::vista_por_entidades);
							$padre->set_arbol($arbol);
							$raices[] = $padre;
						} else {
							$padre = $raices[$this->raiz_a_reusar];
							$clave_padre = 'E_'.$valor_entidad['entidad'];
						}
						$this->agregar_nodo_hijo($clave_fecha, $etiqueta_hijo, $padre, $clave_padre, $arbol, self::vista_por_entidades);
					}
					break;
			}
			$this->cn()->entidad()->unset_cursor_periodo();
		}
		foreach ($raices as $clave => $valor) {
			$pos_menor = $this->buscar_pos_menor_entidad($raices, guarani_arreglos::count($raices), $clave);
			$temp = $raices[$clave];
			$raices[$clave] = $raices[$pos_menor];
			$raices[$pos_menor] = $temp;
		}				
		$arbol->set_datos($raices);
		$arbol->set_ancho_nombres(80);
		$arbol->set_todos_abiertos();
	}
	
	function buscar_pos_menor_entidad($raices, $tamanio, $desde)
	{
		$pos_menor = $desde;
		for ( $i = $desde + 1 ; $i <= $tamanio - 1 ; $i++ ) {
			$elem_actual = $raices[$i]->get_nombre_corto();
			$menor = $raices[$pos_menor]->get_nombre_corto();
			if ($this->entidad_es_menor($elem_actual, $menor)) {
				$pos_menor = $i;
			}
		}			
		return $pos_menor;
	}
	
	function entidad_es_menor($elem_actual, $menor)
	{
		$es_entidad_elem_actual = !substr_count($elem_actual, 'Todas');
		$es_entidad_menor = !substr_count($menor, 'Todas');
		if ($es_entidad_elem_actual && !$es_entidad_menor) {
			return true;
		}
		if (!$es_entidad_elem_actual && $es_entidad_menor) {
			return false;
		}
		if ($elem_actual < $menor) {
			return true;
		}
		return false;
	}
	
	function construir_arbol_por_fechas($arbol)
	{
		$raices 	= array();
		$formateo 	= new toba_formateo('html');
		$fechas 	= $this->cn()->entidad()->get_datos_per_inscr_fechas();
		
		foreach ($fechas as $clave_fecha => $valor_fecha) {
			$this->cn()->entidad()->set_cursor_periodo($clave_fecha);
			$clave_padre = $valor_fecha['tipo'].'_'.$clave_fecha;
			
			//Se formatean las Fechas de Inicio y Fin a visualizar:
			//Se las pasa al formato dd/mm/aaaa y se les quita la hora y zona horaria

			$fecha_ini_format = $formateo->formato_fecha($valor_fecha['fecha_inicio']);
			$fecha_fin_format = $formateo->formato_fecha($valor_fecha['fecha_fin']);
			
			$etiqueta_padre = 'Inicio: '.$fecha_ini_format.' - Fin: '.$fecha_fin_format;
			if ($valor_fecha['entidad_tipo'] == g3entidad::tipo_institucion) {
				$etiqueta_padre .= ' (Para Toda la Institución)';
			}
			switch ($valor_fecha['tipo']) {
				case g3entidad::nivel_aplicacion_tipo_subtipo:
					$subtipos = $this->cn()->entidad()->get_datos_per_inscr_subtipos();
					if (! empty($subtipos)) {
						$padre = new nodo_arbol_periodos_por_nivel_per_inscr($etiqueta_padre, null, $clave_padre, null, self::vista_por_fechas);
						$padre->set_arbol($arbol);
						$raices[] = $padre;
					}
					foreach ($subtipos as $clave_subtipo => $valor_subtipo) {
						$subtipo = toba::consulta_php('co_g3entidades')->get_datos_subtipo($valor_subtipo['entidad_subtipo']);
						$this->agregar_nodo_hijo($clave_fecha, $subtipo['nombre'], $padre, $clave_padre, $arbol, self::vista_por_fechas);
					}
					break;
				case g3entidad::nivel_aplicacion_tipo_entidad:
					$entidades = $this->cn()->entidad()->get_datos_per_inscr_entidad();
					if (! empty($entidades)) {
						$padre = new nodo_arbol_periodos_por_nivel_per_inscr($etiqueta_padre, null, $clave_padre, null, self::vista_por_fechas);
						$padre->set_arbol($arbol);
						$raices[] = $padre;
					}
					foreach ($entidades as $clave_entidad => $valor_entidad) {
						$entidad = toba::consulta_php('co_g3entidades')->get_datos_entidad($valor_entidad['entidad']);
						$etiqueta_hijo = toba::consulta_php('co_periodos_de_inscripcion')->get_descripcion_entidad($valor_entidad['entidad'], $valor_fecha['entidad_tipo']);
						$this->agregar_nodo_hijo($clave_fecha, $etiqueta_hijo['entidad_descripcion'], $padre, $clave_padre, $arbol, self::vista_por_fechas);
					}
					break;
			}
			$this->cn()->entidad()->unset_cursor_periodo();
		}
		foreach ($raices as $clave => $valor) {
			$pos_menor = $this->buscar_pos_menor_fecha($raices, guarani_arreglos::count($raices), $clave);
			$temp = $raices[$clave];
			$raices[$clave] = $raices[$pos_menor];
			$raices[$pos_menor] = $temp;
		}
		$arbol->set_datos($raices);
		$arbol->set_ancho_nombres(80);
		$arbol->set_todos_abiertos();
	}
	
	function buscar_pos_menor_fecha($raices, $tamanio, $desde)
	{
		$pos_menor = $desde;
		for ( $i = $desde + 1 ; $i <= $tamanio - 1 ; $i++ ) {
			
			//Creo un objeto toba_fecha con la Fecha actual del arreglo
			$elem_actual = substr($raices[$i]->get_nombre_corto(), 8, 10);
			$elem_actual_fecha_db = toba_fecha::desde_pantalla($elem_actual);
			
			//Creo un objeto toba_fecha con la Fecha menor parcial del arreglo
			$menor = substr($raices[$pos_menor]->get_nombre_corto(), 8, 10);
			$menor_fecha_db = toba_fecha::desde_pantalla($menor);
			
			if (guarani_fecha::es_menor($elem_actual_fecha_db->get_fecha_db(), $menor_fecha_db->get_fecha_db())) {
				$pos_menor = $i;
			}
		}
		return $pos_menor;
	}
	
	/**
	 * Retorna true en caso de que ya se haya creado la raiz para la entidad/entidad subtipo,
	 * y false en caso contrario.
	 *
	 * @param array $periodo_inscripcion
	 * @param array $raices
	 */
	function tiene_raiz($tipo, $periodo_inscripcion, $raices)
	{
		foreach ($raices as $clave => $valor) {
			switch ($tipo) {
				case g3entidad::nivel_aplicacion_tipo_subtipo:
					if ($valor->get_id() == 'S_'.$periodo_inscripcion['entidad_subtipo']) {
						$this->raiz_a_reusar = $clave;
						return true;
					}
					break;
				case g3entidad::nivel_aplicacion_tipo_entidad:
					if ($valor->get_id() == 'E_'.$periodo_inscripcion['entidad']) {
						$this->raiz_a_reusar = $clave;
						return true;
					}
					break;
			}
		}
		return  false;
	}

	/**
	 * Crea un nodo hijo a partir de un nodo padre y un árbol dados.
	 * Le asigna como etiqueta las fechas inicio y fin del Periodo actual.
	 *
	 * @param string									$clave
	 * @param array										$periodo
	 * @param nodo_arbol_periodos_por_nivel_per_inscr	$padre
	 * @param string									$clave_padre
	 * @param toba_ei_arbol								$arbol
	 */
	function agregar_nodo_hijo($clave, $etiqueta, $padre, $clave_padre, $arbol, $vista)
	{
		$hijo = new nodo_arbol_periodos_por_nivel_per_inscr($etiqueta, $padre, $clave_padre.'_'.$clave, null, $vista);
		$padre->agregar_hijo($hijo);
		$hijo->set_arbol($arbol);
	}
	
	function evt__editar_periodo($nodo)
	{	
		$entidad_seleccionada = substr($nodo, strripos($nodo, '_') + 1);
		$this->set_pantalla('edicion_periodo');
		$this->cn()->entidad()->set_cursor_periodo($entidad_seleccionada);
		$this->s__nodo_seleccionado = $entidad_seleccionada;
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION PERIODO -----------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion_periodo(toba_ei_pantalla $pantalla)
	{
		$tipo = $this->dep('form_conf_periodo')->ef('entidad_tipo_nuevo')->get_estado();
		$this->s__tipo = $tipo;
		if ($pantalla->existe_dependencia('form_ml_subtipo')) {
			$pantalla->eliminar_dep('form_ml_subtipo');
		}
		if ($pantalla->existe_dependencia('filtro_entidades')) {
			$pantalla->eliminar_dep('filtro_entidades');
		}
		if ($pantalla->existe_dependencia('form_ml_entidad')) {
			$pantalla->eliminar_dep('form_ml_entidad');
		}		
		if ($tipo) {
			$tipo_datos = explode(',',$tipo);
			$this->nivel['tipo'] = $tipo_datos[0];
			$this->nivel['entidad_tipo'] = $tipo_datos[1];
			$this->nivel['entidad_tipo_datos'] = g3entidad::get_datos_tipo($tipo_datos[1]);
			$this->nivel['para_todo'] = false;
			if ($tipo_datos[0] == g3entidad::nivel_aplicacion_tipo_total) {
				$this->nivel['para_todo'] = true;
			}
			
			if ($this->get_tipo() == g3entidad::nivel_aplicacion_tipo_subtipo) {
				$this->pantalla('edicion_periodo')->agregar_dep('form_ml_subtipo');
			}
			if ($this->get_tipo() == g3entidad::nivel_aplicacion_tipo_entidad) {
				$this->pantalla('edicion_periodo')->agregar_dep('filtro_entidades');
				$this->pantalla('edicion_periodo')->agregar_dep('form_ml_entidad');
			}			
			if ($this->nivel['tipo'] == g3entidad::nivel_aplicacion_tipo_total) {
				$this->nivel['tipo'] = g3entidad::nivel_aplicacion_tipo_entidad;
				$this->nivel['entidad_tipo'] = g3entidad::tipo_institucion;
			}
		}
	}
	
	function hay_tipo_definido() 
	{
		return isset($this->nivel['tipo']);
	}
	
	function get_tipo()
	{
		return $this->nivel['tipo'];
	}

	function get_entidad_tipo()
	{
		return $this->nivel['entidad_tipo'];
	}
	
	function get_entidad_tipo_dato($dato)
	{
		return $this->nivel['entidad_tipo_datos'][$dato];
	}
	
	function evt__edicion_periodo__salida()
	{
		unset($this->s__datos_ml);
		unset($this->s__tipo);
		unset($this->s__datos_filtro);
		unset($this->s__filtro);
	}
	
	//---- form_vista -------------------------------------------------------------------
	
	function conf__form_vista(form_vista $form)
	{
		$vista = isset($this->s__vista) ? $this->s__vista : self::vista_por_fechas;
		$form->set_datos(array('vista' => $vista));
	}
	
	function evt__form_vista__cambiar_vista($datos)
	{
		$this->s__vista = $datos['vista'];
	}
	
	//---- form_conf_periodo ------------------------------------------------------------

	function conf__form_conf_periodo(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp['form_conf'])) {
			$this->controlador->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp['form_conf']);
		} else {
			if ($this->cn()->entidad()->hay_cursor_periodo()) {
				$datos = $this->cn()->entidad()->get_datos_per_inscr_fecha();
				if (isset($datos['tipo']) && $datos['tipo']) {
					if ((isset($this->nivel['para_todo']) && $this->nivel['para_todo']) || $this->cn()->entidad()->es_para_institucion_gestionante()) {
						$datos['entidad_tipo_nuevo'] = g3entidad::nivel_aplicacion_tipo_total.','.$datos['entidad_tipo'];
						$this->nivel['entidad_tipo'] = g3entidad::nivel_aplicacion_tipo_total;
						$this->nivel['para_todo'] = true;
					} else {
						$datos['entidad_tipo_nuevo'] = $datos['tipo'].','.$datos['entidad_tipo'];
					}
				}
			} else {
				$datos['habilitado'] = comunes::si;//Si el Período es nuevo entonces por defecto está habilitado
			}
			$form->set_datos($datos);
		}
		//Si estoy dando de alta un nuevo Período oculto el evento 'eliminar' a nivel de solapa
	 	if (!$this->cn()->entidad()->hay_cursor_periodo()) {
			$this->evento('eliminar')->ocultar();
		}
		//Desactivo los eventos que correspondan, a nivel de operación
		$this->controlador->controlador()->evento('cancelar')->desactivar();
		if (!$this->cn()->entidad_es_nueva()) { 
			$this->controlador->controlador()->evento('eliminar')->desactivar();
		}
		$this->controlador->controlador()->evento('guardar')->desactivar();
	}

	function evt__form_conf_periodo__modificacion($datos)
	{
		$this->datos_temp['form_conf'] = $datos;
	}
	
	//---- form_ml_subtipo -----------------------------------------------------------------
	
	function conf__form_ml_subtipo(toba_ei_formulario_ml $form)
	{
		if(isset($this->datos_temp['form_ml'])) {
			$this->controlador->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp['form_ml']);
		} else {
			if ($this->hay_tipo_definido()) {
				// El único tipo de entidad posible es "Propuestas". Los tipos de propuesta se obtienen de la tabla 'sga_propuestas_tipos' (filtrada con perfil de datos).
				$subtipos = guarani::co('co_propuestas')->get_propuestas_tipos_reporte();

				//Determino si fueron cargados como requeridos o no
				foreach ($subtipos as $fila => $subtipo) {
					if ($this->cn()->entidad()->es_subtipo_requerido($subtipos[$fila]['entidad_subtipo'])) {
						$subtipos[$fila]['requerido'] = '1';
					} else {
						$subtipos[$fila]['requerido'] = '0';
					}
				}
				$form->set_datos($subtipos);
			}
		}
		$form->ef('entidad_subtipo_nombre')->set_etiqueta('Tipos de '.$this->get_entidad_tipo_dato('tipo_nombre')); //Seteo el nombre de la columna
	}

	function evt__form_ml_subtipo__modificacion($datos)
	{
	    $this->s__datos_ml = $datos;
	}
	
	//---- form_ml_entidad -----------------------------------------------------------------
	
	function conf__form_ml_entidad(toba_ei_formulario_ml $form)
	{
		if ($this->filtrando) {
			$this->filtrando = false;
			$this->controlador->controlador->set_hubo_cambios();
		}
		if(isset($this->datos_temp['form_ml'])) {
			$this->controlador->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp['form_ml']);
		} else {
			if($this->hay_tipo_definido()) {
				//Cambio la expresion de la columna del filtro
				$this->dep('filtro_entidades')->columna('nombre')->set_expresion($this->get_entidad_tipo_dato('tabla').'.nombre');
			}
			$this->s__filtro = $this->dep('filtro_entidades')->get_sql_where();
			if($this->hay_tipo_definido()) {
				//Levanto todas las entidades
				$entidades = guarani::co('co_g3entidades')->get_listado_tipo_detallado($this->get_entidad_tipo(), $this->s__filtro);

				//Determino si fueron cargadas como requeridas o no
				foreach ($entidades as $fila => $entidad) {
					if ($this->cn()->entidad()->es_entidad_requerida($entidades[$fila]['entidad'])) {
						$entidades[$fila]['requerido'] = '1';
					} else {
						$entidades[$fila]['requerido'] = '0';
					}
				}
				$form->set_datos($entidades);
			}
		}
		$form->ef('entidad_descripcion')->set_etiqueta($this->get_entidad_tipo_dato('tipo_nombre')); //Seteo el nombre de la columna
	}

	function evt__form_ml_entidad__modificacion($datos)
	{
	    $this->s__datos_ml = $datos;
	}
	
	//------ Filtro ----------------------

	function evt__filtro_entidades__filtrar($datos_filtro)
	{
		$this->filtrando = true;
		if (isset($datos_filtro)) {
			$this->s__datos_filtro = $datos_filtro;
			$this->s__filtro = $this->dep('filtro_entidades')->get_sql_where();
		} else {
			$this->evt__filtro__cancelar();
		}
	}

	function evt__filtro_entidades__cancelar()
	{
		unset($this->s__datos_filtro);
		unset($this->s__filtro);
	}

	function conf__filtro_entidades(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro)) {
			$filtro->set_datos($this->s__datos_filtro);
		}		
	}
	
	//---- Eventos ----------------------------------------------------------------------

	function evt__agregar()
	{
		$this->set_pantalla('edicion_periodo');
	}

	function evt__cancelar()
	{
		$this->cn()->entidad()->unset_cursor_periodo();
		$this->set_pantalla('seleccion_periodo');
		unset($this->s__nodo_seleccionado);
	}
	
	function evt__eliminar()
	{
		$this->cn()->entidad()->eliminar_fila_periodo($this->s__nodo_seleccionado);
		$this->cn()->entidad()->unset_cursor_periodo();
		$this->set_pantalla('seleccion_periodo');
		unset($this->s__nodo_seleccionado);
	}
	
	function evt__confirmar()
	{
		try {
			$datos_form_conf = $this->datos_temp['form_conf'];
			if (isset($this->s__tipo)) {
				$tipo_datos = explode(',', $this->s__tipo);
				$tipo = $tipo_datos[0];					//Guardo si es de tipo T, S ó E
				$entidad_tipo = $tipo_datos[1];			//Guardo la entidad
				$datos_form_conf['tipo'] = (($tipo == g3entidad::nivel_aplicacion_tipo_total) || 
											($tipo == g3entidad::nivel_aplicacion_tipo_entidad)) ?	g3entidad::nivel_aplicacion_tipo_entidad : 
																									g3entidad::nivel_aplicacion_tipo_subtipo;
				$datos_form_conf['entidad_tipo'] = $entidad_tipo;
				if (!$this->cn()->entidad()->hay_cursor_periodo()) {	//Si estoy dando de alta un nuevo Período dentro del árbol
					$datos_periodo = $this->cn()->entidad()->get_datos();
					$datos_form_conf['periodo_inscripcion'] = isset($datos_periodo['periodo_inscripcion']) ? $datos_periodo['periodo_inscripcion'] : null;
					$datos_form_conf['apex_ei_analisis_fila'] = 'A';
					$datos_form_conf['x_dbr_clave'] = 0;
					//Creo el nodo padre y seteo el cursor en él
					$id_interno = $this->cn()->entidad()->nueva_fila_per_inscr_fechas($datos_form_conf, $this->s__datos_ml, $tipo);
					$this->cn()->entidad()->set_cursor_periodo($id_interno);
				} else {												//Si estoy editando un Período del árbol
					//Actualizo los datos en el nodo seleccionado
					$datos_form_conf['apex_ei_analisis_fila'] = 'M';
					$datos_periodo[$this->s__nodo_seleccionado] = $datos_form_conf;					
					$this->cn()->entidad()->set_datos_per_inscr_fechas($datos_periodo, $this->s__datos_ml, $tipo);
				}
				switch ($tipo) {
					case g3entidad::nivel_aplicacion_tipo_subtipo:
						$this->cn()->entidad()->set_datos_subtipos($this->s__datos_ml);	 //Agrego los subtipos seleccionados
						break;
					case g3entidad::nivel_aplicacion_tipo_entidad:
						$this->cn()->entidad()->set_datos_entidades($this->s__datos_ml); //Agrego las entidades seleccionadas
						break;
					default:
						$this->cn()->entidad()->set_datos_entidad_para_todo();			 //Agrego la entidad que representa a la institución
				}
				$this->cn()->entidad()->unset_cursor_periodo();
				$this->set_pantalla('seleccion_periodo');
			}
		} catch ( guarani_error $e ) {
			$this->datos_temp['form_ml'] = $this->s__datos_ml;
			$this->datos_temp['form_conf'] = $datos_form_conf;
			throw new guarani_error($e->getMessage());
		}
	}

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		
		if ($this->pantalla('edicion_periodo')->dependencia_cargada('filtro_entidades')) {	//Si estan cargadas las entidades entonces
																							//defino el evento para el filtrar
			$msj_confirm_consulta = "Al filtrar y realizar una nueva configuración se perderán los cambios anteriores. ¿Desea continuar?";
		    $id_js_filtro = $this->dependencia('filtro_entidades')->get_id_objeto_js();
		    $id_js_form_entidades = $this->dependencia('form_ml_entidad')->get_id_objeto_js();
			
	        echo "
	        
	        //---- Eventos ---------------------------------------------
	        
			{$id_js_filtro}.evt__filtrar = function() {
				var filas = {$id_js_form_entidades}.filas();
				var confirmar_filtrar = {$id_js_form_entidades}.hay_cambios();
				if (confirmar_filtrar) {
					return confirmar_filtrado();
				}
				
				for (id_fila in filas) {
					var id_ef = {$id_js_form_entidades}.ef('requerido').ir_a_fila(filas[id_fila]);
					if (id_ef.chequeado()) {
						confirmar_filtrar = true;
						break;
					}
				}
				
				if (confirmar_filtrar) {
					return confirmar_filtrado();
				}
			}
			
			function confirmar_filtrado(proyecto, operacion, url, es_popup) {
				if (!es_popup || typeof(es_popup) == 'undefined') {
					resp = confirm('$msj_confirm_consulta');
					if (resp) {
						return true;
					}
					return false;
				}
				return true;
			}
			";
		}
	}

}