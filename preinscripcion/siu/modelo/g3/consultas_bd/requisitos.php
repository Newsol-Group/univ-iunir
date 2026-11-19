<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\g3\interface_requisitos;
use siu\modelo\util;
use siu\modelo\propuesta;
use siu\modelo\opciones;

class requisitos extends entidad_g3 implements interface_requisitos
{
	protected static $instancia;
	
	protected $tiempo_cache = 30;

	static function instancia()
	{
		$clase = substr(strrchr(get_class(), "\\"), 1);
		if (!isset(self::$instancia)) {
			self::$instancia = kernel::localizador()->instanciar("modelo\\g3\\consultas_bd\\$clase");
		}
		return self::$instancia;
	}
	
	/**
	 * Obtiene el conjunto de requisitos documentales que tienen un tipo de requisito
	 * digital asociado, que aplican como requisito de ingreso de las propuestas en las 
	 * que se preinscribe el aspirante.
	 * 
	 * @param array $propuestas
	 * @return array
	 */
	function requisitos_a_solicitar($propuestas)
	{
		$in_propuestas = implode(',', $propuestas);
		/*
		  Listado de requisitos de ingreso de las propuestas pasadas por parametro.
		  Requisitos que estan visibles en el módulo de preinsripción y que tienen asociado un tipo de documento digital.
		*/
		$sql = "SELECT DISTINCT 
					sga_requisitos.requisito,
					sga_requisitos.nombre as nombre,
					sga_requisitos_propuestas.restrictivo,
					sga_requisitos.tipo_documento_digital,
					sga_requisitos.descripcion,
					sga_requisitos_propuestas.visible_en_preinscripcion,
					sga_requisitos_propuestas.doc_digital_obligatorio_preinscripcion,
					get_requisito_subordinado_de(sga_requisitos.requisito, '') as subordinado_de
				FROM sga_requisitos_propuestas_aplanado 
				JOIN sga_requisitos_propuestas ON sga_requisitos_propuestas.requisito_propuesta = sga_requisitos_propuestas_aplanado.requisito_propuesta
				JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_requisitos_propuestas_aplanado.plan_version
				JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
				JOIN sga_propuestas ON sga_planes.propuesta = sga_propuestas.propuesta
				JOIN sga_requisitos ON sga_requisitos_propuestas.requisito = sga_requisitos.requisito
				WHERE sga_requisitos_propuestas.visible_en_preinscripcion = 'S'
				  AND sga_requisitos_propuestas.requisito_para = 'I'
				  AND sga_propuestas.propuesta IN ($in_propuestas)
				  -- Que tenga un tipo de requisito digital asociado
				  AND sga_requisitos.tipo_documento_digital IS NOT NULL
				ORDER BY	
					sga_requisitos.nombre";
		
		$datos = $this->consultar($sql);	
		return $datos;
	}
	
	/**
	 * Obtiene el tipo de documento digital de un requisito dado
	 * 
	 * @param int $requisito
	 * @return string
	 */
	function tipo_documento_digital($requisito)
	{
		$requisito = kernel::db()->quote($requisito);
		
		$sql = "SELECT 
					tipo_documento_digital
				FROM 
					sga_requisitos
				WHERE
					requisito = $requisito
				";
		
		$datos = $this->consultar_fila($sql);
		$salida = (!empty($datos['tipo_documento_digital'])) ? $datos['tipo_documento_digital'] : null;
		return $salida;
	}
	
	function test($id)
	{
		// Se testean las funciones particualres
		try {
			$datos = array();
			$datos['requisitos'] = $this->requisitos_a_solicitar($id);
			$datos['tipo_documento_digital'] = $this->tipo_documento_digital(current($id));
			$salida = array(
				'resultado' => true,
				'datos' => $datos,
				'msj' => 'OK',
			);
		} catch (\Exception $ex) {
			$salida = array(
				'resultado' => false,
				'msj' => $ex->getMessage(),
			);
		}
		return $salida;
	}
	
}
?>