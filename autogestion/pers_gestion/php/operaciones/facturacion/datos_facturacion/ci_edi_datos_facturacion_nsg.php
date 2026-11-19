<?php
class ci_edi_datos_facturacion_nsg extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial()
	{
		$alumno = $this->controlador()->get_alumno_seleccionado();
		$datos = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($alumno['alumno']);
		$this->ev_info()->set_datos($datos);
		$this->ev_info()->set_datos($alumno['alumno']);

	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'documento_principal', 'legajo', 'propuesta_nombre_abreviado', 'plan_nombre', 'plan_version_nombre', 'ubicacion_nombre'));
			
			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'documento_principal', 'legajo'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre_abreviado', 'plan_nombre', 'plan_version_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('ubicacion_nombre'), 3);
			
			$this->ev_info->set_max_columnas_por_fila(3);
			
			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');
			
			$this->ev_info->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('plan_version_nombre', 'Versión');

			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__formulario__modificacion($datos)
	{
		try {
			$alumno = $this->controlador()->get_alumno_seleccionado();  
			$this->cn()->entidad()->set_datos_facturacion($alumno['alumno'], $datos);
         
 		} catch (guarani_error $e) {
			$this->datos_temp = $datos;
			throw $e;
		}
	}


	function conf__formulario(toba_ei_formulario $form)
	{
		parent::conf__formulario($form);
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Recupera las formas de pago del alumno, según la propuesta.
	 */
	function get_formas_pago_alumno()
	{
		$alumno = $this->controlador()->get_alumno_seleccionado();
		$formas_pago = toba::consulta_php('co_facturacion')->get_formas_pago_alumno_gestion($alumno["alumno"]);

		return $formas_pago;
	}


}
?>