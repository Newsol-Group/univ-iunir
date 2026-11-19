<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class cn_man_confirmar_comisiones extends cn_manejador_entidad
{
	function get_listado_confirmar_comisiones($filtro_comisiones, $filtro_actividades)
	{
		return toba::consulta_php('co_elementos')->get_actividades_propuestas_con_comisiones($filtro_comisiones, $filtro_actividades);
	}
	
	function get_listado_comisiones_subcomisiones($actividad, $per_lectivo, $ubicacion)
	{
		/**
		 * Recupera las comisiones para la Actividad, Período Lectivo y Ubicación seleccionados, y subcomisiones de 
		 * las mismas, con sus datos correspondientes.
		 */
		$where = " sga_comisiones.periodo_lectivo = $per_lectivo AND sga_comisiones.ubicacion = $ubicacion ";
		$comisiones = toba::consulta_php('co_comisiones')->get_datos_comisiones_con_cant_subcomisiones_actividad($actividad, $where);
		$cond_in = guarani_sql::armar_condicion_in($comisiones, 'sga_subcomisiones', 'comision', 'comision');
		$subcomisiones = toba::consulta_php('co_comisiones')->get_datos_subcomisiones($cond_in);
		$datos = array();
		// Arma el arreglo de filas de cada comisión seguida de todas sus subcomisiones.
		foreach ($comisiones as $comision) {
			array_push($datos, $comision);
			foreach ($subcomisiones as $subcomision) {
				if ($comision['comision'] == $subcomision['comision']) {
					$subcomision['nombre'] = $comision['nombre'] . ' - ' . $subcomision['nombre'];
					array_push($datos, $subcomision);
				}
			}
		}
		return $datos;
	}
	
	function habilitar_deshabilitar_inscripcion_comisiones($datos)
	{
		/**
		 * Se recorre el arreglo de Comisiones / Subcomisiones para modicar la habilitación y visibilidad de las 
		 * filas que fueron editadas por el usuario.
		 */
		try {
			foreach ($datos as $comisionsubcom) {
				if ($comisionsubcom['apex_ei_analisis_fila'] == 'M') {
					// Se editó una subcomisión.
					if (!is_null($comisionsubcom['subcomision'])) {
						/**
		 				 * Se invoca a la función que modifica los datos de la habilitación de la subcomisión.
		 			 	 */
						guarani::act('act_comisiones')->modificar_habilitacion_subcomision($comisionsubcom['subcomision'], $comisionsubcom['inscripcion_habilitada']);
					} elseif (!is_null($comisionsubcom['comision'])) {
						/**
		 			 	* Se invoca a la función que modifica los datos de la habilitación y visibilidad de 
		 			 	* la comisión.
		 				*/
						guarani::act('act_comisiones')->modificar_habilitacion_comision($comisionsubcom['comision'], $comisionsubcom['inscripcion_habilitada']);
					}
				}
			}
		} catch (toba_error $e)	{
			throw $e;
		}
	}
}
?>