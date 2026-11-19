<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Alumnos
 * 
 */
class ci_edi_cambio_plan extends guarani_pers_ci
{
	protected $ev_info;
	protected $ev_info_resumen;

	protected $s__datos_plan_anterior;

	protected $datos;
	protected $seleccion;

	//-----------------------------------------------------------------------------------
	//---- Auxiliares
	//-----------------------------------------------------------------------------------

	function get_datos()
	{
		$datos = array();
		$datos['plan_version'] = $this->seleccion['plan_version'];
		$datos['motivo_plan'] = $this->datos['motivo_plan'];
		$datos['documento'] = $this->datos['documento'];
		$datos['observaciones'] = $this->datos['observaciones'];
		if ($this->datos['tipo_cambio'] == alumno::tipo_cambio_solicitado) {
			$datos['tipo'] = ($this->datos['motivo_plan'] == alumno::motivo_plan_cambio) ? alumno::cambio_plan_tipo_solicitado : alumno::cambio_plan_version_tipo_solicitado;
		} else {
			$datos['tipo'] = ($this->datos['motivo_plan'] == alumno::motivo_plan_cambio) ? alumno::cambio_plan_tipo_forzado : alumno::cambio_plan_version_tipo_forzado;
		}
		return $datos;
	}

	function get_seleccion()
	{
		return $this->seleccion;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: pant_inicial
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial()
	{
		$alumno = $this->controlador()->get_alumno_seleccionado();
		$filtro = 'sga_alumnos.alumno = '.toba::db()->quote($alumno);
		$datos = current(guarani::co('co_alumnos')->get_listado($filtro));

		$this->ev_info()->set_datos($datos);

		// Se salvan los datos del plan/plan-versión actual para el resumen.
		$this->s__datos_plan_anterior['plan_original_codigo'] = $datos['plan_codigo'];
		$this->s__datos_plan_anterior['plan_version_original_codigo'] = $datos['plan_version_codigo'];

		// Seteo de parámetros para recuperar en popup.
		toba::memoria()->set_dato('alumno', $alumno);
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array(	'apellido', 'nombres', 'ubicacion_nombre',
															'documento_principal', 'legajo',
															'propuesta_nombre_abreviado', 'plan_codigo', 'plan_version_codigo'));
			
			$this->ev_info->set_columnas_fila(array('apellido', 'nombres', 'ubicacion_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('documento_principal', 'legajo'), 2);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre_abreviado', 'plan_codigo', 'plan_version_codigo'), 3);
			
			
			$this->ev_info->set_max_columnas_por_fila(3);
			
			$this->ev_info->set_etiqueta('apellido', 'Apellido');
			$this->ev_info->set_etiqueta('nombres', 'Nombres');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación Actual');
			
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');
			
			$this->ev_info->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_codigo', 'Plan');
			$this->ev_info->set_etiqueta('plan_version_codigo', 'Versión');
			
			$this->ev_info->set_td_etiqueta_width('120px');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->datos)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos);		
		}
	}

	function evt__formulario__modificacion($datos)
	{
		$this->datos = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_planes ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_planes(cuadro_seleccion_diferida $cuadro)
	{
		if (! empty($this->datos['destino'])) {
			$alumno = $this->controlador()->get_alumno_seleccionado();
			switch ($this->datos['destino']) {
				case alumno::cambio_plan_destino_activos_vigentes:
					$datos = guarani::co('co_alumnos')->get_planes_activos_vigentes($alumno);
					break;
				case alumno::cambio_plan_destino_activos_no_vigentes:
					$datos = guarani::co('co_alumnos')->get_planes_activos_no_vigentes($alumno);
					break;
				case alumno::cambio_plan_destino_anteriores:
					$datos = guarani::co('co_alumnos')->get_planes_anteriores_alumno($alumno);
					break;
				case alumno::cambio_plan_version_destino_activas_vigentes:
					$datos = guarani::co('co_alumnos')->get_versiones_activas_vigentes($alumno);
					break;
				case alumno::cambio_plan_version_destino_activas_no_vigentes:
					$datos = guarani::co('co_alumnos')->get_versiones_activas_no_vigentes($alumno);
					break;
				case alumno::cambio_plan_version_destino_anteriores:
					$datos = guarani::co('co_alumnos')->get_versiones_anteriores_alumno($alumno);
					break;
			}
			$cuadro->set_datos($datos);

			$etiqueta = guarani_etiquetas::get_tabla('sga_planes');
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro_filtrado', array($etiqueta->get_enf('plural'))));
		} else {
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800MAT_filtrar_planes'));
		}
	}

	function evt__cuadro_planes__seleccion($seleccion)
	{
		$this->seleccion = $seleccion;
	}

	function conf_evt__cuadro_planes__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_planes')->get_datos();
		if ($datos[$fila]['estado'] == planes::estado_baja) {
			$evento->anular();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: informacion
	//-----------------------------------------------------------------------------------

	function conf__informacion()
	{
		$filtro = 'sga_alumnos.alumno = '.toba::db()->quote($this->controlador()->get_alumno_seleccionado());
		$datos = current(guarani::co('co_alumnos')->get_listado($filtro));
		$datos = array_merge($datos, $this->s__datos_plan_anterior);
		$datos['tipo_cambio'] = ($this->datos['tipo_cambio'] == alumno::tipo_cambio_forzado) ? 'Forzado' : 'Solicitado';

		$this->ev_info_resumen()->set_datos($datos);

		$this->controlador()->pantalla('edicion')->eliminar_evento('confirmar');
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info_resumen --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info_resumen()
	{
		if (!isset($this->ev_info_resumen)) {
			$this->ev_info_resumen = guarani::ev_info_datos();
			$this->ev_info_resumen->set_columnas_a_mostrar(array(	'apellido', 'nombres', 'propuesta_nombre_abreviado',
																	'plan_original_codigo', 'plan_version_original_codigo',
																	'plan_codigo', 'plan_version_codigo',
																	'tipo_cambio'));
			
			$this->ev_info_resumen->set_columnas_fila(array('apellido', 'nombres', 'propuesta_nombre_abreviado'), 1);
			$this->ev_info_resumen->set_columnas_fila(array('plan_original_codigo', 'plan_version_original_codigo'), 2);
			$this->ev_info_resumen->set_columnas_fila(array('plan_codigo', 'plan_version_codigo'), 3);
			$this->ev_info_resumen->set_columnas_fila(array('tipo_cambio'), 4);
			
			$this->ev_info_resumen->set_max_columnas_por_fila(3);
			
			$this->ev_info_resumen->set_etiqueta('apellido', 'Apellido');
			$this->ev_info_resumen->set_etiqueta('nombres', 'Nombres');
			$this->ev_info_resumen->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			
			$this->ev_info_resumen->set_etiqueta('plan_original_codigo', 'Plan Original');
			$this->ev_info_resumen->set_etiqueta('plan_version_original_codigo', 'Versión Original');
			
			$this->ev_info_resumen->set_etiqueta('plan_codigo', 'Plan Actual');
			$this->ev_info_resumen->set_etiqueta('plan_version_codigo', 'Versión Actual');
			
			$this->ev_info_resumen->set_etiqueta('tipo_cambio', 'Tipo de Cambio');
			
			$this->ev_info_resumen->set_td_etiqueta_width('120px');
			
			$this->ev_info_resumen->set_style("border-style: solid; border-width: 1px; border-color: #DDD; width=100%");
		}
		return $this->ev_info_resumen;
	}
}
?>