<?php
/**
 * BORRAR ARCHIVO
 */
class ci_edi_modalidades extends guarani_pers_ci
{
	protected $seleccionados;
	protected $datos_asoc_temp;
	
	/**
	 * Si se trata de alta, debe tener por defecto todas las modalidades asignadas
	 *
	 */
	function ini__operacion()
	{
		if ($this->cn()->entidad()->es_nueva()) {
			$modalidades = toba::consulta_php('co_planes')->get_modalidades();
			foreach ($modalidades as $seleccion) {
				$in['modalidad'] = $seleccion['modalidad'];
				$this->cn()->entidad()->agregar_fila_modalidad($in);
			}	
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
			$asignados = $this->cn()->entidad()->get_datos_per_inscr_modalidad();
			$form_ml->set_datos($asignados);
		}
	}

	function evt__asignados__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_per_inscr_modalidad($datos);
		} catch ( guarani_error $e ) {
			$this->datos_asoc_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__asignados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_fila_modalidad_asignada($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponibles(toba_ei_cuadro $cuadro)
	{
		$asignados = $this->cn()->entidad()->get_datos_per_inscr_modalidad();
		$filtro = 'true';
			
		if (!empty($asignados)) {			
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($asignados, 'sga_modalidad_cursada', 'modalidad', 'modalidad', true);		    	
		}
			
		$datos = toba::consulta_php('co_planes')->get_modalidades($filtro);
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
				$this->cn()->entidad()->agregar_fila_modalidad($seleccion);
			}			
		}
	}

}

?>