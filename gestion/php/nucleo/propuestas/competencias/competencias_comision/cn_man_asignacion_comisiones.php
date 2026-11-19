<?php
class cn_man_asignacion_comisiones extends cn_manejador_entidad
{	
	function get_indice_instancia()
	{
		return "competencias_comision";
	}
	
	function get_elemento()
	{
		$comision = $this->entidad()->get_elemento();
		$datos = toba::consulta_php('co_comisiones')->get_listado_comisiones('sga_comisiones.comision = '.$comision['comision']);
		if($datos) { 
			return array('nombre'=>$datos[0]['actividad'], 'codigo'=>$datos[0]['codigo']);
		}
		return array(); 
	}
	
	function get_listado($filtro)
	{
		$listado = toba::consulta_php('co_elementos')->get_listado(elemento::tipo_actividad, null, $filtro);
		$listado = rs_ordenar_por_columnas($listado, array('subtipo_desc', 'nombre', 'codigo'));
		return $listado;
	}
}

?>