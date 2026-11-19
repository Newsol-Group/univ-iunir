<?php
/**
 *
 *
 * @package 	Matricula
 * @subpackage 	Alumnos
 * 
 */
class cn_ent_vencimiento_regularidad extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar($datos);
		$this->tabla('regularidades_venc')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('regularidades_venc')->get();
	}
	
	function validar($datos)
	{
		$this->validar_fecha($datos);
		// Se valida que la fecha de vencimiento no coincida con la del registro anterior (si existe). 
		if (isset($datos['fecha_vigencia_anterior']) && isset($datos['fecha_vigencia_nuevo']) && $datos['fecha_vigencia_anterior'] == $datos['fecha_vigencia_nuevo']) {
			$this->set_error('800_control_fechas', array('de Vigencia anterior', 'igual', 'de Vigencia nueva'));
		}
		
		if ($this->hay_errores) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_fecha($datos)
	{
		if (!is_null($datos['fecha_vigencia_nuevo'])) {
			if (!is_null($datos['id_acta'])) {
				$id_acta = toba::db()->quote($datos['id_acta']);
				$alumno = toba::db()->quote($datos['alumno']);
				$filtro = " vw_regularidades.id_acta = $id_acta AND
							vw_regularidades.alumno = $alumno AND
							vw_regularidades.origen_otra_propuesta = false";
			} else {
				$equivalencia = toba::db()->quote($datos['equivalencia']);
				$filtro = " vw_regularidades.equivalencia = $equivalencia";
			}

			$datos_regularidad = current(toba::consulta_php('co_alumnos')->get_regularidades($filtro));
			if(!isset($datos_regularidad['fecha'])){
				$datos_regularidad['actividad_codigo_y_nombre'] = isset($datos_regularidad['actividad_codigo_y_nombre']) ? $datos_regularidad['actividad_codigo_y_nombre'] : ''; 
				throw guarani::error("Revise la regularidad del alumno en la actividad ". $datos_regularidad['actividad_codigo_y_nombre'] . " su fecha es vacía");
			}
			if (guarani_fecha::es_mayor($datos_regularidad['fecha'], $datos['fecha_vigencia_nuevo'])) {
				$this->set_error('800_control_fechas', array('Regularidad', 'mayor', 'de Vigencia'));
			}
		}
	}
}
?>