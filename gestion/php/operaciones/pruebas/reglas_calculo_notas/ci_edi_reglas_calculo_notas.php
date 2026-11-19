<?php
class ci_edi_reglas_calculo_notas extends guarani_pers_ci
{
	protected $s__datos_form;
	protected $s__promedio;

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->s__datos_form)) {
			$form->set_datos($this->s__datos_form);
		}
	}

	function evt__formulario__modificacion($datos)
	{
		if (is_array($datos['alumno'])) {
			$datos['alumno'] = current($datos['alumno']);
		}
		$this->s__datos_form = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_resultado ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_resultado(toba_ei_formulario $form)
	{
		$form->set_datos(array('promedio' => $this->s__promedio));
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__ejecutar_regla()
	{
		$php_clase_regla = guarani::co('co_reglas_calculo')->get_php_clase_regla($this->s__datos_form['regla_calculo']);
		$clase = new $php_clase_regla();
		$this->s__promedio = $clase->get_promedio($this->s__datos_form['alumno'], $this->s__datos_form['comision'], $this->s__datos_form['instancia']);

		$this->set_pantalla('pant_resultado');
	}

	function evt__volver()
	{
		$this->set_pantalla('pant_inicial');
	}
	
	function get_alumno_nombre($id)
	{
		return toba::consulta_php('co_alumnos')->get_alumno_nombre($id);
	}
	
	function get_nombre_comision($comision)
	{
		return toba::consulta_php('co_comisiones')->get_nombre_comision($comision);
	}
}
?>