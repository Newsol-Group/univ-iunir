<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Alumnos
 * 
 */
class ci_edi_vencimiento_regularidad extends ci_edicion_simple 
{
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__formulario__modificacion($datos)
	{		
		$datos_modificacion = array();
		$datos_modificacion['alumno'] = $this->controlador()->get_preseleccion();
		$datos_modificacion['id_acta'] = $this->controlador()->get_id_acta();
		$datos_modificacion['equivalencia'] = $this->controlador()->get_equivalencia();
		$datos_modificacion['documento'] = $datos['documento'];
		if ($this->cn()->entidad()->es_nueva()) {
			$datos_modificacion['fecha_vigencia_anterior'] = $this->controlador()->get_fecha_vencimiento_actual();
		}
		$datos_modificacion['fecha_vigencia_nuevo'] = $datos['fecha_vigencia_nuevo'];
		$datos_modificacion['observaciones'] = $datos['observaciones'];
		parent::evt__formulario__modificacion($datos_modificacion);
	}
}
?>