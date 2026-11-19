<?php
namespace siu\operaciones\zona_comisiones\comisiones;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\formularios\builder_filtro;
use siu\extension_kernel\formularios\fabrica_formularios;
use siu\extension_kernel\formularios\guarani_form;
use siu\extension_kernel\formularios\guarani_form_elemento;
use siu\guarani;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\parametro;
use siu\modelo\_comun\comunes;

class builder_filtro_comisiones extends builder_filtro
{
    protected $data;

    function get_id_html()
    {
        return '';
    }

    function get_action()
    {
        return kernel::vinculador()->crear('zona_comisiones', 'index');
    }

    /**
     * El pl le envia data para configurarlo.
     * @param $data
     */
    function set_data($data)
    {
        $this->data = $data;

    }

    protected function get_peridos_lectivos()
    {
        $periodos = array(NULL => ucfirst(kernel::traductor()->trans("vigentes"))); //no seleccion
		$docente = array('docente' => kernel::persona()->get_id_docente());
		foreach (catalogo::consultar('unidad_academica', 'periodos_lectivos_fecha_inactivacion_mayor_igual_fecha_actual', $docente ) as $aux) {
            $periodos[$aux['periodo_lectivo']] = $aux['anio_academico'] . ' - ' . $aux['periodo_lectivo_nombre'];
        }
        return $periodos;
        //todo: migrar a algo como lo de abajo
        //return guarani_form_elemento::armar_combo_opciones($datos, 'anio_academico', 'anio_academico', false);

    }

    protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica)
    {

        $form->add_accion($fabrica->accion_boton_submit('btn_filtro', 'Filtrar'));

        //OBS: los labels estan "harcodeados", solamente los copio como estaban. todo - pasar a mensajes.
        $opciones = $this->get_peridos_lectivos();
        $form->add_elemento($fabrica->elemento_select('periodo_lectivo', $opciones, array(
            FormElementoConfig::label => ucfirst(kernel::traductor()->trans("form_filtro_periodo_lectivo")),
            FormElementoConfig::filtro => validador::TIPO_INT,
            FormElementoConfig::obligatorio => false,
            //FormElementoConfig::elemento		=> array('tipo' => 'select'),
            //FormElementoConfig::multi_options => ,
            //todo - esta mal-->esta consulta trae el año, no la clave (antes tampoco andaba)
            //FormElementoConfig::valor_default => catalogo::consultar('unidad_academica', 'get_anio_academico_actual')
        )));

    }


    /**
     * Por defecto los filtros de guarani se basan en el layout grilla. Se debe configurar en este metodo para poder
     * instanciar el mismo
     */
    function get_configuracion_layout_grilla()
    {

        // ------------------------   DATOS ACTA   ---------------------------
        return array(
            array(
                //'grupo'		=>	'datos_acta',
                'titulo' => ucfirst(kernel::traductor()->trans('filtro')), //todo - pasar a mensajes
                'filas' => array(
                    array('periodo_lectivo' => array('span' => 12))
                ),
            ),

        );


    }
}