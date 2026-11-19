<?php
namespace siu\operaciones\zona_examenes\mesas;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\formularios\builder_filtro;
use siu\extension_kernel\formularios\fabrica_formularios;
use siu\extension_kernel\formularios\guarani_form;

class builder_filtro_mesas extends builder_filtro
{

	const CANT_DIAS_RANGO_FECHAS = 7;

	protected $data;

    function get_id_html()
    {
        return '';
    }

    function get_action()
    {
        return kernel::vinculador()->crear('zona_examenes', 'index');
    }

    /**
     * El pl le envia data para configurarlo.
     * @param $data
     */
    function set_data($data)
    {
        $this->data = $data;

    }

    protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica)
    {

		if (kernel::sesion()->esta_seteada('filtrar_por')) {
			$default_filtrar_por = kernel::sesion()->get('filtrar_por');
		} else {
			$default_filtrar_por = 'r';
			kernel::sesion()->set('filtrar_por', $default_filtrar_por);
		}

        $opciones = array(
			'r' => ucfirst(kernel::traductor()->trans("rango_fechas")),
            'f' => ucfirst(kernel::traductor()->trans("fecha"))
        );
        $form->add_elemento($fabrica->elemento_select('filtrar_por', $opciones, array(
            FormElementoConfig::label => ucfirst(kernel::traductor()->trans("filtrar_por")),
            FormElementoConfig::filtro => validador::TIPO_TEXTO,
            FormElementoConfig::obligatorio => false,
            FormElementoConfig::valor_default => $default_filtrar_por
        )));

		if (kernel::sesion()->esta_seteada('date_time_examen')) {
			$default_fecha = \DateTime::createFromFormat('Y-m-d', kernel::sesion()->get('date_time_examen'))->format('d/m/Y');
		} else {
			$hoy = new \DateTime();
			$default_fecha = $hoy->format('d/m/Y');
			kernel::sesion()->set('date_time_examen', $hoy->format('Y-m-d'));
		}

        $form->add_elemento($fabrica->elemento_fecha('fecha', array(
            FormElementoConfig::label => ucfirst(kernel::traductor()->trans("fecha")),
            FormElementoConfig::filtro => validador::TIPO_DATE,
            FormElementoConfig::obligatorio => false,
            FormElementoConfig::valor_default => $default_fecha
        )));

		if (!kernel::sesion()->esta_seteada('date_time_examen_desde') && !kernel::sesion()->esta_seteada('date_time_examen_hasta')) {
			$desde = new \DateTime();
			$desde->add(\DateInterval::createFromDateString('-'.static::CANT_DIAS_RANGO_FECHAS.' days'));
			kernel::sesion()->set('date_time_examen_desde', $desde->format('Y-m-d'));

			$hasta = new \DateTime();
			$hasta->add(\DateInterval::createFromDateString(static::CANT_DIAS_RANGO_FECHAS.' days'));
			kernel::sesion()->set('date_time_examen_hasta', $hasta->format('Y-m-d'));
		}

		$default_desde = "";
		if (kernel::sesion()->esta_seteada('date_time_examen_desde')) {
			$default_desde = \DateTime::createFromFormat('Y-m-d', kernel::sesion()->get('date_time_examen_desde'))->format('d/m/Y');
		}

        $form->add_elemento($fabrica->elemento_fecha('desde', array(
            FormElementoConfig::label => ucfirst(kernel::traductor()->trans("form_filtro_fecha_desde")),
            FormElementoConfig::filtro => validador::TIPO_DATE,
            FormElementoConfig::obligatorio => false,
            FormElementoConfig::valor_default => $default_desde
        )));

		$default_hasta = "";
		if (kernel::sesion()->esta_seteada('date_time_examen_hasta')) {
			$default_hasta = \DateTime::createFromFormat('Y-m-d', kernel::sesion()->get('date_time_examen_hasta'))->format('d/m/Y');
		}

        $form->add_elemento($fabrica->elemento_fecha('hasta', array(
            FormElementoConfig::label => ucfirst(kernel::traductor()->trans("form_filtro_fecha_hasta")),
            FormElementoConfig::filtro => validador::TIPO_DATE,
            FormElementoConfig::obligatorio => false,
            FormElementoConfig::valor_default => $default_hasta
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
                    array(
                        'filtrar_por' => array('span' => 4),
                        'fecha' => array('span' => 4),
                        'desde' => array('span' => 4),
                        'hasta' => array('span' => 4))
                ),
            ),

        );


    }
}