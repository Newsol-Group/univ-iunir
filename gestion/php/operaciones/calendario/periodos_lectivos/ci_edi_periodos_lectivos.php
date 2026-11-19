<?php
class ci_edi_periodos_lectivos extends ci_edi_datos_periodos_lectivos
{
	protected $s__tipo;
	protected $nivel;
	protected $raiz_a_reusar;

	protected $s__datos_filtro;
	protected $s__filtro;
	protected $filtrando = false;

	protected $datos_periodo;
	protected $datos_subtipos;
	protected $datos_entidades;
	protected $datos_temp_subtipos;
	protected $datos_temp_entidades;

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION PERIODO ---------------------------------------------------
	//-----------------------------------------------------------------------------------

	//---- arbol ------------------------------------------------------------------------
	
	function conf__arbol(toba_ei_arbol $arbol)
	{
		$this->construir_arbol_por_nivel($arbol);
	}
	
	function construir_arbol_por_nivel($arbol)
	{
		$raices 	= array();
		$formateo 	= new toba_formateo('html');
		$per_inscr 	= $this->cn()->entidad()->get_datos_per_inscr();

		foreach ($per_inscr as $valor) {
			$clave = $valor[apex_datos_clave_fila];

			$this->cn()->entidad()->seleccionar_per_inscr($clave);

			$valor = array_merge($valor, $this->cn()->entidad()->get_datos_per_inscr_fechas());

			// Se formatean las fechas de inicio y fin a visualizar: se las pasa al formato dd/mm/aaaa y se les quita la hora y zona horaria.
			$fecha_ini = new DateTime($valor['fecha_inicio']);
			$fecha_fin = new DateTime($valor['fecha_fin']);

			$fecha_ini_format = $formateo->formato_fecha($fecha_ini->format('Y-m-d'));
			$fecha_fin_format = $formateo->formato_fecha($fecha_fin->format('Y-m-d'));

			$etiqueta_hijo = 'Inicio: '.$fecha_ini_format.' - Fin: '.$fecha_fin_format;
			switch ($valor['tipo']) {
				case g3entidad::nivel_aplicacion_tipo_subtipo:
					$subtipos = $this->cn()->entidad()->get_datos_per_inscr_subtipos();
					foreach ($subtipos as $clave_subtipo => $valor_subtipo) {
						if (!$this->tiene_raiz(g3entidad::nivel_aplicacion_tipo_subtipo, $valor_subtipo, $raices)) {
							$subtipo = toba::consulta_php('co_g3entidades')->get_datos_subtipo($valor_subtipo['entidad_subtipo']);
							$clave_padre = 'S_'.$valor_subtipo['entidad_subtipo'];
							$padre = new nodo_arbol_periodos_por_nivel($subtipo['nombre'], null, $clave_padre);
							$padre->set_arbol($arbol);
							$raices[] = $padre;
						} else {
							$padre = $raices[$this->raiz_a_reusar];
							$clave_padre = 'S_'.$valor_subtipo['entidad_subtipo'];
						}
						$this->agregar_nodo_hijo($clave, $etiqueta_hijo, $padre, $clave_padre, $arbol);
					}
					break;
				case g3entidad::nivel_aplicacion_tipo_entidad:
					$entidades = $this->cn()->entidad()->get_datos_per_inscr_entidades();
					foreach ($entidades as $clave_entidad => $valor_entidad) {
						if (!$this->tiene_raiz(g3entidad::nivel_aplicacion_tipo_entidad, $valor_entidad, $raices)) {
							$entidad = toba::consulta_php('co_g3entidades')->get_datos_entidad($valor_entidad['entidad']);
							$clave_padre = 'E_'.$valor_entidad['entidad'];
							$etiqueta_entidad = toba::consulta_php('co_periodos_lectivos')->get_descripcion_entidad($valor_entidad['entidad'], $valor['entidad_tipo']);
							$etiqueta_padre = ($valor['entidad_tipo'] == g3entidad::tipo_institucion ) ? 
											  $etiqueta_entidad['entidad_descripcion'].' (Para Toda la Institución)' : $etiqueta_entidad['entidad_descripcion'];
							$padre = new nodo_arbol_periodos_por_nivel($etiqueta_padre, null, $clave_padre);
							$padre->set_arbol($arbol);
							$raices[] = $padre;
						} else {
							$padre = $raices[$this->raiz_a_reusar];
							$clave_padre = 'E_'.$valor_entidad['entidad'];
						}
						$this->agregar_nodo_hijo($clave, $etiqueta_hijo, $padre, $clave_padre, $arbol);						
					}
					break;
			}
			$this->cn()->entidad()->deseleccionar_per_inscr();
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
	
	function agregar_nodo_hijo($clave, $etiqueta, $padre, $clave_padre, $arbol)
	{
		$hijo = new nodo_arbol_periodos_por_nivel($etiqueta, $padre, $clave_padre.'_'.$clave);
		$padre->agregar_hijo($hijo);
		$hijo->set_arbol($arbol);
	}
	
	//---- Eventos del arbol ------------------------------------------------------------

	function evt__editar_periodo($nodo)
	{
		$entidad_seleccionada = substr($nodo, strripos($nodo, '_') + 1);
		$this->cn()->entidad()->seleccionar_per_inscr($entidad_seleccionada);
		$this->set_pantalla('edicion_periodo');
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

		$ci_navegacion = $this->controlador()->controlador();
		$ci_navegacion->evento('cancelar')->desactivar();
		if ($ci_navegacion->pantalla('edicion')->existe_evento('eliminar')) {
			$ci_navegacion->evento('eliminar')->desactivar();
		}
		$ci_navegacion->evento('guardar')->desactivar();

		if ($this->cn()->entidad()->hay_per_inscr_seleccionado()) {
			$this->evento('alta')->ocultar();
			$this->evento('baja')->set_msg_confirmacion(guarani_mensajes::get('800CAL_confirma_eliminar_periodo'));
		} else {
			$this->evento('baja')->ocultar();
			$this->evento('modificacion')->ocultar();
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

	//---- form_conf_periodo ------------------------------------------------------------
	
	function conf__form_conf_periodo(toba_ei_formulario $form)
	{
		// Marcar los EFs obligatorios.
		$efs = array('nombre', 'modalidad', 'ubicacion', 'fecha_inicio', 'fecha_fin', 'fecha_tope_bajas', 'habilitado');
		foreach ($efs as $ef) {
			$form->ef($ef)->set_estilo_etiqueta('ei-form-etiq-oblig');		
			$etiqueta = $form->ef($ef)->get_etiqueta() . ' (*)';
			$form->ef($ef)->set_etiqueta($etiqueta);
		}

		// Valores por defecto.
		$datos_per = $this->cn()->entidad()->get_datos();
		$datos['nombre'] = $datos_per['nombre'];
		if (!$this->cn()->entidad()->hay_per_inscr_seleccionado() && toba::consulta_php('co_ubicaciones')->get_cantidad_ubicaciones() == 1) {
			$ubicaciones = guarani::co('co_ubicaciones')->get_listado();
			$datos['ubicacion'] = array($ubicaciones[0]['ubicacion']);
		}
		$datos['habilitado'] = comunes::si;
		$datos['habilitado_interfaz'] = comunes::interfaz_todas;
		$datos['tipo_alumno'] = periodo_de_inscripcion::tipo_alumno_todos;
		$datos['tipo_cursado'] = periodo_de_inscripcion::tipo_cursado_todos;
		$form->set_datos_defecto($datos);

		if (isset($this->datos_periodo)) {
			$datos = $this->datos_periodo;
		} elseif ($this->cn()->entidad()->hay_per_inscr_seleccionado()) {
			$datos_per_inscr = $this->cn()->entidad()->get_datos_per_inscr_seleccionado();
			$datos_per_inscr_fechas = $this->cn()->entidad()->get_datos_per_inscr_fechas();
			$datos = array_merge($datos_per_inscr, $datos_per_inscr_fechas);

			if (isset($datos['tipo']) && $datos['tipo']) {
				if ((isset($this->nivel['para_todo']) && $this->nivel['para_todo']) || $this->cn()->entidad()->es_para_institucion_gestionante()) {
					$datos['entidad_tipo_nuevo'] = g3entidad::nivel_aplicacion_tipo_total.','.$datos['entidad_tipo'];
					$this->nivel['entidad_tipo'] = g3entidad::nivel_aplicacion_tipo_total;
					$this->nivel['para_todo'] = true;
				} else {
					$datos['entidad_tipo_nuevo'] = $datos['tipo'].','.$datos['entidad_tipo'];
				}
			}

			// Se formatea la fecha y hora para el ef_editable_fecha_hora.
			if (!empty($datos['fecha_inicio'])) {
				$datos['fecha_inicio'] = guarani_fecha::separar_fecha_hora($datos['fecha_inicio']);					
			}
			if (!empty($datos['fecha_fin'])) {
				$datos['fecha_fin'] = guarani_fecha::separar_fecha_hora($datos['fecha_fin']);
			}
			if (!empty($datos['fecha_tope_bajas'])) {
				$datos['fecha_tope_bajas'] = guarani_fecha::separar_fecha_hora($datos['fecha_tope_bajas']);
			}

			// Se formatean modalidades y ubicaciones para el ef_multi_seleccion_check.
			$datos['modalidad'] = aplanar_matriz($this->cn()->entidad()->get_datos_per_inscr_modalidades(), 'modalidad');
			$datos['ubicacion'] = aplanar_matriz($this->cn()->entidad()->get_datos_per_inscr_ubicaciones(), 'ubicacion');
		}

		$form->set_datos($datos);
	}

	function evt__form_conf_periodo__modificacion($datos)
	{
		if ($datos['habilitado'] == comunes::no) {
			$datos['habilitado_interfaz'] = comunes::interfaz_todas;
			$datos['tipo_alumno'] = periodo_de_inscripcion::tipo_alumno_todos;
			$datos['tipo_cursado'] = periodo_de_inscripcion::tipo_cursado_todos;
		}
		$this->datos_periodo = $datos;
	}
	
	//---- form_ml_subtipo --------------------------------------------------------------
	
	function conf__form_ml_subtipo(toba_ei_formulario_ml $form)
	{
		if (isset($this->datos_temp_subtipos)) {
			$form->set_datos($this->datos_temp_subtipos);
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
	    $this->datos_subtipos = $datos;
	}
	
	//---- form_ml_entidad --------------------------------------------------------------
	
	function conf__form_ml_entidad(toba_ei_formulario_ml $form)
	{
		if ($this->filtrando) {
			$this->filtrando = false;
			$this->controlador->controlador->set_hubo_cambios();
		}
		if (isset($this->datos_temp_entidades)) {
			$form->set_datos($this->datos_temp_entidades);
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
	    $this->datos_entidades = $datos;
	}
	
	//------ filtro_entidades -----------------------------------------------------------

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
		unset($this->s__tipo);

		unset($this->s__datos_filtro);
		unset($this->s__filtro);

		unset($this->datos_periodo);
		unset($this->datos_subtipos);
		unset($this->datos_entidades);

		$this->cn()->entidad()->deseleccionar_per_inscr();
		$this->set_pantalla('seleccion_periodo');
	}

	function evt__alta()
	{
		try {
			if (isset($this->s__tipo)) {
				$this->cn()->entidad()->nuevo_per_inscr($this->datos_periodo);

				$this->cn()->entidad()->set_datos_per_inscr_modalidades($this->datos_periodo['modalidad']);
				$this->cn()->entidad()->set_datos_per_inscr_ubicaciones($this->datos_periodo['ubicacion']);

				$this->set_datos_periodos_por_nivel();

				$this->evt__cancelar();
			}
		} catch (guarani_error $e) {
			$this->datos_temp_subtipos = $this->datos_subtipos;
			$this->datos_temp_entidades = $this->datos_entidades;
			throw $e;
		}
	}

	function evt__modificacion()
	{
		try {
			if (isset($this->s__tipo)) {
				$this->cn()->entidad()->set_datos_per_inscr($this->datos_periodo);
	
				$this->cn()->entidad()->set_datos_per_inscr_modalidades($this->datos_periodo['modalidad']);
				$this->cn()->entidad()->set_datos_per_inscr_ubicaciones($this->datos_periodo['ubicacion']);
	
				$this->set_datos_periodos_por_nivel();
	
				$this->evt__cancelar();
			}
		} catch (guarani_error $e) {
			$this->datos_temp_subtipos = $this->datos_subtipos;
			$this->datos_temp_entidades = $this->datos_entidades;
			throw $e;
		}
	}

	function set_datos_periodos_por_nivel()
	{
		$datos = $this->datos_periodo;

		$tipo_datos = explode(',', $this->s__tipo);
		$tipo = $tipo_datos[0];
		$entidad_tipo = $tipo_datos[1];

		if (($tipo == g3entidad::nivel_aplicacion_tipo_total) || ($tipo == g3entidad::nivel_aplicacion_tipo_entidad)) {
			$datos['tipo'] = g3entidad::nivel_aplicacion_tipo_entidad;
		} else {
			$datos['tipo'] = g3entidad::nivel_aplicacion_tipo_subtipo;
		}

		$datos['entidad_tipo'] = $entidad_tipo;

		// Se formatea la fecha y hora recibida en el formato del ef_editable_fecha_hora.
		if (isset($datos['fecha_inicio'])) {
			$datos['fecha_inicio'] = "{$datos['fecha_inicio'][0]} {$datos['fecha_inicio'][1]}";
		}
		if (isset($datos['fecha_fin'])) {
			$datos['fecha_fin'] = "{$datos['fecha_fin'][0]} {$datos['fecha_fin'][1]}";
		}
		if (isset($datos['fecha_tope_bajas'])) {
			$datos['fecha_tope_bajas'] = "{$datos['fecha_tope_bajas'][0]} {$datos['fecha_tope_bajas'][1]}";
		}

		$this->cn()->entidad()->set_datos_per_inscr_fechas($datos);

		switch ($tipo) {
			case g3entidad::nivel_aplicacion_tipo_subtipo:
				$this->cn()->entidad()->set_datos_subtipos($this->datos_subtipos); //Agrego los subtipos seleccionados
				break;
			case g3entidad::nivel_aplicacion_tipo_entidad:
				$this->cn()->entidad()->set_datos_entidades($this->datos_entidades); //Agrego las entidades seleccionadas
				break;
			default:
				$this->cn()->entidad()->set_datos_entidad_para_todo(); //Agrego la entidad que representa a la institución
		}
	}

	function evt__baja()
	{
		$this->cn()->entidad()->eliminar_per_inscr_seleccionado();
		$this->evt__cancelar();
	}

	//---- Javascript -------------------------------------------------------------------

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