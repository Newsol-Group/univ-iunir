<?php

namespace siu\modelo\datos\db;

use \SIU\Chulupi\kernel;

class derecho_entidades
{
	/**
	 * parametros: persona, derecho, comisiones
	 * cache: no
	 * no_quote: comisiones
	 * filas: n
	 */
	function tiene_derecho_comisiones($parametros)
	{
		
		$derecho_para_comision = kernel::db()->quote(derechos_comision::derecho_para_comision);
		
		$sql = "SELECT --1 as tiene_derecho,
				sga_comisiones.comision
			   --gdu_derechos.derecho as derecho,
			   --gdu_derechos.nombre as nombre
		  FROM sga_comisiones, 
			   gdu_derechos_personas,
			   gdu_derechos
		 WHERE sga_comisiones.comision IN ({$parametros['comisiones']})
		   AND gdu_derechos_personas.persona = {$parametros['persona']}
		   AND gdu_derechos_personas.derecho = {$parametros['derecho']}
			   
		   AND gdu_derechos_personas.entidad = sga_comisiones.entidad
		   AND gdu_derechos.derecho = gdu_derechos_personas.derecho
		   AND gdu_derechos.derecho_para = $derecho_para_comision";
		
		$datos = kernel::db()->consultar($sql);
		$nueva = array();
		foreach ($datos as $id => $fila) {
			$nueva[$fila['comision']] = 1; //devuelvo ids que tienen derechos
		}
		return $nueva;
		
	}
	
	
	/**
	 * parametros: persona, derecho, llamados_mesas
	 * cache: no
	 * no_quote: llamados_mesas
	 * filas: n
	 */
	function tiene_derecho_llamados_mesas($parametros)
	{
		
		$derecho_para_mesa = kernel::db()->quote(derechos_mesa::derecho_para_mesa);
		
		$sql = "SELECT --1 as tiene_derecho
				sga_llamados_mesa.llamado_mesa 
		  FROM sga_llamados_mesa, 
			   gdu_derechos_personas,
			   gdu_derechos
		 WHERE sga_llamados_mesa.llamado_mesa IN ({$parametros['llamados_mesas']})
		   AND gdu_derechos_personas.persona = {$parametros['persona']}
		   AND gdu_derechos_personas.derecho = {$parametros['derecho']}
			   
		   AND gdu_derechos_personas.entidad = sga_llamados_mesa.entidad
		   AND gdu_derechos.derecho = gdu_derechos_personas.derecho
		   AND gdu_derechos.derecho_para = $derecho_para_mesa";
		
		$datos = kernel::db()->consultar($sql);
		$nueva = array();
		foreach ($datos as $id => $fila) {
			$nueva[$fila['llamado_mesa']] = 1; //devuelvo ids que tienen derechos
		}
		return $nueva;
	}
	
	/**
	 * parametros: persona, derecho, actas_fc
	 * cache: no
	 * no_quote: actas_fc
	 * filas: n
	 */
	function tiene_derecho_actas_fc($parametros)
	{
		
		$derecho_para_mesa = kernel::db()->quote(derechos_mesa::derecho_para_mesa);
		
		$sql = "  SELECT sga_llamados_mesa.llamado_mesa 
					FROM sga_llamados_mesa, 
						 gdu_derechos_personas,
						 gdu_derechos
				   WHERE sga_llamados_mesa.llamado_mesa IN ({$parametros['llamados_mesas']})
					 AND gdu_derechos_personas.persona = {$parametros['persona']}
					 AND gdu_derechos_personas.derecho = {$parametros['derecho']}

					 AND gdu_derechos_personas.entidad = sga_llamados_mesa.entidad
					 AND gdu_derechos.derecho = gdu_derechos_personas.derecho
					 AND gdu_derechos.derecho_para = $derecho_para_mesa";
		
		$datos = kernel::db()->consultar($sql);
		$nueva = array();
		foreach ($datos as $id => $fila) {
			$nueva[$fila['llamado_mesa']] = 1; //devuelvo ids que tienen derechos
		}
		return $nueva;
	}

}

?>
