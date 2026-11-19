<?php

namespace siu\operaciones\encuestas_resultados\filtros;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use \siu\extension_kernel\formularios\builder_formulario;
use \siu\extension_kernel\formularios\fabrica_formularios;
use \siu\extension_kernel\formularios\guarani_form;
use siu\modelo\datos\catalogo;
use siu\extension_kernel\formularios\guarani_form_elemento;

class builder_form_filtro extends builder_formulario {

    function get_id_html() {
        return 'formulario_filtro';
    }

    function get_action() {
        return kernel::vinculador()->crear('encuestas_resultados', 'index');
    }

    protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica) {

        $form->add_elemento($fabrica->elemento('anio_academico', array(
                    FormElementoConfig::label => ucfirst(kernel::traductor()->trans('encuesta_resultados.filtro_anio_academico')),
                    FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
                    FormElementoConfig::obligatorio => true,
                    FormElementoConfig::elemento => array('tipo' => 'select'),
                    FormElementoConfig::multi_options => static::get_anios_academicos(),
                    FormElementoConfig::validar_select => false,
                    FormElementoConfig::clase_css => 'filtros_comunes',
        )));
        $form->add_elemento($fabrica->elemento('periodo', array(
                    FormElementoConfig::label => ucfirst(kernel::traductor()->trans('encuesta_resultados.filtro_periodo_lectivo')),
                    FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
                    FormElementoConfig::obligatorio => false,
                    FormElementoConfig::elemento => array('tipo' => 'select'),
                    FormElementoConfig::validar_select => false,
                    FormElementoConfig::clase_css => 'filtros_cursadas',
        )));

        $materia = $form->add_elemento($fabrica->elemento('materia_descr', array(
                    FormElementoConfig::label => kernel::traductor()->trans('encuesta_resultados.filtro_materia'),
                    FormElementoConfig::filtro => validador::TIPO_TEXTO,
                    FormElementoConfig::obligatorio => false,
                    FormElementoConfig::elemento => array('tipo' => 'text'),
                    FormElementoConfig::valor_default => '',
                    FormElementoConfig::placeholder => kernel::traductor()->trans('encuesta_resultados.filtro_todas'),
        )));
        $fabrica->elemento_decorar_boton_limpiar($materia, 'limpiar_materia');

        $form->add_elemento($fabrica->elemento('materia', array(
                    FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
                    FormElementoConfig::obligatorio => false,
                    FormElementoConfig::elemento => array('tipo' => 'hidden'),
                    FormElementoConfig::valor_default => '',
        )));


        $form->add_accion($fabrica->accion_boton_submit('boton_buscar', kernel::traductor()->trans('encuesta_resultados.filtro_buscar')));
    }

    /**
     * Por defecto los filtros de guarani se basan en el layout grilla. Se debe configurar en este metodo para poder
     * instanciar el mismo
     */
    function get_configuracion_layout_grilla() {
        return array(
            array(
                'grupo' => 'filtros',
                'filas' => array(
                    array(
                        'anio_academico' => array('span' => 6),
                        'periodo' => array('span' => 6),
                    ),
                    array(
                        'materia_descr' => array('span' => 12),
                        'materia' => array('span' => 12),
                    ),
                )
            ),
        );
    }

    function get_anios_academicos() {
        $datos = catalogo::consultar('unidad_academica', 'anios_academicos');
        return guarani_form_elemento::armar_combo_opciones($datos, catalogo::id, 'anio_academico', false, false, ucfirst(kernel::traductor()->trans('actas.filtro_seleccione')));
    }

}
