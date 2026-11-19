<?php

class ci_seleccion_docente extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;

	protected $indx_msg_eof = '800_eof_cuadro_sinfiltro';
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_docentes')->get_listado($filtro);
	}

	//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();
		
		if (isset($this->s__filtro)){
			$filtro = $this->s__filtro;

			$datos = $this->get_datos($filtro);
			$cuadro->set_datos($datos);
		}

		$etiquetas = guarani_etiquetas::get_tabla('sga_docentes');
		$mje = guarani_mensajes::get($this->indx_msg_eof, array($etiquetas->get_enf('plural')));
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
		
		//Concatena al filtro que el docente esté en actividad
		if ($this->s__filtro != ''){
			$this->s__filtro .= ' AND ';
		}
		$this->s__filtro .= " sga_docentes.estado = '".comunes::estado_activo."'";
	}

	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		unset($this->s__filtro);
	}
}

?>