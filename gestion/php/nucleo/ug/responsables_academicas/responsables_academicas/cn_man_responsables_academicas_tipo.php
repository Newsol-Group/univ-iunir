<?php
class cn_man_responsables_academicas_tipo extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'responsable_academica_tipo';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_responsables_academicas')->get_listado_tipos($filtro);
	}
}

?>