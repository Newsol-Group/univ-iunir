<?php 
/**
 *
 *
 * @package Matriculas
 * @subpackage Actualizaciones
 */
class ci_rep_alumnos_posgrado_a_facturar_nsg extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_facturacion')->get_reporte_alumnos_posgrado_a_facturar($filtro);
	}

	function procesar_alumnos_posgrado()
	{
		// Se insertan los registros correspondientes en la tabla de alumnos.
		toba::consulta_php('co_facturacion')->insertar_alumnos_posgrado();

		// Se insertan los registros correspondientes en la tabla de alumnos.
		toba::consulta_php('co_facturacion')->borrar_tabla_temporal();
	}

	function evt__procesar()
	{
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
	
		if (empty($perfil_datos)) {
			$this->procesar_alumnos_posgrado();
		} else {
			throw new guarani_error($e->getMessage());
		}
	}

}
?>