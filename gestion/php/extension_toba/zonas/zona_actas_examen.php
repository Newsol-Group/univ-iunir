<?php
class zona_actas_examen extends guarani_zona_pers
{
	protected $datos_acta;

	function cargar_info()
	{
		$filtro = "sga_actas.id_acta = {$this->editable_id}";
		$this->datos_acta = current(toba::consulta_php('co_actas')->get_listado_actas_examen_con_sin_mesas($filtro));
	}

	function generar_html_barra_superior()
	{
		// Dependiendo del estado del acta seleccionada se deshabilitan operaciones.
		if ($this->datos_acta['estado'] != acta::estado_abierta) {
			$this->desactivar_item('37000059'); // Carga de Notas a Acta de Examen
			$this->desactivar_item('38000086'); // Cerrar Actas de Examen
			$this->desactivar_item('38000088'); // Anular Actas de Examen
		}
		if ($this->datos_acta['estado'] != acta::estado_cerrada ||
			$this->datos_acta['tipo_acta'] != acta::tipo_normal ||
			$this->datos_acta['tiene_rectificativas_abiertas']) {
			$this->desactivar_item('38000087'); // Rectificar Actas de Examen
		}
		if ($this->datos_acta['estado'] != acta::estado_abierta ||
			$this->datos_acta['tipo_acta'] != acta::tipo_normal) {
			$this->desactivar_item('38000084'); // Agregar/Eliminar Alumnos en Acta de Examen
		}
		if ($this->datos_acta['estado'] != acta::estado_abierta ||
			$this->datos_acta['tipo_acta'] != acta::tipo_rectificativa) {
			$this->desactivar_item('37000058'); // Agregar/Eliminar Alumnos en Acta Rectificativa de Examen
		}
		parent::generar_html_barra_superior();
	}
}
?>