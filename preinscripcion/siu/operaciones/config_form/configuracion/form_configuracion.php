<?php

namespace siu\operaciones\config_form\configuracion;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\modelo\util;

class form_configuracion extends form
{
	function get_id_html()
	{
		return 'form_configuracion';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('config_form', 'guardar', array('configuracion'));
	}

	function control_group($campo, $nombre = '' )
	{
		$html = '<tr>';
		$html .= '<td width="25%">' . $campo . '</td>';
		$html .= '<td>' . kernel::traductor()->trans($campo) . '</td>';
		$html .= '<td width="8%" style="text-align:center;">' . $this->elemento($campo . '_visible') . '</td>';
		$html .= '<td width="8%" style="text-align:center;">' . $this->elemento($campo . '_obligatorio') . '</td>';
		$html .= '<td width="8%" style="text-align:center;">' . $this->elemento($campo . '_se_imprime') . '</td>';
		$html .= '<td width="8%" style="text-align:center;">' . $this->elemento($campo . '_genera_reporte') . '</td>';
		$html .= '</tr>';
		return $html;
	}

	function generar_salida_html()
	{
		$campos_pagelets = util::form()->get_campos_secciones();
		$campos_configurables = util::form()->get_campos_configurables();
		$html = '';
		foreach ($campos_pagelets as $nombre_pagelet => $campos_secciones) {
			$html .= '<div class="well">';
			$html .= "<a name='$nombre_pagelet'></a>";
			$html .= '<h2 class=\'toggle-header\' onclick="toggle(\'div_' . $nombre_pagelet . '\')">' . kernel::traductor()->trans('pantalla') . ': ' . kernel::traductor()->trans($nombre_pagelet) . '</h2>';
			$html .= '<div id=\'div_' . $nombre_pagelet . '\' class=\'toggle-div\'>';
			foreach ($campos_secciones as $nombre_seccion => $campos_seccion) {
				$html .= "<table class='table table-stripped table-hover'>
							<tbody>";
				$html .= "		<tr>
									<td colspan='6' style='padding-top: 16px; padding-bottom:0px;'>
										<h4>" . kernel::traductor()->trans('seccion') . ': ' . kernel::traductor()->trans($nombre_seccion) . "</h4>
									</td>
								</tr>";
				$html .= "		<tr>
									<td width='15%' style='font-weight:bold;'>" . kernel::traductor()->trans('campo') . "</td>
									<td width='25%' style='font-weight:bold;'>" . kernel::traductor()->trans('etiqueta') . "</td>
									<td style='text-align:center; font-weight:bold;'>" . kernel::traductor()->trans('visible') . "</td>
									<td style='text-align:center; font-weight:bold;'>" . kernel::traductor()->trans('obligatorio') . "</td>
									<td style='text-align:center; font-weight:bold;'>" . kernel::traductor()->trans('se_imprime') . "</td>
									<td style='text-align:center; font-weight:bold;'>" . kernel::traductor()->trans('genera_reporte') . "</td>
								</tr>";
				foreach ($campos_seccion as $campo) {
					if (in_array($campo, $campos_configurables)) {
						$html .= $this->control_group($campo);
					}
				}
				$html .= "	</tbody>
						</table>\n";
			}
			$html .= '</div>';
			$html .= '</div>';
		}
		return $html;
	}

	function generar_links_secciones()
	{
		$campos_pagelets = util::form()->get_campos_secciones();
		$html = '';
		$contador = 0;
		foreach ($campos_pagelets as $nombre_pagelet => $campos_secciones) {
			if ($contador > 0) {
				if ($contador == 4) {
					$html .= '<br/>';
				} else {
					$html .= ' | ';
				}
			}
			$html .= "<a class='no-ajax' href='#$nombre_pagelet'>" . kernel::traductor()->trans($nombre_pagelet) . "</a>";
			$contador++;
		}
		return $html;
	}

	protected function set_dato($nombre, $dato)
	{
		$grupo = $this->get_group($nombre);
		if ($grupo != '') {
			$this->datos[$grupo][$nombre] = $dato;
		} else {
			$this->datos[$nombre] = $dato;
		}

		$this->get_zend_form()->getElement($nombre)->setValue($dato);

		if (strripos($nombre, '_visible')) {
			$campo = substr($nombre, 0, strripos($nombre, '_visible'));

			$this->get_zend_form()->getElement($nombre)->setAttrib('data-campo', $campo);
			$this->get_zend_form()->getElement($nombre)->setAttrib('data-visible', $campo . '_visible');
			$this->get_zend_form()->getElement($nombre)->setAttrib('data-obligatorio', $campo . '_obligatorio');
			$this->get_zend_form()->getElement($nombre)->setAttrib('data-se_imprime', $campo . '_se_imprime');
			$this->get_zend_form()->getElement($nombre)->setAttrib('data-genera_reporte', $campo . '_genera_reporte');
		}
	}

	protected function generar_definicion()
	{
		$campos_conf = util::form()->get_campos_configurables();
		$campos_no_ocultables_obligatorios = util::form()->get_campos_no_ocultables_obligatorios();
		foreach ($campos_conf as $campo) {
			if (in_array($campo, $campos_no_ocultables_obligatorios)) {
				$disabled_visible = true;
				$disabled_obligatorio = true;
				$clase_js = 'js-no_modificable';
			} else {
				$disabled_visible = false;
				$disabled_obligatorio = false;
				$clase_js = '';
			}

			$this->set_campo($campo . '_visible', array(
				form::label => 'label',
				form::filtro => validador::TIPO_TEXTO,
				form::obligatorio => false,
				form::elemento => array('tipo' => 'checkbox'),
				form::largo => 1,
				form::checked_value => '1',
				form::unchecked_value => '0',
				form::clase_css => "js-check-visible $clase_js",
				form::disabled => $disabled_visible
			));

			$this->set_campo($campo . '_obligatorio', array(
				form::label => 'label',
				form::filtro => validador::TIPO_TEXTO,
				form::obligatorio => false,
				form::elemento => array('tipo' => 'checkbox'),
				form::largo => 1,
				form::checked_value => '1',
				form::unchecked_value => '0',
				form::clase_css => "js-check-visible $clase_js",
				form::disabled => $disabled_obligatorio
			));

			$this->set_campo($campo . '_se_imprime', array(
				form::label => 'label',
				form::filtro => validador::TIPO_TEXTO,
				form::obligatorio => false,
				form::elemento => array('tipo' => 'checkbox'),
				form::largo => 1,
				form::checked_value => '1',
				form::unchecked_value => '0',
			));

			$this->set_campo($campo . '_genera_reporte', array(
				form::label => 'label',
				form::filtro => validador::TIPO_TEXTO,
				form::obligatorio => false,
				form::elemento => array('tipo' => 'checkbox'),
				form::largo => 1,
				form::checked_value => '1',
				form::unchecked_value => '0',
			));
		}
	}

}
?>