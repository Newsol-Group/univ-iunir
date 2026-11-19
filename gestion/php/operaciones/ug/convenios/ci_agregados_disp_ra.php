<?php
class ci_agregados_disp_ra extends guarani_pers_ci
{
	protected $responsables_seleccionados;

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro', false);
	}

	function evt__filtro__filtrar($datos)
	{
		$this->filtro_filtrar('filtro', $datos);
	}

	function evt__filtro__cancelar()
	{
		$this->filtro_limpiar('filtro');
	}

	//-----------------------------------------------------------------------------------
	//---- agregados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__agregados(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_responsables_academicas');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural'))));

		$datos = $this->cn()->entidad()->get_datos_ra();
		$cuadro->set_datos($datos);
	}

	function evt__agregados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_datos_ra($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponibles(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_responsables_academicas');
		$instituciones = $this->cn()->entidad()->get_datos_instit_part();
		if (empty($instituciones)) {
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800UNG_seleccionar_instituciones', array($etiqueta->get_enf('plural'))));
		} else {
			$filtro = $this->get_filtro('filtro');
			$indx_msj_eof = empty($filtro) ? '800_eof_cuadro' : '800_eof_cuadro_filtrado';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj_eof, array($etiqueta->get_enf('plural'))));

			$filtro = empty($filtro) ? 'true' : $filtro;

			// Filtro por instituciones participantes.
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($instituciones, 'sga_instituciones', 'institucion', 'institucion');

			$ras_asociadas = $this->cn()->entidad()->get_datos_ra();
			if (! empty($ras_asociadas)) {
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($ras_asociadas, 'sga_responsables_academicas', 'responsable_academica', 'responsable_academica', true);
			}

			$datos = $this->cn()->get_listado_ra($filtro);
			$cuadro->set_datos($datos);
		}
	}

	function evt__disponibles__agregar()
	{
		if (! empty($this->responsables_seleccionados)) {
			foreach ($this->responsables_seleccionados as $ra) {
				$this->cn()->entidad()->set_datos_ra($ra);
			}
		}
	}

	function evt__disponibles__seleccion($seleccion)
	{
		$this->responsables_seleccionados = $seleccion;
	}
}
?>