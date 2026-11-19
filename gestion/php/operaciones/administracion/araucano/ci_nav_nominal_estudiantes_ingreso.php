<?php
class ci_nav_nominal_estudiantes_ingreso extends guarani_pers_ci
{
	protected $anio_academico;
	protected $responsable_academica;

	function conf__resultados()
	{
		$this->evento('descargar_archivos')->vinculo()->set_parametros(array('anio_academico' => $this->anio_academico, 'responsable_academica' => $this->responsable_academica, 'estudiantes_ingreso' => true));
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__formulario__modificacion($datos)
	{
		$this->anio_academico = $datos['anio_academico'];
		$this->responsable_academica = $datos['responsable_academica'];
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__generar()
	{
		$this->cn()->generar($this->anio_academico, $this->responsable_academica);
		$this->set_pantalla('resultados');
	}

	function evt__volver()
	{
		$this->set_pantalla('pant_inicial');
	}
}
?>