<?php
/**
 *
 * @package Calendario
 * @subpackage Planificacion
 */
class cn_man_clases extends cn_manejador_entidad
{
	function etiquetas($dep=null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_clases');
		} else {
			parent::etiquetas($dep);
		}
	}

	// Recupera las Comisiones con Períodos Lectivos en curso, según el filtro dado.
	function get_listado_preseleccion($filtro)
	{
		return toba::consulta_php('co_comisiones')->get_listado($filtro);			
	}
	
	// Recupera las Subcomisiones 
	function get_listado_subcomisiones($comision)
	{
		return toba::consulta_php('co_comisiones')->get_subcomisiones_comision_con_asignacion_horaria($comision);
	}
	
	// Recupera todas las Clases de la Comisión o Subcomisión dada con cantidad de Presentes y Ausentes.
	function get_listado_clases($comision, $subcomision=null)
	{
		if (!is_null($subcomision)) {
			return toba::consulta_php('co_clases')->get_clases_subcomision_con_aulas($subcomision);
		} else {
			return toba::consulta_php('co_clases')->get_clases_comision_con_aulas($comision);
		}
	}
	
	function agregar_clase($comision, $subcomision=null, $datos)
	{
		try {
			$this->desactivar_transacciones_anidadas();
			toba::db()->abrir_transaccion();
			$this->validar_datos_clase($comision, $subcomision, $datos);
			/**
			 * Se inserta la clase con los datos. Debe verificarse si se seleccionó utilizar una banda horaria existente,
			 * en cuyo caso sólo se da de alta la Clase, o si se debe crear una nueva banda horaria y asignación
			 * correspondientes para la Clase.
			 */
			if (!$datos['utilizar_bh_definida']) {
				// Se obtiene el día de semana correspondiente a la fecha de la clase.
				$dia_num = guarani_fecha::get_parte($datos['fecha'], 'DOW');
				$offset = ($dia_num + 6) % 7;
				$dias_semana = toba::consulta_php('co_comunes')->get_dias_semana();
				$dia_semana = $dias_semana[$offset]['valor'];
				$asignacion = guarani::act('act_clases')->insertar_asignacion_para_clase($datos['fecha'], $datos['fecha'], $dia_semana, $datos['hora_inicio'], $datos['hora_finalizacion'], comision::asignacion_tipo_comision, banda_horaria::periodicidad_semanal, $datos['cantidad_horas_dictadas']);
				$bh = guarani::act('act_clases')->insertar_bh_comision_para_clase($comision, $asignacion, $datos['tipo_clase']);
				if (!is_null($subcomision)) {
					guarani::act('act_clases')->insertar_bh_subcomision_para_clase($subcomision, $bh);
				}
				/**
				 * La clase se inserta por trigger al insertar la asignación para la misma, con los datos de la fecha y 
				 * cantidad de horas que se cargaron por pantalla. Resta recuperar el id de clase insertada para 
				 * modificarla con los datos adicionales cargados por el usuario.
				 */
				$filtro = "sga_clases.banda_horaria = $bh AND sga_asignaciones.asignacion = $asignacion";
				$clase = toba::consulta_php('co_clases')->get_clases_comision($comision, $filtro);
				$id_clase = $clase[0]['clase'];
				guarani::act('act_clases')->modificar_clase($id_clase, $datos['valido'], $datos['asistencia'], $datos['motivo_invalidacion'], $datos['tema_planificado'], $datos['id_tramo'], $datos['modalidad']);
			} else {
				guarani::act('act_clases')->insertar_clase($datos['banda_horaria'], $datos['fecha'], $datos['valido'], $datos['asistencia'], $datos['motivo_invalidacion'], $datos['tema_planificado'], $datos['cantidad_horas_dictadas'], $datos['id_tramo'], $datos['modalidad']);
			}
			toba::db()->cerrar_transaccion();
			$this->activar_transacciones_anidadas();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}

	function modificar_clase($id_clase, $datos)
	{
		try {
			toba::db()->abrir_transaccion();
			// Se actualiza la clase según el id y los datos recibidos.
			guarani::act('act_clases')->modificar_clase($id_clase, $datos['valido'], $datos['asistencia'], $datos['motivo_invalidacion'], null, null, $datos['modalidad']);
			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}
	
	function eliminar_clase($id_clase)
	{
		try {
			toba::db()->abrir_transaccion();
			$datos_clase = toba::consulta_php('co_clases')->get_datos_clase_asignacion($id_clase);
			$this->validar_eliminacion_clase($id_clase, $datos_clase);
			// Se elimina la clase según el id recibido.
			guarani::act('act_clases')->eliminar_clase($id_clase);
			// Si no existe ninguna otra clase asociada a la banda horaria de la clase se elimina también la misma.
			if (toba::consulta_php('co_clases')->get_cant_clases_banda_horaria($datos_clase['banda_horaria']) == 0) {
				guarani::act('act_clases')->eliminar_banda_horaria($datos_clase['banda_horaria']);
				if (toba::consulta_php('co_bandas_horarias')->get_cant_bandas_horarias_asignacion($datos_clase['asignacion']) == 0) {
					guarani::act('act_clases')->eliminar_asignacion($datos_clase['asignacion']);
				}
			}
			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}
	
	/**
	 * Valida los datos de la clase que se está dando de Alta (seleccionando o creando una Banda Horaria para la misma).
	 *
	 */
	function validar_datos_clase($comision, $subcomision, $datos)
	{
		// Hora fin de la asignación de la clase mayor o igual a la de inicio.
		if (guarani_hora::es_mayor_igual($datos['hora_inicio'], $datos['hora_finalizacion'])) {
			$msj_valor_minimo = guarani_mensajes::get('800_valor_menor', array('Hora Fin', 'Hora Inicio'));
			throw new guarani_error($msj_valor_minimo);
		}
		// Fecha definida para la Clase dentro del Período Lectivo de la Comisión.
		$datos_periodo = toba::consulta_php('co_comisiones')->get_datos_periodo_comision($comision);
		if (! guarani_fecha::esta_entre_fechas($datos['fecha'], $datos_periodo['fecha_inicio_dictado'], $datos_periodo['fecha_fin_dictado'])) {
			$msj_fecha = guarani_mensajes::get('800CAL_fecha_fuera_periodo', array($datos_periodo['fecha_inicio_dictado_visual'], $datos_periodo['fecha_fin_dictado_visual']));
			throw new guarani_error($msj_fecha);
		}
        $es_dia_no_laboral = toba::consulta_php('co_dias_no_laborables')->es_dia_no_laborable_ubicacion($datos['fecha'], $datos_periodo['ubicacion']);
        if ($es_dia_no_laboral) {
            $msj_dia_no_laboral = guarani_mensajes::get('800_dia_no_laboral');
            throw new guarani_error($msj_dia_no_laboral);			
		}
		/**
		 * Si se seleccionó una BH existente, verificar que la Fecha de la clase corresponda al mismo día de semana que el de la BH.
		 * Si no se seleccionó una BH existente, no se hace ninguna validacion.
		 */
		if ($datos['utilizar_bh_definida']) {
			// La fecha ingresada corresponda al mismo día que la definida para la banda horaria seleccionada 800CAL_fecha_dia_bh
			// Se obtiene el día de semana correspondiente a la fecha de la clase.
			$dia_num = guarani_fecha::get_parte($datos['fecha'], 'DOW');
			$offset = ($dia_num + 6) % 7;
			$dias_semana = toba::consulta_php('co_comunes')->get_dias_semana();
			$dia_semana = $dias_semana[$offset]['valor'];
			$asignaciones_bh = toba::consulta_php('co_bandas_horarias')->get_datos_banda_horaria($datos['banda_horaria']);
			$asignaciones_dias = guarani_arreglos::aplanar_matriz_sin_nulos($asignaciones_bh, 'dia_semana');
			if (! in_array($dia_semana, $asignaciones_dias)) {
				$msj_dias = guarani_mensajes::get('800CAL_fecha_dia_bh');
				throw new guarani_error($msj_dias);
			}
		}	
		
		// Si se ha seleccionado una Subcomisión el Tipo de la Clase no puede ser diferente a la de la Subcomisión.
		if (!is_null($subcomision)) {
			$datos_subcomision = current(toba::consulta_php('co_comisiones')->get_datos_subcomision($subcomision));
			if ($datos_subcomision['tipo_clase'] <> $datos['tipo_clase']) {
				$msj_tipo_clase = guarani_mensajes::get('800CAL_tipo_clase_subco');
				throw new guarani_error($msj_tipo_clase);
			}
		}
	}	
	
	/**
	 * Verifica que se cumplan las condiciones para poder eliminar la Clase seleccionada.
	 *
	 */
	function validar_eliminacion_clase($id_clase, $datos_clase)
	{
		// La clase no debe tener asistencias / inasistencias cargadas para los alumnos y/o docentes 
		if (toba::consulta_php('co_clases')->clase_con_asistencia_alumnos_cargada($id_clase) ||
			toba::consulta_php('co_clases')->clase_con_inasistencia_docentes_cargada($id_clase)) {
			$msj_asist = guarani_mensajes::get('800_dato_no_eliminable', array('la Clase', 'tiene asistencias cargadas'));
			throw new guarani_error($msj_asist);
		}
		// La clase no debe tener temas planificados cargados 
		if (isset($datos_clase['tema_planificado']) && trim($datos_clase['tema_planificado']) != '') {
			$msj_temas = guarani_mensajes::get('800_dato_no_eliminable', array('la Clase', 'tiene temas planificados cargados'));
			throw new guarani_error($msj_temas);
		}
	}
}
?>
