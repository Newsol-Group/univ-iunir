<?php

namespace siu\operaciones\notas_cursada_comision;

use siu\guarani;
use siu\operaciones\_comun\controlador_carga_notas;
use SIU\Chulupi\kernel;
use siu\errores\error_guarani_procesar_renglones;
use SIU\Chulupi\util\validador;
use siu\modelo\entidades\alumno_foto;
use siu\errores\error_guarani_usuario;
use siu\errores\error_guarani_acta_cursada_cerrada;
use \siu\modelo\entidades\parametro;
use \siu\modelo\datos\catalogo;
use siu\operaciones\_comun\operaciones\reporte\generador_pdf;

class controlador extends controlador_carga_notas
{
	public $params_comision, $pagina, $hash_comision;
	
	protected $pdf_hoja = "A4";
	protected $pdf_encabezado = array("", "");
	protected $pdf_encabezado_img = array("img" => "", "width" => "");
	protected $pdf_fuente_texto = 10;
	protected $pdf_fuente_titulo = 15;
	protected $pdf_fuente_subtitulo = 10;
	protected $nombre_archivo = 'notas_de_cursada.pdf';

	protected function crear_ezpdf()
	{
		$instancia = new \Cezpdf($this->pdf_hoja, 'landscape');
		return $instancia;
	}

	function get_clase_vista()
	{
		if ($this->accion == 'index' || $this->accion == 'ver_mas') {
			return 'vista_comisiones';
		} else {
			return 'vista_edicion_notas';
		}
	}

	/**
	 * @return \siu\modelo\transacciones\carga_notas_cursada_comision
	 */
	function modelo()
	{
		return guarani::carga_notas_cursada_comision();
	}

	function get_info_zona()
	{
		if (isset($this->params_comision['comision'])) { 
			// Si hay selecci�n, no se activa la zona
			return array('zona' => 'comision', 'seleccion' => $this->params_comision);
		}
	}
	
	function get_id_comision()
	{
		return $this->params_comision['comision'];
	}

        /**
        * @return array Datos de la comisi�n, subcomisi�n y acta.
        */
        function get_params_comision()
	{
            return $this->params_comision;
	}

	function accion__index()
	{
		$periodo_lectivo_actual = $this->get_periodo_lectivo_actual();
		$this->vista()->pagelet('comisiones')->data['renglones'] = $this->get_comisiones(null, $periodo_lectivo_actual, null);
		$this->vista()->pagelet('comisiones')->data['periodo_lectivo'] = $periodo_lectivo_actual;
	}

	function accion__ver_mas()
	{
		$periodo_get = $this->validate_param('periodo_lectivo', 'get', validador::TIPO_INT);
		$periodos_lectivos = catalogo::consultar('unidad_academica', 'periodos_lectivos_anteriores', array('periodo_lectivo' => $periodo_get));

		$comisiones = array();
		$per_lec = null;
		foreach ($periodos_lectivos as $periodo_lectivo) {
			$comisiones = $this->get_comisiones(null, $periodo_lectivo['periodo_lectivo'], null);
			if (!empty($comisiones)) {
				$per_lec = $periodo_lectivo['periodo_lectivo'];
				break;
			}
		}
		$render = kernel::load_template('comisiones.twig')->render(array('renglones' => $comisiones, 'this' => $this->vista()->pagelet('comisiones')));
		$this->render_ajax($render, array('periodo' => $per_lec));
	}

	private function get_periodo_lectivo_actual()
	{
		$periodos = catalogo::consultar('unidad_academica', 'periodos_lectivos_todos');
		if (!empty($periodos)) {
			foreach ($periodos as $periodo) {
				if ($periodo['vigente'] == true) {
					return $periodo['periodo_lectivo'];
				}
			}
		}
		return false;
	}

	public function get_comisiones($anio_academico = null, $periodo_lectivo = null, $actividad = null, $propuesta = null)
	{
		$comisiones = $this->codificador_modelo()->info__comisiones_docente($anio_academico, $periodo_lectivo, $actividad);
		$comisiones->link('LINK', 'notas_cursada_comision', 'edicion');
		$columnas = array('comision', 'comision_nombre', 'subcomision_nombre', 'ubicacion_nombre', 'responsabilidad_nombre', 'turno_nombre', 'cant_inscriptos_comision', 'tipo_clase');
		$datos = array();
		foreach ($comisiones as $fila) {
			$periodo_key = $fila['anio_academico'] . ' - ' . $fila['periodo_lectivo_nombre'];
			$actividad_key = $fila['actividad_orden'];
			foreach ($columnas as $campo) {
				$nuevo[$campo] = isset($fila[$campo]) ? $fila[$campo] : null;
			}
			$nuevo['cant_inscriptos'] = $fila['es_subcomision'] ? $fila['cant_inscriptos_subcomision'] : $fila['cant_inscriptos_comision'];
			$nuevo['link'] = $fila['LINK'];
			$datos[$periodo_key][$actividad_key][] = $nuevo;
		}
		return $datos;
	}

	function accion__ficha_alumno()
	{
		$alumno_y_comision = $this->decodificar_param(0, 'get');
		$id_imagen = $this->validate_param('id_imagen', 'get', validador::TIPO_TEXTO);

		$clase = 'operaciones\_comun\pagelets\pagelet_ficha_alumno';
		$pagelet = kernel::localizador()->instanciar($clase, 'ficha_alumno');

		$data_ficha = $this->modelo()->info__parciales($alumno_y_comision);
		$pagelet->set_data($data_ficha);

		$url_imagen = empty($id_imagen) ? kernel::vinculador()->vinculo_recurso('img/iconos/mm_grande.png') : alumno_foto::url_imagen($id_imagen, alumno_foto::TAMANIO_GRANDE);
		$pagelet->set_url_imagen($url_imagen);
		kernel::renderer()->add($pagelet);
	}

	function accion__edicion()
	{
		$this->load_params();
		$renglon = $this->validate_param('renglon', 'get', validador::TIPO_INT, array('default' => ''));
		$this->vista()->pagelet('renglones')->set_highlight($renglon);
	}

	function accion__imprimir_notas()
	{
		$this->load_params();

		$renglones = $this->codificador_modelo()->info__alumnos_comision($this->params_comision);
		$renglones->link('URL_FICHA', 'notas_cursada_comision', 'ficha_alumno', [], ['id_imagen' => 'id_imagen']);
		$datos = $renglones->get_datos();

		$pdf = new generador_pdf($this->crear_ezpdf());
		$pdf->set_font($this->pdf_fuente);
		$pdf->set_text_options(array('left' => 5, 'top' => 20, 'maxWidth' => 800, 'width' => 500));
		$pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);
		$pdf->set_pdf_fuente_titulo($this->pdf_fuente_titulo);
		$pdf->set_pdf_fuente_subtitulo($this->pdf_fuente_subtitulo);
		$pdf->set_nombre_archivo($this->nombre_archivo);

		$encabezado = $this->get_encabezado();

		$this->pdf_encabezado = array("<b>" . kernel::traductor()->trans("notas_cursadas_comision_renglones.notas_cursada") . "</b>", "", 112);

		$logo = kernel::localizador()->encontrar_img('www/img', 'logo_comprobante_con_bajada_nuevo.jpg');
		$this->pdf_encabezado_img = array('img' => $logo['path'], 'width' => '103');

		$pdf->set_encabezado($this->pdf_encabezado[0], $this->pdf_encabezado[1], $this->pdf_encabezado[2]);
		$pdf->set_encabezado_img($this->pdf_encabezado_img["img"], $this->pdf_encabezado_img["width"]);

		$info_zona = $this->get_info_zona();
		$comision = $info_zona['seleccion'];

		$pdf->agregar_texto("<b>" . ucfirst(kernel::traductor()->trans('propuesta')) . ":</b> " . $this->get_nombre_propuesta($comision['comision']));

		$pdf->agregar_texto("<b>" . ucfirst(kernel::traductor()->trans('actividad')) . ":</b>  " . $comision['actividad_nombre'] . " (" . $comision['actividad_codigo'] . ")");
		$pdf->agregar_texto("<b>" . ucfirst(kernel::traductor()->trans('comision')) . ":</b> " . $comision['comision_nombre']);

		if ($comision['es_subcomision']) {
			$pdf->agregar_texto("<b>" . ucfirst(kernel::traductor()->trans('subcomision')) . ":</b> " . $comision['subcomision_nombre']);
			$pdf->agregar_texto("<b>" . ucfirst(kernel::traductor()->trans('nombre_tipo_clase')) . ":</b> " . $comision['tipo_clase']);
		} else {
			$pdf->agregar_texto("<b>" . ucfirst(kernel::traductor()->trans('subcomision')) . ":</b> " . ucfirst(kernel::traductor()->trans('no_tiene')));
		}

		$pdf->agregar_texto("<b>" . ucfirst(kernel::traductor()->trans('ubicacion')) . ":</b> " . $comision['ubicacion_nombre']);
		$pdf->agregar_texto("<b>" . ucfirst(kernel::traductor()->trans('periodo_lectivo')) . ":</b> " . $comision['periodo_lectivo_nombre']);
		$pdf->agregar_texto("<b>" . ucfirst(kernel::traductor()->trans('anio_academico')) . ":</b> " . $comision['anio_academico']);

		if ($comision['horario_comision'] || $comision['horario_subcomision']) {
			if ($comision['es_subcomision']) {
				$horario = $comision['horario_subcomision'];
			} else {
				$horario = $comision['horario_comision'];
			}
			$pdf->agregar_texto("<b>" . ucfirst(kernel::traductor()->trans('nombre_horario')) . ":</b> " . $horario);
		}

		if ($comision['turno_nombre']) {
			$pdf->agregar_texto("<b>" . ucfirst(kernel::traductor()->trans('turno')) . ":</b> " . $comision['turno_nombre']);
		}

		if ($comision['catedra_nombre']) {
			$pdf->agregar_texto("<b>" . ucfirst(kernel::traductor()->trans('catedra')) . ":</b> " . $comision['catedra_nombre']);
		}

		if ($comision['cant_inscriptos_comision']) {
			$pdf->agregar_texto("<b>" . ucfirst(kernel::traductor()->trans('inscriptos')) . ":</b> " . $comision['cant_inscriptos_comision']);
		}

		if ($comision['cant_inscriptos_subcomision']) {
			$pdf->agregar_texto("<b>" . ucfirst(kernel::traductor()->trans('inscriptos')) . ":</b> " . $comision['cant_inscriptos_subcomision']);
		}

		$columnas = array(
			'alumno' => kernel::traductor()->trans('notas_cursadas_comision_renglones.alumno'),
			'acta_cursada' => kernel::traductor()->trans('notas_cursadas_comision_renglones.acta_cursada'),
			'fecha_cursada' => kernel::traductor()->trans('notas_cursadas_comision_renglones.fecha_cursada'),
			'nota_cursada' => kernel::traductor()->trans('notas_cursadas_comision_renglones.nota_cursada'),
			'resultado_cursada' => kernel::traductor()->trans('notas_cursadas_comision_renglones.resultado_cursada'),
			'condicion' => kernel::traductor()->trans('notas_cursadas_comision_renglones.condicion'),
			'observacion_cursada' => kernel::traductor()->trans('notas_cursadas_comision_renglones.observaciones_cursada'),
			'acta_promocion' => kernel::traductor()->trans('notas_cursadas_comision_renglones.acta_promocion'),
			'fecha_promocion' => kernel::traductor()->trans('notas_cursadas_comision_renglones.fecha_promocion'),
			'nota_promocion' => kernel::traductor()->trans('notas_cursadas_comision_renglones.nota_promocion'),
			'resultado_promocion' => kernel::traductor()->trans('notas_cursadas_comision_renglones.resultado_promocion'),
			'observacion_promocion' => kernel::traductor()->trans('notas_cursadas_comision_renglones.observaciones_promocion'),
		);

		$precondiciones = kernel::localizador()->instanciar_params_array('operaciones\notas_cursada_comision\precondiciones_comision', array(
			$encabezado, $renglones
		));
		$condiciones = $this->modelo()->info__condiciones();

		if ($precondiciones->tiene_instancia_regular()) {
			$resultados_regularidad = $this->modelo()->info__resultados(\comision::inst_cursar, $precondiciones->get_escala_regular());
		}

		if ($precondiciones->tiene_instancia_promocion()) {
			$resultados_promocion = $this->modelo()->info__resultados(\comision::inst_promocionar, $precondiciones->get_escala_promocion());
		}

		$nuevos_datos = array();
		foreach ($datos as $dato) {
			// Si no se ingreso ning�n dato para el alumno NO se imprime
			if (!$dato['fecha_regular'] && !$dato['nota_cursada'] && !$dato['resultado_cursada'] && !$dato['cond_regularidad'] && !$dato['fecha_promocion'] && !$dato['nota_promocion'] && !$dato['resultado_promocion']) {
				continue;
			}

			$resultado_cursada = "-";
			foreach ($resultados_regularidad as $resultado_regularidad) {
				if ($resultado_regularidad['valor'] == $dato['resultado_cursada']) {
					$resultado_cursada = $resultado_regularidad['descr'];
					break;
				}
			}

			$resultado_promocion = "-";
			foreach ($resultados_promocion as $resultado_promocion_item) {
				if ($resultado_promocion_item['valor'] == $dato['resultado_promocion']) {
					$resultado_promocion = $resultado_promocion_item['descr'];
					break;
				}
			}

			$condicion = "-";
			foreach ($condiciones as $condicion_item) {
				if ($condicion_item['cond_regularidad'] == $dato['cond_regularidad']) {
					$condicion = $condicion_item['nombre'];
					break;
				}
			}

			$alumno = array();
			$alumno['alumno'] = $dato['apellido_nombres_elegido'] . "\n" . $dato['documento_principal'];
			$alumno['acta_cursada'] = ($dato['nro_acta_regularidad']) ? $dato['nro_acta_regularidad'] : kernel::traductor()->trans('sin_acta');
			$alumno['fecha_cursada'] = ($dato['fecha_regular']) ? $dato['fecha_regular'] : "-";
			$alumno['nota_cursada'] = isset($dato['nota_cursada']) ? $dato['nota_cursada'] : "-";
			$alumno['resultado_cursada'] = $resultado_cursada;
			$alumno['condicion'] = $condicion;
			$alumno['observacion_cursada'] = ($dato['observacion_cursada']) ? $dato['observacion_cursada'] : "-";
			$alumno['acta_promocion'] = ($dato['nro_acta_promocion']) ? $dato['nro_acta_promocion'] : kernel::traductor()->trans('sin_acta');
			$alumno['fecha_promocion'] = ($dato['fecha_promocion']) ? $dato['fecha_promocion'] : "-";
			$alumno['nota_promocion'] = isset($dato['nota_promocion']) ? $dato['nota_promocion'] : "-";
			$alumno['resultado_promocion'] = $resultado_promocion;
			$alumno['observacion_promocion'] = ($dato['observacion_promocion']) ? $dato['observacion_promocion'] : "-";

			$nuevos_datos[] = $alumno;
		}

		// Opciones de tabla
		$pdf->set_options(array('showHeadings' => 1, 'protectRows' => 1, 'maxWidth' => 500, 'width' => 800, 'fontSize' => $this->pdf_fuente_texto, 'shadeCol' => array(0.8, 0.8, 0.8), 'cols' => array(
				'alumno' => array('justification' => 'center', 'width' => 100),
				'acta_cursada' => array('justification' => 'center', 'width' => 60),
				'fecha_cursada' => array('justification' => 'center', 'width' => 80),
				'nota_cursada' => array('justification' => 'center', 'width' => 60),
				'resultado_cursada' => array('justification' => 'center', 'width' => 60),
				'condicion' => array('justification' => 'center', 'width' => 60),
				'observacion_cursada' => array('justification' => 'center', 'width' => 60),
				'acta_promocion' => array('justification' => 'center', 'width' => 60),
				'fecha_promocion' => array('justification' => 'center', 'width' => 80),
				'nota_promocion' => array('justification' => 'center', 'width' => 60),
				'resultado_promocion' => array('justification' => 'center', 'width' => 60),
				'observacion_promocion' => array('justification' => 'center', 'width' => 60),
		)));

		$pdf->dejar_espacio(-20);
		$pdf->agregar_tabla($nuevos_datos, $columnas, '');
		$archivo = $pdf->generar(false);
                $this->descargar_archivo($archivo, $this->nombre_archivo, true);
	}

	function accion__info_escala()
	{
		$escala_id = $this->validate_param(0, 'get', validador::TIPO_INT);
		$escala_notas = $this->modelo()->info__escala_notas($escala_id);
		$this->render_raw_json($escala_notas);
	}

	function accion__guardar()
	{
		try {
			$this->load_params();
			$this->guardar_pagina(kernel::request()->getPost('renglones'));
		} catch (error_guarani_acta_cursada_cerrada $e) {
			kernel::log()->add_error($e);
			$error = new error_guarani_usuario(kernel::traductor()->trans('acta_cerrada_comunicarse_oficina_alumnos', array('%1%' => kernel::vinculador()->crear('notas_cursada_comision'))));
			$error->set_codigo_response(-5);
			throw $error;
		} catch (error_guarani_procesar_renglones $e) {
			kernel::log()->add_debug('error al procesar renglones', $e);
			$this->vista()->pagelet('renglones')->set_error_guarani_procesar_renglones($e);
		}

		// Se indica que cuando llegue el pedido ajax a esta acci�n la respuesta
		// debe ser solamente el contenido del pagelet renglones, no toda la operac�n
		kernel::renderer()->add($this->vista()->pagelet('renglones'));
		$this->accion__edicion();
	}

	/**
	 * Autocompletado de alumno
	 */
	function accion__auto_alumno()
	{
		$this->load_params(); 
		$term = $this->getAutocompleteTerm();

		if (empty($term)) {
			$this->render_raw_json(array());
			return;
		}

		$raw_data = $this->modelo()->info__busqueda_alumno($this->params_comision, $term);
		$data = array();
		foreach ($raw_data as $alumno) {
			$data[] = array('id' => kernel::vinculador()->crear('notas_cursada_comision', 'edicion', array(0 => $this->hash_comision, 1 => $alumno['pagina'], 'renglon' => $alumno['renglon'])),'label' => $alumno['apellido_nombres']);
		}
		$this->render_raw_json($data);
	}

	//--------------------------------------------------------------------------
	//--- UTIL: guardado y validaci�n de renglones
	//--------------------------------------------------------------------------
	
	/**
	 * Busca en el get el acta y la p�gina
	 */
	protected function load_params()
	{
		$this->hash_comision = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$this->params_comision = $this->decodificar_hash($this->hash_comision, 'edicion');
		$this->pagina = $this->validate_param(1, 'get', validador::TIPO_INT, array('default' => 1));
	}

	protected function get_clave_sesion_pagina()
	{
		return "pagina[$this->hash_comision,$this->pagina]";
	}

	function get_encabezado()
	{
		return $this->modelo()->info__comision_cabecera($this->params_comision);
	}

	function get_pagina()
	{
		$renglones = $this->codificador_modelo()->info__alumnos_comision($this->params_comision, $this->pagina);
		// Se guardan en sesi�n las filas para despu�s actualizar, ver m�todo guardar_pagina.
		kernel::sesion()->set($this->get_clave_sesion_pagina(), $renglones->get_datos());
		$renglones->link('URL_FICHA', 'notas_cursada_comision', 'ficha_alumno', [], ['id_imagen' => 'id_imagen']);
		return $renglones->get_datos();
	}

	protected function vacio_a_null($valor)
	{
		return (trim($valor) == '') ? null : $valor;
	}

	protected function guardar_pagina($renglones_post)
	{
		$renglones_sesion = kernel::sesion()->get($this->get_clave_sesion_pagina());
		$renglones_modificados = array();

		foreach ($renglones_sesion as $id => $fila_s) {
			$renglon = $fila_s['renglon'];
			if (isset($renglones_post[$renglon])) {
				$hay_cambios_detalle_regularidad = $this->hay_cambios_detalle_reg($renglones_post[$renglon], $fila_s, ['nota_cursada', 'cond_regularidad', 'fecha_regular', 'resultado_cursada', 'observacion_cursada']);
				$hay_cambios_detalle_promocion = $this->hay_cambios_detalle_prom($renglones_post[$renglon], $fila_s, ['nota_promocion', 'fecha_promocion', 'resultado_promocion', 'observacion_promocion']);
				if ($hay_cambios_detalle_regularidad || $hay_cambios_detalle_promocion) {
					$renglones_modificados[$id] = $fila_s;
					$renglones_modificados[$id]['hay_cambios_regularidad'] = $hay_cambios_detalle_regularidad;
					$renglones_modificados[$id]['hay_cambios_promocion'] = $hay_cambios_detalle_promocion;

					if ($hay_cambios_detalle_regularidad) {
						$fecha = $this->validate_value($renglones_post[$renglon]['fecha_regular'], validador::TIPO_DATE, array('format' => 'd/m/Y', 'default' => null));
						$renglones_modificados[$id]['nota_cursada'] = $this->vacio_a_null($renglones_post[$renglon]['nota_cursada']);
						$renglones_modificados[$id]['cond_regularidad'] = $this->vacio_a_null($renglones_post[$renglon]['cond_regularidad']);
						$renglones_modificados[$id]['fecha_regular'] = $fecha;
						$renglones_modificados[$id]['resultado_cursada'] = $this->vacio_a_null($renglones_post[$renglon]['resultado_cursada']);
						$renglones_modificados[$id]['observacion_cursada'] = $this->vacio_a_null($renglones_post[$renglon]['observacion_cursada']);
					}

					if ($hay_cambios_detalle_promocion) {
						$fecha = $this->validate_value($renglones_post[$renglon]['fecha_promocion'], validador::TIPO_DATE, array('format' => 'd/m/Y', 'default' => null));
						$renglones_modificados[$id]['nota_promocion'] = $this->vacio_a_null($renglones_post[$renglon]['nota_promocion']);
						$renglones_modificados[$id]['fecha_promocion'] = $fecha;
						$renglones_modificados[$id]['resultado_promocion'] = $this->vacio_a_null($renglones_post[$renglon]['resultado_promocion']);
						$renglones_modificados[$id]['observacion_promocion'] = $this->vacio_a_null($renglones_post[$renglon]['observacion_promocion']);
					}
				}
			}
		}

		if ($renglones_modificados) {
			$this->modelo()->evt__procesar_pagina($this->params_comision, $renglones_modificados);
		}
	}

	function hay_cambios_detalle_reg($renglon_post, $renglon_sesion, $columnas)
	{
		if ($renglon_sesion['acta_cursada_cerrada'] == 1) { 
			// No puede haber cambios si el acta estaba cerrada
			return false;
		}

		$atributos = catalogo::consultar('atributos_params', 'get_propuestas_comision', array('comision' => $this->params_comision['comision']));
		$param_asentar = parametro::get_valor('cur_asentar_notas_actas_regulares', $atributos) == \comunes::si;

		foreach ($columnas as $columna) {
			if ($columna == 'nota_cursada' && $param_asentar) { 
				// No se tiene en cuenta el cambio en la nota
				continue;
			}
			if (!isset($renglon_post[$columna])) {
				continue;
			}
			if ($renglon_post[$columna] != $renglon_sesion[$columna]) {
				return true;
			}
		}
		return false;
	}

	function hay_cambios_detalle_prom($renglon_post, $renglon_sesion, $columnas)
	{
		if ($renglon_sesion['acta_promocion_cerrada'] == 1) { 
			// No puede haber cambios si el acta estaba cerrada
			return false;
		}

		$atributos = catalogo::consultar('atributos_params', 'get_propuestas_comision', array('comision' => $this->params_comision['comision']));
		$param_asentar = parametro::get_valor('cur_asentar_notas_actas_promocion', $atributos) == \acta::ingreso_notas_sin_notas;
		foreach ($columnas as $columna) {
			if ($columna == 'nota_promocion' && $param_asentar) { 
				// No se tiene en cuenta el cambio en la nota
				continue;
			}
			if (!isset($renglon_post[$columna])) {
				continue;
			}
			if ($renglon_post[$columna] != $renglon_sesion[$columna]) {
				return true;
			}
		}
		return false;
	}

	function get_nombre_propuesta($comision)
	{
		$datos = catalogo::consultar('carga_notas_cursada_comision', 'get_nombre_propuesta', array('comision' => $comision));
		if (!empty($datos)) {
			return $datos['nombre'];
		}
		return false;
	}

	function accion__calcular_notas()
	{
		try {
			
			$hubo_error = false;
			$msg_error = "";
			$hubo_error_regla_calculo = false;
			$msg_error_regla_calculo = "";
			$notas = [];
			
			$this->load_params();
			
			// Si la petici�n no es POST salgo
			if (!kernel::request()->isPost()) {
				throw new \Exception(kernel::traductor()->trans('notas_cursada_comision.peticion_post'));
			}
			
			$instancia = kernel::request()->getPost('instancia');
			$instancias_validas = [
				\comision::inst_cursar,
				\comision::inst_promocionar
			];
			// Si el campo "Instancia" es inv�lido
			if (!in_array($instancia, $instancias_validas)) {
				throw new \Exception(kernel::traductor()->trans('notas_cursada_comision.instancia_invalida'));
			}
			
			$regla_calculo = kernel::request()->getPost('regla_calculo');
			$reglas_calculo = catalogo::consultar('reglas_calculo', 'get_reglas_calculo', ['filtro' => null]);
			$reglas_calculo_ids = array_column($reglas_calculo, "regla_calculo");
			// Si el campo "regla de c�lculo" es inv�lido
			if (!in_array($regla_calculo, $reglas_calculo_ids)) {
				throw new \Exception(kernel::traductor()->trans('notas_cursada_comision.regla_calculo_invalido'));
			}
			
			$alumnos = [];
			$evaluacion = null;
			
			// Si es una petici�n por AJAX ("aplicar a" igual a "Todos los alumnos de la p�gina" o "Alumnos de la p�gina sin datos")
			if (kernel::request()->isXmlHttpRequest()) {
				
				$alumnos = kernel::request()->getPost('alumnos');
				// Si no hay alumnos para calcular notas
				if (empty($alumnos)) {
					throw new \Exception(kernel::traductor()->trans('notas_cursada_comision.sin_alumnos_calcular_nota'));
				}
			
			// Si no es una petici�n por AJAX ("aplicar a" igual a "Todos los alumnos" o "Alumnos sin datos")
			} else {
				$aplicar_a = kernel::request()->getPost('aplicar_a');
				$aplicar_a_validas = [
					\comision::autocompletar_para_todos,
					\comision::autocompletar_para_sin_datos,
				];
				// Si el campo "aplicar a" es inv�lido
				if (!in_array($aplicar_a, $aplicar_a_validas)) {
					throw new \Exception(kernel::traductor()->trans('notas_cursada_comision.aplicar_a_invalido'));
				}
				
				$renglones = $this->codificador_modelo()->info__alumnos_comision($this->params_comision);
				$renglones->link('URL_FICHA', 'notas_cursada_comision', 'ficha_alumno', [], ['id_imagen' => 'id_imagen']);
				$datos = $renglones->get_datos();
				
				// Decido cual es el campo de la nota y acta cerrada seg�n la instancia
				$campo_nota = ($instancia == \comision::inst_cursar)? 'nota_cursada' : 'nota_promocion';
				$campo_acta_cerrada = ($instancia == \comision::inst_cursar)? 'acta_cursada_cerrada' : 'acta_promocion_cerrada';
				foreach ($datos as $renglon) {
					
					// Si el acta esta cerrada no le modifico la nota al alumno
					if ($renglon[$campo_acta_cerrada]) {
						continue;
					}
					
					// Si "aplicar a" igual "Alumnos sin datos" y la nota es vac�a
					if (($aplicar_a == \comision::autocompletar_para_sin_datos) && empty($renglon[$campo_nota])) {
						$alumnos[] = $renglon['alumno'];
					// Si "aplicar a" igual "Todos los alumnos"
					} else if ($aplicar_a == \comision::autocompletar_para_todos) {
						$alumnos[] = $renglon['alumno'];
					}
				}
				
				// Obtengo la evaluaci�n
				$primer_renglon = reset($datos);
				$evaluacion = $primer_renglon['evaluacion'];
			}
			
			$comision = $this->params_comision['comision'];
			
			$php_clase_regla = \toba::consulta_php('co_reglas_calculo')->get_php_clase_regla($regla_calculo);
			$clase = new $php_clase_regla();
			
			foreach ($alumnos as $alumno) {
				$notas[$alumno] = $clase->get_promedio($alumno, $comision, $instancia);
			}
			
			// Si no es una petici�n por AJAX ("aplicar a" igual a "Todos los alumnos" o "Alumnos sin datos")
			if (!kernel::request()->isXmlHttpRequest()) {
				// Si hay notas las completo
				if (!empty($notas)) {
					// Veo si hay error en la regla de c�lculo (notas negativas)
					list($hubo_error_regla_calculo, $msg_error_regla_calculo) = $this->error_regla_calculo($notas);
					// Si no hubo error en la regla de c�lculo
					if (!$hubo_error_regla_calculo) {
						$hubo_error = $this->calcular_notas_cursada($evaluacion, $instancia, $notas);
					}
				}
			}
			
		} catch (\Exception $e) {
			$hubo_error = true;
			$msg_error = $e->getMessage();
			kernel::log()->add_error($e);
		}
		
		// Si es una petici�n por AJAX ("aplicar a" igual a "Todos los alumnos de la p�gina" o "Alumnos de la p�gina sin datos")
		if (kernel::request()->isXmlHttpRequest()) {
			
			$respuesta = [
				'hubo_error' => $hubo_error,
				'msg_error' => $msg_error,
				'notas' => $notas
			];
			$this->render_json($respuesta);
			
		// Si no es una petici�n por AJAX ("aplicar a" igual a "Todos los alumnos" o "Alumnos sin datos")
		} else {
			
			// Si hubo error al calcular notas
			if ($hubo_error) {
				kernel::sesion()->set('error_calcular_nota', kernel::traductor()->trans('notas_cursada_comision.error_calcular_nota'));
			// Si hubo error en la regla de c�lculo (notas negativas)
			} else if ($hubo_error_regla_calculo) {
				kernel::sesion()->set('error_regla_calculo', $msg_error_regla_calculo);
			// Si no hay alumnos para calcular nota
			} else if (empty($notas)) {
				kernel::sesion()->set('sin_alumnos_calcular_nota', kernel::traductor()->trans('notas_cursada_comision.sin_alumnos_calcular_nota'));
			// Si ocurri� un error al calcular notas
			} else {
				kernel::sesion()->set('exito_calcular_nota', kernel::traductor()->trans('notas_cursada_comision.exito_calcular_nota'));
			}

			// Redirijo a la pantalla de Edici�n
			$operacion = kernel::ruteador()->get_id_operacion();
			$url_edicion = kernel::vinculador()->crear($operacion, 'edicion', [$this->hash_comision, $this->pagina]);
			header("Location: $url_edicion");
			die();
		}
		
	}
	
	protected function error_regla_calculo($notas)
	{
		$hubo_error = false;
		$nro_error = 0;
		$msg_error = "";
		
		foreach ($notas as $alumno => $nota) {
			// Si la nota es nula o negativa hubo error
			if (is_null($nota) || $nota < 0) {
				$hubo_error = true;
				$nro_error = $nota;
				break;
			}
		}
		
		// Si hubo error
		if ($hubo_error) {
			// Decido el mensaje seg�n el n�mero de error
			switch ($nro_error) {
				case -1:
					$msg_error = kernel::traductor()->trans('notas_cursadas_comision.escalas_notas_deben_ser_numericas');
					break;
				case -2:
					$msg_error = kernel::traductor()->trans('notas_cursadas_comision.no_hay_evaluaciones_promediables');
					break;
				case -3:
					$msg_error = kernel::traductor()->trans('notas_cursadas_comision.escalas_notas_deben_ser_consistentes');
					break;
				case -4:
					$msg_error = kernel::traductor()->trans('notas_cursadas_comision.no_hay_evaluaciones_comision');
					break;
				default:
					$msg_error = kernel::traductor()->trans('notas_cursadas_comision.error_desconocido');
			}
			$msg_error_encabezado = kernel::traductor()->trans('notas_cursadas_comision.no_pueden_calcular_notas');
			$msg_error = "<div style='font-weight: bold; text-align: center;'>{$msg_error_encabezado}</div><br /><div>{$msg_error}</div>";
		}
		
		return [$hubo_error, $msg_error];
	}
	
	protected function calcular_notas_cursada($evaluacion, $instancia, $notas)
	{
		
		$alumnos_completar = [];
		
		// Si la instancia es "Regularidad"
		if ($instancia == \comision::inst_cursar) {
			$escala_nota = 'escala_nota_regularidad';
			$campo_nota = 'nota_cursada';
			$campo_resultado = 'resultado_cursada';
		} else {// Si la instancia es "Promoci�n"
			$escala_nota = 'escala_nota_promocion';
			$campo_nota = 'nota_promocion';
			$campo_resultado = 'resultado_promocion';
		}
		
		foreach ($notas as $alumno => $nota) {
			// Obtengo la nota
			$alumnos_completar[$alumno][$campo_nota] = $nota;
			// Obtengo resultado de la nota
			$encabezado = $this->get_encabezado();
			$validador = \escalas_notas::validador_escalas_notas();
			$resultado = $validador->get_resultado_escala_nota($encabezado[$escala_nota], $nota);
			$alumnos_completar[$alumno][$campo_resultado] = $resultado;
			
			// Solo si la instancia es "Regularidad"
			if ($instancia == \comision::inst_cursar) {
				// Obtengo condici�n de regularidad del resultado
				$cond_regularidad = \toba::consulta_php('co_condiciones_regularidad')->get_condicion_defecto_resultado($resultado);
				$alumnos_completar[$alumno]['cond_regularidad'] = $cond_regularidad;
			}
			
		}
		
		return $this->modelo()->evt__calcular_notas_cursada($evaluacion, $instancia, $alumnos_completar);
	}
	
	function accion__autocompletar()
	{
		
		try {
			
			$filas_afectadas = 0;
			
			$this->load_params();

			$encabezado = $this->get_encabezado();

			$tiene_instancia_regularidad = !empty($encabezado['instancia_regularidad']);
			$tiene_instancia_promocion = !empty($encabezado['instancia_promocion']);
			
			// Si la petici�n no es POST salgo
			if (!kernel::request()->isPost()) {
				throw new \Exception(kernel::traductor()->trans('notas_cursada_comision.peticion_post'));
			}
			
			$campo = kernel::request()->getPost('campo');
			$campos_validos = [
				\comision::autocompletar_campo_nota_cursada,
				\comision::autocompletar_campo_nota_promocion,
				\comision::autocompletar_campo_resultado_cursada,
				\comision::autocompletar_campo_resultado_promocion,
				\comision::autocompletar_campo_fecha,
				\comision::autocompletar_campo_observaciones,
			];
			// Si el campo "Completar" es inv�lido
			if (!in_array($campo, $campos_validos)) {
				throw new \Exception(kernel::traductor()->trans('notas_cursada_comision.completar_invalido'));
			}
			
			$valor = kernel::request()->getPost('valor');
			// Si el campo "con el valor" es vac�o
			if (empty($valor)) {
				throw new \Exception(kernel::traductor()->trans('notas_cursada_comision.valor_vacio'));
			}

			$para = kernel::request()->getPost('para');
			$para_validos = [
				\comision::autocompletar_para_sin_datos,
				\comision::autocompletar_para_sin_datos_pagina,
				\comision::autocompletar_para_todos,
				\comision::autocompletar_para_todos_pagina,
			];
			// Si el campo "para" es inv�lido
			if (!in_array($para, $para_validos)) {
				throw new \Exception(kernel::traductor()->trans('notas_cursada_comision.para_invalido'));
			}
			
			// Si el campo es "Resultado de Regularidad" o "Resultado de Promoci�n"
			if (in_array($campo, [\comision::autocompletar_campo_resultado_cursada, \comision::autocompletar_campo_resultado_promocion])) {
				$resultados = $this->modelo()->info__escalas_notas_resultados();
				$resultados_ids = array_column($resultados, "valor");
				// Si los campos "Resultado de Regularidad" o "Resultado de Promoci�n" son inv�lidos
				if (!in_array($valor, $resultados_ids)) {
					throw new \Exception(kernel::traductor()->trans('notas_cursada_comision.resultado_invalido'));
				}
			}

			// Si el campo es "Fecha"
			if ($campo === \comision::autocompletar_campo_fecha) {

				$fecha_inicio = \DateTime::createFromFormat('!d/m/Y', $encabezado['fecha_inicio_periodo_visual']);
				$fecha_fin = new \DateTime('today');
				$fecha_seleccionada = \DateTime::createFromFormat('!d/m/Y', $valor);
				// Si la fecha es menor a $fecha_inicio o mayor a $fecha_fin
				if (($fecha_seleccionada < $fecha_inicio) || ($fecha_seleccionada > $fecha_fin)) {
					throw new \Exception(kernel::traductor()->trans('fecha_invalida_acta', ['%1%' => $encabezado['fecha_inicio_periodo_visual'], '%2%' => $fecha_fin->format('d/m/Y')]));
				}
			}

			$columnas_completar = [];
			
			// Ver funci�n "ajax__completar_todos" de "ci_autocompletar_calcular_notas"
			switch ($campo) {
				// Si se trata de "Nota de Regularidad"
				case \comision::autocompletar_campo_nota_cursada:

					$validador = \escalas_notas::validador_escalas_notas();
					$resultado_cursada = $validador->get_resultado_escala_nota($encabezado['escala_nota_regularidad'], $valor);
					$cond_regularidad = \toba::consulta_php('co_condiciones_regularidad')->get_condicion_defecto_resultado($resultado_cursada);
					
					$columnas_completar['principales'] = [$campo => $valor];
					$columnas_completar['extras'][\comision::autocompletar_campo_resultado_cursada] = $resultado_cursada;
					$columnas_completar['extras'][\comision::autocompletar_campo_condicion] = $cond_regularidad;
					
					break;
				// Si se trata de "Nota de Promoci�n"
				case \comision::autocompletar_campo_nota_promocion:
					
					$validador = \escalas_notas::validador_escalas_notas();
					$resultado_promocion = $validador->get_resultado_escala_nota($encabezado['escala_nota_promocion'], $valor);
					
					$columnas_completar['principales'] = [$campo => $valor];
					$columnas_completar['extras'][\comision::autocompletar_campo_resultado_promocion] = $resultado_promocion;
					
					break;
				// Si se trata de "Resultado de Regularidad"
				case \comision::autocompletar_campo_resultado_cursada:
					
					$cond_regularidad = \toba::consulta_php('co_condiciones_regularidad')->get_condicion_defecto_resultado($valor);
					
					$columnas_completar['principales'] = [$campo => $valor];
					$columnas_completar['extras'][\comision::autocompletar_campo_condicion] = $cond_regularidad;
					$columnas_completar['extras'][\comision::autocompletar_campo_nota_cursada] = null;
					
					break;
				// Si se trata de "Resultado de Promoci�n"
				case \comision::autocompletar_campo_resultado_promocion:
					
					$columnas_completar['principales'] = [$campo => $valor];
					$columnas_completar['extras'][\comision::autocompletar_campo_nota_promocion] = null;
					
					break;
				// Si se trata de "Fecha"
				case \comision::autocompletar_campo_fecha:
					
					// Formateo la fecha para Postgres
					$fecha = \DateTime::createFromFormat('d/m/Y', $valor);
					$valor = $fecha->format('Y-m-d');
					
					// Si tiene la instancia de Regularidad 
					if ($tiene_instancia_regularidad) {
						$columnas_completar['principales'][\comision::autocompletar_campo_fecha_regular] = $valor;
					}
					
					// Si tiene la instancia de Promoci�n 
					if ($tiene_instancia_promocion) {
						$columnas_completar['principales'][\comision::autocompletar_campo_fecha_promocion] = $valor;
					}
					
					break;
				// Si se trata de "Observaciones"
				case \comision::autocompletar_campo_observaciones:
					
					// Si tiene la instancia de Regularidad 
					if ($tiene_instancia_regularidad) {
						$columnas_completar['principales'][\comision::autocompletar_campo_observacion_cursada] = $valor;
					}
					
					// Si tiene la instancia de Promoci�n 
					if ($tiene_instancia_promocion) {
						$columnas_completar['principales'][\comision::autocompletar_campo_observacion_promocion] = $valor;
					}
					
					break;
			}
			
			kernel::log()->add_debug("=== columnas_completar ===", $columnas_completar);
			
			$filas_afectadas = $this->autocompletar_notas_cursada($columnas_completar, $para);
			
		} catch (\Exception $e) {
			$filas_afectadas = -1;
			kernel::log()->add_error($e);
		}
		
		// Si hubo error
		if ($filas_afectadas == -1) {
			kernel::sesion()->set('error_autocompletar', kernel::traductor()->trans('notas_cursada_comision.error_autocompletar'));
		// Si hubo cambios
		} elseif ($filas_afectadas > 0) {
			kernel::sesion()->set('exito_autocompletar', kernel::traductor()->trans('notas_cursada_comision.exito_autocompletar'));
		// Si no hubo cambios
		} else {
			kernel::sesion()->set('no_cambios_autocompletar', kernel::traductor()->trans('notas_cursada_comision.no_cambios_autocompletar'));
		}

		// Redirijo a la pantalla de Edici�n
		$operacion = kernel::ruteador()->get_id_operacion();
		$url_edicion = kernel::vinculador()->crear($operacion, 'edicion', [$this->hash_comision, $this->pagina]);
		header("Location: $url_edicion");
		die();
	}
	
	protected function autocompletar_notas_cursada($columnas_completar, $para)
	{
		$renglones_sesion = kernel::sesion()->get($this->get_clave_sesion_pagina());
		// Obtengo los alumnos de la p�gina
		$alumnos = array_column($renglones_sesion, 'alumno');
		// Obtengo la evaluaci�n
		$primer_renglon = reset($renglones_sesion);
		$evaluacion = $primer_renglon['evaluacion'];
                
		return $this->modelo()->evt__autocompletar_notas_cursada(
                    $evaluacion,
                    $alumnos,
                    $columnas_completar,
                    $para,
                    $this->params_comision['subcomision'] ?? null,
                    $this->params_comision['id_acta'] ?? null,
                    $this->params_comision['origen'] ?? null
                );
	}
	
	public function get_instancias_calcular_notas()
	{
		$encabezado = $this->get_encabezado();
		$tiene_instancia_regularidad = !empty($encabezado['instancia_regularidad']);
		$tiene_instancia_promocion = !empty($encabezado['instancia_promocion']);
		
		$comision = $this->get_id_comision();
		$atributos = catalogo::consultar('atributos_params', 'get_propuestas_comision', ['comision' => $comision]);
		$cur_asentar_notas_actas_regulares = parametro::get_valor(\comision::cur_asentar_notas_actas_regulares, $atributos);
		$cur_asentar_notas_actas_promocion = parametro::get_valor(\comision::cur_asentar_notas_actas_promocion, $atributos);
		
		$instancias_comisiones = $this->modelo()->info__listado_instancias_comisiones();
		
		foreach ($instancias_comisiones as $key => $instancia) {
			
			// Si la instancia es "Regularidad", 
			// y la comisi�n no tiene dicha instancia o el par�metro 'cur_asentar_notas_actas_regulares' tiene el valor 'Sin Nota' la elimino de la lista
			if (($instancia['instancia'] == \comision::inst_cursar) &&
					(!$tiene_instancia_regularidad || $cur_asentar_notas_actas_regulares == \acta::ingreso_notas_sin_notas)
				) 
			{
				unset($instancias_comisiones[$key]);
			}
			
			// Si la instancia es "Promoci�n",
			//  y la comisi�n no tiene dicha instancia o el par�metro 'cur_asentar_notas_actas_promocion' tiene el valor 'Sin Nota' la elimino de la lista
			if (($instancia['instancia'] == \comision::inst_promocionar) && 
					(!$tiene_instancia_promocion || $cur_asentar_notas_actas_promocion == \acta::ingreso_notas_sin_notas)
				) 
			{
				unset($instancias_comisiones[$key]);
			}
		}
		
		return $instancias_comisiones;
	}
	
}