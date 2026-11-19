<?php 
/**
 * Representa la definición de un Certificado sobre un plan_version
 *
 *
 * @package Propuestas
 * @subpackage Plan
 */
class cn_man_certificado_plan extends cn_man_condiciones
{
	protected $s__certificado_editable;
	protected $s__msj_certif_no_editable='';

	function get_indice_instancia()
	{
		return 'certificado_plan';
	}

	/**
		Retorna la lista de certificados
	*/
	function get_certificados($where='')
	{
		if ($where != '') $where .= ' AND ';
		$where .= " sga_certificados.estado = '".comunes::estado_activo."'" ;
		return toba::consulta_php('co_certificados')->get_listado($where);
	}
	
	/**
		Retorna la lista de certificados asignados
	*/
	function get_certificados_plan_version($where='')
	{
		if ($where != '') $where .= ' AND ';
		$where .= " sga_certificados.estado = '".comunes::estado_activo."'" ;
		return toba::consulta_php('co_certificados')->get_certificados_plan_version($this->get_id_plan_version(), $where);
	}

	/**
	 * Retorna los datos de los Certificados asignados al Plan Versión, que están asociados a $elemento
	 * @param integer $elemento id del elemento para el cual se recuperan los certificados asociados.
	 * @return array
	 */
	function certificados_asociados_a($elemento)
	{
		$plan_version = toba::db()->quote($this->get_id_plan_version());
		$incluir_modulos = 'true';
		$where = "$elemento IN (SELECT	*
								FROM	get_actividades_certificado(sga_certificados.certificado, $plan_version, $incluir_modulos))" ;
		return $this->get_certificados_plan_version($where);
	}
	
	/**
		Retorna la lista de certificados disponibles
	*/
	function get_certificados_disponibles($where='')
	{
		if ($where != '') $where .= ' AND ';
		$where .= " sga_certificados.estado = '".comunes::estado_activo."'" ;
		return toba::consulta_php('co_certificados')->get_certificados_disponibles_plan_version($this->get_id_plan_version(),$where);
	}

		
	//-------------------------------------------------------------------
	//--- Manejo de certificados
	//-------------------------------------------------------------------
	
	// Se encuentra en la pantalla de Edición de un Certificado del plan seleccionado y sus condiciones.
	function certificado_cargado()
	{
		return $this->entidad()->dep('datos')->esta_cargada();
	}
	
	function asociar_certificado($certificado, $entidad)
	{
		$this->cargar(array('plan_version' => $this->s__plan_version, 'certificado' => $certificado, 'entidad' => $entidad, 'plan_version_editable' => $this->s__plan_version_editable));
		$this->entidad()->asociar_certificado($certificado, $entidad);
	}

	function seleccionar_certificado($certificado, $entidad)
	{
		$this->cargar(array('plan_version' => $this->s__plan_version, 'certificado' => $certificado, 'entidad' => $entidad, 'plan_version_editable' => $this->s__plan_version_editable));
	}

	function disociar_certificado($param)
	{
		$this->cargar($param, false);
		$this->entidad()->eliminar_condiciones();
		$this->guardar();
		$this->resetear_entidades();
	}

	//-------------------------------------------------------------------
	//--- Consultas
	//-------------------------------------------------------------------
	
	function resetear_certificado()
	{
		unset($this->s__certificado_editable);
		unset($this->s__msj_certif_no_editable);
	}
	
	/** Se redefine la función del padre, ya que en el caso de los Planes Personalizados Activos, puede 
	 *	no ser editable cada certificado en particular, más allá de la condición de edición del Plan.
	 *	Esto debe verificarse sólo para este caso y cuando se está en la pantalla de Edición de las 
	 *	Condiciones de un Certificado ya seleccionado. En cualquier otro caso (planes estructurados o en la
	 *	pantalla de todos los certificados del Plan) se invoca la función del padre que verifica la 
	 *	editabilidad del Plan en general.
	 */
	function es_editable() 
	{
		return $this->es_plan_editable() && $this->es_certificado_editable();
	}
	
	function es_plan_editable()
	{
		return $this->s__plan_version_editable;
	}
	
	function es_certificado_editable()
	{
		if	($this->certificado_cargado() && 
			($this->entidad()->es_plan_personalizado() || $this->entidad()->es_plan_para_administrar_cursos() || $this->entidad()->es_propuesta_vocacional()) && 
			$this->entidad()->es_version_activa()) {
			if (!isset($this->s__certificado_editable)) {
				$this->s__certificado_editable = $this->verificar_edicion_certificado_alumno(); 
			}
			return $this->s__certificado_editable;
		} else {
			return parent::es_editable();
		}
	}
	
	function condicion_certif_no_editable()
	{
		// Si hubiese más de una validación para no editar, se van sumando los msjes de información.
		return $this->s__msj_certif_no_editable;
	}
	
	/**
	 * Si el Alumno correspondiente al Plan Personalizado, o un Plan Estructurado que Administra Cursos, o un plan correspondiente a una propuesta vocacional,
	 * Activo-Vigente seleccionado ya ha iniciado el trámite de solicitud del Certificado que seleccionado, 
	 * el mismo no puede eliminarse ni editar sus opciones de cumplimiento. 
	 */
	function verificar_edicion_certificado_alumno()
	{
		$plan_version = $this->get_id_plan_version();
		$certificado = $this->entidad()->get_id_certificado();
		if ($this->entidad()->es_plan_personalizado()) {
			$datos_alumno = toba::consulta_php('co_alumnos')->get_alumno_de_plan_version($plan_version); 
			$alumno = $datos_alumno['alumno'];
			$tipo_plan = planes::tipo_personalizado;
			$desc_alumno = "el alumno para el que el plan fue creado";
		} else {
			$alumno = null;
			$tipo_plan = $this->entidad()->es_plan_para_administrar_cursos() ? planes::tipo_estruct_curso_descr : planes::tipo_vocacional_descr;
			$desc_alumno = "existe al menos un alumno que";
		}
		$editable = !toba::consulta_php('co_alumnos')->tiene_certificado_otorgado_en_plan_version($certificado, $plan_version, $alumno);
		if (!$editable) {
			$this->s__msj_certif_no_editable = guarani_mensajes::get('800PRO_editar_plan_activo_certificado_alumno_egresado', array($tipo_plan, $desc_alumno));
		}
		return $editable;
	}	
}
?>