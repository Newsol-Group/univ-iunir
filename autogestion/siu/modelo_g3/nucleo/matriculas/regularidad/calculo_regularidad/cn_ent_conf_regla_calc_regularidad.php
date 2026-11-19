<?php
/**
 *
 *
 * @package		Matricula
 * @subpackage	Regularidad
 * 
 */
class cn_ent_conf_regla_calc_regularidad extends cn_entidad
{	
	function get_datos_propuestas_ubicacion()
	{
		return $this->tabla('propuestas_regularidad')->get_filas();
	}

	function set_datos_propuestas_ubicacion($datos)
	{
		$this->tabla('propuestas_regularidad')->procesar_filas($datos);
	}

	function eliminar_propuestas_ubicacion($clave)
	{
		$this->tabla('propuestas_regularidad')->eliminar_fila($clave);
	}

	function agregar_propuestas_ubicacion($datos_propuesta)
	{		
		if($this->validar_propuestas_ubicacion($datos_propuesta)) {
			$this->tabla('propuestas_regularidad')->nueva_fila($datos_propuesta);
		} else {
			toba::notificacion()->agregar($this->get_mensaje('800MAT_propuesta_ubicacion_superpuesta',[$datos_propuesta['propuesta_nombre'], $datos_propuesta['ubicacion_nombre']]), 'info');	
		}
	}
	
	//-----------------------------------
	
	function validar_propuestas_ubicacion($datos_propuesta)
	{
		$valido = true;
		$datos_actuales = $this->get_datos_propuestas_ubicacion();
		$i = 0;
		
		while( $valido && ($i < guarani_arreglos::count($datos_actuales)) ) {
			if( ($datos_actuales[$i]['propuesta'] == $datos_propuesta['propuesta']) && 
				($datos_actuales[$i]['ubicacion'] == $datos_propuesta['ubicacion']) ) {
				$valido = false;
			} else {
				$i++;
			}
		}
		
		return $valido;	
	}
}
?>