<?php

class ci_seleccion_requisito extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;
	protected $s__requisito_tipo;
	
	function ini()
	{	
		$requisito_tipo = toba::memoria()->get_parametro('requisito_tipo');
		if (isset($requisito_tipo)) {
			$this->s__requisito_tipo = $requisito_tipo;
		}
	}

	function conf()
	{
		if (isset($this->s__requisito_tipo)) {
			$this->dep('filtro')->eliminar_columnas(array('nombre'));
			$this->dep('filtro')->columna('requisito_tipo')->set_solo_lectura(true);
			$this->dep('filtro')->eliminar_evento('filtrar');
			$this->dep('filtro')->eliminar_evento('cancelar');
		}
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_requisitos')->get_listado($filtro);
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
		} else {
			// Controlar si tiene un parametro enviado al popup y setear el filtro
			if (isset($this->s__requisito_tipo)) {
				$datos = array('requisito_tipo' => array('condicion' => 'es_igual_a', 'valor' => $this->s__requisito_tipo, 'condicion_cs' => '1', 'condicion_as' => '1'));
				$filtro->set_datos($datos);
				$this->s__filtro = $this->dep('filtro')->get_sql_where();
			}
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