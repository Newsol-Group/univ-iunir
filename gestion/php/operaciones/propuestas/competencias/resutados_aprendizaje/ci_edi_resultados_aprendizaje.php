<?php
class ci_edi_resultados_aprendizaje extends ci_edicion_simple 
{
	protected $s__filtro;
	protected $s__datos;
	
	protected $seleccionados;
	protected $datos_asoc_temp;
	
	function conf__formulario(toba_ei_formulario $form)
	{
		parent::conf__formulario($form);
		if (! $this->cn()->entidad()->es_nueva()) {
			$form->ef('resultado_tipo')->set_solo_lectura(true);
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- asignados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__asignados(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_asoc_temp)) {
			$this->controlador->controlador->set_hubo_cambios();
			$form_ml->set_datos($this->datos_asoc_temp);		
		} else {
			$asignados = $this->cn()->entidad()->get_datos_result_comp();
			$form_ml->set_datos($asignados);
		}
	}

	function evt__asignados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_fila_result_comp($seleccion);
	}
	
	function evt__asignados__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_result_comp($datos);
		} catch ( guarani_error $e ) {
			$this->datos_asoc_temp = $datos;
			throw guarani::error($e->getMessage());
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

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
		$this->s__filtro = '';
	}

	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponibles(toba_ei_cuadro $cuadro)
	{
		$asignados = $this->cn()->entidad()->get_datos_result_comp();
		$filtro = 'true';
			
		if (!empty($asignados)) {
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($asignados, 'sga_resultados_aprendizaje', 'resultado', 'resultado_hijo', true);		    	
		}
		
		if (!((is_null($this->s__filtro)) || ($this->s__filtro == ''))) {
			$filtro .= " AND $this->s__filtro";
		}
		
		$datos = toba::consulta_php('co_resultados_de_aprendizaje')->get_listado_no_competencia($filtro);
		
		$cuadro->set_datos($datos);
	}

	function evt__disponibles__seleccion($seleccion)
	{
		$this->seleccionados = $seleccion;
	}	

	function evt__disponibles__agregar()
	{
		if (!empty($this->seleccionados)) {
			foreach ($this->seleccionados as $seleccion) {
				$resultado['resultado_hijo'] = $seleccion['resultado'];
				$this->cn()->entidad()->agregar_fila_result_comp($resultado);
			}			
		}
	}

}
?>