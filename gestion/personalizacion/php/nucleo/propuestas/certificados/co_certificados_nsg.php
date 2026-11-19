<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Certificados
 */
class co_certificados_nsg extends co_certificados
{
    //Newsol (NUEVO) - Devuelve el título araucano	
    function get_int_araucano_titulos()
    {
		$sql = "SELECT ta.titulo_araucano as valor,
						ta.nombre || ' (' || ta.titulo_araucano || ')' as descr
  				FROM int_arau_titulos as ta
				ORDER BY 2
				";			

		return guarani_db::consultar($sql);
    }

}
?>