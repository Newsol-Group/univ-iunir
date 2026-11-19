<?php

namespace siu\operaciones\encuestas_resultados_docente;

use SIU\Chulupi\ErrorKernelValidacion;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\encuestas_kolla;

class controlador extends controlador_g3w2 {

    protected $form_builder;
    protected $datos_filtro = array('anio_academico' => "", 'periodo' => "");

    function modelo() {
        return null;
    }

    function get_clase_vista() {
        if ($this->accion == 'index') {
            return 'vista';
        } else {
            return 'vista_encuesta';
        }
    }

    function accion__index()
	{

		$periodo = null;
		$comision = null;
		$anio_academico = null;

		$pagelet_filtros = $this->vista()->pagelet('filtros');
		$pagelet_listado_encuestas = $this->vista()->pagelet('listado_encuestas');

		if (isset($_GET['formulario_filtro'])) {//Si hay filtros...
			$form = $this->get_form();
			$this->recuperar_filtro();
			$filtros = $this->datos_filtro;

			$anio_academico_hash = $filtros['anio_academico'];
			$periodo_hash = $filtros['periodo'];
			$comision = $filtros['comision'];

			if ($anio_academico_hash) {
				//Valor en el filtro usado, se guarda para conservar valor luego de una busqueda
				$pagelet_filtros->add_var_js('anio_academico_hash', $anio_academico_hash);
			}
			if ($periodo_hash) {
				//Valor en el filtro usado, se guarda para conservar valor luego de una busqueda
				$pagelet_filtros->add_var_js('periodo', $periodo_hash);
			}
			if ($comision) {
				$pagelet_filtros->add_var_js('comision', $comision);
			} else {
				throw new ErrorKernelValidacion("Error al validar el filtro");
			}

			$parametros = array('comision' => $comision);
			$resultados_encuestas = catalogo::consultar('encuestas', 'reporte_encuestas_actuacion_otros_docentes_comision', $parametros);
			$pagelet_listado_encuestas->data['encuestas_resultados'] = $resultados_encuestas;
			$url_ver_bloques = kernel::vinculador()->crear('encuestas_resultados', 'ver_bloques');
			$pagelet_listado_encuestas->data['url_ver_bloques'] = $url_ver_bloques;
			klog2('Resultado Encuestas Filtradas', $resultados_encuestas);
		}
	}

    function accion__buscar_periodo_lectivo() {

        $anio_academico_hash = $this->validate_param('anio_academico', 'get', validador::TIPO_ALPHANUM);
        $anio_academico = $this->decodificar_parametro($anio_academico_hash, 'unidad_academica', 'anios_academicos', 'anio_academico');
        $datos = array();
        if ($anio_academico) {
            $datos['periodos'] = catalogo::consultar('unidad_academica', 'periodos_lectivos_un_anio_academico_docente', array('anio_academico' => $anio_academico, 'docente' => kernel::persona()->get_id_docente()));
        }
        klog2('accion__buscar_periodo_lectivo', $datos);
        $this->render_json($datos);
    }

    function accion__buscar_materias() {
        $materia = $this->getAutocompleteTerm();

        $docente = guarani::persona()->get_id_docente();

        if (!is_null($materia)) {
            $datos = catalogo::consultar('docente', 'lista_actividades', array('term' => $materia, 'docente' => $docente));
        }

        $this->render_raw_json($datos);
    }

    function accion__buscar_comision(){
        $hash_periodo = $this->validate_param('periodo_lectivo', 'get', validador::TIPO_ALPHANUM);
        $anio_academico_hash = $this->validate_param('anio_academico', 'get', validador::TIPO_ALPHANUM);

        $anio_academico = $this->decodificar_parametro($anio_academico_hash, 'unidad_academica', 'anios_academicos', 'anio_academico');
        $docente = guarani::persona()->get_id_docente();
		$periodo = $this->decodificar_parametro($hash_periodo, 'unidad_academica',  'periodos_lectivos_un_anio_academico_docente', 'periodo_lectivo', array('anio_academico' => $anio_academico, 'docente' => $docente));

        if (!is_null($docente) && !is_null($periodo)) {
            $datos['comisiones'] = catalogo::consultar('encuestas', 'get_comision_ver_encuestas_docente', array('periodo_lectivo' => $periodo, 'docente' => $docente));
        }
        klog2('accion__buscar_comision', $datos);
        return $this->render_json($datos);
    }

    /**
     * Solo la vista 'vista' tiene el form. Falla en otro caso
     */
    private function recuperar_filtro() {
        $form = $this->get_form();

        $validacion_ok = $form->procesar('GET');
        //si no se llega aca, aplican los valores default
        $this->datos_filtro = $form->get_datos();
        return $validacion_ok;
    }

    /**
     * @return guarani_form
     */
    function get_form() {
        return $this->get_form_builder()->get_formulario();
    }

    function get_vista_form() {
        return $this->get_form_builder()->get_vista();
    }

    /**
     * @return builder_form_filtro
     */
    function get_form_builder() {
        // se hace esta función para no construir cada vez el builder
        if (!isset($this->form_builder)) {
            $this->form_builder = kernel::localizador()->instanciar('operaciones\encuestas_resultados_docente\filtros\builder_form_filtro');
        }
        return $this->form_builder;
    }

    function decodificar_parametro($parametro_hash, $clase, $metodo, $campo_comparacion, $parametros_query = array()) {

        $datos = catalogo::consultar($clase, $metodo, $parametros_query);
        $array_column = array_map(function($element) {return $element[catalogo::id];}, $datos);

        if (!empty($datos)) {
                $value = array_search($parametro_hash, $array_column);
                if ($value !== false) {
                    return $datos[$value][$campo_comparacion];
                }
        }
        return false;
    }

    function decodificar_bloque($encuesta, $bloque_hash, $unidad_gestion) {

        $datos = encuestas_kolla::get_encuesta_bloques($encuesta, $unidad_gestion);
        $array_column = array_map(function($element) {return $element[catalogo::id];}, $datos['detalle']);

        if (!empty($datos)) {
            $value = array_search($bloque_hash, $array_column);
                if ($value !== false) {
                    return $datos['detalle'][$value]['bloque'];
                }
        }
        return false;
    }

    function decodificar_materia($materia_hash) {

        $docente = guarani::persona()->get_id_docente();
        $datos = catalogo::consultar('docente', 'lista_actividades', array('term' => '', 'docente' => $docente));

        if (!empty($datos)) {
            foreach ($datos as $value) {
                if ($value['id'] == $materia_hash) {
                    return array($value['elemento'], "{$value['label']}");
                }
            }
        }
        return array(null, null);
    }

}
