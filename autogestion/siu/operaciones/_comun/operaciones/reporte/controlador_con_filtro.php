<?php
namespace siu\operaciones\_comun\operaciones\reporte;

use SIU\Chulupi\ErrorKernelValidacion;


class controlador_con_filtro extends controlador
{

	/**
	 * @return guarani_form
	 */
	function get_form()
	{
		return $this->get_form_builder()->get_formulario();
	}

	function get_vista_form()
	{
		return $this->get_form_builder()->get_vista();
	}


	/**
	 * @return builder_filtro
	 */
	public function get_form_builder()
	{
		if (!isset($this->form_builder)) {
			$this->form_builder = $this->instanciar_form_builder();
		}
		return $this->form_builder;
	}

	function accion__index()
	{
		$this->recuperar_filtro();
	}

	function accion__generar_pdf()
	{
		$this->recuperar_filtro();

		parent::accion__generar_pdf();
	}

	function accion__generar_excel()
	{
		$this->recuperar_filtro();
		parent::accion__generar_excel();
	}

	function accion__generar_csv()
	{
		$this->recuperar_filtro();
		parent::accion__generar_csv();
	}

	/**
	 * Solo la vista 'vista' tiene el form. Falla en otro caso
	 */
	private function recuperar_filtro()
	{
		$form = $this->get_form();
		if (!$form->procesar('GET')) {
			throw new ErrorKernelValidacion("Error al validar el filtro");
		}

		//si no se llega aca, aplican los valores default
		$this->datos_filtro = $form->get_datos();
	}
}

?>