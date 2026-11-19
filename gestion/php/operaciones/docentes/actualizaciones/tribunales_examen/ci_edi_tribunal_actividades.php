<?php
class ci_edi_tribunal_actividades extends guarani_pers_ci
{
	protected $seleccionados;

	protected $s__filtro_disp;
	protected $s__datos_disp;
	
	protected $datos_asoc_temp;

	//-----------------------------------------------------------------------------------
	//---- asignados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__asignados(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_asoc_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_asoc_temp);		
		} else {
			$asignados = $this->cn()->entidad()->get_datos_actividades_asignadas();
			$form_ml->set_datos($asignados);
		}
	}

	function evt__asignados__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_actividades_asignadas($datos);
		} catch ( guarani_error $e ) {
			$this->datos_asoc_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
	
	function evt__asignados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_fila_actividad_asignada($seleccion);
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
		$asignados = $this->cn()->entidad()->get_datos_actividades_asignadas();		
		$filtro = 'TRUE';
			
		if (!empty($asignados)) {
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($asignados, 'sga_elementos', 'elemento', 'elemento', true);
		}
				
		if (!((is_null($this->s__filtro_disp)) || ($this->s__filtro_disp == ''))) {
			$filtro .= " AND $this->s__filtro_disp";
		}
			
		$datos = $this->cn()->get_listado_actividades($filtro);					
		
		$cuadro->set_datos($datos);
	}

	function evt__disponibles__seleccion($datos)
	{		
		$this->seleccionados = $datos;
	}

	function evt__disponibles__agregar()
	{
		if (!empty($this->seleccionados)) {
			foreach ($this->seleccionados as $seleccion) {
				$this->cn()->entidad()->agregar_fila_actividad($seleccion);
			}			
		}
	}

}
?>