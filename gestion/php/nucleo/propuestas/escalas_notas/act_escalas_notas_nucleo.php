<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 *
 *
 * @package Propuestas
 * @subpackage EscalaDeNotas
 */
class act_escalas_notas_nucleo
{
	/**
	 * Elimina las notas de una escala de notas dada. Usado para la eliminación de escalas contínuas.
	 */
	function eliminar_notas($escala_nota)
	{
		$escala_nota = toba::db()->quote($escala_nota);
		$sql = "DELETE FROM sga_escalas_notas_det WHERE escala_nota = $escala_nota;";
		guarani_db::ejecutar($sql, false, true);
	}
	
	/**
	 * Genera las notas correspondientes a una escala de notas contínua dada.
	 */
	function generar_notas($escala_nota)
	{
		$escala_nota = toba::db()->quote($escala_nota);
		$sql = "SELECT f_generar_escala_notas_continua FROM f_generar_escala_notas_continua($escala_nota);";
		guarani_db::ejecutar($sql, true, true);
	}

	function copiar_escala($escala_nota)
	{
		$escala_nota = toba::db()->quote($escala_nota);
		$sql = "SELECT f_copiar_escala_notas($escala_nota);";
		return guarani_db::ejecutar($sql, true);
	}
}
?>