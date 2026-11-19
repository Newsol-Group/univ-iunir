<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Actas
 * 
 */
class cn_pro_generacion_actas_cursada extends cn_procesos_bk
{
	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_generacion_actas_cursada::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function etiquetas($concepto)
	{
		if (!isset($this->etiquetas[$concepto])) {
			$this->etiquetas[$concepto] = guarani_etiquetas::get_concepto($concepto);
		}
		return $this->etiquetas[$concepto];
	}

	function get_listado($origen, $filtro = '')
	{
		$filtro_activas = "sga_comisiones.estado = '" . comunes::estado_activo . "'";
		$filtro = empty($filtro) ? $filtro_activas : $filtro . ' AND ' . $filtro_activas;
		return toba::consulta_php('co_comisiones')->get_comisiones_con_inscriptos_sin_acta_generada($origen, $filtro);
	}

}
?>