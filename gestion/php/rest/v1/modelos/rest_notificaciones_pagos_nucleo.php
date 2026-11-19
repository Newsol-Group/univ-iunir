<?php

namespace SIU\Guarani\php\rest\v1\modelos;

use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_validador;
use SIU\Guarani\php\rest\v1\notificaciones_pagos\recurso_notificaciones_pagos;

class rest_notificaciones_pagos_nucleo
{
    
    /**
     * @return rest_filtro_sql
     */
    function get_filtro_base(){
        $filtro = new rest_filtro_sql();
        return $filtro;
    }

    function post($data)
    {

        try {

                \toba::db()->abrir_transaccion();

                $modelos = recurso_notificaciones_pagos::_get_modelos();
                $modelo_notificacion_pago = $modelos['Notificacion_pago'];
                //Valido el modelo
                rest_validador::validar($data, $modelo_notificacion_pago);

		$nro_transaccion = $data['transaccion'];
		$estado = $data['estado'];
		$concepto = $data['concepto'] ?? \cobro::CONCEPTO_NP_COMISION;

		// Si el concepto es una comisión
		if($concepto == \cobro::CONCEPTO_NP_COMISION){

			$datos_inscripcion = \toba::consulta_php('co_inscripciones_cursadas')->get_insc_cursada_por_nro_transaccion($nro_transaccion);

			if (empty($datos_inscripcion)) {
				throw new rest_error(404, "La inscripción a cursada con el numero de transacción '{$nro_transaccion}' no existe");
			}

			if ($estado == \cobro::ESTADO_PAGO_VENCIO) {
				// Se procede a dar de baja la inscripción a la comisión.
				$result = \guarani::act('act_inscripcion_cursadas')->eliminar_insc_cursada_func($datos_inscripcion[0]['inscripcion']);
			} elseif ($estado == \cobro::ESTADO_PAGO_PAGO) {
				// Se llama al proceso de actividades pendientes
				$parametros = array(
					'inscripciones' => $datos_inscripcion,
					'operacion' => '800SIUCUR025'
				);
				$proceso = \guarani::proceso_bk(\pro_proc_insc_act_pendientes::nombre);
				$proceso->set_datos($parametros);
				\guarani::ejecutar_proceso_bk($proceso);
			}

		}
		// si el concepto es una propuesta
		elseif($concepto == \cobro::CONCEPTO_NP_PROPUESTA){

			$datos_inscripcion = \toba::consulta_php('co_inscripciones')->get_datos_inscripcion_by_nro_transaccion($nro_transaccion);

			if (empty($datos_inscripcion)) {
				throw new rest_error(404, "La inscripción a propuesta con el numero de transacción '{$nro_transaccion}' no existe");
			}

			if ($estado == \cobro::ESTADO_PAGO_VENCIO) {

				$observaciones = \guarani_mensajes::get('800_motivo_baja_falta_pago');
				// Se actualiza la calidad del alumno se inserta el registro histórico de cambio de calidad.
				\guarani::act('act_alumnos')->actualizar_calidad_alumno($datos_inscripcion['alumno'], \alumno::calidad_pasivo);
				\guarani::act('act_alumnos')->insertar_cambio_calidad($datos_inscripcion['alumno'], \alumno::calidad_pasivo, \alumno::motivo_calidad_falta_pago, 'now()', $observaciones);


			}

		}

                \toba::db()->cerrar_transaccion();

        // En caso de arrojar un 400 o 404
        } catch (rest_error $e) {

            \toba::db()->abortar_transaccion();
            // Relanzo la excepción
            throw $e;

        // En caso de arrojar un Error o Excepción devuelvo 500
        } catch (\Throwable $e) {

            \toba::logger_ws()->error($e->getMessage());
            \toba::logger_ws()->error($e->getTraceAsString());
            \toba::db()->abortar_transaccion();

            throw new rest_error(500, \guarani_mensajes::get('800_fallo_notificacion_pagos'), [$e->getMessage()]);

        }

    }
}