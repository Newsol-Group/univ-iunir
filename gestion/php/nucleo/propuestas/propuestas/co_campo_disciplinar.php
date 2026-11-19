<?php
/**
 * @package Propuestas
 * @subpackage Propuestas
 */
class co_campo_disciplinar
{
	/* --------------------------------------------------------------------------------------
	/* ----  Catálogo de campos disciplinares -----------------------------------------------
	/* --------------------------------------------------------------------------------------

	/**
     * Retorna la lista de campos disciplinares 
     */
    function get_campos_disciplinares()
    {
		$sql = 'SELECT 		sga_campo_disciplinar.campo_disciplinar	AS valor,
							sga_campo_disciplinar.nombre			AS descr
				FROM 		sga_campo_disciplinar
				ORDER BY	sga_campo_disciplinar.nombre
			   ';
		
		return guarani_db::consultar($sql);
    }
}
?>