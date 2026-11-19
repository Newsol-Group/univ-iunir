<?php
class ci_edi_ubicaciones extends guarani_pers_ci
{
	protected $seleccionados;
	protected $datos_asoc_temp;

	protected $s__filtro_disp;
	protected $s__datos_disp;
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Si se trata de un alta y existe una única ubicación, ésta deberá aparecer seleccionada por defecto
	 */
	function conf__pant_inicial()
	{
		if ($this->cn()->entidad()->es_nueva() && toba::consulta_php('co_ubicaciones')->get_cantidad_ubicaciones() == 1 && !$this->cn()->entidad()->esta_cargada_per_inscr_ubicacion()) {
			$ubicaciones = toba::consulta_php('co_ubicaciones')->get_listado();
			$this->cn()->entidad()->agregar_fila_ubicacion($ubicaciones[0]);
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- informacion ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__informacion(toba_ei_formulario $form)
	{
		$periodo = $this->cn()->entidad()->get_datos();
		$periodo_nombre = $periodo['nombre'];
		$per_inscr_prop = $this->cn()->entidad()->get_datos_per_inscr_prop();
		$anio_academico = $per_inscr_prop['anio_academico'];
		
		$form->set_datos(array('nombre' => $periodo_nombre, 'anio_academico' => $anio_academico));
	}
	
	//-----------------------------------------------------------------------------------
	//---- asignados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__asignados(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_asoc_temp)) {
			$this->controlador->set_hubo_cambios();
			$form_ml->set_datos($this->datos_asoc_temp);		
		} else {
			$asignados = $this->cn()->entidad()->get_datos_per_inscr_ubicacion();
			$form_ml->set_datos($asignados);
		}
	}

	function evt__asignados__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_per_inscr_ubicacion($datos);
		} catch ( guarani_error $e ) {
			$this->datos_asoc_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
	
	function evt__asignados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_fila_ubicacion_asignada($seleccion);
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
		$asignados = $this->cn()->entidad()->get_datos_per_inscr_ubicacion();
		$filtro = 'true';
			
		if (!empty($asignados)) {			
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($asignados, 'sga_ubicaciones', 'ubicacion', 'ubicacion', true);		    	
		}
				
		if (!((is_null($this->s__filtro_disp)) || ($this->s__filtro_disp == ''))) {
			$filtro .= " AND $this->s__filtro_disp";
		}
			
		$datos = toba::consulta_php('co_ubicaciones')->get_listado($filtro);
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
				$this->cn()->entidad()->agregar_fila_ubicacion($seleccion);
			}
		}
	}
}
?>