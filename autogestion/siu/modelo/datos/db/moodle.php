<?php
namespace siu\modelo\datos\db;

class moodle 
{
	
	/**
	 * parametros: comision
	 * no_quote: comision
	 * no_log: token
	 * cache: memoria
	 */
	public function get_plataformas_by_comision($parametros) 
	{
		return \toba::consulta_php('co_comisiones')->get_plataformas_by_comision($parametros['comision']);
	}
	
	/**
	 * parametros: plataforma
	 * no_quote: plataforma
	 * no_log: token
	 * cache: memoria
	 */
	public function get_plataforma_educacion_virtual($parametros) 
	{
		return \toba::consulta_php('co_configuracion')->get_plataforma_educacion_virtual($parametros['plataforma']);
	}
	
	/**
	 * parametros: comision
	 * no_quote: comision
	 * no_log: plataforma_token
	 * cache: memoria
	 */
	public function get_curso_virtual_by_comision($parametros) 
	{
		return \toba::consulta_php('co_comisiones')->get_curso_virtual_by_comision($parametros['comision']);
	}
	
	/**
	 * parametros: llamado_mesa
	 * no_quote: llamado_mesa
	 * no_log: plataforma_token
	 * cache: memoria
	 */
	public function get_curso_virtual_by_llamado_mesa($parametros) 
	{
		return \toba::consulta_php('co_mesas_de_examen')->get_curso_virtual_llamado_mesa($parametros['llamado_mesa']);
	}
	
	/**
	 * parametros: comision, plataforma
	 * no_quote: comision, plataforma
	 * cache: memoria
	 */
	public function get_docentes_comision_moodle($parametros) 
	{
		return \toba::consulta_php('co_comisiones')->get_docentes_comision_moodle($parametros['comision'], $parametros['plataforma']);
	}
	
	/**
	 * parametros: plataforma
	 * no_quote: plataforma
	 * cache: memoria
	 */
	public function get_roles_docentes_by_plataforma($parametros) 
	{
		return \toba::consulta_php('co_configuracion')->get_roles_docentes_by_plataforma($parametros['plataforma']);
	}
	
	/**
	 * parametros: curso
	 * no_quote: curso
	 * cache: memoria
	 */
	public function get_docentes_curso_moodle($parametros) 
	{
		return \toba::consulta_php('co_comisiones')->get_docentes_curso_moodle($parametros['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
	}
	
	/**
	 * parametros: comision, plataforma
	 * param_null: plataforma
	 * no_quote: comision, plataforma
	 * cache: memoria
	 */
	public function get_alumnos_inscriptos_a_comision_moodle($parametros) 
	{
		return \toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_comision_moodle($parametros['comision'], '', $parametros['plataforma']);
	}
	
	/**
	 * parametros: comision, plataforma
	 * param_null: plataforma
	 * no_quote: comision, plataforma
	 * cache: memoria
	 */
	public function get_alumnos_eliminados_a_comision_moodle($parametros) 
	{
		return \toba::consulta_php('co_comisiones')->get_alumnos_eliminados_a_comision_moodle($parametros['comision'], 'alumnos_eliminados.usuario_moodle IS NOT NULL', $parametros['plataforma']);
	}
	
	/**
	 * parametros: curso
	 * no_quote: curso
	 * cache: memoria
	 */
	public function get_alumnos_inscriptos_a_curso_moodle($parametros) 
	{
		return \toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_curso_moodle($parametros['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
	}
	
	/**
	 * parametros: curso, plataforma
	 * no_quote: curso, plataforma
	 * cache: memoria
	 */
	public function get_datos_curso_externo($parametros) 
	{
		return \toba::consulta_php('co_configuracion')->get_datos_curso_externo($parametros['curso'], $parametros['plataforma']);
	}
	
	/**
	 * parametros: curso
	 * no_quote: curso
	 * cache: no
	 */
	public function eliminar_curso($parametros) 
	{
		\guarani::act('act_plataformas_virtuales')->delete_curso_pv($parametros['curso']);
	}
	
	/**
	 * parametros: comision
	 * no_quote: comision
	 * cache: memoria
	 */
	public function get_datos_comision($parametros) 
	{	
		return current(\toba::consulta_php('co_comisiones')->get_listado_comisiones_curso_virtual("vw_comisiones.comision = {$parametros['comision']}"));
	}
	
}