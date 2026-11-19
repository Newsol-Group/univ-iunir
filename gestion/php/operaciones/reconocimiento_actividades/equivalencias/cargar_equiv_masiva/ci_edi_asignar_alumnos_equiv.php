<?php
/**
 *
 *
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Equivalencias
 * 
 */
class ci_edi_asignar_alumnos_equiv extends guarani_pers_ci 
{
	//Mensaje cuadro
	protected $indx_msg_eof = '800_eof_cuadro';
	
	//Filtro
	protected $s__filtro;
	protected $s__datos;
	
	//Componente Visual de Información.
	protected $ev_info;
	
	//Datos auxiliares
	protected $seleccionados;
	protected $plan;
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__pant_inicial()
	{
		$this->ev_info = $this->controlador()->ev_info();
		$this->plan = $this->controlador()->get_plan();
	}
	
	function ev_info()		
	{
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- asignados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__asignados(toba_ei_cuadro $cuadro)
	{
		$asignados = $this->controlador()->get_alumnos_asignados();
		
		if (empty($asignados)) {
			$msg_eof = guarani_mensajes::get($this->indx_msg_eof, array('Alumnos seleccionados'));
			$cuadro->set_eof_mensaje($msg_eof);
		} else {
			$filtro = guarani_sql::armar_condicion_in($asignados, 'sga_alumnos', 'alumno', 'alumno');
			$alumnos = toba::consulta_php('co_alumnos')->get_listado($filtro);			
			$cuadro->set_datos($alumnos);
		}
	}
	
	function evt__asignados__eliminar($seleccion)
	{
		$this->controlador()->eliminar_alumno($seleccion);
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
		if (isset($datos) && !empty($datos)) {
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
	
	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponibles(toba_ei_cuadro $cuadro)
	{
		$index = $this->indx_msg_eof;
		
		if (!isset($this->s__filtro)) {
			$index .= '_sinfiltro';
		} else {
			$index .= '_filtrado';
			$asignados = $this->controlador()->get_alumnos_asignados();
			$filtro = empty($this->s__filtro) ? ' true AND ' : $this->s__filtro . ' AND ';
			$filtro .= 'sga_alumnos.plan_version = ' . $this->plan;
			
			if (!empty($asignados)) {
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($asignados, 'sga_alumnos', 'alumno', 'alumno', true);
			}
			
			$alumnos = toba::consulta_php('co_alumnos')->get_alumnos_activos($filtro);
			$cuadro->set_datos($alumnos);
		}
		
		$msg_eof = guarani_mensajes::get($index, array('Alumnos'));
		$cuadro->set_eof_mensaje($msg_eof);
	}

	function evt__disponibles__seleccion($datos)
	{
		$this->seleccionados = $datos;
	}

	function evt__disponibles__agregar()
	{
		if (!empty($this->seleccionados)) {
			$this->controlador()->set_alumnos_asignados($this->seleccionados);
		}
	}
	
	function resetear_variables()
	{
		unset($this->s__datos);
		unset($this->s__filtro);
	}
	
}
?>