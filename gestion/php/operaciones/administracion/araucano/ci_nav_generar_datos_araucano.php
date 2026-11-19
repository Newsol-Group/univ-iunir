<?php
class ci_nav_generar_datos_araucano extends guarani_pers_ci
{
	protected $anio_academico;
	protected $responsable_academica;
	protected $cuadros_4_y_5;
	protected $archivo_13;

	function conf__resultados()
	{
		$this->evento('descargar_archivos')->vinculo()->set_parametros(array('anio_academico' => $this->anio_academico, 'responsable_academica' => $this->responsable_academica));
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__formulario__modificacion($datos)
	{
		$this->anio_academico = $datos['anio_academico'];
		$this->responsable_academica = $datos['responsable_academica'];
		$this->cuadros_4_y_5 = $datos['cuadros_4_y_5'];
		$this->archivo_13 = $datos['archivo_13'];
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__generar()
	{
		$this->cn()->generar($this->anio_academico, $this->cuadros_4_y_5, $this->archivo_13, $this->responsable_academica);
		$this->set_pantalla('resultados');
	}

	function evt__volver()
	{
		$this->set_pantalla('pant_inicial');
	}
}