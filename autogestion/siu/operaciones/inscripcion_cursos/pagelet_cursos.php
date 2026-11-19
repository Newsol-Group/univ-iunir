<?php
namespace siu\operaciones\inscripcion_cursos;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_cursos extends pagelet
{
    function ini()
    {
    }

    function get_nombre()
    {
        return 'cursos';
    }

    /**
     * Shortcut al modelo
     * @return \siu\modelo\transacciones\cursos
     */
    protected function modelo()
    {
        return $this->controlador->modelo();
    }

    function prepare()
    {
        $operacion = kernel::ruteador()->get_id_operacion();

        $this->add_var_js('buscar_curso', kernel::traductor()->trans('inscripcion_cursos.buscar_curso'));
        $this->add_var_js('url_actividades_curso', kernel::vinculador()->crear($operacion, 'actividades_curso'));
        $this->add_var_js('url_elegir_propuesta_curso', kernel::vinculador()->crear($operacion, 'elegir_propuesta_curso'));

        $this->data['catalogo_id'] = catalogo::id;
        $this->data['url_elegir_actividad_curso'] = kernel::vinculador()->crear($operacion, 'elegir_actividad_curso');
        $this->data['propuestas_cursos'] = $this->modelo()->info__propuestas_administran_cursos();

        $propuesta_curso_hash = '';
        if($this->controlador->hay_var('propuesta_curso_hash')) {
            $propuesta_curso_hash = $this->controlador->get_var('propuesta_curso_hash');
        }

        $curso_hash = '';
        if($this->controlador->hay_var('curso_hash')) {
            $curso_hash = $this->controlador->get_var('curso_hash');
        }

        //Si hay propuestas de tipo curso
        if(!empty($this->data['propuestas_cursos'])){

            //Marco como seleccionada la primer propuesta
            $propuesta_curso_seleccionada = $this->data['propuestas_cursos'][0];

            if($propuesta_curso_hash){//Si ya hay una propuesta seleccionada tomo esa
                foreach($this->data['propuestas_cursos'] as $key => $propuesta_curso){
                    if($propuesta_curso[catalogo::id] == $propuesta_curso_hash){
                        $propuesta_curso_seleccionada = $propuesta_curso;
                        $this->data['propuestas_cursos'][$key]['seleccionada'] = true;
                        break;
                    }
                }
            }

            $this->controlador->set_propuesta($propuesta_curso_seleccionada['valor']);

            $this->data['propuesta_curso_hash'] = $propuesta_curso_seleccionada[catalogo::id];
            $this->data['actividades_curso'] = $this->modelo()->info__actividades_propuesta_curso_por_persona($propuesta_curso_seleccionada['valor']);

            if($curso_hash){//Si ya hay una actividad seleccionada tomo esa
                foreach($this->data['actividades_curso'] as $key => $actividad_curso){
                    if($actividad_curso[catalogo::id] == $curso_hash){
                        $this->data['actividades_curso'][$key]['seleccionada'] = true;
                        break;
                    }
                }
            }
        }
    }

    function get_js_files()
    {
        $archivos = parent::get_js_files();
        $archivos[] = kernel::vinculador()->vinculo_recurso("js/filtro_lista.js");
        return $archivos;
    }

}