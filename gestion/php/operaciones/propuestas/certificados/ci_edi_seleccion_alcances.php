<?php
/**
 *
 *
 * @package 	Propuestas
 * @subpackage 	Certificados
 * 
 */
class ci_edi_seleccion_alcances extends guarani_pers_ci
{
	
	protected $seleccionados;

	protected $s__filtro_disp;
	protected $s__datos_disp;
	
	protected $datos_asoc_temp;
	
	function get_mensaje_eof_cuadro($etiqueta)
	{
		$indx_msg = '800_eof_cuadro';
		if (!empty($this->s__datos_disp)) {
			$indx_msg .= '_filtrado';
		}
		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}
	
	//-----------------------------------------------------------------------------------
	//---- asignados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__asignados(toba_ei_cuadro $cuadro)
	{
		if (isset($this->datos_asoc_temp)) {
			$this->controlador->set_hubo_cambios();
			$cuadro->set_datos($this->datos_asoc_temp);		
		} else {
			$asignados = $this->cn()->entidad()->get_datos_alcances();
			$where = guarani_sql::armar_condicion_in($asignados, 'sga_alcances', 'alcance', 'alcance');
			$datos = toba::consulta_php('co_certificados')->get_listado_alcances_activos($where);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array('Alcances seleccionados')));
	}
	
	function evt__asignados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_fila_alcance_asignada($seleccion);
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

	function conf__disponibles(toba_ei_cuadro $cuadro)
	{
		$asignados = $this->cn()->entidad()->get_datos_alcances();
		$filtro = 'true';
			
		if (!empty($asignados)) {			
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($asignados, 'sga_alcances', 'alcance', 'alcance', true);
		}
				
		if (!((is_null($this->s__filtro_disp)) || ($this->s__filtro_disp == ''))) {
			$filtro .= " AND $this->s__filtro_disp";
		}
			
		$datos = toba::consulta_php('co_certificados')->get_listado_alcances_activos($filtro);
		$cuadro->set_datos($datos);
		$cuadro->set_eof_mensaje($this->get_mensaje_eof_cuadro('Alcances'));
	}

	function evt__disponibles__seleccion($datos)
	{
		$this->seleccionados = $datos;
	}

	function evt__disponibles__agregar()
	{
		if (!empty($this->seleccionados)) {
			foreach ($this->seleccionados as $seleccion) {
				$this->cn()->entidad()->agregar_fila_alcance($seleccion);
			}
		}
	}
}
?>