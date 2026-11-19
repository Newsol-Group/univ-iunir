<?php
class ci_edi_operaciones extends guarani_pers_ci
{
	protected $s__filtro_operaciones;
	protected $s__datos_operaciones;
	
	protected $seleccionados;
	
	//-----------------------------------------------------------------------------------
	//---- form_datos_acciones ----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__form_datos_acciones(toba_ei_formulario $form)
	{
		$form->set_datos($this->cn()->entidad()->get_datos_acciones());
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_op_asociadas ----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__cuadro_op_asociadas(toba_ei_cuadro $cuadro)
	{
		$opers = $this->cn()->entidad()->get_datos_operaciones();			
		if (!empty($opers)) {
			$filtro = guarani_sql::armar_condicion_in($opers, '', 'apex_item.parametro_a', 'operacion', false);
			$datos = $this->cn()->get_listado_operaciones($filtro);
			$cuadro->set_datos($datos);	
		}
	}
	
	function evt__cuadro_op_asociadas__eliminar($seleccion)
	{
		$sel = null;
		$opers = $this->cn()->entidad()->get_datos_operaciones();
		foreach ($opers as $op) {
			if ($seleccion['operacion'] == $op['operacion']) {
				$sel = $op['x_dbr_clave'];
			}
		}		
		$this->cn()->entidad()->eliminar_operacion($sel);
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro_operaciones -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__filtro_operaciones(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_operaciones)) {
			$filtro->set_datos($this->s__datos_operaciones);
		}
	}
	
	function evt__filtro_operaciones__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_operaciones = $datos;
			$this->s__filtro_operaciones = $this->dep('filtro_operaciones')->get_sql_where();
		} else {
			unset($this->s__datos_operaciones);
			$this->s__filtro_operaciones = '';
		}
	}
	
	function evt__filtro_operaciones__cancelar()
	{
		unset($this->s__datos_operaciones);
		$this->s__filtro_operaciones = '';
	}	
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_op_disp ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
		
	function conf__cuadro_op_disp(toba_ei_cuadro $cuadro)
	{
		$opers = $this->cn()->entidad()->get_datos_operaciones();
		
		if ((is_null($this->s__filtro_operaciones)) || ($this->s__filtro_operaciones == '')) {
			$filtro = '';			
		} else {
			$filtro = $this->s__filtro_operaciones;
		}
		
		if (!empty($opers)) {
			if ($filtro) {
				$filtro .= ' AND ';
			}
			$filtro .= guarani_sql::armar_condicion_in($opers, '', 'apex_item.parametro_a', 'operacion', true);
		}
		
		//Que sólo liste aquellas que no están asociadas a ninguna acción.
		$operaciones_en_accion = toba::consulta_php('co_operaciones')->get_operaciones_en_accion();
		if ($filtro) {
			$filtro .= ' AND ';
		}
		$filtro .= guarani_sql::armar_condicion_in($operaciones_en_accion, '', 'apex_item.parametro_a', 'operacion', true);
		
		$datos = $this->cn()->get_listado_operaciones($filtro);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_op_disp__agregar()
	{
		if (! empty($this->seleccionados)) {
			foreach ($this->seleccionados as $dat) {
				$this->cn()->entidad()->agregar_operaciones($dat);
			}
		}
	}
	
	function evt__cuadro_op_disp__seleccion($datos)
	{
		$this->seleccionados = $datos;
	}

}
?>