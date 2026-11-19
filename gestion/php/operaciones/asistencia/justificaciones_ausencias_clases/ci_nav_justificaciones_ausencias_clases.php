<?php
/**
 *
 *
 * @package Asistencias
 * @subpackage -
 */
class ci_nav_justificaciones_ausencias_clases extends ci_nav_con_preseleccion
{
	protected $s__alumno;
	protected $s__comision;
	protected $s__clase;
	protected $s__datos_alumno;
	protected $s__datos_comision;
	protected $s__datos_clase;
	
	//Componentes Visuales de Información.
	protected $ev_info_alumno;
	protected $ev_info_comision;
	protected $ev_info_clase;
	
	//Parámetro de indicar cantidad de inasistencias.
	protected $s__indica_cant_inasistencias;
	protected $s__carga_horas;
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial_preseleccion();
	}

	function ini()
	{
		parent::set_concepto_preseleccion('Alumno');
	}

	function set_preseleccion($seleccion)
	{
		$this->s__alumno = $seleccion['alumno'];
	}

	function get_preseleccion()
	{
		return $this->s__alumno;
	}

	function set_seleccion_comision($seleccion)
	{
		$this->s__comision = $seleccion['comision'];
	}
	
	function get_seleccion_comision()
	{
		return $this->s__comision;
	}
	
	function set_seleccion_clase($seleccion)
	{
		$this->s__clase = $seleccion['clase'];
	}
	
	function get_seleccion_clase()
	{
		return $this->s__clase;
	}
	
	function get_datos_alumno()
	{
		return $this->s__datos_alumno;
	}
	
	function get_datos_comision()
	{
		return $this->s__datos_comision;
	}
	
	function get_datos_clase()
	{
		return $this->s__datos_clase;
	}
	
	function indica_cantidad_inasistencias()
	{
		if ($this->s__indica_cant_inasistencias == comunes::si) {
			return true;
		} else {
			return false;
		}
	}

	function get_minimo_justificadas()
	{
		if ($this->s__carga_horas == comunes::si) {
			return 0;
		} else {
			return 0.5;
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección Comisión --------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	// Se redefine la selección del primer cuadro para pasar a la segunda pantalla (ya que existen 3 pant de selección).
	function evt__cuadro_preseleccion__seleccion($seleccion)
	{
		parent::evt__cuadro_preseleccion__seleccion($seleccion);
		$this->set_pantalla('seleccion_comision');
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección Comisión --------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__seleccion_comision(toba_ei_pantalla $pantalla)
	{
		$this->s__datos_alumno = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($this->get_preseleccion());
		$this->ev_info_alumno()->set_datos($this->s__datos_alumno);
	}
	
	//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$msj = guarani_mensajes::get($this->indx_msg_eof, array('Comisiones con Períodos Lectivos en Curso a las que el Alumno esté inscripto'));
		$cuadro->set_eof_mensaje($msj);
		$datos = $this->cn()->get_listado_comisiones_alumno($this->s__alumno);
		$cuadro->set_datos($datos);
	}
	
	// Se extiende el método de selección del ci nav con preselección porque existe una tercer pantalla de Selección.
	function evt__cuadro__seleccion($seleccion)
	{
		$this->set_seleccion_comision($seleccion);
		$this->set_pantalla('seleccion');
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección Clase -----------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->ev_info_alumno()->set_datos($this->s__datos_alumno);
		$this->s__datos_comision = current(toba::consulta_php('co_comisiones')->get_datos_comision($this->get_seleccion_comision()));
		$this->ev_info_comision()->set_datos($this->s__datos_comision);
		$this->s__indica_cant_inasistencias = $this->s__datos_comision['asistencia_cantidad_inasist'];
		$this->s__carga_horas = $this->s__datos_comision['asistencia_por_horas'];
	}
	
	//---- cuadro clases ----------------------------------------------------------------
	
	function conf__cuadro_clases(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array('Clases en las que el Alumno haya estado Ausente')));
		$datos = $this->cn()->get_listado_ausencias_clases($this->get_preseleccion(), $this->s__comision);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_clases__seleccion($seleccion)
	{
		$this->set_seleccion_clase($seleccion);
		$array_ids = array('alumno' => $this->get_preseleccion(), 'clase' => current($seleccion));
		$this->cn()->cargar($array_ids);
		$this->set_pantalla('edicion');
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Edición -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$this->ev_info_alumno()->set_datos($this->s__datos_alumno);
		$this->ev_info_comision()->set_datos($this->s__datos_comision);
		$this->s__datos_clase = toba::consulta_php('co_clases')->get_datos_clase_asignacion($this->s__clase);
		$this->ev_info_clase()->set_datos($this->s__datos_clase);
		
		/**
		 * Se redefine el control genérico del ci navegación para chequear si debe mostrarse o no el evento Eliminar.
		 * Esto es debido a que en este caso, la entidad nunca será nueva, siempre se estará editando un registro
		 * de asistencia a clase del alumno, y lo que debe chequearse es si todavía no se ingresó un motivo para 
		 * su ausencia. En este caso no se visualizará el botón de Eliminar, en caso contrario sí.
		 */
		if (!$this->cn()->entidad()->hay_motivo_justificacion()) {
			$pantalla->eliminar_evento('eliminar');
		} else {
			$this->evento('eliminar')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array('la Justificación de la Ausencia a Clases')));
		}
		$this->hay_cambios = $this->cn()->entidad()->dep('datos')->hay_cambios();
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__cancelar_seleccion_comision()
	{
		$this->cn()->resetear();
		$this->set_pantalla('seleccion_comision');
	}
	
	function evt__guardar()
	{
		$this->cn()->entidad()->set_asistencias_por_horas($this->s__datos_comision['asistencia_por_horas']);
		parent::evt__guardar();
	}

	/**
	 * Se extiende el comportamiento del evt Eliminar que en realidad sólo debe resetear los campos relativos a 
	 * la justificación de la inasistencia, y no eliminar el registro asistencia a clase del alumno.
	 */
	function evt__eliminar()
	{	
		try {
			$this->cn()->entidad()->eliminar_justificacion_inasistencia();
			$this->cn()->guardar();
			$this->cn()->resetear();
			toba::notificacion()->agregar($this->get_mensaje('eliminar_ok'), 'info');
			$this->set_pantalla('seleccion');
		} catch (guarani_error $e)	{
			$e->notificar();
		} 
	}
	
	//-----------------------------------------------------------------------------------
	//---- evs_info ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info_alumno()
	{
		if (!isset($this->ev_info_alumno)) {
			$this->ev_info_alumno = guarani::ev_info_datos();
			$this->ev_info_alumno->set_controlador($this);
			$this->ev_info_alumno->set_titulo('Datos del Alumno');
			
			$this->ev_info_alumno->set_columnas_a_mostrar(array('apellido_y_nombre', 'legajo', 'propuesta_nombre_abreviado', 'plan_nombre'));
			$this->ev_info_alumno->set_columnas_fila(array('apellido_y_nombre', 'legajo'), 1);
			$this->ev_info_alumno->set_columnas_fila(array('propuesta_nombre_abreviado', 'plan_nombre'), 2);
			
			$this->ev_info_alumno->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info_alumno->set_etiqueta('legajo', 'Legajo');
			$this->ev_info_alumno->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info_alumno->set_etiqueta('plan_nombre', 'Plan');
		}
		return $this->ev_info_alumno;
	}
	
	function ev_info_comision()
	{
		if (!isset($this->ev_info_comision)) {
			$this->ev_info_comision = guarani::ev_info_datos();
			$this->ev_info_comision->set_controlador($this);
			$this->ev_info_comision->set_titulo('Datos de la Comisión');
			
			$this->ev_info_comision->set_columnas_a_mostrar(array('anio_academico', 'periodo_lectivo_nombre', 'actividad_desc', 'comision_nombre', 'turno_nombre', 'modalidad_nombre', 'ubicacion_nombre'));
			$this->ev_info_comision->set_columnas_fila(array('anio_academico', 'periodo_lectivo_nombre'), 1);
			$this->ev_info_comision->set_columnas_fila(array('actividad_desc', 'comision_nombre'), 2);
			$this->ev_info_comision->set_columnas_fila(array('turno_nombre', 'modalidad_nombre', 'ubicacion_nombre'), 3);
			
			$this->ev_info_comision->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info_comision->set_etiqueta('periodo_lectivo_nombre', 'P. Lectivo');
			$this->ev_info_comision->set_etiqueta('actividad_desc', 'Actividad');
			$this->ev_info_comision->set_etiqueta('comision_nombre', 'Comisión');
			$this->ev_info_comision->set_etiqueta('turno_nombre', 'Turno');
			$this->ev_info_comision->set_etiqueta('modalidad_nombre', 'Modalidad');
			$this->ev_info_comision->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info_comision;
	}
	
	function ev_info_clase()
	{
		if (!isset($this->ev_info_clase)) {
			$this->ev_info_clase = guarani::ev_info_datos();
			$this->ev_info_clase->set_controlador($this);
			$this->ev_info_clase->set_titulo('Datos de la Clase');
			
			$this->ev_info_clase->set_columnas_a_mostrar(array('fecha', 'dia_semana_nombre', 'hora_inicio_formateada', 'hora_finalizacion_formateada', 'cantidad_horas_dictadas'));
			$this->ev_info_clase->set_columnas_fila(array('fecha', 'dia_semana_nombre', 'hora_inicio_formateada', 'hora_finalizacion_formateada', 'cantidad_horas_dictadas'), 1);
			
			$this->ev_info_clase->set_etiqueta('fecha', 'Fecha');
			$this->ev_info_clase->set_etiqueta('dia_semana_nombre', 'Día');
			$this->ev_info_clase->set_etiqueta('hora_inicio_formateada', 'Hs Inicio');
			$this->ev_info_clase->set_etiqueta('hora_finalizacion_formateada', 'Hs Fin');
			$this->ev_info_clase->set_etiqueta('cantidad_horas_dictadas', 'Cant. horas dictadas');
		}
		return $this->ev_info_clase;
	}
}
?>