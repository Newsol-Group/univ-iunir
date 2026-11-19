<?php
/**
 * 
 * 
 * @package 	Matrícula
 * @subpackage 	Alumnos
 *
 */
class ci_edi_convalidar_ha extends guarani_pers_ci
{
	protected $datos_temp;
	protected $ev_info;

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial()
	{
		$datos = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($this->controlador()->get_alumno_seleccionado());
		$this->ev_info()->set_datos($datos);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'legajo', 'propuesta_nombre_abreviado', 'plan_nombre', 'ubicacion_nombre'));
			
			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'legajo'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre_abreviado', 'plan_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('ubicacion_nombre'), 3);
			
			$this->ev_info->set_max_columnas_por_fila(2);
			
			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');
			
			$this->ev_info->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);
		}
	}

	function evt__formulario__modificacion($datos)
	{
		$this->datos_temp = $datos;
		$this->cn()->set_datos($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_aprobadas -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_aprobadas(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_actividades_inv_aprobadas($this->controlador()->get_alumno_seleccionado());
		$cuadro->set_datos($datos);
		if (empty($datos)) {
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800_sin_registros'));
		}
	}

	function evt__cuadro_aprobadas__seleccion($seleccion)
	{
		$this->cn()->set_seleccion_aprobadas($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_regularizadas ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_regularizadas(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_actividades_inv_regularizadas($this->controlador()->get_alumno_seleccionado());
		$cuadro->set_datos($datos);
		if (empty($datos)) {
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800_sin_registros'));
		}
	}

	function evt__cuadro_regularizadas__seleccion($seleccion)
	{
		$this->cn()->set_seleccion_regularizadas($seleccion);
	}
}
?>