<?php
/**
 *
 *
 * @package 	Comunicación
 * @subpackage
 * 
 */
class co_grupos_destinatarios
{
	function get_listado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where;
		}

		$sql = "SELECT 		men_grupos.grupo,
							men_grupos.nombre,
							men_grupos.descripcion
				FROM		men_grupos
							JOIN men_grupos_personas ON men_grupos.grupo = men_grupos_personas.grupo
							JOIN vw_perfil_de_datos_personas ON men_grupos_personas.persona = vw_perfil_de_datos_personas.persona
							$where
				GROUP BY	men_grupos.grupo
				ORDER BY	men_grupos.nombre
		";

		return guarani_db::consultar($sql);
	}
}
?>