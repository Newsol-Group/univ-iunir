<?php

namespace siu\operaciones\encuestas_resultados_docente;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_filtros extends pagelet {

    public function get_nombre(){
        return 'filtros';
    }

    function prepare()
    {
        $operacion = kernel::ruteador()->get_id_operacion();
        
        $this->add_var_js('url_buscar_periodos_lectivos', kernel::vinculador()->crear($operacion, 'buscar_periodo_lectivo'));
        $this->add_var_js('url_buscar_comision', kernel::vinculador()->crear($operacion, 'buscar_comision'));
        $this->add_var_js('url_buscar_materias', kernel::vinculador()->crear($operacion, 'buscar_materias'));
        $this->add_var_js('mensaje_seleccione', kernel::traductor()->trans('descr_no_seleccion'));
        $this->add_var_js('mensaje_sin_comision', ucfirst(kernel::traductor()->trans('no_hay_datos.comisiones')));
        $this->add_var_js('mensaje_sin_periodo', ucfirst(kernel::traductor()->trans('no_hay_datos.periodos')));
        $this->add_var_js('url_filtrar', kernel::vinculador()->crear($operacion, 'index'));

    }

    function get_js_files()
    {
        $js = parent::get_js_files();
        $archivos = array_merge($js, $this->get_vista_form()->get_js_files());
        return $archivos;
    }

    /**
     * @return guarani_form
     */
    function get_form($id_html = null)
    {
        return $this->get_form_builder()->get_formulario();
    }

    function get_vista_form(){
        return $this->get_form_builder()->get_vista();
    }

    /**
     * @return builder_form_filtro
     */
    function get_form_builder()
    {
        return $this->controlador->get_form_builder();
    }

}
