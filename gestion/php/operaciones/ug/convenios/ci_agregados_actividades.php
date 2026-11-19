<?php
/**
 *
 *
 * @package 	Unidades de Gestion
 * @subpackage 	Convenios
 * 
 */
class ci_agregados_actividades extends ci_agregados_disp
{
	protected $actividades_seleccionadas;

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
		$etiqueta = guarani_etiquetas::get_tabla('sga_elementos');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural'))));

		$datos = $this->cn()->entidad()->get_datos_act();
		$cuadro->set_datos($datos);
	}

	function evt__agregados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_datos_act($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponibles(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_elementos');
		$propuestas = $this->cn()->entidad()->get_datos_propuestas(array('es_externa' => comunes::no));
		if (empty($propuestas)) {
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800UNG_seleccionar_propuestas', array($etiqueta->get_enf('plural'))));
		} else {
			$filtro = $this->get_filtro('filtro');
			$indx_msj_eof = empty($filtro) ? '800_eof_cuadro' : '800_eof_cuadro_filtrado';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj_eof, array($etiqueta->get_enf('plural'))));

			$filtro = empty($filtro) ? 'true' : $filtro;

			// Filtro por propuestas.
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($propuestas, 'vw_actividades_plan', 'propuesta', 'propuesta');

			$actividades_asociadas = $this->cn()->entidad()->get_datos_act();
			if (! empty($actividades_asociadas)) {
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($actividades_asociadas, 'vw_actividades_plan', 'elemento', 'elemento', true);
			}

			$datos = $this->cn()->get_listado_actividades($filtro);
			$cuadro->set_datos($datos);
		}
	}

	function evt__disponibles__seleccion($datos)
	{		
		$this->actividades_seleccionadas = $datos;
	}

	function evt__disponibles__agregar()
	{
		if (!empty($this->actividades_seleccionadas)) {
			foreach ($this->actividades_seleccionadas as $actividad) {
				$this->cn()->entidad()->set_datos_act($actividad);
			}			
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Recupera las propuestas asociadas al convenio.
	 *
	 * @return array
	 */
	function get_propuestas()
	{
		$propuestas_asociadas = $this->cn()->entidad()->get_datos_propuestas();
		$filtro = guarani_sql::armar_condicion_in($propuestas_asociadas, 'sga_propuestas', 'propuesta', 'propuesta');
		return toba::consulta_php('co_propuestas')->get_propuestas_nombres($filtro);
	}
}
?>