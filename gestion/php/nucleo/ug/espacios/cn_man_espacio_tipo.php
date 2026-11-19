<?php
class cn_man_espacio_tipo extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'espacios_tipo';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_espacios')->get_listado_tipos($filtro);
	}
}

?>