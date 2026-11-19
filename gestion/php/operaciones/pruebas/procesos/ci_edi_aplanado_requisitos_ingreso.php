<?php
class ci_edi_aplanado_requisitos_ingreso extends guarani_pers_ci
{
	protected $requisito_propuesta;

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__procesar()
	{
		try {
			toba::db()->abrir_transaccion();

			$aplanador = toba::cn('proceso_aplanado_requisitos_ingreso');
			$aplanador->procesar($this->requisito_propuesta);

			toba::notificacion()->agregar('El proceso de aplanado de requisitos de ingreso de completó con éxito.', 'info');

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
		$this->requisito_propuesta = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- AUXILIARES - CARGA DE COMBOS -------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_requisitos_ingreso()
	{
		$filtro = "sga_requisitos_propuestas.requisito_para = '".requisito::requisito_propuesta_para_ingreso."'";
		return toba::consulta_php('co_requisitos_ingreso')->get_listado($filtro);
	}
}
?>