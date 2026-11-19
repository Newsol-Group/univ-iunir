<?php
/**
 * 	
 *
 * @package		Administración
 * @subpackage 	Parámetros
 * 
 */
class ci_nav_sel_param_operacion extends ci_nav_parametros
{
	protected $s__operacion = '';
	protected $s__operacion_nombre = '';
	
	protected $s__datos_filtro_op;
	protected $s__filtro_op;
	
	function conf()
	{
		if (!empty($this->s__operacion) && $this->get_id_pantalla() != 'pant_parametros') {
			$this->set_pantalla('pant_parametros');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA: operaciones
	//-----------------------------------------------------------------------------------
		
	//-----------------------------------------------------------------------------------
	//---- filtro_operaciones -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_filtro_op_condicion()
	{
		return isset($this->s__filtro_op) ? $this->s__filtro_op : null;
	}
	
	function conf__filtro_operaciones(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_op)) {
			$filtro->set_datos($this->s__datos_filtro_op);
		}		
	}

	function evt__filtro_operaciones__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_filtro_op = $datos;
			$this->s__filtro_op = $this->dep('filtro_operaciones')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_op);
			$this->s__filtro_op = '';
		}
	}

	function evt__filtro_operaciones__cancelar()
	{
		unset($this->s__datos_filtro_op);
		unset($this->s__filtro_op);
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_operaciones -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_operaciones(guarani_pers_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_operaciones_con_parametros($this->get_filtro_op_condicion());
		$cuadro->set_datos($datos);
		$cuadro->set_eof_mensaje($this->get_msg_eof_cuadro_operaciones());
	}

	function evt__cuadro_operaciones__seleccion($seleccion)
	{
		$this->s__operacion = $seleccion['operacion_codigo'];
		$this->s__operacion_nombre = $seleccion['operacion_nombre'];
		$this->set_pantalla('pant_parametros');
	}
	
	protected function get_msg_eof_cuadro_operaciones()
	{
		$indx_msg = '800_eof_cuadro';
		if (isset($this->s__filtro_op) && ! empty($this->s__filtro_op)) {
			$indx_msg .= '_filtrado';
		}
		$etiqueta = 'Operaciones';
		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA: parámetros
	//-----------------------------------------------------------------------------------
	
	function conf__pant_parametros(toba_ei_pantalla $pantalla)
	{
		$pantalla->set_descripcion("Parámetros de la operación <strong>[$this->s__operacion] $this->s__operacion_nombre</strong>");
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_listado($this->get_filtro_condicion(), $this->s__operacion);
		$cuadro->set_datos($datos);
		$cuadro->set_eof_mensaje($this->get_msg_eof_cuadro());
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->controlador()->controlador()->evt__cuadro__seleccion($seleccion);
	}
	
	protected function get_msg_eof_cuadro()
	{
		$indx_msg = '800_eof_cuadro';
		if (isset($this->s__filtro) && ! empty($this->s__filtro)) {
			$indx_msg .= '_filtrado';
		}
		$etiqueta = 'Parámetros';
		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar_operacion()
	{
		$this->s__operacion = '';
		$this->s__operacion_nombre = '';
		$this->set_pantalla('pant_operaciones');
	}

}
?>