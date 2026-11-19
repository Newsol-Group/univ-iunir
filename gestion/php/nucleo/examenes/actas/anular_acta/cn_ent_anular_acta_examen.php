<?php
class cn_ent_anular_acta_examen extends cn_ent_acta_examen_basica
{
	function get_listado($filtro)
	{
		$estado_abierta = toba::db()->quote(acta::estado_abierta);
		$filtro_estado_abierta = "sga_actas.estado = $estado_abierta";
		$filtro = $filtro ? "$filtro AND $filtro_estado_abierta" : $filtro;
		return toba::consulta_php('co_actas')->get_listado_actas_examen_con_sin_mesas($filtro);
	}

	function get_alumnos_acta_examen($id_acta = null, $estado = null)
	{
		$params_sistema = array('exa_orden_detalle_actas' => guarani::parametros()->get_valor('exa_orden_detalle_actas'));
		return toba::consulta_php('co_actas')->get_alumnos_acta_examen($id_acta, $estado, $params_sistema);
	}

	function anular_acta($id_acta, $evaluacion, $observaciones)
	{
		$alumnos = $this->get_detalle_acta_abierta($id_acta);
		try {
			// Se abre la transacción
			toba::db()->abrir_transaccion();
			$this->actualizar_eval_detalle_examen($id_acta, $evaluacion);
			foreach ($alumnos as $alumno) {
				// Se copia el alumno al detalle del acta de examen
				guarani::act('act_actas')->insertar_acta_detalle_examen($alumno);
			}
			// Se marca el acta de examen como anulada
			guarani::act('act_actas')->anular_acta($id_acta, $observaciones);
			// Se cierra la transacción
			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}

	function actualizar_eval_detalle_examen($id_acta, $evaluacion)
	{
		$datos_acta = $this->get_datos_basicos_acta_examen($id_acta);
		if ($datos_acta['tipo_acta'] == acta::tipo_rectificativa) {
			// Se eliminan todos los alumnos que estan en el acta rectificativa y estan en en un acta cerrada de la mesa
			guarani::act('act_actas')->eliminar_eval_detalles_examen_en_acta_cerrada($evaluacion, $id_acta);
		}
		if ($datos_acta['mesa_en_turno_examen'] == comunes::no) {
			// Se eliminan todos los alumnos de 'sga_eval_detalle_examenes' para el acta fuera de calendario (no existe inscripción a examen).
			guarani::act('act_actas')->eliminar_eval_detalles_examen_acta($id_acta);
		}
		// Si el acta es original o quedó no se pudo eliminar (se agrego al acta rectificativa 
		// como alumno de la mesa) del acta rectificativa se lo desasocia
		guarani::act('act_actas')->desasociar_eval_detalles_examen($evaluacion, $id_acta);
	}

}
?>