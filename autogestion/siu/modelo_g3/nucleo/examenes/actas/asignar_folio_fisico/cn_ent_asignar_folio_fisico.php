<?php
/**
 *
 * 
 * @package 	Exámenes
 * @subpackage 	Actas
 * 
 */
class cn_ent_asignar_folio_fisico extends cn_ent_acta_examen_basica
{
	function get_listado($filtro)
	{
		// Se listan los Llamados de Mesas de Examen que estén en Estado Activo (no Suspendido).
		$where = "vw_mesas_examen.llamado_mesa_estado = '" . mesa_de_examen::mesa_estado_activo . "'";
        $where .= " AND sga_actas.estado_documento_digital IS NULL";
		$filtro = !empty($filtro) ? $filtro . ' AND ' . $where : $where;
		return toba::consulta_php('co_actas')->get_listado_actas_examen_con_sin_mesas($filtro);
	}

	function actualizar_asignaciones_ff($id_acta, $libro_tomo, $folios)
	{
		$this->validar_espacio_libro($libro_tomo, $folios);
		$this->validar_folios_fisicos_en_uso($id_acta, $libro_tomo, $folios);

		try {
			toba::db()->abrir_transaccion();

			foreach ($folios as $dato) {
				guarani::act('act_actas')->actualizar_foliado_fisico($id_acta, $dato['folio'], $dato['folio_fisico'], $libro_tomo);
			}

			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}

	function validar_espacio_libro($libro, $folios)
	{
		$ultimo_ff = $folios[guarani_arreglos::count($folios) - 1]['folio_fisico'];
		$cant_max_folios = guarani::co('co_actas')->get_cant_max_folios_libro($libro);
		if (!is_null($cant_max_folios) && $ultimo_ff > $cant_max_folios) {
			$mje = guarani_mensajes::get('800_libro_lleno');
			throw guarani::error($mje);
		}
	}

	function validar_folios_fisicos_en_uso($id_acta, $libro_tomo, $folios)
	{
		if (toba::consulta_php('co_actas')->hay_folios_fisicos_en_uso($id_acta, $libro_tomo, $folios)) {
			$mje = guarani_mensajes::get('800_folio_fisico_usado');
			throw guarani::error($mje);
		}
	}

}