<?php

namespace siu\operaciones\porcentaje_avance_carrera;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_reporte extends pagelet
{
	
	function get_nombre()
	{
		return 'reporte';
	}

	function prepare()
	{
		
		$alumno = kernel::persona()->get_id_alumno_activo();
		$parametros = [
			'alumno' => $alumno, 
			'tipo_titulo' => \tramite_certificacion_nucleo::nivel_titulo_todos, 
			'solo_promediables' => 'false'
		];
		
		$certificados = [];
		$certificados = catalogo::consultar('alumno', 'get_porcentaje_avance_propuesta', $parametros);
		
		$parametros = [
			'alumno' => $alumno
		];
		$promedios_por_titulo = catalogo::consultar('alumno', 'get_promedios_por_titulo', $parametros);
		$this->data['promedios_general_propuesta'] = $promedios_por_titulo[0];
		
		foreach ($certificados as $key => $certificado) {
			
			$certificados[$key]['orientacion_elegida_descr'] = ($certificado['orientacion_elegida'] == \comunes::si)? \comunes::si_desc : \comunes::no_desc;
			$certificados[$key]['porcentaje_avance_descr'] = floatval($certificado['porcentaje_avance']).' %';
			
			$certificados[$key]['promedio_general'] = $promedios_por_titulo[$certificado['certificado']]['promedio_general'];
			$certificados[$key]['promedio_sin_aplazos'] = $promedios_por_titulo[$certificado['certificado']]['promedio_sin_aplazos'];
		}
		
		kernel::log()->add_debug("=== Promedios y porcentaje de avance en la carrera ===", $certificados);
		$this->data['certificados'] = $certificados;
	}
	
}