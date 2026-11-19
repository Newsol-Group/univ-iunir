<?php
class ci_edi_alcances_propuestas extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;
	protected $seleccionados;

	//-----------------------------------------------------------------------------------
	//---- asignados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__asignados(toba_ei_formulario_ml $form_ml)
	{
		$resultados = $this->cn()->entidad()->get_propuestas();
		$resultados = rs_ordenar_por_columna($resultados, 'nombre');
		$form_ml->set_datos($resultados);
	}

	function evt__asignados__modificacion($datos)
	{
		foreach ($datos as $k => $dat) {
			$datos[$k] = array('propuesta' => $dat['propuesta'], 'apex_ei_analisis_fila' => 'M');
		}
		$this->cn()->entidad()->set_propuestas($datos);
	}

	function evt__asignados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_propuesta($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(guarani_ei_filtro $filtro)
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
		$asignados = $this->cn()->entidad()->get_propuestas();

		$filtro = '';
		if (!empty($asignados)) {
			$filtro = guarani_sql::armar_condicion_in($asignados, 'sga_propuestas', 'propuesta', 'propuesta', true);
		}
		if (!empty($this->s__filtro)) {
			if ($filtro != '') {
				$filtro .= ' AND ';
			}
			$filtro .= $this->s__filtro;
		}

		$datos = toba::consulta_php('co_propuestas')->get_listado($filtro);
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
				$this->cn()->entidad()->asignar_propuesta($seleccion);
			}
		}
	}

}
?>