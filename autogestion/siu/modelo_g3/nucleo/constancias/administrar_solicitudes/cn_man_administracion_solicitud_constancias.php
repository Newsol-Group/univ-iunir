<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Solicitudes
 * 
 */
class cn_man_administracion_solicitud_constancias extends cn_manejador_entidad
{
	// Se redefine la función que obtiene las etiquetas en el cn manejador para que no se invoque al cn entidad.
	// Se recupera directamente la etiqueta de la tabla ya que no hay ninguna dependencia asociada.
	function etiquetas($dep=null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_constancias_solicitud');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro)
	{
		// Se recuperan sólo aquellas solicitudes en estado de Pendiente o Impreso (no se administran las ya Rechazadas o Entregadas).
		$where = "(sga_constancias_solicitud.estado = '" . constancias::estado_solicitud_pendiente . "' OR sga_constancias_solicitud.estado = '" . constancias::estado_solicitud_impreso . "')";
		if ($filtro) {
			$where .= ' AND ' . $filtro; 
		}
		return toba::consulta_php('co_constancias')->get_listado_const_solicitadas($where);
	}
	
	/**
	 * Registra el rechazo de la Solicitud con el id de Usuario que lo procesa y los datos indicando el Motivo del mismo.
	*/
	function rechazar_solicitud_constancia($id_solicitud, $id_persona, $observaciones='')
	{	
		try {
			guarani::act('act_constancias')->rechazar_solicitud($id_solicitud, $id_persona, $observaciones);	
		} catch (toba_error $e) {
			throw $e;
		}
	}
	
	/**
	 * Registra la Eliminación de la Solicitud con el id de la misma.
	*/
	function eliminar_solicitud_constancia($id_solicitud)
	{	
		$this->desactivar_transacciones_anidadas();
		try {
			toba::db()->abrir_transaccion();
			// Eliminación de los parámetros actuales de la solicitud, si es que existen.
			guarani::act('act_constancias')->eliminar_parametros_solicitud($id_solicitud);
			// Se elimina el registro de la solicitud.
			guarani::act('act_constancias')->eliminar_solicitud($id_solicitud);
			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}
	
	/**
	 * Registra la entrega de la Solicitud con el id de Usuario que lo procesa. Fecha editada por el usuario para la
	 * Entrega y los demás datos de la misma.
	*/
	function registrar_entrega_solicitud_constancia($id_solicitud, $id_persona, $fecha_entrega, $datos_solicitud)
	{
		try {
			$this->validar_datos_entrega_solicitud($fecha_entrega, $datos_solicitud);
			guarani::act('act_constancias')->registrar_entrega_solicitud($id_solicitud, $id_persona, $fecha_entrega);
		} catch (toba_error $e) {
			throw $e;
		}
	}
	
		
	/**
	 * Setea los datos correspondientes al proceso de impresión de la constancia realizado por la persona dada.
	 * Adicionalmente si el usuario confirmó que desea registrar la entrega de la constancia junto con la impresión, se
	 * configurará al usuario como la persona que realiza la entrega y a la fecha de entrega como la fecha y hora actual.
	 * No se realizarán validaciones adicionales sobre la misma, ya que en este caso no tiene sentido (como al registrar
	 * la entrega de una constancia impresa con anterioridad).
	 */
	function registrar_impresion_solicitud_constancia($id_solicitud, $id_persona, $registrar_entrega=false, $observaciones='')
	{
		try {
			if ($registrar_entrega) {
				guarani::act('act_constancias')->registrar_impresion_entrega_solicitud($id_solicitud, $id_persona, $observaciones);
			} else {
				guarani::act('act_constancias')->registrar_impresion_solicitud($id_solicitud, $id_persona, $observaciones);
			}
		} catch (toba_error $e) {
			throw $e;
		}
	}
	
	//-------- Validaciones ---------------------------------------------
	
	function validar_datos_entrega_solicitud($fecha_entrega, $datos_solicitud)
	{
		// Se valida la fecha de entrega seteada por el usuario. Se sabe que la constancia ya fue Impresa.
		if (isset($datos_solicitud['fecha_impresion'])) {
			$fecha_impresion = $datos_solicitud['fecha_impresion'];
			$fecha_hora_actual = guarani_fecha::get_ahora();
			// Se verifica que la fecha y hora de entrega de la constancia sea mayor o igual a la de impresión de la misma 
			// y menor o igual a la fecha y hora actual.
			if (! guarani_fecha::esta_entre_fechas_horas($fecha_entrega, $fecha_impresion, $fecha_hora_actual, true)) {
				$msj_error = guarani_mensajes::get('800_control_fecha_entre', array('y hora de Entrega', 'Fecha y hora de Impresión', 'Fecha y hora Actual'));
				throw new guarani_error($msj_error);
			}
		}
	}
}
?>