<?php
/**
 *
 *
 * @package		Matricula
 * @subpackage	Personas
 * 
 */
class ci_nav_personas extends ci_nav_sin_listado_inicial
{
	protected $s__operacion_a_volver;
	protected $s__celda_a_restaurar;
	protected $s__pantalla_retorno;
	protected $s__persona;
	protected $s__ajax = false;

	function ini__operacion()
	{
		if (!$this->s__ajax) {
			$this->s__operacion_a_volver = toba::memoria()->get_parametro('operacion_a_volver');
			$this->s__celda_a_restaurar = toba::memoria()->get_parametro('celda_a_restaurar');
			$this->s__pantalla_retorno = toba::memoria()->get_parametro('pantalla_retorno');
			$this->s__persona = toba::memoria()->get_parametro('persona');
			if (!empty($this->s__persona)) {
				$this->evt__cuadro__seleccion(array('persona' => $this->s__persona));
			} else {
				if (!empty($this->s__operacion_a_volver) && !empty($this->s__celda_a_restaurar)) {
					$this->evt__agregar();
				}
			}
			$this->s__ajax = true;
		}
	}

	function conf()
	{
		parent::conf();
		if ($this->cn()->entidad_cargada() && $this->cn()->entidad()->hay_cursor_personas()) {
			$datos_persona = $this->cn()->entidad()->get_datos_personas();
			$this->set_titulo("{$datos_persona['apellido']}, {$datos_persona['nombres']}");							
			if (isset($datos_persona['usuario']) && $datos_persona['usuario'] == toba::usuario()->get_id() && $this->existe_evento('eliminar')) {
				$this->evento('eliminar')->desactivar();
			}
		}
	}

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		if (!empty($this->s__operacion_a_volver) && !empty($this->s__celda_a_restaurar)) {
			$pantalla->evento('cancelar')->set_imagen('volver.png');
		} else {
			$pantalla->evento('cancelar')->set_imagen('deshacer.png');
		}

		parent::conf__edicion($pantalla);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_nro_documento = '';
			if ((isset($datos['nro_documento']))) {
				$tabla_personas = $this->cn()->usa_perfil_de_datos() ? 'personas' : 'mdp_personas';
				$expresion_filtro_nro_documento = $this->dep('filtro')->columna('nro_documento')->get_sql_where();
				$filtro_nro_documento = "AND EXISTS (SELECT mdp_personas_documentos.documento FROM mdp_personas_documentos WHERE mdp_personas_documentos.persona = $tabla_personas.persona AND $expresion_filtro_nro_documento)";
				$this->dep('filtro')->columna('nro_documento')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_nro_documento";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__eliminar()
	{
		try {
			// Validaciones antes de eliminar
			$this->cn()->entidad()->validar_datos_eliminar_persona();
			// Se eliminan todos los datos de la persona, censales, del docente y recordatorios
			toba::db()->abrir_transaccion();
			$this->dep('editor')->dep('editor_persona')->eliminar_foto_tmp();
			$this->cn()->entidad()->eliminar_recordatorios_usuario();
			$this->cn()->entidad()->eliminar_aud_login_autogestion();
			$this->cn()->entidad()->eliminar_configuracion();
			$this->cn()->entidad()->eliminar_requisitos_digitales();
			$this->cn()->entidad()->eliminar_mensaje_destinatarios();
			$this->cn()->entidad()->eliminar_encuestas_pendientes();
			$this->cn()->entidad()->eliminar_inscripcion_propuesta_log();

                        // Elimino las cuentas asociadas en SIU-Araí
			$this->eliminar_cuentas_arai();

			$this->cn()->eliminar();
			toba::db()->cerrar_transaccion();
			// Mensaje de ok y seteo de pantalla
			toba::notificacion()->agregar($this->get_mensaje('eliminar_ok'), 'info');
			$this->set_pantalla('seleccion');
		} catch (guarani_error $e) {
			toba::db()->abortar_transaccion();
			$e->notificar();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->cn()->resetear();
			$this->cn()->cargar($this->s__seleccion);
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}

        function eliminar_cuentas_arai()
        {
            $identificador_usuario = null;
            $arai_identificador_sso = $this->cn()->entidad()->get_arai_identificador_sso();

            // Si la versión de la API de Araí Usuarios es 1
            if (arai_usuarios::getVersionApi() == 1) {

                // Araí Usuarios API v1 usa mdp_personas.arai_identificador_sso
                $identificador_usuario = $arai_identificador_sso;

            // Si la versión de la API de Araí Usuarios es mayor o igual a 2
            } else {

                // Araí Usuarios API v2 usa mdp_personas.arai_uuid
                $arai_uuid = $this->cn()->entidad()->get_arai_uuid();

                // Si el UUID viene vacío de la DB lo busco en la API de Araí Usuarios
                if (empty($arai_uuid) && !empty($arai_identificador_sso)) {
                    // recupero el UUID de la API de Araí Usuarios
                    $arai_uuid = arai_usuarios::getDriver()->getUidUsuario($arai_identificador_sso);
                }

                // Araí Usuarios API v2
                $identificador_usuario = $arai_uuid;
            }

            // Si tiene un usuario asociado en SIU-Araí le elimino las cuentas
            if (!empty($identificador_usuario)) {
                // Elimino las cuentas asociadas en SIU-Araí
                $this->cn()->entidad()->eliminar_cuentas_arai($identificador_usuario);
            }
        }

	function evt__cancelar()
	{
		$this->dep('editor')->dep('editor_persona')->eliminar_foto_tmp();
		$this->cn()->resetear();

		if (!empty($this->s__operacion_a_volver) && !empty($this->s__celda_a_restaurar)) {
			$parametros = array();
			$parametros['id_nuevo_registro'] = 0;
			if (isset($this->s__pantalla_retorno)) {
				$parametros['pantalla_retorno'] = $this->s__pantalla_retorno;
			}
			echo toba_js::abrir();
			echo "document.location.href='" .
			toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $this->s__operacion_a_volver, $parametros, array('celda_memoria' => $this->s__celda_a_restaurar)) . "'\n";
			echo toba_js::cerrar();
		} else {
			$this->set_pantalla('seleccion');
		}
	}

	function evt__guardar()
	{
		try {
			// Se preserva una marca para que, en caso de que se esté dando de alta una
			// nueva persona, tras guardar se recargue desde la base, para trabajar con 
			// las mismas condiciones (dado que tras guardar no se retorna a Selección)
			$recargar = false;
			if ($this->cn()->entidad_es_nueva()) {
				$recargar = true;
			}
			
			$persona = $this->cn()->guardar();			
			if (!empty($this->s__operacion_a_volver) && !empty($this->s__celda_a_restaurar)) {
				$parametros = array();
				$parametros['id_nuevo_registro'] = $persona[0]['persona'];
				if (isset($this->s__pantalla_retorno)) {
					$parametros['pantalla_retorno'] = $this->s__pantalla_retorno;
				}
				echo toba_js::abrir();
				echo "document.location.href='" .
				toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $this->s__operacion_a_volver, $parametros, array('celda_memoria' => $this->s__celda_a_restaurar)) . "'\n";
				echo toba_js::cerrar();
			} else {
				// Si se dejó la marca correspondiente, se hace una recarga desde la base
				if ($recargar) {
					$this->cn()->resetear();
					$this->cn()->cargar(array('persona' => $persona[0]['persona']));
				}
				toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
			}
		} catch (guarani_error $e) {
			throw $e;
		}
	}

}