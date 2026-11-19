<?php
/**
 *
 *
 * @package 	Propuestas
 * @subpackage 	Certificados
 * 
 */
class ci_edi_asignar_comp_a_alcances extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;
	
	protected $seleccionados;
	
	// Componente Visual de Información.
	protected $ev_info;

	function get_resultados($where='')
	{
		$filtro = "sga_resultados_aprendizaje.resultado_tipo = 'C'";
		if ($where) {
			$filtro .= ' AND '.$where;
		}
		return toba::consulta_php('co_resultados_de_aprendizaje')->get_listado($filtro);
	}
	
	function get_msg_eof($etiqueta = null)
	{
		$indx_msg = '800_eof_cuadro';
		if (isset($this->s__filtro) && ! empty($this->s__filtro)) {
			$indx_msg .= '_filtrado';
		}
		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('certificado', 'alcance'));
			$this->ev_info->set_columnas_fila(array('certificado'), 1);
			$this->ev_info->set_columnas_fila(array('alcance'), 2);
			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_etiqueta('certificado', 'Certificado');
			$this->ev_info->set_etiqueta('alcance', 'Alcance');
			$this->ev_info->set_td_etiqueta_width('100px');
		}
		return $this->ev_info;
	}
	
	function conf__pant_inicial()
	{
		$this->ev_info()->set_datos($this->controlador()->get_datos_ev_info());
	}
	
	//-----------------------------------------------------------------------------------
	//---- asignados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__asignados(toba_ei_cuadro $cuadro)
	{
		$asignados = $this->cn()->entidad()->get_asignaciones();
		if (!empty($asignados)) {
			$where = guarani_sql::armar_condicion_in($asignados, 'sga_resultados_aprendizaje', 'resultado', 'resultado');
			$datos = $this->get_resultados($where);		
			$cuadro->set_datos($datos);
		}		
	}

	function evt__asignados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_fila($seleccion);
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

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
	
	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponibles(toba_ei_cuadro $cuadro)
	{
		$asignados = $this->cn()->entidad()->get_asignaciones();
		
		if (empty($this->s__filtro)) {
			$filtro = ' true ';			
		} else {
			$filtro = $this->s__filtro; 
		}
		
		if (!empty($asignados)) {
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($asignados, 'sga_resultados_aprendizaje', 'resultado', 'resultado', true);
		}
		
		$datos = $this->get_resultados($filtro);
		$cuadro->set_datos($datos);
		
		$cuadro->set_eof_mensaje($this->get_msg_eof('Competencias'));
	}

	function evt__disponibles__seleccion($datos)
	{
		$this->seleccionados = $datos;
	}

	function evt__disponibles__agregar()
	{
		$certificado = $this->controlador()->get_preseleccion();
		if (! empty($this->seleccionados)) {			
			foreach ($this->seleccionados as $dato) {
				$this->cn()->entidad()->asignar(array('resultado'=>$dato['resultado'], 'certificado' => $certificado));
			}
		}
	}

}
?>