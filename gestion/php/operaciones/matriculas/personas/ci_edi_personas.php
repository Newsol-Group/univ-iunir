<?php
/**
 *
 *
 * @package		Matricula
 * @subpackage	Personas
 * 
 */
class ci_edi_personas extends guarani_pers_ci
{
	protected $datos_usuario_temp;
	protected $datos_tipo_usuario_temp;
	protected $datos_docente_temp;
	protected $datos_docente_resp_temp;
	protected $datos_docente_ra_temp;

	protected $s__usuario_arai_desvinculado = '';

    //-----------------------------------------------------------------------------------
	//---- PANTALLA PERSONAS 
	//-----------------------------------------------------------------------------------

	function evt__pantalla_personas__salida()
	{
		$this->dep('editor_persona')->evt__form_documentos__cancelar();
		$this->dep('editor_persona')->evt__form_contactos__cancelar();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA ACCESO AL SISTEMA 
	//-----------------------------------------------------------------------------------
	
	function conf__pantalla_acceso_sistema(guarani_ei_pantalla $pantalla)
	{

                $this->evento('desvincular_usuario_arai')->ocultar();
                $this->evento('desvincular_usuario_arai')->set_msg_confirmacion(guarani_mensajes::get('800_desvincular_usuario_arai_msg_confirmacion'));

		// Si está vinculado con arai_usuarios controlo la conexión
		if (toba::instalacion()->vincula_arai_usuarios()) {
			// Si la conexión a Araí Usuarios falla no le permito ingresar a "Acceso al sistema"
			if (!arai_usuarios::getDriver()->isConexionOk()) {
				$mje = guarani_mensajes::get('800_servicio_web_arai');
				throw guarani::error($mje);
                        } else {
                            $this->evento('desvincular_usuario_arai')->mostrar();
                            if ($this->habilitar_btn_desvincular_usuario_arai()) {
                                $this->evento('desvincular_usuario_arai')->activar();
                            } else {
                                $this->evento('desvincular_usuario_arai')->desactivar();
                            }
                        }
		} else {// Si no está vinculado con arai_usuarios, no muestro el formulario form_usuario_arai
			$pantalla->eliminar_dep('form_usuario_arai');
		}
	}


        function habilitar_btn_desvincular_usuario_arai()
        {
            $habilitar_btn = false;

            $arai_identificador_sso = $this->cn()->entidad()->get_arai_identificador_sso();

            // Si tiene vinculado Usuario en SIU-Araí habilito el botón
            if (!empty($arai_identificador_sso)) {
                    $habilitar_btn = true;
            }

            return $habilitar_btn;
        }

        function evt__desvincular_usuario_arai()
        {
            $this->s__usuario_arai_desvinculado = $this->cn()->entidad()->desvincular_usuario_arai();
        }
        
	//-----------------------------------------------------------------------------------
	//---- form_usuario -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_usuario(toba_ei_formulario $form)
	{
		if (isset($this->datos_usuario_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_usuario_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_usuario();
		}
		$form->set_datos($datos);
	}

	function evt__form_usuario__modificacion($datos)
	{
		try {
			if (empty($datos['usuario'])) {
				unset($datos['usuario']);
			}
			$this->cn()->entidad()->set_datos_usuario($datos);
		} catch (guarani_error $e) {
			$this->datos_usuario_temp = $datos;
			throw $e;
		}
	}
	
	function habilitar_btn_generar_usuario()
	{
		$habilitar_btn = true;
		
		$datos = $this->cn()->entidad()->get_datos_usuario();
		
		// Si ya tiene usuario seteado no habilito el botón
		if (isset($datos['usuario']) && !empty($datos['usuario'])) {
			$habilitar_btn = false;
		}
		
		return $habilitar_btn;
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_usuario_arai -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

    function conf__form_usuario_arai(toba_ei_formulario $form)
    {
        $datos = $this->cn()->entidad()->get_datos_usuario_arai();

        // Si la columna mdp_personas.arai_identificador_sso no es null
        if (isset($datos['arai_identificador_sso']) && !is_null($datos['arai_identificador_sso'])) {
            // Si la columna mdp_personas.arai_identificador_sso es un string vacío con solo espacios lo trimmeo
            $datos['arai_identificador_sso'] = trim($datos['arai_identificador_sso']);
        }
        
        // Si la persona no tiene Usuario SIU-Guaraní (mdp_personas.usuario)
        if (!isset($datos['usuario']) || empty($datos['usuario'])) {

            $form->ef('arai_identificador_sso')->set_solo_lectura();
            $form->ef('arai_identificador_sso')->set_descripcion(guarani_mensajes::get('800_arai_identificador_sso_solo_lectura'));

        // Si la persona ya tiene Usuario SIU-Araí (mdp_personas.arai_identificador_sso)
        } else if (!empty($datos['arai_identificador_sso'])) {

            $form->ef('arai_identificador_sso')->set_solo_lectura();

        // Le permito vincular Usuario SIU-Araí
        } else {

            $form->ef('arai_identificador_sso')->set_solo_lectura(false);
            $form->ef('arai_identificador_sso')->set_descripcion(guarani_mensajes::get('800_arai_identificador_sso_ayuda', [arai_usuarios::MIN_LENGTH_AUTOCOMPLETE_USUARIOS]));

        }

        $form->set_datos($datos);
    }

    function evt__form_usuario_arai__modificacion($datos)
    {
        try {

            $datos_usuario_arai = $this->cn()->entidad()->get_datos_usuario_arai();
            
            // En caso de que sea una persona recién creada
            $datos_usuario_arai['arai_identificador_sso'] = $datos_usuario_arai['arai_identificador_sso'] ?? null;

            // Si se desvinculó el Usuario de Araí le elimino las cuentas
            if (!empty($this->s__usuario_arai_desvinculado)) {
                $this->cn()->entidad()->eliminar_cuentas_arai($this->s__usuario_arai_desvinculado);
                $this->s__usuario_arai_desvinculado = '';
            }

            // Si hubo cambios en el Usuario de Araí
            if ($datos_usuario_arai['arai_identificador_sso'] != $datos['arai_identificador_sso']) {
                // Araí Usuarios API v1
                $identificador_usuario = $datos['arai_identificador_sso'];

                // Si la versión de la API de Araí Usuarios es mayor o igual a 2
                if (arai_usuarios::getVersionApi() >= 2) {
                    // recupero el UUID de la API de Araí Usuarios
                    $arai_uuid = arai_usuarios::getDriver()->getUidUsuario($datos['arai_identificador_sso']);
                    // Actualizo la columna mdp_personas.arai_uuid
                    $datos['arai_uuid'] = $arai_uuid;
                    // Araí Usuarios API v2
                    $identificador_usuario = $arai_uuid;
                }

                $this->cn()->entidad()->administrar_cuentas_arai($identificador_usuario);

                $this->cn()->entidad()->set_datos_usuario_arai($datos);
            }
            
        } catch (guarani_error $e) {
            // Si falla recuperar el UUID de la API de Araí Usuarios no hago nada para que pueda seguir navegando
        }
    }
    
    //-----------------------------------------------------------------------------------
	//---- ml_perfiles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__ml_perfiles(toba_ei_formulario $form_ml)
	{
		if (isset($this->datos_perfiles_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_perfiles_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_grupos_acceso(array('tipo_usuario' => usuario::tipo_gestion));
			foreach ($datos as $clave => $valor) {
				$condicion = array('tipo_usuario' => usuario::tipo_gestion, 'usuario_grupo_acc' => $valor['usuario_grupo_acc']);
				$datos[$clave]['usuario_perfil_datos'] = aplanar_matriz($this->cn()->entidad()->get_perfiles_datos($condicion), 'usuario_perfil_datos');
			}
		}
		$form_ml->set_datos($datos);
	}

	function evt__ml_perfiles__modificacion($datos)
	{
		try {
                        $this->cn()->entidad()->administrar_cuenta_gestion_arai($datos);
			$this->cn()->entidad()->set_datos_grupos_acceso($datos);
		} catch (guarani_error $e) {
			$this->datos_perfiles_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA DATOS ESTUDIOS 
	//-----------------------------------------------------------------------------------

	function evt__pantalla_datos_estudios__salida()
	{
		$this->dep('editor_datos_estudio')->evt__cancelar();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA ANTECEDENTES DOCENTES 
	//-----------------------------------------------------------------------------------

	function evt__pantalla_ant_docentes__salida()
	{
		$this->dep('editor_ant_docentes')->evt__cancelar();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA ANTECEDENTES PROFESIONALES 
	//-----------------------------------------------------------------------------------

	function evt__pantalla_ant_profesionales__salida()
	{
		$this->dep('editor_ant_profesionales')->evt__cancelar();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA ALLEGADOS 
	//-----------------------------------------------------------------------------------

	function evt__pantalla_allegados__salida()
	{
		$this->dep('editor_allegados')->evt__cancelar();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA BITACORA 
	//-----------------------------------------------------------------------------------

	function evt__pantalla_bitacora__salida()
	{
		$this->dep('editor_bitacora')->evt__cancelar();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA MOODLE 
	//-----------------------------------------------------------------------------------

	function evt__pantalla_moodle__salida()
	{
		$this->dep('editor_moodle')->evt__cancelar();
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Retorna los datos correspondientes al combo "Perfil de Datos" del acceso al sistema.
	 * 
	 * @return array
	 */
	function get_perfiles_datos()
	{
		$filtro = '';
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$filtro = guarani_sql::armar_condicion_in($perfil_datos, 'apex_usuario_perfil_datos', 'usuario_perfil_datos');
		}
		return toba::consulta_php('co_usuarios')->get_perfiles_datos($filtro);
	}
	
	// Se buscan todos los usuarios de araí no asociados que cumplan el filtro
	static function usuarios_arai($filtro)
	{
            return arai_usuarios::getDriver()->getUsuariosByFiltroNoAsociados($filtro);
	}
	
    /**
     * Recupera listado de usuarios de Araí para los combos editables.
     * @param string $filtro    Filtro del combo editable.
     * @return array   Listado de usuarios de Araí.
     */
    static function get_usuarios_arai($filtro = '')
    {

        toba::logger()->debug('ci_edi_personas::get_usuarios_arai $filtro');
        toba::logger()->debug($filtro);

        $usuarios_filtrados = [];

        // Si viene el filtro, es distinto a '%' (cuando inserta un *) y contiene 3 o más carácteres
        if (($filtro != '') && ($filtro != '%') && (strlen($filtro) >= arai_usuarios::MIN_LENGTH_AUTOCOMPLETE_USUARIOS)) {

            // Convierto el filtro a UTF8
            $filtro = utf8_encode($filtro);

            $usuarios = static::usuarios_arai($filtro);

            foreach ($usuarios as $key => $usuario) {
                $usuarios_filtrados[$key]['valor'] = $usuario['identificador'];
                $usuarios_filtrados[$key]['descr'] = arai_usuarios::getDescripcionFormateadaUsuario($usuario);
            }
        }

        return $usuarios_filtrados;
    }

    /**
     * Recupera descripción de un usuario de Araí.
     * @param string $idUsuarioSso    ID de usuario de Araí.
     * @return string   Descripción de un usuario de Araí.
     */
    static function get_descripcion_usuario_arai($idUsuarioSso = null)
    {

        toba::logger()->debug('ci_edi_personas::get_descripcion_usuario_arai $idUsuarioSso');
        toba::logger()->debug($idUsuarioSso);

        // Si $idUsuarioSso es null
        if (is_null($idUsuarioSso)) {
            return null;
        }

        // Es el valor de mdp_personas.arai_identificador_sso
        $usuario = $idUsuarioSso;

        try {

            // Si la versión de la API de Araí Usuarios es mayor o igual a 2
            if (arai_usuarios::getVersionApi() >= 2) {

                $datos_persona = toba::consulta_php('co_personas')->get_persona_x_arai_identificador_sso($idUsuarioSso);

                // Si se trata de una persona nueva o de una persona existente y la columna mdp_personas.arai_uuid es null
                if (empty($datos_persona) || (!empty($datos_persona) && is_null($datos_persona['arai_uuid']))) {
                    // recupero el UUID de la API de Araí Usuarios
                    $usuario = arai_usuarios::getDriver()->getUidUsuario($idUsuarioSso);
                // Si se trata de una persona existente y la columna mdp_personas.arai_uuid es distinta a null
                } else {
                    // Obtengo la columna 'arai_uuid' de 'mdp_personas'
                    $usuario = $datos_persona['arai_uuid'];
                }
            }

            $datos_usuario = arai_usuarios::getDriver()->getUsuario($usuario);
        } catch (Exception $e) {
            $datos_usuario = null;
        }

        // Si no se puede recuperar el usuario desde Araí Usuarios
        if (is_null($datos_usuario)) {
            return $idUsuarioSso;
        } else {// Sino muestro datos del mismo
            return arai_usuarios::getDescripcionFormateadaUsuario($datos_usuario);
        }
    }

    //-----------------------------------------------------------------------------------
	//---- JavaScript -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		$this->js_btn_generar_usuario();
	}

	/*
	 * Funciones necesarias para el botón de Generar Usuario
	 */

	function ajax__generar_usuario($parametros, toba_ajax_respuesta $respuesta)
	{
		$usuario = '';

		$datos = $this->cn()->entidad()->get_datos_usuario();
		// Si hay datos
		if (!empty($datos)) {
			$datos_doc = $this->cn()->entidad()->get_datos_nro_documento_principal();
			// Si tiene documento principal cargado
			if (!empty($datos_doc)) {
				$pais = $datos_doc['pais_documento'];
				$tipo = $datos_doc['tipo_documento'];
				$nro_identificacion = $datos_doc['nro_documento'];
			} else {
				$pais = null;
				$tipo = null;
				$nro_identificacion = null;
			}
			$usuario = usuario::get_usuario_default($pais, $tipo, $nro_identificacion);
		}


		$respuesta->set([$usuario]);
	}

	function js_btn_generar_usuario()
	{
		$id_js = $this->get_objeto_js();
		echo "
			
			function generar_usuario() {
				var parametros = {};
				$id_js.ajax('generar_usuario', parametros, this, cargar_usuario);
				return false;
			}

			cargar_usuario = function(respuesta) {
			
				// Se carga el input con el usuario generado
				var input_usuario = $('#ef_form_38000655_form_usuariousuario');
				input_usuario.val(respuesta[0]);
				
				// Se deshabilita el botón 'Genera Usuario'
				var btn_generar_usuario = $('#js-generar-usuario');
				btn_generar_usuario.prop('disabled', true);
				
			}
			
			$('#ef_form_38000655_form_usuariousuario').on('keyup', function() {
				var usuario = $( this ).val();
				var btn_generar_usuario = $('#js-generar-usuario');
				
				// Si se borra el contenido del input 'Usuario' vuelvo a habilitar el botón
				if (usuario === '') {
					btn_generar_usuario.prop('disabled', false);
				} else {
					btn_generar_usuario.prop('disabled', true);
				}
			});
		";
	}

}