<?php
class ci_nav_emitir_constancia_cursos extends ci_nav_procesos_bk_sin_listado_inicial
{
	protected $s__propuesta;
	protected $s__selecion_alumnos;
	protected $s__constancia;
	protected $s__datos_filtros_alumno;
	protected $s__filtro_alumno;
	protected $ev_info;

	function set_nombre_proceso()
	{
		$this->s__nombre_proceso = 'pro_emitir_constancia_cursos_nucleo';
	}

	function set_sin_listado_inicial()
	{
		$this->con_listado_inicial = false;
		$this->dep('filtro')->set_sin_filtrado_inicial();
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_td_etiqueta_width('140px');
			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_columnas_a_mostrar(array(
				'actividad_nombre',
				'fecha_inicio_dictado_visual',
				'fecha_fin_dictado_visual',
				'horario_descr',
				'ubicacion_nombre'
			));

			$this->ev_info->set_etiqueta('actividad_nombre', 'Curso');
			$this->ev_info->set_etiqueta('fecha_inicio_dictado_visual', 'Inicio de Clases');
			$this->ev_info->set_etiqueta('fecha_fin_dictado_visual', 'Finalización de Clases');
			$this->ev_info->set_etiqueta('horario_descr', 'Horario');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info;
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA: Seleccion de Cursos
	//--------------------------------------------------------------------------
	
	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		if (!$this->cn()->hay_propuesta_seteada()) {
			$this->cn()->resetear_atributos_parametros();
		}
	}
	
	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$indx_msg_eof = '800_eof_cuadro';
		$filtro = $this->get_filtro('filtro');

		if (isset($filtro)) {
			$indx_msg_eof .= '_filtrado';
			$datos = $this->cn()->get_listado($filtro);
			$cuadro->set_datos($datos);
		} else {
			$indx_msg_eof .= '_sinfiltro';
		}

		$mensaje = guarani_mensajes::get($indx_msg_eof, array('Cursos'));
		$cuadro->set_eof_mensaje($mensaje);
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->cn()->set_comision($this->s__seleccion['comision']);
		$this->set_pantalla('pant_alumnos');
	}

	//--------------------------------------------------------------------------
	//---- filtro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro', false);
		$filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
		$filtro->columna('dia_semana')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		$nombre_filtro = 'filtro';

		$propuesta = $datos['propuesta']['valor'];
		$this->cn()->set_propuesta($propuesta);
		$this->dep($nombre_filtro)->columna('propuesta')->resetear_estado();
		
		$this->cn()->cargar_atributos_parametros($propuesta);

		if (!empty($datos['dia_semana'])) {
			$dia_semana = toba::db()->quote($datos['dia_semana']['valor']);
			$this->dep($nombre_filtro)->columna('dia_semana')->resetear_estado();
		}

		if (isset($datos) && !empty($datos)) {
			$this->s__datos_filtros[$nombre_filtro] = $datos;
			$this->s__filtros[$nombre_filtro] = $this->dep($nombre_filtro)->get_sql_where();
			if (!empty($dia_semana)) {
				$this->s__filtros[$nombre_filtro] .= "
					AND $dia_semana IN (
						SELECT 	sga_asignaciones.dia_semana
						FROM 	sga_comisiones_bh,
								sga_asignaciones
						WHERE 	sga_comisiones_bh.comision = sga_comisiones.comision
								AND sga_asignaciones.asignacion = sga_comisiones_bh.asignacion
					)
						";
			}
		} else {
			$this->filtro_limpiar($nombre_filtro);
			$this->s__filtros[$nombre_filtro] = '';
		}
	}

	function evt__filtro__cancelar()
	{
		$this->filtro_limpiar('filtro');
		$this->cn()->resetear_propuesta();
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA: Alumnos (pant_alumnos)
	//--------------------------------------------------------------------------

	function conf__pant_alumnos(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->cn()->get_datos_comision());
		if (empty($this->s__constancia)) {
			$pantalla->eliminar_dep('filtro_alumnos');
			$pantalla->eliminar_dep('cuadro_alumnos');
			$pantalla->eliminar_evento('procesar');
		}
	}

	//--------------------------------------------------------------------------
	//---- Formulario Constancias
	//--------------------------------------------------------------------------

	function conf__form_constancia()
	{
		if (isset($this->s__constancia)) {
			return $this->s__constancia;
		}
	}

	function evt__form_constancia__modificacion($datos)
	{
		// Verificar si exige aprobar y obtener otros datos
		if (!empty($datos['constancia'])) {
			$this->s__constancia = $this->cn()->get_datos_constancia($datos['constancia']);
		} else {
			unset($this->s__constancia);
		}
		$this->evt__filtro_alumnos__cancelar();
	}

	//--------------------------------------------------------------------------
	//---- Filtro Alumnos
	//--------------------------------------------------------------------------

	function conf__filtro_alumnos(guarani_pers_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_alumnos', false);
		if ($this->s__constancia['exige_aprobacion'] == comunes::no) {
			$filtro->eliminar_columnas(array('resultado'));
		}
	}

	function evt__filtro_alumnos__filtrar($datos)
	{
		$nombre_filtro = 'filtro_alumnos';
		$this->s__datos_filtros[$nombre_filtro] = $datos;
		$this->s__filtros[$nombre_filtro] = $this->dep($nombre_filtro)->get_sql_where();
	}

	function evt__filtro_alumnos__cancelar()
	{
		$this->filtro_limpiar('filtro_alumnos');
	}

	//--------------------------------------------------------------------------
	//---- Cuadro Alumnos
	//--------------------------------------------------------------------------

	function evt__cuadro_alumnos__seleccion($datos)
	{
		$this->s__selecion_alumnos = $datos;
	}

	function conf__cuadro_alumnos(toba_ei_cuadro $cuadro)
	{
		if (!empty($this->s__constancia)) {
			$filtro = '';
			$filtro .= $this->get_filtro('filtro_alumnos');

			$indx_msg_eof = '800_eof_cuadro_filtrado';
			$mensaje = guarani_mensajes::get($indx_msg_eof, array('Alumnos'));
			$cuadro->set_eof_mensaje($mensaje);
			
			// Sólo listar los alumnos que aprobaron.
			$exige_aprobacion = $this->s__constancia['exige_aprobacion'] == comunes::si;
			$datos = $this->cn()->get_listado_alumnos($exige_aprobacion, $filtro);
			$cuadro->set_datos($datos);

			if (!$exige_aprobacion) {
				$cuadro->eliminar_columnas(array('fecha', 'nota', 'resultado_descr'));
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- Eventos del CI
	//--------------------------------------------------------------------------

	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_estado_proceso();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}

	function ajax__resultados_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_resultado_proceso(pro_emitir_constancia_cursos_nucleo::reporte_resultados);
		$resultados = current($contenido['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']) {
			case 'rep_no_pasaron':
				$reporte = pro_emitir_constancia_cursos_nucleo::reporte_no_pasaron;
				$operacion = '14000003';
				$titulo = 'Emitir Constancia de Cursos - Constancias No Emitidas';
				break;
		}

		if ($this->modo_visualizacion()) {
			$datos = $this->get_resultado_reporte($reporte);
			$comision = $this->get_resultado_reporte(pro_emitir_constancia_cursos_nucleo::reporte_comision);
		} else {
			// Proceso para pedirle el log de resultados
			$datos = $this->cn()->get_resultado_proceso($reporte);
			$comision = $this->cn()->get_resultado_proceso(pro_emitir_constancia_cursos_nucleo::reporte_comision);
		}

		// Se almacenan los datos en la instancia de impresión para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array(
			'alumnos' => $datos['mensajes'],
			'reporte' => $reporte,
			'titulo' => $titulo
		));
		guarani_datos_impresion::instancia()->set_conf($comision['mensajes']);
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		// Vínculo para que abrir en popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}

	function evt__volver()
	{
		unset($this->s__seleccion);
		unset($this->s__selecion_alumnos);
		unset($this->s__constancia);
		$this->filtro_limpiar('filtro_alumnos');

		$this->cn()->resetear_comision();
		$this->set_pantalla('seleccion');
	}

	function evt__procesar()
	{
		if (guarani_arreglos::count($this->s__selecion_alumnos) == 0) {
			throw new toba_error('Debe seleccionar al menos a un alumno');
		}

		// id_persona del usuario logueado
		$persona = toba::consulta_php('co_personas')->get_persona_x_usuario(toba::usuario()->get_id());

		$alumnos = aplanar_matriz($this->s__selecion_alumnos, 'alumno');
		$parametros = array(
			'comision' => $this->cn()->get_datos_comision(),
			'operacion' => guarani::get_id_operacion(),
			'plan_version' => $this->cn()->get_plan_version(),
			'constancia' => $this->s__constancia,
			'alumnos' => $alumnos,
			'usuario' => $persona['persona']
		);
		$this->cn()->ejecutar_proceso($parametros);
		$this->set_pantalla('pant_procesar');
	}

	function evt__finalizar()
	{
		$this->limpiar_memoria();
		$this->cn()->limpiar_memoria();
		$this->filtro_limpiar('filtro');
		$this->set_pantalla('seleccion');
	}

}
?>