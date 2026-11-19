<?php
/**
 *
 * 
 * @package 	Exámenes
 * @subpackage 	Inscripciones
 * 
 */
class cn_proc_insc_examenes_pendientes extends cn_procesos_bk 
{	
	protected $s__inscripciones_a_rechazar;

	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_proc_insc_examenes_pendientes::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}	
	
	function get_inscripciones_a_rechazar()
	{
		if (!isset($this->s__inscripciones_a_rechazar)) {
			$inscripciones = current($this->get_resultado_proceso(pro_proc_insc_examenes_pendientes::reporte_a_rechazar));
			$this->s__inscripciones_a_rechazar = rs_convertir_asociativo_matriz($inscripciones, array('inscripcion'));
		}
		return $this->s__inscripciones_a_rechazar;
	}
	
	function etiquetas($dep=null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_insc_examen');
		} else {
			parent::etiquetas($dep);
		}
	}

	/**
	 * Obtiene un listado de inscripciones a examen en estado pendiente según filtros especificados.
	 */
	function get_listado($filtro='')
	{
		if ($filtro) {
			$filtro .= ' AND ';
		}
		$filtro .= " sga_insc_examen.estado = '".inscripcion_examen::estado_pendiente."'";
		return guarani::co('co_inscripciones_examenes')->get_listado_inscripciones($filtro);
	}
	
	function rechazar_inscripciones_examen($inscripciones)
	{
		$requisitos_no_cumplidos = toba::memoria()->get_dato('requisitos_no_cumplidos');
		$nro_transaccion_log = toba::consulta_php('co_inscripciones_cursadas')->get_nuevo_nro_transaccion();
		$notificar_estado_inscripcion = guarani::parametros()->get_valor('exa_notificar_estado_inscripcion', null, false);
		foreach ($inscripciones as $inscripcion) {
			try {
				toba::db()->abrir_transaccion();
				guarani::act('act_inscripcion_examenes')->insertar_insc_examen_log($inscripcion['inscripcion'], inscripcion_examen::operacion_rechazo, $nro_transaccion_log);

				// Se inserta el log de requisitos no cumplidos para la inscripción a examen.
				$requisitos['observaciones'] = explode('||', $requisitos_no_cumplidos[$inscripcion['inscripcion']]['observaciones']);
				$requisitos['requisitos'] = explode('||', $requisitos_no_cumplidos[$inscripcion['inscripcion']]['requisitos']);
				$requisitos = array_combine($requisitos['requisitos'],$requisitos['observaciones']);
				guarani::act('act_inscripcion_examenes')->insertar_insc_examen_log_requisitos($inscripcion['inscripcion'], $requisitos);

				guarani::act('act_inscripcion_examenes')->eliminar_insc_examen($inscripcion['inscripcion']);
				unset($this->s__inscripciones_a_rechazar[$inscripcion['inscripcion']]);

				if ($notificar_estado_inscripcion == comunes::si) {
					$parametros['aceptada'] = false;
					$parametros['alumno'] = $inscripcion['alumno'];
					$parametros['fecha'] = $inscripcion['fecha_examen_visual'];
					$parametros['actividad'] = $inscripcion['elemento_nombre'];
					guarani::notificador()->enviar(notificador::inscripciones_pendientes_a_examenes, $parametros);
				}
				toba::db()->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db()->abortar_transaccion();
				throw guarani::error(guarani_mensajes::get('800_rechazar_inscripciones_error'));
			}
		}
		$mje = guarani_mensajes::get('800_rechazar_inscripciones_ok');
		toba::notificacion()->info($mje);
	}
}
?>