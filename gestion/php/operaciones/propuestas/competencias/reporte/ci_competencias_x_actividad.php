<?php
class ci_competencias_x_actividad extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'sga_resultados_aprendizaje';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_competencias')->get_competencias_x_actividad($filtro);
	}
}

?>