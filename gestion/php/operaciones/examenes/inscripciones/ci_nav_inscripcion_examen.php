<?php
class ci_nav_inscripcion_examen extends ci_navegacion_wizard
{
	protected $ev_info; //Componente Visual de Información
	protected $s__datos_temp;
	
	// Mail
	protected $s__datos_mail;
	protected $envio_ok = false;
	protected $archivo = '';

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'documento_principal', 'legajo', 'propuesta_descr', 'plan_nombre', 'plan_version_nombre', 'ubicacion_nombre', 'descr', 'turno_examen_nombre'));
			$this->ev_info->set_max_columnas_por_fila(4);
			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'documento_principal', 'legajo'), 0);
			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('propuesta_descr', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('plan_version_nombre', 'Versión');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info->set_etiqueta('descr', 'Actividad');
			$this->ev_info->set_etiqueta('turno_examen_nombre', 'Turno');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');
		}
		return $this->ev_info;
	}

	function cargar_info($pantalla_info)
	{
		// La información se está levantando desde los cuadros.
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA ALUMNOS 
	//--------------------------------------------------------------------------

	function conf__alumno()
	{
		$this->cn()->deseleccionar_alumno();
		unset($this->s__seleccion['alumno']);
		$this->cn()->deseleccionar_actividad();
		$this->cn()->resetear_atributos_parametros();
	}

	//--------------------------------------------------------------------------
	//---- filtro_alumnos ------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro_alumnos(guarani_ei_filtro $filtro)
	{
		// Si el perfil de datos del usuario pertenece al dominio "Propuestas" es obligatorio filtrar los conceptos por ubicación.
		if (usuario::tiene_perfil_por_propuesta()) {
			$filtro->set_ubicacion_obligatoria();
		}
		$filtro->set_sin_filtrado_inicial();
		$filtro->set_etiquetas($this->cn()->etiquetas('Alumnos'));
		$this->filtro_configurar('filtro_alumnos', false);
	}

	function evt__filtro_alumnos__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_alumnos', $datos);
	}

	function evt__filtro_alumnos__cancelar()
	{
		$this->filtro_limpiar('filtro_alumnos');
	}

	//--------------------------------------------------------------------------
	//---- cuadro_alumnos ------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_alumnos(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('filtro_alumnos');
		if ((isset($filtro) && $filtro != '1=1') || $this->dep('filtro_alumnos')->con_filtrado_inicial()) {
			$datos = $this->cn()->get_alumnos_activos($filtro);
			$cuadro->set_datos($datos);
		}
		$msj_eof = $this->get_filtro_msg_eof('filtro_alumnos', 'Alumno');
		if ($msj_eof) {
			$cuadro->set_eof_mensaje($msj_eof);
		}
	}

	function evt__cuadro_alumnos__seleccion($seleccion)
	{
		$this->cn()->cargar_atributos_parametros($seleccion['propuesta'], $seleccion['plan']);
		
		$legajo = $this->cn()->get_legajo_alumno($seleccion['alumno']);
		$seleccion['legajo'] = $legajo;
		$this->set_seleccion($seleccion);
	}

	//--------------------------------------------------------------------------
	//---- Validación de salida ------------------------------------------------
	//--------------------------------------------------------------------------
	
	function validar_alumno()
	{
		// Se valida que se haya seleccionado algún Alumno
		if ($this->falta_seleccionar('alumno')) {
			$mje = guarani_mensajes::get('800_no_seleccion_unica', array('un alumno'));
			throw guarani::error($mje);
		}
		$alumno = $this->get_seleccion_valor('alumno');
		$this->cn()->set_alumno($alumno);
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA ACTIVIDAD 
	//--------------------------------------------------------------------------

	function conf__actividad()
	{
		$this->cn()->deseleccionar_mesa();

		$this->controlador()->ev_info()->set_datos($this->get_seleccion());

		// Se almacenan datos para el popup de 'Ver Inscripciones'
		$info = $this->get_seleccion();
		$info['titulo'] = $this->get_titulo();
		toba::memoria()->set_dato('info_alumno', $info);
	}

	//--------------------------------------------------------------------------
	//---- filtro_actividades --------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro_actividades(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_actividades', false);
	}

	function evt__filtro_actividades__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_actividades', $datos);
	}

	function evt__filtro_actividades__cancelar()
	{
		$this->filtro_limpiar('filtro_actividades');
	}

	//--------------------------------------------------------------------------
	//---- cuadro_actividades --------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_actividades(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('filtro_actividades');
		if ((isset($filtro) && $filtro != '1=1') || $this->dep('filtro_actividades')->con_filtrado_inicial()) {
			$datos = $this->cn()->get_actividades_disponibles($filtro);
			$cuadro->set_datos($datos);
		}

		$msj_eof = $this->get_filtro_msg_eof('filtro_actividades', 'Actividad');
		if ($msj_eof) {
			$cuadro->set_eof_mensaje($msj_eof);
		}
	}

	function evt__cuadro_actividades__seleccion($seleccion)
	{
		$this->set_seleccion($seleccion);
	}

	function conf_evt__cuadro_actividades__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_actividades', $fila));
	}

	//--------------------------------------------------------------------------
	//---- Validación de salida ------------------------------------------------
	//--------------------------------------------------------------------------
	
	function validar_actividad()
	{
		// Se valida que se haya seleccionado alguna Actividad
		if ($this->falta_seleccionar('id')) {
			$mje = guarani_mensajes::get('800_no_seleccion_unica', array('una actividad'));
			throw guarani::error($mje);
		}
		$actividad=[];
		$actividad['id'] = $this->get_seleccion()['id'];
		$actividad['plan_version'] = $this->get_seleccion()['plan_version'];
		$actividad['plan'] = $this->get_seleccion()['plan'];		
		$actividad['tipo_plan'] = $this->get_seleccion()['tipo_plan'];
				
		$this->cn()->set_actividad($actividad);
	}

	function evt__actividad__salida()
	{
		if ($this->wizard_avanza()) {
			
		}
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA MESAS 
	//--------------------------------------------------------------------------

	function conf__mesa()
	{
		$this->cn()->deseleccionar_final();
		$this->controlador()->ev_info()->set_datos($this->get_seleccion());
	}

	//--------------------------------------------------------------------------
	//---- cuadro_mesas --------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_mesas(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_mesas_disponibles();
		$cuadro->set_datos($datos);

		$etiqueta = $this->cn()->etiquetas('Mesas')->get_enf('plural');
		$msj_eof = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta));
		if ($msj_eof) {
			$cuadro->set_eof_mensaje($msj_eof);
		}

		if (guarani::parametros()->get_valor('exa_mostrar_mesas_todas_modalidades') == comunes::no) {
			$cuadro->eliminar_columnas(array('modalidad_nombre'));
		}
	}

	function evt__cuadro_mesas__seleccion($seleccion)
	{
		$this->set_seleccion($seleccion);
	}

	function conf_evt__cuadro_mesas__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_mesas', $fila));
	}

	function conf_evt__cuadro_mesas__ver_docentes($evento, $fila)
	{
		$datos = $this->dep('cuadro_mesas')->get_datos();
		$docentes_cant = $datos[$fila]['docentes_cant'];
		if ($docentes_cant == 0) {
			$evento->anular();
		}
		// Datos para el popup que muestra los docentes de la mesa
		$info = $this->get_seleccion();
		$info['mesa_examen'] = $datos[$fila]['mesa_examen'];
		$info['llamado_mesa'] = $datos[$fila]['llamado_mesa'];
		$info['mesa_nombre'] = $datos[$fila]['mesa_nombre'];
		$info['titulo'] = $this->get_titulo();
		foreach ($info as $dato => $valor) {
			$evento->vinculo()->agregar_parametro(utf8_encode($dato), utf8_encode($valor));
		}
	}

	//--------------------------------------------------------------------------
	//---- Validación de salida ------------------------------------------------
	//--------------------------------------------------------------------------
	
	function validar_mesa()
	{
		// Se valida que se haya seleccionado alguna Mesa
		if ($this->falta_seleccionar('mesa_examen')) {
			$mje = guarani_mensajes::get('800_no_seleccion_unica', array('una mesa'));
			throw guarani::error($mje);
		}
		$mesa = $this->get_seleccion_valor('mesa_examen');
		$llamado = $this->get_seleccion_valor('llamado_mesa');
		$this->cn()->set_mesa($mesa, $llamado);
	}

	function evt__mesas__salida()
	{
		if ($this->wizard_avanza()) {
			
		}
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA FINALIZAR 
	//--------------------------------------------------------------------------

	function conf__finalizar()
	{
		$this->controlador()->ev_info()->set_datos($this->get_seleccion());
		if ($this->cn()->get_estado() == 'NUEVA') {
			$this->evento('nueva_inscripcion')->set_en_botonera(false);
			$msj = guarani_mensajes::get('800exa_confirmar_alta_insc', array($this->get_seleccion_valor('codigo'), $this->get_seleccion_valor('nombre')), false);
			$this->evento('confirmar')->set_msg_confirmacion($msj);
		} else {
			// CONFIRMADA o ERROR
			$this->evento('confirmar')->set_en_botonera(false);
			if ($this->cn()->get_estado() == 'CONFIRMADA') {
				$this->evento('cambiar_tab__anterior')->set_en_botonera(false);
				$this->preparar_datos_impresion();

				// Se genera el comprobante
				$this->generar_comprobante();

				// Enviar MAIL
				$conf = guarani_datos_impresion::instancia()->get_conf();
				$ra = toba::consulta_php('co_propuestas')->get_ra_por_defecto($conf['propuesta']);
				toba::memoria()->set_dato('ra', $ra);
				toba::memoria()->set_dato('id_persona', $conf['id_persona']);
				toba::memoria()->set_dato('path_comp', $this->archivo);
				$envio_mail_autom = guarani::parametros()->get_valor('email_envio_automatico_comprobante');
				if ($envio_mail_autom == comunes::si) {
					$this->evento('mail')->ocultar();
					$this->enviar_mail($conf['id_persona']);
				}
			}
		}
		if ($this->cn()->get_estado() != 'CONFIRMADA') {
			$this->evento('ver_comprobante')->set_en_botonera(false);
			$this->evento('mail')->ocultar();
		}
	}

	//--------------------------------------------------------------------------
	//---- cuadro_mesa_elegidas ------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_ml_mesa(toba_ei_formulario $form)
	{
		if ($this->cn()->get_estado() == 'NUEVA') {
			if ($this->cn()->hay_seleccion_automatica_de_instancia()) {
				$form->set_solo_lectura(array('tipo_insc'));
				$this->set_seleccion(array('tipo_insc' => $this->cn()->get_instancia()));
			}
		} else {
			$form->set_solo_lectura(array('tipo_insc'));
		}

		$datos[0] = $this->get_seleccion();
		$form->set_datos($datos);
	}

	function evt__form_ml_mesa__modificacion($datos)
	{
		if ($this->cn()->get_estado() != 'NUEVA') {
			return;
		}
		$instancia = $datos[0]['tipo_insc'];
		$this->cn()->set_instancia($instancia);
		$this->cn()->set_instancia_datos();
		$this->set_seleccion(array('tipo_insc' => $instancia));
		$this->set_seleccion(array('tipo_insc_info' => $this->cn()->get_instancia_nombre()));
	}

	function get_instancias_mesa()
	{
		return $this->cn()->get_instancias_mesa();
	}

	//--------------------------------------------------------------------------
	//---- cuadro_docentes_mesa ------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_docentes(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_docentes_mesa();
		if ($datos) {
			$datos = rs_ordenar_por_columna($datos, 'rol');
			$cuadro->set_datos($datos);
		}
		$msg_eof = guarani_mensajes::get('800_eof_cuadro', array('Docentes'));
		$cuadro->set_eof_mensaje($msg_eof);
	}

	//--------------------------------------------------------------------------
	//---- DATOS COMPROBANTE 
	//--------------------------------------------------------------------------

	function preparar_datos_impresion()
	{
		$gimp = guarani_datos_impresion::instancia();
		$datos_impresion = $this->cn()->get_datos_comprobante();

		$gimp->set_titulo('Comprobante de Inscripción a Examen');
		$gimp->set_conf(array('propuesta' => $this->cn()->get_alumno_datos('propuesta'), 'nro_transaccion' => $this->cn()->get_nro_transaccion(), 'id_persona' => $this->cn()->get_alumno_datos('persona')));

		foreach ($datos_impresion as $clave => $dato) {
			
			if (!isset($dato['valor'])) {
				$dato['valor'] = ' ';
			}
			
			$gimp->nueva_fila_datos($dato['etiqueta'], $dato['valor']);
			
		}

		// Se almacenan los datos en memoria para recuperarlos luego para la impresión de comprobante
		$gimp->guardar_datos_imp();
	}

	//--------------------------------------------------------------------------
	//---- Validación de salida ------------------------------------------------
	//--------------------------------------------------------------------------

	function validar_finalizar()
	{
		
	}

	function evt__confirmar()
	{
		$this->cn()->guardar();
	}

	function evt__nueva_inscripcion()
	{
		$this->cn()->resetear();
		$this->limpiar_seleccion();
		$this->filtro_limpiar('filtro_actividades');
		$this->set_pantalla('alumno');
		$this->s__datos_mail = null;
	}

	function enviar_mail($persona)
	{
		$this->s__datos_mail = null;

		$mail = new guarani_mail($persona);
		$mail->set_html(true);

		$mail->agregar_adjunto("Comprobante.pdf", $this->archivo, 'base64', 'pdf');

		try {
			$this->s__datos_mail = $mail->get_datos_mail();
			$mail->ejecutar();
			$this->envio_ok = true;
			if (file_exists($this->archivo)) {
				unlink($this->archivo);
			}
		} catch (toba_error $e) {
			$this->envio_ok = false;
		}
	}

	function get_datos_mail()
	{
		return $this->s__datos_mail;
	}

	function envio_ok()
	{
		return $this->envio_ok;
	}

	function generar_comprobante()
	{
		// Se genera el Comprobante
		$pdf = new guarani_pdf(array($this->dep('comprobante')));
		$this->archivo = $pdf->generar(true);
	}

}
?>
