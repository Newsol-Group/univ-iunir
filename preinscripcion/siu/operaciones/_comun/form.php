<?php

namespace siu\operaciones\_comun;

use SIU\Chulupi\util\form as form_base;

abstract class form extends form_base
{
	function control_group($nombre, $clase_control_group = '')
	{
		if (!$this->check_activo($nombre)) {
			return '';
		}
		if ($this->get_elemento_html_tipo($nombre) == 'hidden') {
			return $this->elemento($nombre);
		}
		$label = $this->label($nombre);

		if (!$this->get_obligatorio($nombre)) {
			$label = '<span class="label-no-obligatorio">' . $label . '</span>';
		} else {
			$label = str_replace('class="optional"', 'class="label-obligatorio"', $label);
			$label = str_replace('</label>', ' (*)</label>', $label);
		}

		$elemento = $this->elemento($nombre);
		$tiene_error = $this->tiene_error($nombre);
		$clase_error = ($tiene_error) ? 'error' : '';

		$id_cg = 'cg-' . $nombre;
		$id_inline_help = 'msg-' . $nombre;

		$html = "<div id='$id_cg' class='control-group $clase_control_group $clase_error'>";
		$html .= $label;
		$html .= $elemento;
		$html .= $this->ayuda($nombre);
		$html .= "<div id='$id_inline_help' style='color: #b94a48;'></div>";
		$html .= "</div>";

		return $html;
	}

}
?>
