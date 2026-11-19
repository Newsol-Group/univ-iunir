<?php
/**
 *
 *
 * @package 	Exámenes
 * @subpackage 	Actas
 * 
 */
class cn_ent_imprimir_actas_examen extends cn_ent_acta_examen_basica
{
	function get_listado($filtro)
	{
		// Se listan los Llamados de Mesas de Examen que estén en Estado Activo (no Suspendido).
		$where = "vw_mesas_examen.llamado_mesa_estado = '" . mesa_de_examen::mesa_estado_activo . "'";
		$filtro = !empty($filtro) ? $filtro . ' AND ' . $where : $where;
		return toba::consulta_php('co_actas')->get_listado_actas_examen_con_sin_mesas($filtro);
	}

	function get_alumnos_acta_examen($id_acta = null, $estado = null)
	{
		$params_sistema = array('exa_orden_detalle_actas' => guarani::parametros()->get_valor('exa_orden_detalle_actas'));
		return toba::consulta_php('co_actas')->get_alumnos_acta_examen($id_acta, $estado, $params_sistema);
	}

	function cargar($id, $parametros = array())
	{
		if (isset($parametros['acta'])) {
			$this->s__acta = $parametros['acta']['id_acta'];
		}
	}

	function actualizar_datos_impresion($datos_acta, $cant_alumnos, $carga_manual_libro, $forma_asignacion_folio_fisico, $agrega_folios_fisicos_extra, $libro_tomo = null)
	{
		if ($datos_acta['version'] != $datos_acta['version_impresa']) {
			try {
				toba::db()->abrir_transaccion();

				// Si corresponde, se realiza la asignación de folios físicos.
				if (($datos_acta['estado'] != acta::estado_anulada) && ($forma_asignacion_folio_fisico == acta::forma_asignacion_folio_fisico_impresion)) {
					$mensaje_error = acta::asignar_folio_fisico($datos_acta, $cant_alumnos, $forma_asignacion_folio_fisico, $carga_manual_libro, $agrega_folios_fisicos_extra, $libro_tomo);
					if (!empty($mensaje_error)) {
						throw guarani::error(guarani_mensajes::get($mensaje_error));
					}
				}

				// Se actualiza la versión impresa del acta.
				guarani::act('act_actas')->actualizar_version_impresa($datos_acta['id_acta']);

				toba::db()->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db()->abortar_transaccion();
				throw $e;
			}
		}		
	}
	
	function actualizar_cerrado_docente($datos_acta)
	{
		if ($datos_acta['cerrada_por_docente'] == comunes::si) {
			try {
				toba::db()->abrir_transaccion();
				guarani::act('act_actas')->actualizar_cerrada_por_docente($datos_acta['id_acta'], comunes::no);
				toba::db()->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db()->abortar_transaccion();
				throw $e;
			}
		}
	}

}
?>