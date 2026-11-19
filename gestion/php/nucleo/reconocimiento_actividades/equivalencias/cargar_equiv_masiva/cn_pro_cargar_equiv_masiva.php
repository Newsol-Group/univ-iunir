<?php
/**
 *
 *
 * @package     Reconocimiento de Actividades
 * @subpackage  Equivalencias
 */
class cn_pro_cargar_equiv_masiva extends cn_procesos_bk
{	
	protected $s__filtro;

	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_cargar_equiv_masiva::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}
	
	function set_filtro($filtro)
	{
		$this->s__filtro = $filtro;
	}
	
	function etiquetas($dep=null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_propuestas');
		} else {
			parent::etiquetas($dep);
		}
	}
}
?>