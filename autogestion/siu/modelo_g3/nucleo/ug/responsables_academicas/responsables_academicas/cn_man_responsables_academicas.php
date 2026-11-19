<?php
class cn_man_responsables_academicas extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'responsable_academica';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_responsables_academicas')->get_listado($filtro);
	}
}

?>