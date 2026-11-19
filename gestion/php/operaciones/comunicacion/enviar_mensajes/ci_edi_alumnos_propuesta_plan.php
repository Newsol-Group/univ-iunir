<?php
/**
 *
 *
 * @package 	Comunicación
 * @subpackage 	-
 * 
 */
class ci_edi_alumnos_propuesta_plan extends guarani_pers_ci
{
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $seleccionados;
	protected $s__filtro_disp;
	protected $s__datos_disp;
	protected $datos_asoc_temp;

	function armar_row_asignados($asignados, $es_not = false)
	{
		$filtro = '';
		if (!empty($asignados)) {
			$filtro .= ' ROW(sga_propuestas.propuesta, sga_planes.plan) ';
			if ($es_not) {
				$filtro .= 'NOT ';
			}
			$filtro .= 'IN (';
			$i = 0;
			foreach ($asignados as $asignado) {
				$filtro .= "('" . $asignado['propuesta'] . "', '" . $asignado['plan'] . "')";
				$i++;
				if ($i < guarani_arreglos::count($asignados)) {
					$filtro .= ', ';
				}
			}
			$filtro .= ')';
		}
		return $filtro;
	}

	function set_solo_lectura()
	{
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
			$filtro = $this->armar_row_asignados($asignados);
			$order = 'sga_planes.propuesta, sga_planes.plan';
			$datos = toba::consulta_php('co_planes')->get_listado($filtro, $order);
			$cuadro->set_datos($datos);
		} else {
			$etiquetas = guarani_etiquetas::get_tabla('sga_propuestas');
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
		$filtro->columna('responsable_academica')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_disp = $datos;

			$filtro_ubicacion = '';
			$filtro_ra = '';

			if ((isset($datos['ubicacion']))) {
				$ubicacion = toba::db()->quote($datos['ubicacion']['valor']);
				$filtro_ubicacion = ' AND sga_propuestas.propuesta IN ';
				$filtro_ubicacion .= "(	SELECT 	sga_propuestas_oferta.propuesta
										FROM 	sga_propuestas_oferta
										WHERE	sga_propuestas_oferta.ubicacion = $ubicacion)";
				$this->dep('filtro')->columna('ubicacion')->resetear_estado();
			}

			if ((isset($datos['responsable_academica']))) {
				$responsable_academica = toba::db()->quote($datos['responsable_academica']['valor']);
				$filtro_ra = ' AND sga_propuestas.propuesta IN ';
				$filtro_ra .= "(SELECT 	sga_propuestas_ra.propuesta
								FROM 	sga_propuestas_ra
								WHERE	sga_propuestas_ra.responsable_academica = $responsable_academica)";
				$this->dep('filtro')->columna('responsable_academica')->resetear_estado();
			}

			$this->s__filtro_disp = "{$this->dep('filtro')->get_sql_where()} $filtro_ubicacion $filtro_ra";
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
		$asignados = $this->cn()->entidad()->get_dominio();

		$filtro = $this->s__filtro_disp;
		if (!empty($filtro)) {
			$filtro .= ' AND ';
		}
		$filtro .= "sga_planes.estado IN ('" . planes::estado_activo . "','" . planes::estado_vigente . "')";

		if (!empty($asignados)) {
			if (!empty($filtro)) {
				$filtro .= ' AND ';
			}
			$filtro .= $this->armar_row_asignados($asignados, true);
		}
		$order = 'sga_planes.propuesta, sga_planes.plan';
		$datos = toba::consulta_php('co_planes')->get_listado($filtro, $order);
		$cuadro->set_datos($datos);

		if (empty($datos)) {
			$index = $this->indx_msg_eof;
			if ($this->s__filtro_disp != '') {
				$index .= '_filtrado';
			}
			$etiquetas = guarani_etiquetas::get_tabla('sga_propuestas');
			$msg_eof = guarani_mensajes::get($index, array($etiquetas->get_enf('plural')));
			$cuadro->set_eof_mensaje($msg_eof);
		}
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

}
?>