<?php
/**
 *
 *
 * @package Equivalencias
 * @subpackage Detalle Grupos Equivalencias
 */
class ci_seleccion_matrices_a_aplicar extends guarani_pers_ci
{
	//Matriz de Equivalencia
	protected $s__alumno_id;
	protected $s__persona_id;
	protected $s__alumno_nombre;
	protected $s__alumno_plan_version;
	protected $s__alumno_version_nombre;
	protected $s__alumno_propuesta;
	protected $s__alumno_prop_nombre;
	protected $s__alumno_plan;
	protected $s__alumno_plan_nombre;
	protected $s__prop_origen;
	protected $s__plan_origen;
	protected $s__version_origen;
	protected $s__tipo_equiv;
	
	protected $s__seleccion_matrices;
	protected $s__claves_seleccion_matrices;
	protected $s__respuesta = '';
	
	//Elementos Visuales de información
	protected $ev_info;
	
	function ini__operacion() 
	{
		$this->s__alumno_id = toba::memoria()->get_parametro('alumno');
		$this->s__persona_id = toba::memoria()->get_parametro('persona');
		$this->s__alumno_nombre = toba::memoria()->get_parametro('alumno_nombre');
		$this->s__alumno_plan_version = toba::memoria()->get_parametro('alumno_plan_version');
		$this->s__alumno_version_nombre = toba::memoria()->get_parametro('alumno_version_nombre');
		$this->s__alumno_propuesta = toba::memoria()->get_parametro('alumno_propuesta');
		$this->s__alumno_prop_nombre = toba::memoria()->get_parametro('alumno_prop_nombre');
		$this->s__alumno_plan = toba::memoria()->get_parametro('alumno_plan');
		$this->s__alumno_plan_nombre = toba::memoria()->get_parametro('alumno_plan_nombre');
		$this->s__prop_origen = toba::memoria()->get_parametro('propuesta_origen');
		$this->s__plan_origen = toba::memoria()->get_parametro('plan_origen');
		$this->s__version_origen = toba::memoria()->get_parametro('version_origen');
		$this->s__tipo_equiv = toba::memoria()->get_parametro('tipo_equivalencia');
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		// Datos del ev info.
		$filtro = "sga_equiv_tramite_origen.origen = $this->s__tipo_equiv";
		$datos_tipo_equiv = current(toba::consulta_php('co_equivalencias')->get_listado_origenes_tipos($filtro));
		$info = array('alumno_nombre' => $this->s__alumno_nombre,
					  'propuesta_nombre' => $this->s__alumno_prop_nombre,
					  'plan_nombre' => $this->s__alumno_plan_nombre,
					  'version_nombre' => $this->s__alumno_version_nombre,
					  'tipo_equiv_descr' => $datos_tipo_equiv['descr']);
		$this->ev_info()->set_datos($info);
		// Se deshabilita el evento Aceptar si no hay matrices para seleccionar.
		$matrices = $this->dep('cuadro_matrices')->get_datos();
		if (empty($matrices)) {
			$pantalla->evento('aceptar')->desactivar();
		}
	}
	
	//---- cuadro_matrices --------------------------------------------------------------
	
	function conf__cuadro_matrices(toba_ei_cuadro $cuadro)
	{
		$datos = toba::consulta_php('co_matrices_de_equivalencia')->get_listado_matrices_aplicables_otorgar_equivalencia($this->s__alumno_id, $this->s__persona_id , $this->s__alumno_plan_version, $this->s__tipo_equiv, $this->s__prop_origen, $this->s__plan_origen, $this->s__version_origen);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_matrices__seleccion($seleccion)
	{
		$this->s__seleccion_matrices = $seleccion;
		$this->s__claves_seleccion_matrices = aplanar_matriz($this->s__seleccion_matrices, 'matriz');
	}
	
	function conf_evt__cuadro_matrices(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dependencia('cuadro_matrices')->get_datos();
		if (isset($this->s__claves_seleccion_matrices)) {
			if (array_search($datos[$fila]['matriz'], $this->s__claves_seleccion_matrices) === false) {
				$evento->set_check_activo(false);
			} else {
				$evento->set_check_activo(true);
			}
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- EVENTOS ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__aceptar()
	{
		if (!isset($this->s__seleccion_matrices)) {
			throw guarani::error('Debe seleccionar al menos una matriz para continuar');
		}
		$this->s__respuesta = implode('$$&$$', $this->s__claves_seleccion_matrices);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('alumno_nombre', 'propuesta_nombre', 'plan_nombre', 'version_nombre', 'tipo_equiv_descr'));
			$this->ev_info->set_td_etiqueta_width('120px');
			$this->ev_info->set_td_valores_width('270px');

			$this->ev_info->set_columnas_fila(array('alumno_nombre', 'propuesta_nombre', 'plan_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('version_nombre', 'tipo_equiv_descr'), 2);
			
			$this->ev_info->set_etiqueta('alumno_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('propuesta_nombre', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('version_nombre', 'Versión');
			$this->ev_info->set_etiqueta('tipo_equiv_descr', 'Tipo de Equivalencia');
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		if (!empty($this->s__respuesta)) {
			echo "
				window.opener.respuesta_popup('". $this->s__respuesta ."');
				window.close();
			";
		}
		
		//Se atiende el evento Cerrar, para provocar que se cierre la ventana popup.
		echo "
			{$this->objeto_js}.evt__cancelar = function() {
				window.close();
			}
		";		
	}
}
?>