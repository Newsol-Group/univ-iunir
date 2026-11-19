<?php
namespace siu\operaciones\reporte_readmisiones;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_reporte_readmisiones extends pagelet
{
	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'reporte_solicitudes_readmisiones';
	}

	function prepare()
	{
		$filtro = "  sga_alumnos.alumno = '" . kernel::persona()->get_id_alumno_activo() . "'";
		$datos = \toba::consulta_php('co_solicitudes_readmision')->get_listado($filtro);

		foreach ($datos as $key => $dato) {
			if ($dato['estado'] == \comunes::estado_aceptado) {
				$datos[$key]['estado_nombre'] = \comunes::estado_aceptado_desc;
			} elseif ($dato['estado'] == \comunes::estado_pendiente) {
				$datos[$key]['estado_nombre'] = \comunes::estado_pendiente_desc;
			}elseif ($dato['estado'] == \comunes::estado_rechazado) {
				$datos[$key]['estado_nombre'] = \comunes::estado_rechazado_desc;
			}
		}


		$this->data['reporte_readmisiones'] = $datos;
	}

}
?>