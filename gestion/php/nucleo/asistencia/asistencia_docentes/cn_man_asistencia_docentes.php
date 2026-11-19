<?php
/**
 *
 *
 * @package		Asistencias
 * @subpackage	-
 * 
 */
class cn_man_asistencia_docentes extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'asistencia_docente';
	}
	
	function cargar_atributos_parametros($comision)
	{
		$operacion = guarani::get_id_operacion();

		$propuestas = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($comision), 'propuesta');
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);
	}

	/**
	 * Recupera las comisiones en estado "Activo" y sin acta de cursada/promoción cerrada, según el filtro dado.
	 */
	function get_listado_preseleccion($filtro)
	{
		$filtro .= " AND NOT EXISTS (	SELECT	sga_actas.id_acta
										FROM	sga_actas
										WHERE	sga_actas.comision = sga_comisiones.comision AND
												sga_actas.estado = '".acta::estado_cerrada."')";
		$filtro .= " AND sga_comisiones.estado = '" . comunes::estado_activo . "'";
		return toba::consulta_php('co_comisiones')->get_listado($filtro);
	}

	/**
	 * Recupera las Subcomisiones 
	 */
	function get_listado_subcomisiones($comision)
	{
		return toba::consulta_php('co_comisiones')->get_subcomisiones_comision_con_asignacion_horaria($comision);
	}

	/**
	 * Recupera todas las Clases de la Comisión o Subcomisión dada.
	 */
	function get_listado_clases($comision, $subcomision = null)
	{
		if (!is_null($subcomision)) {
			return toba::consulta_php('co_clases')->get_clases_subcomision($subcomision);
		} else {
			return toba::consulta_php('co_clases')->get_clases_comision($comision);
		}
	}

	/**
	 * Recupera los Docentes asociados a Comisión o Subcomisión seleccionadas, junto con los datos de 
	 * sus inasistencias a la Clase seleccionada.
	 */
	function get_listado_docentes_inasistencias($comision, $subcomision, $clase)
	{
		if (!is_null($subcomision)) {
			return toba::consulta_php('co_asistencias')->get_docentes_subcomision_con_inasistencias_clase($subcomision, $clase);
		} else {
			return toba::consulta_php('co_asistencias')->get_docentes_comision_con_inasistencias_clase($comision, $clase);
		}
	}

}
?>