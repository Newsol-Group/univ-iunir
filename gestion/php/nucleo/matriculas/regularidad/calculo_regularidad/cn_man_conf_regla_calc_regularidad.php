<?php
/**
 *
 *
 * @package		Matricula
 * @subpackage	Regularidad
 * 
 */
class cn_man_conf_regla_calc_regularidad extends cn_manejador_entidad
{
    function get_indice_instancia() 
    { 
        return 'regla_calculo_regularidad'; 
    }
	
	function get_listado($filtro)
    {
		return toba::consulta_php('co_readmisiones')->get_causas_perdida_regularidad_cant_propuestas($filtro);
    }
	
	function get_ubicaciones_propuestas($filtro='')
    {		
		
		$filtro_propuestas_controlar_regularidad = "sga_propuestas_tipos.control_regularidad = '" . comunes::si . "'";
		$filtro = empty($filtro) ? $filtro_propuestas_controlar_regularidad : $filtro . ' AND ' . $filtro_propuestas_controlar_regularidad;
		
		return toba::consulta_php('co_propuestas')->get_ubicaciones_propuestas($filtro);
    }
}
?>