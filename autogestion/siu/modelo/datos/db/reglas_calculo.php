<?php

namespace siu\modelo\datos\db;

class reglas_calculo 
{
	
	/**
	 * parametros: filtro
	 * param_null: filtro
	 * no_quote: filtro
	 * cache: memoria
	 */
	public function get_reglas_calculo($parametros) 
	{	
		return \toba::consulta_php('co_reglas_calculo')->get_listado($parametros['filtro']);
	}
	
}