<?php
class regla_tiene_paga_insc_propuesta_sq extends regla
{
	function validar()
	{
		// Parámetros de contexto
		$alumno = $this->get_parametro('alumno');

		$datos_alumno = current(guarani::alumno()->get_datos_alumno($alumno));
		$es_cobrable = toba::consulta_php('co_planes')->es_plan_cobrable($datos_alumno['plan']);

		$resultado = true;
		if ($es_cobrable) {
			$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($datos_alumno['propuesta']);
			$usa_sq = guarani::parametros()->get_valor('sq_usa_sanaviron', $ras, false);
			if ($usa_sq == comunes::si) {
				$propuesta_aspira = guarani::co('co_inscripciones')->get_id_ultima_propuesta_aspira($datos_alumno['persona'], $datos_alumno['propuesta']);
				$nro_transaccion = toba::consulta_php('co_inscripciones')->get_nro_transaccion_propuesta_aspira($propuesta_aspira);

                                // Recupero estado registrado en la tabla int_sq_pagos
                                $estado_pago = toba::consulta_php('co_cobros')->get_estado_pago($nro_transaccion, cobro::CONCEPTO_NP_PROPUESTA);

                                // Si no hay estado registrado en la tabla int_sq_pagos
                                if (is_null($estado_pago)) {
                                    // Cargo una clase u otra dependiendo si se trata de Autogestión o Gestión
                                    $cobro_class = (\comunes::es_interfaz_autogestion())? \SIU\Chulupi\kernel::localizador()->instanciar('modelo_g3\cobro') : 'cobro';
                                    // Recupero estado del consumo externo
                                    $estado_consumo = $cobro_class::get_estado_consumo_externo(cobro::TIPO_CONSUMO_SUSCRIPCION, $nro_transaccion);
                                    $resultado = !empty($estado_consumo) ? ($estado_consumo['estado'] == cobro::ESTADO_CONSUMO_PAGADO) : false;

                                    // Si fue pagado o venció
                                    if (in_array($estado_consumo['estado'], [cobro::ESTADO_CONSUMO_PAGADO, cobro::ESTADO_CONSUMO_VENCIDO])) {
                                        $estado = ($estado_consumo['estado'] == cobro::ESTADO_CONSUMO_PAGADO)? \cobro::ESTADO_PAGO_PAGO : \cobro::ESTADO_PAGO_VENCIO;
                                        // Registra pago o vencimiento en la tabla int_sq_pagos
                                        \guarani::act('act_pagos')->registrar_pago($nro_transaccion, $estado, \cobro::CONCEPTO_NP_PROPUESTA);
                                    }

                                } else {// Si hay estado registrado en la tabla int_sq_pagos
                                    $resultado = ($estado_pago == cobro::ESTADO_PAGO_PAGO);
                                }
			}
		}

		$resultado = $this->resultado_final($resultado);
		if (!$resultado) {
			$this->set_parametros_mensaje_validacion(array($datos_alumno['apellido_y_nombre'], $datos_alumno['propuesta_nombre']), true);
		}
		return $resultado;
	}
}