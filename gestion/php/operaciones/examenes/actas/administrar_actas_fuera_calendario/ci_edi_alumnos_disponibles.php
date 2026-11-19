<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Actas
 * 
 */
class ci_edi_alumnos_disponibles extends guarani_pers_ci
{
	protected $s__datos_filtro_alumno;
	protected $s__filtro_alumno;
	protected $s__datos_filtro_comision;
	protected $s__filtro_comision;
	protected $s__comision;

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function resetear_variables()
	{
		unset($this->s__datos_filtro_alumno);
		unset($this->s__filtro_alumno);
		unset($this->s__datos_filtro_comision);
		unset($this->s__filtro_comision);
		unset($this->s__comision);
	}

	/**
	 * Método para la carga del combo del filtro por Comisiónes del Período Lectivo seleccionado y Actividad para 
	 * la que se está creando el Acta.
	 */
	function get_comisiones_periodo_lectivo_actividad($per_lectivo)
	{
		$actividad = $this->controlador()->controlador()->get_actividad_acta();
		$filtro = "sga_comisiones.elemento = $actividad";
		return toba::consulta_php('co_comisiones')->get_comisiones_periodo_lectivo($per_lectivo, $filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Búsqueda Alumnos 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- filtro_por_alumno ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_por_alumno(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_alumno)) {
			$filtro->set_datos($this->s__datos_filtro_alumno);
		}
	}

	function evt__filtro_por_alumno__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_filtro_alumno = $datos;
			$this->s__filtro_alumno = $this->dep('filtro_por_alumno')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_alumno);
			$this->s__filtro_alumno = '';
		}
	}

	function evt__filtro_por_alumno__cancelar()
	{
		unset($this->s__datos_filtro_alumno);
		unset($this->s__filtro_alumno);
	}

	//-----------------------------------------------------------------------------------
	//---- alumnos_generales ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__alumnos_generales(toba_ei_cuadro $cuadro)
	{
		if (!isset($this->s__filtro_alumno)) {
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro_sinfiltro'));
		} else {
			$etiqueta = guarani_etiquetas::get_tabla('sga_alumnos');
			$param = array($etiqueta->get_enf('plural'));
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro_filtrado', $param));

			$filtro = $this->s__filtro_alumno ? $this->s__filtro_alumno : 'true';

			$alumnos_asociados = $this->controlador()->get_alumnos_asociados();
			if (!empty($alumnos_asociados)) {
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($alumnos_asociados, 'sga_alumnos', 'alumno', 'alumno', true);
				// Filtro para que no traiga alumnos repetidos en otras propuestas
				$personas_filtro = guarani_sql::armar_condicion_in($alumnos_asociados, 'sga_alumnos', 'alumno', 'alumno');
				$personas = guarani::co('co_personas')->get_personas_alumnos($personas_filtro);
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($personas, 'sga_alumnos', 'persona', 'persona', true);
			}

			// Sólo alumnos de las propuestas-planes asociadas a la mesa del acta.
			$fecha_llamado_mesa = $this->cn()->entidad()->get_fecha_llamado_mesa();
			$mesa_examen = $this->cn()->entidad()->get_mesa_examen();
			$filtro .= " AND get_plan_version_alumno(sga_alumnos.alumno, '$fecha_llamado_mesa') IN (SELECT	vw_elementos_plan.plan_version
																									FROM	sga_mesas_examen_propuestas
																											JOIN sga_mesas_examen ON sga_mesas_examen_propuestas.mesa_examen = sga_mesas_examen.mesa_examen
																											JOIN vw_elementos_plan ON sga_mesas_examen_propuestas.plan = vw_elementos_plan.plan
																									WHERE 	sga_mesas_examen.elemento = vw_elementos_plan.elemento AND
																											sga_mesas_examen_propuestas.mesa_examen = $mesa_examen)";
			$alumnos = toba::consulta_php('co_alumnos')->get_alumnos_activos($filtro);
			$cuadro->set_datos($alumnos);
		}
	}

	function evt__alumnos_generales__seleccion($seleccion)
	{
		$this->controlador()->agregar_alumno($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Búsqueda Comisión ---------------------------------------------------
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- filtro_por_comision ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_por_comision(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_comision)) {
			$filtro->set_datos($this->s__datos_filtro_comision);
		}
	}

	function evt__filtro_por_comision__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_comision = $datos;
			// El filtro por Comisión se utiliza de forma directa en la sql.
			// Los filtros por Año y Período Lectivo no se utilizan para la sql ya que la Comisión dependerá de los mismos.
			$this->s__comision = $datos['comision']['valor'];
			$this->dep('filtro_por_comision')->columna('anio_academico')->resetear_estado();
			$this->dep('filtro_por_comision')->columna('periodo_lectivo')->resetear_estado();
			$this->dep('filtro_por_comision')->columna('comision')->resetear_estado();
			$this->s__filtro_comision = $this->dep('filtro_por_comision')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_comision);
			unset($this->s__comision);
			$this->s__filtro_comision = '';
		}
	}

	function evt__filtro_por_comision__cancelar()
	{
		unset($this->s__datos_filtro_comision);
		unset($this->s__filtro_comision);
		unset($this->s__comision);
	}

	//-----------------------------------------------------------------------------------
	//---- alumnos_comision -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__alumnos_comision(toba_ei_cuadro $cuadro)
	{
		if (!isset($this->s__comision)) {
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro_sinfiltro'));
		} else {
			$etiqueta = guarani_etiquetas::get_tabla('sga_alumnos');
			$indx_msj = guarani_mensajes::get('800_eof_cuadro_filtrado', array($etiqueta->get_enf('plural')));
			$cuadro->set_eof_mensaje($indx_msj);

			$filtro = $this->s__filtro_comision ? $this->s__filtro_comision : 'true';

			$alumnos_asociados = $this->controlador()->get_alumnos_asociados();
			if (!empty($alumnos_asociados)) {
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($alumnos_asociados, 'sga_alumnos', 'alumno', 'alumno', true);
				// Filtro para que no traiga alumnos repetidos en otras propuestas
				$personas_filtro = guarani_sql::armar_condicion_in($alumnos_asociados, 'sga_alumnos', 'alumno', 'alumno');
				$personas = guarani::co('co_personas')->get_personas_alumnos($personas_filtro);
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($personas, 'sga_alumnos', 'persona', 'persona', true);
			}

			$alumnos = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_comision($this->s__comision, true, $filtro);
			$cuadro->set_datos($alumnos);
		}
	}

	function evt__alumnos_comision__seleccion($seleccion)
	{
		$this->controlador()->agregar_alumno($seleccion);
	}

}
?>