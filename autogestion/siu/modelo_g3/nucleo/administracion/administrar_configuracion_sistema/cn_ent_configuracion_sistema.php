<?php
/**
 *
 * @package		Administración
 * @subpackage 	Administrar Configuración del Sistema
 * 
 */
class cn_ent_configuracion_sistema extends cn_entidad
{
	
	function set_datos_plataformas($datos)
	{
		$this->tabla('plataformas')->set($datos);
	}
	
	function get_datos_plataformas()
	{
		return $this->tabla('plataformas')->get();
	}
	
	function set_datos_roles_docentes($datos)
	{
		$this->tabla('roles_docentes')->procesar_filas($datos);
	}
	
	function get_datos_roles_docentes()
	{
		return $this->tabla('roles_docentes')->get_filas();
	}
	
	function set_datos_responsables_academicas($datos)
	{
		$this->validar_ras_ya_asignadas_otras_plataformas($datos);
		$ras = $this->tabla('responsables_academicas')->get_filas();
		$ras_aplanadas = aplanar_matriz($ras, 'responsable_academica');
		
		$ras_a_borrar = array_diff($ras_aplanadas, $datos);
		$this->validar_ras_con_cursos_asignados($ras_a_borrar);
		
		$ras_a_insertar = array_diff($datos, $ras_aplanadas);

		$ras_asociativas = rs_convertir_asociativo($ras, array('responsable_academica'), apex_datos_clave_fila);
		foreach ($ras_a_borrar as $ra) {
			$this->tabla('responsables_academicas')->eliminar_fila($ras_asociativas[$ra]);
		}

		foreach ($ras_a_insertar as $ra) {
			$this->tabla('responsables_academicas')->nueva_fila(array('responsable_academica' => $ra));
		}
	}
	
	function get_datos_responsables_academicas()
	{
		return $this->tabla('responsables_academicas')->get_filas();
	}
	
	/**
	 * Valida que las Responsables Académicas elegidas no estén ya asignadas a otra plataforma
	*/
	function validar_ras_ya_asignadas_otras_plataformas($ras)
	{
		$datos = $this->get_datos_plataformas();
		// Si se trata de una edición tomo el Id de plataforma, si es nueva le pongo 0 
		$plataforma = (isset($datos['plataforma']))? $datos['plataforma'] : 0;
		
		$ras_otras_plataformas = toba::consulta_php('co_configuracion')->get_ras_otras_plataformas($plataforma, $ras);
		// Si se seleccionaron RAs ya asignadas a otras plataformas
		if (!empty($ras_otras_plataformas)) {
			$nombres_ras = array_column($ras_otras_plataformas, 'descr');
			$nombres_ras = implode(', ', $nombres_ras);
			throw guarani::error(guarani_mensajes::get('800_ras_ya_asignadas_plataformas', array($nombres_ras)));
		}		
	}
	
	function validar_ras_con_cursos_asignados($ras_a_borrar)
	{
		// Si no hay Responsables Académicas a borrar
		if (empty($ras_a_borrar)) {
			return false;
		}
		
		$ras_cant_cursos_asociados = toba::consulta_php('co_configuracion')->get_ras_cant_cursos_asociados($ras_a_borrar);
		
		$ras_con_cursos_asociados = array_filter($ras_cant_cursos_asociados, function ($ra) {
			return $ra['cant_cursos'] > 0;
		});
		
		// Si se quieren borrar Responsables Académicas con cursos asociados 
		if (!empty($ras_con_cursos_asociados)) {
			$nombres_ras = array_column($ras_con_cursos_asociados, 'ra_nombre');
			$nombres_ras = implode(', ', $nombres_ras);
			throw guarani::error(guarani_mensajes::get('800_ras_con_cursos_asociados', array($nombres_ras)));
		}
	}
	
	/**
	 * Implementa la carga de una entidad
	 * @return mixed Clave de la entidad sincronizada	 
	 */
	function sincronizar()
	{
		$this->validar_datos();
		parent::sincronizar();
	}
	
	/**
	 * Valida que se completen los datos en todas las solapas
	 */
	function validar_datos($datos = [])
	{
		$datos_plataformas = $this->get_datos_plataformas();
		
		// Me fijo si faltan datos de otras solapas
		$faltan_datos = (!isset($datos_plataformas['rol_alumno']) || !isset($datos_plataformas['rol_alumno_curso']) || !isset($datos_plataformas['categoria_curso_default']));
		
		// Si faltas datos
		if ($faltan_datos) {
			throw guarani::error(guarani_mensajes::get('800_debe_completar_solapas_todas'));
		}
	}	
	
	/**
	 * Verifica si la plataforma tiene datos asociados (usuarios, cursos, etc.)
	 */
	function tiene_datos_asociados()
	{
		$tiene_datos_asociados = false;
		$datos_plataforma = $this->get_datos_plataformas();
		
		// Si se trata de una edición
		if (isset($datos_plataforma['plataforma'])) {
			
			$plataforma = $datos_plataforma['plataforma'];
			$tiene_datos_asociados = toba::consulta_php('co_configuracion')->plataforma_tiene_datos_asociados($plataforma);
			
		}
		
		return $tiene_datos_asociados;
	}
}
?>