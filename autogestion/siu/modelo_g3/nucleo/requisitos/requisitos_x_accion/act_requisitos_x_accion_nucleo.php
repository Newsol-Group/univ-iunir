<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Acción
 * 
 */
class act_requisitos_x_accion_nucleo
{
	/**
	 * Inserta un nuevo registro en la tabla de aplanado.
	 * 
	 */
	function insertar_aplanado($requisito_accion, $plan_version)
	{
		$sql = "INSERT INTO	sga_requisitos_aplanado (
							requisito_accion,
							plan_version)
				VALUES (
							$requisito_accion,
							$plan_version)
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el registro de aplanado con éxito. ************');
	}

	/**
	 * Elimina de la tabla de aplanado todos los registros correspondientes a un requisito por acción dado.
	 * 
	 */
	function eliminar_aplanado($requisito_accion)
	{
		$sql = "DELETE
				FROM	sga_requisitos_aplanado
				WHERE	requisito_accion = $requisito_accion
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros de aplanado con éxito. ************');
	}
}

?>