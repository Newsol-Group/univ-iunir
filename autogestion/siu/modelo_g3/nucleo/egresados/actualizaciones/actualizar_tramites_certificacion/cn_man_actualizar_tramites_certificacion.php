<?php
/**
 *
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class cn_man_actualizar_tramites_certificacion extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'actualizar_tramites_certificacion';
	}

	function get_listado($filtro)
    {
        // Sólo solicitudes no anuladas.
        $where = 'sga_certificados_otorg.anulado = 0';
        if ($filtro) {
            $where .= " AND $filtro";
        }

        // Obtengo los Trámites de Certificación
        $tramites = toba::consulta_php('co_tramites_certificacion')->get_listado($where);

        foreach ($tramites as $key => $tramite) {
            // Obtiene la descripción del Trámite de Certificación desde SUDOCU.
            $tramites[$key]['nro_expediente'] = sudocu::getDescripcionTramiteCertificacion($tramite);
        }

        return $tramites;
    }

    function validar_seleccion_tramite($nro_solicitud)
	{
		$datos_tramite = toba::consulta_php('co_tramites_certificacion')->get_datos_tramite($nro_solicitud);
		if (!empty($datos_tramite['estado']) && toba::consulta_php('co_circuitos_tramite')->es_estado_final_de_circuito($datos_tramite['estado'], $datos_tramite['circuito'])) {
			if (! toba::consulta_php('co_circuitos_tramite')->estado_tiene_transiciones_recursivas_circuito($datos_tramite['estado'], $datos_tramite['circuito'])) {
				$msj_error = guarani_mensajes::get('800EGR_sin_cambio_estado_final');
				throw guarani::error($msj_error);
			}
		}
		if (!empty($datos_tramite['estado_requisitos']) && $datos_tramite['estado_requisitos'] != tramite_certificacion::estado_requisitos_aceptado) {
			$msj_error = guarani_mensajes::get('800EGR_requisitos_egreso_pendientes');
			throw guarani::error($msj_error);
		}
	}
}