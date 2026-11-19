<?php
/**
 * 
 * 
 * @package 	Comunicación
 * @subpackage 	-
 *
 */
class cn_ent_envio_mensaje extends cn_entidad
{
	protected $s__adjuntos;
	
	// Manejo de adjuntos en edición
	protected $s__adjuntos_agregar;
	protected $s__adjuntos_eliminar;
	protected $s__proceso;
	protected $s__enviar_mail = false;

	//-----------------------------------------------------------------------------------
	//---- Envío de mensajes
	//-----------------------------------------------------------------------------------

	function enviar_mensaje()
	{		
		$datos_mensaje = $this->get_datos();
		
		if (isset($datos_mensaje['enviar_por_twitter']) && ($datos_mensaje['enviar_por_twitter'] == 1)) {
			$this->publicar_en_twitter();
		}
		if (isset($datos_mensaje['enviar_por_facebook']) && ($datos_mensaje['enviar_por_facebook'] == 1)) {
			$this->publicar_en_facebook();
		}
		
		$personas = $this->get_destinatarios();
		
		if (empty($personas)) {
			throw new toba_error('No existen personas para el alcance seleccionado');
		} else {
			$personas = aplanar_matriz($personas, 'persona');
		}
		
		$responder_a = '';
		if(isset($datos_mensaje['responder_a'])) {
			$responder_a = $datos_mensaje['responder_a'];
		}
		
		$enviar_por_twitter = 0;
		if(isset($datos_mensaje['enviar_por_twitter'])) {
			$enviar_por_twitter = $datos_mensaje['enviar_por_twitter'];
		}
		
		$enviar_por_facebook = 0;
		if(isset($datos_mensaje['enviar_por_facebook'])) {
			$enviar_por_facebook = $datos_mensaje['enviar_por_facebook'];
		}
		
		$mensaje_twitter = '';
		if(isset($datos_mensaje['mensaje_twitter'])) {
			$mensaje_twitter = $datos_mensaje['mensaje_twitter'];
		}
		
		$parametros = [
			'titulo' => $datos_mensaje['titulo'],
			'cuerpo' => $datos_mensaje['cuerpo'],
			'alcance' => $datos_mensaje['alcance'],
			'tipoMensaje' => $datos_mensaje['tipo_mensaje'],
			'fechaDesde' => $datos_mensaje['fecha_desde'],
			'fechaHasta' => $datos_mensaje['fecha_hasta'],
			'personas' => $personas,
			'enviarEmail' => $datos_mensaje['enviar_por_mail'],
			'adjuntos' => $this->get_adjuntos(),
			'responderA' => $responder_a,
			'enviarPorTwitter' => $enviar_por_twitter,
			'mensajeTwitter' => $mensaje_twitter,
			'enviarPorFacebook' => $enviar_por_facebook,
			'dominios' => $this->get_dominio(),
			'visibleGestion' => comunes::si
		];
		guarani::notificador()->enviar(notificador::enviar_mensaje, $parametros);
	}

	function enviar_adeudan_requisitos()
	{
		$parametros = ['dominios' => $this->get_dominio()];	
		guarani::notificador()->enviar(notificador::adeudan_requisitos, $parametros);		
	}
	
	function publicar_en_twitter()
	{
		$datos = $this->get_datos();
		try {
			guarani_twitter::publicar($datos['mensaje_twitter']);
			$mensaje = 'Se publicó en Twitter correctamente.';
			$this->set_error($mensaje, null, comunes::error_informacion);
		} catch (guarani_error $e) {
			guarani::act('act_mensajes')->desmarcar_publicar_twitter($datos['mensaje']);
			$mensaje = 'No se pudo publicar en Twitter. ' . $e->get_mensaje();
			$this->set_error($mensaje, null, comunes::error_critico);
		}
	}

	function publicar_en_facebook()
	{
		$datos = $this->get_datos();
		try {
			guarani_facebook::publicar($datos['cuerpo']);
			$mensaje = 'Se publicó en Facebook correctamente.';
			$this->set_error($mensaje, null, comunes::error_informacion);
		} catch (guarani_error $e) {
			guarani::act('act_mensajes')->desmarcar_publicar_facebook($datos['mensaje']);
			$mensaje = 'No se pudo publicar en Facebook. ' . $e->get_mensaje();
			$this->set_error($mensaje, null, comunes::error_critico);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Sincronización
	//-----------------------------------------------------------------------------------

	function sincronizar()
	{
		$this->pre_sincronizar();
		$this->desactivar_transacciones_anidadas();

		try {			
			//$this->sincronizar_adjuntos();
			$this->set_error('guardar_ok', null, comunes::error_informacion);
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->unset_id_mensaje();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}

	function sincronizar_adjuntos()
	{
		if (!empty($this->s__adjuntos)) {
			foreach ($this->s__adjuntos as $key => $adjunto) {
				// Si se encuentra en una carpeta temporal...
				if (stripos($adjunto['path'], '/temp/')) {
					// Se mueve al repositorio.
					$this->s__adjuntos[$key]['path'] = guarani_repositorio::mover_temp_al_repositorio($adjunto['path']);					
				}
			}
		}
	}

	function procesar_adjuntos_marcados($mensaje)
	{
		// Eliminar los marcados
		if (!empty($this->s__adjuntos_eliminar)) {
			foreach ($this->s__adjuntos_eliminar as $key => $path) {
				$id_archivo = guarani::co('co_mensajes')->get_id_archivo($path);
				if (!empty($id_archivo)) {
					guarani::act('act_mensajes')->eliminar_men_documentos($mensaje, $id_archivo);
					guarani::act('act_mensajes')->eliminar_rep_archivo($id_archivo);
				}
				guarani_repositorio::eliminar_archivo($path);
			}
			unset($this->s__adjuntos_eliminar);
		}

		// Insertar los nuevos adjuntos
		if (!empty($this->s__adjuntos_agregar)) {
			foreach ($this->s__adjuntos_agregar as $key => $adjunto) {
				// Si se encuentra en una carpeta temporal...
				if (stripos($adjunto['path'], '/temp/')) {
					// Se mueve al repositorio.
					$this->s__adjuntos_agregar[$key]['path'] = guarani_repositorio::mover_temp_al_repositorio($adjunto['path']);
					// Se inserta el correspondiente registro en la base de datos.
					$id_archivo = guarani::act('act_mensajes')->insertar_rep_archivo($this->s__adjuntos_agregar[$key]['path'], $adjunto['nombre'], $adjunto['tamanio'], $adjunto['extension']);
					guarani::act('act_mensajes')->insertar_men_documentos($mensaje, $id_archivo);
				}
			}
		}
	}

	function pre_sincronizar()
	{
		$alcance = $this->get_alcance();

		// Se asegura que no se pueda publicar en twitter y facebook si el alcance no es 'Todos los alumnos y docentes'.
		if (($alcance != mensajes::todos_alumnos_y_docentes)) {
			$datos = array();
			$datos['enviar_por_twitter'] = '0';
			$datos['enviar_por_facebook'] = '0';
			$datos['mensaje_twitter'] = null;
			$this->set_datos($datos);
		}

		// Validaciones generales.
		$this->validar();

		// Se valida, en función del alcance elegido, que haya al menos una selección.
		switch ($alcance) {
			case mensajes::alumnos_ra :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Responsable Académica'));
				}
				break;

			case mensajes::alumnos_ubicacion :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Ubicación'));
				}
				break;

			case mensajes::grupo_alumnos :
				$datos = $this->get_personas_asociadas();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('un Alumno'));
				}
				break;

			case mensajes::alumnos_propuesta :
			case mensajes::alumnos_extranjeros :
			case mensajes::alumnos_adeudan_requisitos :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Propuesta'));
				}
				break;

			case mensajes::alumnos_propuesta_plan :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Propuesta'));
				}
				break;

			case mensajes::alumnos_propuesta_plan_ubicacion :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Propuesta'));
				}
				break;

			case mensajes::alumnos_ingresantes :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Propuesta'));
				}
				break;

			case mensajes::alumnos_comision :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Comisión'));
				} else {
					$dominio = current($datos);
					if (($dominio['insc_aceptadas'] == comunes::no) && ($dominio['insc_pendientes'] == comunes::no) && ($dominio['insc_rechazadas'] == comunes::no)) {
						$this->set_error('800_no_seleccion', array('un estado de Inscripción'));
					}
				}
				break;

			case mensajes::alumnos_mesa_examen :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Mesa de Examen'));
				} else {
					$dominio = current($datos);
					if (($dominio['insc_aceptadas'] == comunes::no) && ($dominio['insc_pendientes'] == comunes::no) && ($dominio['insc_rechazadas'] == comunes::no)) {
						$this->set_error('800_no_seleccion', array('un estado de Inscripción'));
					}
				}
				break;

			case mensajes::grupo_docentes :
				$datos = $this->get_personas_asociadas();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('un Docente'));
				}
				break;

			case mensajes::docentes_catedra :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Cátedra'));
				}
				break;

			case mensajes::grupo_destinatarios :
				$datos = current($this->get_dominio());
				if (empty($datos['grupo'])) {
					$this->set_error('800_no_seleccion', array('un Grupo de Destinatarios'));
				}
				break;

			case mensajes::egresados_colacion :
				$datos = current($this->get_dominio());
				if (empty($datos['colacion'])) {
					$this->set_error('800_no_seleccion', array('una Colación'));
				}
				break;
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar()
	{
		$datos = $this->get_datos();

		// La fecha de inicio de publicación debe ser mayor o igual a la fecha actual (solo validar en el alta)
		if ($this->es_nueva()) {
			if (isset($datos['fecha_desde']) && guarani_fecha::es_menor($datos['fecha_desde'], guarani_fecha::get_hoy())) {
				$this->set_error('800_control_fechas', array('Desde', 'menor', 'actual'));
			}
		}
		
		if (isset($datos['fecha_desde']) && isset($datos['fecha_hasta'])) {
			// La fecha de fin de publicación debe ser mayor o igual a la fecha de inicio de publicación.
			if (guarani_fecha::es_menor($datos['fecha_hasta'], $datos['fecha_desde'])) {
				$this->set_error('800_control_fechas', array('Hasta', 'menor', 'Desde'));
			}
		}
	}

	function get_destinatarios()
	{
		$alcance = $this->get_alcance();
		$dominio = $this->get_dominio();
		$personas = array();		
		switch ($alcance) {
			case mensajes::todos_alumnos_y_docentes :
				$personas = guarani::co('co_mensajes')->get_todos_alumnos_y_docentes();
				break;

			case mensajes::todos_alumnos :
				$personas = guarani::co('co_mensajes')->get_todos_alumnos();
				break;

			case mensajes::alumnos_ra :
				$personas = guarani::co('co_mensajes')->get_alumnos_ra($dominio);
				break;

			case mensajes::alumnos_ubicacion :
				$personas = guarani::co('co_mensajes')->get_alumnos_ubicacion($dominio);
				break;

			case mensajes::alumnos_propuesta :				
				$personas = guarani::co('co_mensajes')->get_alumnos_propuesta($dominio);
				break;

			case mensajes::alumnos_propuesta_plan :
				$personas = guarani::co('co_mensajes')->get_alumnos_propuesta_plan($dominio);
				break;

			case mensajes::alumnos_propuesta_plan_ubicacion :
				$personas = guarani::co('co_mensajes')->get_alumnos_propuesta_plan_ubicacion($dominio);
				break;

			case mensajes::alumnos_ingresantes :
				$personas = guarani::co('co_mensajes')->get_alumnos_ingresantes($dominio);
				break;

			case mensajes::alumnos_comision :
				$personas = guarani::co('co_mensajes')->get_alumnos_comision($dominio);
				break;

			case mensajes::alumnos_mesa_examen :
				$personas = guarani::co('co_mensajes')->get_alumnos_mesa_examen($dominio);
				break;

			case mensajes::todos_docentes :
				$personas = guarani::co('co_mensajes')->get_todos_docentes();
				break;

			case mensajes::docentes_mesa_examen :
				$personas = guarani::co('co_mensajes')->get_docentes_mesa_examen($dominio);
				break;

			case mensajes::docentes_comision :
				$personas = guarani::co('co_mensajes')->get_docentes_comision($dominio);
				break;

			case mensajes::docentes_catedra :
				$personas = guarani::co('co_mensajes')->get_docentes_catedra($dominio);
				break;

			case mensajes::grupo_destinatarios :
				$personas = guarani::co('co_mensajes')->get_grupo_destinatarios($dominio);
				break;

			case mensajes::alumnos_con_encuestas_pendientes :
				$personas = guarani::co('co_mensajes')->get_alumnos_con_encuestas_pendientes();
				break;
			
			case mensajes::grupo_alumnos :
			case mensajes::grupo_docentes :				
				$personas = $this->get_personas_asociadas();
				break;
			
			case mensajes::alumnos_extranjeros :
				$personas = guarani::co('co_mensajes')->get_alumnos_extranjeros($dominio);
				break;
			
			case mensajes::alumnos_adeudan_requisitos :				
				$personas = guarani::co('co_mensajes')->get_alumnos_adeudan_requisitos($dominio);
				break;

			case mensajes::egresados_colacion :				
				$personas = guarani::co('co_mensajes')->get_egresados_colacion($dominio);
				break;
		}
		return $personas;
	}

	//-----------------------------------------------------------------------------------

	function resetear_relacion()
	{
		$this->tabla('dominio')->eliminar_filas();
		$this->tabla('destinatarios')->eliminar_filas();
	}

	function resetear_adjuntos_temporales()
	{		
		unset($this->s__adjuntos);
		unset($this->s__adjuntos_agregar);
		unset($this->s__adjuntos_eliminar);
	}

	function unset_id_mensaje()
	{
		$datos = $this->get_datos();
		unset($datos['mensaje']);
		$this->get_datos($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- Mensaje
	//-----------------------------------------------------------------------------------

	function set_datos($datos, $tabla = null, $validar = true)
	{
		// Si se seteo el cuerpo le quito los espacios demás luego del <p>
		if(isset($datos['cuerpo'])){
			$patron				= '/(<p>)([\s]*)([^\s].*)(<\/p>)/i';
			$sustitucion		= '${1}${3}${4}';
			$datos['cuerpo']	= preg_replace($patron, $sustitucion, $datos['cuerpo']);
		}
		
		$this->tabla('mensaje')->set($datos);
	}

	function get_datos($tabla = null)
	{
		return $this->tabla('mensaje')->get();
	}

	//-----------------------------------------------------------------------------------
	//---- Alcance
	//-----------------------------------------------------------------------------------

	function set_alcance($alcance)
	{
		$this->tabla('mensaje')->set_columna_valor('alcance', $alcance);
	}

	function get_alcance()
	{
		return $this->tabla('mensaje')->get_columna('alcance');
	}

	//-----------------------------------------------------------------------------------
	//---- Dominio
	//-----------------------------------------------------------------------------------

	function set_dominio($datos)
	{
		$this->resetear_dominio();
		foreach ($datos as $dominio) {
			$this->tabla('dominio')->nueva_fila($dominio);
		}
	}

	function get_dominio()
	{
		return $this->tabla('dominio')->get_filas();
	}

	function agregar_dominio($asignacion)
	{
		$this->tabla('dominio')->nueva_fila($asignacion);
	}

	function modificar_dominio($id_fila, $asignacion)
	{
		$this->tabla('dominio')->modificar_fila($id_fila, $asignacion);
	}

	function resetear_dominio()
	{
		$this->tabla('dominio')->eliminar_filas();
	}

	function eliminar_dominio($dominio)
	{
		$fila = $this->tabla('dominio')->get_filas($dominio);
		if (!empty($fila)) {
			$this->tabla('dominio')->eliminar_fila($fila[0]['x_dbr_clave']);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Personas
	//-----------------------------------------------------------------------------------

	function eliminar_persona_asignada($asignacion)
	{
		$fila = $this->tabla('destinatarios')->get_filas($asignacion);
		if (!empty($fila)) {
			$this->tabla('destinatarios')->eliminar_fila($fila[0]['x_dbr_clave']);
		}
	}

	function get_personas_asociadas()
	{
		return $this->tabla('destinatarios')->get_filas();
	}

	function asignar_persona($asignacion)
	{
		$this->tabla('destinatarios')->nueva_fila($asignacion);
	}

	//-----------------------------------------------------------------------------------
	//---- Repositorio
	//-----------------------------------------------------------------------------------

	function agregar_documento($datos)
	{
		$datos_adjunto = array();
		$datos_adjunto['path'] = guarani_repositorio::subir_archivo_temp($datos['archivo']);		
		$datos_adjunto['tamanio'] = ceil($datos['archivo']['size'] / 1024);
		$nombre = explode('.', $datos['archivo']['name']);
		$datos_adjunto['extension'] = $nombre[1];
		$datos_adjunto['nombre'] = $nombre[0];

		$this->nuevo_adjunto($datos_adjunto);
	}

	function marcar_para_agregar_documento($datos)
	{
		$datos_adjunto = array();
		$datos_adjunto['path'] = guarani_repositorio::subir_archivo_temp($datos['archivo']);
		$datos_adjunto['nombre'] = $datos['archivo']['name'];
		$datos_adjunto['tamanio'] = ceil($datos['archivo']['size'] / 1024);
		$nombre = explode('.', $datos['archivo']['name']);
		$datos_adjunto['extension'] = $nombre[1];

		if (!isset($this->s__adjuntos_agregar)) {
			$this->s__adjuntos_agregar = array();
		}
		$this->s__adjuntos_agregar[] = $datos_adjunto;
	}

	function get_adjuntos()
	{
		if (empty($this->s__adjuntos)) {
			// Si hay ID de mensaje, lo traigo de la base de datos
			$datos_mensaje = $this->get_datos();
			if (!empty($datos_mensaje['mensaje'])) {
				if (empty($this->s__adjuntos_eliminar)) {
					$this->s__adjuntos = guarani::co('co_mensajes')->get_listado_adjuntos($datos_mensaje['mensaje']);
				}
			} else {
				$this->s__adjuntos = array();
			}
		}

		if (!empty($this->s__adjuntos_agregar)) {
			$this->s__adjuntos = array_merge($this->s__adjuntos, $this->s__adjuntos_agregar);
		}		
		
		return $this->s__adjuntos;
	}

	function eliminar_de_adjuntos_agregar($path)
	{
		if (!empty($this->s__adjuntos_agregar)) {
			$nueva_lista = array();
			foreach ($this->s__adjuntos_agregar as $adjunto) {
				if ($adjunto['path'] != $path) {
					$nueva_lista[] = $adjunto;
				}
			}
			$this->s__adjuntos_agregar = $nueva_lista;
		}
	}

	function nuevo_adjunto($datos)
	{
		if (!isset($this->s__adjuntos)) {
			$this->s__adjuntos = array();
		}
		$this->s__adjuntos[] = $datos;
	}

	function eliminar_adjunto($path)
	{
		guarani_repositorio::eliminar_archivo($path);
		foreach ($this->s__adjuntos as $key => $adjunto) {
			if ($adjunto['path'] == $path) {
				unset($this->s__adjuntos[$key]);
				break;
			}
		}
	}

	function marcar_para_eliminar($path)
	{
		if (empty($this->s__adjuntos_eliminar)) {
			$this->s__adjuntos_eliminar = array();
		}
		$this->s__adjuntos_eliminar[] = $path;
		foreach ($this->s__adjuntos as $key => $adjunto) {
			if ($adjunto['path'] == $path) {
				unset($this->s__adjuntos[$key]);
				break;
			}
		}
	}

	function eliminar_adjuntos_temporales()
	{
		if (!empty($this->s__adjuntos)) {
			$adjuntos = $this->s__adjuntos;
			$this->s__adjuntos = array();
			foreach ($adjuntos as $key => $adjunto) {
				if (stripos($adjunto['path'], '/temp/')) {
					guarani_repositorio::eliminar_archivo($adjunto['path']);
				} else {
					$this->s__adjuntos[] = $adjunto;
				}
			}
		}
	}

}
?>