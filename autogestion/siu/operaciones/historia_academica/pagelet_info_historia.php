<?php
namespace siu\operaciones\historia_academica;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;

class pagelet_info_historia extends pagelet
{
    function get_nombre()
    {
        return 'info_historia';
    }

	function get_js_files()
	{
		$archivos = parent::get_js_files();
		//$archivos[] = kernel::vinculador()->vinculo_recurso("js/plugin_autocompletar.js");
		$archivos[] = kernel::vinculador()->vinculo_recurso("js/utilidades.js");
		return $archivos;
	}

	function tiene_datos()
    {
        return !empty($this->data['historia']);
    }
	
	function tiene_filtros()
    {
        return $this->data['tiene_filtros'];
    }
	
    function prepare()
    {
        $this->add_var_js('url_datos_historia_academica', kernel::vinculador()->crear('historia_academica', 'index'));
        $this->add_var_js('url_detalle_examen', kernel::vinculador()->crear('historia_academica', 'detalle_examen'));
        $this->add_var_js('url_detalle_promocion', kernel::vinculador()->crear('historia_academica', 'detalle_promocion'));
        $this->add_var_js('url_detalle_cursada', kernel::vinculador()->crear('historia_academica', 'detalle_cursada'));
        $this->add_var_js('url_detalle_encurso', kernel::vinculador()->crear('historia_academica', 'detalle_encurso'));
        $this->add_var_js('url_detalle_equivalencia', kernel::vinculador()->crear('historia_academica', 'detalle_equivalencia'));
        $this->add_var_js('url_detalle_creditos', kernel::vinculador()->crear('historia_academica', 'detalle_creditos'));
		$this->add_mensaje_js('detalle', kernel::traductor()->trans('historia_academica.detalle'));
		$this->add_mensaje_js('cerrar', kernel::traductor()->trans('historia_academica.cerrar'));
		$this->prepare_autocompletar();
		
		$this->add_var_js('url_pdf', kernel::vinculador()->crear('historia_academica', 'exportar_pdf'));
		$this->add_var_js('url_xls', kernel::vinculador()->crear('historia_academica', 'exportar_xls'));
		$this->add_var_js('url_flt', kernel::vinculador()->crear('historia_academica', 'index'));
    }
	
	function prepare_autocompletar(){
		$defecto = array('id' => '', 'value' => ucfirst(kernel::traductor()->trans('filtrar_materias')));
		
		$actividades = array();
		$actividades[0]['id'] = '';
		$actividades[0]['label'] = ucfirst(kernel::traductor()->trans('todas_las_materias'));
		$i = 1;
		foreach ($this->data['materias'] as $key => $actividad){
			$actividades[$i]['id'] = $key;
			$actividades[$i]['label'] = $actividad;
			$i++;
			
			if ($this->data['orden'] != 'anio' && $this->data['param_modo'] == $key){
				$defecto = array('id' => $key, 'value' => $actividad);
			}
		}
		$this->add_var_js('autocompletar_defecto', $defecto);
		$this->add_var_js('autocompletar_actividades', $actividades);
	}
}
?>
