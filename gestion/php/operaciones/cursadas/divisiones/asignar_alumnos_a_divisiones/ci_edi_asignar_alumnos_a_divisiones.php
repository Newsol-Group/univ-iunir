<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Divisiones
 * 
 */
class ci_edi_asignar_alumnos_a_divisiones extends guarani_pers_ci
{
	// Mensaje cuadro
	protected $indx_msg_eof = '800_eof_cuadro';
	
	// Filtro
	protected $s__filtro;
	protected $s__datos;
	protected $s__filtro_turno_preferido = '';
	
	// Componente Visual de Información.
	protected $ev_info;
	protected $seleccionados;
	protected $turno_division;
	protected $s__turno_estricto;
	protected $s__turno_estricto_error;
	
	/**
	 * 
	 * @return boolean	true si hay alumnos con turno distinto al de la división
	 */
	function get_turno_estricto()
	{
		return $this->s__turno_estricto;
	}

	/**
	 * 
	 * @return boolean	true si hay alumnos con turno distinto al de la división y el 
	 *					parámetro 'cur_turno_estricto_alumnos_divisiones' no lo permite
	 */
	function get_turno_estricto_alumnos_divisiones()
	{
		return $this->s__turno_estricto_error;
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial()
	{
		$division = $this->controlador()->get_seleccion_division();
		$division = $division['division'];
		$filtro = "sga_divisiones.division = '$division'";
		$datos = $this->cn()->get_listado($filtro);
		$this->turno_division = $datos[0]['turno'];
		$this->ev_info()->set_datos(current($datos));
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('nombre', 'turno_nombre'));
			$this->ev_info->set_etiqueta('nombre', 'División');
			$this->ev_info->set_etiqueta('turno_nombre', 'Turno');
			$this->ev_info->set_td_etiqueta_width('70px');
		}

		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- asignados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__asignados(toba_ei_cuadro $cuadro)
	{
		$asignados = $this->cn()->entidad()->get_alumnos_asignados();

		if (empty($asignados)) {
			$msg_eof = guarani_mensajes::get($this->indx_msg_eof, array('Alumnos asignados a la División'));
			$cuadro->set_eof_mensaje($msg_eof);
		} else {
			$this->s__turno_estricto = false;
			$this->s__turno_estricto_error = false;
			foreach ($asignados as $clave => $valor) {
				// Si el turno preferido del alumno difiere del turno de la división se agrega a la fila un indicador
				if ($this->turno_preferido_alumno_difiere_turno_division($valor['alumno'])) {
					$this->s__turno_estricto = true;
					if (guarani::parametros()->get_valor('cur_turno_estricto_alumnos_divisiones', $valor['propuesta']) == comunes::si) {
						$tooltip = "El turno preferido del alumno es distinto al de la división, y sólo puede vincularse a divisiones del mismo turno (ver parámetro).";
						$asignados[$clave]['turno_estricto'] = toba_recurso::imagen_toba('error.gif', true, null, null, $tooltip);
						$this->s__turno_estricto_error = true;
					} else {
						$tooltip = "El turno preferido del alumno es distinto al de la división.";
						$asignados[$clave]['turno_estricto'] = toba_recurso::imagen_toba('warning.gif', true, null, null, $tooltip);
					}
				}
			}

			if (!$this->s__turno_estricto) {
				$cuadro->eliminar_columnas(array('turno_estricto'));
			}

			$cuadro->set_datos($asignados);
		}
	}

	function turno_preferido_alumno_difiere_turno_division($alumno)
	{
		$turno_preferido_alumno = toba::consulta_php('co_alumnos')->get_turno_preferido($alumno);
		return !is_null($turno_preferido_alumno) && ($turno_preferido_alumno != $this->turno_division);
	}

	function evt__asignados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_alumno($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}
		$filtro->columna('turno_preferido')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro_turno_preferido = '';

			if ((isset($datos['turno_preferido']))) {

				$turno_preferido_nombre = toba::consulta_php('co_comisiones')->get_nombre_turno_cursada($datos['turno_preferido']['valor']);
				$this->s__filtro_turno_preferido = 'turno_preferido = ' . quote($turno_preferido_nombre);
				$this->dep('filtro')->columna('turno_preferido')->resetear_estado();
			}

			$this->s__filtro = $this->dep('filtro')->get_sql_where();
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		unset($this->s__filtro);
		$this->s__filtro_turno_preferido = '';
	}

	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponibles(toba_ei_cuadro $cuadro)
	{
		$index = $this->indx_msg_eof;

		if (!isset($this->s__filtro)) {
			$index .= '_sinfiltro';
		} else {
			$index .= '_filtrado';
			$asignados = $this->cn()->entidad()->get_alumnos_asignados();
			$filtro = $this->s__filtro;

			if (!empty($asignados)) {
				$filtro .= empty($this->s__filtro) ? ' true AND ' : ' AND ';
				$filtro .= guarani_sql::armar_condicion_in($asignados, 'sga_alumnos', 'alumno', 'alumno', true);
			}

			// Alumnos no asignados a ninguna división y alumnos desasociados en esta edición
			$alumnos = toba::consulta_php('co_alumnos')->get_alumnos_no_asignados_a_division($filtro, $this->s__filtro_turno_preferido);
			$alumnos_desasignados = $this->cn()->entidad()->get_alumnos_desasignados();
			$cuadro->set_datos(array_merge($alumnos, $alumnos_desasignados));
		}

		$msg_eof = guarani_mensajes::get($index, array('Alumnos'));
		$cuadro->set_eof_mensaje($msg_eof);
	}

	function evt__disponibles__seleccion($datos)
	{
		$this->seleccionados = $datos;
	}

	function evt__disponibles__agregar()
	{
		if (!empty($this->seleccionados)) {
			$division = $this->controlador()->get_seleccion_division();
			foreach ($this->seleccionados as $asignacion) {
				// Se agrega alumno a la división
				$this->cn()->entidad()->agregar_alumno($asignacion, $division['division']);
			}
		}
	}

	function resetear_variables()
	{
		unset($this->s__datos);
		unset($this->s__filtro);
		unset($this->s__filtro_turno_preferido);
		unset($this->s__turno_estricto);
	}

}
?>