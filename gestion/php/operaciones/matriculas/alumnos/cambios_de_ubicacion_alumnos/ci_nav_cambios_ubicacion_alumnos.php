<?php
/**
 * Representa los diferentes cambios de ubicación sobre la propuesta a la que está inscripto un alumno.
 *
 *
 * @package Matricula
 * @subpackage Personas/Alumnos/Cambios de Ubicación de Alumnos
 */

class ci_nav_cambios_ubicacion_alumnos extends ci_nav_con_preseleccion
{
	protected $s__alta_cambio_ubicacion = false;

	/**
	 * Se configura el concepto para el mensaje de eof del cuadro de preselección, invocando a la función del ci padre 
	 * (ci_nav_con_preseleccion). El concepto que el usuario elige en la pantalla de preselección es un Alumno
	 * (Persona + Propuesta).
	 */
	function ini()
	{
		parent::set_concepto_preseleccion('Alumno');
	}

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial_preseleccion();
	}
	
	//Determina si se está dando de alta un nuevo cambio de ubicación.
	function es_ubicacion_nueva()
	{
		return $this->s__alta_cambio_ubicacion;
	}
	
	/**
	 * Se redefine la función abstracta del ci de nav con preselección para setear los datos correspondientes ante el
	 * evento de selección en el cuadro_preseleccion. En este caso se realiza el cargar de la relación a partir del
	 * alumno seleccionado en la primer pantalla.
	 */
	function set_preseleccion($seleccion)
	{
		$this->cn()->cargar($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- Dependencias -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Se redefine la función a partir de la cual se cargan los datos
	 * (en el cn manejador de alumnos habrá varios listados de preselección ya que, según la operación
	 * desde la que se invoque, deben filtrarse diferentes datos de los alumnos).
	 */

	protected function get_listado_preseleccion()
	{
		$filtro = empty($this->s__filtro_preseleccion) ? 'true' : $this->s__filtro_preseleccion;
		// Sólo los alumnos de calidad "Activo" pueden cambiar de ubicación.
		$filtro .= " AND sga_alumnos.calidad = '".alumno::calidad_activo."'";
		return $this->cn()->get_listado($filtro);
	}
	
	function conf__info_alumno_prop(toba_ei_formulario $form)
	{
		$datos = $this->cn()->entidad()->get_datos_alumno();
		if ($datos['regular'] == comunes::si) {
			$datos['regular_descr'] = comunes::si_desc;
		} else {
			$datos['regular_descr'] = comunes::no_desc;
		}
		$datos['apellido_nombres'] = $datos['apellido'] . ', ' . $datos['nombres'];
		$form->set_datos($datos);
	}

	/**
	 * Configura el cuadro de la selección luego de la preselección. Para esto solo debe recuperar los datos de la tabla
	 * hija de la relación (alum_hist_ubicacion), ya que se cargó la relación con todos los datos del alumno elegido en la 
	 * preselección.
	 *
	 * @param toba_ei_cuadro $cuadro
	 */
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cambios_ubicaciones_alumno = $this->cn()->entidad()->get_datos_alum_historial_ubicacion();	
		$cuadro->set_datos($cambios_ubicaciones_alumno);
		$msj = $this->get_msg_eof('alum_hist_ubicacion');
		$cuadro->set_eof_mensaje($msj);
	}
	
	/**
	 * Ante el evento de selección del cuadro de cambios de ubicaciones del alumno, sólo debe setearse el cursor en la
	 * tabla hija, dado que la relación ya está cargada para el alumno elegido en preselección con todo su historial de
	 * ubicaciones.
	 * El cursor se setea directamente a partir de la selección ya que se utiliza como clave del cuadro la clave del dt.
	 */
	function evt__cuadro__seleccion($seleccion)
	{
		$this->cn()->entidad()->set_cursor_historial_ubicaciones(current($seleccion));
		$this->set_pantalla('edicion');
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Se redefine el evento agregar (del ci_navegacion), ya que en ese caso no necesita crearse una entidad nueva (desde
	 * el cn manejador), debido a que ya se realizó el cargar en la preselección. Por lo tanto solo se creará un registro 
	 * nuevo para la tabla hija de historial de ubicaciones, desde en cn entidad.
	 *
	 */
	function evt__agregar()
	{
		$this->s__alta_cambio_ubicacion = true;
		$this->cn()->entidad()->nuevo_cambio_ubicacion();
		$this->set_pantalla('edicion');
	}
	
	/**
	 * Se redefine el evento cancelar (del ci_navegacion), ya que en este caso el cn recién se va a resetear cuando se 
	 * dispare el evento cancelar_preseleccion (se cancela la selección del alumno, y por lo tanto se resetea toda la 
	 * carga de la entidad). Cuando se cancela la selección del cambio de ubicación sólo debe resetearse el cursor 
	 * posicionado sobre esta tabla hija, y en caso de estar en el Agregar (no en edición), eliminar la nueva fila de
	 * ubicación que se había creado ante dicho evento.
	 *
	 */
	function evt__cancelar()
	{
		if ($this->s__alta_cambio_ubicacion) {
			$this->cn()->entidad()->eliminar_cambio_ubicacion();
			$this->s__alta_cambio_ubicacion = false;
		} else {
			$this->cn()->entidad()->unset_cursor_historial_ubicaciones();
		}
		$this->set_pantalla('seleccion');
	}
	
	/**
	 * Se redefine el evento guardar (del ci_navegacion), ya que en este caso el cn no debe resetearse hasta que no se
	 * dispare el evento cancelar_preseleccion (se cancela la selección del alumno, y por lo tanto se resetea toda la 
	 * carga de la entidad). Cuando se guardan los cambios realizados sobre el historial de ubicación del alumno, sólo 
	 * debe resetearse el cursor posicionado sobre esta tabla hija y resetear la variable de sesion que señaliza el Alta 
	 * (todo esto en caso de Volver a la pantalla de selección, no si se queda al guardar en la pantalla de edición).
	 *
	 */
	function evt__guardar()
	{
		try {
			$this->cn()->guardar();
			if ($this->volver_al_guardar) {
				//Si desde el ci de edición se seteara la condición de No volver a selección al guardar, no se hace nada.
				$this->cn()->entidad()->unset_cursor_historial_ubicaciones();
				$this->s__alta_cambio_ubicacion = false;
				$this->set_pantalla('seleccion');
			}
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (guarani_error $e)	{
			$e->notificar();
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_confirmacion = guarani_mensajes::get('800_abandono_edicion');
		$msj_sin_cambios = guarani_mensajes::get('800_no_hubo_cambios');
		
		$confirmar = 'var confirmar = toba.hay_cambios();';
		
		echo $this->js_evt_cancelar();
		echo "
			{$this->objeto_js}.evt__guardar = function() {
				return confirmar_no_cambios();
			}

			function confirmar_cambios(proyecto, operacion, url, es_popup) {
				if (! es_popup || typeof(es_popup) == 'undefined') {
					$confirmar
					if (confirmar) {
						resp = confirm('$msj_confirmacion');
						if (! resp) {
							return false;
						}
					}
				}
				return true;
			}
			
			function confirmar_no_cambios(proyecto, operacion, url, es_popup) {
				if (! es_popup || typeof(es_popup) == 'undefined') {
					$confirmar
					if (! confirmar) {
						alert('$msj_sin_cambios');
						return false;
					}
				}
				return true;
			}
			
            toba.set_callback_menu(confirmar_cambios);
		";
	}
}

?>