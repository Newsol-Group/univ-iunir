<?php 
/**
 *
 *
 * @package Facturación
 * @subpackage Actualización Transacciones de Exámenes
 */
class ci_rep_transacciones_examenes_nsg extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_facturacion')->get_transacciones_examenes_perfil_de_datos($filtro);
	}

	function procesar_transacciones_examenes()
	{
		// Chequea si hay registros en la tabla de transacciones de Softland, si hay no permite insertar porque significa que hay datos sin validar/corregir
		$ret = toba::consulta_php('co_facturacion')->chequear_tabla_temporal();
		
		if ($ret == 1)
			// Se insertan los registros correspondientes en la tabla de transacciones de examenes.
			toba::consulta_php('co_facturacion')->insertar_transacciones_examenes();
		else 
			throw guarani::error(guarani_mensajes::get('800nsg_error_no_esta_vacia_tabla'));
	}

	function evt__procesar()
	{
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
	
		if (empty($perfil_datos)) {
			$this->procesar_transacciones_examenes();
		} else {
			throw new guarani_error($e->getMessage());
		}
	}

}
?>