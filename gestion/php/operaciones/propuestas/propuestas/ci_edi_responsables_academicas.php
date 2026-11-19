<?php
/**
 * 
 * @package Propuestas Formativas
 */
class ci_edi_responsables_academicas extends ci_edicion_entidad
{
	protected $ras_seleccionadas;

	function inicializar($parametro=array())
	{
		parent::inicializar();
		$this->componentes_eventos_a_inhabilitar['cuadro'] = array('seleccion', 'agregar', 'eliminar');
	}

	function post_configurar()
	{
		if (! $this->cn()->entidad()->es_editable()) {
			$this->inhabilitar_componentes_eventos();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- ras_asociadas ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__ras_asociadas(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_responsables_academicas');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural'))));

		$datos = $this->cn()->entidad()->get_datos_ras();
		$cuadro->set_datos($datos);
	}

	function evt__ras_asociadas__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_ra($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_ras -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_ras(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_ras', false);
	}

	function evt__filtro_ras__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_ras', $datos);
	}

	function evt__filtro_ras__cancelar()
	{
		$this->filtro_limpiar('filtro_ras');
	}

	//-----------------------------------------------------------------------------------
	//---- ras_disponibles --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__ras_disponibles(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('filtro_ras');
		$etiqueta = guarani_etiquetas::get_tabla('sga_responsables_academicas');
		$indx_msj_eof = empty($filtro) ? '800_eof_cuadro' : '800_eof_cuadro_filtrado';
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj_eof, array($etiqueta->get_enf('plural'))));

		$filtro = empty($filtro) ? 'true' : $filtro;
		$ras_asociadas = $this->cn()->entidad()->get_datos_ras();
		if (! empty($ras_asociadas)) {
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($ras_asociadas, 'sga_responsables_academicas', 'responsable_academica', 'responsable_academica', true);
		}

		$datos = toba::consulta_php('co_responsables_academicas')->get_listado($filtro);
		$cuadro->set_datos($datos);
	}

	function evt__ras_disponibles__seleccion($seleccion)
	{
		$this->ras_seleccionadas = $seleccion;
	}

	function evt__ras_disponibles__agregar()
	{
		if (! empty($this->ras_seleccionadas)) {
			foreach ($this->ras_seleccionadas as $ra) {
				$this->cn()->entidad()->agregar_ra($ra);
			}
		}
	}
}
?>