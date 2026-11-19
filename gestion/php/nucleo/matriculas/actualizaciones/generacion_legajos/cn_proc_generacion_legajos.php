<?php
/**
 *
 *
 * @package		Matricula
 * @subpackage	Actualizaciones
 * 
 */
class cn_proc_generacion_legajos extends cn_procesos_bk
{
	protected $etiquetas;

	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_generacion_legajos::nombre);
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
	
	//--------------------------------------------------------------------------
	//---- Atributos parámetros del sistema ------------------------------------
	//--------------------------------------------------------------------------
	
	function cargar_atributos_parametros_ras($ras)
	{
		$operacion = guarani::get_id_operacion();
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
	}
	
	function resetear_atributos_ras()
	{
		$this->resetear_atributos_parametros(parametro::nivel_ra);
	}
	
	function cargar_atributos_parametros_propuestas($propuestas)
	{
		$operacion = guarani::get_id_operacion();
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);
	}
	
	function resetear_atributos_propuestas()
	{
		$this->resetear_atributos_parametros(parametro::nivel_propuesta);
	}

	//--------------------------------------------------------------------------

	function get_clase_consulta()
	{
		return 'co_inscripciones_cursadas';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_inscripciones')->get_listado($filtro);
	}

	//---- VALIDACIONES --------------------------------------------------------
	
	/**
	 * Verifica no duplicidad de legajos ingresados manualmente y que se hayan cargado valores para todos las filas.
	 * Esta validación se realiza antes de actualizar la base de datos.
	 *
	 */
	function validar_entrada_legajos($datos, $unico_legajo)
	{
		$usados = array();
		foreach ($datos as $key => $dato) {
			if ($dato['requerido']) {
				if (in_array($dato['legajo'], $usados)) {
					throw guarani::error("El valor de Legajo '{$dato['legajo']}' se ha ingresado más de una vez.");
				}
				if (empty($dato['legajo']) && ($unico_legajo == comunes::no || empty($dato['legajo_otra_propuesta']))) {
					throw guarani::error("Quedan Legajos sin completar.");
				}
				$usados[] = $dato['legajo'];
			}
		}
	}

}
?>