<?php 
/**
 *
 *
 * @package Facturación
 * @subpackage Actualización Datos de Docentes
 */
class ci_rep_actualizar_docentes_nsg extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'sga_docentes';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_facturacion')->get_datos_docentes_perfil_de_datos($filtro);
	}

	function procesar_docentes()
	{

		// Se insertan los registros correspondientes en la tabla de docentes.
		toba::consulta_php('co_facturacion')->insertar_docentes_tabla_softland();

		// Se borran los registros correspondientes en la tabla de docentes.
		toba::consulta_php('co_facturacion')->procesar_en_softland_y_borrar();

	}

	function evt__procesar()
	{
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		
		if (empty($perfil_datos)) {
			$this->procesar_docentes();
		} else {
			throw new guarani_error($e->getMessage());
		}
	}

}
?>