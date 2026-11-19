<?php
/**
 *
 * 
 * @package		Unidades de Gestión
 * @subpackage	Actualizaciones
 * 
 */
class cn_man_libros_actas extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'libro_actas';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_actas')->get_listado_libros_actas($filtro);
	}

}
?>