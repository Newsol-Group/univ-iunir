<?php
class cn_man_administrar_turnos extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'administrar_turnos_preinscripcion';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_turnos_preinscripcion')->get_listado_turnos_admistrador($filtro);
	}

}