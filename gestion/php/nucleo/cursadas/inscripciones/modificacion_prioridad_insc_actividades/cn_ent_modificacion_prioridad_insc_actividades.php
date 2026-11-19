<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Evaluaciones
 * 
 */
class cn_ent_modificacion_prioridad_insc_actividades extends cn_entidad
{
	function sincronizar()
	{
		$this->tabla('insc_cursada')->sincronizar();
	}

	//--------------------------------------------------------------------------
	//---- Evaluación
	//--------------------------------------------------------------------------

	function set_datos($datos, $tabla = null, $validar = true)
	{
		$this->validar_datos($datos);
		$this->tabla('insc_cursada')->procesar_filas($datos);
	}

	function get_datos($tabla = null)
	{
		$datos = $this->tabla('insc_cursada')->get_filas();
		return rs_ordenar_por_columna($datos, 'prioridad');
	}

	//--------------------------------------------------------------------------
	//---- Validaciones
	//--------------------------------------------------------------------------

	function validar_datos($datos)
	{
		$comision_prioridad_1 = false;
		$prioridades_usadas = array();
		$vacios = false;

		// Se valida que no haya duplicidad de prioridades
		foreach ($datos as $key => $comision) {
			if (empty($comision['prioridad'])) {
				if (!$vacios) {
					$this->hay_errores = true;
					$mensaje = array('mensaje' => 'No deben quedar prioridades sin completar.', 'tipo' => comunes::error_critico);
					$this->errores[] = $mensaje;
					$vacios = true;
				}
			} else {
				if (in_array($comision['prioridad'], $prioridades_usadas)) {
					$this->hay_errores = true;
					$mensaje = array('mensaje' => 'Hay duplicidad de prioridades.', 'tipo' => comunes::error_critico);
					$this->errores[] = $mensaje;
				}

				$prioridades_usadas[] = $comision['prioridad'];

				if ($comision['prioridad'] == 1) {
					$comision_prioridad_1 = true;
				}
			}
		}

		// Se valida que alguno de los registros sea de prioridad 1
		if (!$comision_prioridad_1) {
			$this->hay_errores = true;
			$mensaje = array('mensaje' => 'Alguno de los registros debe ser de prioridad 1.', 'tipo' => comunes::error_critico);
			$this->errores[] = $mensaje;
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

}
?>