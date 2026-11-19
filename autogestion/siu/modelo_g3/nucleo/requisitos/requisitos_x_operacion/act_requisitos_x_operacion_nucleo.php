<?php
/**
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Operación
 */
class act_requisitos_x_operacion_nucleo
{
	function actualizar_requisito_x_operacion($conf, $requisito_accion)
	{
		$operacion = toba::db()->quote($conf['operacion']);
		$interfaz = toba::db()->quote($conf['interfaz']);
		$requisito_accion = toba::db()->quote($requisito_accion);
		$actua_como = toba::db()->quote($conf['actua_como']);
		$activo = toba::db()->quote($conf['activo']);
		$sql = "UPDATE	sga_requisitos_conf_x_oper
				SET		actua_como = $actua_como,
						activo = $activo
				WHERE	operacion = $operacion AND
						interfaz = $interfaz AND
						requisito_accion = $requisito_accion
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó la configuración de requisitos por operación con éxito. ************');
	}
}
?>