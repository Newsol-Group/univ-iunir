<?php
/**
 * 
 * @package Encuestas
 */
class ci_edi_actividades extends guarani_pers_ci
{
	protected $actividades_seleccionadas;

	function resetear()
	{
		unset($this->s__datos_filtros);
		unset($this->s__filtros);
	}

	//-----------------------------------------------------------------------------------
	//---- actividades_asociadas --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__actividades_asociadas(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_elementos');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural'))));

		$datos = $this->cn()->entidad()->get_datos_actividades();
		$cuadro->set_datos($datos);
	}

	function evt__actividades_asociadas__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_actividad($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_actividades -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_actividades(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_actividades', false);
	}

	function evt__filtro_actividades__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_actividades', $datos);
	}

	function evt__filtro_actividades__cancelar()
	{
		$this->filtro_limpiar('filtro_actividades');
	}

	//-----------------------------------------------------------------------------------
	//---- actividades_disponibles ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__actividades_disponibles(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('filtro_actividades');
		$etiqueta = guarani_etiquetas::get_tabla('sga_elementos');
		$indx_msj_eof = empty($filtro) ? '800_eof_cuadro' : '800_eof_cuadro_filtrado';
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj_eof, array($etiqueta->get_enf('plural'))));

		$filtro = empty($filtro) ? 'true' : $filtro;
		$actividades_asociadas = $this->cn()->entidad()->get_datos_actividades();
		if (!empty($actividades_asociadas)) {
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($actividades_asociadas, 'sga_elementos', 'elemento', 'elemento', true);
		}

		$datos = toba::consulta_php('co_elementos')->get_listado_actividades($filtro);
		$cuadro->set_datos($datos);
	}

	function evt__actividades_disponibles__seleccion($seleccion)
	{
		$this->actividades_seleccionadas = $seleccion;
	}

	function evt__actividades_disponibles__agregar()
	{
		if (!empty($this->actividades_seleccionadas)) {
			foreach ($this->actividades_seleccionadas as $actividad) {
				$this->cn()->entidad()->agregar_actividad($actividad);
			}
		}
	}
}
?>