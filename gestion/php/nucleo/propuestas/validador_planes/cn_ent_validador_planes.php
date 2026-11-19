<?php 
/**
 * 
 *
 *
 * @package Propuestas
 * @subpackage ValidadorPlanes
 */
class cn_ent_validador_planes extends cn_entidad
{
	protected $id;	
	protected $dt_cer = 'certificados';
	protected $dt_pv = 'plan_version';
	protected $s__nueva = false;
	
	
	function inicializar_estado()
	{
		$this->tabla('plan')->nueva_fila(array());
		$this->tabla('plan')->set_cursor(0);
		$this->dep($this->dt_pv)->set_es_unico_registro(true);
	}

	function cargar($id)
	{
		$this->dep('datos')->cargar($id);
		//$this->tabla('plan')->set_cursor(0);
		$this->dep($this->dt_pv)->set_es_unico_registro(true);
		return $this->get_resultado();
	}

	function get_plan()
	{
		return $this->tabla('plan')->get();
	}

	function set_plan($datos)
	{
		return $this->tabla('plan')->set($datos);
	}
	
	function get_actividad() {
		/*$a = $this->get_form_carga();
		toba::logger()->debug("a: ".$a['plan_version']." pv: ".$pv);
		if($a['plan_version'] == $pv) {*/
			return $this->tabla('actividades')->get_filas();
		/*} else {
			return array(array());
		}*/
	}
	
	function asignar($elemento) {
		$fila = $this->tabla('actividades')->nueva_fila();
		$this->tabla('actividades')->modificar_fila($fila,array('elemento'=>$elemento)); 
	}
	
	function set_actividad($datos, $eliminar=false) {
		if($eliminar)
		{
			foreach($datos as $k=>$dat)
			{
				if($dat['seleccion'] == 1)
				{
					$datos[$k]= array("apex_ei_analisis_fila" => "B");
				}
			}
		}
		$this->tabla('actividades')->procesar_filas($datos);
		/*if ($this->tabla('actividades')->esta_cargada()) {
			foreach ($datos as $fila) {
				$this->tabla('actividades')->modificar_fila($fila['x_dbr_clave'],$fila);
			}
		} else {
			foreach ($datos as $fila) {
				$this->tabla('actividades')->nueva_fila($fila);
			}
		}*/
	}
	
	function has_actividad() {
		return $this->tabla('actividades')->get_cantidad_filas() > 0;
	}
	
	function get_resultado() {
		/*$a = $this->get_form_carga();
		if($a['plan_version'] == $pv) {*/
			return $this->tabla('resultados')->get_filas();
		/*} else {
			return array(array());
		}*/
	}
	
	function set_resultado($datos) {
		$this->tabla('resultados')->procesar_filas($datos);
		/*if ($this->tabla('resultados')->esta_cargada()) {
			foreach ($datos as $fila) {
				$this->tabla('resultados')->modificar_fila($fila['x_dbr_clave'],$fila);
			}
		} else {
			foreach ($datos as $fila) {
				$this->tabla('resultados')->nueva_fila($fila);
			}
		}*/
	}
	
	function has_resultado() {
		return $this->tabla('resultados')->get_cantidad_filas() > 0;
	}
	
	function cargar_certificados($datos) {
		$this->dep($this->dt_cer)->cargar_con_datos($datos);
	}
	
	function get_certificados() {
		return $this->dep($this->dt_cer)->get_filas();
	}
	
	function cargar_plan_version($datos) {
		$this->dep($this->dt_pv)->cargar_con_datos($datos);
	}
	
	function get_plan_version() {
		return $this->dep($this->dt_pv)->get();
	}
	
	function sincronizar() {
		parent::sincronizar();
	}
	
	function get_datos($tabla=null) {
		
	}
}

?>