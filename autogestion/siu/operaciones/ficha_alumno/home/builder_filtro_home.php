<?php
namespace siu\operaciones\ficha_alumno\home;

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

class builder_filtro_home extends builder_filtro
{
    protected $data;

    function get_id_html()
    {
        return '';
    }

    function get_action()
    {
        return kernel::vinculador()->crear('ficha_alumno', 'index');
    }

    /**
     * Información enviada por el pagelet para configurarlo.
     * @param $data
     */
    function set_data($data)
    {
        $this->data = $data;
    }

    protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica)
    {
        $form->add_accion($fabrica->accion_boton_submit('btn_filtro', kernel::traductor()->trans('ficha_alumno.filtro.filtrar')));

		$form->add_elemento(
			$fabrica->elemento(
				'cuadro_busqueda', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('ficha_alumno.filtro.alumno')),
					FormElementoConfig::filtro => validador::TIPO_TEXTO,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'text'),
					FormElementoConfig::largo => 300,
					FormElementoConfig::placeholder => kernel::traductor()->trans('ficha_alumno.filtro.actividad.placeholder')
				)
			)
		);
		
        $form->add_elemento(
			$fabrica->elemento_select(
				'periodo_lectivo', 
				$this->get_peridos_lectivos(), 
				array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('ficha_alumno.filtro.periodos_lectivo')),
					FormElementoConfig::filtro => validador::TIPO_INT,
					FormElementoConfig::obligatorio => false,
				)
			)
		);
		
		$form->add_elemento(
			$fabrica->elemento_select(
				'turno', 
				$this->get_turnos(), 
				array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('ficha_alumno.filtro.turno')),
					FormElementoConfig::filtro => validador::TIPO_INT,
					FormElementoConfig::obligatorio => false,
				)
			)
		);
		
		$form->add_elemento(
			$fabrica->elemento_select(
				'ubicacion', 
				$this->get_ubicaciones(), 
				array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('ficha_alumno.filtro.ubicacion')),
					FormElementoConfig::filtro => validador::TIPO_INT,
					FormElementoConfig::obligatorio => false,
				)
			)
		);
		
		$elemento = $form->add_elemento(
			$fabrica->elemento_autocompletar('actividad', array(
				FormElementoConfig::label => ucfirst(kernel::traductor()->trans('ficha_alumno.filtro.actividad')),
				FormElementoConfig::obligatorio => false,
				FormElementoConfig::valor_default => '', // Todos
				FormElementoConfig::clase_css => ''
			),
			function ($id) {
				if (!$id) {
					return kernel::traductor()->trans('ficha_alumno.filtro.actividad.todas');
				}
				$actividad_info = catalogo::consultar('unidad_academica', 'actividad', array('actividad' => $id));
				return $actividad_info['nombre'] . ' (' . $actividad_info['codigo'] . ')';
			},
			kernel::vinculador()->crear('ficha_alumno', 'buscar_actividad')

		));
		$fabrica->elemento_decorar_boton_limpiar($elemento, 'limpiar_actividad');

    }

	//--------------------------------------------------------------------------
	//---- Datos para el filtro ------------------------------------------------
	//--------------------------------------------------------------------------
	
    protected function get_peridos_lectivos()
    {
        $periodos = array(NULL => ucfirst(kernel::traductor()->trans('ficha_alumno.filtro.no_finalizados'))); //no seleccion
        $parametros['docente'] = kernel::persona()->get_id_docente();
        $datos_periodos_lectios = catalogo::consultar('unidad_academica', 'periodos_lectivos_fecha_inactivacion_no_finalizada', $parametros);
        foreach ($datos_periodos_lectios as $aux) {
            $periodos[$aux['periodo_lectivo']] = $aux['anio_academico'] . ' - ' . $aux['periodo_lectivo_nombre'];
        }
        return $periodos;
    }
	
	protected function get_turnos()
	{
		$todos = kernel::traductor()->trans('ficha_alumno.filtro.no_finalizados');
		$turnos = array(null => $todos); // No seleccion
		$parametros['docente'] = kernel::persona()->get_id_docente();
		$datos_turnos_examen = catalogo::consultar('unidad_academica', 'turnos_examen_de_anios_academicos_con_turno_no_finalizado', $parametros);
		foreach($datos_turnos_examen as $turno) {
			$turnos[$turno['turno_examen']] = $turno['anio_academico'] . ' - ' . $turno['turno_examen_nombre'];
		}
		return $turnos;
	}
	
	protected function get_ubicaciones()
	{
		$datos = catalogo::consultar('docente', 'get_lista_ubicaciones');
		$todos = kernel::traductor()->trans("ficha_alumno.filtro.todas");
		return guarani_form_elemento::armar_combo_opciones($datos, 'ubicacion', 'ubicacion_nombre', true, true, $todos);
	}
	
	protected function get_actividades()
	{
		$datos = catalogo::consultar('docente', 'get_lista_ubicaciones');
		$todos = kernel::traductor()->trans('ficha_alumno.filtro.todos');
		return guarani_form_elemento::armar_combo_opciones($datos, 'ubicacion', 'ubicacion_nombre', true, true, $todos);
	}
	
	//--------------------------------------------------------------------------
	//---- Layout --------------------------------------------------------------
	//--------------------------------------------------------------------------
	
    function get_configuracion_layout_grilla()
    {
        return array(
            array(
                'titulo' => ucfirst(kernel::traductor()->trans('ficha_alumno.filtro')), 
                'filas' => array(
					array(
						'cuadro_busqueda' => array('span' => 12)
					),
					array(
						'actividad' => array('span' => 6),
						'ubicacion' => array('span' => 6),  
					),
					array(
						'periodo_lectivo' => array('span' => 6),
						'turno' => array('span' => 6),
					),
                ),
            ),
        );
    }
}
