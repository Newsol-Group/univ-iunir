<?php
/**
 * 
 * 
 * @package Matricula
 * @subpackage Alumnos
 * 
 */
class ci_nav_imprimir_comprobantes_inscripcion extends ci_nav_con_preseleccion
{
	//Elemento Visual de Información
	protected $ev_info;
	
	//Preselección correspondiente al alumno
	protected $s__preseleccion;
	
	// Propuesta
	protected $s__propuesta;
	
	//Índice mensaje eof
	protected $indx_msg_eof = '';
	
	//Abrir popup con comprobante?
	protected $popup = false;

	function ini()
	{
		parent::ini();
		parent::set_concepto_preseleccion('Alumno');
	}

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial_preseleccion();
	}
	
	function set_preseleccion($preseleccion)
	{
		$this->s__preseleccion = $preseleccion;
	}

	protected function get_listado_preseleccion($alumno='')
	{
		$filtro  = $this->get_filtro_preseleccion_condicion();
		$filtro .= $alumno ? " AND sga_alumnos.alumno = $alumno" : '';
		$filtro .= " AND sga_alumnos.calidad = '".comunes::estado_activo."'";
		return toba::consulta_php('co_alumnos')->get_alumnos_perfil_de_datos($filtro);
	}
	
	//-----------------------------------------------------------------------------------
	//---- SELECCION --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$preseleccion = current($this->s__preseleccion);
		$datos_alumno = $this->get_listado_preseleccion($preseleccion);
		$this->controlador()->ev_info()->set_datos($datos_alumno[0]);
		$this->s__propuesta = $datos_alumno[0]['propuesta'];
	}
	
	//---- filtro -----------------------------------------------------------------------
	
	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}
	
	//---- Cuadro -----------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (!isset($this->s__filtro)) {
			$this->indx_msg_eof = '800_eof_cuadro_sinfiltro';
		} else {
			$this->indx_msg_eof = '800_eof_cuadro_filtrado';
			$datos = toba::consulta_php('co_comprobantes')->get_comprobantes_inscripcion_alumno(current($this->s__preseleccion), $this->get_filtro_condicion());
			$cuadro->set_datos($datos);
		}
		
		$indx_msj = guarani_mensajes::get($this->indx_msg_eof, array('Comprobantes de Inscripción'));
		$cuadro->set_eof_mensaje($indx_msj);
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();		
		if($datos[$fila]['tiene_comprobante']) {
			$evento->mostrar();
		} else {
			$evento->ocultar();			
		}
	}
	
	function evt__cuadro__seleccion($seleccion)
	{

		$alumno = current($this->s__preseleccion);
		
		$nro_t = $seleccion['nro_transaccion'];
		$tipo_comp = $seleccion['tipo'];
		$nro_insc = $seleccion['inscripcion'];
		$propuesta = $this->s__propuesta;
		
		// Preparo los datos para la impresion
		$titulo = '';
		$conf = array();
		
		// Preparo el filtro con los datos seleccionado para obtener los datos del comprobante
		$filtro = "	vw_comprobantes.tipo = '$tipo_comp' AND vw_comprobantes.inscripcion = $nro_insc AND vw_comprobantes.alumno = $alumno ";	
		$formateo = new toba_formateo('html');
		$gimp = guarani_datos_impresion::instancia();
		
		switch ($tipo_comp) {
			case comprobantes::tipo_comprobante_baja_insc_act :
				
				$datos = current(toba::consulta_php('co_comprobantes')->get_datos_comprobante_baja_cursadas($filtro));
				// Preparo los datos para la impresion
				$titulo = 'Comprobante de Baja de Inscripción a Cursada';
				$conf = array('propuesta' => $propuesta, 'nro_transaccion' => $nro_t, 'id_persona' => $datos['persona']);
				
				// Armo el arreglo de datos
				$gimp->nueva_fila_datos('Apellido y Nombre', $datos['apellido_y_nombres']);
				$gimp->nueva_fila_datos('Identificación', $datos['nro_identificacion']);
				$gimp->nueva_fila_datos('Legajo', $datos['legajo']);
				$gimp->nueva_fila_datos('Propuesta', $datos['propuesta_nombre']);				
				$gimp->nueva_fila_datos('Fecha de Inscripción', $formateo->formato_fecha_hora($datos['fecha_inscripcion']));
				$gimp->nueva_fila_datos('Fecha de Baja', $formateo->formato_fecha_hora($datos['fecha_baja']));			
				$gimp->nueva_fila_datos('Actividad', $datos['actividad_descr']);
				$gimp->nueva_fila_datos('Año Académico', $datos['anio_academico']);
				$gimp->nueva_fila_datos('Cátedra', $datos['catedra_nombre']);
				$gimp->nueva_fila_datos('Comisión', $datos['comision_nombre']);
				$gimp->nueva_fila_datos('Período Lectivo', $datos['periodo_nombre']);
				$gimp->nueva_fila_datos('Turno', $datos['turno_nombre']);

				break;
				
			case comprobantes::tipo_comprobante_baja_insc_exa :

				$datos = current(toba::consulta_php('co_comprobantes')->get_datos_comprobantes_baja_examenes($filtro));

				$titulo = 'Comprobante de Baja de Inscripción a Examen';
				$conf = array('propuesta' => $propuesta, 'nro_transaccion' => $nro_t, 'id_persona' => $datos['persona']);

				// Armo el arreglo de datos
				$gimp->nueva_fila_datos('Alumno', $datos['apellido_y_nombres']);
				$gimp->nueva_fila_datos('Identificación', $datos['nro_identificacion']);
				$gimp->nueva_fila_datos('Legajo', $datos['legajo']);
				$gimp->nueva_fila_datos('Propuesta', $datos['propuesta_nombre']);
				$gimp->nueva_fila_datos('Actividad', $datos['actividad_descr']);
				$gimp->nueva_fila_datos('Fecha de Inscripción', $formateo->formato_fecha_hora($datos['fecha_inscripcion']));
				$gimp->nueva_fila_datos('Fecha de Baja', $formateo->formato_fecha_hora($datos['fecha_baja']));
				$gimp->nueva_fila_datos('Año Académico', $datos['anio_academico']);
				$gimp->nueva_fila_datos('Turno', $datos['turno_nombre']);
				$gimp->nueva_fila_datos('Llamado', $datos['llamado_nombre']);
				$gimp->nueva_fila_datos('Mesa de Exámen', $datos['mesa_nombre']);			
				$gimp->nueva_fila_datos('Fecha', $formateo->formato_fecha_hora($datos['mesa_examen_fecha']));
				$gimp->nueva_fila_datos('Horario', $datos['horario_examen']);
				$gimp->nueva_fila_datos('Inscripto para rendir', $datos['instancia_nombre']);
				$gimp->nueva_fila_datos('Cátedra', $datos['catedra_nombre']);
				$gimp->nueva_fila_datos('Ubicación', $datos['ubicacion_nombre']);
						
				break;
						
			case comprobantes::tipo_comprobante_insc_act :
				
				$datos = current(toba::consulta_php('co_comprobantes')->get_datos_comprobante_cursadas($filtro));

				$titulo = 'Comprobante de Inscripción a Cursada';
				$conf = array('propuesta' => $propuesta, 'nro_transaccion' => $nro_t, 'id_persona' => $datos['persona']);

				// Armo el arreglo de datos				
				$gimp->nueva_fila_datos('Alumno', $datos['apellido_y_nombres']);
				$gimp->nueva_fila_datos('Identificación', $datos['nro_identificacion']);
				$datos['legajo'] = (!isset($datos['legajo'])) ? '  ' : $datos['legajo'];
				$gimp->nueva_fila_datos('Legajo', $datos['legajo']);
				$gimp->nueva_fila_datos('Propuesta', $datos['propuesta_nombre']);
				$gimp->nueva_fila_datos('Plan',  '(' . $datos['plan_codigo'] . ') ' . $datos['plan_nombre']);
				$gimp->nueva_fila_datos('Actividad', $datos['actividad_descr']);
				$gimp->nueva_fila_datos('Fecha de inscripción', $formateo->formato_fecha_hora($datos['fecha_inscripcion']));
				$gimp->nueva_fila_datos('Comisión',  $datos['comision_nombre']);
				$gimp->nueva_fila_datos('Subcomisiones',  $datos['subcomisiones_nombre']);
				$gimp->nueva_fila_datos('Año - Período lectivo', $datos['anio_academico'] . ' - ' . $datos['periodo_nombre']);
				$gimp->nueva_fila_datos('Cátedra', $datos['catedra_nombre']);
				$gimp->nueva_fila_datos('Turno', $datos['turno_nombre']);
				$gimp->nueva_fila_datos('Ubicación', $datos['ubicacion_nombre']);
				
				$comision = toba::consulta_php('co_comisiones')->get_instancias_comision($datos['comision']);
				
				$gimp->nueva_fila_datos('Inscripto a', implode(' / ', aplanar_matriz($comision, 'nombre')));
				$gimp->nueva_fila_datos('Estado de Inscripción', $datos['estado_nombre']);
				
				break;
				
			case comprobantes::tipo_comprobante_insc_exa :
				
				$datos = current(toba::consulta_php('co_comprobantes')->get_datos_comprobantes_examenes($filtro));

				$titulo = 'Comprobante de Inscripción a Examen';
				$conf = array('propuesta' => $propuesta, 'nro_transaccion' => $nro_t, 'id_persona' => $datos['persona']);

				// Armo el arreglo de datos
				$gimp->nueva_fila_datos('Alumno', $datos['apellido_y_nombres']);
				$gimp->nueva_fila_datos('Identificación', $datos['nro_identificacion']);
				$gimp->nueva_fila_datos('Legajo', $datos['legajo']);
				$gimp->nueva_fila_datos('Propuesta', $datos['propuesta_nombre']);
				$gimp->nueva_fila_datos('Actividad', $datos['actividad_descr']);
				$gimp->nueva_fila_datos('Fecha de Inscripción', $formateo->formato_fecha_hora($datos['fecha_inscripcion']));
				$gimp->nueva_fila_datos('Año Académico', $datos['anio_academico']);
				
				$gimp->nueva_fila_datos('Turno', $datos['turno_nombre']);
				$gimp->nueva_fila_datos('Llamado', $datos['llamado_nombre']);
				$gimp->nueva_fila_datos('Mesa de Exámen', $datos['mesa_nombre']);
				
				$gimp->nueva_fila_datos('Inscripto para rendir', $datos['instancia_nombre']);
				$gimp->nueva_fila_datos('Estado de Inscripción', $datos['estado_nombre']);
				$gimp->nueva_fila_datos('Fecha', $formateo->formato_fecha_hora($datos['mesa_examen_fecha']));
				$gimp->nueva_fila_datos('Horario', $datos['horario_examen']);	
				
				$gimp->nueva_fila_datos('Cátedra', $datos['catedra_nombre']);
				$gimp->nueva_fila_datos('Ubicación', $datos['ubicacion_nombre']);
						
				break;
			
			case comprobantes::tipo_comprobante_insc_prop :
				
				$datos = current(toba::consulta_php('co_comprobantes')->get_datos_comprobantes_propuestas($filtro));
				
				// Preparo los datos a pasar
				$titulo = 'Comprobante de Inscripción a Propuesta';
				$conf = array('propuesta' => $propuesta, 'nro_transaccion' => $nro_t, 'id_persona' => $datos['persona']);
				
				// Armo el arreglo de datos
				$gimp->nueva_fila_datos('Apellido y Nombre', $datos['apellido_y_nombres']);
				$gimp->nueva_fila_datos('Identificación', $datos['nro_identificacion']);
				$gimp->nueva_fila_datos('Propuesta', $datos['propuesta_nombre']);
				$gimp->nueva_fila_datos('Plan de Estudios', $datos['plan_version_nombre']);
				$gimp->nueva_fila_datos('Modalidad de Cursada', $datos['modalidad_nombre']);
				$gimp->nueva_fila_datos('Ubicación', $datos['ubicacion_nombre']);
				$gimp->nueva_fila_datos('Tipo de Ingreso', $datos['tipo_ingreso_nombre']);
				$gimp->nueva_fila_datos('Convenio', $datos['convenio']);
				$gimp->nueva_fila_datos('Estado de Inscripción', $datos['estado_nombre']);			
				$gimp->nueva_fila_datos('Fecha de Inscripción', $formateo->formato_fecha_hora($datos['fecha_inscripcion']));
	
				break;
			
			case comprobantes::tipo_comprobante_reinsc_prop :
				
				$datos = current(toba::consulta_php('co_comprobantes')->get_datos_comprobantes_reinsc_propuestas($filtro));
				
				// Preparo los datos para la impresion
				$titulo = 'Comprobante de Reinscripción a Propuesta';
				$conf = array('propuesta' => $propuesta, 'nro_transaccion' => $nro_t, 'id_persona' => $datos['persona']);
				
				// Armo el arreglo de datos
				$gimp->nueva_fila_datos('Apellido y Nombre', $datos['apellido_y_nombres']);
				$gimp->nueva_fila_datos('Identificación', $datos['nro_identificacion']);
				$gimp->nueva_fila_datos('Legajo', $datos['legajo']);
				$gimp->nueva_fila_datos('Propuesta', $datos['propuesta_nombre']);
				$gimp->nueva_fila_datos('Plan de Estudios', $datos['plan_version_nombre']);
				$gimp->nueva_fila_datos('Modalidad', $datos['modalidad_nombre']);
				$gimp->nueva_fila_datos('Ubicación', $datos['ubicacion_nombre']);
				$gimp->nueva_fila_datos('Año Académico', $datos['anio_academico']);
				$gimp->nueva_fila_datos('Fecha de Reinscripción', $formateo->formato_fecha_hora($datos['fecha_reinscripcion']));
				
				break;

		}
		
		$gimp->set_titulo($titulo);
		$gimp->set_conf($conf);
		
		//Almaceno los datos en memoria para recuperarlos luego para la impresión de comprobante
		$gimp->guardar_datos_imp();

		// Indico que se debe abrir el Popup
		$this->popup = true;

	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__cancelar_preseleccion()
	{
		unset($this->s__datos);
		unset($this->s__filtro);
		unset($this->s__preseleccion);
		$this->set_pantalla('preseleccion');
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'documento_principal', 'legajo', 'propuesta_nombre_abreviado', 'plan_nombre', 'plan_version_nombre', 'ubicacion_nombre'));
			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'documento_principal', 'legajo'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre_abreviado', 'plan_nombre', 'plan_version_nombre', 'ubicacion_nombre'), 2);
			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');
			$this->ev_info->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('plan_version_nombre', 'Versión');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if ($this->popup) {
			$url = toba::vinculador()->get_url(toba::proyecto()->get_id(), '34000020', array(), array('celda_memoria'=>'celda_impresion'));
			echo "
				abrir_popup('impresion', '$url', {'resizable': 1, 'scrollbars':'1'});
			";
			$this->popup = false;
		}
	}
	
}
?>