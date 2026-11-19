<?php
class zona_cursadas extends guarani_zona_pers
{
	protected $instancias;

	function cargar_info()
	{
		$filtro = "sga_comisiones.entidad = {$this->editable_id}";
		$instancias = toba::consulta_php('co_comisiones')->get_instancias_comision(null, $filtro);
		$this->instancias = aplanar_matriz($instancias, 'instancia');
	}

	function generar_html_barra_superior()
	{
		// Dependiendo las instancias a las que está asociada la comisión seleccionada, se deshabilitan operaciones.
		if (!in_array(comision::inst_cursar, $this->instancias)) {
			$this->desactivar_item('32000015'); // Generación de Actas de Regulares
			$this->desactivar_item('41000025'); // Cierre de Acta de Regulares
			$this->desactivar_item('41000026'); // Rectificativa de Acta de Regulares
		}
		if (!in_array(comision::inst_promocionar, $this->instancias)) {
			$this->desactivar_item('41000024'); // Generación de Actas de Promocionales
			$this->desactivar_item('41000027'); // Cierre de Acta de Promocionales
			$this->desactivar_item('41000028'); // Rectificativa de Acta de Promocionales
		}
		// No se muestran datos de la comisión cargada en la zona porque esa información está en los ev_info.
		echo "<div class='zona-barra-sup' id='zona_{$this->id}'>";
		echo "<div class='zona-items'>";
		$this->generar_html_barra_vinculos();
		echo '</div>';
		$this->generar_html_barra_especifico();
		echo "</div>\n";
	}
}
?>