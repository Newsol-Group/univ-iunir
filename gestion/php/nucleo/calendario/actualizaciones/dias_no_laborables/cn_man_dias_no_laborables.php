<?php
/**
 *	
 *
 * @package Calendario
 * @subpackage Días No Laborables
 */
class cn_man_dias_no_laborables extends cn_manejador_entidad
{
	
	function get_indice_instancia()
	{
		return 'dia_no_laborable';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_dias_no_laborables')->get_dias_no_laborables_perfil_de_datos($filtro);
	}
    
    function guardar()
    {
        //Verificar que el dia no este cargado.
        $datos = $this->entidad()->get_datos();
        $dia_no_laborable = isset($datos['dia_no_laborable']) ? $datos['dia_no_laborable'] : null;
        if (isset($datos['ubicacion'])) {
            if(toba::consulta_php('co_dias_no_laborables')->es_dia_no_laborable_ubicacion($datos['fecha'], $datos['ubicacion'], $dia_no_laborable)) {
                throw new toba_error('El día ya esta cargado como no laborable');
            }
        } elseif(toba::consulta_php('co_dias_no_laborables')->es_dia_no_laborable($datos['fecha'], $dia_no_laborable)) {
            throw new toba_error('El día ya esta cargado como no laborable');
        }
        parent::guardar();
    }
	
}

?>