<?php
class ci_edi_aplanado_requisitos_x_accion extends guarani_pers_ci
{
	protected $accion;

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__procesar()
	{
		try {
			toba::db()->abrir_transaccion();

			$aplanador = toba::cn('proceso_aplanado_requisitos_x_accion');
			$aplanador->procesar($this->accion);

			toba::notificacion()->agregar('El proceso de aplanado de requisitos por acción de completó con éxito.', 'info');

			toba::db()->cerrar_transaccion();
		} catch (guarani_error $e) {
			toba::db()->abortar_transaccion();
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__formulario__modificacion($datos)
	{
		$this->accion = $datos['accion'];
	}
}
?>