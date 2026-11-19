<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class ci_nav_reg_excep_masivas_a_req_ing extends ci_navegacion_wizard
{
	//Filtros
	protected $s__filtro_periodo;
	protected $s__filtro_requisito;
	protected $s__filtro_propuesta;
	protected $s__filtro_ubicacion;
	
	//Datos Filtros
	protected $s__datos_filtro_periodo;
	protected $s__datos_filtro_requisito;
	protected $s__datos_filtro_propuesta;
	protected $s__datos_filtro_ubicacion;
	
	//Datos Form
	protected $s__datos_confirmar;
	
	//Elemento Visual de Información
	protected $ev_info;
	
	//Proceso background
	protected $s__proceso;
	protected $mje_confirma_excepcion = '800MAT_exceptuar';
	
	//Aspirantes y Requisitos Aspirantes
	protected $s__requisitos_aspirante;
	protected $s__aspirantes;
	
	/*
	 * Se maneja el avance de pantalla.
	 */
	function evt__cambiar_tab__siguiente()
	{
		parent::evt__cambiar_tab__siguiente();
		
		//Si clickeó el boton 'Exceptuar' entonces registro las excepciones masivas
		if ($this->s__pantalla_actual == 'confirmar') {
			
			//Armo el array con los parámetros a mandar en la creación del proceso
			$parametros = array_merge(	array('requisito' 			 => $this->get_seleccion_valor('requisito')),
										array('requisitos_aspirante' => $this->s__requisitos_aspirante),
										array('parametros_tabla'	 => $this->s__datos_confirmar));
			
			//Ejecuto el proceso background
			$this->cn()->ejecutar_proceso($parametros);
		}
	}
	
	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_estado_proceso();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}
	
	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		//Datos para la instancia
		$conf 	= array('requisito' => $this->get_seleccion(array('nombre_requisito')));
		$datos 	= $this->get_datos_reporte();
		$titulo = 'Reporte de Excepciones Registradas a Requisitos de Ingreso';
		
		//Almaceno los datos en la instancia de impresion para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_titulo($titulo);
		guarani_datos_impresion::instancia()->set_datos($datos);
		guarani_datos_impresion::instancia()->set_conf($conf);
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		//Creo el vinculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '38000099', null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}
	
	function get_datos_reporte()
    {
        //Obtengo los valores
        $requisito = $this->get_seleccion_valor('requisito');
        $aspirantes = $this->s__aspirantes;
        
        //Se prepara el filtro para la consulta
        $filtro  = 'sga_requisitos_aspirante.requisito = '.$requisito.' AND ';
        $filtro .= guarani_sql::armar_condicion_in($aspirantes, 'sga_requisitos_aspirante', 'propuesta_aspira');
        
        //Retorno el array con las columnas para el reporte
        return toba::consulta_php('co_personas')->get_datos_cuadro_req_aspirante($filtro);
    }
    
	//-----------------------------------------------------------------------------------
	//---- SELECCION PERIODO ------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- filtro_periodos --------------------------------------------------------------
	
	function conf__filtro_periodos(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_periodo)) {
			$filtro->set_datos($this->s__datos_filtro_periodo);
		} else {
			$filtro->colapsar();
		}
	}

	function evt__filtro_periodos__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_periodo = $datos;
			$this->s__filtro_periodo = $this->dep('filtro_periodos')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_periodo);
			$this->s__filtro_periodo = '';
		}
	}

	function evt__filtro_periodos__cancelar()
	{
		unset($this->s__datos_filtro_periodo);
		$this->s__filtro_periodo = '';
	}
	
	//---- cuadro_periodos --------------------------------------------------------------
	
	function conf__cuadro_periodos(toba_ei_cuadro $cuadro)
	{
		if (!isset($this->s__filtro_periodo)) {
			$this->s__filtro_periodo = '';
		}
		$datos = toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_inscripcion_inscriptos($this->s__filtro_periodo);
		$cuadro->set_datos($datos);
	}

	function conf_evt__cuadro_periodos__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_periodos')->get_datos();
		if ($datos[$fila]['cant_pendientes'] == 0) {
			$evento->anular();
		} else {
			$evento->set_check_activo($this->es_fila_seleccionada('cuadro_periodos', $fila));
		}
	}
	
	function evt__cuadro_periodos__seleccion($seleccion)
	{
		$formateo = new toba_formateo('html');
		$seleccion['fecha_cierre_requisitos_format'] = $formateo->formato_fecha($seleccion['fecha_cierre_requisitos']);
		$this->set_seleccion($seleccion);
	}
	
	//Validación de salida
	function validar_sel_periodo_insc()
	{
		//Valido que se haya seleccionado un Período de Inscripción
		if ($this->falta_seleccionar('periodo_insc')) {
			$mje = guarani_mensajes::get('800_no_seleccion_unica', array('un Período de Inscripción'));
			throw guarani::error($mje);
		}
	}
    
    //-----------------------------------------------------------------------------------
    //---- SELECCION REQUISITO ----------------------------------------------------------
    //-----------------------------------------------------------------------------------
    
    function conf__sel_requisito(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->get_seleccion());
	}
	
    //---- filtro_requisitos ------------------------------------------------------------
    
    function conf__filtro_requisitos(guarani_ei_filtro $filtro)
    {
        if (isset($this->s__datos_filtro_requisito)) {
            $filtro->set_datos($this->s__datos_filtro_requisito);
        } else {
            $filtro->colapsar();
        }
    }

    function evt__filtro_requisitos__filtrar($datos)
    {
        if (isset($datos)) {
            $this->s__datos_filtro_requisito = $datos;
            $this->s__filtro_requisito = $this->dep('filtro_requisitos')->get_sql_where();
        } else {
            unset($this->s__datos_filtro_requisito);
            $this->s__filtro_requisito = '';
        }
    }

    function evt__filtro_requisitos__cancelar()
    {
        unset($this->s__datos_filtro_requisito);
        $this->s__filtro_requisito = '';
    }
    
    //---- cuadro_requisitos ------------------------------------------------------------
    
    function conf__cuadro_requisitos(toba_ei_cuadro $cuadro)
    {
        $periodo    = $this->get_seleccion_valor('periodo_insc');
        $filtro     = guarani_sql::armar_condicion_in($periodo, 'sga_propuestas_aspira', 'periodo_insc');
        $filtro    .= ($this->s__filtro_requisito != '') ? ' AND '.$this->s__filtro_requisito : '';
        $datos = toba::consulta_php('co_requisitos')->get_requisitos_con_inscripcion_pendiente($filtro);
        $cuadro->set_datos($datos);
    }

    function conf_evt__cuadro_requisitos__seleccion(toba_evento_usuario $evento, $fila)
    {
        $evento->set_check_activo($this->es_fila_seleccionada('cuadro_requisitos', $fila));
    }
    
    function evt__cuadro_requisitos__seleccion($seleccion)
    {
		$this->set_seleccion($seleccion);
    }
    
    //Validación de salida
    function validar_sel_requisito()
    {
        //Valido que se haya seleccionado un Requisito
        if ($this->falta_seleccionar('requisito')) {
            $mje = guarani_mensajes::get('800_no_seleccion_unica', array('un Requisito'));
            throw guarani::error($mje);
        }
        //Limpio el filtro del Requisito junto con sus datos
        unset($this->s__filtro_requisito);
        unset($this->s__datos_filtro_requisito);
    }
    
    //-----------------------------------------------------------------------------------
    //---- SELECCION PROPUESTAS ---------------------------------------------------------
    //-----------------------------------------------------------------------------------
    
    function conf__sel_propuestas(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->get_seleccion());
	}
	
    //---- filtro_propuestas ------------------------------------------------------------
    
    function conf__filtro_propuestas(guarani_ei_filtro $filtro)
    {
        if (isset($this->s__datos_filtro_propuesta)) {
            $filtro->set_datos($this->s__datos_filtro_propuesta);
        } else {
            $filtro->colapsar();
        }
    }

    function evt__filtro_propuestas__filtrar($datos)
    {
        if (isset($datos)) {
            $this->s__datos_filtro_propuesta = $datos;
            $this->s__filtro_propuesta = $this->dep('filtro_propuestas')->get_sql_where();
        } else {
            unset($this->s__datos_filtro_propuesta);
            $this->s__filtro_propuesta = '';
        }
    }

    function evt__filtro_propuestas__cancelar()
    {
        unset($this->s__datos_filtro_propuesta);
        $this->s__filtro_propuesta = '';
    }
    
    //---- cuadro_propuestas ------------------------------------------------------------
    
    function conf__cuadro_propuestas(toba_ei_cuadro $cuadro)
    {
        $requisito 	= $this->get_seleccion_valor('requisito');
        $filtro 	= $this->s__filtro_propuesta;
        $datos 		= toba::consulta_php('co_propuestas')->get_propuestas_aspira_pendientes_x_req($requisito, $filtro);
        $cuadro->set_datos($datos);
    }

    function conf_evt__cuadro_propuestas__seleccion(toba_evento_usuario $evento, $fila)
    {
        $evento->set_check_activo($this->es_fila_seleccionada('cuadro_propuestas', $fila));
    }
    
    function evt__cuadro_propuestas__seleccion($seleccion)
    {
        if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
    }
    
    //Validación de salida
    function validar_sel_propuestas()
    {
        //Valido que se haya seleccionado alguna Propuesta
        if ($this->falta_seleccionar('propuesta')) {
            $mje = guarani_mensajes::get('800_no_seleccion', array('una Propuesta'));
            throw guarani::error($mje);
        }
        //Limpio el filtro de Propuestas junto con sus datos
        unset($this->s__filtro_propuesta);
        unset($this->s__datos_filtro_propuesta);
    }
    
    //-----------------------------------------------------------------------------------
    //---- SELECCION UBICACIONES --------------------------------------------------------
    //-----------------------------------------------------------------------------------
    
    function conf__sel_ubicaciones(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->get_seleccion());
	}
	
    //---- filtro_ubicaciones -----------------------------------------------------------
    
    function conf__filtro_ubicaciones(guarani_ei_filtro $filtro)
    {
        if (isset($this->s__datos_filtro_ubicacion)) {
            $filtro->set_datos($this->s__datos_filtro_ubicacion);
        } else {
            $filtro->colapsar();
        }
    }

    function evt__filtro_ubicaciones__filtrar($datos)
    {
        if (isset($datos)) {
            $this->s__datos_filtro_ubicacion = $datos;
            $this->s__filtro_ubicacion = $this->dep('filtro_ubicaciones')->get_sql_where();
        } else {
            unset($this->s__datos_filtro_ubicacion);
            $this->s__filtro_ubicacion = '';
        }
    }

    function evt__filtro_ubicaciones__cancelar()
    {
        unset($this->s__datos_filtro_ubicacion);
        $this->s__filtro_ubicacion = '';
    }
    
    //---- cuadro_ubicaciones -----------------------------------------------------------
    
    function conf__cuadro_ubicaciones(toba_ei_cuadro $cuadro)
    {
        //Armo el filtro para la consulta de las Ubicaciones
        $propuestas = $this->get_seleccion_valor('propuesta');
        $filtro     = guarani_sql::armar_condicion_in($propuestas, 'sga_propuestas_aspira', 'propuesta');
        $filtro    .= ($this->s__filtro_ubicacion != '') ? ' AND '.$this->s__filtro_ubicacion : '';
        
        //Se obtienen los datos y se los setea en el cuadro
        $datos = toba::consulta_php('co_ubicaciones')->get_ubicaciones_x_prop_plan_version($filtro);
        $cuadro->set_datos($datos);
    }

    function conf_evt__cuadro_ubicaciones__seleccion(toba_evento_usuario $evento, $fila)
    {
    	$evento->set_check_activo($this->es_fila_seleccionada('cuadro_ubicaciones', $fila));
    }
    
    function evt__cuadro_ubicaciones__seleccion($seleccion)
    {
        if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
    }
    
    //Validación de salida
    function validar_sel_ubicaciones()
    {
        //Valido que se haya seleccionado alguna Ubicacion
        if ($this->falta_seleccionar('ubicacion')) {
            $mje = guarani_mensajes::get('800_no_seleccion', array('una Ubicación'));
            throw guarani::error($mje);
        }
        
        //Cargo los Aspirantes
        $this->s__aspirantes = $this->get_aspirantes();
        $requisito  = $this->get_seleccion_valor('requisito');
        
        //Valido las Inscripciones
        $this->cn()->validar_inscripciones($this->s__aspirantes);
        
        //Obtengo los requisitos aspirante
        $filtro  = 'sga_requisitos_aspirante.requisito = '.$requisito.' AND ';
		$filtro .= guarani_sql::armar_condicion_in($this->s__aspirantes, 'sga_requisitos_aspirante', 'propuesta_aspira');
        $this->s__requisitos_aspirante = toba::consulta_php('co_requisitos')->get_requisitos_aspirante($filtro);
        
        //Limpio el filtro de Ubicaciones junto con sus datos
        unset($this->s__filtro_ubicacion);
        unset($this->s__datos_filtro_ubicacion);
    }
    
    //---- Carga de Aspirantes ---------------------------------------------------------------
    
    function get_aspirantes()
    {
        //Selecciones previas
        $periodo_sel = $this->get_seleccion_valor('periodo_insc');
        $requisito_sel = $this->get_seleccion_valor('requisito');
        $propuestas_sel = $this->get_seleccion_valor('propuesta');
        $ubicaciones_sel = $this->get_seleccion_valor('ubicacion');
        
        //Armo las condiciones para los IN de la Propuesta y Ubicación
        $in_propuestas	= guarani_sql::armar_condicion_in($propuestas_sel, 'sga_propuestas_aspira', 'propuesta');
        $in_ubicaciones = guarani_sql::armar_condicion_in($ubicaciones_sel, 'sga_propuestas_aspira', 'ubicacion');
        
        //Armo el filtro para la consulta de las inscripciones
        $filtro  = 'sga_propuestas_aspira.periodo_insc = '.$periodo_sel.' AND ';
        $filtro .= 'sga_requisitos_aspirante.requisito = '.$requisito_sel.' AND ';
        $filtro .= $in_propuestas.' AND ';
        $filtro .= $in_ubicaciones;
        
        //Se obtienen los datos y se los retorna
        $aspirantes = toba::consulta_php('co_alumnos')->get_alumnos_x_prop_aspira_pendientes($filtro);
        $this->set_seleccion(array('cant_excepciones' => guarani_arreglos::count($aspirantes)));
        return $aspirantes;
    }
    
    //-----------------------------------------------------------------------------------
    //---- CONFIRMAR --------------------------------------------------------------------
    //-----------------------------------------------------------------------------------
    
    function conf__confirmar(toba_ei_pantalla $pantalla)
    {
    	$this->ev_info()->set_datos($this->get_seleccion());
		
        //Seteo del mensaje de confirmación de la excepción y etiqueta del botón
		$mensaje = guarani_mensajes::get($this->mje_confirma_excepcion);
		$this->evento('cambiar_tab__siguiente')->set_msg_confirmacion($mensaje);
        $this->evento('cambiar_tab__siguiente')->set_etiqueta('Exceptuar');
        $this->evento('cambiar_tab__siguiente')->set_imagen('/aplicar.png');
    }
    
    //---- form_confirmar ---------------------------------------------------------------
    
    function conf__form_confirmar(toba_ei_formulario $form)
    {
        if(isset($this->s__datos_confirmar)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->s__datos_confirmar);		
		} else {
			$form->set_datos(array('fecha_excepcion' => guarani_fecha::get_hoy()));
		}
    }

    function evt__form_confirmar__modificacion($datos)
    {
    	$this->s__datos_confirmar = $datos;
    	$this->cn()->validar_datos($datos);
    }
    
    //---- Configuraciones --------------------------------------------------------------
    
    function evt__confirmar__salida()
    {
        if ($this->get_id_pantalla() == 'finalizar') {
            
        	//Seteo la descripción del Documento Respaldatorio
            if (isset($this->s__datos_confirmar['documento_resp'])) {
                $doc         = $this->s__datos_confirmar['documento_resp'];
                $doc_desc     = toba::consulta_php('co_documentos')->get_documento($doc);
                $this->s__datos_confirmar['documento_resp_desc'] = $doc_desc;
            }
            
            //Formateo la fecha
            $formateo         = new toba_formateo('html');
            $fecha_format     = $formateo->formato_fecha($this->s__datos_confirmar['fecha_excepcion']);
            $this->s__datos_confirmar['fecha_excepcion'] = $fecha_format;
            
            //Seteo el Motivo de Excepción
            $motivo         = $this->s__datos_confirmar['motivo_excepcion'];
            $motivo_desc     = toba::consulta_php('co_motivos_de_excepcion_de_requisitos')->get_motivos_excepcion_requisitos($motivo);
            $this->s__datos_confirmar['motivo_excepcion_desc'] = $motivo_desc[0]['descr'];
        }
    }
    
    //-----------------------------------------------------------------------------------
    //---- FINALIZAR --------------------------------------------------------------------
    //-----------------------------------------------------------------------------------
    
    function conf__finalizar(toba_ei_pantalla $pantalla)
    {
    	$this->ev_info()->set_datos($this->get_seleccion());
		
        //Oculto el boton Anterior
        $this->evento('cambiar_tab__anterior')->ocultar();
        
        //Se desactiva el reciclado de memoria
        toba::memoria()->desactivar_reciclado();
    }
    
    //---- form_finalizar ---------------------------------------------------------------
    
    function conf__form_finalizar(toba_ei_formulario $form)
    {
        $form->set_datos($this->s__datos_confirmar);
        $form->set_solo_lectura();
    }

    //---- Eventos ----------------------------------------------------------------------
    
    function evt__finalizar()
    {
        //Limpio la memoria y redirijo de pantalla
        unset($this->s__datos_confirmar);
        $this->eliminar_seleccion_completa();
        $this->set_pantalla('sel_periodo_insc');
    }
    
    //-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('width: 100%;');
			$this->ev_info->set_max_datos_por_columna(10);
			$this->ev_info->set_max_chars_por_columna(130);
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'nombre', 'fecha_cierre_requisitos_format', 'nombre_requisito', 'propuesta_nombre', 'ubicacion_nombre', 'cant_excepciones'));
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre', 'Período de Inscripción');
			$this->ev_info->set_etiqueta('fecha_cierre_requisitos_format', 'Cierre de Requisitos');
			$this->ev_info->set_etiqueta('nombre_requisito', 'Requisito');
			$this->ev_info->set_etiqueta('propuesta_nombre', 'Propuestas');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicaciones');
			$this->ev_info->set_etiqueta('cant_excepciones', 'Cantidad de alumnos');
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Eventos ---------------------------------------------
		
		{$this->objeto_js}.evt__reporte = function()
		{
			var params = [];
			this.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}
		
		{$this->objeto_js}.respuesta_reporte = function(respuesta)
		{
			abrir_popup('reporte',respuesta);
		}
		
		";
	}

}
?>