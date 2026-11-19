<?php
/**
 *	Representa un Turno de Examen
 *
 * @package Calendario
 * @subpackage Turnos de Examen
 */
class cn_ent_turno_de_examen extends cn_entidad
{
	protected $s__datos_iniciales;

	function cargar($id)
	{
		$this->dep('datos')->persistidor()->cargar_con_wheres(array(
					"periodos" => "periodos.periodo = (SELECT sga_turnos_examen.periodo FROM sga_turnos_examen WHERE sga_turnos_examen.turno_examen = ".$id['turno_examen'].")"
				));
		$this->cargar_etiquetas();
		$this->verificar_editabilidad();
	}

	function verificar_editabilidad()
	{
		$fecha_fin = $this->tabla('periodos')->get_columna('fecha_fin');
		//Si ya finalizó entonces no se puede editar ni agregar llamados
		if (guarani_fecha::es_menor($fecha_fin, guarani_fecha::get_hoy())) {
			$this->bloquear_edicion();
		} else {
			$this->habilitar_edicion();
		}
	}

	function set_datos_iniciales($datos_iniciales)
	{
		$this->s__datos_iniciales = $datos_iniciales;
	}
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		// Si están seteados, cargar datos iniciales
		if (isset($this->s__datos_iniciales)) {
			$this->tabla('periodos')->set($this->s__datos_iniciales);
			$this->tabla('turnos')->set($this->s__datos_iniciales);
			unset($this->s__datos_iniciales);
		}
		
		$this->validar_editabilidad();
		if ($validar) $this->validar_datos($datos);
		$this->tabla('periodos')->set($datos);
		$this->tabla('turnos')->set($datos);
	}

	function get_datos($tabla=null)
	{
		$datos_periodo = $this->tabla('periodos')->get();
		$datos_turno = $this->tabla('turnos')->get();
		if (!$datos_periodo) $datos_periodo = array();
		if (!$datos_turno) $datos_turno = array();
		return array_merge($datos_turno,$datos_periodo);
	}

	function validar_datos($datos)
	{
		$this->set_datos_obligatorios(array('nombre'=> 'Nombre',
                                                    'anio_academico'	=> 'Año academico',
                                                    'turno_tipo'	=> 'Tipo',
                                                    'fecha_inicio'	=> 'Fecha inicio',
                                                    'fecha_fin'		=> 'Fecha Fin',
                                                    'fecha_inactivacion'=> 'Fecha inactivación') );
		//Verificar datos obligatorios faltantes
		$this->validar_datos_obligatorios($datos);
		
		$turno_id = $this->controlador->get_turno_id();
		$this->validar_nombre_periodo_anio($datos['nombre'], $datos['anio_academico'], $turno_id);

		//La fecha de finalización no puede ser anterior a hoy
		if (guarani_fecha::es_menor($datos['fecha_fin'], guarani_fecha::get_hoy())) {
			$this->set_error('800_control_fechas', array('Finalización', 'anterior', 'de hoy.'));
		}

		//Que el inicio sea anterior al fin.
		if (guarani_fecha::es_mayor($datos['fecha_inicio'], $datos['fecha_fin'])) {
			$this->set_error('800_control_fechas',array('Inicio','posterior','Finalización'));
		}
		
		//La fecha %1% no puede ser %2% a la fecha %3%
		
		//Que el inicio sea anterior al fin.
                 if (!empty($datos['fecha_publicacion_mesas']) && guarani_fecha::es_mayor($datos['fecha_publicacion_mesas'], $datos['fecha_inicio'])) {
                            $this->set_error('800_control_fechas',array('Publicación  Mesas Alumnos','posterior','Inicio'));
                }
                
		//Que el inicio sea anterior al fin.
		if (guarani_fecha::es_mayor($datos['fecha_fin'], $datos['fecha_inactivacion'])) {
			$this->set_error('800_control_fechas',array('Inactivación','anterior','Finalización'));
		}
		
		//Que el inicio sea anterior al citación docentes
		if (guarani_fecha::es_mayor($datos['fecha_citacion_docentes'], $datos['fecha_inicio'])) {
			$this->set_error('800_control_fechas',array('Citación Docentes','posterior','Inicio'));
		}
                //Que el inicio sea anterior al publicacion de mesas  docentes
		if (!empty($datos['fecha_publicacion_mesas_docente']) & guarani_fecha::es_mayor($datos['fecha_publicacion_mesas_docente'], $datos['fecha_inicio'])) {
			$this->set_error('800_control_fechas',array('Publicación Mesas Docentes','posterior','Inicio'));
		}
               
                //Que el fecha_publicacion_mesas_docente sea anterior al fin.
		if (!empty($datos['fecha_publicacion_mesas_docente']) && !guarani_fecha::es_menor_igual($datos['fecha_publicacion_mesas_docente'], $datos['fecha_fin'])) {
			$this->set_error('800_control_fechas',array('Publicación Mesas Docentes','posterior','Finalización'));
		}
                //Que el fecha_publicacion_mesas_alumno sea anterior al fin.
		if (!empty($datos['fecha_publicacion_mesas']) && !guarani_fecha::es_menor_igual($datos['fecha_publicacion_mesas'], $datos['fecha_fin'])) {
			$this->set_error('800_control_fechas',array('Publicación Mesas Alumnos','posterior','Finalización'));
		}
		if ($turno_id) {
			$this->validar_mesas_en_rango($turno_id, $datos['fecha_inicio'], $datos['fecha_fin']);
			$this->validar_llamados_en_rango($turno_id, $datos['fecha_inicio'], $datos['fecha_fin']);
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_nombre_periodo_anio($nombre, $anio_academico, $turno)
	{
		if (!toba::consulta_php('co_turnos_de_examen')->validar_nombre_periodo_anio($nombre, $anio_academico, $turno)) {
			$this->set_notificacion('800CAL_turno_duplicado');
		}
	}

	function validar_mesas_en_rango($turno, $fecha_inicio, $fecha_fin)
	{
		if (!$this->co()->validar_mesas_en_rango_turno($turno, $fecha_inicio, $fecha_fin)) {
			$this->set_notificacion('800EXA_Mesas_Fuera_Rango');
		}
	}
	
	function validar_llamados_en_rango($turno, $fecha_inicio, $fecha_fin)
	{
		if (!$this->co()->validar_llamados_en_rango($turno, $fecha_inicio, $fecha_fin)) {
			$this->set_error('Existen llamados previamente creados fuera del rango de fechas definido para el turno.');
		}
	}
}
?>