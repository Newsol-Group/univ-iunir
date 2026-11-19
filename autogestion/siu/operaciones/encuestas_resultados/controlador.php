<?php

namespace siu\operaciones\encuestas_resultados;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\encuestas_kolla;

class controlador extends controlador_g3w2
{
	protected $form_builder;
	protected $datos_filtro = array('anio_academico' => "", 'periodo' => "");

	function modelo()
	{
		return null;
	}

	function get_clase_vista()
	{
		if ($this->accion == 'index') {
			return 'vista';
		} else {
			return 'vista_encuesta';
		}
	}

	function accion__index()
	{

		$materia = null;
		$materia_descr = null;
		$periodo = null;
		$turno = null;
		$anio_academico = null;

		$docente = kernel::persona()->get_id_docente();
		$pagelet_filtros = $this->vista()->pagelet('filtros');

		if (isset($_GET['formulario_filtro'])) {//Si hay filtros...
			$form = $this->get_form();
			$this->recuperar_filtro();
			$filtros = $this->datos_filtro;

			$anio_academico_hash = $filtros['anio_academico'];
			$periodo_hash = $filtros['periodo'];
			$materia_hash = $filtros['materia'];

			if ($anio_academico_hash) {
				$anio_academico = $this->decodificar_parametro($anio_academico_hash, 'unidad_academica', 'anios_academicos', 'anio_academico');
				//Valor en el filtro usado, se guarda para conservar valor luego de una busqueda
				$pagelet_filtros->add_var_js('anio_academico_hash', $anio_academico_hash);
			}
			if ($periodo_hash) {
				$periodo = $this->decodificar_parametro($periodo_hash, 'unidad_academica', 'periodos_lectivos_un_anio_academico', 'periodo_lectivo', array('anio_academico' => $anio_academico, 'docente' => kernel::persona()->get_id_docente()));
				//Valor en el filtro usado, se guarda para conservar valor luego de una busqueda
				$pagelet_filtros->add_var_js('periodo', $periodo_hash);
			}
			if ($materia_hash) {
				list($materia, $materia_descr) = $this->decodificar_materia($materia_hash);
			}

			$form->set_datos(array("materia_descr" => $materia_descr));
		}

		if (!isset($anio_academico)) {
			$anios_academicos = catalogo::consultar('unidad_academica', 'anios_academicos');
			$anio_academico = $anios_academicos[0]['anio_academico'];
		}

		$parametros = array('anio_academico' => $anio_academico,
			'periodo_lectivo' => $periodo,
			'actividad' => $materia,
			'docente' => $docente,
			'comision' => null);

		$resultados_encuestas = catalogo::consultar('encuestas', 'reporte_encuestas_actuacion_docente', $parametros);

		klog2('Resultado Encuestas Filtradas', $resultados_encuestas);

		$url_ver_bloques = kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'ver_bloques');
		$pagelet_listado_encuestas = $this->vista()->pagelet('listado_encuestas');
		$pagelet_listado_encuestas->data['encuestas_resultados'] = $resultados_encuestas;
		$pagelet_listado_encuestas->data['url_ver_bloques'] = $url_ver_bloques;
	}

	function accion__ver_bloques()
	{

		$encuesta_hash = $this->validate_param('encuesta', 'get', validador::TIPO_ALPHANUM);
		$formulario_encuesta_hash = $this->validate_param('formulario', 'get', validador::TIPO_ALPHANUM);
		$id_habilitacion_kolla_hash = $this->validate_param('habilitacion', 'get', validador::TIPO_ALPHANUM);
		// Cuando la Unidad de Gestión es '0' viene como vació '', por eso se usa el 'default' => '0'
		$url_unidad_gestion = $this->validate_param('unidad_gestion', 'get', validador::TIPO_TEXTO, ['default' => '0']);
		$comision = $this->validate_param('comision', 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
		$elemento_encuestado_hash = $this->validate_param('elemento', 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
		$encuesta = $this->decodificar_parametro($encuesta_hash, 'encuestas', 'get_id_kolla_encuestas', 'kolla_id_encuesta');

		$pagelet_listado_bloques = $this->vista()->pagelet('listado_encuesta');

		try {

            if ($comision){
				$id_formulario = $this->decodificar_parametro($formulario_encuesta_hash, 'encuestas', 'get_id_formularios', 'formulario');
				$docentes = catalogo::consultar('docente', 'get_docentes_formulario', array('formulario' => $id_formulario));
				$pagelet_listado_bloques->data['docentes'] = $docentes;
				$pagelet_listado_bloques->add_var_js('comision', $comision);
				$pagelet_listado_bloques->add_var_js('unidad_gestion', $url_unidad_gestion);
			}

			//Bloques de la encuesta $encuesta pedidas a Kolla
			$encuesta_bloques = encuestas_kolla::get_encuesta_bloques($encuesta, $url_unidad_gestion);

			$this->procesar_bloque($encuesta_bloques);

			klog2('Resultado Bloques', $encuesta_bloques);
			//$formulario_externo_hash = ;
			//$elemento_externo_encuestado_hash = ;

			$pagelet_listado_bloques->data['bloques_encuesta'] = $encuesta_bloques;
			$pagelet_listado_bloques->data['id_formulario'] = $formulario_encuesta_hash;
			$pagelet_listado_bloques->data['id_elemento'] = $elemento_encuestado_hash;
			$pagelet_listado_bloques->data['id_habilitacion'] = $id_habilitacion_kolla_hash;
			$pagelet_listado_bloques->data['id_encuesta'] = $encuesta_hash;
			$pagelet_listado_bloques->data['kolla_unidad_gestion'] = $url_unidad_gestion;
			$pagelet_listado_bloques->data['url_ver_preguntas'] = kernel::vinculador()->crear('encuestas_resultados', 'ver_preguntas') . '/' . $encuesta_hash . '/' . $url_unidad_gestion . '/';
		} catch (\Exception $ex) {
			$pagelet_listado_bloques->data['mensaje_error'] = $ex->getMessage();
		}
	}

	function accion__ver_preguntas()
	{

		$encuesta_hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		// Cuando la Unidad de Gestión es '0' viene como vació '', por eso se usa el 'default' => '0'
		$unidad_gestion = $this->validate_param(1, 'get', validador::TIPO_TEXTO, ['default' => '0']);
		$bloque_hash = $this->validate_param(2, 'get', validador::TIPO_ALPHANUM);

		$encuesta = $this->decodificar_parametro($encuesta_hash, 'encuestas', 'get_id_kolla_encuestas', 'kolla_id_encuesta');
		$bloque = $this->decodificar_bloque($encuesta, $bloque_hash, $unidad_gestion);

		try {
			//Preguntas devueltas por kolla de la encuesta $encuesta+$bloque
			$rta_kolla_preguntas = encuestas_kolla::get_preguntas($encuesta, $bloque, $unidad_gestion);
			$this->procesar_preguntas($rta_kolla_preguntas);
			$preguntas['preguntas'] = $rta_kolla_preguntas['bloques'][0]['preguntas'];
			$preguntas['url_ver_respuestas'] = kernel::vinculador()->crear('encuestas_resultados', 'ver_respuestas') . '/' . $encuesta_hash . '/' . $bloque_hash . '/' . $unidad_gestion . '/';
			$preguntas['id_bloque'] = $bloque;
		} catch (\Exception $ex) {
			$preguntas['mensaje_error'] = $ex->getMessage();
		}

		klog2('Preguntas devueltas Kolla', $preguntas['preguntas']);

		$this->render_template('listado_preguntas.twig', $preguntas);
	}

	function accion__ver_respuestas()
	{

		$encuesta_hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		$bloque_hash = $this->validate_param(1, 'get', validador::TIPO_ALPHANUM);
		// Cuando la Unidad de Gestión es '0' viene como vació '', por eso se usa el 'default' => '0'
		$unidad_gestion = $this->validate_param(2, 'get', validador::TIPO_TEXTO, ['default' => '0']);
		$id_pregunta = $this->validate_param(3, 'get', validador::TIPO_INT);

		try {
			$formulario_encuesta_hash = $this->validate_param('formulario_encuesta', 'get', validador::TIPO_ALPHANUM);
			$elemento_encuestado_hash = $this->validate_param('elemento_encuestado', 'get', validador::TIPO_ALPHANUM);
			$id_habilitacion_kolla_hash = $this->validate_param('id_habilitacion_alcance_kolla', 'get', validador::TIPO_ALPHANUM);
			$pregunta_orden = $this->validate_param('pregunta_orden', 'get', validador::TIPO_ALPHANUM);

			$formulario_encuesta = $this->decodificar_parametro($formulario_encuesta_hash, 'encuestas', 'get_id_formularios', 'formulario');
			$elemento_encuestado = $this->decodificar_parametro($elemento_encuestado_hash, 'encuestas', 'get_id_elementos', 'item');
			$id_habilitacion_kolla = $this->decodificar_parametro($id_habilitacion_kolla_hash, 'encuestas', 'get_id_kolla_habilitacion', 'kolla_id_habilitacion');
			$id_encuesta = $this->decodificar_parametro($encuesta_hash, 'encuestas', 'get_id_kolla_encuestas', 'kolla_id_encuesta');
			$id_bloque = $this->decodificar_bloque($id_encuesta, $bloque_hash, $unidad_gestion);


			//Recupero el valor de formulario y elemento registro en las tablas de  kolla.		
			$id_formulario = catalogo::consultar('encuestas', 'get_id_formulario_externo', array('formulario' => $formulario_encuesta));
			$id_elemento = catalogo::consultar('encuestas', 'get_id_elemento_externo', array('item' => $elemento_encuestado));

			$rta_kolla = encuestas_kolla::get_respuesta($id_habilitacion_kolla, $id_formulario, $id_encuesta, $id_elemento, $id_bloque, $id_pregunta, $pregunta_orden, $unidad_gestion);
			$this->procesar_respuestas($rta_kolla, $unidad_gestion);

			//Ya que el servicio devuelve una sola respuesta siempre, se elije solo la primera posicion.
			$rta_kolla = $rta_kolla['bloques'][0]['preguntas'][0];

			if (kernel::proyecto()->get_encuesta_evaluar_rtas_libres_expreg() && ($rta_kolla['es_libre'] == 'S')) {
				$this->evaluar_rtas_texto($rta_kolla);
			}

			if ($rta_kolla) {
				$this->completar_info_grafico($rta_kolla, $id_bloque);
				$respuestas['pregunta'] = $rta_kolla;
			}
		} catch (\Exception $ex) {
			$respuestas['mensaje_error'] = $ex->getMessage();
		}

		klog2("Respuesta devuelta Kolla y procesada por guarani", $respuestas);

		$this->render_template('listado_respuestas.twig', $respuestas);
	}

	function accion__ver_encuesta_completa()
	{

		$encuesta_hash = $this->validate_param('encuesta', 'get', validador::TIPO_ALPHANUM);
		$formulario_encuesta_hash = $this->validate_param('formulario_encuesta', 'get', validador::TIPO_ALPHANUM);
		$elemento_encuestado_hash = $this->validate_param('elemento_encuestado', 'get', validador::TIPO_ALPHANUM);
		$id_habilitacion_kolla_hash = $this->validate_param('habilitacion', 'get', validador::TIPO_ALPHANUM);
		// Cuando la Unidad de Gestión es '0' viene como vació '', por eso se usa el 'default' => '0'
		$unidad_gestion = $this->validate_param('unidad_gestion', 'get', validador::TIPO_TEXTO, ['default' => '0']);
		$comision = $this->validate_param('comision', 'get', validador::TIPO_TEXTO, array('allowempty' => true));
		$docentes = null;

		try {

			$id_formulario = $this->decodificar_parametro($formulario_encuesta_hash, 'encuestas', 'get_id_formularios', 'formulario');
			$id_elemento = $this->decodificar_parametro($elemento_encuestado_hash, 'encuestas', 'get_id_elementos', 'item');

			//Recupero el valor de formulario y elemento registro en las tablas de  kolla.		
			$id_formulario = catalogo::consultar('encuestas', 'get_id_formulario_externo', array('formulario' => $id_formulario));
			$id_elemento = catalogo::consultar('encuestas', 'get_id_elemento_externo', array('item' => $id_elemento));


			$id_habilitacion = $this->decodificar_parametro($id_habilitacion_kolla_hash, 'encuestas', 'get_id_kolla_habilitacion', 'kolla_id_habilitacion');
			$id_encuesta = $this->decodificar_parametro($encuesta_hash, 'encuestas', 'get_id_kolla_encuestas', 'kolla_id_encuesta');

			if ($comision) {
				$id_formulario = $this->decodificar_parametro($formulario_encuesta_hash, 'encuestas', 'get_id_formularios', 'formulario');
				$docentes = catalogo::consultar('docente', 'get_docentes_formulario', array('formulario' => $id_formulario));
			}

			$encuesta_completa = encuestas_kolla::get_encuesta_completa($id_habilitacion, $id_formulario, $id_encuesta, $id_elemento, $unidad_gestion);
			$url_ver_preguntas = kernel::vinculador()->crear('encuestas_resultados', 'ver_preguntas') . '/' . $encuesta_hash . '/';

			$this->procesar_respuestas($encuesta_completa, $unidad_gestion);

			//Recorro el array con todas las respuestas dentro de cada bloque y por cada pregunta agrego los datos necesarios para graficar las rtas
			foreach ($encuesta_completa['bloques'] as $key => $bloque) {
				foreach ($bloque['preguntas'] as $key2 => $pregunta) {

					$this->completar_info_grafico($encuesta_completa['bloques'][$key]['preguntas'][$key2], $bloque['bloque']);

					if (kernel::proyecto()->get_encuesta_evaluar_rtas_libres_expreg() && ($pregunta['es_libre'] == 'S')) {

						$this->evaluar_rtas_texto($encuesta_completa['bloques'][$key]['preguntas'][$key2]);
					}
				}
			}

			$encuesta_completa['data'] = array(
				'id_encuesta' => $encuesta_hash,
				'kolla_unidad_gestion' => $unidad_gestion,
				'id_habilitacion' => $id_habilitacion_kolla_hash,
				'id_formulario' => $formulario_encuesta_hash,
				'id_elemento' => $elemento_encuestado_hash,
				'url_ver_preguntas' => $url_ver_preguntas,
				'docentes' => $docentes,
				'bloques_encuesta' => array('nombre' => $encuesta_completa['nombre'], 'bloques' => $encuesta_completa['bloques']));

			unset($encuesta_completa['bloques']);

			klog2('Encuesta completa devuelta por Kolla', $encuesta_completa);
		} catch (\Exception $ex) {
			$encuesta_completa['mensaje_error'] = $ex->getMessage();
		}

		$this->render_template('listado_bloques.twig', $encuesta_completa);
	}

	//Compara las rtas de texto libre con la expresion regular, en caso de encontrarse la elimina y la cuenta para dps informarle al usuario
	function evaluar_rtas_texto(&$preg)
	{

		$count_rtas_borradas = 0;

		foreach ($preg['respuestas'] as $key => $rta) {

			$ignorar = array("/^[\.\-\,\ ]+$/", "/no.*tu/i", "/no.*ten/i", "/no fue mi/i", "/no opin/i", "/sin coment/i", "/^ningun./i", "/^sin observ/i", "/no hay observ/i", "/nada para agregar/i", "/nada en especial/i", "/sin opini/i", "/^sin descripc/i");

			foreach ($ignorar as $patron) {

				//si el valor de la rta es igual a alguno de los patrones
				if (preg_match($patron, strtolower(trim($rta['respuesta_valor'])))) {

					//me aseguro que la porcion encontrada no sea parte de una rta larga
					if (strlen(trim($rta['respuesta_valor'])) < 50) {

						$count_rtas_borradas++;
						unset($preg['respuestas'][$key]);
					}
				}
			}
		}

		$preg['rtas_omitidas'] = $count_rtas_borradas;
	}

	//Normaliza las rtas de kolla
	function procesar_bloque(&$rta_kolla_bloques)
	{

		$rta_kolla_bloques['bloques'] = array();

		foreach ($rta_kolla_bloques['detalle'] as $bloque) {

			$rta_kolla_bloques['bloques'][] = $bloque;
		}
		unset($rta_kolla_bloques['detalle']);
	}

	//Normaliza las rtas de kolla
	function procesar_preguntas(&$rta_kolla_preguntas)
	{

		$rta_kolla_preguntas['detalle']['preguntas'] = array();

		foreach ($rta_kolla_preguntas['detalle']['detalle'] as $pregunta) {
			$rta_kolla_preguntas['detalle']['preguntas'][] = $pregunta;
		}

		unset($rta_kolla_preguntas['detalle']['detalle']);
		$rta_kolla_preguntas['bloques'][] = $rta_kolla_preguntas['detalle'];
		unset($rta_kolla_preguntas['detalle']);
	}

	//Normaliza las rtas de kolla
	function procesar_respuestas(&$rta_kolla_respuestas, $unidad_gestion)
	{

		$bloque_anterior = -1;
		$cont_bloques = -1;
		$rta_kolla_respuestas['bloques'] = array();
		$array_bloques = encuestas_kolla::get_encuesta_bloques($rta_kolla_respuestas['encuesta'], $unidad_gestion);

		foreach ($rta_kolla_respuestas['detalle'] as $key => $pregunta) {

			if ($pregunta['bloque'] != $bloque_anterior) {

				$key_bloque = array_search($pregunta['bloque'], array_column($array_bloques['detalle'], 'bloque'));

				$rta_kolla_respuestas['bloques'][] = array('bloque' => $pregunta['bloque'],
					'nombre' => $array_bloques['detalle'][$key_bloque]['nombre'],
					'preguntas' => array(0 => array('pregunta_id' => $pregunta['pregunta_id'],
							'pregunta_texto' => $pregunta['pregunta_texto'],
							'componente' => $pregunta['componente'],
							'es_libre' => $pregunta['es_libre'],
							'es_multiple' => $pregunta['es_multiple'],
							'obligatoria' => $pregunta['obligatoria'],
							'pregunta_orden_bloque' => $pregunta['pregunta_orden_bloque'],
							'respuestas' => $pregunta['respuestas']
						)
					)
				);
				$cont_bloques++;
			} else {

				$rta_kolla_respuestas['bloques'][$cont_bloques]['preguntas'][] = array('pregunta_id' => $pregunta['pregunta_id'],
					'pregunta_texto' => $pregunta['pregunta_texto'],
					'componente' => $pregunta['componente'],
					'es_libre' => $pregunta['es_libre'],
					'es_multiple' => $pregunta['es_multiple'],
					'obligatoria' => $pregunta['obligatoria'],
					'pregunta_orden_bloque' => $pregunta['pregunta_orden_bloque'],
					'respuestas' => $pregunta['respuestas']
				);
			}
			$bloque_anterior = $pregunta['bloque'];
		}

		unset($rta_kolla_respuestas['detalle']);
	}

	function completar_info_grafico(&$respuestas, $bloque)
	{

		if ($respuestas) {

			$data_grafico = "[";

			//Cuenta la cantidad de respuestas por cada opcion de respuesta.
			$cantidad_rtas = array_sum(array_map(function ($element) {
						return $element['elegida_veces'];
					}, $respuestas['respuestas']));

			//Recorre el array de de cada opcion de rta
			foreach ($respuestas['respuestas'] as $key => $respuesta) {

				//Agrega la info de cada opcion segun el fomato requerido de libreria para generar grafico
				$data_grafico .= "['" . $respuesta['respuesta_valor'] . " (" . $respuesta['elegida_veces'] . ")', " . $respuesta['elegida_veces'] . "],";

				//Por cada opcion de rta agrega el porcentaje que fue elegida para el caso donde veo rtas en formato tabla.
				if ($cantidad_rtas > 0) {
					$respuestas['respuestas'][$key]['porcentage_elegido'] = round((($respuesta['elegida_veces'] * 100) / $cantidad_rtas));
				} else {
					$respuestas['respuestas'][$key]['porcentage_elegido'] = 0;
				}
			}

			$data_grafico .= "]";
			$respuestas['data_grafico']['data'] = $data_grafico;
			$respuestas['data_grafico']['id'] = $bloque . $respuestas['pregunta_id'] . $respuestas['pregunta_orden_bloque'];
		}
	}

	function accion__buscar_periodo_lectivo()
	{

		$anio_academico_hash = $this->validate_param('anio_academico', 'get', validador::TIPO_ALPHANUM);
		$anio_academico = $this->decodificar_parametro($anio_academico_hash, 'unidad_academica', 'anios_academicos', 'anio_academico');
		$datos = array();
		if ($anio_academico) {
			$datos['periodos'] = catalogo::consultar('unidad_academica', 'periodos_lectivos_un_anio_academico_docente', array('anio_academico' => $anio_academico, 'docente' => kernel::persona()->get_id_docente()));
		}
		klog2('accion__buscar_periodo_lectivo', $datos);
		$this->render_json($datos);
	}

	function accion__buscar_materias()
	{
		$materia = $this->getAutocompleteTerm();

		$docente = guarani::persona()->get_id_docente();

		if (!is_null($materia)) {
			$datos = catalogo::consultar('docente', 'lista_actividades', array('term' => $materia, 'docente' => $docente));
		}

		$this->render_raw_json($datos);
	}

	/**
	 * Solo la vista 'vista' tiene el form. Falla en otro caso
	 */
	private function recuperar_filtro()
	{
		$form = $this->get_form();

		$validacion_ok = $form->procesar('GET');
		//si no se llega aca, aplican los valores default
		$this->datos_filtro = $form->get_datos();
		return $validacion_ok;
	}

	/**
	 * @return guarani_form
	 */
	function get_form()
	{
		return $this->get_form_builder()->get_formulario();
	}

	function get_vista_form()
	{
		return $this->get_form_builder()->get_vista();
	}

	/**
	 * @return builder_form_filtro
	 */
	function get_form_builder()
	{
		// se hace esta función para no construir cada vez el builder
		if (!isset($this->form_builder)) {
			$this->form_builder = kernel::localizador()->instanciar('operaciones\encuestas_resultados\filtros\builder_form_filtro');
		}
		return $this->form_builder;
	}

	function decodificar_parametro($parametro_hash, $clase, $metodo, $campo_comparacion, $parametros_query = array())
	{

		$datos = catalogo::consultar($clase, $metodo, $parametros_query);
		$array_column = array_map(function($element) {
			return $element[catalogo::id];
		}, $datos);

		if (!empty($datos)) {
			$value = array_search($parametro_hash, $array_column);
			if ($value !== false) {
				return $datos[$value][$campo_comparacion];
			}
		}
		return false;
	}

	function decodificar_bloque($encuesta, $bloque_hash, $unidad_gestion)
	{

		$datos = encuestas_kolla::get_encuesta_bloques($encuesta, $unidad_gestion);
		$array_column = array_map(function($element) {
			return $element[catalogo::id];
		}, $datos['detalle']);

		if (!empty($datos)) {
			$value = array_search($bloque_hash, $array_column);
			if ($value !== false) {
				return $datos['detalle'][$value]['bloque'];
			}
		}
		return false;
	}

	function decodificar_materia($materia_hash)
	{

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