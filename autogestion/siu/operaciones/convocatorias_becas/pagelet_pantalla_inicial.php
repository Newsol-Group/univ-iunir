<?php

namespace siu\operaciones\convocatorias_becas;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_pantalla_inicial extends pagelet
{
	
	function get_nombre()
	{
		return 'pantalla_inicial';
	}

	function prepare()
	{
		try {
			
			//$this->data = [];
			$this->data['error'] = false;
			$this->data['convocatorias_vigentes'] = [];
			$this->data['convocatorias_no_vigentes'] = [];
		
			$operacion = kernel::ruteador()->get_id_operacion();
                        $this->data['url_preaplicar'] = kernel::vinculador()->crear($operacion, 'preaplicar');
			$this->data['url_consultar_estado'] = kernel::vinculador()->crear($operacion, 'consultar_estado');
                        $this->add_var_js('url_consultar_estado', kernel::vinculador()->crear($operacion, 'consultar_estado'));
			$this->data['url_imprimir_solicitud'] = kernel::vinculador()->crear($operacion, 'imprimir_solicitud');
			$this->data['url_modificar_datos'] = kernel::vinculador()->crear($operacion, 'modificar_datos');

			$this->data['convocatorias_vigentes'] = $this->controlador->modelo()->info__convocatorias_vigentes();

			$this->data['convocatorias_no_vigentes'] = $this->controlador->modelo()->info__convocatorias_aplicadas();
                        
			$this->data['cuil'] = kernel::persona()->get_cuil();

		} catch (\Exception $e) {
			
			kernel::log()->add_error($e);
			$this->data['error'] = true;
			
		}
		
	}
}