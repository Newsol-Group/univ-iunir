<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Procesos
 * 
 */
class cn_man_pasar_insc_a_historico extends cn_manejador_entidad
{
	function procesar_inscripciones($anio_academico, $cursadas, $examenes)
	{
		if ($cursadas) {
			$cant_periodos_lectivos = toba::consulta_php('co_periodos_lectivos')->get_cant_periodos_lectivos_no_finalizados_por_anio_academico($anio_academico);
			if ($cant_periodos_lectivos > 0) {
				$cursadas = 0;
				toba::notificacion()->error(guarani_mensajes::get('800ADM_historico_anio_con_periodos'));
			}
		}
		if ($examenes) {
			$cant_turnos_examen = toba::consulta_php('co_turnos_de_examen')->get_cant_turnos_examen_no_finalizados_por_anio_academico($anio_academico);
			if ($cant_turnos_examen > 0) {
				$examenes = 0;
				toba::notificacion()->error(guarani_mensajes::get('800ADM_historico_anio_con_turnos'));
			}
		}
		if ($cursadas || $examenes) {
			try {
				toba::db()->abrir_transaccion();
				guarani::act('act_inscripciones')->pasar_inscripciones_a_historico($anio_academico, $cursadas, $examenes);
				toba::db()->cerrar_transaccion();
				$indice_mensaje = '800ADM_historico_ok';
				if (!($cursadas && $examenes)) {
					$indice_mensaje .= $cursadas ? '_cursadas' : '_examenes';
				}
				toba::notificacion()->info(guarani_mensajes::get($indice_mensaje));
			} catch (toba_error $e) {
				toba::db()->abortar_transaccion();
				throw $e;
			}
		}
	}
}
?>