<?php
/**
 *
 * 
 * @package		Cursadas
 * @subpackage	Inscripciones
 * 
 */
class cn_man_baja_inscripcion_cursada extends cn_manejador_entidad
{
	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_alumnos');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro)
	{
		$where = "sga_planes_versiones.estado IN ('" . planes::estado_vigente . "','" . planes::estado_activo . "')";
		if ($filtro) {
			$where .= "	AND $filtro";
		}
		return toba::consulta_php('co_alumnos')->get_listado($where);
	}

	function eliminar_inscripcion_cursada($datos_inscripcion)
	{
		$this->validar_puntos_de_control($datos_inscripcion);
		
		try {

			// Si la comisión es cobrable
			if ($datos_inscripcion['cobrable'] == comunes::si) {

				$comision = $datos_inscripcion['comision'];
				// Se obtienen las propuestas asocaidas a la comisión
				$propuestas = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($comision), 'propuesta');
				// Se obtienen las Responsables Académicas de esas propuestas
				$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);
				// Se determina si se usa Sanavirón-Quilmes como módulo de pago para esas RAs
				$usa_sq = guarani::parametros()->get_valor('sq_usa_sanaviron', $ras);

				// Si usa SQ
				if ($usa_sq == comunes::si) {

					$estado_consumo_externo = cobro::get_estado_consumo_externo(cobro::TIPO_CONSUMO_SUSCRIPCION, $datos_inscripcion['nro_transaccion']);

					// Si existe el consumo externo en SQ
					if ($estado_consumo_externo['status_code'] != cobro::ESTADO_GET_VACIO) {
						inscripcion_cursada::baja_inscripcion_sq($datos_inscripcion['nro_transaccion'], $datos_inscripcion['modo_devolucion']);
					}
					
				}

			}
			
			$result = guarani::act('act_inscripcion_cursadas')->eliminar_insc_cursada_func($datos_inscripcion['inscripcion']);
			return $result;
			
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}

	function validar_puntos_de_control($datos_inscripcion)
	{
		$puntos_de_control = array(
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::persona_operacion,
			guarani_punto_de_control::alumno_operacion
		);

		$parametros = array(
			'fecha' => guarani_fecha::get_hoy(),
			'persona' => $datos_inscripcion['persona'],
			'alumno' => $datos_inscripcion['alumno'],
			'anio_academico' => $datos_inscripcion['anio_academico'],
			'plan_version' => $datos_inscripcion['plan_version']
		);

		$retorno = guarani::validador_puntos_de_control($puntos_de_control, $parametros)->controlar();
		guarani::validador_puntos_de_control()->notificar();

		if ($retorno == guarani_punto_de_control::rechazado) {
			throw new guarani_error(null, null, guarani_mensajes::get('800_baja_inscripcion_fallo_ctrl'));
		}
	}

}
?>