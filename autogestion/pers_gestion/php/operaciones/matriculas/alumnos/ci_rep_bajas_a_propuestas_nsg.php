<?php
class ci_rep_bajas_a_propuestas_nsg extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		$datos = guarani::co('co_inscripciones')->get_bajas_de_alumnos($filtro);
		return $datos;
	}
}
?>