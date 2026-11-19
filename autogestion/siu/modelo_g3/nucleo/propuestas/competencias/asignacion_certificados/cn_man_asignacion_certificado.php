<?php
class cn_man_asignacion_certificado extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'asignacion_certificados';
	}
	
	function get_listado($filtro)
	{
		$listado = toba::consulta_php('co_certificados')->get_listado($filtro);		
		$listado = rs_ordenar_por_columnas($listado, array('nombre', 'codigo', 'certificado_tipo', 'titulo_nivel'));
		return $listado;
	}

}

?>