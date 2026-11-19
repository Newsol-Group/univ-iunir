<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Inscripciones
 */
class ci_nav_baja_insc_propuesta extends ci_nav_sin_listado_inicial
{

	protected $popup = false;

	//---- cuadro -----------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$eliminado = false;

		try {
			$eliminado = $this->cn()->eliminar_inscripcion_a_propuesta($seleccion['propuesta_aspira'], $seleccion['fecha_inscripcion'], $seleccion['persona'], $seleccion['propuesta'], $seleccion['alumno']);

			if ($eliminado) {
				// Se notifica el éxito en la eliminación.
				$mensaje = guarani_mensajes::get('800MAT_insc_eliminar_ok', array($seleccion['propuesta_nombre_abreviado']));
				$nivel	 = comunes::error_informacion;
			} else {
				// Se notifica el fracaso de la eliminación porque el alumno registra historia académica en la propuesta.
				$mensaje = guarani_mensajes::get('800MAT_baja_insc_hist_acad', array($seleccion['propuesta_nombre_abreviado']));
				$nivel	 = comunes::error_critico;
			}

			toba::notificacion()->agregar($mensaje, $nivel);
		} catch (guarani_error $e) {
			$mje = guarani_mensajes::get('eliminar_error');
			toba::notificacion()->agregar($mje, 'error');
		} catch (toba_error_db $e) {
			$mje = ($e->get_sqlstate() == 'db_23503') ? guarani_mensajes::get('800MAT_baja_insc_actividad', array($seleccion['propuesta_nombre_abreviado'])) : guarani_mensajes::get('eliminar_error');
			toba::notificacion()->agregar($mje, 'error');
		}

		if ($eliminado) {
			$nro_transaccion_log = comunes::get_nuevo_nro_transaccion();
	
			// Preparo los datos para la impresion
			$gimp = new guarani_datos_impresion();
			
			$gimp->set_titulo('Comprobante de Baja de Inscripción a Propuesta');
			$gimp->set_conf(array('propuesta'=>$seleccion['propuesta'], 'nro_transaccion' => $nro_transaccion_log, 'id_persona' => $seleccion['persona'], 'envio_autom' => true));
			// Armo el arreglo de datos
			$gimp->nueva_fila_datos('Apellido y Nombre',   $seleccion['apellido_y_nombres']);
			$gimp->nueva_fila_datos('Identificación',  $seleccion['nro_identificacion']);
			$gimp->nueva_fila_datos('Propuesta',  $seleccion['propuesta_descripcion']);
			$gimp->nueva_fila_datos('Plan de Estudios',  $seleccion['plan_nombre']);
			$gimp->nueva_fila_datos('Modalidad',  $seleccion['modalidad_nombre']);
			$gimp->nueva_fila_datos('Ubicación',  $seleccion['ubicacion_nombre']);
			$gimp->nueva_fila_datos('Fecha de Baja', guarani_fecha::get_hoy(true));
			
			//Almaceno los datos en memoria para recuperarlos luego para la impresión de comprobante
			$gimp->guardar_datos_imp();
			
			// Indico que se debe abrir el Popup
			$this->popup = true;
		}
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
		$mensaje = guarani_mensajes::get('800MAT_confirmar_baja_insc', array($datos[$fila]['propuesta_nombre_abreviado']));
		$evento->set_msg_confirmacion($mensaje);
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if ($this->popup) {
			$url = toba::vinculador()->get_url(toba::proyecto()->get_id(), '34000020', array(), array('celda_memoria'=>'celda_impresion'));
			echo "
				abrir_popup('impresion', '$url', {'resizable': 1, 'scrollbars':'1', 'width': '700px', 'height': '350px'});
			";
			$this->popup = false;
		}
	}
	
}
?>
