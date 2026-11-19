<?php
/**
 *
 * @package		Egresados
 * @subpackage	Reportes
 */
class ci_rep_alumnos_adeudan_requisitos_egreso extends ci_rep_sin_listado_inicial
{
	protected $s__filtro_requisito = '';

	function get_tabla_principal()
	{
		return 'sga_requisitos_certificado';
	}

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('requisito')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		$this->s__filtro = '';
		$this->s__filtro_requisito = null;
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_ra = '';
			if (isset($datos['responsable_academica'])) {
				$filtro_ra .= " AND EXISTS (SELECT * FROM sga_propuestas_ra WHERE sga_propuestas_ra.propuesta = sga_alumnos.propuesta AND sga_propuestas_ra.responsable_academica = '{$datos['responsable_academica']['valor']}')";
				$this->dep('filtro')->columna('responsable_academica')->resetear_estado();
			}
			if (isset($datos['requisito'])) {
				$this->s__filtro_requisito = $datos['requisito']['valor'];
				$this->dep('filtro')->columna('requisito')->resetear_estado(); 
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_ra";
		} else {
			unset($this->s__datos_filtro);
		}
	}

	function get_datos($filtro) {}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_filtro_asociado($this->dep('filtro'));
		$this->msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro)) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			$datos = toba::consulta_php('co_requisitos_ingreso')->get_alumnos_deudores_egreso($this->s__filtro, $this->s__filtro_requisito);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dependencia('cuadro')->get_datos();
		$evento->vinculo()->set_parametros(array('nro_solicitud' => $datos[$fila]['nro_solicitud']));
	}
}