<?php
class ci_edi_requisitos extends guarani_pers_ci
{	
	protected $s__filtro_requisitos;
	protected $s__datos_requisitos;	
	
	//-----------------------------------------------------------------------------------
	//---- form_datos_acciones ----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__form_datos_acciones(toba_ei_formulario $form)
	{
		$form->set_datos($this->cn()->entidad()->get_datos_acciones());
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_req_asoc --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__cuadro_req_asoc(toba_ei_cuadro $cuadro)
	{
		$opers = $this->cn()->entidad()->get_datos_requisitos();	
		$cuadro->set_datos($opers);
	}
	
	function evt__cuadro_req_asoc__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_requisito($seleccion);		
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro_requisitos ------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__filtro_requisitos(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_requisitos)) {
			$filtro->set_datos($this->s__datos_requisitos);
		}		
	}
	
	function evt__filtro_requisitos__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_requisitos = $datos;
			$this->s__filtro_requisitos = $this->dep('filtro_requisitos')->get_sql_where();
		} else {
			unset($this->s__filtro_requisitos);
			$this->s__filtro_requisitos = '';
		}
	}
	
	function evt__filtro_requisitos__cancelar()
	{	
		unset($this->s__datos_requisitos);
		$this->s__filtro_requisitos = '';
	}	
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_req_disp --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
		
	function conf__cuadro_req_disp(toba_ei_cuadro $cuadro)
	{
		$req = $this->cn()->entidad()->get_datos_requisitos();	

		if ((is_null($this->s__filtro_requisitos)) || ($this->s__filtro_requisitos == '')) {
			$filtro = '';
		} else {
			$filtro = $this->s__filtro_requisitos;
		}
		
		if (!empty($req)) {
			if ($filtro) {
				$filtro .= ' AND ';	
			}
			$filtro .= guarani_sql::armar_condicion_in($req, 'sga_requisitos', 'requisito', 'requisito', true);		
		}
		
		$datos = $this->cn()->get_listado_requisitos($filtro);
		$cuadro->set_datos($datos);
	}
	
	function evt__cuadro_req_disp__seleccion($datos)
	{
		$this->seleccionados = $datos;
	}
	
	function evt__cuadro_req_disp__agregar($seleccion)
	{
		foreach ($this->seleccionados as $seleccion) {
			$this->cn()->entidad()->agregar_requisitos($seleccion);
		}
	}
	
}

?>