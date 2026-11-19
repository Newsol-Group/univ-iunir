<?php
/**
 * 
 * @package		Matrícula
 * @subpackage	Regularidades
 */
class cn_man_solicitudes_readmision extends cn_manejador_entidad
{
	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_readmisiones_solicitud');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro)
	{
		$filtro .= " AND sga_readmisiones_solicitud.estado = '". vencimiento_regularidad::solicitud_readmision_pendiente."'";
		return toba::consulta_php('co_solicitudes_readmision')->get_listado($filtro);
	}

	function rechazar_solicitudes($solicitudes, $observaciones)
	{
		guarani::act('act_vencimientos_regularidad')->actualizar_estado_solicitudes_readmision($solicitudes, vencimiento_regularidad::solicitud_readmision_rechazada, guarani_fecha::get_hoy(), $observaciones);
		toba::notificacion()->agregar(guarani_mensajes::get('800MAT_rechazo_solicitudes_readmision_ok'), comunes::error_informacion);
	}

	function aceptar_solicitudes($solicitudes, $observaciones)
	{
		try {
			toba::db()->abrir_transaccion();
			foreach ($solicitudes as $solicitud) {
				guarani::act('act_vencimientos_regularidad')->actualizar_estado_solicitudes_readmision($solicitud['solicitud_readmision'], vencimiento_regularidad::solicitud_readmision_aceptada, guarani_fecha::get_hoy(), $observaciones);
				$fecha_vencimiento_readmision = ($solicitud['anio_academico'] + 1).'-03-31';
				guarani::act('act_alumnos')->insertar_readmision($solicitud['perdida_regularidad'], $solicitud['anio_academico'], vencimiento_regularidad::readmision_tipo_reincorporacion, comunes::si, guarani_fecha::get_hoy(), $fecha_vencimiento_readmision);
				$cant_readmisiones = toba::consulta_php('co_alumnos')->get_cant_readmisiones($solicitud['alumno']);
				guarani::act('act_alumnos')->actualizar_cant_readmisiones($solicitud['alumno'], $cant_readmisiones + 1);
				guarani::act('act_alumnos')->actualizar_regularidad($solicitud['alumno'], comunes::si);
			}
			toba::db()->cerrar_transaccion();
			toba::notificacion()->agregar(guarani_mensajes::get('800MAT_aceptacion_solicitudes_readmision_ok'), comunes::error_informacion);
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}
}
?>