<?php

namespace siu\operaciones\encuestas_resultados;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_listado_encuesta extends pagelet {

    public function get_nombre()
    {
        return 'listado_encuesta';
    }
    
    function prepare()
    {
        $this->add_var_js('url_ver_encuesta_completa', kernel::vinculador()->crear('encuestas_resultados', 'ver_encuesta_completa'));
        $this->add_var_js('url_ver_bloques', kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'ver_bloques'));

    }
    
    function get_js_files()
    {
        $files = parent::get_js_files();
        $files[] = kernel::vinculador()->vinculo_recurso("js/jqplot_v1_0_9/jquery.jqplot.js");
        $files[] = kernel::vinculador()->vinculo_recurso("js/jqplot_v1_0_9/plugins/jqplot.pieRenderer.js");
        $files[] = kernel::vinculador()->vinculo_recurso("js/jqplot_v1_0_9/plugins/jqplot.highlighter.js");
        $files[] = kernel::vinculador()->vinculo_recurso("js/jqplot_v1_0_9/plugins/jqplot.barRenderer.js");
        $files[] = kernel::vinculador()->vinculo_recurso("js/jqplot_v1_0_9/plugins/jqplot.categoryAxisRenderer.js");
        $files[] = kernel::vinculador()->vinculo_recurso("js/jqplot_v1_0_9/plugins/jqplot.canvasAxisTickRenderer.js");
        $files[] = kernel::vinculador()->vinculo_recurso("js/jqplot_v1_0_9/plugins/jqplot.canvasTextRenderer.js");
        return $files;
    }
    
    function get_css_files()
    {
        $files = parent::get_css_files();
        $files[] = kernel::vinculador()->vinculo_recurso("css/jqplot_v1_0_9/jquery.jqplot.css");
        return $files;
    }

} 