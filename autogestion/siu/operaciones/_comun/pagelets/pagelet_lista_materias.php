<?php
namespace siu\operaciones\_comun\pagelets;

use \SIU\Chulupi\interfaz\pagelet;
use \SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_lista_materias extends pagelet
{
    protected function get_operacion_fisica()
    {
        return '_comun/pagelets';
    }
    
    function get_nombre()
    {
        return 'lista_materias';
    }
	
    function get_js_files()
    {
		$archivos = parent::get_js_files();
		$archivos[] = kernel::vinculador()->vinculo_recurso("js/filtro_lista.js");
		return $archivos;
    }
	
	/**
	 * Shortcut al modelo
	 * @return \siu\modelo\transacciones\inscripcion_cursada
	 */
	protected function modelo()
	{
		return $this->controlador->modelo();
	}
    
    function prepare()
    {
        $this->add_var_js('busca_actividad', kernel::traductor()->trans('busca_actividad'));
        $this->data['materias'] = $this->modelo()->info__lista_materias();
        $operacion = kernel::ruteador()->get_id_operacion();

        if ($this->controlador->hay_var('materia')) {
                $materia = $this->controlador->get_var('materia');
        } else {
                $materia = '';
        }
		
        foreach ($this->data['materias'] as $key => $dato) {
			$this->data['materias'][$key]['SELECCIONADA'] = ($materia == $dato[catalogo::id]);
            $this->data['materias'][$key]['URL_MATERIA'] = kernel::vinculador()->crear($operacion, 'elegir_materia', $dato[catalogo::id]);
        }

		$this->data['prueba_stress_habilitada'] = kernel::proyecto()->prueba_stress_habilitada();
    }
}