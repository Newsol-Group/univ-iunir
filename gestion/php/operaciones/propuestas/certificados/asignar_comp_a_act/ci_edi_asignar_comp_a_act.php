<?php
/**
 *
 *
 * @package 	Propuestas
 * @subpackage 	Certificados
 * 
 */
class ci_edi_asignar_comp_a_act extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;
	
	protected $seleccionados;

	
	function get_resultados($where='')
	{
		$selecciones = $this->cn()->get_selecciones();
		return toba::consulta_php('co_resultados_de_aprendizaje')->get_resultados_por_certificado($selecciones['certificado'], $where);
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
	
	function conf__pant_inicial()
	{
		$this->controlador()->ev_info()->set_datos($this->cn()->get_ev_info_datos());
	}
	
	//-----------------------------------------------------------------------------------
	//---- asignados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__asignados(toba_ei_formulario_ml $form_ml)
	{
		$datos = $this->cn()->entidad()->get_asignaciones();
		$datos = rs_ordenar_por_columna($datos, 'resultado_alcanzado');
		$form_ml->set_datos($datos);
	}

	function evt__asignados__modificacion($datos)
	{
		$this->cn()->entidad()->procesar_asignados($datos);
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
		$selecciones = $this->cn()->get_selecciones();
		if (! empty($this->seleccionados)) {			
			foreach ($this->seleccionados as $dato) {
				$this->cn()->entidad()->asignar(array(	'plan_version' => $selecciones['plan_version'],
														'certificado' => $selecciones['certificado'],
														'elemento' => $selecciones['elemento'],
														'resultado' => $dato['resultado']));
			}
		}
	}	

}
?>