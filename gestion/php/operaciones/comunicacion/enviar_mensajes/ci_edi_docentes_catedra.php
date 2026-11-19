<?php
/**
 *
 *
 * @package 	Comunicación
 * @subpackage 	-
 * 
 */
class ci_edi_docentes_catedra extends guarani_pers_ci
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
			$filtro = guarani_sql::armar_condicion_in($asignados, 'sga_catedras', 'catedra', 'catedra', false);
			$datos = toba::consulta_php('co_catedras')->get_catedras_perfil_de_datos($filtro);
			$cuadro->set_datos($datos);
		} else {
			$etiquetas = guarani_etiquetas::get_tabla('sga_catedras');
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
		$filtro->columna('elemento')->set_condicion_fija('contiene');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_disp = $datos;
			$filtro_elemento = '';
			if (isset($datos['elemento'])) {

				$expresion_filtro_sga_elementos_codigo = guarani_ei_filtro::get_expresion('sga_elementos.codigo', $datos['elemento']['valor'], $datos['elemento']['condicion_cs'], $datos['elemento']['condicion_as']);
				$expresion_filtro_sga_elementos_nombre = guarani_ei_filtro::get_expresion('sga_elementos.nombre', $datos['elemento']['valor'], $datos['elemento']['condicion_cs'], $datos['elemento']['condicion_as']);
                                
				$condicion = "($expresion_filtro_sga_elementos_codigo OR $expresion_filtro_sga_elementos_nombre)";
				$filtro_elemento .= " AND sga_catedras.catedra IN (	SELECT	sga_catedras_actividades.catedra
																	FROM	sga_catedras_actividades,
																			sga_elementos
																	WHERE	sga_catedras_actividades.elemento = sga_elementos.elemento AND
																			$condicion)";
				$this->dep('filtro')->columna('elemento')->resetear_estado();
			}
			$this->s__filtro_disp = "{$this->dep('filtro')->get_sql_where()} $filtro_elemento ";
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
		if (!empty($asignados)) {
			if (!empty($filtro)) {
				$filtro .= ' AND ';
			}
			$filtro .= guarani_sql::armar_condicion_in($asignados, 'sga_catedras', 'catedra', 'catedra', true);
		}
		$datos = toba::consulta_php('co_catedras')->get_catedras_perfil_de_datos($filtro);
		$cuadro->set_datos($datos);

		if (empty($datos)) {
			$index = $this->indx_msg_eof;
			if ($this->s__filtro_disp != '') {
				$index .= '_filtrado';
			}
			$etiquetas = guarani_etiquetas::get_tabla('sga_catedras');
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