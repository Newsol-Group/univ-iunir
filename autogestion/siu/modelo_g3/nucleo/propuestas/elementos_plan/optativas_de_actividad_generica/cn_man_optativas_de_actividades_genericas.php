<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class cn_man_optativas_de_actividades_genericas extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
	 	return 'optativas_de_generica';
	}

	function get_listado($filtro)
	{
		// Se debe verificar que las actividades genéricas estén en algún plan de estudios.
		$where = "sga_elementos.elemento IN (	SELECT	sga_elementos_revision.elemento
												FROM	sga_elementos_plan,
														sga_elementos_revision
												WHERE	sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision
											)";
		if ($filtro) {
			$where .= ' AND ' . $filtro;
		}
		return toba::consulta_php('co_elementos')->get_materias_genericas($where);
	}
	
	function seleccionar_optativa($seleccion, $optativa_confirmada)
	{
		$this->entidad()->seleccionar_componente_optativa($seleccion, $optativa_confirmada);		
	}
	
	function get_datos_optativa_planes_versiones()
	{
		// El cursor ya está posicionado en una optativa componente de la genérica, con sus configuraciones para cada plan versión.	
		return $this->entidad()->get_datos_optativas();
	}
	
	function cargar_optativa_plan_version($optativa, $plan_version)
	{
		$this->entidad()->seleccionar_optativa_plan_version($optativa, $plan_version);
	}
	
	function hay_plan_version_seleccionado()
	{
		return $this->entidad()->hay_optativa_plan_version_seleccionado();	
	}
	
	function deseleccionar_plan_version()
	{
		$this->entidad()->deseleccionar_optativa_plan_version();	
	}
	
	function confirmar_optativas($materia_generica)
	{
		try {
			/**
		 	* Se invoca a la función que copia los datos de las optativas asociadas a la materia genérica, de las tablas
		 	* temporales a las tablas definitivas.
		 	*/
			guarani::act('act_planes')->confirmar_optativas_de_generica($materia_generica);
			toba::notificacion()->agregar($this->get_mensaje('800PRO_confirmar_ok'), 'info');
		} catch (guarani_error $e)	{
			throw $e;
		} 
	}
}

?>