<?php
/**
 *
 *
 * @package Complementos
 * @subpackage Complementos
 */
class co_complementos
{
	/**
        Obtiene los complemento según el filtro especificado
    */
	function get_listado($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}
		$sql = "SELECT 	mdp_complementos.id_complemento,
						mdp_complementos.nombre,
						mdp_complementos.ubicacion,
						mdp_complementos.clase_implementacion
				FROM	mdp_complementos
						$where
						;";
						
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene los datos básicos del complemento especificado
    */
	function get_datos_basicos($id)
	{
		$id = toba::db()->quote($id);
		$sql = "SELECT 	mdp_complementos.nombre,
						mdp_complementos.ubicacion,
						mdp_complementos.clase_implementacion
				FROM	mdp_complementos
				WHERE 	mdp_complementos.id_complemento = $id
						;";
						
		return guarani_db::consultar_fila($sql);
	}
	
	/**
        Obtiene los complementos de una persona
    */
	function get_complementos_persona($persona)
	{
		$persona = toba::db()->quote($persona);
		$sql = "SELECT 	mdp_complementos.id_complemento,
						mdp_complementos.nombre,
						mdp_complementos.ubicacion,
						mdp_complementos.clase_implementacion
				FROM	mdp_complementos,
						mdp_complementos_personas 
				WHERE 	mdp_complementos_personas.persona = $persona 
				AND 	mdp_complementos.id_complemento = mdp_complementos_personas.id_complemento
						;";
						
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene los complementos de una persona
    */
	function get_complementos_usuario_activo()
	{
		$persona = persona::get_id();
		return $this->get_complementos_persona($persona);
	}
	
}
?>