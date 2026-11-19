<?php

class ci_seleccion extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;
	protected $s__seleccion;
	protected $indx_msg_eof = '800_eof_cuadro';

	//------ Filtro ----------------------

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = $this->dep('filtro')->get_sql_where();
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		$this->s__filtro = '';
	}

	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}		
	}

	//------ Cuadro ----------------------

	function evt__cuadro__seleccion($seleccion) 
	{
		$this->s__seleccion = $seleccion;
	}
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (isset($this->s__filtro)) {
			$datos = $this->get_datos($this->s__filtro);
		} else {
			$datos = $this->get_datos();
		}
		$msj = $this->get_msg_eof(isset($this->s__filtro));
		$cuadro->set_eof_mensaje($msj);
		$cuadro->set_datos($datos);
	}

}
?>