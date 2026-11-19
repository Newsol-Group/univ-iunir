<?php
/**
 * 
 * @package Propuestas Formativas
 */
class ci_edi_planes extends guarani_pers_ci
{
	protected $planes_seleccionados;

	//-----------------------------------------------------------------------------------
	//---- planes_asociados -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__planes_asociados(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_planes');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural'))));

		$datos = $this->cn()->entidad()->get_datos_planes();
		$cuadro->set_datos($datos);
	}

	function evt__planes_asociados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_plan($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_planes ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_planes(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_planes', false);
	}

	function evt__filtro_planes__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_planes', $datos);
	}

	function evt__filtro_planes__cancelar()
	{
		$this->filtro_limpiar('filtro_planes');
	}

	//-----------------------------------------------------------------------------------
	//---- planes_disponibles -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__planes_disponibles(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('filtro_planes');
		$etiqueta = guarani_etiquetas::get_tabla('sga_planes');
		$indx_msj_eof = empty($filtro) ? '800_eof_cuadro' : '800_eof_cuadro_filtrado';
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj_eof, array($etiqueta->get_enf('plural'))));

		$filtro = empty($filtro) ? 'true' : $filtro;
		$planes_asociados = $this->cn()->entidad()->get_datos_planes();
		if (! empty($planes_asociados)) {
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($planes_asociados, 'sga_planes', 'plan', 'plan', true);
		}

		$propuesta_origen = $this->controlador()->controlador()->get_propuesta();
		$filtro .= " AND sga_planes.propuesta <> $propuesta_origen";
		$filtro .= " AND sga_planes.propuesta NOT IN (	SELECT	sga_propuestas_relacion.propuesta
														FROM	sga_propuestas_relacion,
																sga_propuestas_relacion_grupo,
																sga_propuestas_relacion_plan,
																sga_planes as p
														WHERE	sga_propuestas_relacion.relacion = sga_propuestas_relacion_grupo.relacion AND
																sga_propuestas_relacion_grupo.relacion_grupo = sga_propuestas_relacion_plan.relacion_grupo AND
																sga_propuestas_relacion_plan.plan = p.plan AND
																p.propuesta = $propuesta_origen)
		";

		$datos = toba::consulta_php('co_propuestas_relaciones')->get_listado_planes($filtro);
		$cuadro->set_datos($datos);
	}

	function evt__planes_disponibles__seleccion($seleccion)
	{
		$this->planes_seleccionados = $seleccion;
	}

	function evt__planes_disponibles__agregar()
	{
		if (! empty($this->planes_seleccionados)) {
			foreach ($this->planes_seleccionados as $plan) {
				$this->cn()->entidad()->agregar_plan($plan);
			}
		}
	}
}
?>