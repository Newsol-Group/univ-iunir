<?php

namespace siu\operaciones\asociar_usuario_se\asociar_usuario;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;

class form_asociar_usuario extends form
{
	function get_id_html()
	{
		return 'form_asociar_usuario';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('asociar_usuario_se', 'asociar');
	}

	function control_group($nombre, $clase_control_group = '')
	{
		if (!$this->check_activo($nombre)) {
			return '';
		}
		if ($this->get_elemento_html_tipo($nombre) == 'hidden') {
			return $this->elemento($nombre);
		}

		$label = $this->label($nombre);
		if ($this->get_obligatorio($nombre)) {
			$label = str_replace('class="optional"', 'class="label-obligatorio"', $label);
			$label = str_replace('</label>', ' (*)</label>', $label);
		}

		$elemento = $this->elemento($nombre);
		$tiene_error = $this->tiene_error($nombre);
		$clase_error = ($tiene_error) ? 'error' : '';

		$html = "<div class='control-group $clase_control_group $clase_error'>";
		$html .= $label;
		$html .= $elemento;
		$html .= $this->ayuda($nombre);
		if ($tiene_error) {
			$html .= "<span class='help-inline'>{$this->error($nombre)}</span>";
		}
		$html .= "</div>";

		return $html;
	}

	protected function generar_definicion()
	{
		$this->set_campo('password', array(
			form::label => 'clave',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'password'),
			form::largo => 30
		));
	}

}
?>