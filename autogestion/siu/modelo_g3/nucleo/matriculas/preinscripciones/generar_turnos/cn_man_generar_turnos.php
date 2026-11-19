<?php
class cn_man_generar_turnos extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'generar_turnos_preinscripcion';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_turnos_preinscripcion')->get_listado_turnos_config($filtro);
	}
}
?>