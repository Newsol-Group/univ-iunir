<?php
class ci_estado_firma_documento extends guarani_pers_ci
{
	protected $s__documento;
	protected $s__movimientos;
	protected $s__estado_codigo;
	protected $s__autorizado;
	protected $s__rechazado;
	protected $s__autorizaciones;

	function ini()
	{
		$documento = toba::memoria()->get_parametro('id_documento');
		if (!empty($documento)) {
			$this->s__documento = $documento;
                        // Recupero los movimientos del documento en SIU-Araí
                        $this->s__movimientos = arai_documentos::getMovimientosDocumento($this->s__documento);
                        // Recupero si el documento fue autorizado
                        $this->s__autorizado = arai_documentos::esDocumentoAutorizadoByMovimientos($this->s__movimientos);
                        // Tomo el resultado del último evento
                        $this->s__estado_codigo = arai_documentos::getResultadoUltimoEvento($this->s__movimientos);
                        // Si el documento fue rechazado
                        $this->s__rechazado = ($this->s__estado_codigo == arai_documentos::FALLO_PROCESO_AUTORIZACION);
                        // Tomo las autorizaciones
                        $this->s__autorizaciones = arai_documentos::getAutorizacionesByMovimientos($this->s__movimientos);
                        
                        // Si el documento fue autorizado o rechazado guardo los datos,
                        // no lo puedo hacer al estar pendiente de firma ya que lo pueden seguir firmando
                        if ($this->s__autorizado || $this->s__rechazado) {
                            // Guardo los datos recuperados desde SIU-Araí para no volverlos a consultar
                            $this->guardar_info();
                        }
		}
	}
	
	function get_estado_firma()
	{
		try {
                    // Si el documento fue rechazado
                    if ($this->s__rechazado) {
                        $estado_firma = arai_documentos::DOCUMENTO_RECHAZADO_DESC;
                    // Si el documento fue autorizado
                    } else if ($this->s__autorizado) {
                        $estado_firma = arai_documentos::DOCUMENTO_FIRMADO_DESC;
                    // Sino el documento esta pendiente de firma
                    } else {
                        $estado_firma = arai_documentos::DOCUMENTO_PENDIENTE_FIRMA_DESC;
                    }
                    return $estado_firma;
		} catch (Exception $e){
                    return arai_documentos::DOCUMENTO_NO_INFO_DESC;
		}
	}

        function get_firmantes()
	{
            try {
                
                $firmantes = [];

                // Si hay firmantes
                if (!empty($this->s__autorizaciones)) {

                    foreach ($this->s__autorizaciones as $autorizacion) {

                        // Si se trata de una firma
                        if ($autorizacion['estado'] == arai_documentos::ESTADO_SOLICITUD_AUTORIZACION_ACEPTADA_DESC) {
                            // Recupero datos de la persona de la DB
                            $datos_persona = toba::consulta_php('co_personas')->get_persona_x_arai_identificador_sso($autorizacion['usuario_sso']);
                            // Si no esta vinculada la persona con el usuario de SIU-Araí
                            if (empty($datos_persona)) {
                                $firmantes[] = $autorizacion['usuario_sso'];
                            } else {
                                $firmantes[] = "{$datos_persona['apellido_elegido']} {$datos_persona['nombres_elegido']}";
                            }
                        }
                    }

                }
                
                if (empty($firmantes)) {
                    return guarani_mensajes::get('800_sin_movimientos_autorizantes');
                } else {
                    return implode(', ', $firmantes);
                }
                
            } catch (Exception $e){
                return '';
            }
	}

        function get_pendientes_firma()
	{
            try {

                $pendientes = [];

                // Si hay firmantes
                if (!empty($this->s__autorizaciones)) {

                    foreach ($this->s__autorizaciones as $autorizacion) {

                        // Si se trata de un pendiente de firma
                        if ($autorizacion['estado'] == arai_documentos::ESTADO_SOLICITUD_AUTORIZACION_PENDIENTE_DESC) {
                            // Recupero datos de la persona de la DB
                            $datos_persona = toba::consulta_php('co_personas')->get_persona_x_arai_identificador_sso($autorizacion['usuario_sso']);
                            // Si no esta vinculada la persona con el usuario de SIU-Araí
                            if (empty($datos_persona)) {
                                $pendientes[] = $autorizacion['usuario_sso'];
                            } else {
                                $pendientes[] = "{$datos_persona['apellido_elegido']} {$datos_persona['nombres_elegido']}";
                            }
                        }
                    }

                }

                if (empty($pendientes)) {
                    return guarani_mensajes::get('800_sin_movimientos_autorizantes');
                } else {
                    return implode(', ', $pendientes);
                }
                
            } catch (Exception $e){
                return '';
            }
	}

        function get_rechazado_por()
	{
            try {

                $rechazado_por = '';
                $usuario_sso = null;

                // Si hay firmantes
                if (!empty($this->s__autorizaciones)) {

                    foreach ($this->s__autorizaciones as $autorizacion) {
                        // Si se trata de un rechazo
                        if ($autorizacion['estado'] == arai_documentos::ESTADO_SOLICITUD_AUTORIZACION_RECHAZADA_DESC) {
                            $usuario_sso = $autorizacion['usuario_sso'];
                            break;
                        }
                    }

                }

                // Si no hay info de rechazo es porque la rechazo el usuario origen/originante
                if (is_null($usuario_sso)) {
                    return guarani_mensajes::get('800_usuario_originante');
                }

                // Recupero datos de la persona de la DB
                $datos_persona = toba::consulta_php('co_personas')->get_persona_x_arai_identificador_sso($usuario_sso);
                // Si no esta vinculada la persona con el usuario de SIU-Araí
                if (empty($datos_persona)) {
                    $rechazado_por = $usuario_sso;
                } else {
                    $rechazado_por = "{$datos_persona['apellido_elegido']} {$datos_persona['nombres_elegido']}";
                }

                return $rechazado_por;
            } catch (Exception $e){
                return '';
            }
	}

        function guardar_info()
	{
            try {
                
                toba::db()->abrir_transaccion();

                // Tomo el modelo de autorización del documento
                $modelo = arai_documentos::getModeloAutorizacionByMovimientos($this->s__movimientos);

                // Actualizo el estado del documento
                guarani::act('act_arai_documentos')->actualizar_estado_documento($this->s__documento, $this->s__autorizado, $this->s__estado_codigo, $modelo);

                // Registro las autorizaciones
                if (!empty($this->s__autorizaciones)) {
                    guarani::act('act_arai_documentos')->eliminar_autorizaciones_documento($this->s__documento);
                    guarani::act('act_arai_documentos')->agregar_autorizaciones_documento($this->s__documento, $this->s__autorizaciones);
                }

                toba::db()->cerrar_transaccion();
            } catch (Exception $e){
                toba::db()->abortar_transaccion();
            }
	}

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evt__cancelar = function()
		{
			window.close();
		}
		";
	}
}