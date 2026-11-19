<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class cn_man_requisitos_ingreso extends cn_manejador_entidad
{
	protected $s__requisitos_ingreso;
	protected $s__modo_egreso = false;

	function get_indice_instancia()
	{
		return 'requisito_ingreso';
	}
	
	function get_clase_consulta() 
	{
		return 'co_requisitos_ingreso';
	}

	function get_proceso_aplanado()
	{
		return 'proceso_aplanado_requisitos_ingreso';
	}

	function set_modo_egreso()
	{
		$this->s__modo_egreso = true;
	}

	function modo_egreso()
	{
		return $this->s__modo_egreso;
	}

	function get_listado_requisitos_ingreso($pantalla)
	{
		$requisito_para = $this->modo_egreso() ? requisito::requisito_propuesta_para_egreso : requisito::requisito_propuesta_para_ingreso;
		$filtro = "sga_requisitos_propuestas.requisito_para = '$requisito_para'";
		$orden = ($pantalla == 'vista_por_requisitos') ? 'sga_requisitos.nombre, sga_g3entidades_tipos.nombre, entidad_subtipo_nombre, sga_requisitos_propuestas_entidades.entidad' : 'sga_g3entidades_tipos.nombre, entidad_subtipo_nombre, sga_requisitos_propuestas_entidades.entidad, sga_requisitos.nombre';
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		$this->s__requisitos_ingreso = empty($perfil_datos) ? $this->co()->get_listado($filtro, $orden) : $this->co()->get_requisitos_ingreso_perfil_datos($filtro, $orden);
		return $this->s__requisitos_ingreso;
	}

	function get_listado_requisitos_propuesta_entidad($entidad)
	{
		$requisito_para = $this->modo_egreso() ? requisito::requisito_propuesta_para_egreso : requisito::requisito_propuesta_para_ingreso;
		$filtro = "sga_requisitos_propuestas.requisito_para = '$requisito_para'";
		return $this->co()->get_listado_plan_version($entidad, $filtro);
	}

	function get_listado_disponibles($filtro)
	{
		if (! empty($filtro)) {
			$filtro .= " AND ";
		}
		$filtro .= guarani_sql::armar_condicion_not_in($this->s__requisitos_ingreso, 'sga_requisitos', 'requisito', 'requisito');
		$filtro .= " AND sga_requisitos.activo = '".comunes::si."'";
		if ($this->modo_egreso()) {
			$filtro .= ' AND sga_requisitos.requisito_tipo = '.requisito::tipo_documentales;
			$filtro .= " AND sga_requisitos.requisito_egreso = '".comunes::si."'";
		} else {
			$filtro .= ' AND sga_requisitos.requisito_tipo IN ('.requisito::tipo_documentales.', '.requisito::tipo_procesos.')';
			$filtro .= " AND sga_requisitos.requisito_ingreso = '".comunes::si."'";
		}
		return guarani::co('co_requisitos')->get_listado($filtro);
	}
}
?>