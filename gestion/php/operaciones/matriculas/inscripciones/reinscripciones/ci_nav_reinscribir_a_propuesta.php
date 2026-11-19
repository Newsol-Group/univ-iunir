<?php
/**
 *
 *
 * @package		Matrícula
 * @subpackage	Inscripciones
 * 
 */
class ci_nav_reinscribir_a_propuesta extends ci_nav_sin_listado_inicial
{
	protected $popup = false;
	protected $s__anio_academico;

	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$this->s__anio_academico = $datos['anio_academico']['valor'];
			$this->dep('filtro')->columna('anio_academico')->resetear_estado();
			$this->s__filtro = $this->dep('filtro')->get_sql_where();
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
			$this->s__anio_academico = '';
		}
	}

	function evt__filtro__cancelar()
	{
		parent::evt__filtro__cancelar();
		unset($this->s__anio_academico);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$this->indx_msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro)) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			$datos = $this->cn()->get_listado_reinscripcion_propuesta($this->s__anio_academico, $this->get_filtro_condicion());
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}

	function evt__cuadro__seleccion($seleccion)
	{
		try {
			$nro_transaccion = comunes::get_nuevo_nro_transaccion();
			$this->cn()->reinscribir_a_propuesta($seleccion, $this->s__anio_academico, $nro_transaccion);
			$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($seleccion['alumno']);
			
			$mensaje = guarani_mensajes::get('800MAT_reinscripcion_ok', array($this->s__anio_academico, $seleccion['propuesta_nombre_abreviado'], $datos_alumno['apellido_y_nombre']));
			toba::notificacion()->info($mensaje);

			// Datos para la impresion
			guarani_datos_impresion::instancia()->set_titulo('Comprobante de Reinscripción a Propuesta');
			guarani_datos_impresion::instancia()->set_conf(array('propuesta' => $seleccion['propuesta'], 'nro_transaccion' => $nro_transaccion, 'id_persona' => $seleccion['persona'], 'envio_autom' => true));
			
			// Arreglo de datos
			guarani_datos_impresion::instancia()->nueva_fila_datos('Apellido y Nombre', $seleccion['apellido_y_nombre']);
			guarani_datos_impresion::instancia()->nueva_fila_datos('Identificación', $seleccion['documento_principal']);
			guarani_datos_impresion::instancia()->nueva_fila_datos('Legajo', $datos_alumno['legajo']);
			guarani_datos_impresion::instancia()->nueva_fila_datos('Propuesta', $seleccion['propuesta_descripcion']);
			guarani_datos_impresion::instancia()->nueva_fila_datos('Plan de Estudios', $seleccion['plan_nombre']);
			guarani_datos_impresion::instancia()->nueva_fila_datos('Modalidad', $seleccion['modalidad_nombre']);
			guarani_datos_impresion::instancia()->nueva_fila_datos('Ubicación', $seleccion['ubicacion_nombre']);
			guarani_datos_impresion::instancia()->nueva_fila_datos('Año Académico', $this->s__anio_academico);
			guarani_datos_impresion::instancia()->nueva_fila_datos('Fecha de Reinsc.', guarani_fecha::get_hoy(true));

			// Se almacenan los datos en memoria para recuperarlos luego para la impresión de comprobante
			guarani_datos_impresion::instancia()->guardar_datos_imp();

			// Se indica que se debe abrir el Popup
			$this->popup = true;
		} catch (toba_error $e) {
			throw $e;
		}
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();

		// Si el alumno está reinscripto en el año académico se anula el evento.
		if ($datos[$fila]['reinscripto'] == comunes::si_desc) {
			$evento->anular();
		} else {
			// Mensaje de confirmación.
			$mensaje = guarani_mensajes::get('800MAT_confirmar_reinscripcion', array($datos[$fila]['apellido_y_nombre'], $datos[$fila]['propuesta_nombre_abreviado'], $this->s__anio_academico));
			$evento->set_msg_confirmacion($mensaje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if ($this->popup) {
			$url = toba::vinculador()->get_url(toba::proyecto()->get_id(), '34000020', array(), array('celda_memoria' => 'celda_impresion'));
			echo "
				abrir_popup('impresion', '$url', {'resizable': 1, 'scrollbars':'1', 'width': '700px', 'height': '500px' });
			";
			$this->popup = false;
		}
	}

}
?>
