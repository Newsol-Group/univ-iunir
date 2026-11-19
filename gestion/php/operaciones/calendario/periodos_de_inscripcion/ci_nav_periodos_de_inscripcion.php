<?php
class ci_nav_periodos_de_inscripcion extends ci_navegacion
{
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

			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');

			toba::db()->cerrar_transaccion();
		} catch (guarani_error $e) {
			toba::db()->abortar_transaccion();
			toba::logger()->error($e->getMessage());
			throw $e;
		}
	}
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		/**
			*    Si no está seteado el filtro entonces es la carga inicial del cuadro (el usuario no presionó filtrar).
			*    No se traen datos (no se ejecuta la consulta) y se pone un mensaje en el cuadro que indica al usuario
			*    que debe filtrar para obtener datos.
		*/
		$this->indx_msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro) ) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$datos = $this->cn()->get_listado($this->get_filtro_condicion());
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}
}
?>