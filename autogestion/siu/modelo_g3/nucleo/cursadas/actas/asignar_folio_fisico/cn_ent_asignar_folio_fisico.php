<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Actas
 * 
 */
class cn_ent_asignar_folio_fisico extends guarani_pers_cn
{
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
		$ultimo_ff = ($folios[guarani_arreglos::count($folios) - 1]['folio_fisico']) ?? null;
		$cant_max_folios = guarani::co('co_actas')->get_cant_max_folios_libro($libro);
		if (!is_null($cant_max_folios) && $ultimo_ff > $cant_max_folios) {
			$mje = guarani_mensajes::get('800_libro_lleno');
			throw guarani::error($mje);
		}
	}

	function validar_folios_fisicos_en_uso($id_acta, $libro, $folios)
	{
		if (toba::consulta_php('co_actas')->hay_folios_fisicos_en_uso($id_acta, $libro, $folios)) {
			$mje = guarani_mensajes::get('800_folio_fisico_usado');
			throw guarani::error($mje);
		}
	}

	//--------------------------------------------------------------------------
	//---- Manejo de atributos de parámetros -----------------------------------
	//--------------------------------------------------------------------------
	
	function cargar_atributos_parametros($comision)
	{
		$operacion = guarani::get_id_operacion();

		$propuestas = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($comision), 'propuesta');
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);

		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
	}
	
	function cargar_dependencias_parametros($origen_acta, $digital)
	{
		try {
			$operacion = guarani::get_id_operacion();
			if ($origen_acta == acta::origen_regulares) {
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_forma_asignacion_folio_fisico_promo');
				if ($digital == comunes::si) {
					guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_forma_asignacion_folio_fisico_regul');
				}
			} else {
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_forma_asignacion_folio_fisico_regul');
				if ($digital == comunes::si) {
					guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_forma_asignacion_folio_fisico_promo');
				}
			}
		} catch (guarani_error_param_no_usado $e) {
		}
	}
}