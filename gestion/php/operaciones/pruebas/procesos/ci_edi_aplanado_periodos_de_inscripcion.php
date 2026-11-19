<?php
class ci_edi_aplanado_periodos_de_inscripcion extends guarani_pers_ci
{
	protected $periodo_inscripcion;

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__procesar()
	{
		try {
			toba::db()->abrir_transaccion();

			$aplanador = toba::cn('proceso_aplanar_periodo_inscripcion');
			$aplanador->procesar($this->periodo_inscripcion);

			toba::notificacion()->agregar('El proceso de aplanado de períodos de inscripción de completó con éxito.', 'info');

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
		$this->periodo_inscripcion = $datos['periodo_inscripcion'];
	}

}
?>