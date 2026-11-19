<?php
/**
 *
 * 
 * @package 	Matrícula
 * @subpackage 	Competencias
 * 
 */
class ci_edi_asignacion extends guarani_pers_ci
{
	
	protected $s__filtro;
	protected $s__datos;
	
	protected $seleccionados;
	
	//Componente Visual de Información
	protected $ev_info;
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__pant_inicial()
	{
		$seleccion = $this->controlador()->get_seleccion();
		$datos = toba::consulta_php('co_propuestas')->get_propuestas_datos($seleccion['propuesta']);
		$this->ev_info()->set_datos($datos);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			
			$this->ev_info->set_columnas_a_mostrar(array('nombre'));
			
			$this->ev_info->set_columnas_fila(array('nombre'), 1);
			
			$this->ev_info->set_max_columnas_por_fila(1);
			
			$this->ev_info->set_etiqueta('nombre', 'Propuesta');
			
			$this->ev_info->set_td_etiqueta_width('70px');
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- asignados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__asignados(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_asignados();
		$cuadro->set_datos($datos);
		
		if (empty($datos)) {
			$indx_msg = '800_eof_cuadro';
			$msj = guarani_mensajes::get($indx_msg, array('Competencias'));
			$cuadro->set_eof_mensaje($msj);
		}		
	}

	function evt__asignados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_fila($seleccion['x_dbr_clave']);
	}

	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponibles(toba_ei_cuadro $cuadro)
	{
		$asignados = $this->cn()->entidad()->get_asignados();
		$filtro = 'true';

		if (!empty($asignados)) {
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($asignados, 'sga_competencias_ingreso', 'competencia', 'competencia', true);		    	
		}

		if (! empty($this->s__filtro)) {
			$filtro .= " AND $this->s__filtro";
		}

		$datos = $this->cn()->get_listado_competencias($filtro);
		$cuadro->set_datos($datos);
		
		if (empty($datos)) {
			$indx_msg = '800_eof_cuadro';
			if (!empty($this->s__datos)) {
				$indx_msg .= '_filtrado';
			}
			$msj = guarani_mensajes::get($indx_msg, array('Competencias'));
			$cuadro->set_eof_mensaje($msj);
		}
	}

	function evt__disponibles__seleccion($datos)
	{
		$this->seleccionados = $datos;
	}

	function evt__disponibles__agregar()
	{
		if (! empty($this->seleccionados)) {
			$seleccion = $this->controlador()->get_seleccion();
			$propuesta = $seleccion['propuesta'];
			foreach ($this->seleccionados as $dato) {
				$this->cn()->entidad()->asignar(array('competencia'=>$dato['competencia'], 'propuesta'=>$propuesta));
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(guarani_ei_filtro $filtro)
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
		$this->s__filtro = '';
	}

}
?>