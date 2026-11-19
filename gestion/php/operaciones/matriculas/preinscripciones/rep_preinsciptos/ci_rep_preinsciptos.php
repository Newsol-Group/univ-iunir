<?php
class ci_rep_preinsciptos extends ci_rep_sin_listado_inicial
{
	protected $s__filtro_condiciones;
	protected $msg_800MAT_Asignar_Preinscripciones = '800MAT_Asignar_Preinscripciones';

	// Selección de prenscripción para edición.
	protected $s__seleccion;
	protected $s__datos_preinscripcion;

	// Nuevos datos de preisncripción editada.
	protected $nuevos_datos_preinscripcion;

	// Componente Visual de Información.
	protected $ev_info;
	protected $ev_info_enviar_mensaje;
		
	function get_datos($filtro)
	{
		return toba::consulta_php('co_preinscripciones')->get_datos_reporte_preinscripciones($this->s__filtro_condiciones);
	}
	
	function get_tabla_principal()
	{
		return 'sga_preinscripcion_propuesta';
	}

	function conf__pant_modificar_preinscripto(toba_ei_pantalla $pantalla)
	{
		$this->s__datos_preinscripcion = toba::consulta_php('co_preinscripciones')->get_datos_preinscripcion($this->s__seleccion['id_preinscripcion'], $this->s__seleccion['fuente']);
		$this->ev_info()->set_datos($this->s__datos_preinscripcion);
		$msj = guarani_mensajes::get('800MAT_eliminar_preinscripcion');
		$this->evento('eliminar')->set_msg_confirmacion($msj);
	}

	function conf__pant_enviar_mensaje(toba_ei_pantalla $pantalla)
	{
		$this->s__datos_preinscripcion = toba::consulta_php('co_preinscripciones')->get_datos_preinscripcion($this->s__seleccion['id_preinscripcion'], $this->s__seleccion['fuente']);
		$this->ev_info_enviar_mensaje()->set_datos($this->s__datos_preinscripcion);
	}

	//--------------------------------------------------------------------------
	//----- filtro -------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodo_preinscripcion')->set_condicion_fija('es_igual_a');
		$filtro->columna('propuesta_tipo')->set_condicion_fija('es_igual_a');
		$filtro->columna('genero')->set_condicion_fija('es_igual_a');
		$filtro->columna('estado')->set_condicion_fija('es_igual_a');
		$filtro->columna('duplicada')->set_condicion_fija('es_igual_a');
		$filtro->columna('mismo_nombre_fecha_nac')->set_condicion_fija('es_igual_a');
		if (!$filtro->columna('origen')->tiene_estado()) {
			$filtro->columna('origen')->set_estado(array('valor' => array('3w', 'preinscripcion')));
		}
		if (!$filtro->columna('duplicada')->tiene_estado()) {
			$filtro->columna('duplicada')->set_estado(array('condicion' => 'es_igual_a', 'valor' => comunes::si));
		}
	}
	
	function evt__filtro__filtrar($datos)
	{
		parent::evt__filtro__filtrar($datos);
		
		// Se obtiene el conjunto de propuestas que puede ver el usuario según su perfil de datos
		$sql_propuestas = "SELECT propuesta FROM sga_propuestas;";
		$propuestas = aplanar_matriz(guarani_db::consultar($sql_propuestas), 'propuesta');
		
		// Condiciones de filtrado
		$this->s__filtro_condiciones = array();
		$this->s__filtro_condiciones['propuestas'] = $propuestas;
		if (!empty($datos['periodo_preinscripcion'])) {
			$this->s__filtro_condiciones['periodos'] = toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_insc_fechas_de_periodo_inscripcion($datos['periodo_preinscripcion']['valor']);
		}
		$this->s__filtro_condiciones['origenes'] = $datos['origen']['valor'];
		if (!empty($datos['propuesta_tipo'])) {
			$propuestas = toba::consulta_php('co_propuestas')->get_propuestas_x_proptipo($datos['propuesta_tipo']['valor']);
			$this->s__filtro_condiciones['propuestas'] = array_intersect($this->s__filtro_condiciones['propuestas'], aplanar_matriz($propuestas, 'valor'));
		}
		if (!empty($datos['estado'])) {
			$this->s__filtro_condiciones['estado'] = $datos['estado']['valor'];
		}
		if (!empty($datos['duplicada'])) {
			$this->s__filtro_condiciones['duplicada'] = $datos['duplicada']['valor'];
		}
		if (!empty($datos['mismo_nombre_fecha_nac'])) {
			$this->s__filtro_condiciones['mismo_nombre_fecha_nac'] = $datos['mismo_nombre_fecha_nac']['valor'];
		}

		// Reset de columnas que no forman parte del where (filtrado ad-hoc)
		$this->dep('filtro')->columna('anio_academico')->resetear_estado();
		$this->dep('filtro')->columna('periodo_preinscripcion')->resetear_estado();
		$this->dep('filtro')->columna('origen')->resetear_estado();
		$this->dep('filtro')->columna('propuesta_tipo')->resetear_estado();
		$this->dep('filtro')->columna('estado')->resetear_estado();
		$this->dep('filtro')->columna('duplicada')->resetear_estado();
		$this->dep('filtro')->columna('mismo_nombre_fecha_nac')->resetear_estado();

		// Where SQL
		$this->s__filtro_condiciones['where_preinscripcion'] = $this->get_where_preinscripcion($this->dep('filtro')->get_sql_where());
		$this->s__filtro_condiciones['where_3w'] = $this->get_where_3w($this->dep('filtro')->get_sql_where());
	}

	function evt__filtro__cancelar()
	{
		parent::evt__filtro__cancelar();
		unset($this->s__filtro_condiciones);
	}

	protected function get_where_preinscripcion($where)
	{
		$where = str_replace('tabla.responsable_academica', 'sga_preinscripcion_propuestas.responsable_academica', $where);
		$where = str_replace('tabla.propuesta', 'sga_preinscripcion_propuestas.propuesta', $where);
		$where = str_replace("tabla.apellido || ' ' || tabla.nombre", "COALESCE(sga_preinscripcion.apellido_elegido, sga_preinscripcion.apellido) || ' ' || COALESCE(sga_preinscripcion.nombres_elegido, sga_preinscripcion.nombres)", $where);
		$where = str_replace('tabla.nro_documento', 'sga_preinscripcion.nro_documento', $where);
		$where = str_replace("tabla.sexo = 'M'", "sga_preinscripcion.sexo = '1'", $where);
		$where = str_replace("tabla.sexo = 'F'", "sga_preinscripcion.sexo = '2'", $where);
		$where = str_replace("tabla.sexo = 'X'", "sga_preinscripcion.sexo = '3'", $where);
		$where = str_replace('tabla.nacionalidad', 'sga_preinscripcion.nacionalidad', $where);
		$where = str_replace('tabla.modalidad', 'sga_preinscripcion_propuestas.modalidad', $where);
		$where = str_replace('tabla.ubicacion', 'sga_preinscripcion_propuestas.ubicacion', $where);
		return $where;
	}

	protected function get_where_3w($where)
	{
		$where = str_replace('tabla.responsable_academica', 'sga_preinscripcion_propuesta.responsable_academica', $where);
		$where = str_replace('tabla.propuesta', 'sga_preinscripcion_propuesta.propuesta', $where);
		$where = str_replace("tabla.apellido || ' ' || tabla.nombre", "COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) || ' ' || COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres)", $where);
		$where = str_replace('tabla.nro_documento', 'mdp_personas_documentos.nro_documento', $where);
		$where = str_replace('tabla.sexo', 'mdp_personas.sexo', $where);
		$where = str_replace('tabla.nacionalidad', 'mdp_personas.nacionalidad', $where);
		$where = str_replace('tabla.modalidad', 'sga_preinscripcion_propuesta.modalidad', $where);
		$where = str_replace('tabla.ubicacion', 'sga_preinscripcion_propuesta.ubicacion', $where);
		return $where;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->set_pantalla('pant_modificar_preinscripto');
	}

	function evt__cuadro__enviar_mensaje($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->set_pantalla('pant_enviar_mensaje');
	}

	function evt__cuadro__asignarme($seleccion)
	{
		$mje = guarani_mensajes::get($this->msg_800MAT_Asignar_Preinscripciones, array($seleccion['apellido_y_nombres']));
		$this->informar_msg($mje, 'info');

		$usuario_gestion = toba::consulta_php('co_personas')->get_persona_x_usuario(toba::usuario()->get_id());
		if (is_null($seleccion['id_preinscripcion_3w'])) {
			$fuente = $seleccion['fuente'];
			$id_preinscripcion = $seleccion['id_preinscripcion'];
		} else {
			$fuente = preinscripcion::origen_autogestion;
			$id_preinscripcion = $seleccion['id_preinscripcion_3w'];
		}
		guarani::act('act_preinscripciones')->cambio_atendedor($id_preinscripcion, $fuente, $usuario_gestion);
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
		if ($datos[$fila]['estado'] != preinscripcion::sin_finalizar_por_el_aspirante_desc) {
			$evento->anular();
		}
	}

	function conf_evt__cuadro__enviar_mensaje(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
		if ($datos[$fila]['estado'] != preinscripcion::sin_finalizar_por_el_aspirante_desc || empty($datos[$fila]['mail'])) {
			$evento->anular();
		}
	}

	function conf_evt__cuadro__asignarme(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
		if ($datos[$fila]['estado'] == preinscripcion::sin_finalizar_por_el_aspirante_desc) {
			$evento->anular();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		$form->ef('e_mail')->set_expreg(comunes::er_email);
		$datos = isset($this->nuevos_datos_preinscripcion) ? $this->nuevos_datos_preinscripcion : $this->s__datos_preinscripcion;
		$form->set_datos($datos);
	}

	function evt__formulario__modificacion($datos)
	{
		$this->nuevos_datos_preinscripcion = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_enviar_mensaje ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_enviar_mensaje__enviar($datos)
	{
		$hacia = $this->s__seleccion['mail'];
		$asunto = $datos['titulo'];
		$mensaje = $datos['mensaje'];

		try {
			$mail = new guarani_mail(null, $hacia, $asunto, $mensaje);
			$mail->set_html(true);
			$mail->ejecutar();
		} catch (toba_error $e) {
			toba::logger()->error($e->getMessage());
			throw guarani::error('No se ha podido enviar el mail. Revisar los parámetros de configuración.');
		}

		$this->set_pantalla('pant_inicial');
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();

			$this->ev_info->set_controlador($this);
			// Apellido y Nombre, Género, Nacionalidad, Teléfono, Origen y Atendido por
			$this->ev_info->set_columnas_a_mostrar(array('apellido', 'nombres', 'sexo_descr', 'nacionalidad_descr', 'celular_numero'));
			$this->ev_info->set_columnas_fila(array('apellido', 'nombres'), 1);
			$this->ev_info->set_columnas_fila(array('sexo_descr', 'nacionalidad_descr'), 2);
			$this->ev_info->set_columnas_fila(array('celular_numero'), 3);

			$this->ev_info->set_etiqueta('apellido', 'Apellido');
			$this->ev_info->set_etiqueta('nombres', 'Nombres');
			$this->ev_info->set_etiqueta('sexo_descr', 'Género');
			$this->ev_info->set_etiqueta('nacionalidad_descr', 'Nacionalidad');
			$this->ev_info->set_etiqueta('celular_numero', 'Teléfono');
		}
		return $this->ev_info;
	}

	function ev_info_enviar_mensaje()
	{
		if (!isset($this->ev_info_enviar_mensaje)) {
			$this->ev_info_enviar_mensaje = guarani::ev_info_datos();

			$this->ev_info_enviar_mensaje->set_controlador($this);
			$this->ev_info_enviar_mensaje->set_columnas_a_mostrar(array('apellido', 'nombres', 'e_mail'));
			$this->ev_info_enviar_mensaje->set_columnas_fila(array('apellido', 'nombres'), 1);
			$this->ev_info_enviar_mensaje->set_columnas_fila(array('e_mail'), 2);

			$this->ev_info_enviar_mensaje->set_etiqueta('apellido', 'Apellido');
			$this->ev_info_enviar_mensaje->set_etiqueta('nombres', 'Nombres');
			$this->ev_info_enviar_mensaje->set_etiqueta('e_mail', 'Email');
		}
		return $this->ev_info_enviar_mensaje;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
		unset($this->s__seleccion);
		unset($this->s__datos_preinscripcion);
		$this->set_pantalla('pant_inicial');
	}

	function evt__eliminar()
	{
		try {
			toba::db()->abrir_transaccion();
			guarani::act('act_preinscripciones')->eliminar_preinscripcion($this->s__seleccion['id_preinscripcion'], $this->s__seleccion['fuente']);
			guarani::act('act_preinscripciones')->marcar_preinscripciones_no_duplicadas($this->s__seleccion['id_preinscripcion'], $this->s__seleccion['fuente'], $this->s__datos_preinscripcion); 
			toba::db()->cerrar_transaccion();
			toba::notificacion()->agregar($this->get_mensaje('eliminar_ok'), 'info');
			$this->set_pantalla('pant_inicial');
		} catch (guarani_error $e)	{
			toba::db()->abortar_transaccion();
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}

	function evt__guardar()
	{
		if (toba::consulta_php('co_preinscripciones')->existe_mail($this->nuevos_datos_preinscripcion['e_mail'], $this->s__seleccion['fuente'], $this->s__seleccion['id_preinscripcion'])) {
			throw new toba_error(guarani_mensajes::get('800MAT_existe_mail_preinscripcion'));
		}
		if (toba::consulta_php('co_personas')->existe_persona($this->nuevos_datos_preinscripcion['pais_documento'], $this->nuevos_datos_preinscripcion['tipo_documento'], $this->nuevos_datos_preinscripcion['nro_documento'])) {
			throw new toba_error(guarani_mensajes::get('800MAT_existe_persona_gestion'));
		}
		if (toba::consulta_php('co_preinscripciones')->existe_persona($this->nuevos_datos_preinscripcion['pais_documento'], $this->nuevos_datos_preinscripcion['tipo_documento'], $this->nuevos_datos_preinscripcion['nro_documento'], $this->s__seleccion['fuente'], $this->s__seleccion['id_preinscripcion'])) {
			throw new toba_error(guarani_mensajes::get('800MAT_existe_persona_preinscripcion'));
		}
		try {
			toba::db()->abrir_transaccion();
			guarani::act('act_preinscripciones')->modificar_preinscripcion($this->s__seleccion['id_preinscripcion'], $this->s__seleccion['fuente'], $this->nuevos_datos_preinscripcion); 
			// Si hubo un cambio en la identificación, la cuenta deja de estar duplicada (puede que sólo haya cambiado el mail).
			if ($this->nuevos_datos_preinscripcion['pais_documento'] != $this->s__datos_preinscripcion['pais_documento'] ||
				$this->nuevos_datos_preinscripcion['tipo_documento'] != $this->s__datos_preinscripcion['tipo_documento'] ||
				$this->nuevos_datos_preinscripcion['nro_documento'] != $this->s__datos_preinscripcion['nro_documento']) {
				guarani::act('act_preinscripciones')->marcar_preinscripciones_no_duplicadas($this->s__seleccion['id_preinscripcion'], $this->s__seleccion['fuente'], $this->s__datos_preinscripcion); 
			}
			toba::db()->cerrar_transaccion();
			$this->set_pantalla('pant_inicial');
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (guarani_error $e)	{
			toba::db()->abortar_transaccion();
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}

	function get_periodos_preinscripcion_por_anio_academico($anio_academico)
	{
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (empty($perfil_datos)) {
			return toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_preinscripcion_por_anio_academico($anio_academico);
		} else {
			$filtro = "	sga_periodos_inscripcion_propuesta.periodo_insc_tipo IN ('".periodo_de_inscripcion::tipo_preinscripcion."', '".periodo_de_inscripcion::tipo_todos."') AND 
						sga_periodos_inscripcion_propuesta.anio_academico = ".toba::db()->quote($anio_academico);
			return toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_inscripcion_propuesta_perfil_de_datos($filtro);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();

		$msj_confirmacion = guarani_mensajes::get('800_abandono_edicion');
		$msj_sin_cambios = guarani_mensajes::get('800_no_hubo_cambios');
		
		$confirmar = 'var confirmar = toba.hay_cambios();';
		
		echo $this->js_evt_cancelar();
		echo "
			{$this->objeto_js}.evt__guardar = function() {
				return confirmar_no_cambios();
			}

			function confirmar_cambios(proyecto, operacion, url, es_popup) {
				if (! es_popup || typeof(es_popup) == 'undefined') {
					$confirmar
					if (confirmar) {
						resp = confirm('$msj_confirmacion');
						if (! resp) {
							return false;
						}
					}
				}
				return true;
			}
			
			function confirmar_no_cambios(proyecto, operacion, url, es_popup) {
				if (! es_popup || typeof(es_popup) == 'undefined') {
					$confirmar
					if (! confirmar) {
						alert('$msj_sin_cambios');
						return false;
					}
				}
				return true;
			}
			
            toba.set_callback_menu(confirmar_cambios);
		";
	}

	// Se modulariza el evento js cancelar para poder personalizar su comportamiento al extender esta clase
	function js_evt_cancelar() 
	{
		return "
			{$this->objeto_js}.evt__cancelar = function() {
				return confirmar_cambios();
			}
			";
	}
}
