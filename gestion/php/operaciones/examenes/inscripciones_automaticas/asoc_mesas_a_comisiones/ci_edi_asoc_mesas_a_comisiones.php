<?php
/**
 *
 * 
 * @package     Exámenes
 * @subpackage     Inscripciones
 * 
 */
class ci_edi_asoc_mesas_a_comisiones extends guarani_pers_ci
{
	protected $indx_msg_eof = '800_eof_cuadro';
	
	protected $s__filtro;
	protected $s__datos;
	
	protected $seleccionados;
	
	// Componente Visual de Información.
	protected $ev_info;

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial()
	{
		$datos_llamado = $this->cn()->entidad()->get_datos();
		$llamado_mesa = $datos_llamado['llamado_mesa'];
		$filtro = "sga_llamados_mesa.llamado_mesa = '$llamado_mesa'";
		$datos = $this->cn()->get_listado($filtro);
		$this->ev_info()->set_datos(current($datos));
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('codigo_nombre_actividad', 'anio_academico', 'nombre_turno_examen',
				'nombre', 'nombre_llamado', 'fecha_formato_visual_sin_segundos', 'instancias_nombre',
				'modalidades_nombre', 'catedra_nombre', 'nombre_ubicacion')
			);

			$this->ev_info->set_columnas_fila(array('codigo_nombre_actividad', 'anio_academico', 'nombre_turno_examen'), 1);
			$this->ev_info->set_columnas_fila(array('nombre', 'nombre_llamado', 'fecha_formato_visual_sin_segundos'), 2);
			$this->ev_info->set_columnas_fila(array('nombre_ubicacion', 'modalidades_nombre', 'catedra_nombre'), 3);
			$this->ev_info->set_columnas_fila(array('instancias_nombre'), 4);

			$this->ev_info->set_max_columnas_por_fila(3);

			$this->ev_info->set_etiqueta('codigo_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_turno_examen', 'Turno');

			$this->ev_info->set_etiqueta('nombre', 'Mesa');
			$this->ev_info->set_etiqueta('nombre_llamado', 'Llamado');
			$this->ev_info->set_etiqueta('fecha_formato_visual_sin_segundos', 'Fecha');

			$this->ev_info->set_etiqueta('instancias_nombre', 'Instancia');
			$this->ev_info->set_etiqueta('modalidades_nombre', 'Modalidad');
			$this->ev_info->set_etiqueta('catedra_nombre', 'Cátedra');

			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
		}

		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- asignados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__asignados(toba_ei_cuadro $cuadro)
	{
		$asignados = $this->cn()->entidad()->get_asociaciones();

		if (!empty($asignados)) {
			$datos_llamado = $this->cn()->entidad()->get_datos();
			$llamado_mesa = $datos_llamado['llamado_mesa'];
			$filtro = guarani_sql::armar_condicion_in($asignados, 'vw_comisiones', 'comision', 'comision', false);

			$datos = toba::consulta_php('co_comisiones')->get_comisiones_asociables_a_mesa($llamado_mesa, $filtro);
			$cuadro->set_datos($datos);
		} else {
			$msg_eof = guarani_mensajes::get($this->indx_msg_eof, array('Comisiones asociadas a la Mesa de Examen'));
			$cuadro->set_eof_mensaje($msg_eof);
		}
	}

	function evt__asignados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_fila($seleccion);
	}

	function conf_evt__asignados__eliminar(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->cn()->entidad()->get_asociaciones();
		/**
		 * Se podrán eliminar todas las comisiones que fueron asociadadas siempre y cuando 
		 * la misma no haya sido procesada (sga_insc_auto_examen.cantidad = 0)
		 */
		if ((isset($datos[$fila]['cantidad'])) && ($datos[$fila]['cantidad'] != 0)) {
			$evento->anular();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}

		$filtro->columna('modalidad')->set_condicion_fija('es_igual_a');
		$filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_modalidad = '';
			if (isset($datos['modalidad'])) {
				$filtro_modalidad .= " AND EXISTS (SELECT * FROM sga_comisiones_modalidad WHERE sga_comisiones_modalidad.comision = vw_comisiones.comision AND sga_comisiones_modalidad.modalidad = '{$datos['modalidad']['valor']}')";
				$this->dep('filtro')->columna('modalidad')->resetear_estado();
			}

			$filtro_propuesta = '';
			if (isset($datos['propuesta'])) {
				$filtro_propuesta .= " AND EXISTS (SELECT * FROM sga_comisiones_propuestas WHERE sga_comisiones_propuestas.comision = vw_comisiones.comision AND sga_comisiones_propuestas.propuesta = '{$datos['propuesta']['valor']}')";
				$this->dep('filtro')->columna('propuesta')->resetear_estado();
			}

			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_modalidad $filtro_propuesta";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		$this->s__filtro = '';
	}

	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponibles(toba_ei_cuadro $cuadro)
	{
		$datos_llamado = $this->cn()->entidad()->get_datos();
		$llamado_mesa = $datos_llamado['llamado_mesa'];

		$asignados = $this->cn()->entidad()->get_asociaciones();

		$filtro = $this->s__filtro;

		if (!empty($asignados)) {
			if (!empty($this->s__filtro)) {
				$filtro .= ' AND ';
			}
			$filtro .= guarani_sql::armar_condicion_in($asignados, 'vw_comisiones', 'comision', 'comision', true);
		}

		$filtro_activas = " vw_comisiones.estado = '" . comunes::estado_activo . "'";
		$filtro = empty($filtro) ? $filtro_activas : $filtro . ' AND ' . $filtro_activas;

		$datos = toba::consulta_php('co_comisiones')->get_comisiones_asociables_a_mesa($llamado_mesa, $filtro);
		$cuadro->set_datos($datos);

		if (empty($datos)) {
			$index = $this->indx_msg_eof;
			if ($this->s__filtro != '') {
				$index .= '_filtrado';
			}
			$msg_eof = guarani_mensajes::get($index, array('Comisiones'));
			$cuadro->set_eof_mensaje($msg_eof);
		}
	}

	function evt__disponibles__seleccion($datos)
	{
		$this->seleccionados = $datos;
	}

	function evt__disponibles__agregar()
	{
		if (!empty($this->seleccionados)) {
			foreach ($this->seleccionados as $asignacion) {
				$this->cn()->entidad()->asignar($asignacion);
			}
		}
	}

}
?>