<?php
class ci_agregados_disp_propcurr extends guarani_pers_ci
{
	protected $propuestas_seleccionadas;

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
		$etiqueta = guarani_etiquetas::get_tabla('sga_propuestas');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural'))));

		$datos = $this->cn()->entidad()->get_datos_propuestas();
		foreach ($datos as $clave => $valor) {
			$datos[$clave]['nombre'] = ($valor['es_externa'] == comunes::si) ? $valor['propuesta_ext_nombre'] : $valor['propuesta_nombre'];
			$datos[$clave]['propuesta_tipo_desc'] = ($valor['es_externa'] == comunes::si) ? $valor['propuesta_tipo_ext_desc'] : $valor['propuesta_tipo_desc'];
			$datos[$clave]['institucion_nombre'] = ($valor['es_externa'] == comunes::si) ? $valor['institucion_ext_nombre'] : $valor['institucion_nombre'];
		}
		$cuadro->set_datos($datos);
	}

	function evt__agregados__eliminar($seleccion)
	{
		$datos = $this->cn()->entidad()->get_datos_propuesta($seleccion);
		$this->cn()->entidad()->eliminar_datos_prop_curr($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponibles(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_propuestas');
		$instituciones = $this->cn()->entidad()->get_datos_instit_part();
		if (empty($instituciones)) {
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800UNG_seleccionar_instituciones', array($etiqueta->get_enf('plural'))));
		} else {
			$filtro = $this->get_filtro('filtro');
			$indx_msj_eof = empty($filtro) ? '800_eof_cuadro' : '800_eof_cuadro_filtrado';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj_eof, array($etiqueta->get_enf('plural'))));

			$filtro = empty($filtro) ? 'true' : $filtro;
			// Filtro por instituciones participantes.
			$filtro .= ' AND EXISTS (	SELECT	*
										FROM	sga_propuestas_ra,
												sga_responsables_academicas
										WHERE	sga_propuestas_ra.responsable_academica = sga_responsables_academicas.responsable_academica AND
												sga_propuestas_ra.propuesta = sga_propuestas.propuesta AND ' . guarani_sql::armar_condicion_in($instituciones, 'sga_responsables_academicas', 'institucion', 'institucion') . ')';
			$propuestas_asociadas = $this->cn()->entidad()->get_datos_propuestas(array('es_externa' => comunes::no));
			if (! empty($propuestas_asociadas)) {
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($propuestas_asociadas, 'sga_propuestas', 'propuesta', 'propuesta', true);
			}

			$this->dep('filtro')->columna('nombre')->set_expresion('sga_propuestas_ext.nombre');
			$filtro_ext = empty($this->s__filtros['filtro']) ? 'true' : $this->dep('filtro')->get_sql_where();
			// Filtro por instituciones participantes.
			$filtro_ext .= ' AND ' . guarani_sql::armar_condicion_in($instituciones, 'sga_instituciones', 'institucion', 'institucion');
			$propuestas_ext_asociadas = $this->cn()->entidad()->get_datos_propuestas(array('es_externa' => comunes::si));
			if (! empty($propuestas_ext_asociadas)) {
				$filtro_ext .= ' AND ' . guarani_sql::armar_condicion_in($propuestas_asociadas, 'sga_propuestas_ext', 'propuesta_ext', 'propuesta_ext', true);
			}

			$datos = $this->cn()->get_listado_prop($filtro, $filtro_ext);
			$cuadro->set_datos($datos);
		}
	}

	function evt__disponibles__agregar($seleccion)
	{
		if (! empty($this->propuestas_seleccionadas)) {
			foreach ($this->propuestas_seleccionadas as $propuesta) {
				if (empty($propuesta['propuesta'])) {
					$propuesta['propuesta'] = null;
					$propuesta['es_externa'] = comunes::si;
				} else {
					$propuesta['propuesta_ext'] = null;
					$propuesta['es_externa'] = comunes::no;
				}
				$this->cn()->entidad()->set_datos_prop_curr($propuesta);
			}
		}
	}

	function evt__disponibles__seleccion($seleccion)
	{
		$this->propuestas_seleccionadas = $seleccion;
	}
}
?>