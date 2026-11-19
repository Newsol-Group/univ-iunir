<?php
class cn_resultados_x_area extends guarani_pers_cn
{
	protected $s__filtros = array();
	protected $s__filtro_actual;
	
	function get_listado($filtro = false)
	{
		if($filtro && $this->s__filtro_actual != $filtro)
		{
			if(!in_array($filtro, $this->s__filtros))
			{
				$datos = toba::consulta_php("co_competencias")->get_resultados_area("ambito = {$filtro['ambito']} and campo_disciplinar = {$filtro['campo_disciplinar']}");
				$this->dep('rxa')->anexar_datos($datos);
				$this->s__filtros[]=$filtro;
			}
			$this->s__filtro_actual = $filtro;
		}
		$datos = $this->dep('rxa')->get_filas($this->s__filtro_actual);
		return $datos;
	}
	
	function procesar_datos($datos, $eliminar = false)
	{
		foreach($datos as $k=>$dat)
		{
			if($eliminar && $dat['eliminar'] == 1)
			{
				$datos[$k]= array("apex_ei_analisis_fila" => "B");
			}
			else
			{
				$datos[$k] = array_merge(array("resultado"=>$dat['resultado'], 'orden_listado'=>$dat['orden_listado'], "apex_ei_analisis_fila" => "M"), $this->s__filtro_actual);
			}
		}
		$this->dep('rxa')->procesar_filas($datos);
	}
	
	function sincronizar()
	{
		$this->dep('rxa')->sincronizar();
	}
	
	function asignar($resultado)
	{
		
		$this->dep('rxa')->nueva_fila($resultado);
	}
	
	function next_orden()
	{
		return guarani_arreglos::count($this->get_listado())+1;
	}
}

?>