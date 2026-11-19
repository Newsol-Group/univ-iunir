<?php
class cn_man_excepciones_vigencia_regularidad extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'exceptuar_vigencia_regularidad';
	}
	
	function get_listado($filtro)
	{
		return guarani::co('co_elementos')->get_elementos_excepcion_regularidad($filtro);
	}
	
	function get_planes_excepcion()
	{
		$excepcion = $this->entidad()->get_datos_excepcion();
		return guarani::co('co_planes')->get_planes_activos_vigentes_elemento($excepcion['elemento']);
	}
	
	function get_valor_parametros()
	{
		$where = guarani_sql::armar_condicion_in(array('cur_cant_meses_vigencia_regularidad', 'exa_cant_meses_vigencia_regularidad_revalida'), 'par_parametros_sistema', 'parametro');
		$valores = guarani::co('co_parametros')->get_listado($where);
		$aux = array();
		/*
		 * Armo el array con la clave 'nombre_parametro' para tener los valores 
		 * desde - hasta de los mismos
		 */
		foreach(array_keys($valores) as $key) {
			$aux[$valores[$key]['parametro']] = array();
			$aux[$valores[$key]['parametro']]['valor_desde'] = $valores[$key]['valor_desde'];
			$aux[$valores[$key]['parametro']]['valor_hasta'] = $valores[$key]['valor_hasta'];
		}
		return $aux;		
	}
	
	function get_elementos_planes_exeptuados()
	{
		/*
		 * Traigo los planes que están siendo usados en otra exepción
		 */
		$excepcion = $this->entidad()->get_datos_excepcion();		
		$where = ' sga_excep_regularidad.elemento = '. $excepcion['elemento'];
		//Si está seteado me indica que esta modificando
		if (isset($excepcion['excep_regularidad'])) {
			$where .= ' AND sga_excep_regularidad_planes.excep_regularidad <> '.$excepcion['excep_regularidad'];
		}		
		$usados = guarani::co('co_elementos')->get_planes_excepcion_regularidad($where);
		return aplanar_matriz($usados,'plan');
	}
}
?>