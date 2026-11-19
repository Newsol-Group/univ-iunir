<?php
class ci_rep_alumnos_regulares_x_propuesta_nsg extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		$datos = guarani::co('co_alumnos')->get_alumnos_regulares_x_propuesta($filtro);
		return $datos;
	}
}
?>