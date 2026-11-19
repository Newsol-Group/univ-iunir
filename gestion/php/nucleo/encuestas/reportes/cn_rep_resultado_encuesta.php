<?php
class cn_rep_resultado_encuesta extends guarani_pers_cn
{
	protected $s__encuesta;
	protected $s__bloques;
			
		
	function get_listado($filtro)
	{
		return guarani::co('co_encuestas')->get_reporte_encuestas_actuacion_docente($filtro);
	}
	
	function cargar_encuesta($id, $elemento_externo)
	{
		$this->s__encuesta = guarani::co('co_encuestas')->get_id($id, $elemento_externo);		
		$this->s__bloques = encuesta::get_bloques($this->s__encuesta['kolla_id_encuesta'], $this->s__encuesta['kolla_unidad_gestion']);		
	}
	
	function get_encuesta()
	{
		return $this->s__encuesta;
	}
	
	function get_bloques()
	{
		return $this->s__bloques['detalle'];
	}
	
	function get_preguntas($id_encuesta, $id_bloque, $unidad_gestion)
	{
		return encuesta::get_preguntas($id_encuesta, $id_bloque, $unidad_gestion);
	}
	
	function get_respuestas($id_habilitacion, $id_formulario, $id_encuesta, $id_elemento, $id_bloque, $id_pregunta, $pregunta_orden, $unidad_gestion)
	{
		$repuestas = encuesta::get_respuesta($id_habilitacion, $id_formulario, $id_encuesta, $id_elemento, $id_bloque, $id_pregunta, $pregunta_orden, $unidad_gestion);		
		return $repuestas['detalle']['0']['respuestas'];		
	}
	
	function reset_variables()
	{
		unset($this->s__encuesta);
		unset($this->s__bloques);
	}
	
}

?>