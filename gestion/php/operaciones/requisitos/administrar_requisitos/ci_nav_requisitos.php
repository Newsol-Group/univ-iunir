<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos
 * 
 */
class ci_nav_requisitos extends ci_navegacion
{
	
	function evt__agregar()
	{
		//Seteo el parámetro proviene_de_abm para indicar que la llamada se realiza desde
		//el ABM de Requisitos y no desde la opción de Asociación de Requisitos a Acciones
		toba::memoria()->set_dato('proviene_de_abm', true);
		parent::evt__agregar();
	}
	
	function evt__cancelar()
	{
		toba::memoria()->eliminar_dato('proviene_de_abm');
		parent::evt__cancelar();
	}

	function evt__eliminar()
	{
		toba::memoria()->eliminar_dato('proviene_de_abm');
		try {
			$this->cn()->eliminar();
			toba::notificacion()->agregar($this->get_mensaje('eliminar_ok'), 'info');
			$this->set_pantalla('seleccion');
		} catch (guarani_error $e) {
			$e->notificar();
		} catch (toba_error $e) {
			$this->cn()->resetear();
			$this->cn()->cargar($this->s__seleccion);
			// Mensaje de error de BD en la eliminación del requisito por existencia de FKs.
			$mje = ($e->get_sqlstate() == 'db_23503') ? guarani_mensajes::get('800REQ_baja_requisito') : guarani_mensajes::get('eliminar_error');
			toba::notificacion()->agregar($mje, 'error');
			toba::logger()->error($mje);
		}
	}

	function evt__guardar()
	{
		toba::memoria()->eliminar_dato('proviene_de_abm');
		parent::evt__guardar();
	}
	
}
?>