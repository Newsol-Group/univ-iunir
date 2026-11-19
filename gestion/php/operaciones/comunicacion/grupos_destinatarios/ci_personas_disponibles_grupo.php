<?php
/**
 *
 *
 * @package 	Comunicación
 * @subpackage
 * 
 */
class ci_personas_disponibles_grupo extends guarani_pers_ci
{
	// Filtro alumnos.
	protected $s__filtro_alumnos;
	protected $s__datos_filtro_alumnos;
	protected $s__filtro_legajo_propuesta_ubicacion;

	// Filtro docentes.
	protected $s__filtro_docentes;
	protected $s__datos_filtro_docentes;

	// Alumnos y docentes seleccionados.
	protected $alumnos_seleccionados;
	protected $docentes_seleccionados;

	//-----------------------------------------------------------------------------------
	//---- PANTALLA ALUMNOS -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//---- filtro_alumnos ---------------------------------------------------------------

	function conf__filtro_alumnos(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_alumnos)) {
			$filtro->set_datos($this->s__datos_filtro_alumnos);
		}
		$filtro->columna('legajo')->set_condicion_fija('contiene');
		$filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro_alumnos__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_alumnos = $datos;
			$this->s__filtro_legajo_propuesta_ubicacion = 'true';

			if (isset($datos['legajo'])) {
				$this->s__filtro_legajo_propuesta_ubicacion .= ' AND ' . guarani_ei_filtro::get_expresion('sga_alumnos.legajo', $datos['legajo']['valor'], $datos['legajo']['condicion_cs'], $datos['legajo']['condicion_as']);
				$this->dep('filtro_alumnos')->columna('legajo')->resetear_estado();
			}

			if (isset($datos['propuesta'])) {
				$this->s__filtro_legajo_propuesta_ubicacion .= " AND sga_alumnos.propuesta = {$datos['propuesta']['valor']}";
				$this->dep('filtro_alumnos')->columna('propuesta')->resetear_estado();
			}

			if (isset($datos['ubicacion'])) {
				$this->s__filtro_legajo_propuesta_ubicacion .= " AND sga_alumnos.ubicacion = {$datos['ubicacion']['valor']}";
				$this->dep('filtro_alumnos')->columna('ubicacion')->resetear_estado();
			}

			$this->s__filtro_alumnos = $this->dep('filtro_alumnos')->get_sql_where();
		} else {
			unset($this->s__datos_alumnos);
			$this->s__filtro_alumnos = '';
			$this->s__filtro_legajo_propuesta_ubicacion = '';
		}
	}

	function evt__filtro_alumnos__cancelar()
	{
		unset($this->s__datos_alumnos);
		unset($this->s__filtro_alumnos);
		unset($this->s__filtro_legajo_propuesta_ubicacion);
	}

	//---- cuadro_alumnos ---------------------------------------------------------------

	function conf__cuadro_alumnos(toba_ei_cuadro $cuadro)
	{
		if (!isset($this->s__filtro_alumnos)) {
			$this->indx_msg_eof = '800_eof_cuadro_sinfiltro';
		} else {
			$this->indx_msg_eof = '800_eof_cuadro_filtrado';

			$filtro = empty($this->s__filtro_alumnos) ? 'true' : $this->s__filtro_alumnos;
			$personas_asociadas = $this->cn()->entidad()->get_personas_asociadas();
			$filtro .= ! empty($personas_asociadas) ? ' AND ' . guarani_sql::armar_condicion_not_in($personas_asociadas, 'mdp_personas', 'persona', 'persona') : '';

			$datos = $this->cn()->get_listado_alumnos($filtro, $this->s__filtro_legajo_propuesta_ubicacion);
			$cuadro->set_datos($datos);
		}

		$etiqueta = guarani_etiquetas::get_tabla('sga_alumnos');
		$indx_msj = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural')));
		$cuadro->set_eof_mensaje($indx_msj);
	}

	function evt__cuadro_alumnos__seleccion($seleccion)
	{
		$this->alumnos_seleccionados = $seleccion;
	}

	function evt__cuadro_alumnos__agregar()
	{
		$this->cn()->entidad()->agregar_personas_grupo($this->alumnos_seleccionados);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DOCENTES ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//---- filtro_docentes --------------------------------------------------------------

	function conf__filtro_docentes(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_docentes)) {
			$filtro->set_datos($this->s__datos_filtro_docentes);
		}
		$filtro->columna('catedra')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}

	function evt__filtro_docentes__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_docentes = $datos;
			$filtro_catedra = '';
			$filtro_actividad = '';

			if (isset($datos['catedra'])) {
				$filtro_catedra = " AND sga_docentes.docente IN (	SELECT	sga_catedras_docentes.docente
																	FROM	sga_catedras_docentes
																	WHERE	sga_catedras_docentes.catedra = {$datos['catedra']['valor']})";
				$this->dep('filtro_docentes')->columna('catedra')->resetear_estado();
			}

			if (isset($datos['actividad'])) {
				$expresion_nombre = guarani_ei_filtro::get_expresion('sga_elementos.nombre', $datos['actividad']['valor'], $datos['actividad']['condicion_cs'], $datos['actividad']['condicion_as']);
				$expresion_codigo = guarani_ei_filtro::get_expresion('sga_elementos.codigo', $datos['actividad']['valor'], $datos['actividad']['condicion_cs'], $datos['actividad']['condicion_as']);
				$filtro_actividad = " AND sga_docentes.docente IN  (SELECT	sga_docentes_comision.docente
																	FROM	sga_docentes_comision, sga_comisiones, sga_periodos_lectivos, sga_periodos, sga_elementos
																	WHERE	sga_docentes_comision.comision = sga_comisiones.comision
																	AND		sga_comisiones.elemento = sga_elementos.elemento
																	AND		sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
																	AND		sga_periodos_lectivos.periodo = sga_periodos.periodo
																	AND		sga_periodos.fecha_fin >= CURRENT_DATE
																	AND	   ($expresion_nombre OR $expresion_codigo))";
				$this->dep('filtro_docentes')->columna('actividad')->resetear_estado();
			}

			$this->s__filtro_docentes = "{$this->dep('filtro_docentes')->get_sql_where()} $filtro_catedra $filtro_actividad";
		} else {
			unset($this->s__datos_docentes);
			$this->s__filtro_docentes = '';
		}
	}

	function evt__filtro_docentes__cancelar()
	{
		unset($this->s__datos_docentes);
		unset($this->s__filtro_docentes);
	}

	//---- cuadro_docentes --------------------------------------------------------------

	function conf__cuadro_docentes(toba_ei_cuadro $cuadro)
	{
		if (!isset($this->s__filtro_docentes)) {
			$this->indx_msg_eof = '800_eof_cuadro_sinfiltro';
		} else {
			$this->indx_msg_eof = '800_eof_cuadro_filtrado';

			$filtro = empty($this->s__filtro_docentes) ? 'true' : $this->s__filtro_docentes;
			$personas_asignadas = $this->cn()->entidad()->get_personas_asociadas();
			$filtro .= ! empty($personas_asignadas) ? ' AND ' . guarani_sql::armar_condicion_not_in($personas_asignadas, 'vw_personas', 'persona', 'persona') : '';

			$datos = $this->cn()->get_listado_docentes($filtro);
			$cuadro->set_datos($datos);
		}

		$etiqueta = guarani_etiquetas::get_tabla('sga_docentes');
		$indx_msj = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural')));
		$cuadro->set_eof_mensaje($indx_msj);
	}

	function evt__cuadro_docentes__seleccion($seleccion)
	{
		$this->docentes_seleccionados = $seleccion;
	}

	function evt__cuadro_docentes__agregar()
	{
		$this->cn()->entidad()->agregar_personas_grupo($this->docentes_seleccionados);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		$msj_confirm_filtrar = guarani_mensajes::get('800_confirmar_filtro_pierde_datos');

		if ($this->get_id_pantalla() == 'alumnos') {
			$id_js_filtro = $this->dependencia('filtro_alumnos')->get_id_objeto_js();
			$id_js_cuadro = $this->dependencia('cuadro_alumnos')->get_id_objeto_js();
		} else {
			$id_js_filtro = $this->dependencia('filtro_docentes')->get_id_objeto_js();
			$id_js_cuadro = $this->dependencia('cuadro_docentes')->get_id_objeto_js();
		}

		echo "
			{$id_js_filtro}.evt__filtrar = function() {
				// La advertencia sólo debe emitirse en caso de que el cuadro tenga filas seleccionadas.
				filas_seleccionadas = {$id_js_cuadro}.get_ids_seleccionados('seleccion');
				if (filas_seleccionadas.length > 0) {
					return confirmar_filtrado();
				}
			}

			function confirmar_filtrado(proyecto, operacion, url, es_popup) {
				if (! es_popup || typeof(es_popup) == 'undefined') {
					resp = confirm('$msj_confirm_filtrar');
					if (! resp) {
						return false;
					}
				}
				return true;
			}
		";
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function resetear_variables()
	{
		unset($this->s__filtro_alumnos);
		unset($this->s__datos_filtro_alumnos);
		unset($this->s__filtro_legajo_propuesta_ubicacion);

		unset($this->s__filtro_docentes);
		unset($this->s__datos_filtro_docentes);
	}
}