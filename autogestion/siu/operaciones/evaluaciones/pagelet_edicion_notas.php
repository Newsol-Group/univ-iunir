<?php
namespace siu\operaciones\evaluaciones;

use \SIU\Chulupi\kernel;
use SIU\Chulupi\util\modelo\DecoradorIds;
use \siu\modelo\datos\catalogo;
use siu\operaciones\_comun\pagelets\pagelet_renglones;

class pagelet_edicion_notas extends pagelet_renglones
{

    function get_url_guardar()
    {
        return kernel::vinculador()->crear('evaluaciones', 'editar_alumnos');
    }

    function get_escalas()
    {
        $cabecera = $this->controlador->get_encabezado();
        return $cabecera['escala_nota'];
    }

    function get_nombre()
	{
		return 'edicion_notas';
	}

	function get_js_files()
	{
		$archivos = parent::get_js_files();
		$archivos[] = kernel::vinculador()->vinculo_recurso('js/plugin_autocompletar.js');
		return $archivos;
	}

	function prepare()
	{
		$eval = $this->controlador->params_comision;
		$evaluacion_id = $eval[DecoradorIds::$nombre_columna_generada];

                // Si se trata de una subcomisión
		$this->data['es_subcomision'] = !empty($eval['subcomision']);

		$this->data['cabecera'] = $this->controlador->modelo()->info__evaluacion_cabecera($eval);
		$this->data['alumnos'] = $this->controlador->modelo()->info__evaluacion_detalle($eval);
		$this->data['resultados'] = $this->controlador->modelo()->info__resultados_posibles_escala_nota($this->data['cabecera']['escala_nota']);
                
                $this->data['subcomisiones'] = [];
                // Si se trata de una subcomisión
                if ($this->data['es_subcomision']) {
                    $this->data['subcomisiones'] = catalogo::consultar('carga_evaluaciones_parciales', 'lista_subcomisiones_comision', [
                        'comision' => $this->data['cabecera']['comision'],
                        'docente' => kernel::persona()->get_id_docente()
                    ]);
                }

                $this->data['escala_continua'] = \escalas_notas::tipo_continua;
		$this->data['escala_discreta'] = \escalas_notas::tipo_discreta;

		/** @var $evaluaciones ProcesadorVista */
		$evaluaciones = $this->controlador->codificador_modelo()->info__lista_evaluaciones($this->controlador->params_comision);
		$evaluaciones->link('LINK', 'evaluaciones', 'agregar_resultado');
		//$evaluaciones = $evaluaciones->get_datos();
		foreach ($evaluaciones->get_datos() as $evaluacion) {
			if ($evaluacion['comision'] == $eval['comision'] && $evaluacion['id_fila'] != $eval['id_fila'])
				$this->data['evaluaciones'][] = $evaluacion;
		}

		$this->add_var_js('hay_alumnos', !empty($this->data['alumnos']));
		$this->add_var_js('url_escala', kernel::vinculador()->crear('evaluaciones', 'info_escala', $this->data['cabecera']['escala_nota']));
		$this->add_var_js('url_subcomision', kernel::vinculador()->crear('evaluaciones', 'info_subcomision'));

                $params_url_buscar_alumnos = [];
                $params_url_buscar_alumnos['comision'] = $this->data['cabecera']['comision'];
                // Si es una subcomisión
                if (!empty($eval['subcomision'])) {
                    $params_url_buscar_alumnos['subcomision'] = $eval['subcomision'];
                }
		$this->add_var_js('url_buscar_alumnos', kernel::vinculador()->crear('evaluaciones', 'buscar_alumnos', $params_url_buscar_alumnos));

		$this->add_var_js('evaluacion', $evaluacion_id);
		$this->add_var_js('url_agregar_alumno', kernel::vinculador()->crear('evaluaciones', 'agregar_alumno'));
		$this->add_var_js('url_agregar_comision', kernel::vinculador()->crear('evaluaciones', 'agregar_comision'));
		$this->add_var_js('url_agregar_subcomision', kernel::vinculador()->crear('evaluaciones', 'agregar_subcomision'));
		$this->add_var_js('url_agregar_resultado', kernel::vinculador()->crear('evaluaciones', 'agregar_resultado'));
		$this->add_var_js('url_eliminar', kernel::vinculador()->crear('evaluaciones', 'eliminar_alumno'));
		$this->add_var_js('url_guardar', $this->get_url_guardar());
		$this->add_var_js('texto_invalido', kernel::traductor()->trans('texto_invalido'));
		$this->add_var_js('escala_continua', \escalas_notas::tipo_continua);
		$this->add_var_js('escala_discreta', \escalas_notas::tipo_discreta);
		$this->add_var_js('id_escala', $this->get_escalas());

        if ($this->data['cabecera']['escala_nota_tipo'] == \escalas_notas::tipo_discreta)
        {
            $this->data['escala_notas'] = $this->controlador->modelo()->info__escala_notas($this->data['cabecera']['escala_nota']);
            $resultados = array();
            foreach ($this->data['escala_notas'] as $escala)
                $resultados[] = array(
                    'nota' => $escala['nota'],
                    'resultado' => $escala['resultado'],
                );
        }
		$this->add_var_js('resultado_notas', $resultados);
		$this->data['url_back'] = $this->controlador->get_index_url();
		$this->add_var_js('alumnos', $this->controlador->modelo()->info__evaluacion_alumnos($eval));
		$this->data['resultado_ausente'] = \escalas_notas::resultado_ausente;
	}
}