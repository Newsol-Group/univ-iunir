<?php
namespace siu\modelo\datos\db;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;
use \siu\modelo\entidades;

class calendario_academico {

	
	/**
	 * cache: memoria
	 * filas: n
	 */
	function get_anios_academicos_con_periodos_lectivos_vigentes()
	{
		// Recupera años academicos que tienen períodos lectivos que no finalizaron.
		$sql = "SELECT sga_anios_academicos.anio_academico
				  FROM sga_anios_academicos,
					   sga_periodos,
				       sga_periodos_lectivos
				WHERE sga_periodos.anio_academico = sga_anios_academicos.anio_academico
				  AND sga_periodos_lectivos.periodo = sga_periodos.periodo
				  AND sga_periodos.fecha_fin >= CURRENT_DATE
				GROUP BY sga_anios_academicos.anio_academico
				ORDER BY sga_anios_academicos.anio_academico DESC;
				";
		
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}
} 
