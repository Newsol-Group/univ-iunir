<?php

class ci_agregados_disp extends guarani_pers_ci
{
	protected $instituciones_seleccionadas;

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(guarani_ei_filtro $filtro)
	{
		$filtro->columna('pais')->set_estado(array('condicion' => 'es_igual_a', 'valor' => persona::pais_argentina));
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
		$etiqueta = guarani_etiquetas::get_tabla('sga_instituciones');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural'))));

		$datos = $this->cn()->entidad()->get_datos_instit_part();
		$cuadro->set_datos($datos);
	}

	function evt__agregados__eliminar($seleccion)
	{
		$datos = $this->cn()->entidad()->get_datos_instit_part($seleccion);
		$this->cn()->entidad()->eliminar_datos_instit_part($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponibles(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('filtro');
		$etiqueta = guarani_etiquetas::get_tabla('sga_instituciones');
		$indx_msj_eof = empty($filtro) ? '800_eof_cuadro' : '800_eof_cuadro_filtrado';
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj_eof, array($etiqueta->get_enf('plural'))));

		$filtro = empty($filtro) ? 'true' : $filtro;

		// Filtro por tipo de instituciones.
		$datos_convenio = $this->cn()->entidad()->get_datos_convenios();
		if ($datos_convenio['realizado_con'] == convenio::realizado_con_nacionales) {
			$filtro .= ' AND mug_paises.pais = ' . persona::pais_argentina;
		}

		$instituciones_asociadas = $this->cn()->entidad()->get_datos_instit_part();
		if (! empty($instituciones_asociadas)) {
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($instituciones_asociadas, 'sga_instituciones', 'institucion', 'institucion', true);
		}

		$datos = $this->cn()->get_listado_instituciones($filtro);
		$cuadro->set_datos($datos);
	}

	function evt__disponibles__agregar()
	{
		if (!empty($this->instituciones_seleccionadas)) {
			foreach ($this->instituciones_seleccionadas as $institucion) {
				$this->cn()->entidad()->set_datos_instit_part($institucion);		
			}	
		}	
	}
	
	function evt__disponibles__seleccion($seleccion)
	{
		$this->instituciones_seleccionadas = $seleccion;
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Recupera los paises válidos para el tipo de convenio.
	 *
	 * @return array
	 */
	function get_paises()
	{
		$datos_convenio = $this->cn()->entidad()->get_datos_convenios();
		$filtro = '';
		if ($datos_convenio['realizado_con'] == convenio::realizado_con_nacionales) {
			$filtro = 'mug_paises.pais = ' . persona::pais_argentina;
		}
		return toba::consulta_php('co_mug')->get_paises(null, $filtro);
	}
}
?>