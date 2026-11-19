<?php

namespace siu\operaciones\plan_estudio;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use siu\modelo\transacciones\plan_estudios;
use siu\operaciones\_comun\operaciones\reporte\generador_excel;
use siu\modelo\entidades\parametro;

class controlador extends controlador_g3w2
{
	protected $pdf_hoja = "A4";
	protected $pdf_encabezado = array("", "");
	protected $pdf_encabezado_img = array("img" => "", "width" => "");
	protected $pdf_fuente_texto = 10;
	protected $pdf_fuente_titulo = 10;
	protected $pdf_fuente_subtitulo = 12;
	protected $tiene_encabezado = true;
	protected $tiene_encabezado_long = true;
	protected $encabezado_reporte = "";
	protected $columnas_reporte;

	/**
	 * @return \siu\modelo\transacciones\plan_estudios
	 */
	function modelo()
	{
		return guarani::plan_estudios();
	}

	function accion__index()
	{

		$contenido_plan_version = $this->modelo()->get_contenido_plan_version_con_historia_academica();
		$info_propuesta_activa = guarani::persona()->get_info_propuesta_activa();
		$info_plan_activo = guarani::persona()->get_info_plan_activo();
		$info_plan_version_activo = guarani::persona()->get_info_plan_version_activo();

		$encabezado = array(
			'propuesta_codigo' => $info_propuesta_activa['codigo'],
			'propuesta_nombre' => $info_propuesta_activa['nombre'],
			'plan_codigo' => $info_plan_activo['codigo'],
			'plan_nombre' => $info_plan_activo['nombre'],
			'plan_version' => $info_plan_version_activo['version'],
		);

		$historia_academica = guarani::historia_academica();
		$certificados_obtenidos = $historia_academica->get_certificados_obtenidos();
		$this->vista()->pagelet('info_plan')->data['plan'] = $contenido_plan_version;
		$this->vista()->pagelet('info_plan')->data['encabezado'] = $encabezado;
		$this->vista()->pagelet('info_plan')->data['certificados_obtenidos'] = $certificados_obtenidos;
		$this->vista()->pagelet('info_plan')->data['subtipo_materia'] = \elemento::subtipo_materia;
		$this->vista()->pagelet('info_plan')->data['subtipo_materia_generica'] = \elemento::subtipo_materia_generica;
		$this->vista()->pagelet('info_plan')->data['subtipo_agrupador_modulos'] = \elemento::subtipo_agrupador_modulos;
		$this->vista()->pagelet('info_plan')->data['subtipo_agrupador_orientaciones'] = \elemento::subtipo_agrupador_orientaciones;
		$this->vista()->pagelet('info_plan')->data['subtipo_orientacion'] = \elemento::subtipo_orientacion;

		$this->vista()->pagelet('info_plan')->data['no_es_optativa_compartida'] = \elemento::no_es_optativa_compartida;
		$this->vista()->pagelet('info_plan')->data['es_optativa_compartida_asignada_a_esa_generica'] = \elemento::es_optativa_compartida_asignada_a_esa_generica;
		$this->vista()->pagelet('info_plan')->data['es_optativa_compartida_asignada_a_otra_generica'] = \elemento::es_optativa_compartida_asignada_a_otra_generica;
		$this->vista()->pagelet('info_plan')->data['es_optativa_compartida_sin_generica_asignada'] = \elemento::es_optativa_compartida_sin_generica_asignada;
	}

	function accion__correlativas()
	{
		$elemento = $this->validate_param('elemento', 'post', validador::TIPO_TEXTO);
		$elemento_padre = $this->validate_param('elemento_padre', 'post', validador::TIPO_TEXTO, array('allowempty' => true));
		$correlativas = $this->modelo()->get_correlativas($elemento);
		$resultado = array();
		foreach ($correlativas as $key => $correlativa) {
			if ($correlativa['tipo_correlativa'] == 1) {
				$resultado['correlativas']['cursar'][] = $correlativa;
			} elseif ($correlativa['tipo_correlativa'] == 2) {
				$resultado['correlativas']['aprobar'][] = $correlativa;
			}
		}
		$resultado['elemento'] = $elemento;
		$resultado['elemento_padre'] = $elemento_padre;
		$resultado['subtipos_entidades']['materia'] = \elemento::subtipo_materia;
		$resultado['subtipos_entidades']['materia_generica'] = \elemento::subtipo_materia_generica;
		$resultado['subtipos_entidades']['agrupador_modulos'] = \elemento::subtipo_agrupador_modulos;
		$this->render_template('correlativas.twig', $resultado);
	}

	function accion__verificar_correlativas()
	{

		if (kernel::request()->isPost()) {

			$hubo_error = false;
			$mensaje_error = "";

			try {

				$materia = $this->validate_param('materia', 'post', validador::TIPO_INT);
				$tipo_correlativa = $this->validate_param('tipo_correlativa', 'post', validador::TIPO_TEXTO);

				$mensaje_usuario = "";

				if ($tipo_correlativa == "cursar") {
					list($cumple_regla, $mensajes) = $this->modelo()->verificar_correlativas(plan_estudios::CORRELATIVA_CURSAR, $materia);
					$mensaje_usuario = kernel::traductor()->trans('plan_estudio.cumplis_correlativas_cursar_materia');
				} elseif ($tipo_correlativa == "aprobar") {
					list($cumple_regla, $mensajes) = $this->modelo()->verificar_correlativas(plan_estudios::CORRELATIVA_APROBAR, $materia);
					$mensaje_usuario = kernel::traductor()->trans('plan_estudio.cumplis_correlativas_rendir_materia');
				}

				if (!$cumple_regla) {
					foreach ($mensajes as $mensaje) {
						if ($mensaje['interfaz'] == 2) {
							$mensaje_usuario = $mensaje['mensaje'];
							break;
						}
					}
				}

				kernel::renderer()->add_to_ajax_response('cumple_regla', $cumple_regla);
				kernel::renderer()->add_to_ajax_response('mensaje_usuario', $mensaje_usuario);
			} catch (\Exception $e) {

				$hubo_error = true;
				$mensaje_error = $e->getMessage();
			}

			kernel::renderer()->add_to_ajax_response('hubo_error', $hubo_error);
			kernel::renderer()->add_to_ajax_response('mensaje_error', $mensaje_error);
		}
	}

	function accion__verificar_cumplimientos()
	{

		if (kernel::request()->isPost()) {

			$hubo_error = false;
			$mensaje_error = "";

			try {

				$materia = $this->validate_param('materia', 'post', validador::TIPO_INT);
				$regla = $this->validate_param('regla', 'post', validador::TIPO_TEXTO);
				$parametros = $this->validate_param('parametros', 'post', validador::TIPO_TEXTO);
		
				$mensaje_usuario = "";
					list($cumple_regla, $mensajes) = $this->modelo()->verificar_cumplimientos($regla,$materia ,$parametros );
					$mensaje_usuario = kernel::traductor()->trans('plan_estudio.cumplis_cumplimientos');
				
				if (!$cumple_regla) {
					foreach ($mensajes as $mensaje) {
						if ($mensaje['interfaz'] == 2) {
							$mensaje_usuario = $mensaje['mensaje'];
							break;
						}
					}
				}
				kernel::renderer()->add_to_ajax_response('cumple_regla', $cumple_regla);
				kernel::renderer()->add_to_ajax_response('mensaje_usuario', $mensaje_usuario);
			} catch (\Exception $e) {

				$hubo_error = true;
				$mensaje_error = $e->getMessage();
			}

			kernel::renderer()->add_to_ajax_response('hubo_error', $hubo_error);
			kernel::renderer()->add_to_ajax_response('mensaje_error', $mensaje_error);
		}
	}
	
	function accion__optativas()
	{
		if (kernel::request()->isPost()) 
		{
			try {
			
				$hubo_error = false;
				$mensaje = "";
				$datos = [];
				
				$elemento = $this->validate_param('elemento', 'post', validador::TIPO_INT);
				
				$datos['elemento'] = $elemento;
				$datos['entidad_subtipo'] = \elemento::subtipo_materia_generica;
				
				$datos['data']['subtipo_materia_generica'] = \elemento::subtipo_materia_generica;
				$datos['data']['es_optativa_compartida_asignada_a_esa_generica'] = \elemento::es_optativa_compartida_asignada_a_esa_generica;
				$datos['data']['es_optativa_compartida_asignada_a_otra_generica'] = \elemento::es_optativa_compartida_asignada_a_otra_generica;
				$datos['data']['es_optativa_compartida_sin_generica_asignada'] = \elemento::es_optativa_compartida_sin_generica_asignada;
				$datos['data']['url_reasignar_optativa'] = kernel::vinculador()->crear('reasignar_optativa');
		
				$datos['materias'] = $this->modelo()->get_optativas_generica_ajax($elemento);
				
			} catch (\Exception $e) {

				$hubo_error = true;
				$mensaje = kernel::traductor()->trans('plan_estudio.error_recuperar_optativas');
				kernel::log()->add_error($e);
			}
			
			kernel::renderer()->add_to_ajax_response('hubo_error', $hubo_error);
			kernel::renderer()->add_to_ajax_response('mensaje', $mensaje);
			
			if ($hubo_error) {
				$this->render_ajax_html();
			} else {
				$this->render_template('optativas.twig', $datos);
			}
			
		}
	}

	protected function crear_ezpdf()
	{
		$instancia = new \Cezpdf($this->pdf_hoja, 'landscape');
		return $instancia;
	}

	function accion__generar_pdf()
	{
                $alumno = guarani::persona()->get_id_alumno_activo();
		$info_plan_version_activo = guarani::persona()->get_info_plan_version_activo();
		$legajo = guarani::persona()->get_legajo_activo();
		//En el contructor imprimo el PDF no hace falta el get comprobante
                $pdf = new \guarani_ficha_plan_estudio_alumno_nucleo($alumno, $info_plan_version_activo['plan_version'], $legajo);
                $archivo = $pdf->generar_pdf();
                $this->descargar_archivo($archivo, 'plan_estudios.pdf', true);
        }

	function accion__generar_excel()
	{
		$xls = new generador_excel();

		$xls->set_nombre_archivo('plan_estudios');

		$institucion = guarani::get_nombre_universidad();
		$alumno = guarani::persona()->get_nombre();
		$legajo = guarani::persona()->get_legajo_activo();

		$info_propuesta_activa = guarani::persona()->get_info_propuesta_activa();
		$info_plan_activo = guarani::persona()->get_info_plan_activo();
		$info_plan_version_activo = guarani::persona()->get_info_plan_version_activo();

		$xls->agregar_texto($institucion, 1);

		$estilo_titulo = array(
			'font' => array('bold' => true),
		);

		$xls->agregar_texto_estilos(kernel::traductor()->trans('plan_estudio.encabezado_excel'), 1, $estilo_titulo);
		$xls->agregar_texto_sin_enter(kernel::traductor()->trans('plan_estudio.alumno') . ': ' . $alumno, 1);
		$xls->agregar_texto_sin_enter(kernel::traductor()->trans('plan_estudio.legajo') . ': ' . $legajo, 1);
		$xls->agregar_texto_sin_enter(kernel::traductor()->trans('plan_estudio.propuesta') . ': (' . $info_propuesta_activa['codigo'] . ') ' . $info_propuesta_activa['nombre'], 1);
		$xls->agregar_texto_sin_enter(kernel::traductor()->trans('plan_estudio.plan') . ': (' . $info_plan_activo['codigo'] . ') ' . $info_plan_activo['nombre'], 1);
		$xls->agregar_texto_sin_enter(kernel::traductor()->trans('plan_estudio.version') . ': ' . $info_plan_version_activo['version'], 1);

		// Obtengo el parámetro 'pro_ver_optativas_en_estructura_plan'
		$ver_optativas_en_estructura_plan = parametro::get_valor('pro_ver_optativas_en_estructura_plan');
		
		$tratar_generica_como_materia = false;
		// Si el parámetro 'pro_ver_optativas_en_estructura_plan' es 'No' trato las genéricas como materias comunes
		if ($ver_optativas_en_estructura_plan == \comunes::no) {
			$tratar_generica_como_materia = true;
		}
			
		$params = [
			'ver_optativas_en_estructura_plan' => $ver_optativas_en_estructura_plan,
			'tratar_generica_como_materia' => $tratar_generica_como_materia,
		];
		$contenido_plan_version = $this->modelo()->get_contenido_plan_version_con_historia_academica($params);

		if (isset($contenido_plan_version['modulos_genericas']) || isset($contenido_plan_version['materias'])) {

			if (isset($contenido_plan_version['modulos_genericas'])) {
				foreach ($contenido_plan_version['modulos_genericas'] as $modulo_generica) {
					$this->agregar_modulo_generica_excel($modulo_generica, $xls);
				}
			}

			if (isset($contenido_plan_version['materias'])) {
				$xls->agregar_texto("", 1);
				$xls->agregar_tabla($contenido_plan_version['materias'], $this->get_columnas_reporte(), "");
			}
			
		}

		$historia_academica = guarani::historia_academica();
		$certificados_obtenidos = $historia_academica->get_certificados_obtenidos();

		if ($certificados_obtenidos) {

			$xls->agregar_texto_sin_enter("", 1);
			$xls->agregar_texto_estilos(kernel::traductor()->trans('plan_estudio.promedios'), 1, $estilo_titulo);

			foreach ($certificados_obtenidos as $certificado) {

				$promedio_con_aplazos = number_format($certificado['promedio_con_aplazos'], 2);
				$promedio_sin_aplazos = number_format($certificado['promedio_sin_aplazos'], 2);

				$xls->agregar_texto_sin_enter($this->trans('plan_estudio.titulo') . ": " . $certificado['nombre'], 1);
				$xls->agregar_texto_sin_enter($this->trans('plan_estudio.promedio_con_aplazos') . ": " . $promedio_con_aplazos, 1);
				$xls->agregar_texto_sin_enter($this->trans('plan_estudio.promedio_sin_aplazos') . ": " . $promedio_sin_aplazos, 1);
				$xls->agregar_texto_sin_enter($this->trans('plan_estudio.estado_tramite') . ": " . $certificado['estado_actual_tramite'], 1);
				$xls->agregar_texto_sin_enter("", 1);
			};
		}

		// Si el parámetro 'pro_ver_optativas_en_estructura_plan' es 'Si' muestro la siguiente leyenda
		if ($ver_optativas_en_estructura_plan == \comunes::si) {
			$xls->agregar_texto_sin_enter("", 1);
			$xls->agregar_texto_sin_enter("(*): " . kernel::traductor()->trans('plan_estudio.optativa_compartida_y_asignada_esa_generica_leyenda'), 1);
			$xls->agregar_texto_sin_enter("(-): " . kernel::traductor()->trans('plan_estudio.optativa_compartida_y_asignada_otra_generica_leyenda'), 1);
			$xls->agregar_texto_sin_enter("(!): " . kernel::traductor()->trans('plan_estudio.optativa_compartida_y_sin_asignar_generica_leyenda'), 1);
		}

		$xls->generar();
	}

	protected function agregar_modulo_generica_excel($modulo_generica, &$xls, &$label = "")
	{

		$estilo_titulo = array(
			'font' => array('bold' => true),
		);

		//Si el modulo/generica no esta vacio (tiene submodulos o materias)
		if ((isset($modulo_generica['modulos_genericas']) && $modulo_generica['modulos_genericas']) || (isset($modulo_generica['materias']) && $modulo_generica['materias'])) {

			$label_bis = $label . "/ {$modulo_generica['nombre']} ";

			if ($modulo_generica['entidad_subtipo'] == \elemento::subtipo_agrupador_modulos) {
				$label_bis = strtoupper(kernel::traductor()->trans('plan_estudio.modulo')) . ": " . $label_bis;
			} elseif ($modulo_generica['entidad_subtipo'] == \elemento::subtipo_materia_generica) {
				$label_bis = strtoupper(kernel::traductor()->trans('plan_estudio.materia_generica_bis')) . ": " . $label_bis;
			} elseif ($modulo_generica['entidad_subtipo'] == \elemento::subtipo_orientacion) {
				$label_bis = strtoupper(kernel::traductor()->trans('plan_estudio.orientacion')) . ": " . $label_bis;
			}

			if (isset($modulo_generica['materias']) && $modulo_generica['materias']) {

				$materias = $modulo_generica['materias'];
				// Si es una optativa contenida en una genérica le agrego info de optativa compartida si corresponde
				if ($modulo_generica['entidad_subtipo'] == \elemento::subtipo_materia_generica) {
					$materias = array_map(array($this, 'agregar_info_optativa_compartida'), $materias);
				}

				$xls->agregar_texto("", 1);
				$xls->agregar_texto_estilos($label_bis, 1, $estilo_titulo);
				$xls->agregar_tabla($materias, $this->get_columnas_reporte(), "");
			}

			if (isset($modulo_generica['modulos_genericas']) && $modulo_generica['modulos_genericas']) {

				$label_bis = $label . "/ {$modulo_generica['nombre']} ";

				foreach ($modulo_generica['modulos_genericas'] as $modulo_generica_bis) {
					$this->agregar_modulo_generica_excel($modulo_generica_bis, $xls, $label_bis);
				}
			}
		}
	}
	
	protected function get_columnas_reporte()
	{
		if (!$this->columnas_reporte) {
			$this->columnas_reporte = array(
				'actividad' => $this->trans('plan_estudio.actividad'),
				'tipo' => $this->trans('plan_estudio.tipo_materia'),
				'anio' => $this->trans('plan_estudio.ano_cursada'),
				'periodo' => $this->trans('plan_estudio.periodo_dictado'),
				'nota' => $this->trans('plan_estudio.nota'),
				'origen' => $this->trans('plan_estudio.origen'),
				'credito' => $this->trans('plan_estudio.credito'),
				'puntaje' => $this->trans('plan_estudio.puntaje'),
			);
		}
		return $this->columnas_reporte;
	}
	
	function agregar_info_optativa_compartida($materia, $reporte = 'pdf')
	{
		$icono = '';
		switch ($materia['optativa_compartida']) {
			case \elemento::no_es_optativa_compartida:
				$icono = '';
				break;
			case \elemento::es_optativa_compartida_asignada_a_esa_generica:
				$icono = ' (*)';
				break;
			case \elemento::es_optativa_compartida_asignada_a_otra_generica:
				$icono = ' (-)';
				break;
			case \elemento::es_optativa_compartida_sin_generica_asignada:
				$icono = ' (!)';
				break;
		}
		$materia['actividad'] = $materia['actividad'] . $icono;
		return $materia;
	}

}
