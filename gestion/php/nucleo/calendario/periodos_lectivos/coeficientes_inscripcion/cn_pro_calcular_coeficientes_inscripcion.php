<?php
/**
 *
 * @package		Calendario
 * @subpackage	Períodos Lectivos
 */
class cn_pro_calcular_coeficientes_inscripcion extends cn_procesos_bk
{
	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_calcular_coeficientes_inscripcion::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function etiquetas($concepto=null)
	{
		if (is_null($concepto)) {
			return guarani_etiquetas::get_tabla('sga_propuestas');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_propuestas')->get_propuestas_ofertas($filtro);
	}
}
?>