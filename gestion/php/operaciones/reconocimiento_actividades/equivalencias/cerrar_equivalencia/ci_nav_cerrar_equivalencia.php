<?php
/**
 *
 *
 * @package		Reconocimiento de Actividades
 * @subpackage	Equivalencias
 * 
 */
class ci_nav_cerrar_equivalencia extends ci_nav_sin_listado_inicial
{
	//--------------------------------------------------------------------------
	//---- Auxiliares ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function get_tramite_equiv_seleccionado()
	{
		return current($this->s__seleccion);
	}

	//--------------------------------------------------------------------------
	//---- Configuraciones -----------------------------------------------------
	//--------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->s__pantalla = $this->get_id_pantalla();
		if ($this->s__pantalla == 'pant_cierre') {
			// Se setea el mensaje de confirmación para el evento de Cierre del Trámite de Equivalencia.
			$msj = guarani_mensajes::get('800EQU_confirmar_cierre');
			$this->evento('cerrar_tramite_equivalencia')->set_msg_confirmacion($msj);
		}
	}

	//--------------------------------------------------------------------------
	//------ PANTALLA de Selección 
	//--------------------------------------------------------------------------
	
	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}
	
	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	/**
	 *  Se redefine la selección del cuadro ya que no existe un DR a cargar.
	 */
	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->set_pantalla('pant_cierre');
		$this->cn()->cargar_atributos_parametros($seleccion['propuesta_alumno_id']);
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA de Cierre 
	//--------------------------------------------------------------------------
	
	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__cerrar_tramite_equivalencia()
	{
		try {
			// Se realizan todas las acciones para cerrar el trámite de equivalencia seleccionado.
			$id_tramite = $this->get_tramite_equiv_seleccionado();
			$this->cn()->cerrar_tramite_equivalencia($id_tramite, $this->s__seleccion['digital']);

			// Se informa el éxito del cierre del Trámite de Equivalencia.
			$mje = guarani_mensajes::get('800EQU_cierre_ok');
			toba::notificacion()->agregar($mje, comunes::error_informacion);

			// Una vez cerrada la equivalencia, se desactiva el evento (sólo se podrá Imprimir o Volver a la pantalla inicial).
			$this->evento('cerrar_tramite_equivalencia')->desactivar();
		} catch (guarani_error $e) {
			// Se evita que el control llegue al método 'disparar_eventos' de la clase 'guarani_ci'
			// porque cuando hay más de un mensaje de error los notifica en una ventana sin título (método 'notificar' de la clase 'g3_error').
			// El título es necesario porque en algunos casos contiene el nombre de la actividad que no pasó los controles.
			if ($e->procesar_con_metodo_anterior()) {
				throw $e;
			} else {
				$notificador = toba::notificacion();
				$notificador->set_titulo($e->get_titulo_ventana());
				$errores = $e->get_errores();
				foreach ($errores as $error) {
					toba::logger()->error($error['mensaje']);
					$notificador->agregar($error['mensaje'], $error['tipo']);
				}
			}
		}
	}

}