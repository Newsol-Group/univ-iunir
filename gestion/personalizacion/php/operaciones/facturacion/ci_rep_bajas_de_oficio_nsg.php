<?php
class ci_rep_bajas_de_oficio_nsg extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_clases_asistencia_acum';
	}

	function get_datos($filtro)
	{
		return guarani::co('co_alumnos')->get_bajas_de_oficio_x_inasistencias($filtro);
	}
}
?>