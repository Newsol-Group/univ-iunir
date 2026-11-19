<?php

class ci_nav_turnos_de_examen extends ci_navegacion
{
	function ini()
	{
		parent::ini();
		$this->set_no_volver_al_guardar();
	}

	function evt__agregar()
	{
		unset($this->s__seleccion);
		$this->dep('edicion_turnos')->set_turno_examen(null);
		$this->cn()->agregar_turno();
		$this->set_pantalla('edicion');
	}

	function evt__eliminar($volver=false)
	{	
		try {
			$this->cn()->eliminar();
			if ($volver) {
				toba::notificacion()->agregar($this->get_mensaje('eliminar_ok'), 'info');
				$this->set_pantalla('seleccion');
			}
		} catch (guarani_error $e)	{
			$e->notificar();
		} catch (toba_error $e) {
			$this->cn()->resetear();
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
		$this->cn()->resetear();
	}

	function evt__guardar()
	{
		// Se desactivan las transacciones para genererar una única que contenga los dos pasos: guardar y aplanar (si es necesario) el período de inscripción.
		$this->cn()->desactivar_transacciones_anidadas();

		try {
			toba::db()->abrir_transaccion();

			$this->cn()->guardar();

			if (method_exists($this->cn()->entidad(), 'hay_que_generar_aplanado') && $this->cn()->entidad()->hay_que_generar_aplanado()) {
				// Se ejecuta el proceso de aplanado del período de inscripción.
				$periodo = $this->cn()->entidad()->get_datos();
				$aplanador = toba::cn($this->cn()->get_proceso_aplanado());
				$aplanador->procesar($periodo['periodo_inscripcion']);
			}
			
			if ($this->volver_al_guardar) {
				$this->cn()->resetear();
				$this->set_pantalla('seleccion');
			}

			toba::db()->cerrar_transaccion();
		} catch (guarani_error $e) {
			toba::db()->abortar_transaccion();
			toba::logger()->error($e->getMessage());
			throw $e;
		}
	}

	function evt__limpiar()
	{
		$this->dep('edicion_turnos')->evt__cancelar();
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->dep('edicion_turnos')->set_turno_examen($seleccion['turno_examen']);
		$this->set_pantalla('edicion');
	}
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		/**
		 *    Si no está seteado el filtro entonces es la carga inicial del cuadro (el usuario no presionó filtrar).
		 *    No se traen datos (no se ejecuta la consulta) y se pone un mensaje en el cuadro que indica al usuario
		 *    que debe filtrar para obtener datos.
		 */
		$this->indx_msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro)) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
            $datos = $this->cn()->get_listado($this->get_filtro_condicion());
            $cuadro->set_datos($datos);

			$img_verde = toba_recurso::imagen_proyecto('iconos_estados/verde.png');
			$img_amarillo = toba_recurso::imagen_proyecto('iconos_estados/amarillo.png');
			$img_rojo = toba_recurso::imagen_proyecto('iconos_estados/rojo.png');
			$datos_turnos = $this->cn()->get_listado($this->get_filtro_condicion());

			foreach ($datos_turnos as $key => $datos) {
				$datos_turnos[$key]['estado'] = toba_recurso::imagen($img_verde, null, null, 'Turno OK');
				if ($datos['cant_llamados_sin_periodo_insc'] > 0) {
					$datos_turnos[$key]['estado'] = toba_recurso::imagen($img_amarillo, null, null, 'Faltan Períodos de Inscripción');
				}
				if ($datos['cant_llamados'] == 0) {
					$datos_turnos[$key]['estado'] = toba_recurso::imagen($img_rojo, null, null, 'Turno sin Llamados');
				}
			}

			$cuadro->set_datos($datos_turnos);
			$msj = $this->get_msg_eof('turnos');
			$cuadro->set_eof_mensaje($msj);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		if ($this->cn()->entidad_cargada()) {
			  $this->hay_cambios = $this->cn()->entidad()->dep('datos')->hay_cambios();
		}
	}

	function get_seleccion($dato=null)
	{
		return ($dato) ? $this->s__seleccion[$dato] : $this->s__seleccion;
	}

}

?>