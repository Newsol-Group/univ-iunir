<?php
/**
 * 
 * 
 * @package		Cursadas
 * @subpackage	Cursos
 * 
 */
class ci_nav_insc_cursos extends ci_navegacion_wizard
{
	protected $ev_info; 
	protected $ev_info_resumen; 
	
	protected $s__inscripcion_completa = false;
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_td_etiqueta_width('100px');
			
			$this->ev_info->set_columnas_a_mostrar(array('nombre_completo', 'documento'));
			$this->ev_info->set_max_columnas_por_fila(1);
			
			$this->ev_info->set_etiqueta('nombre_completo', 'Apellido y Nombres');
			$this->ev_info->set_etiqueta('documento', 'Identificación');
		}
		return $this->ev_info;
	}
	
	function ev_info_resumen()
	{
		
		if (!isset($this->ev_info_resumen)) {
			$this->ev_info_resumen = guarani::ev_info_datos();
			$this->ev_info_resumen->set_td_etiqueta_width('170px');
			
			$this->ev_info_resumen->set_columnas_a_mostrar(array('nombre_completo', 'actividad_comision', 'fecha_inicio_dictado_visual', 'horario_descr', 'ubicacion_nombre', 'costo'));
			$this->ev_info_resumen->set_max_columnas_por_fila(1);
			
			$this->ev_info_resumen->set_etiqueta('nombre_completo', 'Alumno');
			$this->ev_info_resumen->set_etiqueta('actividad_comision', 'Curso');
			$this->ev_info_resumen->set_etiqueta('fecha_inicio_dictado_visual', 'Inicio de clases');
			$this->ev_info_resumen->set_etiqueta('horario_descr', 'Horario');
			$this->ev_info_resumen->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info_resumen->set_etiqueta('costo', 'Costo');
		}
		return $this->ev_info_resumen;
	}
	
	function ini()
	{
		$persona = toba::memoria()->get_parametro('id_nuevo_registro');
		if (isset($persona)) {
			if ($persona == '0') { //El usuario presionó "Volver" en el ABM de Personas, no dio de alta la nueva persona.
				$this->set_pantalla('pant_persona');
			} else { //El usuario agregó una nueva persona: es el alumno que desea elegir.
				$this->set_seleccion(array('persona' => $persona), 'pant_persona');
				$this->validar_pant_persona();
				$this->set_pantalla('pant_curso');
			}
		}
	}
	
	//--------------------------------------------------------------------------
	//---- PANTALLA: Persona (pant_persona)
	//--------------------------------------------------------------------------
	
	function conf__pant_persona(toba_ei_pantalla $pantalla)
	{
		$this->dep('cuadro_persona')->evento('agregar_persona')->vinculo()->set_parametros(array('operacion_a_volver' => guarani::get_id_operacion_toba(), 'celda_a_restaurar' => toba::memoria()->get_celda_memoria_actual_id()));
		$this->dep('cuadro_persona')->evento('agregar_persona')->vinculo()->set_celda_memoria('celda_persona');
		$this->cn()->resetear_atributos_parametros();
	}
	
	function validar_pant_persona()
	{
		// Se valida que se haya seleccionado alguna Persona
		if ($this->falta_seleccionar('persona')) {
			$mje = "Debe seleccionar una persona para poder continuar";
			throw guarani::error($mje);
		}
		$persona = $this->get_seleccion_valor('persona');
		$this->cn()->set_persona($persona);
	}
	
	//--------------------------------------------------------------------------
	//---- filtro_persona ------------------------------------------------------
	//--------------------------------------------------------------------------
		
	function conf__filtro_persona(guarani_pers_ei_filtro $filtro)
	{
		$filtro->set_sin_filtrado_inicial();
		$this->filtro_configurar('filtro_persona', false);				
	}


	function evt__filtro_persona__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_persona', $datos);		
	}


	function evt__filtro_persona__cancelar()
	{
		$this->filtro_limpiar('filtro_persona');
	}

	//--------------------------------------------------------------------------
	//---- cuadro_persona ------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_persona(cuadro_seleccion_diferida $cuadro)
	{
		$indx_msg_eof = '800_eof_cuadro';
		$filtro = $this->get_filtro('filtro_persona');
		
		if (isset($filtro)) {			
			$indx_msg_eof .= '_filtrado';
			$datos = $this->cn()->get_personas($filtro);
			$cuadro->set_datos($datos);
		} else {
			$indx_msg_eof .= '_sinfiltro';
		}
		
		$mensaje = guarani_mensajes::get($indx_msg_eof, array('Personas'));
		$cuadro->set_eof_mensaje($mensaje);
	}

	function evt__cuadro_persona__seleccion($seleccion)
	{		
		$this->set_seleccion($seleccion);
	}
	
	function conf_evt__cuadro_persona__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_persona', $fila));		
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA: Curso (pant_curso)
	//--------------------------------------------------------------------------
	
	function conf__pant_curso(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->cn()->get_datos_persona());
	}
	
	function validar_pant_curso()
	{
		// Se valida que se haya seleccionado algún Curso
		if ($this->falta_seleccionar('comision')) {
			$mje = "Debe seleccionar un curso para poder continuar";
			throw guarani::error($mje);
		}
		$comision = $this->get_seleccion_valor('comision');
		$this->cn()->set_comision($comision);
	}
	
	function evt__cambiar_tab__anterior()
	{
		if ($this->s__pantalla_actual == 'pant_curso') {
			$this->evt__filtro_curso__cancelar();
			$this->cn()->resetear_comision();
			$this->eliminar_seleccion_previa();
		}
		parent::evt__cambiar_tab__anterior();
	}
	
	//--------------------------------------------------------------------------
	//---- filtro_curso ------------------------------------------------------
	//--------------------------------------------------------------------------
		
	function conf__filtro_curso(guarani_pers_ei_filtro $filtro)
	{
		$filtro->set_sin_filtrado_inicial();
		$this->filtro_configurar('filtro_curso', false);
		$filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
		$filtro->columna('dia_semana')->set_condicion_fija('es_igual_a');
	}


	function evt__filtro_curso__filtrar($datos)
	{
		$nombre_filtro = 'filtro_curso';
		
		$propuesta = $datos['propuesta']['valor'];
		$this->cn()->set_propuesta($propuesta);
		$this->dep($nombre_filtro)->columna('propuesta')->resetear_estado();
		
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
		
		$this->cn()->cargar_atributos_parametros(array($propuesta));
	}

	function evt__filtro_curso__cancelar()
	{
		$this->filtro_limpiar('filtro_curso');
		$this->cn()->resetear_propuesta();
		$this->cn()->resetear_atributos_parametros();
	}
	
	//--------------------------------------------------------------------------
	//---- cuadro_curso --------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_curso(toba_ei_cuadro $cuadro)
	{
		$indx_msg_eof = '800_eof_cuadro';
		$filtro = $this->get_filtro('filtro_curso');
		
		if (isset($filtro)) {			
			$datos = $this->cn()->get_cursos_disponibles($filtro);
			$cuadro->set_datos($datos);
		} else {
			$indx_msg_eof .= '_sinfiltro';
		}
		
		$mensaje = guarani_mensajes::get($indx_msg_eof, array('Cursos'));
		$cuadro->set_eof_mensaje($mensaje);
	}

	function evt__cuadro_curso__seleccion($seleccion)
	{
		$this->set_seleccion($seleccion);
	}

	function conf_evt__cuadro_curso__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_curso', $fila));
	}

	function conf_evt__cuadro_curso__ver_comision(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_curso')->get_datos();
		$datos_fila = $datos[$fila];
		foreach ($datos_fila as $dato => $valor) {
			$evento->vinculo()->agregar_parametro(utf8_encode($dato), utf8_encode($valor));
		}
	}
	
	//--------------------------------------------------------------------------
	//---- PANTALLA: Resumen (pant_resumen)
	//--------------------------------------------------------------------------

	function conf__pant_resumen(toba_ei_pantalla $pantalla)
	{
		$this->ev_info_resumen()->set_datos($this->cn()->get_datos_resumen());
		if ($this->inscripcion_completa()) {
			$pantalla->eliminar_evento('confirmar');
			$pantalla->eliminar_evento('cambiar_tab__anterior');
			if (!$this->cn()->es_cobrable()) {
				$pantalla->eliminar_evento('pagar');
			}
		} else {
			$pantalla->eliminar_evento('nueva_inscripcion');
			$pantalla->eliminar_evento('ver_comprobante');
			$pantalla->eliminar_evento('pagar');
		}
		
		//Si NO usa inscripción cerrada elimino el formulario
		if (!$this->cn()->es_inscripcion_cerrada()) {
			$pantalla->eliminar_dep('form_codigo');
		}

	}


	function evt__form_codigo__modificacion($datos)
	{
		$codigo_validacion_cant_digitos = comision::CODIGO_INSCRIPCION_CANT_DIGITOS;
		$codigo_validacion_regexp = comision::CODIGO_INSCRIPCION_REGEXP;
		$msj_codigo_validacion_solo_letras_numeros = guarani_mensajes::get('800_codigo_validacion_solo_letras_numeros');
		$msj_codigo_validacion_x_caracteres = guarani_mensajes::get('800_codigo_validacion_x_caracteres', array($codigo_validacion_cant_digitos));
		$msj_codigo_validacion_incorrecto = guarani_mensajes::get('800_codigo_validacion_incorrecto');

		$codigo_inscripcion_enviado = $datos['codigo_inscripcion'];
		$codigo_inscripcion_esperado = $this->cn()->get_codigo_inscripcion_cerrada();

		//Si el código de inscripción tiene la cantidad de caracteres correspondiente
		if (strlen($codigo_inscripcion_enviado) == $codigo_validacion_cant_digitos) {
			//Si el código de inscripción es alfanumerico
			if (preg_match("/$codigo_validacion_regexp/", $codigo_inscripcion_enviado)) {
				// Si el código de inscripción NO coincide
				// Si es incorrecto (si se desea insensible a mayúsculas y minúsculas usar strcasecmp)
				if (strcmp($codigo_inscripcion_enviado, $codigo_inscripcion_esperado) != 0) {
					throw new toba_error($msj_codigo_validacion_incorrecto);
				}

			}
			else {//Si el código de inscripción NO es alfanumerico
				throw new toba_error($msj_codigo_validacion_solo_letras_numeros);
			}

		}
		else {//Si el código de inscripción NO tiene la cantidad de caracteres correspondiente
			throw new toba_error($msj_codigo_validacion_x_caracteres);
		}
	}

	function evt__pant_resumen__salida()
	{
		$this->s__inscripcion_completa = false;
	}
	
	function inscripcion_completa()
	{
		return $this->s__inscripcion_completa;
	}

	//--------------------------------------------------------------------------
	//---- EVENTOS
	//--------------------------------------------------------------------------

	function evt__confirmar()
	{
		// Se procede a realizar la inscripción con los datos recolectados, y se
		// informa el resultado para mostrar los links si corresponde.
		$res = $this->cn()->inscribir();
		
		$mje = guarani_mensajes::get($res['msg_indice'], $res['pars']);
		if ($res['res'] < 1) {
			$this->s__inscripcion_completa = false;
			throw guarani::error($mje);
		} else {
			$this->s__inscripcion_completa = true;
			$this->preparar_datos_impresion();
			$mensaje = guarani_mensajes::get($res['msg_indice'], $res['pars']);
			toba::notificacion()->agregar($mensaje, 'info');
		}
	}

	function evt__nueva_inscripcion()
	{
		$this->cn()->resetear();
		$this->limpiar_seleccion();
		$this->s__inscripcion_completa = false;
		$this->filtro_limpiar('filtro_persona');
		$this->filtro_limpiar('filtro_curso');
		$this->set_pantalla('pant_persona');
	}
    
    function evt__pagar()
    {       
		//Cuando se selecciona pagar el sistema se redirige a sanaviron para 
		//que pueda hacer el correspondiente pago
        $token = $this->cn()->get_token();
        $url = guarani::parametros()->get_valor('sq_url_pagos') . $token;
        echo toba_js::abrir();
        echo "document.location.href='" .$url . "'\n";
        echo toba_js::cerrar();
    }
	
	//--------------------------------------------------------------------------
	//---- Comprobante
	//--------------------------------------------------------------------------
	
	function preparar_datos_impresion()
	{
		$gimp = guarani_datos_impresion::instancia();
		$gimp->limpiar_datos(); 
		
		$datos_impresion = $this->cn()->get_datos_impresion();
		$gimp->set_titulo('Comprobante de Inscripción a Curso');
		
		$gimp->set_conf(array(	
			'nro_transaccion' => $datos_impresion['nro_transaccion'], 
			'persona' => $datos_impresion['persona'],
			'propuesta' => $datos_impresion['propuesta'],
			'alumno' => $datos_impresion['nombre_completo'],
			'identificacion' => $datos_impresion['documento'],
			'actividad' => $datos_impresion['actividad_comision'],
			'fecha_insc' => $datos_impresion['fecha_inscripcion_visual'],
			'inicio_clases' => $datos_impresion['fecha_inicio_dictado_visual'],
			'horario' => $datos_impresion['horario_descr'],
			'estado' => $datos_impresion['estado_nombre']
		));

		$gimp->nueva_fila_datos('Alumno', $datos_impresion['nombre_completo']);
		$gimp->nueva_fila_datos('Identificación', $datos_impresion['documento']);
		$gimp->nueva_fila_datos('Curso', $datos_impresion['actividad_comision']);
		$gimp->nueva_fila_datos('Fecha de inscripción', $datos_impresion['fecha_inscripcion_visual']);
		$gimp->nueva_fila_datos('Inicio de clases', $datos_impresion['fecha_inicio_dictado_visual']);
		$gimp->nueva_fila_datos('Horario', $datos_impresion['horario_descr']);
		$gimp->nueva_fila_datos('Estado', $datos_impresion['estado_nombre']);
		
		// Se almacenan los datos en memoria para recuperarlos luego para la impresión de comprobante
		$gimp->guardar_datos_imp();
	}
	
	//--------------------------------------------------------------------------
	//---- Pendiente: implementar pago
	//--------------------------------------------------------------------------

	function extender_objeto_js()
	{
		/*echo "
		//---- Eventos ---------------------------------------------
		
		{$this->objeto_js}.evt__pagar = function()
		{            
			alert('@todo: implementar');
		}
		";*/
	}
}