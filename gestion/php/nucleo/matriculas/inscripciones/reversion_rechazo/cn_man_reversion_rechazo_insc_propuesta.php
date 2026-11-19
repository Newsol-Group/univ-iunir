<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage	Inscripciones
 * 
 */
class cn_man_reversion_rechazo_insc_propuesta extends cn_manejador_entidad
{
	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_propuestas_aspira');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro)
	{
		return guarani::co('co_inscripciones')->get_ultimas_inscripciones_rechazadas($filtro);
	}

	function revertir_rechazo_insc_propuesta($propuesta_aspira, $propuesta)
	{
		try {
			toba::db()->abrir_transaccion();	
			// Se setea la situación del aspirante previa al rechazo de la inscripción a propuesta en sga_propuestas_aspira
			// situacion_asp se manda en null para que tome la situación anterior.
			// situacion_asp_anterior, fecha_rechazo y observaciones_rechazo se mandan en falso y nulo para que se 
			// limpien dichos campos al Revertir el Rechazo.
			$observaciones_rechazo = ' ';
			guarani::act('act_inscripciones')->actualizar_situacion_propuesta_aspira($propuesta_aspira, null, null, null, false, $observaciones_rechazo);	
			// Se eliminan los registros relativos al Rechazo de Requisitos en la Inscripción a Propuesta del aspirante (sga_propuestas_apira_rechazo).
			guarani::act('act_inscripciones')->eliminar_registros_rechazo_propuesta_aspira($propuesta_aspira);
                        
                        /**
                         * Desde aquí se encola en Redis para notificar a SQ vía Rest-Hooks
                         * @see https://redmine.siu.edu.ar/redmine/projects/guarani3/wiki/Casos_de_UsoRevertir_Rechazo_Insc_Propuesta Caso de uso.
                         */
                        cobro::post_suscripciones_novedades_rest_hooks(cobro::TIPO_POSGRADO, $propuesta_aspira, cobro::TIPO_NOVEDAD_ACTIVA, inscripcion::operacion_revertir_rechazo);

			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}

}