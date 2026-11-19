<?php
/**
 *
 *
 * @package 	Matricula
 * @subpackage 	Actualizaciones
 * 
 */

class pro_cierre_periodo_insc_nucleo extends guarani_procesos_bk
{
	const nombre = 'pro_cierre_periodo_insc';
	
	const reporte_rechazadas = 1; // Inscripciones rechazadas
	const reporte_aceptadas = 2; // Inscripciones aceptadas
	const reporte_pendientes = 3; // Inscripciones pendientes
	const reporte_resultados = 4; // Reporte para mostrar cantidades

	protected $rechazadas;
	protected $aceptadas;
	protected $pendientes;
	
	// Datos de inscripciones con legajos para actualizar.
	protected $datos;
	protected $usuario_actual;

	function set_datos($datos = array())
	{
		$this->usuario_actual = $datos['usuario'];
		$this->datos = $datos['datos'];
	}

	function procesar()
	{
		$paso = 100 / guarani_arreglos::count($this->datos);

		$this->rechazadas = 0;
		$this->aceptadas = 0;
		$this->pendientes = 0;

		// Procesamiento de cada inscripción.
		foreach ($this->datos as $inscripcion) {
			
			$param_mat_rechazar_inscripcion = guarani::parametros()->get_valor('mat_rechazar_inscripcion', $inscripcion['propuesta']);
			
			// Se obtienen los requisitos generales no exceptuados.
			$where_req_grales = " sga_requisitos_aspirante.exceptuado = '" . comunes::no . "'";
			$req_generales = guarani::co('co_requisitos')->get_requisitos_generales_propuesta_aspira($inscripcion['propuesta_aspira'], $where_req_grales);

			$cantidad_requisitos = guarani_arreglos::count($req_generales);
			// Control de aborto en proceso
			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			}
			if ($cantidad_requisitos > 0) {
				$contador_ok = 0;
				$contador_no_cumplidos = 0;

				$req_no_cumplidos = array();

				foreach ($req_generales as $req_gral) {
					/**
					 * La fecha tope de presentación de cada requisito es la mayor entre:
					 *		- Fecha de prórroga otorgada al alumno (sga_requisito_aspirante.fecha_prorroga).
					 *		- Fecha de excepción de presentación en el período de inscripción (sga_requisitos_ingreso_excep.fecha_tope), si existe, o fecha de cierre definida en el período de inscripción (sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos).
					 */
					$fecha_tope = guarani::co('co_requisitos')->get_fecha_limite_presentacion($inscripcion['propuesta_aspira'], $req_gral['requisito']);
					if ($req_gral['fecha_prorroga'] != null) {
						if ($req_gral['fecha_prorroga'] > $fecha_tope) {
							$fecha_tope = $req_gral['fecha_prorroga'];
						}
					}

					$hoy = guarani_fecha::get_hoy();
					$requisito_cumplido = $this->requisito_cumplido($req_gral, $inscripcion, $hoy, $fecha_tope);

					if ($requisito_cumplido) {
						$contador_ok++;
					} else {
						// El requisito no fue presentado: verificar si la fecha tope de presentación está vencida.
						if (guarani_fecha::es_mayor($hoy, $fecha_tope)) {
							$contador_no_cumplidos++;
							$req_no_cumplidos[] = $req_gral['requisito'];
						}
					}
				}

				if ($contador_no_cumplidos > 0) {
					// Si las fecha tope de ALGÚN REQUISITO no presentado del aspirante están vencidas y/o los requisitos están 
					// presentados fuera de término, la inscripción se rechazará siempre y cuando el aspirante no tenga historia académica
					if ($param_mat_rechazar_inscripcion == 'N') {
						// Rechaza la inscripcion sin importar si tiene o no Historia Académica.
						$this->rechazar_inscripcion($inscripcion, $req_no_cumplidos);
						$this->log->set($inscripcion, array(self::reporte_rechazadas));
						$this->rechazadas++;
					} elseif ($param_mat_rechazar_inscripcion == 'A') {
						// Rechaza la inscripcion y anula automaticamente la historia académica si tiene.
						$this->rechazar_inscripcion($inscripcion, $req_no_cumplidos, true);
						$this->log->set($inscripcion, array(self::reporte_rechazadas));
						$this->rechazadas++;
					} elseif ($param_mat_rechazar_inscripcion == 'M') {
						// No rechaza a aquellos alumnos que tienen historia académica debido a que debe invalidarse manualmente. 
						if (!alumno::tiene_historia_academica($inscripcion['alumno'])) {
							// Si no tiene historia académica entonces se rechaza la inscripción.
							$this->rechazar_inscripcion($inscripcion, $req_no_cumplidos);
							$this->log->set($inscripcion, array(self::reporte_rechazadas));
							$this->rechazadas++;
						} else {
							$this->log->set($inscripcion, array(self::reporte_pendientes));
							$this->pendientes++;
						}
					}
				} elseif ($contador_ok == $cantidad_requisitos) {
					// Si todos los requisitos están presentados en término y/o excptuados, la inscripción se acepta 
					$this->aceptar_inscripcion($inscripcion);
					$this->log->set($inscripcion, array(self::reporte_aceptadas));
					$this->aceptadas++;
				} else {
					// En el resto de los casos, la inscripción continuará en estado pendiente
					$this->log->set($inscripcion, array(self::reporte_pendientes));
					$this->pendientes++;
				}
			} else {
				// Si no hay requisitos generales no exceptuados y de cumplimiento obligatorio establecidos,
				// la inscripción se acepta 
				$this->aceptar_inscripcion($inscripcion);
				$this->log->set($inscripcion, array(self::reporte_aceptadas));
				$this->aceptadas++;
			}
			
			$this->log->guardar();
			$this->log_estado->incrementar_progreso($paso);
		}

		$mensaje_resultado['rechazadas'] = $this->rechazadas;
		$mensaje_resultado['aceptadas'] = $this->aceptadas;
		$mensaje_resultado['pendientes'] = $this->pendientes;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}

	function requisito_cumplido($requisito, $inscripcion, $hoy, $fecha_tope)
	{
		if ($requisito['requisito_tipo'] == requisito::tipo_documentales) {
			$requisito_cumplido = toba::consulta_php('co_requisitos')->requisito_presentado($inscripcion['persona'], $requisito['requisito'], $hoy, $inscripcion['propuesta_aspira'], $fecha_tope);
		} else { // Requisito de tipo proceso.
			$regla = guarani::regla($requisito['regla']);
			$regla->set_parametros($requisito['parametros']);

			$parametros_contexto['fecha'] = guarani_fecha::get_hoy();
			$parametros_contexto['persona'] = $inscripcion['persona'];
			$parametros_contexto['propuesta'] = $inscripcion['propuesta'];
			$parametros_contexto['anio_academico'] = $inscripcion['anio_academico'];
			$parametros_contexto['plan_version'] = $inscripcion['plan_version'];
			$regla->set_parametros_contexto($parametros_contexto);

			$requisito_cumplido = $regla->validar();
		}
		return $requisito_cumplido;
	}

	function rechazar_inscripcion($inscripcion, $req_no_cumplidos, $invalidar_ha = false)
	{
		try {
			toba::db()->abrir_transaccion();

			// Se actualiza la situacion_asp como inscripción a propuesta rechazada por no cumplir requisitos.
			// Se setea la fecha rechazo y la situación anterior del aspirante con la actual, previa al rechazo.
			guarani::act('act_inscripciones')->actualizar_situacion_propuesta_aspira($inscripcion['propuesta_aspira'], propuesta::situacion_asp_insc_prop_rechazada_x_req, 'now()', null, true);
			guarani::act('act_alumnos')->actualizar_calidad_alumno($inscripcion['alumno'], alumno::calidad_pasivo);
			guarani::act('act_alumnos')->insertar_cambio_calidad($inscripcion['alumno'], alumno::calidad_pasivo, alumno::motivo_calidad_pasivo);
			foreach ($req_no_cumplidos as $requisito) {
				guarani::act('act_inscripciones')->insertar_propuestas_aspira_rechazo($inscripcion['propuesta_aspira'], $requisito);
			}

			if ($invalidar_ha && alumno::tiene_historia_academica($inscripcion['alumno'])) {
				alumno::invalidar_historia_academica($inscripcion['alumno'], $this->usuario_actual);
			}

			/**
                         * Desde aquí se encola en Redis para notificar a SQ vía Rest-Hooks
			 * @see https://redmine.siu.edu.ar/redmine/projects/guarani3/wiki/Casos_de_UsoCierre_Periodo_Inscripcion Caso de uso.
			 */
			$propuesta_aspira = guarani::co('co_inscripciones')->get_id_ultima_propuesta_aspira($inscripcion['persona'], $inscripcion['propuesta']);
			cobro::post_suscripciones_novedades_rest_hooks(cobro::TIPO_POSGRADO, $propuesta_aspira, cobro::TIPO_NOVEDAD_INACTIVA, inscripcion::operacion_rechazo);
			
			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			// Si hubo algún error -> continua pendiente
			echo "Error: {$e->get_mensaje()}\n";
			$this->log->set($inscripcion, array(self::reporte_pendientes));
			$this->pendientes++;
			$this->rechazadas--;
		}
	}

	function aceptar_inscripcion($inscripcion)
	{
		try {
			toba::db()->abrir_transaccion();

			guarani::act('act_inscripciones')->actualizar_situacion_propuesta_aspira($inscripcion['propuesta_aspira'], propuesta::situacion_asp_insc_prop_aceptada);

			// Desde aquí se encola en Redis para notificar a SQ vía Rest-Hooks
			/**
			 * @see https://repositorio.siu.edu.ar/trac/guarani/wiki/Casos_de_Uso/Cierre_Periodo_Inscripcion Caso de uso.
			 */
			$propuesta_aspira = guarani::co('co_inscripciones')->get_id_ultima_propuesta_aspira($inscripcion['persona'], $inscripcion['propuesta']);

                        // Decide si encola en Redis para notificar a SQ las inscripciones a Propuesta vía Rest-Hooks
                        cobro::notificar_inscripcion_propuesta_SQ($propuesta_aspira);
			
			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			// Si hubo algún error -> continua pendiente
			echo "Error: {$e->get_mensaje()}\n";
			$this->log->set($inscripcion, array(self::reporte_pendientes));
			$this->pendientes++;
			$this->aceptadas--;
		}
	}

}