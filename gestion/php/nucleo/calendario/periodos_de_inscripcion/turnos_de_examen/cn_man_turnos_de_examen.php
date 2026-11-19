<?php
/**
 *
 *
 * @package Calendario
 * @subpackage Turnos de Examen
 */
class cn_man_turnos_de_examen extends cn_manejador_entidad
{
	protected $s__indice_entidad_actual = 'turno_de_examen';

	function get_proceso_aplanado()
	{
		return 'proceso_aplanar_periodo_inscripcion';
	}
	
	function usar_turnos()
	{
		$this->s__indice_entidad_actual = 'turno_de_examen';
	}

	function usar_llamados()
	{
		$this->s__indice_entidad_actual = 'llamado_de_turno_de_examen';
	}

	function usar_periodos()
	{
		$this->s__indice_entidad_actual = 'periodo_inscripcion_fechas';
	}

	function set_indice_instancia($indice)
	{
		return $this->s__indice_entidad_actual = $indice;
	}
	
	function get_indice_instancia()
	{
		return $this->s__indice_entidad_actual;
	}

	function get_clase_consulta()
	{
		return 'co_turnos_de_examen';
	}

	function get_listado($filtro)
	{
		return guarani::co('co_turnos_de_examen')->get_turnos_examen_perfil_de_datos($filtro, true);
	}

	function get_turnos_llamados_periodos($filtro=null)
	{
		return guarani::co('co_turnos_de_examen')->get_turnos_llamados_periodos_con_nombre_entidad($filtro);
	}

	function get_turno_id()
	{
		if ($this->s__indice_entidad_actual == 'turno_de_examen') {
			return $this->entidad()->tabla('turnos')->get_columna('turno_examen');
		}
		return false;
	}

	function get_llamado_periodo_id()
	{
		if ($this->s__indice_entidad_actual == 'periodo_inscripcion_fechas') {
			return $this->entidad()->tabla('per_inscripcion')->get_columna('periodo');
		}
		return false;
	}

	function cargar_turno($turno)
	{
		$this->usar_turnos();
		$this->cargar(array('turno_examen' => $turno));
	}

	function cargar_llamado($llamado)
	{
		$this->usar_llamados();
		$this->cargar(array('llamado' => $llamado));
	}

	function cargar_periodo($periodo, $activar_perfil_de_datos=true)
	{
		$this->usar_periodos();
		$this->cargar(array('periodo_inscripcion' => $periodo), $activar_perfil_de_datos);
	}

	function agregar_turno()
	{
		$this->usar_turnos();
		$this->nueva();
		$datos_iniciales['turno_tipo'] = turno_de_examen::tipo_normal;
		$datos_iniciales['periodo_generico'] = periodo_generico::turno_examen;
		$this->entidad()->set_datos_iniciales($datos_iniciales);
	}

	function agregar_llamado_a_turno($turno)
	{
		$this->usar_llamados();
		$this->nueva();
		$datos_iniciales = $this->co()->get_datos_turno($turno);
		$datos_iniciales['periodo'] = null;
		$datos_iniciales['llamado'] = null;
		$datos_iniciales['descripcion'] = null;
		$datos_iniciales['nombre'] = guarani_texto::truncar_texto('Llamado del Turno '.$datos_iniciales['nombre'], 100);
		$this->entidad()->set_datos($datos_iniciales, null, false);
	}

	function agregar_periodo_insc_a_llamado($llamado)
	{
		$this->usar_periodos();
		$this->nueva();
		$datos_iniciales = $this->co()->get_datos_llamado($llamado);
		$datos_iniciales['nombre'] = guarani_texto::truncar_texto('Período de Inscripción al llamado '.$datos_iniciales['nombre'], 100);
		$this->entidad()->set_datos($datos_iniciales, null, false);
	}

	function eliminar_turno($turno)
	{
		$this->eliminar_llamados_turno($turno);
		$this->cargar_turno($turno);
		$this->eliminar();
	}

	function eliminar_llamados_turno($turno)
	{
		$llamados = $this->co()->get_llamados_turno($turno, '', false);
		$this->desactivar_transacciones_anidadas();
		foreach ($llamados as $llamado) {
			$this->eliminar_llamado($llamado['llamado']);
		}
	}

	function eliminar_llamado($llamado)
	{
		$periodos = $this->co()->get_periodos_insc_llamado($llamado);
		$this->desactivar_transacciones_anidadas();
		foreach ($periodos as $periodo) {
			$this->eliminar_periodo($periodo['periodo_inscripcion']);
		}
		$this->cargar_llamado($llamado);
		$this->eliminar();
	}

	function eliminar_periodo($periodo)
	{
		$this->cargar_periodo($periodo, false);
		$this->eliminar();
	}

	function get_llamados_relacion_disponibles()
	{
		return $this->co()->get_llamados_relacion_disponibles();
	}
	
	function guardar()
	{
		if ($this->get_indice_instancia() == 'llamado_de_turno_de_examen') {
			$this->entidad()->validar_llamados_vinculados();
		}
		parent::guardar();
	}
}

?>