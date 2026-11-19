<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Inscripciones
 * 
 */
class cn_pro_reprocesar_inscripciones_actividades extends cn_procesos_bk
{
	protected $s__inscripciones_a_rechazar;
	
	function cargar_atributos_parametros($propuestas)
	{
		$operacion = guarani::get_id_operacion();
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);
	}

	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_reprocesar_inscripciones_actividades::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function get_inscripciones_a_rechazar()
	{
		if (!isset($this->s__inscripciones_a_rechazar)) {
			$inscripciones = current($this->get_resultado_proceso(pro_reprocesar_inscripciones_actividades::reporte_a_rechazar));
			$this->s__inscripciones_a_rechazar = rs_convertir_asociativo_matriz($inscripciones, array('inscripcion'));
		}
		return $this->s__inscripciones_a_rechazar;
	}

	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_insc_cursada');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro = '')
	{
		if ($filtro) {
			$filtro .= ' AND ';
		}
		// Inscripcion aceptada o pendiente
		$filtro .= " sga_insc_cursada.estado IN ('" . inscripcion_cursada::estado_aceptada . "', '" . inscripcion_cursada::estado_pendiente . "')";
		// Inscripcion comun (tipo = I) o inscripcion por prioridad que ya fue procesada y aceptada.
		$filtro .= " AND (sga_insc_cursada.tipo = '" . inscripcion_cursada::tipo_inscripcion . "' OR (sga_insc_cursada.tipo = '" . inscripcion_cursada::tipo_preinscripcion . "' AND sga_insc_cursada.estado_preinscripcion = '" . inscripcion_cursada::preinsc_aceptada . "'))";
		// Comisiones activas
		$filtro .= " AND sga_comisiones.estado = '" . comunes::estado_activo . "'";
		// El alumno no exista en acta de regulares o promociones (abierta o cerrada) en la comision
		$filtro .= " AND NOT EXISTS (-- Alumno en acta de regulares y/o promoción abierta.
									SELECT	1
									FROM	sga_evaluaciones
											JOIN sga_evaluaciones_tipos ON sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo
											JOIN sga_eval_detalle_cursadas ON sga_evaluaciones.evaluacion = sga_eval_detalle_cursadas.evaluacion
									WHERE	sga_evaluaciones.entidad = sga_comisiones.entidad AND
											sga_evaluaciones_tipos.automatica = '" . comunes::si . "' AND
											sga_eval_detalle_cursadas.alumno = sga_insc_cursada.alumno AND
											(sga_eval_detalle_cursadas.id_acta_cursada IS NOT NULL OR sga_eval_detalle_cursadas.id_acta_promocion IS NOT NULL)
									UNION
									-- Alumno en acta de regulares y/o promoción cerrada.
									SELECT	1
									FROM	sga_actas
											JOIN sga_actas_detalle ON sga_actas.id_acta = sga_actas_detalle.id_acta
									WHERE	sga_actas.comision = sga_comisiones.comision AND
											sga_actas_detalle.alumno = sga_insc_cursada.alumno AND
											sga_actas.estado = '" . acta::estado_cerrada . "')
		";
		return guarani::co('co_inscripciones_cursadas')->get_listado($filtro);
	}

	function rechazar_inscripciones_cursada($inscripciones)
	{
		$requisitos_no_cumplidos = toba::memoria()->get_dato('requisitos_no_cumplidos');
		foreach ($inscripciones as $inscripcion) {
			try {
				toba::db()->abrir_transaccion();

				// Se inserta el log de requisitos no cumplidos para la inscripción a cursada.
				$requisitos['observaciones'] = explode('||', $requisitos_no_cumplidos[$inscripcion['inscripcion']]['observaciones']);
				$requisitos['requisitos'] = explode('||', $requisitos_no_cumplidos[$inscripcion['inscripcion']]['requisitos']);
				$requisitos = array_combine($requisitos['requisitos'],$requisitos['observaciones']);
				guarani::act('act_inscripcion_cursadas')->insertar_insc_cursada_log_requisitos($inscripcion['inscripcion'], $requisitos);
				
				// Se eliminan la inscripción a cursada junto a los registros de las tablas relacionadas.
				$result = guarani::act('act_inscripcion_cursadas')->eliminar_insc_cursada_func($inscripcion['inscripcion']);
				if ($result['res'] < 1) {
					$mje = guarani_mensajes::get($result['msg_indice'], array($result['pars']));
					throw guarani::error($mje);
				} else {
					toba::db()->cerrar_transaccion();
					unset($this->s__inscripciones_a_rechazar[$inscripcion['inscripcion']]);
				}
			} catch (toba_error $e) {
				toba::db()->abortar_transaccion();
				throw guarani::error($e->getMessage());
			}
		}
		$mje = guarani_mensajes::get('800_rechazar_inscripciones_ok');
		toba::notificacion()->info($mje);
	}
}