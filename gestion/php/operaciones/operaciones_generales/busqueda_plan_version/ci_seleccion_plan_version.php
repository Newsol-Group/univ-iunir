<?php

class ci_seleccion_plan_version extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;

	function get_datos($filtro)
	{
		return toba::consulta_php('co_planes')->get_lista_planes($filtro);
	}

	//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();

		$filtro = isset($this->s__filtro) ? $this->s__filtro : null;
		$datos = $this->get_datos($filtro);
		$cuadro->set_datos($datos);

		$etiquetas = guarani_etiquetas::get_tabla('sga_planes_versiones');
		$mje = guarani_mensajes::get('800_eof_cuadro_filtrado', array($etiquetas->get_enf('plural')));
		$cuadro->set_eof_mensaje($mje);
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