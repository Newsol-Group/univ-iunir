<?php 
/**
 * 
 * Representa las restricciones aplicadas para realizar una Acción.
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Acción
 * 
 */
class ci_nav_requisitos_x_accion extends ci_navegacion
{
	
	//Mantiene el id del requisito por acción seleccionado por el usuario para su edición en el ci hijo de visualización.
	//Esto es necesario para que en el ci de Edición (ambos controlados por el ci actual) se puedan configurar las pantallas.
	protected $s__requisito_seleccionado;
	
	// Función que permite obtener desde los ci's hijos la acción seleccionada.
	function get_accion_seleccionada()
	{
		return current($this->s__seleccion);
	}

	/**
	 * Al igual que para la pantalla de Edición, se agrega la configuración para la pantalla de Visualización, para que
	 * se tomen en cuenta los cambios realizados sobre el datos relación en la misma. Esto puede ocurrir ya que desde
	 * las diferentes visualizaciones pueden eliminarse Requisitos.
	 *
	 * @param toba_ei_pantalla $pantalla
	 */
	function conf__visualizacion(toba_ei_pantalla $pantalla)
	{
		if ($this->cn()->entidad()->dep('datos')->hay_cambios()) {
				$this->controlador()->set_hubo_cambios();
		}
	}
	
	/**
	 * Se redefine la función de selección del cuadro porque esta operación consta de una pantalla previa a la de edición 
	 * (visualización). 
	 */
	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->cn()->cargar($seleccion);
		$this->set_pantalla('visualizacion');
	}
	
	/**
	 * Se redefine la función correspondiente al evento cancelar porque es necesario resetear las variables de sesión del 
	 * ci de edición controlado por este ci. 
	 */
	function evt__cancelar()
	{
		$this->dependencia('editor')->resetear_variables_sesion();
		parent::evt__cancelar();
	}
	
	/**
	 * Se redefine la función correspondiente al evento guardar porque se vuelve a la pantalla de visualización en lugar
	 * de a la pantalla inicial.
	 */
	function evt__guardar()
	{
		$altas_modificaciones_requisitos_x_accion = $this->cn()->entidad()->get_altas_modificaciones_requisitos_x_accion();

		$requisitos_conf_x_oper = array();
		foreach ($altas_modificaciones_requisitos_x_accion as $id_fila) {
			$datos_grupo_requisito = $this->cn()->entidad()->get_datos_requisito_grupo($this->cn()->entidad()->get_fila_grupo_padre_req_x_accion($id_fila));
			$datos_grupo_requisito['accion'] = $this->s__seleccion['accion'];
			$datos_requisito_accion = $this->cn()->entidad()->get_datos_fila_requisito_x_accion($id_fila);
			$requisitos_conf_x_oper[$id_fila] = toba::consulta_php('co_requisitos_x_operacion')->get_requisitos_conf_x_oper_no_default($datos_grupo_requisito, $datos_requisito_accion);
		}

		// Se desactivan las transacciones para genererar una única que contenga los dos pasos: guardar y aplanar (si es necesario).
		$this->cn()->desactivar_transacciones_anidadas();

		try {
			toba::db()->abrir_transaccion();

			$this->cn()->guardar();

			// Se replica la configuración de requisitos por operación que se reemplazó por la default al editar (por trigger).
			foreach ($altas_modificaciones_requisitos_x_accion as $id_fila) {
				if (isset($requisitos_conf_x_oper[$id_fila])) {
					$datos_requisito_accion = $this->cn()->entidad()->get_datos_fila_requisito_x_accion($id_fila);
					foreach ($requisitos_conf_x_oper[$id_fila] as $conf) {
						guarani::act('act_requisitos_x_operacion')->actualizar_requisito_x_operacion($conf, $datos_requisito_accion['requisito_accion']);
					}
				}
			}

			if ($this->cn()->entidad()->hay_que_generar_aplanado() && !empty($altas_modificaciones_requisitos_x_accion)) {
				// Se ejecuta el proceso de aplanado de la acción.
				$accion = $this->cn()->entidad()->get_datos_accion();
				$aplanador = toba::cn($this->cn()->get_proceso_aplanado());
				$requisitos_x_accion = array();
				foreach ($altas_modificaciones_requisitos_x_accion as $id_fila) {
					$requisitos_x_accion[] = $this->cn()->entidad()->get_datos_fila_requisito_x_accion($id_fila);
				}
				$aplanador->procesar($requisitos_x_accion);
				// Se debe recargar la relación porque se agregaron registros en la tabla tabla de aplanado!
				$this->cn()->resetear();
				$this->cn()->cargar(array('accion'=>$accion['accion']));
			}

			$this->dependencia('editor')->resetear_variables_sesion();
			$this->set_pantalla('visualizacion');
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');

			toba::db()->cerrar_transaccion();
		} catch (guarani_error $e) {
			toba::db()->abortar_transaccion();
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}
	
	/**
	 * Permite setear en el ci de edición la pantalla de configuración de Requisitos como pantalla actual.
	 * Esto es necesario ya que debe navegarse hacia esta pantalla ante la selección de un nodo de un árbol en el ci 
	 * de visualización (ambos controlados por el ci actual).
	 */
	function set_pantalla_configuracion_req()
	{
		$this->set_pantalla('edicion');
		$this->dependencia('editor')->set_pantalla('pant_configurar_requisito');
	}
	
	/**
	 * Permite setear en el ci de edición la pantalla de configuración de Entidades-Estados como pantalla actual.
	 * Esto es necesario ya que debe navegarse hacia esta pantalla ante la selección de un nodo de un árbol en el ci 
	 * de visualización (ambos controlados por el ci actual).
	 */
	function set_pantalla_configuracion_ent_estado()
	{
		$this->set_pantalla('edicion');
		$this->dependencia('editor')->set_pantalla('pant_configurar_ent_estado');
	}
	
	/**
	 * Permite acceder a la variable de sesión que contiene el id del requisito por acción seleccionado en el ci de
	 * Visualización. 
	 */
	function get_visualizador_requisito_seleccionado()
	{
		return $this->s__requisito_seleccionado;
	}
	
	function set_visualizador_requisito_seleccionado($id_req)
	{
		$this->s__requisito_seleccionado = $id_req;
	}
	
}
?>