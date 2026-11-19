<?php
/**
 *
 * @package Exámenes
 * @subpackage Inscripciones
 */
class cn_man_rechazo_insc_examenes_pendientes extends cn_manejador_entidad
{
	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_insc_examen');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro)
	{
		$where = "sga_insc_examen.estado = '" . inscripcion_examen::estado_pendiente . "'";
		$where .= $filtro ? " AND $filtro" : '';
		return guarani::co('co_inscripciones_examenes')->get_listado_inscripciones($where);
	}

	function rechazar_inscripciones($inscripciones, $motivo_rechazo)
	{
		$contador_ok = 0;
		$contador_error = 0;
		$lista_error = '';
		$nro_transaccion_log = toba::consulta_php('co_inscripciones_examenes')->get_nuevo_nro_transaccion();

		foreach ($inscripciones as $inscripcion) {
			try {
				toba::db()->abrir_transaccion();
				$filtro = guarani_sql::armar_condicion_in($inscripcion['inscripcion'], 'sga_insc_examen', 'inscripcion');
				$datos_inscripcion = guarani::co('co_inscripciones_examenes')->get_listado_inscripciones($filtro);
				$datos_inscripcion = rs_convertir_asociativo_matriz($datos_inscripcion, array('inscripcion'));
				guarani::act('act_inscripcion_examenes')->insertar_insc_examen_log($inscripcion['inscripcion'], inscripcion_examen::operacion_rechazo, $nro_transaccion_log, $motivo_rechazo);
				guarani::act('act_inscripcion_examenes')->eliminar_insc_examen($inscripcion['inscripcion']);
				toba::db()->cerrar_transaccion();
				if(guarani::parametros()->get_valor('exa_notificar_estado_inscripcion', null, false) == comunes::si) {
					$parametros['aceptada'] = false;
					$parametros['alumno'] = $datos_inscripcion[$inscripcion['inscripcion']]['alumno'];
					$parametros['fecha'] = $datos_inscripcion[$inscripcion['inscripcion']]['fecha_examen_visual'];
					$datos_elemento = toba::consulta_php('co_elementos')->get_datos_basicos_actividad($datos_inscripcion[$inscripcion['inscripcion']]['elemento']);
					$parametros['actividad'] = $datos_elemento['nombre'];
					guarani::notificador()->enviar(notificador::inscripciones_pendientes_a_examenes, $parametros);
				}
				$contador_ok++;
			} catch (guarani_error $e) {
				toba::db()->abortar_transaccion();
				$contador_error++;
				$lista_error .= '<BR>' . $inscripcion['apellido_y_nombre'];
				toba::logger()->error($e->getMessage());
			}
		}

		$msg = guarani_mensajes::get('800_ok_rechazo_masivo_insc', array($contador_ok, $contador_error, $lista_error));
		if ($contador_error > 0) {
			$nivel = comunes::error_critico;
		} else {
			$nivel = comunes::error_informacion;
		}
		toba::notificacion()->agregar($msg, $nivel);
	}

}
?>