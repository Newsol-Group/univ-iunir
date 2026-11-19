<?php
/**
 *
 *
 * @package 	Tesis
 * @subpackage	-
 * 
 */
class cn_man_tesis extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'tesis';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_tesis')->get_listado($filtro);
	}

	function get_listado_alumnos($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_listado($filtro);
	}

	function get_listado_tutores($filtro, $filtro_docentes)
	{
		return static::get_personas_alumnos_o_docentes($filtro, $filtro_docentes);
	}

	function get_listado_evaluadores($filtro, $filtro_docentes)
	{
		return static::get_personas_alumnos_o_docentes($filtro, $filtro_docentes);
	}

	static function get_personas_alumnos_o_docentes($filtro, $filtro_docentes)
	{
		$personas = toba::consulta_php('co_personas')->get_personas_alumnos_o_docentes($filtro, $filtro_docentes);

		foreach ($personas as $clave => $valor) {

			// En caso de que el tutor sea docente agrego su legajo
			$legajo = toba::consulta_php('co_personas')->get_legajo_persona_docente($valor['persona']);
			$personas[$clave]['legajo'] = empty($legajo) ? null : $legajo['legajo'];
		}

		return $personas;
	}

}
?>