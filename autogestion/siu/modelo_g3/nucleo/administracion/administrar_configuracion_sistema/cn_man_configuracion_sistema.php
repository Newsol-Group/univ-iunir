<?php
/**
 *
 * @package		Administración
 * @subpackage 	Administrar Configuración del Sistema
 * 
 */
class cn_man_configuracion_sistema extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'configuracion_sistema';
	}
			
	
	function get_listado($filtro)
	{
		
		$plataformas = toba::consulta_php('co_configuracion')->get_plataformas_educacion_virtual($filtro);
		
		foreach ($plataformas as $key => $plataforma) {
			
			$moodle_obj = new moodle($plataforma['host'], $plataforma['url'], $plataforma['token']);
			
			if ($moodle_obj->verificarConexion()) {
				$estado = '<i class="fa fa-circle" aria-hidden="true" style="color: green;"></i> Ok';
			} else {
				$estado = '<i class="fa fa-circle" aria-hidden="true" style="color: red;"></i> Error';
			}
			
			$plataformas[$key]['estado'] = $estado;
			$plataformas[$key]['activa_descr'] = ($plataforma['activa'])? comunes::si_desc : comunes::no_desc;
		}
		
		return $plataformas;
	}	
}