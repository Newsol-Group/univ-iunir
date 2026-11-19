<?php
/**
 *
 * 
 * @package		Exámenes
 * @subpackage	Inscripciones
 * 
 */
class ci_edi_baja_insc_examen extends guarani_pers_ci
{
	protected $popup = false;

	//-----------------------------------------------------------------------------------
	//---- cuadro_inscripciones ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_inscripciones(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_insc_examen');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural'))));

		$alumno = toba::db()->quote($this->controlador()->get_seleccion());
		$filtro = 'sga_alumnos.alumno = ' . $alumno;
		$datos = toba::consulta_php('co_inscripciones_examenes')->get_listado($filtro);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_inscripciones__eliminar($seleccion)
	{
		$filtro = "sga_insc_examen.inscripcion = {$seleccion['inscripcion']}";
		$datos = current(toba::consulta_php('co_inscripciones_examenes')->get_listado($filtro));

		$nro_transaccion_log = toba::consulta_php('co_inscripciones_examenes')->get_nuevo_nro_transaccion();
		$this->cn()->eliminar_inscripcion_examen($datos, $nro_transaccion_log);

                $where = "sga_insc_examen_log.nro_transaccion_log = $nro_transaccion_log";
                $insc_examen_log = toba::consulta_php('co_inscripciones_examenes')->get_insc_examen_log($where);
                // Recupero la fecha de la baja
                if (!empty($insc_examen_log)) {
                    $datos['fecha_operacion'] = $insc_examen_log[0]['fecha_operacion'];
                // En caso de no poder recuperarala tomo la fecha de la DB
                } else {
                    $datos['fecha_operacion'] = guarani_fecha::get_ahora(false);
                }
                
		$formateo = new toba_formateo('html');
		
		// Se preparan los datos para la impresión
		guarani_datos_impresion::instancia()->set_titulo('Comprobante de Baja de Inscripción a Examen');
		guarani_datos_impresion::instancia()->set_conf(array('propuesta' => $datos['propuesta'], 'nro_transaccion' => $nro_transaccion_log, 'id_persona' => $datos['persona'], 'envio_autom' => true));
		
		// Se arma el arreglo de datos
		guarani_datos_impresion::instancia()->nueva_fila_datos('Alumno', $datos['apellido_y_nombre']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Identificación', $datos['nro_identificacion']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Legajo', $datos['legajo']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Propuesta', $datos['propuesta_nombre']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Actividad', $datos['actividad_nombre']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Fecha de Insc.', $formateo->formato_fecha_hora($datos['fecha_inscripcion']));
		guarani_datos_impresion::instancia()->nueva_fila_datos('Fecha de Baja', $formateo->formato_fecha_hora($datos['fecha_operacion']));
		guarani_datos_impresion::instancia()->nueva_fila_datos('Año Académico', $datos['anio_academico']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Turno', $datos['turno_nombre']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Llamado', $datos['llamado_nombre']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Mesa de Exámen', $datos['mesa_nombre']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Fecha', $formateo->formato_fecha_hora($datos['mesa_examen_fecha']));
		guarani_datos_impresion::instancia()->nueva_fila_datos('Horario', $datos['horario_examen']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Inscripto para rendir', $datos['instancia_nombre']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Cátedra', $datos['catedra_nombre']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Ubicación', $datos['ubicacion_nombre']);

		// Se almacenan los datos en memoria para recuperarlos luego para la impresión de comprobante
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		// Se indica que se debe abrir el popup
		$this->popup = true;
	}

	function conf_evt__cuadro_inscripciones__eliminar(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_inscripciones')->get_datos();
		$mensaje = guarani_mensajes::get('800EXA_confirmar_baja_insc', array($datos[$fila]['actividad_nombre']), false);
		$evento->set_msg_confirmacion($mensaje);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if ($this->popup) {
			$url = toba::vinculador()->get_url(toba::proyecto()->get_id(), '34000020', array(), array('celda_memoria' => 'celda_impresion'));
			echo "
				abrir_popup('impresion', '$url', {'width': '700px', 'height': '550px', 'resizable': 1, 'scrollbars': '1'});
			";
			$this->popup = false;
		}
	}

}