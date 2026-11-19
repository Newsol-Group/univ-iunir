<?php
/**
 *
 * 
 * @package		Cursadas
 * @subpackage	Inscripciones
 * 
 */
class cn_proc_proc_insc_prioridades extends cn_procesos_bk
{
	protected $etiquetas;

	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_proc_insc_prioridades::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_elementos');
		} else {
			parent::etiquetas($dep);
		}
	}

	//--------------------------------------------------------------------------

	function get_listado($periodo_lectivo, $filtro)
	{
		return toba::consulta_php('co_elementos')->get_actividades_insc_prioridad($periodo_lectivo, $filtro);
	}

	function get_inscripciones_a_procesar($periodo_lectivo, $actividades, $criterios_prioridad = '', $cont_criterios = 0)
	{
		return toba::consulta_php('co_inscripciones_cursadas')->get_actividades_insc_prioridad($periodo_lectivo, $actividades, $criterios_prioridad, $cont_criterios);
	}

}
?>