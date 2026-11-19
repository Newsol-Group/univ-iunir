<?php
/**
 *
 * 
 * @package		Cursadas
 * @subpackage	Inscripciones
 * 
 */
class cn_man_revertir_rechazo_insc_actividades extends cn_manejador_entidad
{
	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_insc_cursada_log');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro)
	{
		$filtro_rechazadas = "sga_insc_cursada_log.operacion = '" . inscripcion_cursada::operacion_rechazo . "'";

		if ($filtro) {
			$filtro .= ' AND ' . $filtro_rechazadas;
		} else {
			$filtro = $filtro_rechazadas;
		}
		return guarani::co('co_inscripciones_cursadas')->get_listado_insc_cursadas_rechazadas_bajas($filtro, true, true);
	}

	function rechazar_inscripciones($id_inscripcion)
	{
		try {
			toba::db()->abrir_transaccion();
			$resultado = guarani::act('act_inscripcion_cursadas')->revertir_rechazo_insc_cursada_func($id_inscripcion);

			// Controlar el valor devuelto por la función para verificar si se ejecutó correctamente.
			if ($resultado['res'] < 1) {
				// Hubo error.
				$mje = guarani_mensajes::get($resultado['msg_indice'], $resultado['params']);
				throw guarani::error($mje);
			} else {
				// Se ejecutó correctamente, se muestra el msj de ok.
				$mje = guarani_mensajes::get($resultado['msg_indice']);
				toba::notificacion()->agregar($mje, comunes::error_informacion);
			}

			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}

}
?>
