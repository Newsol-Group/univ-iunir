<?php
/**
 * 
 * 
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 *
 */
class cn_proc_prop_preinscripcion_masiva extends cn_procesos_bk
{	
	function etiquetas($dep=null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('adm_bases_preinscripcion');
		} else {
			parent::etiquetas($dep);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Listados / Información
	//-----------------------------------------------------------------------------------

	function get_listado($filtro)
	{
		return toba::consulta_php('co_preinscripciones')->get_fuentes_datos_preinscripcion($filtro);
	}

	/**
	 * Obtiene el listado de propuestas para las cuales hay preinscripciones 
	 * pendientes y vigentes 
	 * 
	 * @return array
	 */
	function get_listado_propuestas()
	{
		return toba::consulta_php('co_preinscripciones')->get_listado_propuestas_insc_masiva();
	}
	
	//-----------------------------------------------------------------------------------
	//---- Proceso
	//-----------------------------------------------------------------------------------

	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_prop_preinscripcion_masiva::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}
}
?>