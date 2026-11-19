<?php

abstract class ci_rep_actas extends ci_rep_columnas_opc_sin_listado_inicial
{
	function ini__operacion() 
	{
		parent::ini__operacion();
		foreach ($this->s__filtros_especiales as $columna) {
			$this->s__parametros_filtro[$columna] = null;
		}
	}
	
	function get_tabla_principal()
	{
		return 'sga_actas';
	}
	
	function get_opciones_columnas_optativas() 
	{
		return array(
			array('valor' => 'cant_alumnos', 'descr' => 'Cant. alumnos'),
			array('valor' => 'docentes', 'descr' => 'Docentes'),
			array('valor' => 'version', 'descr' => 'Versión del acta'),
			array('valor' => 'version_impresa', 'descr' => 'Versión impresa'),
			array('valor' => 'rectifica_a', 'descr' => 'Rectifica a'),
			array('valor' => 'acta_digital', 'descr' => 'Acta digital'),
			array('valor' => 'estado_firma', 'descr' => 'Estado de la Firma'),
			array('valor' => 'firmantes', 'descr' => 'Firmantes'),
			array('valor' => 'pendientes_firma', 'descr' => 'Pendientes de firma'),
			array('valor' => 'rechazado_por', 'descr' => 'Rechazado por'),
		);
	}
	
	protected function formatear_datos_cuadro($actas)
	{
                $msj_usuario_originante = guarani_mensajes::get('800_usuario_originante');

		foreach ($actas as $clave => $acta) {

                        $actas[$clave]['estado_firma'] = comunes::no_aplica_desc;
                        $actas[$clave]['firmantes'] = comunes::no_aplica_desc;
                        $actas[$clave]['pendientes_firma'] = comunes::no_aplica_desc;
                        $actas[$clave]['rechazado_por'] = comunes::no_aplica_desc;
                        $actas[$clave]['acta_digital'] = ($acta['acta_digital'] == comunes::si)? comunes::si_desc : comunes::no_desc;

                        // Si es acta digital
			if ($acta['acta_digital'] == comunes::si) {

                            // Si el acta fue procesada por el comando "bin/guarani sincronizar_documentos_arai"
                            if (isset($acta['id_documento_digital'])) {

                                // Si se utilizaron las notificaciones de Araí-Documentos
                                if (!is_null($acta['arai_autorizado'])) {

                                    // Si fue firmado en Araí-Documentos
                                    if ($acta['arai_autorizado']) {

                                        $actas[$clave]['estado_firma'] = arai_documentos::DOCUMENTO_FIRMADO_DESC;
                                        $actas[$clave]['firmantes'] = $this->detalle_autorizaciones($acta['arai_firmantes']);
                                        
                                    // Si NO fue firmado en Araí-Documentos
                                    } else {

                                        // Si el documento fue rechazado en Araí-Documentos
                                        if ($acta['arai_estado_codigo'] == arai_documentos::FALLO_PROCESO_AUTORIZACION) {
                                            $actas[$clave]['estado_firma'] = arai_documentos::DOCUMENTO_RECHAZADO_DESC;
                                            // Si viene vacío es probable que lo rechazo el origen/originante
                                            if (is_null($acta['arai_rechazado_por'])) {
                                                $actas[$clave]['rechazado_por'] = $msj_usuario_originante;
                                            } else {
                                                $actas[$clave]['rechazado_por'] = $this->detalle_autorizaciones($acta['arai_rechazado_por']);
                                            }
                                        // Sino el documento esta pendiente de firma
                                        } else {
                                            $actas[$clave]['estado_firma'] = arai_documentos::DOCUMENTO_PENDIENTE_FIRMA_DESC;
                                            $actas[$clave]['firmantes'] = $this->detalle_autorizaciones($acta['arai_firmantes']);
                                            $actas[$clave]['pendientes_firma'] = $this->detalle_autorizaciones($acta['arai_pendientes_firma']);
                                        }

                                    }

                                // Si NO se utilizaron las notificaciones de Araí-Documentos hay que buscar a la API
                                } else {
                                    $actas[$clave]['estado_firma'] = $this->link_estado($acta['id_documento_digital']);
                                }
                            // Si el acta aún no fue procesada por el comando "bin/guarani sincronizar_documentos_arai"
                            } else {
                                $actas[$clave]['estado_firma'] = arai_documentos::DOCUMENTO_PROCESO_ENVIO_DESC;
                            }
			}
		}
		return $actas;
	}
	
	protected function link_estado($id_documento_digital) 
	{
		$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '5000123', ['id_documento' => $id_documento_digital], ['celda_memoria' => 'celda_estado_firma']);
		$javascript = "abrir_popup('info','$vinculo',{'width':'600','height':'300'})";
		$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
		$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver estado de la firma');
		$salida .= '</a>';
		return $salida;
	}

        protected function detalle_autorizaciones($autorizantes)
        {
            // Si no hay autorizantes
            if (is_null($autorizantes)) {
                return guarani_mensajes::get('800_sin_movimientos_autorizantes');
            // Si hay autorizantes
            } else {
                // Convierto el string del JSON a UTF-8 sino se rompe cuando hay caracteres especiales
                $autorizantes = iconv("ISO-8859-1", "UTF-8", $autorizantes);
                $autorizantes = json_decode($autorizantes, true);
                $detalle_autorizantes = [];
                foreach ($autorizantes as $autorizante) {
                    // Si no se puede recuperar el nombre o el apellido
                    if (is_null($autorizante['apellido']) || is_null($autorizante['nombres'])) {
                        $autorizante_descr = $autorizante['usuario_sso'];
                    } else {
                        $autorizante_descr = "{$autorizante['apellido']} {$autorizante['nombres']}";
                        // Vuelvo a convertir a ISO-8859-1 para mostrar los caracteres especiales
                        $autorizante_descr = iconv("UTF-8", "ISO-8859-1", $autorizante_descr);
                    }
                    $detalle_autorizantes[] = $autorizante_descr;
                }

                return implode(', ', $detalle_autorizantes);
            }
        }

}