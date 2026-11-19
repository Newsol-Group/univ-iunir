<?php

class ci_seleccion_valor_parametro extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;

	function get_datos($filtro)
	{
		return toba::consulta_php('co_parametros')->get_listado_valores($filtro);
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
}

?>