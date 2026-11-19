<?php
/**
 *
 * 
 * @package		Cursadas
 * @subpackage	Inscripciones
 * 
 */
class cn_pro_insc_automat_ingresantes extends cn_procesos_bk
{
	function cargar_atributos_parametros($seleccion)
	{
		$operacion = guarani::get_id_operacion();

		$propuestas = aplanar_matriz($seleccion, 'propuesta');
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);
		
		// Si a partir de la selección se puede determinar el valor de los parámetros de la
		// operación para todos los alumnos involucrados, se deja, si no se resetea la selección
		// y que el procesamiento individual obtenga el valor específico.
		$hay_conflicto = guarani::parametros()->hay_conflictos_en_parametros_operacion($operacion);
		if ($hay_conflicto) {
			$this->resetear_atributos_parametros();
		}
	}
	
	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_inscripcion_automatica_ingresantes::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_insc_cursadas');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro = null)
	{
		$where = "sga_propuestas.estado = '" . comunes::estado_activo . "'";
		if ($filtro) {
			$where .= ' AND ' . $filtro;
		}
		return toba::consulta_php('co_propuestas')->get_listado($where, 'propuesta_nombre');
	}

}
?>