<?php
class ci_edi_asignacion_actividad extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;
	
	protected $seleccionados;

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__formulario(toba_ei_formulario $form)
	{
		$elem = $this->cn()->entidad()->get_elemento();
		$form->set_datos($elem);
	}	
	
	//-----------------------------------------------------------------------------------
	//------- asignados -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__formulario_ml_asig(toba_ei_formulario_ml $form_ml)
	{
		$datos = $this->cn()->entidad()->get_resultados();
		$form_ml->set_datos($datos);		
	}
	
	function evt__formulario_ml_asig__modificacion($datos)
	{	
		$elem = $this->cn()->entidad()->get_elemento();
		foreach ($datos as $k=>$dat) {
			$datos[$k] = array('resultado'=>$dat['resultado'], 'porc_alcanzado'=>$dat['porc_alcanzado'], 'elemento'=>$elem['elemento'], 'apex_ei_analisis_fila' => 'M');
		}		
		$this->cn()->entidad()->set_datos($datos);
	}

	function evt__formulario_ml_asig__eliminar($datos)
	{
		$this->cn()->entidad()->eliminar_fila($datos);
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
		$asignados = $this->cn()->entidad()->get_resultados();
		
		$filtro = '';
		if (!empty($asignados)) {			
			$filtro .= guarani_sql::armar_condicion_in($asignados, 'sga_resultados_aprendizaje', 'resultado', 'resultado', true);
		}
		
		if (($filtro != '') && ($this->s__filtro != '')) {
			$filtro .= ' AND ';
		}
		
		$filtro .= $this->s__filtro;
		
		$datos = toba::consulta_php('co_resultados_de_aprendizaje')->get_listado($filtro);
		$cuadro->set_datos($datos);
	}

	function evt__disponibles__seleccion($datos)
	{
		$this->seleccionados = $datos;
	}

	function evt__disponibles__agregar()
	{
		if (! empty($this->seleccionados)) {
			$elem = $this->cn()->entidad()->get_elemento();
			foreach ($this->seleccionados as $dato) {
				$this->cn()->entidad()->asignar(array('resultado'=>$dato['resultado'], 'porc_alcanzado'=>100, 'elemento'=>$elem['elemento']));
			}
		}
	}

}
?>