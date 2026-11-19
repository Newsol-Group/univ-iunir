<?php
/**
 *
 *
 * @package 	Comunicación
 * @subpackage 	-
 * 
 */
class ci_edi_alumnos_propuesta extends guarani_pers_ci
{
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $seleccionados;
	protected $s__filtro_disp;
	protected $s__datos_disp;
	protected $datos_asoc_temp;

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
			$filtro = guarani_sql::armar_condicion_in($asignados, 'sga_propuestas', 'propuesta', 'propuesta', false);
			$datos = toba::consulta_php('co_propuestas')->get_listado($filtro);
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
		$filtro .= "sga_propuestas.estado = '" . propuesta::estado_activo . "'";

		if (!empty($asignados)) {
			if (!empty($filtro)) {
				$filtro .= ' AND ';
			}
			$filtro .= guarani_sql::armar_condicion_in($asignados, 'sga_propuestas', 'propuesta', 'propuesta', true);
		}
		$datos = toba::consulta_php('co_propuestas')->get_listado($filtro);
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