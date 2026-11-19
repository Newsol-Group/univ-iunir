<?php
/**
 * 	
 *
 * @package		Administración
 * @subpackage 	Parámetros
 * 
 */
class ci_nav_sel_param_modulo extends ci_nav_parametros
{
	protected $s__modulo = '';
	
	protected $s__datos_filtro_modulos;
	protected $s__filtro_modulos;
	
	function conf()
	{
		if (!empty($this->s__modulo) && $this->get_id_pantalla() != 'pant_parametros') {
			$this->set_pantalla('pant_parametros');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA: Módulos
	//-----------------------------------------------------------------------------------
		
	//-----------------------------------------------------------------------------------
	//---- filtro_modulos -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_filtro_modulo_condicion()
	{
		return isset($this->s__filtro_modulos) ? $this->s__filtro_modulos : null;
	}
	
	function conf__filtro_modulos(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_modulos)) {
			$filtro->set_datos($this->s__datos_filtro_modulos);
		}		
	}

	function evt__filtro_modulos__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_filtro_modulos = $datos;
			$this->s__filtro_modulos = $this->dep('filtro_modulos')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_modulos);
			$this->s__filtro_modulos = '';
		}
	}

	function evt__filtro_modulos__cancelar()
	{
		unset($this->s__datos_filtro_modulos);
		unset($this->s__filtro_modulos);
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_modulos -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_modulos(guarani_pers_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_modulos_con_parametros($this->get_filtro_modulo_condicion());
		$cuadro->set_datos($datos);
		$cuadro->set_eof_mensaje($this->get_msg_eof_cuadro_modulos());
	}

	function evt__cuadro_modulos__seleccion($seleccion)
	{
		$this->s__modulo = $seleccion['modulo'];
		$this->set_pantalla('pant_parametros');
	}
	
	protected function get_msg_eof_cuadro_modulos()
	{
		$indx_msg = '800_eof_cuadro';
		if (isset($this->s__filtro_modulos) && ! empty($this->s__filtro_modulos)) {
			$indx_msg .= '_filtrado';
		}
		$etiqueta = 'Módulos';
		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA: parámetros
	//-----------------------------------------------------------------------------------
	
	function conf__pant_parametros(toba_ei_pantalla $pantalla)
	{
		$pantalla->set_descripcion("Parámetros del módulo <strong>[$this->s__modulo]</strong>");
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_listado($this->get_filtro_condicion(), $this->s__modulo);
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

	function evt__cancelar_modulo()
	{
		$this->s__modulo = '';
		$this->s__modulo_nombre = '';
		$this->set_pantalla('pant_modulos');
	}

}
?>