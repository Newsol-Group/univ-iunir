<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Inscripciones
 * 
 */
class cn_proc_insc_act_pendientes extends cn_procesos_bk
{
	protected $s__inscripciones_a_rechazar;

	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_proc_insc_act_pendientes::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function get_inscripciones_a_rechazar()
	{
		if (!isset($this->s__inscripciones_a_rechazar)) {
			$inscripciones = current($this->get_resultado_proceso(pro_proc_insc_act_pendientes::reporte_a_rechazar));
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
		// Inscripcion en estado pendiente
		$filtro .= " sga_insc_cursada.estado = '" . inscripcion_cursada::estado_pendiente . "'";
		// Inscripcion comun (tipo = I) o inscripcion por prioridad que ya fue procesada y aceptada.
		$filtro .= " AND (sga_insc_cursada.tipo = '" . inscripcion_cursada::tipo_inscripcion . "' OR (sga_insc_cursada.tipo = '" . inscripcion_cursada::tipo_preinscripcion . "' AND sga_insc_cursada.estado_preinscripcion = '" . inscripcion_cursada::preinsc_aceptada . "'))";
		$filtro .= " AND sga_comisiones.estado = '" . comunes::estado_activo . "'";
		$order = ' apellido_y_nombre, ubicacion, actividad, comision';
        return guarani::co('co_inscripciones_cursadas')->get_listado($filtro, false, $order);
	}

	function rechazar_inscripciones_cursada($inscripciones)
	{
		$requisitos_no_cumplidos = toba::memoria()->get_dato('requisitos_no_cumplidos');
		$notificar_estado_inscripcion = guarani::parametros()->get_valor(inscripcion_cursada::cur_notificar_estado_inscripcion, null, false);
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
					if ($notificar_estado_inscripcion == comunes::si) {
						$parametros['estado'] = comunes::resultado_inscripcion_rechazada;
						$parametros['nombre_actividad'] = $inscripcion['actividad'];
						$parametros['nombre_comision'] = $inscripcion['comision'];
						$parametros['persona'] = $inscripcion['persona'];
						$parametros['email'] = toba::consulta_php('co_personas')->get_emails_personas(array($inscripcion['persona']));
						$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_alumno($inscripcion['alumno']);
						$parametros['nombre_alumno'] = $datos_alumno[0]['nombres'];
						guarani::notificador()->enviar(notificador::inscribir_a_actividad, $parametros);
					}
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