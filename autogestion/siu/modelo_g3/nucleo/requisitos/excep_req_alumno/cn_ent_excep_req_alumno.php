<?php 
/**
 * 
 *
 * @package 	Requisitos
 * @subpackage 	-
 * 
 */
class cn_ent_excep_req_alumno extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('requisitos_excepciones')->set($datos);
	}

	function get_datos($tabla=null)
	{
		return $this->tabla('requisitos_excepciones')->get();
	}

	function validar_datos($datos)
	{
		// Si la excepción es nueva o no está vigente, "Fecha Desde" debe ser mayor o igual a la actual.
		$excepcion = $this->tabla('requisitos_excepciones')->get_columna('excepcion');
		if (empty($excepcion) || ! toba::consulta_php('co_requisitos')->requisito_excepcion_vigente($excepcion)) {
			$fecha_hoy = guarani_fecha::get_hoy();
			if (guarani_fecha::es_menor($datos['fecha_desde'], $fecha_hoy)) {
				$this->set_error('800_control_fechas', array('desde', 'anterior', 'actual'));
			}
		}

		// "Fecha Desde" debe ser menor a "Fecha Hasta".
		if (guarani_fecha::es_mayor_igual($datos['fecha_desde'], $datos['fecha_hasta'])) {
			$this->set_error('800_control_fechas', array('desde', 'mayor o igual', 'hasta'));
		}

		// No deben poder cargarse excepciones iguales con las mismas fechas de inicio y de fin
		$alumno = toba::db()->quote($datos['alumno']);
		$filtro = "sga_requisitos_excepciones.alumno = $alumno";
					
		if (!empty($excepcion)) {
			$excepcion = toba::db()->quote($excepcion);
			$filtro .= "AND sga_requisitos_excepciones.excepcion <> $excepcion";
		}
		
		$datos_excep = toba::consulta_php('co_requisitos')->get_requisitos_excepciones($filtro);
		
		if (!empty($datos_excep)) {
			foreach ($datos_excep as $dato_excep) {
				if (($dato_excep['accion'] == $datos['accion']) && ($dato_excep['requisito'] == $datos['requisito']) 
					&& (guarani_fecha::rango_solapado_en_rango($datos['fecha_desde'], $datos['fecha_hasta'], $dato_excep['fecha_desde'], $dato_excep['fecha_hasta']))
					&& (($dato_excep['elemento'] == $datos['elemento']) || is_null($dato_excep['elemento']) || is_null($datos['elemento']))) {
					$this->set_error('800_control_duplicado');
				}
			}
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
}
?>