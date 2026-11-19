<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Divisiones
 * 
 */
class cn_ent_asignar_alumno_a_division extends cn_entidad
{
	//-----------------------------------------------------------------------------------
	//---- Funciones para agregar y eliminar alumnos de la división
	//-----------------------------------------------------------------------------------

	function agregar_alumno($alumno, $division)
	{
		$cursor = $this->set_datos_alumno($alumno, $division);
		$this->tabla('alumno')->set_cursor($cursor);
		$this->set_historial_alumno($division);
		$this->tabla('alumno')->resetear_cursor();
	}

	function eliminar_alumno($alumno)
	{
		$this->tabla('alumno')->modificar_fila($alumno['x_dbr_clave'], array('division' => null));
	}

	function get_alumnos_asignados()
	{
		$alumnos = $this->get_alumnos();
		foreach ($alumnos as $clave => $valor) {
			if (is_null($alumnos[$clave]['division'])) {
				unset($alumnos[$clave]);
			} else {
				$turno = toba::consulta_php('co_alumnos')->get_turno_preferido($alumnos[$clave]['alumno']);
				$alumnos[$clave]['turno_preferido'] = toba::consulta_php('co_comisiones')->get_nombre_turno_cursada($turno);
			}
		}
		return $alumnos;
	}

	function get_alumnos_desasignados()
	{
		$alumnos_desasignados = array();
		foreach ($this->get_alumnos() as $clave => $valor) {
			if (is_null($valor['division'])) {
				$turno_preferido = toba::consulta_php('co_alumnos')->get_turno_preferido($valor['alumno']);
				$registro_desasignado['alumno'] = $valor['alumno'];
				$registro_desasignado['apellido_y_nombre'] = $valor['nombre_completo'];
				$registro_desasignado['documento_principal'] = $valor['documento'];
				$registro_desasignado['legajo'] = $valor['legajo'];
				$registro_desasignado['propuesta_nombre'] = $valor['propuesta_nombre'];
				$registro_desasignado['turno_preferido'] = toba::consulta_php('co_comisiones')->get_nombre_turno_cursada($turno_preferido);
				$alumnos_desasignados[] = $registro_desasignado;
			}
		}
		return $alumnos_desasignados;
	}

	//-----------------------------------------------------------------------------------
	//---- alumnos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_alumnos()
	{
		$datos = $this->tabla('alumno')->get_filas();
		return rs_ordenar_por_columna($datos, 'nombre_completo');
	}

	function set_datos_alumno($alumno, $division)
	{
		// Si el alumno no esta agregado al DT entonces se lo anexa
		if (!in_array($alumno['alumno'], aplanar_matriz($this->tabla('alumno')->get_filas(), 'alumno'))) {
			$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_alumno($alumno['alumno']);
			$this->tabla('alumno')->persistidor()->cargar_con_datos($datos_alumno, true);
		}

		// Se establece la división del alumno
		$id_fila = $this->tabla('alumno')->get_id_fila_condicion(array('alumno' => $alumno['alumno']));
		$this->tabla('alumno')->set_fila_columna_valor(current($id_fila), 'division', $division);
		return current($id_fila);
	}

	//-----------------------------------------------------------------------------------
	//---- alumno_hist_division ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function set_historial_alumno($division)
	{
		$this->tabla('alumno_hist_division')->nueva_fila(array('division' => $division));
	}

	//-----------------------------------------------------------------------------------
	//---- Carga de la relación
	//-----------------------------------------------------------------------------------
	
	/**
	 * Redefinición del método de carga de una entidad.
	 */
	function cargar($id)
	{
		// Se preparan los wheres para las tablas del datos relación
		$where_alumnos = 'sga_alumnos.division = ' . current($id);
		$wheres['alumno'] = $where_alumnos;
		var_dump($wheres);
		// Se carga el datos relación a partir de los wheres
		$this->dep('datos')->persistidor()->cargar_con_wheres($wheres);
		$this->cargar_etiquetas();
	}

	/**
	 * Se redefine el método porque el original retorna el identificador del registro agregado en la tabla cabecera de la relación.
	 * En este caso, la tabla cabecera posee más de un registro.
	 * 
	 */
	function sincronizar()
	{
		$this->dep('datos')->sincronizar();
	}

}
?>