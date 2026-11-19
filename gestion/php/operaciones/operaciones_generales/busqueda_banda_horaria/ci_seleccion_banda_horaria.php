<?php
class ci_seleccion_banda_horaria extends guarani_pers_ci
{
	protected $indx_msg_eof = '800_eof_cuadro';

	protected $s__filtro;
	protected $s__datos;

	protected $s__anio_academico;
	protected $s__periodo_lectivo;

	protected $s__comision;
	protected $s__asignaciones;

	function ini()
	{
		$anio_academico = toba::memoria()->get_parametro('anio_academico');
		if (!empty($anio_academico)) {
			$this->s__anio_academico = $anio_academico;
		}
		
		$periodo_lectivo = toba::memoria()->get_parametro('periodo_lectivo');
		if (!empty($periodo_lectivo)) {
			$this->s__periodo_lectivo = $periodo_lectivo;
		}
		
		$comision = toba::memoria()->get_parametro('comision');
		if (! empty($comision)) {
			$this->s__comision = $comision;
		}
		$asignaciones = toba::memoria()->get_parametro('asignaciones');
		if (! empty($asignaciones)) {
			$this->s__asignaciones = explode('||', $asignaciones);
		}		
	}

	function get_datos($filtro)
	{
		$filtro = empty($filtro) ? true : $filtro;
		if (isset($this->s__anio_academico)) {
			$filtro .= " AND sga_periodos.anio_academico = {$this->s__anio_academico}";
		}
		if (isset($this->s__periodo_lectivo)) {
			$filtro .= " AND sga_comisiones.periodo_lectivo = {$this->s__periodo_lectivo}";
		}
		if (isset($this->s__comision)) {
			$filtro .= " AND sga_comisiones_bh.comision <> {$this->s__comision}";
		}
		if (isset($this->s__asignaciones)) {
			$filtro .= ' AND ' . guarani_sql::armar_condicion_not_in($this->s__asignaciones, 'sga_asignaciones', 'asignacion', 'asignacion');
		}
		return toba::consulta_php('co_bandas_horarias')->get_listado($filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{		
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}		
		$filtro->columna('hora_inicio')->set_condicion_fija('es_igual_a');
		$filtro->columna('hora_finalizacion')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;

			$filtro_hora_inicio = '';
			if (isset($datos['hora_inicio'])) {
				$filtro_hora_inicio .= " AND to_char(sga_asignaciones.hora_inicio, '".comunes::formato_hora_visual."') = '{$datos['hora_inicio']['valor']}'";
				$this->dep('filtro')->columna('hora_inicio')->resetear_estado();
			}

			$filtro_hora_finalizacion = '';
			if (isset($datos['hora_finalizacion'])) {
				$filtro_hora_finalizacion .= " AND to_char(sga_asignaciones.hora_finalizacion, '".comunes::formato_hora_visual."') = '{$datos['hora_finalizacion']['valor']}'";
				$this->dep('filtro')->columna('hora_finalizacion')->resetear_estado();
			}

			$this->s__filtro = $this->dep('filtro')->get_sql_where() . $filtro_hora_inicio . $filtro_hora_finalizacion;
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		unset($this->s__filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();
		if (empty($this->s__filtro)) {
			$msg_eof = guarani_mensajes::get($this->indx_msg_eof.'_sinfiltro');
		} else {
			$etiqueta = guarani_etiquetas::get_tabla('sga_comisiones_bh');
			$msg_eof = guarani_mensajes::get($this->indx_msg_eof.'_filtrado', array($etiqueta->get_enf('plural')));

			$datos = $this->get_datos($this->s__filtro);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($msg_eof);
	}
}
?>