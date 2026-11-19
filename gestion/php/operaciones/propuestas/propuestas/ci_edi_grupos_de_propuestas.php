<?php
class ci_edi_grupos_de_propuestas extends guarani_pers_ci
{
	
	protected $propuestas_seleccionadas;
	
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------


	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);		
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos());
		}
	}


	function evt__formulario__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos($datos);	
		} catch ( guarani_error $e ) {
			$this->datos_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}


	//-----------------------------------------------------------------------------------
	//---- prop_no_asociadas ------------------------------------------------------------
	//-----------------------------------------------------------------------------------


	function conf__prop_no_asociadas(toba_ei_cuadro $cuadro)
	{
		$propuestas = $this->cn()->entidad()->get_datos_prop_asoc();	

		if (!empty($propuestas)) {
	    	$filtro = guarani_sql::armar_condicion_in($propuestas, 'sga_propuestas', 'propuesta', 'propuesta', true);
		} else {
			$filtro = '';
		}
		$datos = $this->cn()->get_listado_propuestas($filtro);
		$cuadro->set_datos($datos);
	}

	function evt__prop_no_asociadas__agregar()
	{
		if (!empty($this->propuestas_seleccionadas)) {
			foreach ($this->propuestas_seleccionadas as $propuesta) {
				$this->cn()->entidad()->set_datos_prop_asoc($propuesta);
			}	
		}
		
	}
	
	function evt__prop_no_asociadas__seleccion($seleccion)
	{
		$this->propuestas_seleccionadas = $seleccion;
	}

	//-----------------------------------------------------------------------------------
	//---- propuestas_asociadas ---------------------------------------------------------
	//-----------------------------------------------------------------------------------


	function conf__propuestas_asociadas(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_datos_prop_asoc();
		$cuadro->set_datos($datos);
	}	

	function evt__propuestas_asociadas__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_datos_prop_asoc($seleccion);
	}

}
?>