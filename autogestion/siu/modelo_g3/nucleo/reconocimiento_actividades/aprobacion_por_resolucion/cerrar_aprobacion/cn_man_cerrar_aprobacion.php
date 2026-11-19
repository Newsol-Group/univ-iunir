<?php
/**
 *
 * @package Reconocimiento de Actividades
 * @subpackage Aprobación por Resolución
 */
class cn_man_cerrar_aprobacion extends cn_manejador_entidad
{
	// Se redefine la función que obtiene las etiquetas en el cn manejador para que no se invoque al cn entidad.
	// Se recupera directamente la etiqueta de la tabla ya que no hay ninguna dependencia asociada.
	function etiquetas($dep=null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_reconocimiento');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro)
	{
		$where = "sga_reconocimiento.estado = '".aprobacion_por_resolucion::estado_abierto."'";
		if (isset($filtro) && trim($filtro) != '') {
			$where .= ' AND '.$filtro; 
		}
		return toba::consulta_php('co_aprobacion_por_resolucion')->get_listado($where);
	}

	function cerrar_tramite_aprobacion($nro_tramite)
	{
		$this->validar_datos($nro_tramite);
		guarani::act('act_aprobacion_por_resolucion')->cerrar_tramite_aprobacion($nro_tramite);
	}
	
	function validar_datos($nro_tramite)
	{
		$datos_tramite = toba::consulta_php('co_aprobacion_por_resolucion')->get_datos_tramite_aprobacion_por_resolucion($nro_tramite);
		if ($datos_tramite['tipo_reconocimiento'] == aprobacion_por_resolucion::activ_modulos) {
			$this->validar_actividades_modulos($nro_tramite, $datos_tramite['plan_version'], $datos_tramite['alumno_nombre_completo']);
		} else {
			$this->validar_creditos_globales($datos_tramite['total_creditos'], $datos_tramite['alumno_nombre_completo']);
		}
	}

	/**
	 * Valida el conjunto de actividades/módulos:
	 *  - Si se reconoce un módulo en forma total no pueden existir reconocimientos parciales y viceversa.
	 *  - Si se reconoce una actividad la misma no puede haber sido aprobada por el alumno.
	 *  - La cantidad de créditos debe estar entre el mínimo y el máximo definido para el elemento en el plan.
	 *
	 * @param integer $nro_tramite
	 * @param integer $plan_version
	 * @param string $nombre_alumno
	 */
	function validar_actividades_modulos($nro_tramite, $plan_version, $nombre_alumno)
	{
		$actividades = toba::consulta_php('co_aprobacion_por_resolucion')->get_actividades_reconocimiento_tramite_aprobacion($nro_tramite);
		foreach ($actividades as $actividad) {
			if ($actividad['entidad_tipo'] == g3entidad::tipo_modulo) {
				$reconocimiento = toba::consulta_php('co_aprobacion_por_resolucion')->get_reconocimiento_alumno_modulo($actividad['alumno'], $actividad['elemento']);
				// Cuando el módulo tiene algún reconocimiento parcial en otro trámite ya no puede reconocerse en forma total y viceversa.
				if (!empty($reconocimiento) && ($actividad['reconocimiento_total'] == comunes::si || $reconocimiento['reconocimiento_total'] == comunes::si)) {
					$indice_mje = ($actividad['reconocimiento_total'] == comunes::si) ? '800EQU_reconocimiento_total_invalido' : '800EQU_reconocimiento_parcial_invalido';
					$mje = guarani_mensajes::get($indice_mje, array($actividad['elemento_nombre']));
					throw guarani::error($mje);
				}
			} elseif (toba::consulta_php('co_alumnos')->actividad_aprobada($actividad['alumno'], $actividad['elemento'])) {
				$mje = guarani_mensajes::get('800EQU_actividad_aprobada', array($actividad['elemento_nombre'], $nombre_alumno));
				throw guarani::error($mje);
			}
			$creditos_min_max = toba::consulta_php('co_elementos')->get_creditos_min_max_elemento_plan_version($actividad['elemento'], $plan_version);
			if (isset($creditos_min_max['creditos_max']) && ($actividad['creditos'] > $creditos_min_max['creditos_max'])) {
				$mje = guarani_mensajes::get('800EQU_actividad_creditos_max_invalidos', array($actividad['elemento_nombre'], $creditos_min_max['creditos_max']));
				throw guarani::error($mje);
			}
			if (isset($creditos_min_max['creditos_min']) && ($actividad['creditos'] < $creditos_min_max['creditos_min'])) {
				$mje = guarani_mensajes::get('800EQU_actividad_creditos_min_invalidos', array($actividad['elemento_nombre'], $creditos_min_max['creditos_min']));
				throw guarani::error($mje);
			}
		}
	}

	/**
	 * Valida que la cantidad de créditos globales del trámite sea superior a cero.
	 *
	 * @param float $total_creditos
	 * @param string $nombre_alumno
	 */
	function validar_creditos_globales($total_creditos, $nombre_alumno)
	{
		if ($total_creditos == 0) {
			$mje = guarani_mensajes::get('800EQU_creditos_globales_invalidos', array($nombre_alumno));
			throw guarani::error($mje);
		}
	}
}
?>