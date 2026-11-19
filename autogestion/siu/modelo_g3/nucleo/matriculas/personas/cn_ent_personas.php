<?php
/**
 *
 *
 * @package		Matrícula
 * @subpackage	Personas
 * 
 */
class cn_ent_personas extends cn_entidad
{
	
	const LARGO_TEXTAREA = 2500;
	
	function set_datos_personas($datos)
	{
		$this->validar_datos_personas($datos);
		$this->tabla('personas')->set($datos);

		$datos_extranjero = ($datos['nacionalidad'] == persona::nac_extranjero) ? $datos : null;
		$this->tabla('extranjeros')->set($datos_extranjero);
	}

	function get_datos_personas()
	{
		$persona = $this->tabla('personas')->get();
		if (isset($persona) && $persona['nacionalidad'] == persona::nac_extranjero) {
			// Datos de visa y residencia.
			$extranjero = $this->tabla('extranjeros')->get();
			if (!empty($extranjero)) {
				$persona = array_merge($extranjero, $persona);
			}
		}
		return $persona;
	}

	function hay_cursor_personas()
	{
		return $this->tabla('personas')->hay_cursor();
	}

	//--------------------------------------------------------------------------

	function set_datos_fotos($datos)
	{
		if (isset($datos['imagen'])) {
			$imagen = array();
			$imagen['nombre_imagen'] = $datos['imagen']['name'];
			$imagen['fecha'] = 'now()';
			$this->tabla('fotos')->set($imagen);

			$manejador = guarani_imgs::redimensionar_imagen($datos['imagen']);
			$this->tabla('fotos')->set_blob('imagen', $manejador);
		}
	}

	function get_datos_fotos()
	{
		$datos = $this->tabla('fotos')->get();
		if (!empty($datos)) {
			$datos['blob'] = $this->tabla('fotos')->get_blob('imagen');
		}
		return $datos;
	}

	function eliminar_datos_fotos()
	{
		$this->tabla('fotos')->eliminar_filas(); //la tabla tiene una única fila.
	}

	//-------------------------------------------------------------------

	function seleccionar_documento($id_fila_documento)
	{
		$this->tabla('documentos')->set_cursor($id_fila_documento);
	}

	function deseleccionar_documento()
	{
		$this->tabla('documentos')->resetear_cursor();
	}

	function hay_documento_seleccionado()
	{
		return $this->tabla('documentos')->hay_cursor();
	}

	function set_datos_documento($datos)
	{
		$this->validar_datos_documentos($datos);

		if (isset($datos['cuit'])) {
			//Si se cargó el cuit se setea un documento más de la persona en el mismo campo nro_documento.
			$datos['nro_documento'] = $datos['cuit'];
			unset($datos['cuit']);
		}

		$this->tabla('documentos')->set($datos);
	}

	function nuevo_documento($datos)
	{
		$this->validar_datos_documentos($datos);

		if (isset($datos['cuit'])) {
			//Si se cargó el cuit se setea un documento más de la persona en el mismo campo nro_documento.
			$datos['nro_documento'] = $datos['cuit'];
			unset($datos['cuit']);
		}

		$this->tabla('documentos')->nueva_fila($datos);
	}

	function eliminar_documento_seleccionado()
	{
		if ($this->hay_documento_seleccionado()) {
			$id_fila = $this->tabla('documentos')->get_cursor();
			$this->tabla('documentos')->eliminar_fila($id_fila);
		}
	}

	function get_datos_documento_seleccionado()
	{
		if ($this->hay_documento_seleccionado()) {
			$datos_documento = $this->tabla('documentos')->get();
			if ($datos_documento['tipo_documento'] == persona::doc_CUIL) {
				$datos_documento['cuit'] = $datos_documento['nro_documento'];
			}
			return $datos_documento;
		}
	}

	function get_datos_documentos()
	{
		return $this->tabla('documentos')->get_filas();
	}

	//-------------------------------------------------------------------

	function seleccionar_contacto($id_fila_contacto)
	{
		$this->tabla('contactos')->set_cursor($id_fila_contacto);
	}

	function deseleccionar_contacto()
	{
		$this->tabla('contactos')->resetear_cursor();
	}

	function hay_contacto_seleccionado()
	{
		return $this->tabla('contactos')->hay_cursor();
	}

	function set_datos_contacto($datos)
	{
		$this->validar_datos_contactos($datos);
		$this->tabla('contactos')->set($datos);
	}

	function nuevo_contacto($datos)
	{
		$this->validar_datos_contactos($datos);
		$this->tabla('contactos')->nueva_fila($datos);
	}

	function eliminar_contacto_seleccionado()
	{
		if ($this->hay_contacto_seleccionado()) {
			$id_fila = $this->tabla('contactos')->get_cursor();
			$this->tabla('contactos')->eliminar_fila($id_fila);
		}
	}

	function get_datos_contacto_seleccionado()
	{
		if ($this->hay_contacto_seleccionado()) {
			return $this->tabla('contactos')->get();
		}
	}

	function get_datos_contactos()
	{
		return $this->tabla('contactos')->get_filas();
	}

	//-------------------------------------------------------------------

	function seleccionar_allegado($id_fila_allegado)
	{
		$this->tabla('allegados')->set_cursor($id_fila_allegado);
	}

	function deseleccionar_allegado()
	{
		$this->tabla('allegados')->resetear_cursor();
	}

	function hay_allegado_seleccionado()
	{
		return $this->tabla('allegados')->hay_cursor();
	}

	function set_datos_allegado($datos)
	{
		$this->actualizar_cabecera_datos_censales();
		if (isset($datos['localidad'])) {
			if (stripos($datos['localidad'], ':')) {
				$claves = $datos['localidad'];
				$datos['codigo_postal'] = guarani_texto::extraer_subclave($claves, 1);
				$datos['localidad'] = guarani_texto::extraer_subclave($claves);
			}
		} else {
			/**
			 * Al ef localidad se le reseteó el valor, por lo cual el código postal también debe resetearse.
			 * Es necesario resetear el valor en el servidor, ya que aunque se haya reseteado en el cliente (en la extensión
			 * del form), al ser un ef fijo el cambio no es tomado en cuenta.
			 */
			$datos['codigo_postal'] = '';
		}
		$this->tabla('allegados')->set($datos);
	}

	function nuevo_allegado($datos)
	{
		$this->actualizar_cabecera_datos_censales();
		if (stripos($datos['localidad'], ':')) {
			$claves = $datos['localidad'];
			$datos['codigo_postal'] = guarani_texto::extraer_subclave($claves, 1);
			$datos['localidad'] = guarani_texto::extraer_subclave($claves);
		}
		$this->tabla('allegados')->nueva_fila($datos);
	}

	function eliminar_allegado_seleccionado()
	{
		$this->actualizar_cabecera_datos_censales();
		if ($this->hay_allegado_seleccionado()) {
			$id_fila = $this->tabla('allegados')->get_cursor();
			$this->tabla('allegados')->eliminar_fila($id_fila);
		}
	}

	function get_datos_allegado_seleccionado()
	{
		if ($this->hay_allegado_seleccionado()) {
			return $this->tabla('allegados')->get();
		}
	}

	function get_datos_allegados()
	{
		return $this->tabla('allegados')->get_filas();
	}

	//-------------------------------------------------------------------

	function seleccionar_comentario_bitacora($id_fila_bitacora)
	{
		$this->tabla('bitacora')->set_cursor($id_fila_bitacora);
	}

	function deseleccionar_comentario_bitacora()
	{
		$this->tabla('bitacora')->resetear_cursor();
	}

	function hay_comentario_bitacora_seleccionado()
	{
		return $this->tabla('bitacora')->hay_cursor();
	}

	function set_datos_bitacora($datos)
	{
		$this->tabla('bitacora')->set($datos);
	}

	function nuevo_comentario_bitacora($datos)
	{
		$this->tabla('bitacora')->nueva_fila($datos);
	}

	function eliminar_comentario_bitacora_seleccionado()
	{
		if ($this->hay_comentario_bitacora_seleccionado()) {
			$id_fila = $this->tabla('bitacora')->get_cursor();
			$this->tabla('bitacora')->eliminar_fila($id_fila);
		}
	}

	function get_datos_comentario_bitacora_seleccionado()
	{
		if ($this->hay_comentario_bitacora_seleccionado()) {
			return $this->tabla('bitacora')->get();
		}
	}

	function get_datos_bitacora()
	{
		$datos = $this->tabla('bitacora')->get_filas();
		$salida = array();
		foreach ($datos as $dato) {
			/**
			 * Sólo se muestran registros visibles para TODOS o usuarios de GESTIÓN
			 */
			if (in_array($dato['visibilidad'], array(persona::bitacora_visibilidad_gestion, persona::bitacora_visibilidad_todos))) {
				$salida[] = $dato;
			}
		}
		return $salida;
	}

	//-------------------------------------------------------------------

	function seleccionar_estudio($id_fila_dato_estudios)
	{
		$this->tabla('datos_estudios')->set_cursor($id_fila_dato_estudios);
	}

	function deseleccionar_estudio()
	{
		$this->tabla('datos_estudios')->resetear_cursor();
	}

	function hay_estudio_seleccionado()
	{
		return $this->tabla('datos_estudios')->hay_cursor();
	}

	function set_datos_estudio($datos)
	{
		$this->validar_datos_estudios($datos);
		$this->tabla('datos_estudios')->set($datos);
	}

	function nuevo_estudio($datos)
	{
		$this->validar_datos_estudios($datos);
		$this->tabla('datos_estudios')->nueva_fila($datos);
	}

	function eliminar_estudio_seleccionado()
	{
		if ($this->hay_estudio_seleccionado()) {
			$id_fila = $this->tabla('datos_estudios')->get_cursor();
			$this->tabla('datos_estudios')->eliminar_fila($id_fila);
		}
	}

	function get_datos_estudio_seleccionado()
	{
		if ($this->hay_estudio_seleccionado()) {
			return $this->tabla('datos_estudios')->get();
		}
	}

	function get_datos_estudios()
	{
		return $this->tabla('datos_estudios')->get_filas();
	}

	//-------------------------------------------------------------------

	function seleccionar_ant_docente($id_fila_ant_docente)
	{
		$this->tabla('ant_docentes')->set_cursor($id_fila_ant_docente);
	}

	function deseleccionar_ant_docente()
	{
		$this->tabla('ant_docentes')->resetear_cursor();
	}

	function hay_ant_docente_seleccionado()
	{
		return $this->tabla('ant_docentes')->hay_cursor();
	}

	function set_datos_ant_docente($datos)
	{
		$this->validar_datos_ant_docentes($datos);
		$this->tabla('ant_docentes')->set($datos);
	}

	function nuevo_ant_docente($datos)
	{
		$this->validar_datos_ant_docentes($datos);
		$this->tabla('ant_docentes')->nueva_fila($datos);
	}

	function eliminar_ant_docente_seleccionado()
	{
		if ($this->hay_ant_docente_seleccionado()) {
			$id_fila = $this->tabla('ant_docentes')->get_cursor();
			$this->tabla('ant_docentes')->eliminar_fila($id_fila);
		}
	}

	function get_datos_ant_docente_seleccionado()
	{
		if ($this->hay_ant_docente_seleccionado()) {
			return $this->tabla('ant_docentes')->get();
		}
	}

	function get_datos_ant_docentes()
	{
		return $this->tabla('ant_docentes')->get_filas();
	}

	//----------------------------------------------------------------------

	function seleccionar_ant_profesional($id_fila_ant_profesional)
	{
		$this->tabla('ant_profesionales')->set_cursor($id_fila_ant_profesional);
	}

	function deseleccionar_ant_profesional()
	{
		$this->tabla('ant_profesionales')->resetear_cursor();
	}

	function hay_ant_profesional_seleccionado()
	{
		return $this->tabla('ant_profesionales')->hay_cursor();
	}

	function set_datos_ant_profesional($datos)
	{
		$this->validar_datos_ant_profesional($datos);
		$this->tabla('ant_profesionales')->set($datos);
	}

	function nuevo_ant_profesional($datos)
	{
		$this->validar_datos_ant_profesional($datos);
		$this->tabla('ant_profesionales')->nueva_fila($datos);
	}

	function eliminar_ant_profesional_seleccionado()
	{
		if ($this->hay_ant_profesional_seleccionado()) {
			$id_fila = $this->tabla('ant_profesionales')->get_cursor();
			$this->tabla('ant_profesionales')->eliminar_fila($id_fila);
		}
	}

	function get_datos_ant_profesional_seleccionado()
	{
		if ($this->hay_ant_profesional_seleccionado()) {
			return $this->tabla('ant_profesionales')->get();
		}
	}

	function get_datos_ant_profesionales()
	{
		return $this->tabla('ant_profesionales')->get_filas();
	}

	//----------------------------------------------------------------------

	function set_datos_usuario($datos)
	{
		$this->validar_datos_usuario($datos);
		$this->tabla('personas')->set($datos);
		$tipos_usuario = rs_convertir_asociativo($this->get_datos_tipos_usuario(), array('tipo_usuario'), apex_datos_clave_fila);
		if ($datos['usuario_administrador_autogestion'] && !isset($tipos_usuario[usuario::tipo_admin_3w])) {
			$this->agregar_tipo_usuario(array('tipo_usuario' => usuario::tipo_admin_3w));
		} elseif (!$datos['usuario_administrador_autogestion'] && isset($tipos_usuario[usuario::tipo_admin_3w])) {
			$this->eliminar_tipo_usuario($tipos_usuario[usuario::tipo_admin_3w]);
		}
	}

	function get_datos_usuario()
	{
		$datos_usuario = $this->tabla('personas')->get();
		$tipos_usuario = rs_convertir_asociativo($this->get_datos_tipos_usuario(), array('tipo_usuario'), apex_datos_clave_fila);
		if (isset($tipos_usuario[usuario::tipo_admin_3w])) {
			$datos_usuario['usuario_administrador_autogestion'] = 1;
		}
		return $datos_usuario;
	}
    
	//----------------------------------------------------------------------

	function set_datos_usuario_arai($datos)
	{
		$this->tabla('personas')->set($datos);
	}

	function get_datos_usuario_arai()
	{
		return $this->tabla('personas')->get();
	}

	//---------------------------------------------------------------------

	function get_datos_tipos_usuario()
	{
		return $this->tabla('tipo_usuario')->get_filas();
	}

	function agregar_tipo_usuario($datos)
	{
		$this->tabla('tipo_usuario')->nueva_fila($datos);
	}

	function eliminar_tipo_usuario($id_fila)
	{
		$this->tabla('tipo_usuario')->eliminar_fila($id_fila);
	}

	//---------------------------------------------------------------------

	function set_datos_grupos_acceso($datos)
	{
		$this->validar_grupos_acceso_duplicados($datos);
		$tipos_usuario = rs_convertir_asociativo($this->get_datos_tipos_usuario(), array('tipo_usuario'), apex_datos_clave_fila);
		$tipo_usuario_gestion = false;
		foreach ($datos as $clave => $valor) {
			if ($valor[apex_ei_analisis_fila] == 'B') {
				$grupo_acceso = $this->tabla('grupo_acc')->get_fila($clave);
				$usuario_grupo_acc = $grupo_acceso['usuario_grupo_acc'];
				$usuario_perfil_datos = array();
			} else {
				$tipo_usuario_gestion = true;
				$usuario_grupo_acc = $valor['usuario_grupo_acc'];
				$usuario_perfil_datos = $valor['usuario_perfil_datos'];
			}
			$this->set_perfiles_datos(usuario::tipo_gestion, $usuario_grupo_acc, $usuario_perfil_datos);
			$datos[$clave]['tipo_usuario'] = usuario::tipo_gestion;
		}
		if ($tipo_usuario_gestion && !isset($tipos_usuario[usuario::tipo_gestion])) {
			$this->agregar_tipo_usuario(array('tipo_usuario' => usuario::tipo_gestion));
		} elseif (!$tipo_usuario_gestion && isset($tipos_usuario[usuario::tipo_gestion])) {
			$this->eliminar_tipo_usuario($tipos_usuario[usuario::tipo_gestion]);
		}
		$this->tabla('grupo_acc')->procesar_filas($datos);
	}

	function get_datos_grupos_acceso($condiciones)
	{
		return $this->tabla('grupo_acc')->get_filas($condiciones);
	}

	//---------------------------------------------------------------------

	function set_perfiles_datos($tipo_usuario, $usuario_grupo_acc, $datos)
	{
		$this->validar_dominio_perfiles_datos($usuario_grupo_acc, $datos);
		$condiciones = array('tipo_usuario' => $tipo_usuario, 'usuario_grupo_acc' => $usuario_grupo_acc);
		$perfiles_datos = $this->tabla('perfiles_datos')->get_filas($condiciones);
		$perfiles_aplanados = aplanar_matriz($perfiles_datos, 'usuario_perfil_datos');
		$perfiles_a_borrar = array_diff($perfiles_aplanados, $datos);
		$perfiles_a_insertar = array_diff($datos, $perfiles_aplanados);

		$perfiles_asociativos = rs_convertir_asociativo($perfiles_datos, array('usuario_perfil_datos'), apex_datos_clave_fila);
		foreach ($perfiles_a_borrar as $perfil) {
			$this->tabla('perfiles_datos')->eliminar_fila($perfiles_asociativos[$perfil]);
		}

		foreach ($perfiles_a_insertar as $perfil) {
			$this->tabla('perfiles_datos')->nueva_fila(array('tipo_usuario' => $tipo_usuario, 'usuario_grupo_acc' => $usuario_grupo_acc, 'usuario_perfil_datos' => $perfil));
		}
	}

	function get_perfiles_datos($condiciones = null)
	{
		return $this->tabla('perfiles_datos')->get_filas($condiciones);
	}

	//---------------------------------------------------------------------

	function get_nro_documento_principal()
	{
		$documentos = $this->get_datos_documentos();
		if (!empty($documentos)) {
			$ordenes = aplanar_matriz($documentos, 'orden_principal');
			$doc_principal = current($this->tabla('documentos')->get_filas(array('orden_principal' => min($ordenes))));
		}
		return isset($doc_principal['nro_documento']) ? $doc_principal['nro_documento'] : null;
	}

	function get_datos_nro_documento_principal()
	{
		$documentos = $this->get_datos_documentos();
		if (!empty($documentos)) {
			$ordenes = aplanar_matriz($documentos, 'orden_principal');
			$doc_principal = current($this->tabla('documentos')->get_filas(array('orden_principal' => min($ordenes))));
		}
		return isset($doc_principal['nro_documento']) ? $doc_principal : array();
	}

	//--------------------------------------------------------------------------
	/**
	 * Elimina los recordatorios de usuario.
	 */
	function eliminar_recordatorios_usuario()
	{
		guarani::act('act_personas')->eliminar_recordatorios_usuario($this->tabla('personas')->get_columna('persona'));
	}

	/**
	 * Elimina los registros de login en Autogestión
	 */
	function eliminar_aud_login_autogestion()
	{
		guarani::act('act_personas')->eliminar_aud_login_autogestion($this->tabla('personas')->get_columna('persona'));
	}

	/**
	 * Elimina los registros de login en Autogestión
	 */
	function eliminar_configuracion()
	{
		guarani::act('act_personas')->eliminar_configuracion($this->tabla('personas')->get_columna('persona'));
	}

	/**
	 * Elimina los requisitos digitales presentados
	 */
	function eliminar_requisitos_digitales()
	{
		guarani::act('act_personas')->eliminar_requisitos_digitales($this->tabla('personas')->get_columna('persona'));
	}

	/**
	 * Elimina los mensajes de los cuales la persona es destinataria.
	 */
	function eliminar_mensaje_destinatarios()
	{
		guarani::act('act_personas')->eliminar_mensaje_destinatarios($this->tabla('personas')->get_columna('persona'));
	}

	/**
	 * Elimina las encuestas de la persona.
	 */
	function eliminar_encuestas_pendientes()
	{
		guarani::act('act_personas')->eliminar_encuestas_pendientes($this->tabla('personas')->get_columna('persona'));
	}
	/**
	 * Elimina el log de inscripciones a propuestas
	 */
	function eliminar_inscripcion_propuesta_log()
	{
		guarani::act('act_personas')->eliminar_inscripcion_propuesta_log($this->tabla('personas')->get_columna('persona'));
	}

	//---------------------------------------------------------------------
	/**
	 *  Determina si los datos correspondientes al datos tabla de persona ingresados por el usuario son válidos (invocando a 
	 *  diferentes métodos de validación de datos determinados). De no ser así lanza la excepción con los mensajes correspondientes.
	 * 	 @param array	datos	son los datos sobre los cuales se realizan las validaciones, en formato recorset.
	 */
	function validar_datos_personas($datos)
	{
		if (isset($datos['fecha_nacimiento'])) {
			$this->validar_fecha_nacimiento($datos['fecha_nacimiento']);
		}
		if (isset($datos['localidad_nacimiento'])) {
			$this->validar_pais_nacimiento($datos['localidad_nacimiento'], $datos['nacionalidad']);
		}
		if ($datos['nacionalidad'] == persona::nac_extranjero) {
			$this->validar_campos_tipo_visa($datos);
			$this->validar_visa_obligatoria($datos['pais_origen'], $datos['tipo_visa'], $datos['otorgamiento_visa']);
			$this->validar_fechas_congruentes($datos['otorgamiento_visa'], $datos['vencimiento_visa'], 'visa');
			$this->validar_campos_tipo_residencia($datos);
			$this->validar_fechas_congruentes($datos['otorgamiento_residencia'], $datos['vencimiento_residencia'], 'residencia');
		} else {
			// En caso de que la nacionalidad no sea extranjera, chequea que a los campos referentes a extranjeros no se les haya asignado un valor.
			$claves = array('pais_origen', 'fecha_ingreso_pais', 'tipo_visa', 'otorgamiento_visa', 'vencimiento_visa', 'tipo_residencia', 'otorgamiento_residencia', 'vencimiento_residencia');
			$this->limpiar_campos_no_editables($datos, $claves);
		}
                
                // Si '¿Pertenece a algún pueblo originario?' es 'Sí'
                if ($datos['pertenece_pueblo_originario'] == comunes::si) {
                    $this->validar_pueblo_originario($datos['pueblo_originario'], $datos['pueblo_originario_otro']);
                }

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	/**
	 *  Determina si la fecha de nacimiento es válida:
	 * 		+ que la persona ingresada sea mayor de 15 años de edad.
	 * 		+ que no sea mayor a 150 años
	 * 	 @param date fecha	es la fecha de nacimiento de la persona que se está editando o ingresando.
	 */
	function validar_fecha_nacimiento($fecha)
	{
		$edad = guarani_fecha::get_edad($fecha);
		if ($edad < 15) {
			//$this->set_error('800_dato_invalido', array('fecha de nacimiento', 'la persona debe ser mayor a 15 años'));
		} else {
			$fecha_limite = guarani_fecha::get_fecha_desplazada(guarani_fecha::get_hoy(), -150, 'anio');
			if (guarani_fecha::es_menor($fecha, $fecha_limite)) {
				$this->set_error('800_dato_invalido', array('fecha de nacimiento', 'la persona no puede ser mayor de 150 años'));
			}
		}
	}

	/**
	 * Valida que la localidad de nacimiento sea correcta de acuerdo a la nacionalidad (argentino, extranjero).
	 *
	 * @param integer $localidad
	 * @param integer $nacionalidad
	 */
	function validar_pais_nacimiento($localidad, $nacionalidad)
	{
		$pais_localidad = toba::consulta_php('co_mug')->get_localidad_pais($localidad);
		if (!empty($pais_localidad)) {
			$pais_nacimiento = $pais_localidad['valor'];
			if (($nacionalidad == persona::nac_argentino && $pais_nacimiento != persona::pais_argentina) ||
					($nacionalidad != persona::nac_argentino && $pais_nacimiento == persona::pais_argentina)) {
				$this->set_error('800_dato_dep_invalido', array('localidad de nacimiento', 'la nacionalidad de la persona'));
			}
		}
	}

	/**
	 *  Determina si están cargados los campos obligatorios referentes a la visa según su tipo (sus fechas de otorgamiento y vencimiento).
	 * 	 @param array	datos	son los datos del documento ingresado por el usuario (en formato recordset).
	 */
	function validar_campos_tipo_visa($datos)
	{
		if (isset($datos['tipo_visa'])) {
			if ($datos['tipo_visa'] == persona::visa_temporal) {
				if (!isset($datos['otorgamiento_visa']) || !isset($datos['vencimiento_visa'])) {
					$this->set_error('800_dato_dep_obligatorio', array('fecha de otorgamiento y fecha de vencimiento de la visa', 'tipo de visa'));
				}
			} else {   // la visa es de tipo permanente. 
				if (!isset($datos['otorgamiento_visa'])) {
					$this->set_error('800_dato_dep_obligatorio', array('fecha de otorgamiento', 'tipo de visa'));
				} else {
					parent::limpiar_campos_no_editables($datos, array('vencimiento_visa'));
				}
			}
		} else {
			parent::limpiar_campos_no_editables($datos, array('otorgamiento_visa', 'vencimiento_visa'));
		}
	}

	/**
	 *  Determina si están cargados los campos obligatorios referentes a la residencia según su tipo (sus fechas de otorgamiento y vencimiento).
	 *  @param	array	datos	son los datos del documento ingresado por el usuario (en formato recordset).
	 */
	function validar_campos_tipo_residencia($datos)
	{
		if (isset($datos['tipo_residencia'])) {
			if ($datos['tipo_residencia'] == persona::residencia_precaria || $datos['tipo_residencia'] == persona::residencia_temporal) {
				if (!isset($datos['otorgamiento_residencia']) || !isset($datos['vencimiento_residencia'])) {
					$this->set_error('800_dato_dep_obligatorio', array('fecha de otorgamiento y fecha de vencimiento de la residencia', 'tipo de residencia'));
				}
			} elseif ($datos['tipo_residencia'] == persona::residencia_definitiva) {
				if (!isset($datos['otorgamiento_residencia'])) {
					$this->set_error('800_dato_dep_obligatorio', array('fecha de otorgamiento', 'tipo de residencia'));
				} else {
					parent::limpiar_campos_no_editables($datos, array('vencimiento_residencia'));
				}
			} else {  // el tipo de residencia es sin residencia
				parent::limpiar_campos_no_editables($datos, array('otorgamiento_residencia', 'vencimiento_residencia'));
			}
		} else {
			parent::limpiar_campos_no_editables($datos, array('otorgamiento_residencia', 'vencimiento_residencia'));
		}
	}

	/**
	 *  Determina si el campo visa es obligatorio de acuerdo al país del documento.
	 * 	 @param integer 	pais_doc	es el id del país del documento cargado por el usuario.
	 */
	function validar_visa_obligatoria($pais_doc, $tipo_visa, $otorgamiento_visa)
	{
		$datos_pais = current(toba::consulta_php('co_personas')->get_paises_habilitados($pais_doc));
		$necesita_visa = (!empty($datos_pais)) ? $datos_pais['necesita_visa'] : comunes::no;

		if ($necesita_visa == comunes::si && !isset($tipo_visa)) {
			$this->set_error('800_dato_dep_obligatorio', array('tipo de visa', 'nacionalidad y país del documento'));
		}
	}

	/**
	 *  Determina si las fechas de otorgamiento y vencimiento de un documento son consistentes.
	 * 	 @param date 	fecha_otorgamiento	es la fecha de otorgamiento del documento cargada por el usuario.
	 *  @param date 	fecha_vencimiento	es la fecha de vencimiento del documento cargada por el usuario.
	 *  @param string 	nombre_doc	es el nombre del documento para el cual se está chequeando la congruencia de fechas.
	 */
	function validar_fechas_congruentes($fecha_otorgamiento, $fecha_vencimiento, $nombre_doc)
	{
		if (guarani_fecha::es_menor($fecha_vencimiento, $fecha_otorgamiento)) {
			$this->set_error('800_control_fechas', array(' de vencimiento de ' . $nombre_doc, 'menor', ' de otorgamiento'));
		}

		if (guarani_fecha::es_mayor($fecha_otorgamiento, guarani_fecha::get_hoy())) {
			$this->set_error('800_control_fechas', array(' de otorgamiento de ' . $nombre_doc, 'mayor', ' actual'));
		}

		if (guarani_fecha::es_menor($fecha_vencimiento, guarani_fecha::get_hoy())) {
			$this->set_error('800_control_fechas', array(' de vencimiento de ' . $nombre_doc, 'menor', ' actual'));
		}
	}

        /**
	 *  Valida datos de pueblo originario.
	 *  @param integer $pueblo_originario
	 *  @param string $pueblo_originario_otro
	 */
	function validar_pueblo_originario($pueblo_originario, $pueblo_originario_otro)
	{

                // Si '¿Cuál?' es '-- Seleccione --'
		if (is_null($pueblo_originario)) {
                    $this->set_error('800_dato_dep_obligatorio', ['campo ¿Cuál?', '¿Pertenece a algún pueblo originario?']);
		}

                // Si '¿Cuál?' es 'Otros Especifique' y 'Especificar' es vacío
                if ($pueblo_originario == comunes::pueblo_originario_otros && is_null($pueblo_originario_otro)) {
                    $this->set_error('800_dato_dep_obligatorio', ['campo Especificar', '¿Cuál?']);
                }
	}

	//-------------------------------------------------------------------
	/**
	 *  Determina si los datos correspondientes al datos tabla de documentos ingresados por el usuario son válidos (invocando a 
	 *  diferentes métodos de validación de datos determinados). De no ser así lanza la excepción con los mensajes correspondientes.
	 * 	 @param array	datos	son los datos sobre los cuales se realizan las validaciones, en formato recorset.
	 */
	function validar_datos_documentos($datos)
	{
		if ($datos['tipo_documento'] == persona::doc_CUIL) {
			//Se controla que no se haya ingresado el nro de documento (si se saltearon los controles js).
			$this->limpiar_campos_no_editables($datos, array('nro_documento'));
		} else {
			$this->validar_nro_documento($datos['nro_documento'], $datos['tipo_documento'], $datos['pais_documento']);
			//Se controla que no se haya ingresado el nro de documento (si se saltearon los controles js).
			$this->limpiar_campos_no_editables($datos, array('cuit'));

			if ($datos['tipo_documento'] == persona::doc_dni_temporal) {
				$this->validar_fechas_congruentes($datos['fecha_otorgamiento'], $datos['fecha_vencimiento'], 'DNI Temporal');
			} else {
				$claves = array('fecha_otorgamiento', 'fecha_vencimiento');
				$this->limpiar_campos_no_editables($datos, $claves);
			}
		}

		$datos_persona = $this->get_datos_personas();
		$persona = null;
		if (isset($datos_persona['persona'])) {
			$persona = $datos_persona['persona'];
		}
		$this->validar_tipo_documento($datos['tipo_documento'], $datos_persona['nacionalidad'], $datos['pais_documento']);
		$this->validar_tipo_doc_sexo($datos['tipo_documento'], $datos_persona['sexo']);
		$this->validar_pais_documento($datos['pais_documento'], $datos_persona['nacionalidad']);
		$this->validar_tipo_y_pais_documento($datos['pais_documento'], $datos['tipo_documento']);
		$nro_documento = ($datos['tipo_documento'] == persona::doc_CUIL) ? $datos['cuit'] : $datos['nro_documento'];
		$this->validar_persona_unica($datos['tipo_documento'], $nro_documento, $datos['pais_documento'], $persona);
		$this->validar_doc_persona_no_expulsada($datos['pais_documento'], $datos['tipo_documento'], $nro_documento);
		$this->validar_doc_persona_no_sancionada($datos['pais_documento'], $datos['tipo_documento'], $nro_documento);

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	/**
	 *  Verifica si la persona se encuentra expullsada de otra institución.
	 * 	 @param integer pais_doc	es el id del pais del documento ingresado por el usuario.
	 * 	 @param integer tipo_doc	es el id del tipo de documento que eligió el usuario.
	 *  @param integer nro_doc		es el nro de documento que eligió el usuario.
	 */
	function validar_doc_persona_no_expulsada($pais_doc, $tipo_doc, $nro_doc)
	{
		if (!is_null($nro_doc) && toba::consulta_php('co_personas')->persona_expulsada_de_otra_institucion($pais_doc, $tipo_doc, $nro_doc)) {
			$this->set_error('800_entidad_invalida', array('persona', ' que fue expulsada de otra institución'));
		}
	}

	/**
	 *  Verifica si la persona se encuentra sancionada.
	 * 	 @param integer pais_doc	es el id del pais del documento ingresado por el usuario.
	 * 	 @param integer tipo_doc	es el id del tipo de documento que eligió el usuario.
	 *  @param integer nro_doc		es el nro de documento que eligió el usuario.
	 */
	function validar_doc_persona_no_sancionada($pais_doc, $tipo_doc, $nro_doc)
	{
		if (!is_null($nro_doc) && toba::consulta_php('co_personas')->persona_sancionada_por_documento($pais_doc, $tipo_doc, $nro_doc)) {
			$this->set_error('800_entidad_invalida', array('persona', ' que se encuentra sancionada'));
		}
	}

	/**
	 *  Verifica si la combinación Tipo Documento + País Documento es única por persona.
	 * 	 @param integer pais_doc	es el id del pais del documento ingresado por el usuario.
	 * 	 @param integer tipo_doc	es el id del tipo de documento que eligió el usuario.
	 */
	function validar_tipo_y_pais_documento($pais_doc, $tipo_doc)
	{
		$id_fila = $this->tabla('documentos')->get_cursor();
		$documentos = $this->tabla('documentos')->get_filas();
		foreach ($documentos as $documento) {
			if ($documento['pais_documento'] == $pais_doc && $documento['tipo_documento'] == $tipo_doc && (is_null($id_fila) || $id_fila != $documento[apex_datos_clave_fila])) {
				$this->set_error('800MAT_tipo_pais_documento_repetido', array($documento['tipo_nombre'], $documento['pais_nombre']));
			}
		}
	}

	/**
	 *  Determina si el tipo de documento es válido de acuerdo a la nacionalidad de la persona y el país del documento (por si permite cédula).
	 * 	 @param integer 	tipo_doc	es el id del tipo de documento cargado por el usuario.
	 *  @param integer 	nacionalidad	es el id de la nacionalidad de la persona que se está editando o ingresando.
	 *  @param integer 	pais_doc	es el id del país del documento cargado por el usuario.
	 */
	function validar_tipo_documento($tipo_doc, $nacionalidad, $pais_doc)
	{
		$datos_pais = current(toba::consulta_php('co_personas')->get_paises_habilitados($pais_doc));
		$permite_cedula = (!empty($datos_pais)) ? $datos_pais['permite_cedula'] : comunes::no;

		switch ($nacionalidad) {
			case persona::nac_argentino:
				$tipos_validos = array(persona::doc_dni, persona::doc_cedula, persona::doc_LE, persona::doc_LC, persona::doc_CUIL, persona::doc_pasaporte);
				break;
			case persona::nac_extranjero:
				$tipos_validos = array(persona::doc_pasaporte, persona::doc_dni, persona::doc_dni_temporal, persona::doc_CUIL,
					persona::doc_mercosur, persona::doc_diplomatica, persona::doc_ciudadania, persona::doc_identidad);
				if ($permite_cedula == comunes::si) {
					$tipos_validos[] = persona::doc_cedula;
				}
				break;
			case persona::nac_naturalizado:
			case persona::nac_por_opcion:
				$tipos_validos = array(persona::doc_dni, persona::doc_cedula, persona::doc_CUIL);
				break;
		}

		if (!in_array($tipo_doc, $tipos_validos)) {
			$this->set_error('800_dato_dep_invalido', array('tipo de documento', 'nacionalidad de la persona'));
		}
	}

	/**
	 *  Determina si el tipo de documento es válido de acuerdo al sexo de la persona.
	 * 	 @param integer 	tipo_doc	es el id del tipo de documento cargado por el usuario.
	 *  @param integer 	sexo		es el id del sexo de la persona que se está editando o ingresando.
	 */
	function validar_tipo_doc_sexo($tipo_doc, $sexo)
	{
		if (($sexo == persona::sexo_femenino && $tipo_doc == persona::doc_LE) ||
				($sexo == persona::sexo_masculino && $tipo_doc == persona::doc_LC)) {
			$this->set_error('800_dato_dep_invalido', array('tipo de documento', 'sexo de la persona'));
		}
	}

	/**
	 *  Valida que el nro de documento sea correcto de acuerdo al tipo de documento y los caracteres que este permite.
	 *  Determina si el número de documento contenga solo números y no caracteres si el tipo es numérico. 
	 *  Si el tipo de dato asociado al tipo de documento es alfanumérico chequea que tenga sólo letras y números.
	 *  Si el tipo de documento es cuit/cuil, entonces no se hace nada ya que se valida por el ef_cuit.
	 *  @param string	nro_doc		es el nro de documento a validar, cargado por el usuario (puede ser numérico o alfanumérico).
	 * 	@param integer	tipo_doc	es el id del tipo de documento cargado por el usuario.
	 * 	@param integer	pais_documento	es el id pais de documento cargado por el usuario.
	 */
	function validar_nro_documento($nro_doc, $tipo_doc, $pais_documento)
	{
		
		$tipos = persona::get_tipos_documentos_x_pais($pais_documento);			
		$es_valido = preg_match('/' . $tipos[$tipo_doc."_".$pais_documento]['exp_regular_validacion'] . '/', $nro_doc);
		$msg = $tipos[$tipo_doc."_".$pais_documento]['exp_regular_mensaje'];
			

		if (!$es_valido) {
			$this->set_error($msg);
		}
	}

	/**
	 *  Determina si el país del documento ingresado por el usuario es válido de acuerdo a la nacionalidad de la persona que se está editando o agregando.
	 *  @param integer	pais_doc		es el id del pais del documento cargado por el usuario.
	 * 	 @param integer	nacionalidad	es el id de la nacionalidad de la persona que se está editando o agregando.
	 */
	function validar_pais_documento($pais_doc, $nacionalidad)
	{
		if ($nacionalidad == persona::nac_argentino && $pais_doc != persona::pais_argentina) {
			$this->set_error('800_dato_dep_invalido', array('país del documento', 'nacionalidad de la persona'));
		}
	}

	/**
	 * Determina que en los documentos cargados por el usuario se haya ingresado el cuit/cuil dado que este es requerido
	 * como obligatorio. Sólo puede realizarse como paso previo a la sincronización (pre-sincronizar), ya que es necesario 
	 * que el usuario haya terminado de ingresar documentos.
	 */
	function validar_carga_cuil()
	{
		$persona = $this->get_datos_personas();
		$carga_ok = true;
		if ($persona['nacionalidad'] == persona::nac_argentino) {
			$documentos = $this->get_datos_documentos();
			$carga_ok = false;
			foreach ($documentos as $doc) {
				//Si uno de los documentos cargados por el usuario es el cuil, exigido como obligatorio, entonces no hay error.
				if ($doc['tipo_documento'] == persona::doc_CUIL) {
					$carga_ok = true;
					break;
				}
			}
		}
		if (!$carga_ok) {
			$this->set_error('800_dato_no_seteado', array('documento', 'tipo CUIT/CUIL'));
		}
	}

	//---------------------------------------------------------------------
	/**
	 *  Determina si los datos correspondientes al datos tabla de datoe estudios ingresados por el usuario son válidos 
	 *  (invocando a diferentes métodos de validación de datos determinados). De no ser así lanza la excepción con los 
	 *  mensajes correspondientes.
	 * 	 @param array	datos	son los datos sobre los cuales se realizan las validaciones, en formato recorset.
	 */
	function validar_datos_estudios($datos)
	{
		if (isset($datos['institucion'])) {
			//En caso de que la Institución tenga un valor se chequea que al campo Otra Institución no se les haya asignado un valor.
			$this->limpiar_campos_no_editables($datos, array('institucion_otra'));
		}

		if (isset($datos['titulo'])) {
			//En caso de que el Título tenga un valor se chequea que al campo Otro Título no se les haya asignado un valor.
			$this->limpiar_campos_no_editables($datos, array('titulo_otro'));
		}

		$this->validar_fechas_estudio($datos['fecha_ingreso'], $datos['fecha_egreso']);
		$this->validar_nivel_duplicado($datos['nivel_estudio']);

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_fechas_estudio($fecha_ingreso, $fecha_egreso)
	{
		if (!empty($fecha_ingreso)) {
			if (guarani_fecha::es_mayor($fecha_ingreso, guarani_fecha::get_hoy())) {
				$this->set_error('800_control_fechas', array('de ingreso', 'mayor', 'actual'));
			}
			if (!empty($fecha_egreso) && guarani_fecha::es_menor($fecha_egreso, $fecha_ingreso)) {
				$this->set_error('800_control_fechas', array('de egreso', 'menor', 'de ingreso'));
			}
		}
	}

	function validar_nivel_duplicado($nivel_estudio)
	{
		$id_fila = $this->tabla('datos_estudios')->get_cursor();
		$estudios = $this->tabla('datos_estudios')->get_filas();
		//Se debe permitir cargar mas de un título para los niveles: Universitario(5), Posgrado(6) y Terciario(4)
		$estudiosDuplicados = array(4, 5, 6);
		foreach ($estudios as $estudio) {
			if (!in_array($estudio['nivel_estudio'], $estudiosDuplicados)) {
				if ($estudio['nivel_estudio'] == $nivel_estudio && (is_null($id_fila) || $id_fila != $estudio[apex_datos_clave_fila])) {
					$this->set_error('dato_duplicado', array('nivel de estudio', 'persona'));
					break;
				}
			}
		}
	}

	//---------------------------------------------------------------------
	/**
	 *  Determina si los datos correspondientes al datos tabla de antecedentes docentes ingresados por el usuario son válidos 
	 *  (invocando a diferentes métodos de validación de datos determinados). De no ser así lanza la excepción con los 
	 *  mensajes correspondientes.
	 * 	 @param array	datos	son los datos sobre los cuales se realizan las validaciones, en formato recorset.
	 */
	function validar_datos_ant_docentes($datos)
	{
		if (isset($datos['institucion'])) {
			//En caso de que la Institución tenga un valor se chequea que al campo Otra Institución no se les haya asignado un valor.
			$this->limpiar_campos_no_editables($datos, array('institucion_otra'));
		}

		$this->validar_fechas_antecedente($datos['fecha_desde'], $datos['fecha_hasta']);

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_fechas_antecedente($fecha_desde, $fecha_hasta)
	{
		if (!empty($fecha_desde)) {
			if (guarani_fecha::es_mayor($fecha_desde, guarani_fecha::get_hoy())) {
				$this->set_error('800_control_fechas', array('desde', 'mayor', 'actual'));
			}
			if (!empty($fecha_hasta) && guarani_fecha::es_menor($fecha_hasta, $fecha_desde)) {
				$this->set_error('800_control_fechas', array('hasta', 'menor', 'desde'));
			}
		}
	}

	//---------------------------------------------------------------------

	function validar_datos_ant_profesional($datos)
	{
		$this->validar_fechas_antecedente($datos['fecha_desde'], $datos['fecha_hasta']);

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	//--------------------------------------------------------------------------
	/**
	 *  Determina si los datos correspondientes al datos tabla de contactos ingresados por el usuario son válidos (invocando a 
	 *  diferentes métodos de validación de datos determinados). De no ser así lanza la excepción con los mensajes correspondientes.
	 * 	 @param array	datos	son los datos sobre los cuales se realizan las validaciones, en formato recorset.
	 */
	function validar_datos_contactos($datos)
	{
		$this->validar_campos_tipo_contacto($datos['contacto_tipo'], $datos);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	/**
	 *  Determina si se completaron los datos obligatorios según el tipo de contacto ingresado por el usuario.
	 * 	 @param integer	$tipo	es el id del tipo de contacto ingresado por el usuario.
	 *  @param array	$datos	son los datos del contacto ingresado por el usuario.
	 */
	function validar_campos_tipo_contacto($tipo, $datos)
	{
		if ($tipo == persona::mail_institucional || $tipo == persona::mail_primario || $tipo == persona::mail_secundario) {
			if (!isset($datos['email'])) {
				$this->set_error('800_dato_dep_obligatorio', array('Email', 'tipo de contacto (email)'));
			}
		} elseif (persona::es_contacto_telefonico($tipo)) {
			if (!isset($datos['telefono_codigo_area']) || !isset($datos['telefono_numero'])) {
				$this->set_error('800_dato_dep_obligatorio', array('Código de Área y Número', 'tipo de contacto (telefónico)'));
			}
		} else {   //El tipo de contacto es de otro tipo.
			if (!isset($datos['otros_contactos'])) {
				$this->set_error('800_dato_dep_obligatorio', array('Descripción', 'tipo de contacto (otros contactos)'));
			}
		}
	}

	//--------------------------------------------------------------------------

	function validar_datos_usuario($datos)
	{
		$persona = $this->tabla('personas')->get_columna('persona');
		if (isset($datos['usuario']) && !toba::consulta_php('co_usuarios')->validar_nombre_usuario_unico($datos['usuario'], $persona)) {
			$this->set_error('dato_duplicado', array('nombre de usuario', 'persona'));
		}

		if (isset($datos['usuario']) && !usuario::validar_id_usuario_permitido($datos['usuario'])) {
			// Personalizar...
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_grupos_acceso_duplicados($datos)
	{   
        $grupos_acceso = array();
		$defecto = 0;  
        
        //Variable bandera para controlar si hubo un alta o modificacion.
        //Cuando no hay alta o modificacion no valido si hay perfil defecto.
        $alta_modificacion = false;
        
		foreach ($datos as $clave => $valor) {			
			if (isset($valor['grupo_acceso_default']) && $valor['grupo_acceso_default'] == comunes::si) {
				$defecto ++;
			}			
			if ($valor['apex_ei_analisis_fila'] != 'B') {
                $alta_modificacion = true;
				if (in_array($valor['usuario_grupo_acc'], $grupos_acceso)) {
					$this->set_error('800MAT_perfiles_funcionales_duplicados');
					return;
				} else {
					$grupos_acceso[] = $valor['usuario_grupo_acc'];
				}
			}
		}
		if ($defecto != 1 && guarani_arreglos::count($datos) > 0 && $alta_modificacion) {
			$this->set_error('800MAT_perfiles_funcionales_default');
		}
	}

	function validar_dominio_perfiles_datos($usuario_grupo_acc, $perfiles_datos)
	{
		$filtro = "apex_usuario_grupo_acc.usuario_grupo_acc = '$usuario_grupo_acc'";
		$datos_grupo_acc = current(toba::consulta_php('co_usuarios')->get_grupos_acceso($filtro));
		$dominio = null;
		foreach ($perfiles_datos as $perfil_datos) {
			$perfil_datos_restricciones = toba_proyecto_implementacion::get_perfil_datos_restricciones(toba::proyecto()->get_id(), $perfil_datos);
			foreach ($perfil_datos_restricciones as $perfil_datos_restriccion) {
				$dominio_unidad_gestion = toba::consulta_php('co_unidades_gestion')->get_dominio_unidad_gestion($perfil_datos_restriccion['clave']);
				if (!is_null($dominio) && $dominio != $dominio_unidad_gestion) {
					$this->set_error('800MAT_dominio_perfiles_datos', array($datos_grupo_acc['nombre']));
					break 2;
				}
				$dominio = $dominio_unidad_gestion;
			}
		}
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	//--------------------------------------------------------------------------
	/**
	 *  Determina si es posible dar de baja la persona. De no ser así lanza la excepción con los mensajes correspondientes.
	 *  
	 */
	function validar_datos_eliminar_persona()
	{
		$this->validar_persona_es_alumno();
		$this->validar_persona_es_docente();

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	/**
	 *  Determina si la persona no es alumno.
	 * 	
	 */
	function validar_persona_es_alumno()
	{
		if (toba::consulta_php('co_personas')->es_alumno($this->tabla('personas')->get_columna('persona'))) {
			$this->set_error('800MAT_persona_alumno');
		}
	}

	/**
	 *  Determina si la persona no es docente.
	 * 	
	 */
	function validar_persona_es_docente()
	{
		if (toba::consulta_php('co_personas')->es_docente($this->tabla('personas')->get_columna('persona'))) {
			$this->set_error('800MAT_persona_docente');
		}
	}

	/**
	 * Se valida que no exista una persona con el mismo tipo, numero y pais.
	 * @param type $tipo_documento
	 * @param type $nro_documento
	 * @param type $pais
	 * @param type $persona
	 */
	function validar_persona_unica($tipo_documento, $nro_documento, $pais, $persona = null)
	{
		// Se pasa por parametro el id de persona para que no compare con ella si esta modificando.
		$validar_uniq = toba::consulta_php('co_personas')->existe_persona($pais, $tipo_documento, $nro_documento, $persona);
		if ($validar_uniq) {
			$this->set_error('Ya existe una persona con el tipo, número y país seleccionados');
		}
	}

	//---------------------------------------------------------------------
	/**
	 * 	Extensión de la función del cn_entidad para realizar controles previos a la sincronización de datos.
	 */
	function pre_sincronizar()
	{
		$cuit_obligatorio = guarani::parametros()->get_valor('personas_cuil_obligatorio');
		if ($cuit_obligatorio == comunes::si) {
			$this->validar_carga_cuil();
		}
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	/**
	 * Implementa la carga de una entidad
	 * @return mixed Clave de la entidad sincronizada	 
	 */
	function sincronizar()
	{
		
		// Obtengo los usuarios de Moodle de la persona antes de haber editado (lo que esta en la DB)
		$persona = $this->tabla('personas')->get_columna('persona');
		$usuarios_anteriores = [];
		// Si es una persona nueva no tiene usuarios en Moodle
		if (!is_null($persona)) {
			$usuarios_anteriores = toba::consulta_php('co_configuracion')->get_usuarios_by_persona($persona);
		}
		
		$resultado = parent::sincronizar();
		
		// Si se sincronizo bien
		if (is_array($resultado)) {
			$this->modificar_usuarios_moodle($usuarios_anteriores);
		}
		
		return $resultado;
	}

	//--------------------------------------------------------------------------
	//---- Datos censales ------------------------------------------------------
	//--------------------------------------------------------------------------

	protected function actualizar_cabecera_datos_censales()
	{
		$dato_censal = $this->get_datos_datos_censales();
		$fecha_actual = guarani_fecha::get_hoy();
		if (empty($dato_censal['dato_censal'])) {
			// Si no hay registro previo, se inicializa la cabecera.
			$cabecera_dato_censal = array('fecha_relevamiento' => $fecha_actual, 'fecha_actualizacion' => $fecha_actual);
			$this->set_datos_datos_censales($cabecera_dato_censal);
		} else {
			$this->tabla('datos_censales')->set_columna_valor('fecha_actualizacion', $fecha_actual);
		}
	}

	function get_datos_datos_censales()
	{
		return $this->tabla('datos_censales')->get();
	}

	function set_datos_datos_censales($datos)
	{
		$this->tabla('datos_censales')->set($datos);
	}

	//---- Datos personales ----------------------------------------------------

	function set_datos_datos_personales($datos)
	{
		$this->actualizar_cabecera_datos_censales();

		if (isset($datos['periodo_lectivo_localidad'])) {
			if (stripos($datos['periodo_lectivo_localidad'], ':')) {
				$claves = $datos['periodo_lectivo_localidad'];
				$datos['periodo_lectivo_codigo_postal'] = guarani_texto::extraer_subclave($claves, 1);
				$datos['periodo_lectivo_localidad'] = guarani_texto::extraer_subclave($claves);
			}
		} else {
			/**
			 * Al ef localidad se le reseteó el valor, por lo cual el código postal también debe resetearse.
			 * Es necesario resetear el valor en el servidor, ya que aunque se haya reseteado en el cliente (en la extensión
			 * del form), al ser un ef fijo el cambio no es tomado en cuenta.
			 */
			$datos['periodo_lectivo_codigo_postal'] = '';
		}
		if (isset($datos['procedencia_localidad'])) {
			if (stripos($datos['procedencia_localidad'], ':')) {
				$claves_proc = $datos['procedencia_localidad'];
				$datos['procedencia_codigo_postal'] = guarani_texto::extraer_subclave($claves_proc, 1);
				$datos['procedencia_localidad'] = guarani_texto::extraer_subclave($claves_proc);
			}
		} else {
			/**
			 * Al ef localidad se le reseteó el valor, por lo cual el código postal también debe resetearse.
			 * Es necesario resetear el valor en el servidor, ya que aunque se haya reseteado en el cliente (en la extensión
			 * del form), al ser un ef fijo el cambio no es tomado en cuenta.
			 */
			$datos['procedencia_codigo_postal'] = '';
		}
		$this->validar_datos_personales($datos);
		$this->tabla('datos_personales')->set($datos);
	}

	function get_datos_datos_personales()
	{
		return $this->tabla('datos_personales')->get();
	}

	//---- Datos económicos ----------------------------------------------------

	function set_datos_datos_economicos($datos)
	{
		$this->actualizar_cabecera_datos_censales();

		if (!empty($datos['costeo_estudios_beca'])) {
			$datos['beca'] = $datos['costeo_estudios_beca'];
		}
		$this->validar_datos_economicos($datos);
		$this->tabla('datos_economicos')->set($datos);
	}

	function get_datos_datos_economicos()
	{
		return $this->tabla('datos_economicos')->get();
	}

	//---- Datos actividades ---------------------------------------------------

	function set_datos_datos_actividades($datos)
	{
		$this->actualizar_cabecera_datos_censales();

		$this->validar_datos_actividades($datos);
		$this->tabla('datos_actividades')->set($datos);
	}

	function get_datos_datos_actividades()
	{
		return $this->tabla('datos_actividades')->get();
	}

	//---- Elección de Institución ---------------------------------------------

	function set_datos_eleccion_institucion($datos)
	{
		$this->actualizar_cabecera_datos_censales();
		$this->tabla('eleccion_institucion')->set($datos);
	}

	function get_datos_eleccion_institucion()
	{
		return $this->tabla('eleccion_institucion')->get();
	}

	//---- Elección de Propuesta -----------------------------------------------

	function set_datos_eleccion_propuesta($datos)
	{
		$this->actualizar_cabecera_datos_censales();
		$this->tabla('eleccion_propuesta')->set($datos);
	}

	function get_datos_eleccion_propuesta()
	{
		return $this->tabla('eleccion_propuesta')->get();
	}

	//---- Datos discapacidad --------------------------------------------------

	function set_datos_discapacidad($datos)
	{
		$this->actualizar_cabecera_datos_censales();
		$this->limpiar_datos_discapacidad($datos);
		$this->validar_datos_discapacidad($datos);
		$this->tabla('datos_discapacidad')->set($datos);
	}
	
	function get_datos_discapacidad()
	{
		return $this->tabla('datos_discapacidad')->get();
	}
	
	/**
	 * Determina si los datos correspondientes al datos tabla de datos de discapacidad ingresados por el usuario son válidos (invocando a 
	 * diferentes métodos de validación de datos determinados). De no ser así lanza la excepción con los mensajes correspondientes.
	 * 
	 * @param array	datos	son los datos sobre los cuales se realizan las validaciones, en formato recorset.
	 */
	function validar_datos_discapacidad($datos)
	{
		if ($datos['condicion_discapacidad'] == \comunes::si) {
			$this->validar_campos_discapacidad_si_no($datos);
			$this->validar_campos_discapacidad_textarea($datos);
		}
		
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_campos_discapacidad_si_no($datos)
	{
		$form_invalido = false;
		
		$campos = ['tiene_cud', 'disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra',
				'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos',
				'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos',
				'mot_req_apoyo_aula', 'mot_req_otros_apoyos',
				'psi_req_otros_apoyos',
				'otra_req_apoyo_vida_diaria'];
		
		foreach ($campos as $campo) {
				
			$valor = $datos[$campo];
			
			// Si el valor es distinto a 'S' o 'N'
			if (!in_array($valor, [\comunes::si, \comunes::no])) {
				$form_invalido = true;
				break;
			}
		}
		
		if ($form_invalido) {
			$this->set_error('Las casillas de verificación solo pueden contener los valores "S" o "N".');
		}
		
	}
	
	function validar_campos_discapacidad_textarea($datos)
	{
		$form_invalido = false;
		
		$campos = ['aud_comunicacion_otra', 'aud_apoyo_vida_diaria', 'aud_otros_apoyos', 'vis_apoyo_vida_diaria', 'vis_otros_apoyos', 'mot_apoyo_vida_diaria', 'mot_apoyo_aula',
				'mot_otros_apoyos', 'psi_descripcion', 'psi_apoyo_vida_diaria', 'psi_otros_apoyos', 'otra_descripcion',
				'otra_apoyo_vida_diaria', 'informacion_importante'];
		
		foreach ($campos as $campo) {
				
			$valor = $datos[$campo];
			
			if (strlen($valor) > static::LARGO_TEXTAREA) {
				$form_invalido = true;
				break;
			}
		}
		
		if ($form_invalido) {
			$this->set_error('Los campos de texto no deben contener mas de '.static::LARGO_TEXTAREA.' caracteres.');
		}
		
	}
	
	protected function limpiar_datos_discapacidad(&$datos)
	{
		if ($datos['condicion_discapacidad'] == \comunes::no) {
			$datos['tiene_cud'] = \comunes::no;
			$datos['disc_auditiva'] = \comunes::no;
			$datos['disc_visual'] = \comunes::no;
			$datos['disc_motora'] = \comunes::no;
			$datos['disc_cond_psicosocial'] = \comunes::no;
			$datos['disc_otra'] = \comunes::no;
			$datos['informacion_importante'] = "";
		}
		
		$this->limpiar_discapacidad_auditiva($datos);
		$this->limpiar_discapacidad_visual($datos);
		$this->limpiar_discapacidad_motora($datos);
		$this->limpiar_discapacidad_psicosocial($datos);
		$this->limpiar_discapacidad_otras($datos);
	}
	
	protected function limpiar_discapacidad_auditiva(&$datos)
	{
		if ($datos['disc_auditiva'] == \comunes::no) {
			$datos['aud_tipo'] = \persona::NO_HAY_DIFICULTAD_VAL;
			$datos['aud_lenguaje_senias'] = \comunes::no;
			$datos['aud_lenguaje_labial'] = \comunes::no;
			$datos['aud_comunicacion_otra'] = "";
			$datos['aud_apoyo_vida_diaria'] = "";
			$datos['aud_req_interprete_lengua_senias'] = \comunes::no;
			$datos['aud_req_aro_magnetico'] = \comunes::no;
			$datos['aud_req_otros_apoyos'] = \comunes::no;
			$datos['aud_otros_apoyos'] = "";
		}
		
		if ($datos['aud_req_otros_apoyos'] == \comunes::no) {
			$datos['aud_otros_apoyos'] = "";
		}
	}
	
	protected function limpiar_discapacidad_visual(&$datos)
	{
		if ($datos['disc_visual'] == \comunes::no) {
			$datos['vis_dificultad_para_ver'] = \persona::NO_HAY_DIFICULTAD_VAL;
			$datos['vis_apoyo_vida_diaria'] = "";
			$datos['vis_req_archivo_audio'] = \comunes::no;
			$datos['vis_req_texto_digital'] = \comunes::no;
			$datos['vis_req_texto_braile'] = \comunes::no;
			$datos['vis_req_otros_apoyos'] = \comunes::no;
			$datos['vis_otros_apoyos'] = "";
		}
		
		if ($datos['vis_req_otros_apoyos'] == \comunes::no) {
			$datos['vis_otros_apoyos'] = "";
		}
	}
	
	protected function limpiar_discapacidad_motora(&$datos)
	{
		if ($datos['disc_motora'] == \comunes::no) {
			$datos['mot_apoyo_vida_diaria'] = "";
			$datos['mot_req_apoyo_aula'] = \comunes::no;
			$datos['mot_apoyo_aula'] = "";
			$datos['mot_req_otros_apoyos'] = \comunes::no;
			$datos['mot_otros_apoyos'] = "";
		}
		
		if ($datos['mot_req_apoyo_aula'] == \comunes::no) {
			$datos['mot_apoyo_aula'] = "";
		}
		
		if ($datos['mot_req_otros_apoyos'] == \comunes::no) {
			$datos['mot_otros_apoyos'] = "";
		}
	}
	
	protected function limpiar_discapacidad_psicosocial(&$datos)
	{
		if ($datos['disc_cond_psicosocial'] == \comunes::no) {
			$datos['psi_descripcion'] = "";
			$datos['psi_apoyo_vida_diaria'] = "";
			$datos['psi_req_otros_apoyos'] = \comunes::no;
			$datos['psi_otros_apoyos'] = "";
		}
		
		if ($datos['psi_req_otros_apoyos'] == \comunes::no) {
			$datos['psi_otros_apoyos'] = "";
		}
	}
	
	protected function limpiar_discapacidad_otras(&$datos)
	{
		if ($datos['disc_otra'] == \comunes::no) {
			$datos['otra_descripcion'] = "";
			$datos['otra_req_apoyo_vida_diaria'] = \comunes::no;
			$datos['otra_apoyo_vida_diaria'] = "";
		}
		
		if ($datos['otra_req_apoyo_vida_diaria'] == \comunes::no) {
			$datos['otra_apoyo_vida_diaria'] = "";
		}
	}
	
	//--------------------------------------------------------------------------
	/**
	 * Determina si los datos correspondientes al datos tabla de datos personales ingresados por el usuario son válidos (invocando a 
	 * diferentes métodos de validación de datos determinados). De no ser así lanza la excepción con los mensajes correspondientes.
	 * 	 
	 * @param array	datos son los datos sobre los cuales se realizan las validaciones, en formato recorset.
	 */
	function validar_datos_personales($datos)
	{
		if (isset($datos['estado_civil'])) {
			$this->validar_campos_estado_civil($datos['estado_civil'], $datos['unido_hecho']);
		}
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
	 * Determina si el campo unido de hecho puede estar o no seteado, de acuerdo al valor del estado civil.
	 * 
	 * @param integer	estado_civil	es el id que representa el estado civil de la persona ingresado por el usuario.
	 * @param char		unido_hecho		representa el valor asignado a esta condición (Sí o No).
	 */
	function validar_campos_estado_civil($estado_civil, $unido_hecho)
	{
		if ($estado_civil == persona::casado && $unido_hecho == 'S') {
			$this->set_error('800_dato_invalido', array('condición de unido de hecho', 'no puede setearse si la persona no es casada'));
		}
	}

	//--------------------------------------------------------------------------
	/**
	 * Determina si los datos correspondientes al datos tabla de datos económicos ingresados por el usuario son válidos (invocando a 
	 * diferentes métodos de validación de datos determinados). De no ser así lanza la excepción con los mensajes correspondientes.
	 * 
	 * @param array	datos	son los datos sobre los cuales se realizan las validaciones, en formato recorset.
	 */
	function validar_datos_economicos($datos)
	{
		if (!empty($datos['beca'])) {
			$this->validar_campos_beca($datos['beca'], $datos['beca_fuente_universidad'], $datos['beca_fuente_nacional'], $datos['beca_fuente_provincial'], $datos['beca_fuente_municipal'], $datos['beca_fuente_internacional'], $datos['beca_fuente_otra'], $datos['beca_tipo_economica'], $datos['beca_tipo_servicios'], $datos['beca_tipo_investigacion']);
		}
		if (!empty($datos['beca_tipo_economica'])) {
			$this->validar_campos_beca_economica($datos['beca_tipo_economica'], $datos['beca_economica_transporte'], $datos['beca_economica_efectivo'], $datos['beca_economica_fotocopias'], $datos['beca_economica_habitacional'], $datos['beca_economica_comedor']);
		}
		if (!empty($datos['trabajo_existe'])) {
			$this->validar_campos_trabajo($datos['trabajo_existe'], $datos['trabajo_hace'], $datos['trabajo_familiar'], $datos['trabajo_con_descuentos_jubilatorios'], $datos['trabajo_recibe_pago'], $datos['trabajo_ocupacion'], $datos['trabajo_hora_sem'], $datos['trabajo_carrera'], $datos['trabajo_tarea_descripcion']);
		}
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
	 * Determina si los campos relacionados a la fuente y tipo de beca pueden estar o no seteados, de acuerdo al valor de beca (Sí o No).
	 * 
	 * @param char	beca			representa el valor asignado a esa condición (Sí o No).
	 * @param char	f_univ			representa si la beca es o no de fuente universitaria.
	 * @param char	f_nacional		representa si la beca es o no de fuente nacional.
	 * @param char	f_provincial	representa si la beca es o no de fuente provincial.
	 * @param char	f_municipal		representa si la beca es o no de fuente municipal.
	 * @param char	f_internacional	representa si la beca es o no de fuente internacional.
	 * @param char	f_otra			representa si la beca es o no de otra fuente.
	 * @param char	t_econom		representa si la beca es o no de tipo económica.
	 * @param char	t_serv			representa si la beca es o no de contraprestación de servicios.
	 * @param char	t_invest		representa si la beca es o no de investigación.
	 */
	function validar_campos_beca($beca, $f_univ, $f_nacional, $f_provincial, $f_municipal, $f_internacional, $f_otra, $t_econom, $t_serv, $t_invest)
	{
		if (($beca != 'S') && ($f_univ == 'S' || $f_nacional == 'S' || $f_provincial == 'S' || $f_municipal == 'S' ||
				$f_internacional == 'S' || $f_otra == 'S' || $t_econom == 'S' || $t_serv == 'S' || $t_invest == 'S')) {
			$this->set_error('800_dato_invalido', array('campo de fuente o tipo de beca', 'no puede setearse si la persona no tiene beca'));
		}
	}

	/**
	 * Determina si los campos relacionados con becas de tipo económico pueden estar o no seteados, de acuerdo al valor de beca_tipo_economica (Sí o No).
	 * 
	 * @param char	beca_econom		representa si la beca es de tipo económica.
	 * @param char	transporte		representa si la beca es o no de fuente universitaria.
	 * @param char	efectivo		representa si la beca es o no de fuente nacional.
	 * @param char	fotocopias		representa si la beca es o no de fuente provincial.
	 * @param char	habitacional	representa si la beca es o no de fuente municipal.
	 * @param char	comedor			representa si la beca es o no de fuente internacional.
	 */
	function validar_campos_beca_economica($beca_econom, $transporte, $efectivo, $fotocopias, $habitacional, $comedor)
	{
		if (($beca_econom != 'S') && ($transporte == 'S' || $efectivo == 'S' || $fotocopias == 'S' || $habitacional == 'S' || $comedor == 'S')) {
			$this->set_error('800_dato_invalido', array('campo de datos de becas económicas', 'no puede setearse si la persona no tiene una beca de tipo económica'));
		}
	}

	/**
	 * Chequea que los campos referentes al trabajo esten seteados si el campo de trabajo existe tiene el valor de que la persona trabaja (y que no lo estén en otro caso).
	 * 
	 * @param integer	trab_existe		es el id que representa la situación laboral de la persona.
	 * @param integer	hace			es el id que representa lo que la persona es en ese trabajo.
	 * @param char		familiar		indica si el trabajo es en un negocio o empresa familiar.
	 * @param char		desc_jub		indica si se a la persona le hacen descuentos jubilatorios en dicho trabajo.
	 * @param char		pago			indica si la persona recibe algún tipo de pago por el trabajo.
	 * @param integer	ocupacion		id que representa cuál es la ocupación o cargo de la persona en dicho trabajo.
	 * @param integer	hs_sem			id que representa la cantidad de hs semanales que trabaja la persona.
	 * @param integer	trab_carrera	id que representa la relación entre el trabajo y la carrera de la persona.  
	 * @param string	descr_tarea		descripción de la tarea que la persona realiza en el trabajo.
	 */
	function validar_campos_trabajo($trab_existe, $hace, $familiar, $desc_jub, $pago, $ocupacion, $hs_sem, $trab_carrera, $descr_tarea)
	{
		if ($trab_existe == persona::trabajo) {
			if (!isset($hace) || !isset($familiar) || !isset($desc_jub) || !isset($pago) || !isset($ocupacion) || !isset($hs_sem) || !isset($trab_carrera) || !isset($descr_tarea)) {
				$this->set_error('800_dato_dep_obligatorio', array('campo relacionado al trabajo de la persona', 'Condición de Actividad durante la semana pasada'));
			}
		} else {
			//Si la persona no está trabajando esos campos no deben tener valor.
			if (isset($hace) || $familiar == 'S' || $desc_jub == 'S' || $pago == 'S' || isset($ocupacion) || isset($hs_sem) || isset($trab_carrera) || isset($descr_tarea)) {
				$this->set_error('800_dato_invalido', array('campo de datos laborales', 'no puede setearse si la Condición de Actividad durante la semana pasada indica que la persona no trabajó'));
			}
		}
	}

	//--------------------------------------------------------------------------
	/**
	 * Determina si los datos correspondientes al datos tabla de datos actividades ingresados por el usuario son válidos (invocando a 
	 * diferentes métodos de validación de datos determinados). De no ser así lanza la excepción con los mensajes correspondientes.
	 *
	 * @param array	datos	son los datos sobre los cuales se realizan las validaciones, en formato recorset.
	 */
	function validar_datos_actividades($datos)
	{
		if (!empty($datos['deportes'])) {
			$this->validar_campos_deportes($datos['deportes'], $datos['deportes_universidad'], $datos['deportes_gimnasio'], $datos['deportes_particular'], $datos['deportes_otro_lugar'], $datos['deportes_basquet'], $datos['deportes_futbol'], $datos['deportes_gimnasia'], $datos['deportes_handball'], $datos['deportes_natacion'], $datos['deportes_tenis'], $datos['deportes_voley'], $datos['deportes_otros']);
		}
		if (!empty($datos['idioma_otro'])) {
			$this->validar_campos_idiomas($datos['idioma_otro'], $datos['nivel_idioma_otro']);
		}
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
	 * Determina si los campos relacionados al tipo y lugar donde se realizan deportes pueden estar o no seteados, de acuerdo al valor de deportes (Sí o No).
	 * 
	 * @param char	deportes		representa el valor asignado a esa condición (Sí o No).
	 * @param char	f_univ			representa si la beca es o no de fuente universitaria.
	 * @param char	f_nacional		representa si la beca es o no de fuente nacional.
	 * @param char	f_provincial	representa si la beca es o no de fuente provincial.
	 * @param char	f_municipal		representa si la beca es o no de fuente municipal.
	 * @param char	f_internacional	representa si la beca es o no de fuente internacional.
	 * @param char	f_otra			representa si la beca es o no de otra fuente.
	 * @param char	t_econom		representa si la beca es o no de tipo económica.
	 * @param char	t_serv			representa si la beca es o no de contraprestación de servicios.
	 * @param char	t_invest		representa si la beca es o no de investigación.
	 */
	function validar_campos_deportes($deportes, $univ, $gimnasio, $particular, $otro, $basquet, $futbol, $gimnasia, $handball, $natacion, $tenis, $voley, $otros)
	{
		if (($deportes != 'S') && ($univ == 'S' || $gimnasio == 'S' || $particular == 'S' || $otro == 'S' || $basquet == 'S' ||
				$futbol == 'S' || $gimnasia == 'S' || $handball == 'S' || $natacion == 'S' || $tenis == 'S' || $voley == 'S' || $otros == 'S')) {
			$this->set_error('800_dato_invalido', array('campo de lugar o tipo de deporte', 'no puede setearse si la persona no realiza deportes'));
		}
	}

	/**
	 * Determina si los campos relacionados al estudio de otro idioma están o no seteados, de acuerdo a si otro_idioma tiene algún valor.
	 * 
	 * @param string	otro_idioma		contiene la denominación de otro idioma estudiado por la persona, en caso de tener valor.
	 * @param integer	nivel			id que representa el nivel alcanzado en el estudio de ese idioma.
	 */
	function validar_campos_idiomas($otro_idioma, $nivel)
	{
		if (isset($otro_idioma)) {
			if (!isset($nivel)) {
				$this->set_error('800_dato_dep_obligatorio', array('Nivel del otro idioma', 'Otro idioma'));
			}
		} else {
			// Si no tiene valor otro idioma entonces no puede tener valor su nivel.
			if (isset($nivel)) {
				$this->set_error('800_dato_invalido', array('campo de Nivel de otro idioma', 'no puede setearse si no existe valor para Otro idioma'));
			}
		}
	}
	
	//--------------------------------------------------------------------------
	
	function seleccionar_usuario_moodle($id_fila_usuario)
	{
		$this->tabla('usuarios_moodle')->set_cursor($id_fila_usuario);		
		$datos = $this->tabla('usuarios_moodle')->get();
		toba::memoria()->set_dato('plataforma', $datos['plataforma']);
	}

	function deseleccionar_usuario_moodle()
	{
		$this->tabla('usuarios_moodle')->resetear_cursor();
		toba::memoria()->eliminar_dato('plataforma');
	}

	function hay_usuario_moodle_seleccionado()
	{
		return $this->tabla('usuarios_moodle')->hay_cursor();
	}
	
	function get_datos_usuario_moodle_seleccionado()
	{
		if ($this->hay_usuario_moodle_seleccionado()) {
			return $this->tabla('usuarios_moodle')->get();
		}
	}
	
	function get_datos_usuarios_moodle()
	{
		// Obtengo todas las filas del datos tabla
		$usuarios_moodle = $this->tabla('usuarios_moodle')->get_filas();
		$datos = [];
		$hubo_cambios = false;
		
		foreach ($usuarios_moodle as $usuario_moodle) {
			
			// Obtengo información del usuario en Moodle 
			$moodle = new moodle($usuario_moodle['host'], $usuario_moodle['url'], $usuario_moodle['token']);
			$criteria = [
				[
					'key' => "id",
					'value' => $usuario_moodle['id_usuario_externo'],
				]
			];
			$infoUsuario = $moodle->getUsuarios($criteria);
			
			if (is_array($infoUsuario)) { // Si se pudo obtener información del usuario desde Moodle
				
				$infoUsuario = reset($infoUsuario);
				$estado_plataforma = ($usuario_moodle['activa'])? "Activa" : "Inactiva";
				$usuario_moodle['plataforma_nombre'] = "{$usuario_moodle['nombre']} ({$estado_plataforma})";
				$usuario_moodle['moodle_usuario_descr'] = "{$infoUsuario['id']} - {$infoUsuario['lastname']}, {$infoUsuario['firstname']} ({$infoUsuario['username']} - {$infoUsuario['email']})";
				$datos[] = $usuario_moodle;
				
			} elseif ($infoUsuario === false) { // Si el usuario fue eliminado en Moodle lo desvinculo en Guaraní
				
				$this->tabla('usuarios_moodle')->eliminar_fila($usuario_moodle['x_dbr_clave']);
				$hubo_cambios = true;
				
			}
			
		}
		
		if ($hubo_cambios) {
			$this->tabla('usuarios_moodle')->sincronizar();
		}
		
		return $datos;
	}
	
	function set_datos_usuario_moodle($datos)
	{
		$this->tabla('usuarios_moodle')->set($datos);
	}
	
	function nuevo_usuario_moodle($datos)
	{
		$this->tabla('usuarios_moodle')->nueva_fila($datos);
	}
	
	function eliminar_usuario_moodle_seleccionado()
	{
		if ($this->hay_usuario_moodle_seleccionado()) {
			$id_fila = $this->tabla('usuarios_moodle')->get_cursor();
			$this->tabla('usuarios_moodle')->eliminar_fila($id_fila);
		}
	}
	
	function get_plataformas_disponibles($plataforma_excepcion = null)
	{
		
		// Obtengo todas las filas del datos tabla
		$usuarios_moodle = $this->tabla('usuarios_moodle')->get_filas();
		$plataformas_a_excluir = array_column($usuarios_moodle, 'plataforma');
		
		// En caso de edición debo incluir la plataforma a editar
		if (!is_null($plataforma_excepcion)) {
			$plataformas_a_excluir = array_diff($plataformas_a_excluir, [$plataforma_excepcion]);
		}
		
		$plataformas = toba::consulta_php('co_configuracion')->get_plataformas_educacion_virtual();
		
		$plataformas = array_filter($plataformas, function($plataforma) use ($plataformas_a_excluir) {
			return !in_array($plataforma['plataforma'], $plataformas_a_excluir);
		});

		return $plataformas;
	}
	
	function get_usuarios_moodle($filtro, $plataforma)
	{
		$datos_plataforma = toba::consulta_php('co_configuracion')->get_plataforma_educacion_virtual($plataforma);
		$moodle = new moodle($datos_plataforma['host'], $datos_plataforma['url'], $datos_plataforma['token']);
		// Filtro por apellido en Moodle
		$criteria = [
			[
				'key' => "lastname",
				'value' => "%$filtro%",
			]
		];
		$usuarios = $moodle->getUsuarios($criteria);
		
		$usuarios_asignados = toba::consulta_php('co_configuracion')->get_usuarios_by_plataforma($plataforma);
		$usuarios_asignados_ids = array_column($usuarios_asignados, 'id_usuario_externo');
		
		foreach ($usuarios as $key => $usuario) {
			
			// Si el usuario de Moodle ya esta asociado a una persona de Guaraní lo quito
			if (in_array($usuario['id'], $usuarios_asignados_ids)) {
				unset($usuarios[$key]);
				continue;
			}
			
			$usuarios[$key]['descr'] = "{$usuario['id']} - {$usuario['lastname']}, {$usuario['firstname']} ({$usuario['username']} - {$usuario['email']})";
		}
		
		return $usuarios;
	}
	
	function get_usuario_moodle($id)
	{		
		$plataforma = toba::memoria()->get_dato('plataforma');		
		$datos_plataforma = toba::consulta_php('co_configuracion')->get_plataforma_educacion_virtual($plataforma);
		$moodle = new moodle($datos_plataforma['host'], $datos_plataforma['url'], $datos_plataforma['token']);
		// Filtro por id en Moodle
		$criteria = [
			[
				'key' => "id",
				'value' => $id,
			]
		];
		$usuarios = $moodle->getUsuarios($criteria);
		$usuario = reset($usuarios);
		$usuario['descr'] = "{$usuario['id']} - {$usuario['lastname']}, {$usuario['firstname']} ({$usuario['username']} - {$usuario['email']})";
		
		return $usuario['descr'];
	}
	
	public function modificar_usuarios_moodle($usuarios_anteriores = [])
	{
		
		$usuarios_a_quitar = [];
		foreach ($usuarios_anteriores as $key => $usuario_anterior) {
			$usuario_anterior['persona'] = 0;
			$usuarios_a_quitar[$usuario_anterior['plataforma']] = $usuario_anterior;
		}
		
		// Obtengo los usuarios de Moodle de la persona después de haber editado (lo que esta en el datos tabla)
		$usuarios_nuevos = $this->tabla('usuarios_moodle')->get_filas();
		
		$usuarios_a_agregar = [];
		foreach ($usuarios_nuevos as $key => $usuario_nuevo) {
			
			if (isset($usuarios_a_quitar[$usuario_nuevo['plataforma']]) && ($usuarios_a_quitar[$usuario_nuevo['plataforma']]['id_usuario_externo'] == $usuario_nuevo['id_usuario_externo'])) {
				unset($usuarios_a_quitar[$usuario_nuevo['plataforma']]);
				continue;
			}
			
			$usuarios_a_agregar[$usuario_nuevo['plataforma']] = $usuario_nuevo;
		}
		
		$usuarios_a_modificar = array_merge($usuarios_a_quitar, $usuarios_a_agregar);
		
		foreach ($usuarios_a_modificar as $usuario_modificado) {
			
			$moodle = new moodle($usuario_modificado['host'], $usuario_modificado['url'], $usuario_modificado['token']);
			
			$usuarios = [
				[
					'id' => $usuario_modificado['id_usuario_externo'],
					'idnumber' => $usuario_modificado['persona']
				]
			];
			$moodle->modificarUsuarios($usuarios);
			
		}
	}
	
	//--------------------------------------------------------------------------

        /**
         * Administra las cuentas de SIU-Araí
         *
         * @param string $identificador_usuario ID de usuario de SIU-Araí, para la API v2 es un UUID.
         *
         * @throws guarani_error En caso de error
         * @return void.
         */
        function administrar_cuentas_arai($identificador_usuario)
        {
            // Si no vincula usuarios con SIU-Araí salgo
            if (!toba::instalacion()->vincula_arai_usuarios()) {
                return false;
            }

            $this->eliminar_cuentas_arai($identificador_usuario);
            $this->crear_cuentas_arai($identificador_usuario);
        }

        /**
         * Crea las cuentas de SIU-Araí
         *
         * @param string $identificador_usuario ID de usuario de SIU-Araí, para la API v2 es un UUID.
         *
         * @throws guarani_error En caso de error
         * @return void.
         */
	function crear_cuentas_arai($identificador_usuario)
	{
            
            // Si la persona es nueva (no existe en la DB)
            if ($this->es_persona_nueva()) {
                return false;
            }

            $crear_cuentas = false;

            $tiene_perfil_gestion = $this->tiene_perfil(persona::perfil_gestion);
            $tiene_perfil_docente = $this->tiene_perfil(persona::perfil_docente);

            // Si la persona tiene perfil 'Gestión' y 'Docente'
            if ($tiene_perfil_gestion && $tiene_perfil_docente) {
                $interfaz = comunes::interfaz_todas;
                $crear_cuentas = true;
            // Si la persona tiene perfil 'Gestión'
            } else if ($tiene_perfil_gestion) {
                $interfaz = comunes::interfaz_gestion;
                $crear_cuentas = true;
            // Si la persona tiene perfil 'Docente'
            } else if ($tiene_perfil_docente) {
                $interfaz = comunes::interfaz_autogestion;
                $crear_cuentas = true;
            }

            // Si se deben crear cuentas
            if ($crear_cuentas) {
                $usuario = $this->get_usuario();
                arai_usuarios::getDriver()->crearCuentas($identificador_usuario, $usuario, $interfaz);
            }
	}

        /**
         * Elimina las cuentas de SIU-Araí
         *
         * @param string $identificador_usuario ID de usuario de SIU-Araí, para la API v2 es un UUID.
         *
         * @throws guarani_error En caso de error
         * @return void.
         */
	function eliminar_cuentas_arai($identificador_usuario)
	{
            arai_usuarios::getDriver()->eliminarCuentas($identificador_usuario, comunes::interfaz_todas);
	}

        /**
         * Administra la cuenta de SIU-Guaraní Gestión en SIU-Araí
         *
         * @param array $perfiles_gestion Perfiles para Usuario de Gestión.
         *
         * @throws guarani_error En caso de error
         * @return void.
         */
	function administrar_cuenta_gestion_arai($perfiles_gestion)
        {

            // Si no vincula usuarios con SIU-Araí salgo
            if (!toba::instalacion()->vincula_arai_usuarios()) {
                return false;
            }
            
            $identificador_usuario = $this->get_identificador_usuario_arai();
            // Si no tiene asociado usuario en SIU-Araí salgo
            if (empty($identificador_usuario)) {
                return false;
            }

            $usuario = $this->get_usuario();
            // Si no tiene usuario en SIU-Guaraní salgo
            if (empty($usuario)) {
                return false;
            }

            $cant_total = count($perfiles_gestion);
            // Si no hay cambios salgo
            if ($cant_total == 0) {
                return false;
            }

            $cant_agregadas = 0;
            $cant_borradas = 0;
            foreach ($perfiles_gestion as $perfil_gestion) {

                if ($perfil_gestion['apex_ei_analisis_fila'] == 'A') {
                    $cant_agregadas++;
                }

                if ($perfil_gestion['apex_ei_analisis_fila'] == 'B') {
                    $cant_borradas++;
                }
                
            }

            // Si se borran todos los perfiles de Gestión le elimino la cuenta y salgo
            if ($cant_total == $cant_borradas) {
                arai_usuarios::getDriver()->eliminarCuentas($identificador_usuario, comunes::interfaz_gestion);
                return false;
            }

            // Si se agregan perfiles de Gestión
            if ($cant_agregadas > 0) {
                $datos_grupos_acceso = $this->get_datos_grupos_acceso(['tipo_usuario' => usuario::tipo_gestion]);
                // Si previamente no tenia perfil de Gestión agrego la cuenta
                if (empty($datos_grupos_acceso)) {
                    arai_usuarios::getDriver()->eliminarCuentas($identificador_usuario, comunes::interfaz_gestion);
                    arai_usuarios::getDriver()->crearCuentas($identificador_usuario, $usuario, comunes::interfaz_gestion);
                }
            }
        }

        /**
         * Desvincula usuario de SIU-Araí
         * @return string ID de usuario de SIU-Araí, para la API v2 es un UUID.
         */
	function desvincular_usuario_arai()
	{
            $identificador_usuario = $this->get_identificador_usuario_arai();
            
            $this->tabla('personas')->set_columna_valor('arai_identificador_sso', null);
            $this->tabla('personas')->set_columna_valor('arai_uuid', null);

            return $identificador_usuario;
	}

        /**
	 * Verifica si la persona es nueva (no existe en la DB).
         * 
	 *  @return boolean TRUE Si la persona es nueva, FALSE en caso contrario.
	 */
	function es_persona_nueva()
	{
            $persona = $this->tabla('personas')->get_columna('persona');
            return is_null($persona);
	}

        /**
	 * Verifica si una persona tiene un perfil dado.
         *
         * @return boolean TRUE Si la persona tiene un perfil dado, FALSE en caso contrario.
	 *
	 */
	function tiene_perfil($perfil)
	{
            return toba::consulta_php('co_personas')->tiene_perfil($this->tabla('personas')->get_columna('persona'), $perfil);
	}

        /**
	 * Devuelve el usuario.
	 *
         * @return string Columna usuario dentro de mdp_personas.
	 */
	function get_usuario()
	{
            return $this->tabla('personas')->get_columna('usuario');
	}

        /**
	 * Devuelve arai_identificador_sso.
	 *
         * @return string Columna arai_identificador_sso dentro de mdp_personas.
	 */
	function get_arai_identificador_sso()
	{
            return $this->tabla('personas')->get_columna('arai_identificador_sso');
	}

        /**
	 * Devuelve arai_uuid.
         *
         * @return string Columna arai_uuid dentro de mdp_personas.
	 */
	function get_arai_uuid()
	{
            return $this->tabla('personas')->get_columna('arai_uuid');
	}

        /**
	 * Devuelve el identificador_usuario de SIU-Araí.
         *
         * @return string ID de usuario de SIU-Araí, para la API v2 es un UUID.
	 */
	function get_identificador_usuario_arai()
	{
            $arai_identificador_sso = $this->get_arai_identificador_sso();
            $arai_uuid = $this->get_arai_uuid();

            return arai_usuarios::getDriver()->getIdentificadorUsuarioByVersionApi($arai_identificador_sso, $arai_uuid);
	}

        //--------------------------------------------------------------------------
}