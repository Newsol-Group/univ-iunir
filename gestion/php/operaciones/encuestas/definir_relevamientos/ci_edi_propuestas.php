<?php
/**
 * 
 * @package Encuestas
 */
class ci_edi_propuestas extends guarani_pers_ci
{
	protected $propuestas_seleccionadas;

	function resetear()
	{
		unset($this->s__datos_filtros);
		unset($this->s__filtros);
	}

	//-----------------------------------------------------------------------------------
	//---- propuestas_asociadas ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__propuestas_asociadas(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_propuestas');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural'))));

		$datos = $this->cn()->entidad()->get_datos_propuestas();
		$cuadro->set_datos($datos);
	}

	function evt__propuestas_asociadas__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_propuesta($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_propuestas ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_propuestas(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_propuestas', false);
	}

	function evt__filtro_propuestas__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_propuestas', $datos);
	}

	function evt__filtro_propuestas__cancelar()
	{
		$this->filtro_limpiar('filtro_propuestas');
	}

	//-----------------------------------------------------------------------------------
	//---- propuestas_disponibles -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__propuestas_disponibles(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('filtro_propuestas');
		$etiqueta = guarani_etiquetas::get_tabla('sga_propuestas');
		$indx_msj_eof = empty($filtro) ? '800_eof_cuadro' : '800_eof_cuadro_filtrado';
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj_eof, array($etiqueta->get_enf('plural'))));

		$filtro = empty($filtro) ? 'true' : $filtro;
		$propuestas_asociadas = $this->cn()->entidad()->get_datos_propuestas();
		if (!empty($propuestas_asociadas)) {
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($propuestas_asociadas, 'sga_propuestas', 'propuesta', 'propuesta', true);
		}

		$datos = toba::consulta_php('co_propuestas')->get_listado($filtro);
		$cuadro->set_datos($datos);
	}

	function evt__propuestas_disponibles__seleccion($seleccion)
	{
		$this->propuestas_seleccionadas = $seleccion;
	}

	function evt__propuestas_disponibles__agregar()
	{
		if (!empty($this->propuestas_seleccionadas)) {
			foreach ($this->propuestas_seleccionadas as $propuesta) {
				$this->cn()->entidad()->agregar_propuesta($propuesta);
			}
		}
	}
}
?>