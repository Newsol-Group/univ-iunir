<?php

namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\errores\error_guarani_usuario;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\parametro;

class readmision extends transaccion_alumno
{
	protected $cumple;

	function __construct($persona, $propuesta, $alumno, $plan, $plan_version, $ubicacion, $modalidad = null, $division = null)
	{
		parent::__construct($persona, $propuesta, $alumno, $plan, $plan_version, $ubicacion, $modalidad, $division);

		//si hay un periodo tipo readmision o todos activo
		$datos_periodo_de_inscripcion = \toba::consulta_php('co_periodos_de_inscripcion')->get_periodo_vigente_solicitud_readmision($this->alumno);
		if (!empty($datos_periodo_de_inscripcion['anio_academico'])) {
			$this->cumple = \toba::consulta_php('co_alumnos')->puede_solicitar_readmision($this->alumno, $datos_periodo_de_inscripcion['anio_academico']);
		}
	}

	function puede_readmitirse()
	{
		if (empty($this->cumple)) {
			throw new error_guarani_usuario(kernel::traductor()->trans('error'), 1);
		}
	}

	function solicitudes()
	{
		if (!empty($this->cumple)) {
			$mat_estado_solicitud_readmision = parametro::get_valor('mat_estado_solicitud_readmision');
			$fecha_solicitud = \guarani_fecha::get_hoy();
			$anio_academico = $this->cumple[0]['anio_academico_solicitud_readmision'];
			$perdida_regularidad = $this->cumple[0]['perdida_regularidad'];

			/* si el parametro  mat_estado_solicitud_readmision es:
			 *  P	Las solicitudes de readmisión se crearán en estado pendiente para su posterior evaluación
			 *  A	Las solicitudes de readmisión se aceptarán automáticamente sin evaluación */

			if ($mat_estado_solicitud_readmision == 'A') {
				try {
					kernel::db()->abrir_transaccion();

					//insert_solicitud_readmision con estado A y observacion aprobacion autmatica
					$fecha_procesamiento = \guarani_fecha::get_hoy();
					$estado = 'A';
					$observaciones = "Se aceptó automáticamente"; //Si se acepta, registrar el texto
					\guarani::act('act_vencimientos_regularidad')->insert_solicitud_readmision($this->alumno, $perdida_regularidad, $anio_academico, $fecha_solicitud, $estado, $observaciones, $fecha_procesamiento);
					$fecha_vencimiento_readmision = ($anio_academico + 1) . '-03-31';
					\guarani::act('act_alumnos')->insertar_readmision($perdida_regularidad, $anio_academico, \vencimiento_regularidad::readmision_tipo_reincorporacion, \comunes::si, \guarani_fecha::get_hoy(), $fecha_vencimiento_readmision);
					$cant_readmisiones = \toba::consulta_php('co_alumnos')->get_cant_readmisiones($this->alumno);
					\guarani::act('act_alumnos')->actualizar_cant_readmisiones($this->alumno, $cant_readmisiones + 1);
					\guarani::act('act_alumnos')->actualizar_regularidad($this->alumno, \comunes::si);


					kernel::db()->cerrar_transaccion();
					return kernel::traductor()->trans('800MAT_aceptacion_solicitudes_readmision_ok');
				} catch (toba_error $e) {
					kernel::db()->abortar_transaccion();
					throw $e;
				}
			} elseif ($mat_estado_solicitud_readmision == 'P') {

				try {
					kernel::db()->abrir_transaccion();
					$estado = 'P';
					$fecha_procesamiento = '';
					$observaciones = '';
					\guarani::act('act_vencimientos_regularidad')->insert_solicitud_readmision($this->alumno, $perdida_regularidad, $anio_academico, $fecha_solicitud, $estado, $observaciones, $fecha_procesamiento);
					kernel::db()->cerrar_transaccion();
					return kernel::traductor()->trans('800MAT_aceptacion_solicitudes_readmision_pendiente');
				} catch (toba_error $e) {
					kernel::db()->abortar_transaccion();
					throw $e;
				}
			}
		} else {
			throw new error_guarani_usuario(kernel::traductor()->trans('error'), 1);
		}
	}

}