<?php
/**
 * 	
 *
 * @package		Administración
 * @subpackage 	Parámetros
 * 
 */
class ci_nav_parametros extends ci_navegacion
{
	protected $s__pantalla_retorno;
	
	/**
	 * Se establece a qué vista (todos los parámetros / parámetros por operación)
	 * se debe regresar después de una edición.
	 * 
	 * @param string $pantalla
	 */
	function set_pantalla_retorno($pantalla)
	{
		$this->s__pantalla_retorno = $pantalla;
	}
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_listado($this->get_filtro_condicion());
		$cuadro->set_datos($datos);
		$msj = $this->get_msg_eof();
		$cuadro->set_eof_mensaje($msj);
	}
	
	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__guardar()
	{
		try {
			$this->cn()->guardar();
			if ($this->volver_al_guardar) {
				$this->evt__cancelar();
			}
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (guarani_error $e)	{
			throw $e;
		} 
	}
	
	function evt__cancelar()
	{
		// Reset de varias de la vista
		$this->dep('editor')->dep('editor_nivel_entidad')->unset_vars();
		// Se preserva la pantalla de retorno
		if (!empty($this->s__pantalla_retorno)) {
			$pantalla_retorno = $this->s__pantalla_retorno;
		}
		// Se resetea CN y se regresa a la pantalla principal
		parent::evt__cancelar();
		// Se establece el tab de la vista correspondiente
		if (!empty($pantalla_retorno)) {
			$this->dep('selector')->set_pantalla($pantalla_retorno);
		}
	}

}
?>