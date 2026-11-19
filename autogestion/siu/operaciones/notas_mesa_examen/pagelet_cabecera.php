<?php
namespace siu\operaciones\notas_mesa_examen;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_cabecera extends pagelet
{
	function get_nombre()
	{
		return 'cabecera';
	}

	function prepare()
	{
		$this->data = array();
		$this->data = $this->controlador->get_encabezado();
		// Datos de instancias
		$datos_instancias = $this->controlador->modelo()->info__cabecera_escala_notas($this->controlador->llamado_mesa_params);
		foreach ($datos_instancias as $key => $dato) {
			$datos_instancias[$key]['url'] = kernel::vinculador()->crear('notas_mesa_examen', 'info_escala', $dato['escala_nota']);
		}
		$this->data['instancias'] = $datos_instancias;

		$this->add_var_js('instancias', $datos_instancias);
		$this->add_var_js('msg_valor', ucfirst(kernel::traductor()->trans('valor')));
		$this->add_var_js('msg_descripcion', ucfirst(kernel::traductor()->trans('descripcion')));
		$this->add_var_js('msg_resultado', ucfirst(kernel::traductor()->trans('resultado')));
		$this->add_var_js('esconder_escala_notas', kernel::traductor()->trans('esconder_escala_notas'));
	}

}
?>
