<?php
/**
 * 
 * @package Encuestas
 */
class cn_ent_encuesta extends cn_entidad
{

	function encuesta_asociada_a_grupo()
	{
		$encuesta = $this->tabla('encuestas')->get_columna('encuesta');
		return toba::consulta_php('co_encuestas')->encuesta_asociada_a_grupo($encuesta);
	}

	//------------------------------------------------------------------------
	//-- Encuesta
	//------------------------------------------------------------------------

	function get_datos($tabla = null)
	{
		return $this->tabla('encuestas')->get();
	}

	function set_datos($datos, $tabla = null, $validar = true)
	{
		$this->tabla('encuestas')->set($datos);
	}

}
?>