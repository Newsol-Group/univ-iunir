<?php
class ci_sel_alumnos extends guarani_pers_ci
{
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $seleccionados;
	protected $s__filtro_disp;
	protected $s__datos_disp;
	protected $datos_asoc_temp;

	function resetear()
	{
		$this->evt__filtro__cancelar();
	}
	
	//-----------------------------------------------------------------------------------
	//---- asignados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__asignados(toba_ei_cuadro $cuadro)
	{
		$asignados = $this->cn()->get_alumnos_seleccionados();
		if (!empty($asignados)) {
			$filtro = guarani_sql::armar_condicion_in($asignados, 'sga_alumnos', 'alumno', 'alumno', false);
			$datos = toba::consulta_php('co_alumnos')->get_listado($filtro);
			$cuadro->set_datos($datos);
		} else {
			$etiquetas = guarani_etiquetas::get_tabla('sga_alumnos');
			$msg_eof = guarani_mensajes::get($this->indx_msg_eof, array($etiquetas->get_enf('plural')));
			$cuadro->set_eof_mensaje($msg_eof);
		}
	}

	function evt__asignados__eliminar($seleccion)
	{
		$this->cn()->eliminar_alumno_seleccionado($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_disp)) {
			$filtro->set_datos($this->s__datos_disp);
		}
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_disp = $datos;
			$this->s__filtro_disp = $this->dep('filtro')->get_sql_where();
		} else {
			unset($this->s__datos_disp);
			$this->s__filtro_disp = '';
		}
	}

	function evt__filtro__cancelar()
	{
		unset($this->s__datos_disp);
		$this->s__filtro_disp = '';
	}

	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponibles(cuadro_seleccion_multiple $cuadro)
	{
		if (!empty($this->s__filtro_disp)) {
			$asignados = $this->cn()->get_alumnos_seleccionados();

			$filtro = $this->s__filtro_disp;
			if (!empty($asignados)) {
				if (!empty($this->s__filtro_disp)) {
					$filtro .= ' AND ';
				}
				$filtro .= guarani_sql::armar_condicion_in($asignados, 'sga_alumnos', 'alumno', 'alumno', true);
			}
			$datos = $this->cn()->get_alumnos_disponibles($filtro);
			$cuadro->set_datos($datos);
		}

		$index = $this->indx_msg_eof;
		if (empty($this->s__datos_disp)) {
			$index .= '_sinfiltro';
		} else {
			$index .= '_filtrado';
		}
		$etiquetas = guarani_etiquetas::get_tabla('sga_alumnos');
		$msg_eof = guarani_mensajes::get($index, array($etiquetas->get_enf('plural')));
		$cuadro->set_eof_mensaje($msg_eof);
	}

	function evt__disponibles__seleccion($datos)
	{
		$this->seleccionados = $datos;
	}

	function evt__disponibles__agregar($datos)
	{
		if (!empty($this->seleccionados)) {
			$this->cn()->agregar_alumnos_seleccionados($this->seleccionados);
		}
	}
	
}
?>