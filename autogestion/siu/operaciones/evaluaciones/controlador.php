<?php
namespace siu\operaciones\evaluaciones;

use siu\extension_kernel\controlador_g3w2;
use \siu\guarani;
use \SIU\Chulupi\kernel;
use \SIU\Chulupi\util\validador;
use \siu\modelo\datos\catalogo;
use \siu\errores\error_guarani;
use \siu\errores\error_guarani_comision_inexistente;
use \siu\errores\error_guarani_evaluacion_inexistente;
use \siu\modelo\_comun\comunes;

class controlador extends controlador_g3w2
{
	public $params_comision;
	private $url_index;
	private $info_comision;

	function get_clase_vista()
	{
		switch ($this->accion) {
			case 'nueva_evaluacion':
			case 'crear_evaluacion':

				return 'vista_crear';

			case 'editar_evaluacion':
			case 'actualizar_evaluacion':

				return 'vista_editar';
			case 'listar_notas':
				return 'vista_notas';
			case 'editar_notas':
				return 'vista_edicion_notas';
		}
		return parent::get_clase_vista();
	}

	/** @return \siu\modelo\transacciones\carga_evaluaciones_parciales */
	function modelo()
	{
		return guarani::carga_evaluaciones_parciales();
	}

	function get_info_zona()
	{
		if (isset($this->params_comision)) {
			return array('zona' => 'comision', 'seleccion' => $this->params_comision);
		}
	}


	function get_index_url()
	{
		if (!$this->url_index) {
			$com = $this->get_info_comision();
			$com->link('LINK', 'evaluaciones', 'ver_comision');
			$datos = $com->get_datos();
			$this->url_index = $datos[0]['LINK'];
		}
		return $this->url_index;
	}

	function get_info_comision()
	{

		if (!$this->info_comision) {
			$this->info_comision = $this->codificador_modelo()->info__comision($this->params_comision);
		}
		return $this->info_comision;
	}

	function accion__index()
	{

	}

	function accion__ver_comision()
	{
		$this->params_comision = $this->decodificar_param(0, 'GET', '*');
	}

	function accion__listar_notas()
	{
		$this->params_comision = $this->decodificar_param(0, 'GET');
	}

	function accion__editar_notas()
	{
		$this->params_comision = $this->decodificar_param(0, 'GET');
	}

	function accion__nueva_evaluacion()
	{
		$this->params_comision = $this->decodificar_param(0, 'GET');

	}

	function accion__editar_evaluacion()
	{
		$this->params_comision = $this->decodificar_param(0, 'GET');
	}

	function accion__crear_evaluacion()
	{
		if (!kernel::request()->isPost()) {
			return;
		}
		$this->params_comision = $this->decodificar_param(0, 'GET', 'nueva_evaluacion');
		$form = $this->get_form();
		if ($form->procesar() ) {
			$rta_datos_form = $this->validar_datos_form($form->get_datos());
			if ($rta_datos_form['datos_validos']) {
				try {

					$mensaje = $this->modelo()->alta_evaluacion_parcial($this->params_comision, $form->get_datos());
					$this->nuevo_request_url($this->get_index_url(), $mensaje);
					//$this->render_ajax($mensaje, array('estado' => 'ok'));

				} catch (error_guarani $e) {
					kernel::renderer()->add_to_ajax_response('estado', 'error');
					kernel::renderer()->add_to_ajax_response('mensaje', $e->getMessage());
					kernel::log()->add_error($e);
				}
			} else {
				if(!$rta_datos_form['hora_fin_valida']) kernel::renderer()->add_to_ajax_response('error_hora_fin', kernel::traductor()->trans('800CUR_evaluacion_alta_error_hora_fin'));
				if(!$rta_datos_form['fecha_valida']) kernel::renderer()->add_to_ajax_response('error_fecha', kernel::traductor()->trans('800CUR_evaluacion_alta_error_fecha'));
				kernel::renderer()->add_to_ajax_response('estado', 'error');
				kernel::renderer()->add_to_ajax_response('mensaje', 'Hubo errores en la validación');
			}
		} else {
			kernel::renderer()->add_to_ajax_response('estado', 'error');
			kernel::renderer()->add_to_ajax_response('mensaje', 'Hubo errores en la validación');
		}

	}

	function accion__actualizar_evaluacion()
	{
		if (!kernel::request()->isPost()) {
			return;
		}
		$this->params_comision = $this->decodificar_param(0, 'GET', 'editar_evaluacion');
		$form = $this->get_form();

		if ($form->procesar())
		{
			$rta_datos_form = $this->validar_datos_form($form->get_datos());
			if ($rta_datos_form['datos_validos']) {
				try {
					$mensaje = $this->modelo()->modificar_evaluacion_parcial($this->params_comision, $form->get_datos());
					//$this->render_ajax($mensaje, array('estado' => 'ok'));
					$this->nuevo_request_url($this->get_index_url(), $mensaje);
				} catch (error_guarani $e) {
					kernel::renderer()->add_to_ajax_response('estado', 'error');
					kernel::renderer()->add_to_ajax_response('mensaje', $e->getMessage());
					kernel::log()->add_error($e);
				}
			}
			else{
				if(!$rta_datos_form['hora_fin_valida']) kernel::renderer()->add_to_ajax_response('error_hora_fin', kernel::traductor()->trans('800CUR_evaluacion_alta_error_hora_fin'));
				if(!$rta_datos_form['fecha_valida']) kernel::renderer()->add_to_ajax_response('error_fecha', kernel::traductor()->trans('800CUR_evaluacion_alta_error_fecha'));
				kernel::renderer()->add_to_ajax_response('estado', 'error');
				kernel::renderer()->add_to_ajax_response('mensaje', 'Hubo errores en la validación');
			}
		} else {
			kernel::renderer()->add_to_ajax_response('estado', 'error');
			kernel::renderer()->add_to_ajax_response('mensaje', 'Hubo errores en la validación');
		}
	}


	function accion__borrar_evaluacion()
	{
		$this->params_comision = $this->decodificar_param(0, 'get');
		try {
			$mensaje = $this->modelo()->baja_evaluacion_parcial($this->params_comision);
			$this->render_ajax($mensaje, array('result' => 0));
		} catch (error_guarani_evaluacion_inexistente $e) {
			kernel::log()->add_error($e);
			$this->render_ajax($e->getMessage(), array('result' => -1));
		}
		catch (error_guarani $e) {
			kernel::log()->add_error($e);
			$this->render_ajax($e->getMessage(), array('result' => 1));
		}
	}

	function accion__info_escala()
	{
		$this->render_ajax('', array(
			'html' => kernel::load_template('escala_notas.twig')->render(array(
				'notas' => $this->modelo()->info__escala_notas($this->validate_param(0, 'get', validador::TIPO_INT))
			))
		));
	}

	function accion__info_subcomision()
	{
		$this->params_comision = $this->decodificar_param('evaluacion', 'get', 'editar_notas');
		$subcomision_param = $this->validate_param('subcomision', 'get', validador::TIPO_INT);
		$evaluacion = $this->params_comision; //$this->modelo()->info__evaluacion_cabecera(kernel::request()->getParam('evaluacion'));
		$subcomisiones = catalogo::consultar('carga_evaluaciones_parciales', 'lista_subcomisiones_comision', [
			'comision' => $evaluacion['comision'],
                        'docente' => kernel::persona()->get_id_docente()
		]);
		foreach ($subcomisiones as $subcomision) {
			if ($subcomision['subcomision'] == $subcomision_param)
				$this->render_ajax('', array(
					'html' => kernel::load_template('subcomision.twig')->render(array(
						'nombre' => $subcomision['nombre'],
						'tipo' => $subcomision['tipo_clase'],
						'horario' => $subcomision['horario'],
						'docentes' => $subcomision['docentes'],
					))
				));
		}
	}

	public function accion__buscar_alumnos()
	{
		$json = array();
		$alumnos = catalogo::consultar('carga_evaluaciones_parciales', 'buscar_alumnos_comision', array(
			'comision' => kernel::request()->getQuery('comision'),
			'subcomision' => kernel::request()->getQuery('subcomision'),
			'term' => $this->getAutocompleteTerm(),
		));
		foreach ($alumnos as $alumno) {
			$json[] = array(
				'id' => $alumno['alumno'],
				'label' => $alumno['alumno_nombre'] . (!empty($alumno['legajo']) ? ' (Legajo: ' . $alumno['legajo'] . ')' : '')
			);
		}
		$this->render_raw_json($json);
	}

	public function accion__agregar_alumno()
	{
		$this->params_comision = $this->decodificar_param('evaluacion', 'post', 'editar_notas');
		$alumno = $this->validate_param('alumno', 'post', validador::TIPO_INT);
		try {
			$agregados = $this->modelo()->agregar_alumno($this->params_comision, $alumno);
			$evaluacion = $this->modelo()->info__evaluacion_cabecera($this->params_comision);
			$resultados = $this->modelo()->info__resultados_posibles_escala_nota($evaluacion['escala_nota']);
			$this->render_ajax('', array(
				'result' => 0,
				'agregados' => $agregados,
				'html' => $agregados
					? kernel::load_template('lista_alumnos.twig')->render(array(
						'alumnos' => $this->modelo()->info__evaluacion_detalle($this->params_comision),
						'escala_notas' => $this->modelo()->info__escala_notas($evaluacion['escala_nota']),
						'editar' => true,
                        'escala_continua' => \escalas_notas::tipo_continua,
                        'escala_discreta' => \escalas_notas::tipo_discreta,
                        'escala_notas_comision' => $evaluacion['escala_nota_tipo'],
                        'resultados' => $resultados
					))
					: null,
				'alumnos' => $this->modelo()->info__evaluacion_alumnos($this->params_comision),
			));
		} catch (error_guarani_comision_inexistente $e) {
			kernel::log()->add_error($e);
			$this->render_ajax($e->getMessage(), array('result' => -1));
		}
		catch (error_guarani_evaluacion_inexistente $e) {
			kernel::log()->add_error($e);
			$this->render_ajax($e->getMessage(), array('result' => -1));
		}
		catch (error_guarani $e) {
			kernel::log()->add_error($e);
			$this->render_ajax('No se pudo agregar alumno.', array('result' => 1));
		}
	}

	public function accion__agregar_comision()
	{
		$this->params_comision = $this->decodificar_param('evaluacion', 'post', 'editar_notas');
		try {
			$agregados = $this->modelo()->agregar_comision($this->params_comision);
			$evaluacion = $this->modelo()->info__evaluacion_cabecera($this->params_comision);
			$resultados = $this->modelo()->info__resultados_posibles_escala_nota($evaluacion['escala_nota']);
			$this->render_ajax('', array(
				'result' => 0,
				'agregados' => $agregados,
				'html' => $agregados
					? kernel::load_template('lista_alumnos.twig')->render(array(
						'alumnos' => $this->modelo()->info__evaluacion_detalle($this->params_comision),
						'escala_notas' => $this->modelo()->info__escala_notas($evaluacion['escala_nota']),
						'editar' => true,
                        'escala_continua' => \escalas_notas::tipo_continua,
                        'escala_discreta' => \escalas_notas::tipo_discreta,
                        'escala_notas_comision' => $evaluacion['escala_nota_tipo'],
                        'resultados' => $resultados
					))
					: null,
				'alumnos' => $this->modelo()->info__evaluacion_alumnos($this->params_comision),
			));
		} catch (error_guarani_comision_inexistente $e) {
			kernel::log()->add_error($e);
			$this->render_ajax($e->getMessage(), array('result' => -1));
		}
		catch (error_guarani_evaluacion_inexistente $e) {
			kernel::log()->add_error($e);
			$this->render_ajax($e->getMessage(), array('result' => -1));
		}
		catch (error_guarani $e) {
			kernel::log()->add_error($e);
			$this->render_ajax('No se pudo agregar alumnos.', array('result' => 1));
		}
	}

	public function accion__agregar_subcomision()
	{
		$this->params_comision = $this->decodificar_param('evaluacion', 'post', 'editar_notas');
		$sucomision = $this->validate_param('subcomision', 'post', validador::TIPO_INT);
		try {
			$agregados = $this->modelo()->agregar_subcomision($this->params_comision, $sucomision);
			$evaluacion = $this->modelo()->info__evaluacion_cabecera($this->params_comision);
			$resultados = $this->modelo()->info__resultados_posibles_escala_nota($evaluacion['escala_nota']);
			$this->render_ajax('', array(
				'result' => 0,
				'agregados' => $agregados,
				'html' => $agregados
					? kernel::load_template('lista_alumnos.twig')->render(array(
						'alumnos' => $this->modelo()->info__evaluacion_detalle($this->params_comision),
						'escala_notas' => $this->modelo()->info__escala_notas($evaluacion['escala_nota']),
						'editar' => true,
                        'escala_continua' => \escalas_notas::tipo_continua,
                        'escala_discreta' => \escalas_notas::tipo_discreta,
                        'escala_notas_comision' => $evaluacion['escala_nota_tipo'],
                        'resultados' => $resultados
					))
					: null,
				'alumnos' => $this->modelo()->info__evaluacion_alumnos($this->params_comision),
			));
		} catch (error_guarani_comision_inexistente $e) {
			kernel::log()->add_error($e);
			$this->render_ajax($e->getMessage(), array('result' => -1));
		}
		catch (error_guarani_evaluacion_inexistente $e) {
			kernel::log()->add_error($e);
			$this->render_ajax($e->getMessage(), array('result' => -1));
		}
		catch (error_guarani $e) {
			kernel::log()->add_error($e);
			$this->render_ajax('No se pudo agregar alumnos.', array('result' => 1));
		}
	}

	public function accion__agregar_resultado()
	{
		$this->params_comision = $this->decodificar_param('evaluacion', 'post', 'editar_notas');
		$params_origen = $this->decodificar_param('origen', 'post');

		try {
			$agregados = $this->modelo()->agregar_resultado(
				$this->params_comision, //kernel::request()->getParam('evaluacion'),
				kernel::request()->getParam('resultado'),
				$params_origen //kernel::request()->getParam('origen')
			);
			$evaluacion = $this->modelo()->info__evaluacion_cabecera($this->params_comision);
			$resultados = $this->modelo()->info__resultados_posibles_escala_nota($evaluacion['escala_nota']);
			$this->render_ajax('', array(
				'result' => 0,
				'agregados' => $agregados,
				'html' => $agregados
					? kernel::load_template('lista_alumnos.twig')->render(array(
						'alumnos' => $this->modelo()->info__evaluacion_detalle($this->params_comision),
						'escala_notas' => $this->modelo()->info__escala_notas($evaluacion['escala_nota']),
						'editar' => true,
                        'escala_continua' => \escalas_notas::tipo_continua,
                        'escala_discreta' => \escalas_notas::tipo_discreta,
                        'escala_notas_comision' => $evaluacion['escala_nota_tipo'],
                        'resultados' => $resultados
					))
					: null,
				'alumnos' => $this->modelo()->info__evaluacion_alumnos($this->params_comision),
			));
		} catch (error_guarani_comision_inexistente $e) {
			kernel::log()->add_error($e);
			$this->render_ajax($e->getMessage(), array('result' => -1));
		}
		catch (error_guarani_evaluacion_inexistente $e) {
			kernel::log()->add_error($e);
			$this->render_ajax($e->getMessage(), array('result' => -1));
		}
		catch (error_guarani $e) {
			kernel::log()->add_error($e);
			$this->render_ajax('No se pudo agregar alumnos.', array('result' => 1));
		}
	}

	public function accion__eliminar_alumno()
	{
		$this->params_comision = $this->decodificar_param('evaluacion', 'post', 'editar_notas');
		$alumno = $this->validate_param('alumno', 'post', validador::TIPO_INT);
		$ultimo_cambio = $this->validate_param('ultimo_cambio', 'post', validador::TIPO_TEXTO);
		try {
			$cabecera = $this->modelo()->info__evaluacion_cabecera($this->params_comision);
			$eliminado = $this->modelo()->eliminar_alumno($this->params_comision, $alumno, $ultimo_cambio);
			$resultados = $this->modelo()->info__resultados_posibles_escala_nota($cabecera['escala_nota']);
			if ($eliminado == 1)
				$this->render_ajax('El alumno ha sido eliminado de la evaluación.', array(
					'result' => 0,
					'html' => kernel::load_template('lista_alumnos.twig')->render(array(
						'alumnos' => $this->modelo()->info__evaluacion_detalle($this->params_comision),
						'escala_notas' => $this->modelo()->info__escala_notas($cabecera['escala_nota']),
						'editar' => true,
                        'escala_continua' => \escalas_notas::tipo_continua,
                        'escala_discreta' => \escalas_notas::tipo_discreta,
                        'escala_notas_comision' => $cabecera['escala_nota_tipo'],
                        'resultados' => $resultados
					)),
					'alumnos' => $this->modelo()->info__evaluacion_alumnos($this->params_comision),
				));

			$this->render_ajax('El alumno ya fué eliminado de la evaluación o sus datos fueron modificados por otro usuario.', array(
				'result' => 1,
				'html' => kernel::load_template('lista_alumnos.twig')->render(array(
					'alumnos' => $this->modelo()->info__evaluacion_detalle($this->params_comision),
					'escala_notas' => $this->modelo()->info__escala_notas($cabecera['escala_nota']),
					'editar' => true,
                    'escala_continua' => \escalas_notas::tipo_continua,
                    'escala_discreta' => \escalas_notas::tipo_discreta,
                    'escala_notas_comision' => $cabecera['escala_nota_tipo'],
                    'resultados' => $resultados
				)),
				'alumnos' => $this->modelo()->info__evaluacion_alumnos($this->params_comision),
			));
		} catch (error_guarani_comision_inexistente $e) {
			kernel::log()->add_error($e);
			$this->render_ajax($e->getMessage(), array('result' => -1));
		}
		catch (error_guarani_evaluacion_inexistente $e) {
			kernel::log()->add_error($e);
			$this->render_ajax($e->getMessage(), array('result' => -1));
		}
		catch (error_guarani $e) {
			kernel::log()->add_error($e);
			$cabecera = $this->modelo()->info__evaluacion_cabecera($this->params_comision);
			$resultados = $this->modelo()->info__resultados_posibles_escala_nota($cabecera['escala_nota']);
			$this->render_ajax('Ha ocurrido un error inesperado.', array(
				'result' => 1,
				'html' => kernel::load_template('lista_alumnos.twig')->render(array(
					'alumnos' => $this->modelo()->info__evaluacion_detalle($this->params_comision),
					'escala_notas' => $this->modelo()->info__escala_notas($cabecera['escala_nota']),
					'editar' => true,
                    'escala_continua' => \escalas_notas::tipo_continua,
                    'escala_discreta' => \escalas_notas::tipo_discreta,
                    'escala_notas_comision' => $cabecera['escala_nota_tipo'],
                    'resultados' => $resultados
				)),
			));
		}
	}

	public function accion__editar_alumnos()
	{
		$this->params_comision = $this->decodificar_param('evaluacion', 'post', 'editar_notas');
		$param_alumnos = $this->validate_param('alumnos', 'post', validador::TIPO_ARRAY_TEXTO);
		try {
			$fallidos = false;
			$mensajes = $this->modelo()->evt__procesar_evaluaciones($this->params_comision, $param_alumnos);
			$evaluacion = $this->modelo()->info__evaluacion_cabecera($this->params_comision);
			$alumnos = $this->modelo()->info__evaluacion_alumnos($this->params_comision);
			$resultados = $this->modelo()->info__resultados_posibles_escala_nota($evaluacion['escala_nota']);
			$mensajes_ajax = array();
			foreach ($mensajes as $id_alumno => $mensaje) {
				if (!$mensaje['result'])
					$fallidos = true;
				if (isset($alumnos[$id_alumno])) {
					$alumnos[$id_alumno]['class'] = $mensaje['result'] ? 'guardado' : 'error';
					$alumnos[$id_alumno]['mensaje'] = $mensaje['message'];
				} else
					$mensajes_ajax[] = $mensaje['message'];
			}
			array_unshift($mensajes_ajax, $fallidos ? 'Algunos alumnos no han podido actualizarse.' : '¡Las notas se guardaron con éxito!');
			$this->render_ajax(implode(' ', $mensajes_ajax), array(
				'result' => 0,
				'html' => kernel::load_template('lista_alumnos.twig')->render(array(
					'alumnos' => $alumnos,
					'escala_notas' => $this->modelo()->info__escala_notas($evaluacion['escala_nota']),
					'mensajes' => $mensajes,
					'editar' => true,
                    'escala_continua' => \escalas_notas::tipo_continua,
                    'escala_discreta' => \escalas_notas::tipo_discreta,
                    'escala_notas_comision' => $evaluacion['escala_nota_tipo'],
                    'resultados' => $resultados
				)),
				'fallidos' => $fallidos,
				'alumnos' => $this->modelo()->info__evaluacion_alumnos($this->params_comision),
			));
		} catch (error_guarani_comision_inexistente $e) {
			kernel::log()->add_error($e);
			$this->render_ajax($e->getMessage(), array('result' => -1));
		}
		catch (error_guarani_evaluacion_inexistente $e) {
			kernel::log()->add_error($e);
			$this->render_ajax($e->getMessage(), array('result' => -1));
		}
		catch (error_guarani $e) {
			kernel::log()->add_error($e);
			$this->render_ajax('Ha ocurrido un error inesperado.', array('result' => 1));
		}
	}

	/* -- Esto lo usaba el js de candotti-
	public function accion__existe_evaluacion()
	{
		try
		{
			$this->modelo()->info__evaluacion_existe(kernel::request()->getParam('evaluacion'));
			$this->render_ajax('', array('result' => 0));
		}
		catch(error_guarani_evaluacion_inexistente $e)
		{
			kernel::log()->add_error($e);
			$this->render_ajax($e->getMessage(), array('result' => -1));
		}
		catch(error_guarani $e)
		{
			kernel::log()->add_error($e);
			$this->render_ajax('Ha ocurrido un error inesperado.', array('result' => 1));
		}
	}*/

	function accion__listar_notas_pdf()
	{
		$this->params_comision = $this->decodificar_param(0, 'GET');
		$lista_notas = new pagelet_lista_notas('id_pl_notas');
		$lista_notas->set_controlador($this);
                list($path_archivo, $nombre_archivo) = $lista_notas->generar_pdf();
                $this->descargar_archivo($path_archivo, $nombre_archivo, true);
	}

	function accion__listar_notas_xls()
	{
		$this->params_comision = $this->decodificar_param(0, 'GET');
		$lista_notas = new pagelet_lista_notas('id_pl_notas');
		$lista_notas->set_controlador($this);
		$lista_notas->generar_xls();
	}

    function get_encabezado()
    {
        return $this->modelo()->info__evaluacion_cabecera($this->params_comision);
    }


	/**
	 * @return \siu\extension_kernel\formularios\guarani_form
	 */
	function get_form()
	{
		return $this->get_form_builder()->get_formulario();
	}

	/**
	 * @return \siu\extension_kernel\formularios\guarani_vista_form
	 */
	function get_vista_form()
	{
		$vista = $this->get_form_builder()->get_vista();
		$vista->get_salida()->set_es_minimizable(false);
		return $vista;
	}

	/**
	 * @return builder_nueva_evaluacion
	 */
	function get_form_builder()
	{
		if (!isset($this->form_builder)) {
			$this->form_builder = kernel::localizador()->instanciar('operaciones\evaluaciones\nueva_evaluacion\builder_nueva_evaluacion');
			$this->form_builder->set_modelo($this->modelo());
			$this->form_builder->set_comision_seleccionada($this->params_comision);
		}
		return $this->form_builder;
	}

	/**
	 * Valida que la fecha del formulario este dentro del rango de fechas de inicio y fin
	 * del periodo lectivo de la comisión en la que esta la evaluación,
	 * y que la hora de finalización sea menor a la hora de comienzo
	 *
	 * @param $datos
	 * @return array
	 */
	protected function validar_datos_form($datos)
	{
		$hora_fin = strtotime($datos['hora_fin']);
		$hora_inicio = strtotime($datos['hora_inicio']);

		$rta_datos_form = array('datos_validos' => false,
				'hora_fin_valida' => false,
				'fecha_valida' => false);

		if (! empty($datos['hora_fin']))
		{
			$rta_datos_form['hora_fin_valida'] = ($hora_fin > $hora_inicio);
		}
		if (! empty($datos['fecha']))
		{
			//Obtengo la fecha de inicio y fin del periodo lectivo de la comision a la que correspodne la evaluacion
            // y la formateo para hacer la comparacion con la fecha ingresada en el formulario de evaluacion
			$datos_comision = \toba::consulta_php('co_comisiones')->get_datos_periodo_comision($this->params_comision['comision']);
			$fecha_inicio_periodo = date('Y-m-d', strtotime($datos_comision['periodo_fecha_inicio']));
			$fecha_fin_periodo = date('Y-m-d', strtotime($datos_comision['periodo_fecha_fin']));

            //Formateo y comparo el rango de fechas de inicio y fin con la fecha del formulario de la evaluacion
			$fecha_com_sin_formato = \DateTime::createFromFormat('d/m/Y', $datos['fecha']);
			$fecha_comision = $fecha_com_sin_formato->format('Y-m-d');
			$rta_datos_form['fecha_valida'] = ($fecha_inicio_periodo <= $fecha_comision  && $fecha_fin_periodo >= $fecha_comision);
		}

		$rta_datos_form['datos_validos'] = ($rta_datos_form['hora_fin_valida'] && $rta_datos_form['fecha_valida']);
		return $rta_datos_form;
	}
	
	function tiene_derechos_cerrar_evaluacion()
	{
		$tiene_derecho = kernel::persona()->tiene_derecho_entidad('evaluacion_cerrar', 'comision', $this->params_comision['comision']);

		return $tiene_derecho;
	}

	function tiene_derechos_abrir_evaluacion()
	{
		$tiene_derecho = kernel::persona()->tiene_derecho_entidad('evaluacion_abrir', 'comision', $this->params_comision['comision']);

		return $tiene_derecho;
	}

	function accion__cerrar_evaluacion()
	{
		$this->params_comision = $this->decodificar_param(0, 'GET', '*');
		catalogo::limpiar_cache('carga_evaluaciones_parciales', 'evaluacion_detalle', $this->params_comision);
		$cabecera = $this->modelo()->info__evaluacion_cabecera($this->params_comision);

		if ($cabecera['estado'] == \siu\modelo\_comun\comunes::estado_abierto) {
			$datos = $this->modelo()->info__evaluacion_detalle($this->params_comision);
			foreach ($datos as $dato) {
				// Si el resultado es distinto a ausente y además no tiene nota, entonces no lo dejo cerrar.
				if (($dato['resultado'] != \escalas_notas::resultado_ausente) && is_null($dato['nota'])) {
					$error = 1;
					break;
				}
			}
			if (empty($error)) {

				$datos = array('evaluacion' => $this->params_comision['evaluacion'], 'estado' => comunes::estado_cerrado, 'fecha_cierre' => \guarani_fecha::get_hoy());
				$this->modelo()->evt__cambio_estado_evaluacion($datos);
				catalogo::limpiar_cache('carga_evaluaciones_parciales', 'evaluacion_detalle', $this->params_comision);
			} else {
				//mesaje alumno sin nota

				throw new \siu\errores\error_guarani(kernel::traductor()->trans('Existen alumnos a los que les falta completar datos y que no puede cerrarse'));
			}
		}
	}

	function accion__reabrir_evaluacion()
	{ /* para reabrir el estado de la evalucion debe estar cerrado y el acta de cursada debe estar abierta
	 */

		$this->params_comision = $this->decodificar_param(0, 'GET', '*');
		if (\toba::consulta_php('co_comisiones')->tiene_acta_cursada_promocion_cerrada($this->params_comision['comision']) == false) {
			$datos = array('evaluacion' => $this->params_comision['evaluacion'], 'estado' => comunes::estado_abierto, 'fecha_cierre' => NULL);
			$this->modelo()->evt__cambio_estado_evaluacion($datos);
			$mensajes = array();
			$mensajes[] = guarani::crear_mensaje(guarani::control_advertencia, kernel::traductor()->trans('La evaluación se abrió correctamente. Esta disponible para su edición'));
			$titulo = kernel::traductor()->trans('La evaluación se abrió correctamente. Esta disponible para su edición');
			kernel::renderer()->set_popup_notificaciones($titulo, $mensajes);
		} else {
			//mensaje tiene_acta_cursada_promocion_cerrada
			throw new \siu\errores\error_guarani('Error al reabrir la evaluación. La comisión tiene actas cerradas.');
		}
	}

}