<?php

class ci_seleccion_documento extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;

	function get_datos($filtro)
	{
		return toba::consulta_php('co_documentos')->get_listado($filtro);
	}

	//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();
		if (isset($this->s__filtro)) {
			$datos = $this->get_datos($this->s__filtro);
			$cuadro->set_datos($datos);
		} else {
			$datos = $this->get_datos('');
			$cuadro->set_datos($datos);
		}
	}

	//---- filtro -----------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}
	}

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
		unset($this->s__filtro);
	}
}

?>