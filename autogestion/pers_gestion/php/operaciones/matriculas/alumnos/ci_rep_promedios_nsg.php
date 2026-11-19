<?php
class ci_rep_promedios_nsg extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		return guarani::co('co_alumnos')->get_reporte_promedios($filtro);
	}

}
?>