<?php
namespace siu\operaciones\notas_mesa_examen;

use SIU\Chulupi\interfaz\pagelet;

class pagelet_actas extends pagelet
{
	function get_nombre()
	{
		return 'actas';
	}

	function prepare()
	{
		$this->data = array();
		/* @var $actas \SIU\Chulupi\util\modelo\ProcesadorVista */
		$actas = $this->controlador->codificador_modelo()->info__cabecera_actas($this->controlador->llamado_mesa_params);
		$actas->link('link_edicion', 'admin_actas_examen_fc', 'editar');

		$this->data['actas'] = $actas;
		$this->add_var_js('actas', $actas);
	}

}
?>
