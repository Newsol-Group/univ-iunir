<?php 
class ci_edi_abm_validacion extends guarani_pers_ci
{
	private $s__plan_version;

	//-----------------------------------------------------------------------------------
	//---- DEPENDENCIAS -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//---- form_seleccion ---------------------------------------------------------------

	function conf__form_seleccion(toba_ei_formulario_ml $form_ml)
	{
		$planes = $this->cn()->entidad()->get_plan();
		$datos = toba::consulta_php('co_planes')->get_contenido_plan_version($planes['plan_version'], array('solo_actividades' => true));
		foreach ($datos as $k=>$dat) {
			$actividades[$k] = $dat['nombre'];
		}
		array_multisort($actividades, $datos);
		$a = $this->cn()->entidad()->get_actividad();
		$elem = array();
		foreach ($a as $e) {
			$elem[] = $e['elemento'];
		}
		foreach ($datos as $k=>$dat) {
			if (!in_array($dat['elemento'], $elem)) {
				$datos[$k] = array('elemento'=>$dat['elemento'], 'nombre'=>'('.$dat['codigo'].') '.$dat['nombre']);
			} else {
				unset($datos[$k]);
			}
		}
		$form_ml->set_datos($datos);
	}
	
	function evt__form_seleccion__modificacion($datos)
	{
		foreach ($datos as $dat) {
			if ($dat['seleccion'] == 1) {
				$this->cn()->entidad()->asignar($dat['elemento']);
			}
		}
	}

	//---- ct_form_actividad ------------------------------------------------------------

	function conf__ct_form_actividad(toba_ei_formulario_ml $form_ml)
	{
		$a = $this->cn()->entidad()->get_actividad();
		foreach ($a as $k=>$d) {
			$a[$k]['nombre'] = $this->get_actividad_nombre($d['elemento']);
		}
		$form_ml->set_datos($a);
	}

	function evt__ct_form_actividad__modificacion($datos)
	{
		$this->cn()->entidad()->set_actividad($datos);
	}
	
	function evt__ct_form_actividad__eliminar($datos)
	{
		$this->cn()->entidad()->set_actividad($datos, true);
	}

	//---- ct_form_carga ----------------------------------------------------------------

	function conf__ct_form_carga(toba_ei_formulario $form)
	{
		$a = $this->cn()->entidad()->get_plan();
		if (isset($a['plan_version'])) {
			$this->s__plan_version = $a['plan_version'];
		}
		if (!isset($a['fecha_creacion'])) {
			$a['fecha_creacion'] = guarani_fecha::get_hoy();
		}
		$form->set_datos($a);
		if ($this->cn()->entidad()->has_actividad() || $this->cn()->entidad()->has_resultado()) {
			$form->ef('plan_version')->set_solo_lectura();
		} 
	}

	function evt__ct_form_carga__modificacion($datos)
	{
		$this->cn()->entidad()->set_plan($datos);
	}

	//---- ct_form_resultado ------------------------------------------------------------

	function conf__ct_form_resultado(toba_ei_formulario_ml $form_ml)
	{
		$a = $this->cn()->entidad()->get_resultado($this->s__plan_version);
		$form_ml->set_datos($a);
	}

	function evt__ct_form_resultado__modificacion($datos)
	{
		$this->cn()->entidad()->set_resultado($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- AUXILIARES -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_actividad_nombre($actividad) 
	{
		$a = $this->cn()->entidad()->get_plan();
		$act = toba::consulta_php('co_elementos')->get_info_elemento_plan($a['plan_version'], $actividad);
		return ($act['codigo']) ? "({$act['codigo']}) {$act['nombre']}" : $act['nombre'];
	}
	
	function get_certificados_plan_version()
	{
		$a = $this->cn()->entidad()->get_plan();
		return toba::consulta_php('co_certificados')->get_certificados_plan_version($a['plan_version']);
	}
}

?>