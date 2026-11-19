<?php
class cn_man_asignacion_actividad extends cn_manejador_entidad
{	
	function get_indice_instancia()
	{
		return "asignacion_actividades";
	}
	
	function get_listado($filtro)
	{
		$listado = toba::consulta_php('co_elementos')->get_listado(elemento::tipo_actividad, null, $filtro);
		$listado = rs_ordenar_por_columnas($listado, array('subtipo_desc', 'nombre', 'codigo'));
		return $listado;
	}
}

?>