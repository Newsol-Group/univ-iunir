<?php
/**
 *
 *
 * @package 	Comunicación
 * @subpackage 	-
 * 
 */
class ci_edi_sel_mesa extends guarani_pers_ci
{
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $seleccionados;
	protected $s__filtro_disp;
	protected $s__datos_disp;
	protected $datos_asoc_temp;
	protected $s__datos_inscripciones;

	function resetear()
	{
		unset($this->s__filtro_disp);
		unset($this->s__datos_disp);
		unset($this->s__datos_inscripciones);
	}

	function set_solo_lectura()
	{
		$this->dep('form_inscripciones')->set_solo_lectura(array('insc_aceptadas', 'insc_pendientes', 'insc_rechazadas'));
		$this->dep('asignados')->evento('eliminar')->anular();
		$this->dep('disponibles')->evento('agregar')->desactivar();
	}

	//-----------------------------------------------------------------------------------
	//---- asignados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__asignados(toba_ei_cuadro $cuadro)
	{
		$asignados = $this->cn()->entidad()->get_dominio();
		if (!empty($asignados)) {
			$filtro = guarani_sql::armar_condicion_in($asignados, 'sga_llamados_mesa', 'llamado_mesa', 'llamado_mesa', false);
			$datos = toba::consulta_php('co_mesas_de_examen')->get_listado($filtro);
			$cuadro->set_datos($datos);
		} else {
			$etiquetas = guarani_etiquetas::get_tabla('sga_llamados_mesa');
			$msg_eof = guarani_mensajes::get($this->indx_msg_eof, array($etiquetas->get_enf('plural')));
			$cuadro->set_eof_mensaje($msg_eof);
		}
	}

	function evt__asignados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_dominio($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_disp)) {
			$filtro->set_datos($this->s__datos_disp);
		}
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('turno_examen')->set_condicion_fija('es_igual_a');
		$filtro->columna('llamado')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_disp = $datos;
			$this->s__filtro_disp = $this->dep('filtro')->get_sql_where();
		} else {
			unset($this->s__datos_disp);
			$this->s__filtro_disp = '';
		}
	}

	function evt__filtro__cancelar()
	{
		unset($this->s__datos_disp);
		$this->s__filtro_disp = '';
	}

	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponibles(cuadro_seleccion_multiple $cuadro)
	{
		if (!empty($this->s__filtro_disp)) {
			$asignados = $this->cn()->entidad()->get_dominio();
			$filtro = $this->s__filtro_disp;

			if (!empty($asignados)) {
				if (!empty($filtro)) {
					$filtro .= ' AND ';
				}
				$filtro .= guarani_sql::armar_condicion_in($asignados, 'sga_llamados_mesa', 'llamado_mesa', 'llamado_mesa', true);
			}
			$datos = toba::consulta_php('co_mesas_de_examen')->get_listado($filtro);
			$cuadro->set_datos($datos);
		}

		$index = $this->indx_msg_eof;
		if (empty($this->s__datos_disp)) {
			$index .= '_sinfiltro';
		} else {
			$index .= '_filtrado';
		}
		$etiquetas = guarani_etiquetas::get_tabla('sga_llamados_mesa');
		$msg_eof = guarani_mensajes::get($index, array($etiquetas->get_enf('plural')));
		$cuadro->set_eof_mensaje($msg_eof);
	}

	function evt__disponibles__seleccion($datos)
	{
		$this->seleccionados = $datos;
	}

	function evt__disponibles__agregar($datos)
	{
		if (!empty($this->seleccionados)) {
			foreach ($this->seleccionados as $asignacion) {
				$this->cn()->entidad()->agregar_dominio($asignacion);
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_inscripciones -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_inscripciones(form_inscripciones $form)
	{
		if (!empty($this->s__datos_inscripciones)) {
			$form->set_datos($this->s__datos_inscripciones);
		} else {
			$asignados = $this->cn()->entidad()->get_dominio();
			if (!empty($asignados)) {
				$asignado = current($asignados);
				$datos = array();

				$datos['insc_aceptadas'] = ($asignado['insc_aceptadas'] == comunes::si) ? 1 : 0;
				$datos['insc_pendientes'] = ($asignado['insc_pendientes'] == comunes::si) ? 1 : 0;
				$datos['insc_rechazadas'] = ($asignado['insc_rechazadas'] == comunes::si) ? 1 : 0;

				$form->set_datos($datos);
			}
		}
	}

	function evt__form_inscripciones__modificacion($datos)
	{
		$this->s__datos_inscripciones = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function post_eventos()
	{
		/**
		 * Se agrega en el dominio los estados de las inscripciones seleccionados
		 */
		if (isset($this->s__datos_inscripciones)) {
			$asignados = $this->cn()->entidad()->get_dominio();
			foreach ($asignados as $asignado) {
				$id_fila = $asignado['x_dbr_clave'];
				unset($asignado['x_dbr_clave']);
				$asignado['insc_aceptadas'] = ($this->s__datos_inscripciones['insc_aceptadas'] == 1) ? comunes::si : comunes::no;
				$asignado['insc_pendientes'] = ($this->s__datos_inscripciones['insc_pendientes'] == 1) ? comunes::si : comunes::no;
				$asignado['insc_rechazadas'] = ($this->s__datos_inscripciones['insc_rechazadas'] == 1) ? comunes::si : comunes::no;
				$this->cn()->entidad()->modificar_dominio($id_fila, $asignado);
			}
		}
	}

}
?>