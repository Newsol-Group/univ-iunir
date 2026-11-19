<?php
namespace siu\operaciones\encuestas_respondidas\filtros;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use \siu\extension_kernel\formularios\builder_formulario;
use \siu\extension_kernel\formularios\fabrica_formularios;
use \siu\extension_kernel\formularios\guarani_form;

class builder_form_filtro extends builder_formulario
{
    function get_id_html()
    {
        return 'formulario_filtro';
    }

    function get_action()
    {
        return kernel::vinculador()->crear('encuestas_respondidas', 'index');
    }

    protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica)
    {

        $materia = $form->add_elemento($fabrica->elemento('materia_descr', array(
            FormElementoConfig::label => kernel::traductor()->trans('encuestas_respondidas.materia'),
            FormElementoConfig::filtro            => validador::TIPO_TEXTO,
            FormElementoConfig::obligatorio       => false,
            FormElementoConfig::elemento          => array('tipo' => 'text'),
            FormElementoConfig::valor_default => '',
            FormElementoConfig::placeholder => kernel::traductor()->trans('encuestas_respondidas.todas'),
        )));
        $fabrica->elemento_decorar_boton_limpiar($materia, 'limpiar_materia');

        $form->add_elemento($fabrica->elemento('materia', array(
            FormElementoConfig::filtro            => validador::TIPO_ALPHANUM,
            FormElementoConfig::obligatorio       => false,
            FormElementoConfig::elemento          => array('tipo' => 'hidden'),
            FormElementoConfig::valor_default => '',
        )));

        $docente = $form->add_elemento($fabrica->elemento('docente_descr', array(
            FormElementoConfig::label => ucfirst(kernel::traductor()->trans('encuestas_respondidas.docente')),
            FormElementoConfig::filtro            => validador::TIPO_TEXTO,
            FormElementoConfig::obligatorio       => false,
            FormElementoConfig::elemento          => array('tipo' => 'text'),
            FormElementoConfig::valor_default => '',
            FormElementoConfig::placeholder => kernel::traductor()->trans('encuestas_respondidas.todos'),
        )));
        $fabrica->elemento_decorar_boton_limpiar($docente, 'limpiar_docente');

        $form->add_elemento($fabrica->elemento('docente', array(
            FormElementoConfig::filtro            => validador::TIPO_ALPHANUM,
            FormElementoConfig::obligatorio       => false,
            FormElementoConfig::elemento          => array('tipo' => 'hidden'),
            FormElementoConfig::valor_default => '',
        )));

        $fecha_desde = $form->add_elemento($fabrica->elemento_fecha('fecha_desde', array(
            FormElementoConfig::label			=> kernel::traductor()->trans('encuestas_respondidas.fecha_desde'),
            FormElementoConfig::obligatorio	=> false,
            FormElementoConfig::placeholder	=> kernel::traductor()->trans('encuestas_respondidas.filtro_seleccione'),
            FormElementoConfig::clase_css => 'date',
            FormElementoConfig::readonly => true,
        )));
        $fabrica->elemento_decorar_boton_limpiar($fecha_desde, 'limpiar_fecha_desde');

        $fecha_hasta = $form->add_elemento($fabrica->elemento_fecha('fecha_hasta', array(
            FormElementoConfig::label			=> kernel::traductor()->trans('encuestas_respondidas.fecha_hasta'),
            FormElementoConfig::obligatorio	=> false,
            FormElementoConfig::placeholder	=> kernel::traductor()->trans('encuestas_respondidas.filtro_seleccione'),
            FormElementoConfig::clase_css => 'date',
            FormElementoConfig::readonly => true,
        )));
        $fabrica->elemento_decorar_boton_limpiar($fecha_hasta, 'limpiar_fecha_hasta');

        $form->add_accion($fabrica->accion_boton_submit('boton_buscar', kernel::traductor()->trans('encuestas_respondidas.filtro_buscar')));
    }

    /**
     * Por defecto los filtros de guarani se basan en el layout grilla. Se debe configurar en este metodo para poder
     * instanciar el mismo
     */
    function get_configuracion_layout_grilla()
    {
        return array(
            array(
                'grupo' => 'filtros',
                'filas' => array(
                    array(
                        'materia_descr' => array('span' => 12),
                        'materia' => array('span' => 0),
                    ),
                    array(
                        'docente_descr' => array('span' => 12),
                        'docente' => array('span' => 0),
                    ),
                    array(
                        'fecha_desde' => array('span' => 3),
                        'fecha_hasta' => array('span' => 3),
                    ),
                )
            ),
        );
    }
}