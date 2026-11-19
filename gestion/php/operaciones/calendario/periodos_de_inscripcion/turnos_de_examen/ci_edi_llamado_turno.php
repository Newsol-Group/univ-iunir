<?php
class ci_edi_llamado_turno extends ci_edicion_simple
{
	protected $s__filtro;
	protected $s__datos;
	protected $llamados_seleccionados;
	
	//-----------------------------------------------------------------------------------
	//---- turno_info -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$turno = $this->controlador->get_turno_examen();
		$datos_turno = $this->cn()->co()->get_datos_turno($turno);
		$this->controlador()->ev_info()->set_datos($datos_turno);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
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
		unset($this->s__filtro);
	}

	function conf__filtro(toba_ei_filtro $filtro)
	{	
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}		
	}

	//-----------------------------------------------------------------------------------
	//---- disponible -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponible(cuadro_seleccion_multiple_sin_todos $cuadro)
	{
		if (!isset($this->s__filtro)) {
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro_sinfiltro'));
		} else{
			$cuadro->set_datos($this->cn()->entidad()->get_llamados_disponibles($this->s__filtro));
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro_filtrado', array('Llamados')));
		}
		
	}

	function evt__disponible__seleccion($seleccion)
	{
		$this->llamados_seleccionados = $seleccion;
	}
	
	function evt__disponible__agregar()
	{
		$this->cn()->entidad()->agregar_vinculados($this->llamados_seleccionados);
	}
	
	//-----------------------------------------------------------------------------------
	//---- asociados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__asociados(guarani_pers_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->cn()->entidad()->get_llamados_asociados());
	}

	function evt__asociados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_asociado($seleccion);
	}
	
}
?>