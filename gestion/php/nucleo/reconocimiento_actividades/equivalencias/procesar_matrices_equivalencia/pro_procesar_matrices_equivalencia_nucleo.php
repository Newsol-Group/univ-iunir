<?php
/**
 *
 * @package		Reconocimiento de Actividades
 * @subpackage	Equivalencias
 */
class pro_procesar_matrices_equivalencia_nucleo extends guarani_procesos_bk
{
	protected $aplicar_a;
	protected $anio_academico;
	protected $turno_examen;
	protected $periodo_lectivo;
	protected $ubicaciones;
	protected $planes;
	protected $versiones;

	const reporte_resultados = 1;
	const nombre = 'pro_procesar_matrices_equivalencia';

	function set_datos($datos = array())
	{
		$this->aplicar_a = $datos['responsable_academica']['aplicar_a'];
		$this->anio_academico = $datos['responsable_academica']['anio_academico'];
		$this->turno_examen = $datos['responsable_academica']['turno_examen'];
		$this->periodo_lectivo = $datos['responsable_academica']['periodo_lectivo'];
		$this->ubicaciones = $datos['ubicacion'];
		$this->planes = $datos['plan'];
		$this->versiones = $datos['plan_version'];
	}

	function procesar()
	{
		$cant_actas_procesadas = 0;
		$cant_alumnos_evaluados = 0;
		$cant_equiv_otorgadas = 0;

		switch ($this->aplicar_a) {
			case 'Examenes':
				$alumnos = toba::consulta_php('co_actas')->get_alumnos_acta_examen_aplicar_matrices($this->anio_academico, $this->turno_examen, $this->ubicaciones, $this->planes);
				break;
			case 'Cursadas':
				$alumnos = toba::consulta_php('co_actas')->get_alumnos_acta_cursada_aplicar_matrices($this->anio_academico, $this->periodo_lectivo, $this->ubicaciones, $this->planes);
				break;
			case 'Planes':
				$alumnos = toba::consulta_php('co_alumnos')->get_alumnos_cambio_plan_aplicar_matrices($this->ubicaciones, $this->planes);
				break;
			case 'Versiones':
				$alumnos = toba::consulta_php('co_alumnos')->get_alumnos_cambio_version_aplicar_matrices($this->ubicaciones, $this->versiones);
				break;
			case 'Simultanea':
				$alumnos = toba::consulta_php('co_alumnos')->get_alumnos_cursada_simultanea_aplicar_matrices($this->ubicaciones, $this->planes);
				break;
		} 
		$cant_alumnos = guarani_arreglos::count($alumnos);
		if ($cant_alumnos > 0) {
			$paso = 100/$cant_alumnos;
		} else {
			$this->log_estado->incrementar_progreso(100);
		} 

		$id_acta_anterior = 0;
		foreach ($alumnos as $alumno) {
			$estado = $this->get_estado();
			if (isset($estado['abortar']) && $estado['abortar']) {
				$this->abortar = true;
				break;
			}
			$cant_alumnos_evaluados++;
			if ($alumno['id_acta'] <> $id_acta_anterior) {
				if ($id_acta_anterior <> 0) {
					guarani::act('act_equivalencias')->insertar_equiv_actas_procesadas($id_acta_anterior);
					$cant_actas_procesadas++;
				}
				$id_acta_anterior = $alumno['id_acta'];
			}
			try {
				toba::db()->abrir_transaccion();
				$rs = guarani::act('act_equivalencias')->otorgar_equivalencias($alumno['accion'], $alumno['alumno'], $alumno['propuesta_origen'], $alumno['plan_origen'], $alumno['plan_version_origen'], $alumno['elemento']);
				$cant_equiv_otorgadas += $rs['resultado'];
				toba::db()->cerrar_transaccion();
			} catch (toba_error $e) {
				$this->log_estado->set_error();
				echo "Error: {$e->get_mensaje()}\n";
				toba::db()->abortar_transaccion();
			}
			$this->log_estado->incrementar_progreso($paso);
		}
		if ($id_acta_anterior <> 0) {
			guarani::act('act_equivalencias')->insertar_equiv_actas_procesadas($id_acta_anterior);
			$cant_actas_procesadas++;
		}

		$mensaje = array();
		$mensaje['actas_procesadas'] = $cant_actas_procesadas;
		$mensaje['alumnos_evaluados'] = $cant_alumnos_evaluados;
		$mensaje['equiv_otorgadas'] = $cant_equiv_otorgadas;
		$this->log->set($mensaje, array(self::reporte_resultados));
		$this->log->guardar();
	}
}
?>