<?php
class regla_no_existe_acta_examen_abierta_cerrada extends regla
{
	function validar()
	{
		$llamado_mesa = $this->get_parametro('llamado_mesa');

		$origen_examen  = toba::db()->quote(acta::origen_examen);
		$estado_abierta = toba::db()->quote(acta::estado_abierta);
		$estado_cerrada = toba::db()->quote(acta::estado_cerrada);
		
		// Controla que no exista un acta de examen abierta o cerrada en la mesa de examen y llamado
		// Si existe falla el control.
		$sql = "SELECT COUNT(1) cant_acta_abierta_cerrada
		          FROM sga_actas
				 WHERE sga_actas.llamado_mesa = $llamado_mesa
				   AND sga_actas.origen = $origen_examen
				   AND sga_actas.estado IN ($estado_abierta, $estado_cerrada)
				";
		$rs  = guarani_db::consultar_fila($sql, null, false);
		
		// Falla el control si existe al menos un acta en estado abierta o cerrada	
		$resultado = $this->resultado_final($rs['cant_acta_abierta_cerrada'] == 0);
		
		// La regla no tiene parametros en el mensaje. Devuelve directamente el resultado.
		return $resultado;
	}
}
?>