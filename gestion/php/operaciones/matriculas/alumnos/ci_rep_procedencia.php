<?php
class ci_rep_procedencia extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		$datos = guarani::co('co_datos_censales')->get_procedencias($filtro);
		return $datos;
	}

	function get_datos_cant($filtro)
	{
		$datos = guarani::co('co_datos_censales')->get_procedencias($filtro);
		return $datos;
	}



}

?>