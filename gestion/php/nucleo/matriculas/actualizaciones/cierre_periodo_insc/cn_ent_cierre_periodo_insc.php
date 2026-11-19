<?php
/**
 *
 *
 * @package		Matrícula
 * @subpackage 	Actualizaciones
 * 
 */
class cn_ent_cierre_periodo_insc extends cn_procesos_bk
{
	protected $etiquetas;

	function cargar_atributos_parametros_propuesta($propuestas)
	{
		$operacion = guarani::get_id_operacion();
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);
	}
	
	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_cierre_periodo_insc::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function etiquetas($tabla)
	{
		if (!isset($this->etiquetas[$tabla])) {
			$this->etiquetas[$tabla] = guarani_etiquetas::get_tabla($tabla);
		}
		return $this->etiquetas[$tabla];
	}

}
?>