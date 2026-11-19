<?php
/**
 *
 * 
 * @package		Matrícula
 * @subpackage	Inscripciones
 * 
 */

class cn_man_rechazar_inscripciones_propuesta extends cn_manejador_entidad
{
	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_propuestas_aspira');
		} else {
			parent::etiquetas($dep);
		}
	}
	
	function cargar_atributos_parametros($propuestas)
	{
		$operacion = guarani::get_id_operacion();
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);
		
		// Se eliminan propuestas repetidas (por estar en más de un plan)
		$propuestas = array_unique($propuestas);
		
		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
	}

	/**
	 * Retorna los datos de inscripciones a propuesta en estado ACEPTADA o PENDIENTE.
	 * 
	 * @param string $filtro
	 * @return array
	 */
	function get_listado($filtro)
	{
		if (!empty($filtro)) {
			$filtro .= " AND ";
		}
		$filtro .= "sga_situacion_aspirante.resultado_asp IN ('" . propuesta::inscripcion_aceptada . "', '" . propuesta::inscripcion_pendiente . "')";
		return guarani::co('co_inscripciones')->get_alumnos_inscriptos($filtro);
	}

	function rechazar_inscripciones($propuesta, $inscripciones, $situacion_asp, $observaciones_rechazo = '')
	{
		$param_rechazo_insc = guarani::parametros()->get_valor('mat_rechazar_inscripcion', array($propuesta));
		switch ($param_rechazo_insc) {
			case inscripcion::PARAM_RECHAZO_ANULA_HA:
				$rechazar_con_ha = true;
				$invalidar = true;
				break;
			case inscripcion::PARAM_RECHAZO_NO_ANULA_HA:
				$rechazar_con_ha = true;
				$invalidar = false;
				break;
			case inscripcion::PARAM_RECHAZO_NO_RECHAZA_CON_HA:
				$rechazar_con_ha = false;
				$invalidar = false;
		}
		
		toba::db()->abrir_transaccion();
		foreach ($inscripciones as $inscripcion) {
			try {
				$tiene_ha = alumno::tiene_historia_academica($inscripcion['alumno']);
				// Si el alumno tiene HA y ésta se invalida automáticamente.
				if ($tiene_ha && $invalidar) {
					alumno::invalidar_historia_academica($inscripcion['alumno']);
				}
				// Si el alumno tiene HA e igual se definió rechazar, o si se rechaza porque no tiene HA.
				if (($tiene_ha && $rechazar_con_ha) || !$tiene_ha) {
					// Se pasa el alumno a calidad pasivo.
					guarani::act('act_alumnos')->actualizar_calidad_alumno($inscripcion['alumno'], alumno::calidad_pasivo);
					guarani::act('act_alumnos')->insertar_cambio_calidad($inscripcion['alumno'], alumno::calidad_pasivo, alumno::motivo_calidad_pasivo);
					// Se rechaza la inscripción a propuesta. Se setea la fecha y observaciones rechazo y 
					// la situación anterior del aspirante con la actual, previa al rechazo.
					guarani::act('act_inscripciones')->actualizar_situacion_propuesta_aspira($inscripcion['propuesta_aspira'], $situacion_asp, 'now()', null, true, $observaciones_rechazo);

					/**
                                         * Desde aquí se encola en Redis para notificar a SQ vía Rest-Hooks
					 * @see https://redmine.siu.edu.ar/redmine/projects/guarani3/wiki/Casos_de_UsoRechazar_Inscripcion_Propuesta Caso de uso.
					 */
					cobro::post_suscripciones_novedades_rest_hooks(cobro::TIPO_POSGRADO, $inscripcion['propuesta_aspira'], cobro::TIPO_NOVEDAD_INACTIVA, inscripcion::operacion_rechazo);
					
				}
			} catch (toba_error $e) {
				toba::db()->abortar_transaccion();
				throw $e;
			}
		}
		toba::db()->cerrar_transaccion();
		return $rechazar_con_ha;
	}

}