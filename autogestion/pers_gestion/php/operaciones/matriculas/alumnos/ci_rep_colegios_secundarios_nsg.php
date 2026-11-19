<?php
class ci_rep_colegios_secundarios_nsg extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		$datos = guarani::co('co_alumnos')->get_colegios_secundarios($filtro);
		return $datos;
	}
}

?>