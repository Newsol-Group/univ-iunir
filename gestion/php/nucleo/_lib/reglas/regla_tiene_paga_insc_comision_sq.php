<?php
class regla_tiene_paga_insc_comision_sq extends regla
{
	function validar()
	{
		// Parámetros de contexto.
		$comision = $this->get_parametro('comision');
		$alumno = $this->get_parametro('alumno');

		$datos_alumno = current(guarani::alumno()->get_datos_alumno($alumno));
		$es_cobrable = toba::consulta_php('co_comisiones')->es_cobrable($comision);

		$resultado = true;
		if ($es_cobrable) {
			$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($datos_alumno['propuesta']);
			$usa_sq = guarani::parametros()->get_valor_en_reglas('sq_usa_sanaviron', $ras);
			if ($usa_sq == comunes::si) {
				$datos_insc = toba::consulta_php('co_inscripciones_cursadas')->get_insc_cursada_por_alumno_comision($alumno, $comision);
				// Se obtiene desde SQ el estado del trámite de pago de la suscripción.
				$id_tramite_ref = $datos_insc['nro_transaccion'];
				if (!is_null($id_tramite_ref)) {

                                    // Recupero estado registrado en la tabla int_sq_pagos
                                    $estado_pago = toba::consulta_php('co_cobros')->get_estado_pago($id_tramite_ref, cobro::CONCEPTO_NP_COMISION);

                                    // Si no hay estado registrado en la tabla int_sq_pagos
                                    if (is_null($estado_pago)) {
                                        // Cargo una clase u otra dependiendo si se trata de Autogestión o Gestión
                                        $cobro_class = (\comunes::es_interfaz_autogestion())? \SIU\Chulupi\kernel::localizador()->instanciar('modelo_g3\cobro') : 'cobro';
                                        // Recupero estado del consumo externo
					$estado_consumo = $cobro_class::get_estado_consumo_externo(cobro::TIPO_CONSUMO_SUSCRIPCION, $id_tramite_ref);
					$resultado = !empty($estado_consumo) ? ($estado_consumo['estado'] == cobro::ESTADO_CONSUMO_PAGADO) : false;
                                        
                                        // Si fue pagado o venció
                                        if (in_array($estado_consumo['estado'], [cobro::ESTADO_CONSUMO_PAGADO, cobro::ESTADO_CONSUMO_VENCIDO])) {
                                            $estado = ($estado_consumo['estado'] == cobro::ESTADO_CONSUMO_PAGADO)? \cobro::ESTADO_PAGO_PAGO : \cobro::ESTADO_PAGO_VENCIO;
                                            // Registra pago o vencimiento en la tabla int_sq_pagos
                                            \guarani::act('act_pagos')->registrar_pago($id_tramite_ref, $estado, \cobro::CONCEPTO_NP_COMISION);
                                        }
                                        
                                    } else {// Si hay estado registrado en la tabla int_sq_pagos
                                        $resultado = ($estado_pago == cobro::ESTADO_PAGO_PAGO);
                                    }

				}
			}
		}

		$resultado = $this->resultado_final($resultado);
		if (!$resultado) {
			$nombre_comision = toba::consulta_php('co_comisiones')->get_nombre_comision($comision);
			$this->set_parametros_mensaje_validacion(array($datos_alumno['apellido_y_nombre'], $nombre_comision), true);
		}
		return $resultado;
	}
}