<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class ci_nav_requisitos_ingreso extends ci_navegacion
{
	function conf()
	{
		if (guarani::get_id_operacion() == '800SIUREQ011') {
			$this->set_titulo('Definir Requisitos de Egreso');
			$this->cn()->set_modo_egreso();
		}
		parent::conf();
		//Se quita el filtro porque la unica columna que tiene para filtrar es fija para esta operación. 
		//Si se llegan a habilitar más tipos de requisitos borrar las siguientes 4 líneas
		if ($this->get_id_pantalla() == 'seleccion_nueva') { 
			$this->pantalla()->eliminar_dep('filtro');
			$this->dep('cuadro')->eliminar_columnas(array('tipo_nombre'));
		}
	}
	
	function evt__agregar()
	{
		parent::evt__agregar();
		$this->set_pantalla('seleccion_nueva');
	}

	function evt__guardar()
	{
		// Se desactivan las transacciones para genererar una única que contenga los dos pasos: guardar y aplanar (si es necesario) el requisito de ingreso.
		$this->cn()->desactivar_transacciones_anidadas();

		try {
			toba::db()->abrir_transaccion();

			$this->cn()->guardar();

			if ($this->cn()->entidad()->hay_que_generar_aplanado()) {
				// Se ejecuta el proceso de aplanado del requisito de ingreso.
				$requisito = $this->cn()->entidad()->get_datos();
				$aplanador = toba::cn($this->cn()->get_proceso_aplanado());
				$aplanador->procesar(array('requisito_propuesta' => $requisito['requisito_propuesta']));
			}

			$this->cn()->resetear();
			$this->set_pantalla('seleccion');
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');

			toba::db()->cerrar_transaccion();
		} catch (guarani_error $e) {
			toba::db()->abortar_transaccion();
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_listado_disponibles($this->s__filtro);
		$cuadro->set_datos($datos);
		$msj = $this->get_msg_eof();
		$cuadro->set_eof_mensaje($msj);
	}
	
	function evt__cuadro__seleccion($seleccion,$es_nuevo=true)
	{
		if ($es_nuevo) {
			$this->cn()->entidad()->agregar_requisito($seleccion);
		} else {
			$this->s__seleccion = $seleccion;
			$this->cn()->cargar($seleccion);
		}
		$this->set_pantalla('edicion');
	}
	
}
?>