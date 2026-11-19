<?php
/**
 *
 * @package		Propuestas Formativas
 * @subpackage	Actividades
 */
class ci_edi_actividad_excepciones_comunes extends guarani_pers_ci
{
	// Componente Visual de Información
	protected $ev_info; 

	//--------------------------------------------------------------------------
	//---- ml_planes ----------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__ml_planes(toba_ei_formulario $form)
	{
		$seleccion = $this->controlador()->get_seleccion();
		$datos = toba::consulta_php('co_elementos')->get_otros_planes_by_actividad_compartida($seleccion['elemento'], $seleccion['plan']);
		$form->set_datos($datos);
	}

	function evt__ml_planes__modificacion($datos)
	{
		$this->cn()->set_excepciones($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- pant_planes ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_planes(guarani_ei_pantalla $pantalla)
	{
		$seleccion = $this->controlador()->get_seleccion();
		$seleccion['elemento_descripcion'] = "({$seleccion['elemento_codigo']}) {$seleccion['elemento_nombre']}";
		$this->ev_info()->set_datos($seleccion);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('elemento_descripcion', 'propuesta_descripcion', 'plan_descripcion'));
			$this->ev_info->set_columnas_fila(array('elemento_descripcion'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_descripcion'), 2);
			$this->ev_info->set_columnas_fila(array('plan_descripcion'), 3);

			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_td_etiqueta_width('130px');

			$this->ev_info->set_etiqueta('elemento_descripcion', 'Actividad:');
			$this->ev_info->set_etiqueta('propuesta_descripcion', 'Propuesta Origen:');
			$this->ev_info->set_etiqueta('plan_descripcion', 'Plan Origen:');
		}
		return $this->ev_info;
	}
}
?>