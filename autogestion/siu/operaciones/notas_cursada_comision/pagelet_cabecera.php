<?php
namespace siu\operaciones\notas_cursada_comision;

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
		
		$this->data['instancias_calcular_notas'] = $this->get_instancias_calcular_notas();
		
		$this->add_var_js('msg_valor', ucfirst(kernel::traductor()->trans('valor')));
		$this->add_var_js('msg_descripcion', ucfirst(kernel::traductor()->trans('descripcion')));
		$this->add_var_js('msg_resultado', ucfirst(kernel::traductor()->trans('resultado')));
		$this->add_var_js('esconder_escala_notas', kernel::traductor()->trans('esconder_escala_notas'));
		$this->add_var_js('url_escala_regularidad', kernel::vinculador()->crear('notas_cursada_comision', 'info_escala', $this->data['escala_nota_regularidad']));
		$this->add_var_js('url_escala_promocion', kernel::vinculador()->crear('notas_cursada_comision', 'info_escala', $this->data['escala_nota_promocion']));
	}
	
	protected function get_instancias_calcular_notas()
	{
		return $this->controlador->get_instancias_calcular_notas();
	}

}
