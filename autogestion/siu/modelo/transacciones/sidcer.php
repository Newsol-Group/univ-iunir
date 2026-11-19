<?php

namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class sidcer extends transaccion_alumno
{
	
	function info__solicitudes_sidcer()
	{
		
		// Obtengo los certificados asociados al plan del alumno
		$certificados = catalogo::consultar('certificacion', 'get_certificados_plan_version', array(
			'plan_version' => $this->plan_version
		));
		$titulos_araucano = array_column($certificados, 'titulo_araucano');
		kernel::log()->add_debug("=== Títulos de Araucano ===", $titulos_araucano);
		
		// Obtengo los títulos del alumno en SidCer
		$tipoDocumento = kernel::persona()->get_tipo_documento_desc_abreviada();
		$numeroDocumento = kernel::persona()->get_nro_documento();
		kernel::log()->add_debug("=== Tipo Documento ===", $tipoDocumento);
		kernel::log()->add_debug("=== Número Documento ===", $numeroDocumento);
		$solicitudes_sidcer = \siu\modelo_g3\sidcer::getEstadoSolicitud(['tipo_documento' => $tipoDocumento, 'numero_documento' => $numeroDocumento]);
		
		// Filtro los títulos del alumno que correspondan al plan actual
		$solicitudes_sidcer['solicitudes'] = array_filter($solicitudes_sidcer['solicitudes'], function ($solicitud_sidcer) use ($titulos_araucano) {
			return in_array($solicitud_sidcer['id_titulo'], $titulos_araucano);
		});
		
		return $solicitudes_sidcer;
	}

	function info__analiticos_diplomas_digitales()
	{
		
		$parametros['persona'] = $this->persona;
		$parametros['propuesta'] = $this->propuesta;
		// Traigo todos los trámites de certificación para la persona en la carrera actual
		$tramites = catalogo::consultar('certificacion', 'get_tramites_certificacion_by_persona_propuesta', $parametros);
		
		foreach ($tramites as $key => $tramite)
		{
			$tramites[$key]['analitico']['id_documento_digital'] = false;
			$tramites[$key]['analitico']['mensaje'] = kernel::traductor()->trans('diploma_digital.documento_no_disponible');
			$tramites[$key]['diploma']['id_documento_digital'] = false;
			$tramites[$key]['diploma']['mensaje'] = kernel::traductor()->trans('diploma_digital.documento_no_disponible');
			// Traigo todos los documentos asociados al trámite (Diploma y Analítico)
			$documentos_asociados = catalogo::consultar('certificacion', 'get_documentos_asociados_tramite_certificacion', ['nro_solicitud' => $tramite['nro_solicitud']]);
			
			foreach ($documentos_asociados as $documento_asociado)
			{
				// Si es Analítico
				if ($documento_asociado['tipo_documento'] == \siu\modelo_g3\sidcer::documentoAnaliticoAbrev) {
					$documento = 'analitico';
				} else if ($documento_asociado['tipo_documento'] == \siu\modelo_g3\sidcer::documentoDiplomaAbrev) {// Si es Diploma
					$documento = 'diploma';
				}
				
				// Traigo la solicitud de SICer
				$solicitudes = \siu\modelo_g3\sidcer::getEstadoSolicitud(['solicitud' => $documento_asociado['nro_tramite_sicer']]);
				// Si hubo error en SICer
				if ($solicitudes['error']) {
					$tramites[$key][$documento]['mensaje'] = kernel::traductor()->trans('diploma_digital.fallo_generico');
					continue;
				} else {
					$solicitud = current($solicitudes['solicitudes']);
					$ultimoEstado = \siu\modelo_g3\sidcer::getUltimoEstadoSolicitudSicer($solicitud['historial']);
					// Si la solicitud de SICer esta en estado "Finalizado"
					if ($ultimoEstado == \siu\modelo_g3\sidcer::solicitudFinalizada) {
						$tramites[$key][$documento]['id_documento_digital'] = $documento_asociado['id_documento_digital'];
						$tramites[$key][$documento]['mensaje'] = "";
						continue;
					} else {
						$tramites[$key][$documento]['mensaje'] = kernel::traductor()->trans('diploma_digital.documento_no_disponible');
						continue;
					}
				}
			}
		}
		
		return $tramites;
	}
	
}