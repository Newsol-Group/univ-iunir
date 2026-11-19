<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Solicitudes
 * 
 */
class ci_nav_administrar_solicitudes_constancias extends ci_navegacion
{
	// Variable que guarda el parámetro solicitud si se invoca desde la operación de Solicitar Constancias.
	protected $s__solicitud_inicial;
	
	function ini__operacion()
	{
		parent::ini__operacion();
		toba::memoria()->desactivar_reciclado();
		$this->s__solicitud_inicial = toba::memoria()->get_parametro('solicitud');
		
		if (isset($this->s__solicitud_inicial)) {
			$this->s__seleccion = array('solicitud' => $this->s__solicitud_inicial);
			$this->seleccionar_solicitud_a_administrar($this->s__seleccion);
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function get_solicitud_seleccionada()
	{
		return current($this->s__seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial();
	}
	
	function conf__administrar_solicitud(toba_ei_pantalla $pantalla)
	{
		$this->evento('eliminar')->set_msg_confirmacion($this->get_msg_confirmar_eliminar());
	}
	
	//-----------------------------------------------------------------------------------
	//---- Dependencias -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//------ Cuadro ---------------------------------------------------------------------
	
	// Se redefine la selección del cuadro ya que no existe un DR a cargar.
	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->seleccionar_solicitud_a_administrar($seleccion);
	}
	
	function seleccionar_solicitud_a_administrar($solicitud)
	{
		$this->set_pantalla('administrar_solicitud');
		// Se setea como pantalla actual del ci de edición la pantalla correspondiente según el estado de la solicitud seleccionada.
		$datos = toba::consulta_php('co_constancias')->get_solicitud_estado($this->get_solicitud_seleccionada());
		switch ($datos['estado']) {
			case constancias::estado_solicitud_pendiente:
				$this->dependencia('editor')->set_pantalla('pant_imprimir_rechazar');
				break;
			case constancias::estado_solicitud_impreso: 
				$this->dependencia('editor')->set_pantalla('pant_registrar_entrega');
				break;
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
		$this->dependencia('editor')->resetear_vars();
		
		if (isset($this->s__solicitud_inicial)) {
			//Limpio la memoria
			unset($this->s__solicitud_inicial);
			$this->cn()->resetear();
			// Vuelvo a la operacion de Solicitar Constancias
			try {
				echo toba_js::abrir();
				echo "document.location.href='" . toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '37000079', array()) . "'";
				echo toba_js::cerrar();
			} catch (guarani_error $e) {
				throw $e;
			}
		} else {
			parent::evt__cancelar();			
		}
	}
	
	function evt__eliminar()
	{
		// Se realizan las actualizaciones correspondientes a la Eliminación de la Solicitud.
		$solicitud = $this->get_solicitud_seleccionada();
		$this->cn()->eliminar_solicitud_constancia($solicitud);
		// Se informa el éxito de la eliminación.
		$msje = guarani_mensajes::get('eliminar_ok');
		toba::notificacion()->agregar($msje, comunes::error_informacion);
		$this->controlador()->evt__cancelar();
	}

}
?>