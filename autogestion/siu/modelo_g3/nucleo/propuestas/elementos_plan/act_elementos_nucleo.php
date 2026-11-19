<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class act_elementos_nucleo
{
	
	/**
	 * Modifica en la tabla sga_elementos_no_comunes.
	 *
	 * @param int	$elemento			Elemento.
	 * @param int	$plan_origen		Plan origen.
	 * @param int[] $planes_destinos	Planes destino.
	 *
	 * @return bool true en caso de éxito, false en caso de error.
	 */
	function modificar_elementos_no_comunes($elemento, $plan_origen, array $planes_destino)
	{
		$ok = true;
		try {
			toba::db()->abrir_transaccion();
			$sql = "DELETE FROM sga_elementos_no_comunes WHERE elemento = $elemento AND plan_origen = $plan_origen";
			guarani_db::ejecutar($sql);
			if (!empty($planes_destino)) {
				$values = array();
				foreach ($planes_destino as $plan_destino) {
					$values[] = "({$elemento}, {$plan_origen}, {$plan_destino})";
				}
				$values_sql = implode(', ', $values);
				$sql = "INSERT INTO sga_elementos_no_comunes (elemento, plan_origen, plan_destino) VALUES {$values_sql}";
				guarani_db::ejecutar($sql);
			}
			toba::db()->cerrar_transaccion();
			toba::logger()->info('************ Se modificó en sga_elementos_no_comunes con éxito. ************');
		} catch (Exception $e) {
			toba::db()->abortar_transaccion();
			toba::logger()->error('************ Falló modificación en sga_elementos_no_comunes. ************');
			toba::logger()->error("Archivo: '".$e->getFile()."', Línea: ".$e->getLine().", Mensaje: ".$e->getMessage());
			$ok = false;
		}
		return $ok;
	}
}