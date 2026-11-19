<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class pant_docentes_comision extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('docentes_asociados')->generar_html();
		echo '<hr />';
		$this->dep('filtro_docentes')->generar_html();
		echo '<hr />';
		$this->dep('seleccion_docentes')->generar_botones();
		$this->dep('seleccion_docentes')->evento('agregar')->set_en_botonera(false);
		$this->dep('seleccion_docentes')->generar_html();
		echo '<hr />';
	}

	protected function generar_html_descripcion($mensaje, $tipo = null)
	{
		if ($mensaje == guarani_mensajes::get('800CUR_encuestas_generadas_com')) {
			$datos_comision = $this->controlador()->cn()->entidad()->get_datos_generales();
			$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '32000038', array('comision' => $datos_comision['comision']), array('celda_memoria' => 'celda_encuestas'));
			$javascript = "abrir_popup('info','$vinculo',{'width':'800','height':'600','scrollbars':'yes'})";

			$imagen = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
			$imagen .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver encuestas');
			$imagen .= '</a>';

			$descripcion = toba_parser_ayuda::parsear($mensaje);
			echo "<table class='tabla-0 ei-barra-sup-desc-error'><tr><td class='ei-barra-sup-desc-img'>$imagen</td><td>$descripcion</td></table>\n";
		} else {
			parent::generar_html_descripcion($mensaje, $tipo);
		}
	}

}
?>