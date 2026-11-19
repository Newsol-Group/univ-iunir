<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Alumnos
 * 
 */
class ci_nav_vencimiento_regularidad extends ci_nav_con_preseleccion
{
	// Datos del alumno seleccionado.
	protected $s__alumno;
	protected $s__datos_alumno;

	// Datos de la regularidad seleccionada.
	protected $s__id_acta;
	protected $s__equivalencia;
	protected $s__fecha_vencimiento_actual;

	// Componentes Visuales de Información.
	protected $ev_info_alumno;
	protected $ev_info_actividad;
	protected $s__datos_cuadro;
	protected $s__info_actividad;

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

	function set_seleccion($seleccion)
	{
		$clave = $seleccion['clave_acta_equivalencia'];
		$exp = explode(':', $clave);
		if (empty($exp[0])) {
			$this->s__id_acta = null;
			$this->s__equivalencia = $exp[1];
		} else {
			$this->s__id_acta = $exp[0];
			$this->s__equivalencia = null;
		}
	}

	function get_id_acta()
	{
		return $this->s__id_acta;
	}

	function get_equivalencia()
	{
		return $this->s__equivalencia;
	}

	function get_fecha_vencimiento_actual()
	{
		return $this->s__fecha_vencimiento_actual;
	}
	
	function get_datos_actividad()
	{
		$formateo = new toba_formateo('html');

		if (! empty($this->s__id_acta)) {
			$id_acta = toba::db()->quote($this->s__id_acta);
			$alumno = toba::db()->quote($this->s__alumno);
			$filtro = " vw_regularidades.id_acta = $id_acta AND
						vw_regularidades.alumno = $alumno AND
						vw_regularidades.origen_otra_propuesta = false";
            $datos_regularidad = current(toba::consulta_php('co_alumnos')->get_regularidades($filtro));
		} else {
			$equivalencia = toba::db()->quote($this->s__equivalencia);
            $datos_regularidad = toba::consulta_php('co_equivalencias')->get_equivalencia_parcial($equivalencia);
		}

        $fecha_venc = toba::consulta_php('co_vencimientos_regularidad')->get_max_fecha_vencimiento($this->s__alumno, $this->s__id_acta, $this->s__equivalencia);
		$this->s__fecha_vencimiento_actual = empty($fecha_venc) ? $datos_regularidad['fecha_vigencia'] : $fecha_venc['fecha_vigencia_nuevo'];		

		$datos_regularidad['fecha_regular'] = isset($datos_regularidad['fecha']) ? $formateo->formato_fecha($datos_regularidad['fecha']) : ''; 
		$datos_regularidad['fecha_venc_actual'] = ($this->s__fecha_vencimiento_actual == null) ? 'Sin Vencimiento' : $formateo->formato_fecha($this->s__fecha_vencimiento_actual); 
		
		return $datos_regularidad;
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial_preseleccion();
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		if (! isset($this->s__datos_alumno)) {
			$this->s__datos_alumno = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($this->s__alumno);
		}
		$this->ev_info_alumno()->set_datos($this->s__datos_alumno);
	}
	
	//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array('Actividades Regularizadas')));

		$datos = $this->cn()->get_listado_vencimiento_regularidad($this->s__alumno);
		$aux = array();
		foreach(array_keys($datos) as $key) {
			$aux[$datos[$key]['clave_acta_equivalencia']] = $datos[$key];
		}
		$this->s__datos_cuadro = $aux;
		$cuadro->set_datos($datos);
	}

	function evt__cuadro__seleccion($seleccion)
	{		
		$this->set_seleccion($seleccion);		
		$this->s__info_actividad = $this->s__datos_cuadro[$seleccion['clave_acta_equivalencia']];
		$this->set_pantalla('seleccion_modificatoria');
	}

	//---- ev_info_alumno ---------------------------------------------------------------

	function ev_info_alumno()
	{
		if (!isset($this->ev_info_alumno)) {
			$this->ev_info_alumno = guarani::ev_info_datos();
			$this->ev_info_alumno->set_columnas_a_mostrar(array('apellido_y_nombre', 'legajo', 'propuesta_nombre_abreviado', 'plan_nombre', 'ubicacion_nombre'));
			
			$this->ev_info_alumno->set_columnas_fila(array('apellido_y_nombre', 'legajo'), 1);
			$this->ev_info_alumno->set_columnas_fila(array('propuesta_nombre_abreviado', 'plan_nombre'), 2);
			$this->ev_info_alumno->set_columnas_fila(array('ubicacion_nombre'), 3);
			
			$this->ev_info_alumno->set_max_columnas_por_fila(2);
			
			$this->ev_info_alumno->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info_alumno->set_etiqueta('legajo', 'Legajo');
			
			$this->ev_info_alumno->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info_alumno->set_etiqueta('plan_nombre', 'Plan');
			
			$this->ev_info_alumno->set_etiqueta('ubicacion_nombre', 'Ubicación');
			
			$this->ev_info_alumno->set_td_etiqueta_width('125px');
			$this->ev_info_alumno->set_td_valores_width('330px');
			
			$this->ev_info_alumno->set_table_width('');
		}
		return $this->ev_info_alumno;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección Modificatoria ---------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_modificatoria()
	{
		$this->ev_info_alumno()->set_datos($this->s__datos_alumno);
		$this->ev_info_actividad()->set_datos($this->get_datos_actividad());
	}

	//---- cuadro_modificatorias --------------------------------------------------------

	function conf__cuadro_modificatorias(toba_ei_cuadro $cuadro)
	{
		$etiqueta = $this->cn()->etiquetas()->get_enf('plural');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta)));

		$datos = $this->cn()->get_listado_modificatorias($this->s__alumno, $this->s__id_acta, $this->s__equivalencia);
		$cuadro->set_datos($datos);
	}

	//---- ev_info_actividad ------------------------------------------------------------

	function ev_info_actividad()
	{
		if (!isset($this->ev_info_actividad)) {
			$this->ev_info_actividad = guarani::ev_info_datos();
			$this->ev_info_actividad->set_columnas_a_mostrar(array('actividad_codigo_y_nombre', 'nota', 'origen', 'acta_resolucion', 'fecha_formato', 'fecha_vigencia_formato', 'anio_academico', 'periodo_lectivo'));
			
			$this->ev_info_actividad->set_columnas_fila(array('actividad_codigo_y_nombre', 'nota'), 1);
			$this->ev_info_actividad->set_columnas_fila(array('origen', 'acta_resolucion'), 2);
			$this->ev_info_actividad->set_columnas_fila(array('fecha_formato', 'fecha_vigencia_formato'), 3);
			$this->ev_info_actividad->set_columnas_fila(array('anio_academico', 'periodo_lectivo'), 4);
			
			$this->ev_info_actividad->set_max_columnas_por_fila(2);
			
			$this->ev_info_actividad->set_etiqueta('actividad_codigo_y_nombre', 'Actividad');
			$this->ev_info_actividad->set_etiqueta('nota', 'Nota');
			
			$this->ev_info_actividad->set_etiqueta('origen', 'Origen');
			$this->ev_info_actividad->set_etiqueta('acta_resolucion', 'Acta/Resolución');
			
			
			//Cambiar campo dependiendo origen
			$this->ev_info_actividad->set_etiqueta('fecha_formato', 'Fecha');
			$this->ev_info_actividad->set_etiqueta('fecha_vigencia_formato', 'Fecha de Venc. Actual');
			
			$this->ev_info_actividad->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info_actividad->set_etiqueta('periodo_lectivo', 'Período Lectivo');
			
			$this->ev_info_actividad->set_td_etiqueta_width('125px');
			$this->ev_info_actividad->set_td_valores_width('330px');
			
			$this->ev_info_alumno->set_table_width('');
		}
		return $this->ev_info_actividad;
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Edición -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$this->ev_info_alumno()->set_datos($this->s__datos_alumno);
		$this->ev_info_actividad()->set_datos($this->get_datos_actividad());
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		try {
			$this->cn()->guardar();
			$this->evt__cancelar();
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (guarani_error $e)	{
			throw $e;
		}
	}

	function evt__cancelar_preseleccion()
	{
		unset($this->s__alumno);
		unset($this->s__datos_alumno);
		parent::evt__cancelar_preseleccion();
	}

	function evt__cancelar_seleccion()
	{
		unset($this->s__id_acta);
		unset($this->s__equivalencia);
		unset($this->s__fecha_vencimiento_actual);
		$this->set_pantalla('seleccion');
	}

	function evt__cancelar()
	{
		$this->cn()->resetear();
		$this->set_pantalla('seleccion_modificatoria');
	}

	//-----------------------------------------------------------------------------------
	//---- Javascript -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function js_evt_guardar() 
	{
		$msj_confirmacion = toba::proyecto()->get_mensaje_proyecto('800MAT_confirmar_modif_venc_regularidad');
		$msj_confirmacion = $msj_confirmacion['m'];
		return "
			{$this->objeto_js}.evt__guardar = function() {
				if (confirmar_no_cambios()) {
					var msj = '$msj_confirmacion';
					var fecha = '<Sin Vencimiento>';
					if (this.dep('editor').dep('formulario').ef('fecha_vigencia_nuevo').tiene_estado()) {
						var fecha = this.dep('editor').dep('formulario').ef('fecha_vigencia_nuevo').get_estado();
					}
					resp = confirm(msj.replace('%1%', fecha));
					if (! resp) {
						return false;
					}
					return true;
				}
				return false;
			}
		";
	}
}	
?>