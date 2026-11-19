<?php
namespace siu\operaciones\historia_academica;

use SIU\Chulupi\kernel;
use siu\guarani;
use siu\extension_kernel\controlador_g3w2;
use SIU\Chulupi\util\validador;
use siu\modelo\transacciones\historia_academica;
use siu\operaciones\_comun\operaciones\reporte\generador_pdf;
use siu\operaciones\_comun\operaciones\reporte\generador_excel as generador_excel;
use SIU\Chulupi\ErrorKernelValidacion;

class controlador extends controlador_g3w2 {

	protected $pdf_hoja = "A4";
	protected $pdf_encabezado = array("", "");
	protected $pdf_encabezado_img = array("img" => "", "width" => "");
	protected $pdf_fuente_texto = 10;
	protected $pdf_fuente_titulo = 10;
	protected $pdf_fuente_subtitulo = 10;

	/**
	 *
	 * @return \siu\modelo\transacciones\historia_academica 
	 */
	function modelo() {
		return guarani::historia_academica();
	}

	function accion__index() 
	{
			
		try {
			
			$pagelet_menu_filtros = $this->vista()->pagelet('menu_filtros');
			$pagelet_menu_filtros->data['filtros'] = [];
			$pagelet_menu_filtros->data['estados'] = ['cursadas' => 'A', 'examenes' => 'A', 'reconocimientos' => 'A'];
			
			$pagelet_historia = $this->vista()->pagelet('info_historia');
			$pagelet_historia->data = ['materias' => []];
			$pagelet_historia->data['tiene_filtros'] = '';
			$pagelet_historia->data['orden'] = 'anio';
			$pagelet_historia->data['param_modo'] = '';
			
			// Chequeo los parámetros
			$modo = $this->validate_param('modo', 'get', validador::TIPO_ALPHA, array(
				'default' => 'anio'
			));

			$param_modo = $this->validate_param('param_modo', 'get', validador::TIPO_ALPHANUM, array(
				'default' => ''
			));

			$checks = $this->validate_param('checks', 'get', validador::TIPO_TEXTO, array(
				'default' => ''
			));
			
			$aChecks = [];
			if (!empty($checks)) {
				$aChecks = explode(",", substr($checks, 0, -1));
			}

			// Estados de los títulos del cada filtro
			$estados = [];
			$estados['cursadas'] = $this->validate_param('e_cu', 'get', validador::TIPO_ALPHA, array(
				'default' => 'A'
			));
			$estados['examenes'] = $this->validate_param('e_ex', 'get', validador::TIPO_ALPHA, array(
				'default' => 'A'
			));
			$estados['reconocimientos'] = $this->validate_param('e_re', 'get', validador::TIPO_ALPHA, array(
				'default' => 'A'
			));
			
			$datos = $this->datos_historia_academica($modo, $param_modo, $aChecks);

			$pagelet_menu_filtros->data['filtros'] = $aChecks;
			$pagelet_menu_filtros->data['estados'] = $estados;
			
			$pagelet_historia->data = $datos;
			$pagelet_historia->data['tiene_filtros'] = $checks;
			$pagelet_historia->data['orden'] = $modo;
			$pagelet_historia->data['param_modo'] = $param_modo;
		
		} catch (ErrorKernelValidacion $e) {
			$pagelet_historia->data['error'] = kernel::traductor()->trans('error_parametros');
		} catch (\Exception $e) {
			$pagelet_historia->data['error'] = kernel::traductor()->trans('error_indefinido');
		}
	}

	function datos_historia_academica($modo, $param_modo = "", $aChecks = array()) {
		$historia = array();
		$historia = $this->modelo()->get_actividad_academica_con_creditos($modo);
		$cant = \guarani_arreglos::count($historia);

		$aAnos = array();
		$aMaterias = array();
		$key = null;
		$historia_final = array();
		$datos = array();
		$datos_final = array();
		$j = 0;

		for ($i=0; $i < $cant; $i++){
			if(in_array(trim($historia[$i]['origen']).trim($historia[$i]['resultado']), $aChecks)){
				if (($key != $historia[$i]['materia'] && $modo == "materia") || ($key != $historia[$i]['anio'] && $modo == "anio")) {
					switch ($modo) {
						case 'materia':
							$aMaterias[$historia[$i]['materia']] = $historia[$i]['materia_nombre'];
							break;

						case 'anio':
							$aAnos[$historia[$i]['anio']] = $historia[$i]['anio'];
							break;
					}
				}
				
				if (empty($param_modo) || (!empty($param_modo) && ($modo == "anio" && $historia[$i]['anio'] == $param_modo)  || ($modo == "materia" && $historia[$i]['materia'] == $param_modo))){
					if (($key != $historia[$i]['materia'] && $modo == "materia") || ($key != $historia[$i]['anio'] && $modo == "anio")) {
						switch ($modo) {
							case 'materia':
								$key = $historia[$i]['materia'];
								break;

							case 'anio':
								$key = $historia[$i]['anio'];
								break;
						}
						$j = 0;
					}

					$historia_final[$key][$j] = $historia[$i];

					if (isset ($historia[$i]['fin_vigencia_regul'])) {
						$historia_final[$key][$j]['fin_vigencia_regul'] = $historia[$i]['fin_vigencia_regul'];
					}

					$historia_final[$key][$j]['id_detalle'] = $i;
					$historia_final[$key][$j]['origen'] = trim($historia_final[$key][$j]['origen']);
					$historia_final[$key][$j]['origen_class'] = str_replace(" ", "_", $historia_final[$key][$j]['origen']);

					$total_creditos = floatval($historia_final[$key][$j]['credito']);

					switch ($historia_final[$key][$j]['tipo']) {
						//Si son Créditos Globales
						case historia_academica::CREDITOS_GLOBALES:

							$historia_final[$key][$j]['titulo'] = $historia_final[$key][$j]['materia_nombre'];

							$historia_final[$key][$j]['leyenda'] = "<strong>".kernel::traductor()->trans('historia_academica.creditos_leyenda', array('%creditos%' => $total_creditos))."</strong>";
							$historia_final[$key][$j]['leyenda'] .= " ".$historia_final[$key][$j]['fecha_display'];
							$historia_final[$key][$j]['leyenda'] .= " - <a href=\"#\" class=\"detalle-credito\" data-nro-tramite=\"{$historia_final[$key][$j]['nro_tramite']}\" data-identificador=\"{$historia_final[$key][$j]['nro_tramite']}\">".kernel::traductor()->trans('historia_academica.detalle')."</a>";

							$historia_final[$key][$j]['detalle_ajax'] = "<div id=\"cred_{$historia_final[$key][$j]['nro_tramite']}\" class=\"toggle_info_credito\">";
							$historia_final[$key][$j]['detalle_ajax'] .= kernel::traductor()->trans('nro_resolucion').": {$historia_final[$key][$j]['acta_resolucion']}</br>";
							$historia_final[$key][$j]['detalle_ajax'] .= "<span class=\"documentacion_presentada\"></span>";
							$historia_final[$key][$j]['detalle_ajax'] .= "</div>";

							break;
						//Si son Créditos de Modulos
						case historia_academica::CREDITOS_MODULO:

							$historia_final[$key][$j]['titulo'] = $historia_final[$key][$j]['materia_nombre'];

							$historia_final[$key][$j]['leyenda'] = "<strong>".kernel::traductor()->trans('historia_academica.reconocimiento_leyenda', array('%reconocimiento%' => $historia_final[$key][$j]['tipo_reconocimiento']))."</strong>";
							$historia_final[$key][$j]['leyenda'] .= " -";
							if($total_creditos) $historia_final[$key][$j]['leyenda'] .= " ".kernel::traductor()->trans('historia_academica.creditos_leyenda', array('%creditos%' => $total_creditos));
							$historia_final[$key][$j]['leyenda'] .= " ".$historia_final[$key][$j]['fecha_display'];
							$historia_final[$key][$j]['leyenda'] .= " - <a href=\"#\" class=\"detalle-credito\" data-nro-tramite=\"{$historia_final[$key][$j]['nro_tramite']}\" data-identificador=\"{$historia_final[$key][$j]['nro_tramite']}_{$historia_final[$key][$j]['materia']}\">".kernel::traductor()->trans('historia_academica.detalle')."</a>";

							$historia_final[$key][$j]['detalle_ajax'] = "<div id=\"cred_{$historia_final[$key][$j]['nro_tramite']}_{$historia_final[$key][$j]['materia']}\" class=\"toggle_info_credito\">";
							$historia_final[$key][$j]['detalle_ajax'] .= kernel::traductor()->trans('nro_resolucion').": {$historia_final[$key][$j]['acta_resolucion']}</br>";
							$historia_final[$key][$j]['detalle_ajax'] .= "<span class=\"documentacion_presentada\"></span>";
							$historia_final[$key][$j]['detalle_ajax'] .= "</div>";

							break;
						//Si es otro item de la Historia académica
						default:

							$historia_final[$key][$j]['titulo'] = "{$historia_final[$key][$j]['materia_nombre']} ({$historia_final[$key][$j]['materia_codigo']})";

							$historia_final[$key][$j]['leyenda'] = "<span class=\"{$historia_final[$key][$j]['resultado_descripcion']}\">";
							$historia_final[$key][$j]['leyenda'] .= "<strong>".ucfirst(kernel::traductor()->trans($historia_final[$key][$j]['origen']))." ";

							switch ($historia_final[$key][$j]['equivalencia_instancia']){
								case \equivalencia::equiv_instancia_total:
									$historia_final[$key][$j]['leyenda'] .= ucfirst(kernel::traductor()->trans('total'));
									break;
								case \equivalencia::equiv_instancia_parcial:
									$historia_final[$key][$j]['leyenda'] .= ucfirst(kernel::traductor()->trans('parcial'));
									break;
								case \equivalencia::equiv_instancia_regularidad:
									$historia_final[$key][$j]['leyenda'] .= ucfirst(kernel::traductor()->trans('regularidad'));
									break;
							}

							$historia_final[$key][$j]['leyenda'] .= "</strong> - ";

							if($historia_final[$key][$j]['nota']){
								$historia_final[$key][$j]['leyenda'] .= "{$historia_final[$key][$j]['nota']} ({$historia_final[$key][$j]['nota_descripcion']}) ";
							}

							$historia_final[$key][$j]['leyenda'] .= $historia_final[$key][$j]['resultado_descripcion'];

							if($historia_final[$key][$j]['origen_class'] == "EnCurso"){
								$historia_final[$key][$j]['leyenda'] .= ucfirst(kernel::traductor()->trans('inicio_periodo_lectivo'));
							}

							if($historia_final[$key][$j]['origen'] == "AprobRes"){
								$historia_final[$key][$j]['leyenda'] .= " - ".kernel::traductor()->trans('historia_academica.creditos_leyenda', array('%creditos%' => $total_creditos))." - ";
							}

							$historia_final[$key][$j]['leyenda'] .= " {$historia_final[$key][$j]['fecha_display']} ";

							if($historia_final[$key][$j]['origen'] != "AprobRes"){
								$historia_final[$key][$j]['leyenda'] .= "- <a href=\"#\" id=\"{$historia_final[$key][$j]['id_detalle']}\" data-origen=\"{$historia_final[$key][$j]['origen']}\">Detalle</a>";
							}

							$historia_final[$key][$j]['detalle_ajax'] = "<div class='toggle_info_catedra' id=\"mat_{$historia_final[$key][$j]['id_detalle']}\" style=\"display:none;\">";
							if(isset($historia_final[$key][$j]['fin_vigencia_regul'])) $historia_final[$key][$j]['detalle_ajax'] .= ucfirst(kernel::traductor()->trans('vencimiento')).": {$historia_final[$key][$j]['fin_vigencia_regul']}";
							if(isset($historia_final[$key][$j]['origen_otra_carrera'])) $historia_final[$key][$j]['detalle_ajax'] .= "<i class=\"icon-info-sign\"></i> ".ucfirst(kernel::traductor()->trans('equivalente_de_otra_carrera'));
							$historia_final[$key][$j]['detalle_ajax'] .= "<div id=\"info_det_{$historia_final[$key][$j]['id_detalle']}\"><!-- Acá escribe el Ajax--></div>";
							$historia_final[$key][$j]['detalle_ajax'] .= "</div>";
					}

					$j++;
				}
			}
			
			switch (trim($historia[$i]['origen'])){
				case "Examen":
					$datos[$i]['acta'] = $historia[$i]['id_acta'];
				break;

				case "AprobRes":
					$datos[$i]['acta'] = $historia[$i]['id_acta'];
				break;

				case "Promocion":
					$datos[$i]['acta'] = $historia[$i]['id_acta'];
				break;

				case "Regularidad" :
					$datos[$i]['acta'] = $historia[$i]['id_acta'];
				break;

				case "EnCurso" :
					$datos[$i]['comision'] = $historia[$i]['comision'];
				break;

				case "Equivalencia" :
					$datos[$i]['equiv'] = $historia[$i]['equivalencia'];
				break;

				case "Equivalencia parc" :
					$datos[$i]['equiv'] = $historia[$i]['equivalencia'];
				break;
			}
		}
		kernel::sesion()->set('materias_a_seleccionar', $datos);
		kernel::log()->add_debug('materias_a_seleccionar', $datos);
		kernel::log()->add_debug('actividad_academica', $historia);

		$datos_final['historia'] = $historia_final;
		$datos_final['anos'] = $aAnos;
		$datos_final['materias'] = $aMaterias;
		return $datos_final;
	}

	function accion__detalle_examen() {
		$datos = kernel::sesion()->get('materias_a_seleccionar');
		$id = $this->validate_param('id', 'post', validador::TIPO_INT);
		$resultado = array();
		if (isset($datos[$id]['acta'])) {
			$detalle = $this->modelo()->get_detalle_materia_aprobada($datos[$id]['acta']);
			if (isset($detalle[0])) {
				$resultado['turno_examen'] = $detalle[0]['turno_examen_nombre'];
				$resultado['mesa_examen'] = $detalle[0]['mesa_examen_nombre'];
				$resultado['tipo_inscripcion'] = $detalle[0]['tipo_inscripcion'];
				$resultado['anio_academico'] = $detalle[0]['anio_academico'];
			}
		}
		$resultado['tipo'] = "detalle_examen";
		$this->render_template('detalle.twig', $resultado);
	}

	function accion__detalle_promocion() {
		$datos = kernel::sesion()->get('materias_a_seleccionar');
		$id = $this->validate_param('id', 'post', validador::TIPO_INT);
		$resultado = array();
		if (isset($datos[$id]['acta'])) {
			$detalle = $this->modelo()->get_detalle_materia_promocionada($datos[$id]['acta']);

			$resultado['encabezado']['per_lec'] = $detalle['comision']['periodo_lectivo'];
			if (isset($detalle['comision']['catedra']))
				$resultado['encabezado']['catedra'] = $detalle['comision']['catedra'];
			$resultado['evaluaciones'] = $detalle['evaluaciones'];
		}
		$resultado['tipo'] = 'detalle_promocion';
		$this->render_template('detalle.twig', $resultado);
	}

	function accion__detalle_cursada() {
		$datos = kernel::sesion()->get('materias_a_seleccionar');
		$id = $this->validate_param('id', 'post', validador::TIPO_INT);

		$resultado = array();
		if (isset($datos[$id]['acta'])) {
			$detalle = $this->modelo()->get_detalle_materia_cursada($datos[$id]['acta']);

			if (isset($detalle)) {
				$resultado['encabezado']['per_lec'] = $detalle['comision']['periodo_lectivo'];
				if (isset($detalle['comision']['catedra'])) {
					$resultado['encabezado']['catedra'] = $detalle['comision']['catedra'];
				}

				if (isset($detalle['evaluaciones'])) {
					$resultado['evaluaciones'] = $detalle['evaluaciones'];
				}
			}
			unset($detalle);
		}
		$resultado['tipo'] = "detalle_cursada";

		$this->render_template('detalle.twig', $resultado);
	}

	function accion__detalle_equivalencia() {
		$datos = kernel::sesion()->get('materias_a_seleccionar');
		$id = $this->validate_param('id', 'post', validador::TIPO_INT);
		$resultado = array();
		if (isset($datos[$id]['equiv'])) {
			$detalle = $this->modelo()->get_detalle_equivalencia($datos[$id]['equiv']);

			if (isset($detalle)) {
				$resultado['fecha_vencimiento'] = $detalle['vencimiento'];
				$resultado['nro_resolucion'] = $detalle['resolucion'];
				$resultado['temas_a_rendir'] = $detalle['temas_a_rendir'];
			}
		}
		$resultado['tipo'] = "detalle_equivalencia";
		$this->render_template('detalle.twig', $resultado);
	}

	function accion__detalle_encurso() {
		$datos = kernel::sesion()->get('materias_a_seleccionar');
		$id = $this->validate_param('id', 'post', validador::TIPO_INT);
		$resultado = array();
		if (isset($datos[$id]['comision'])) {
			$evaluaciones = $this->modelo()->get_detalle_materia_en_curso($datos[$id]['comision']);
			if (!empty($evaluaciones) && \guarani_arreglos::count($evaluaciones['evaluaciones']) > 0) {
				$resultado['evaluaciones'] = $evaluaciones['evaluaciones'];
			}
		}
		$resultado['tipo'] = "detalle_encurso";
		$this->render_template('detalle.twig', $resultado);
	}

	function accion__detalle_creditos() {
		$nro_tramite = $this->validate_param('nro_tramite', 'post', validador::TIPO_INT);

		$resultado['documentos'] = $this->modelo()->get_detalle_creditos($nro_tramite);

		$resultado['tipo'] = "detalle_creditos";
		$this->render_template('detalle.twig', $resultado);
	}

	/* Exportación */

	function accion__exportar_pdf() 
	{
		
		try {
			
			$pagelet_menu_filtros = $this->vista()->pagelet('menu_filtros');
			$pagelet_menu_filtros->data['filtros'] = [];
			$pagelet_menu_filtros->data['estados'] = ['cursadas' => 'A', 'examenes' => 'A', 'reconocimientos' => 'A'];
			
			$pagelet_historia = $this->vista()->pagelet('info_historia');
			$pagelet_historia->data = ['materias' => []];
			$pagelet_historia->data['tiene_filtros'] = '';
			$pagelet_historia->data['orden'] = 'anio';
			$pagelet_historia->data['param_modo'] = '';
			
			// Chequeo los parámetros	
			$modo = $this->validate_param('modo', 'get', validador::TIPO_ALPHA, array(
				'default' => 'anio'
			));

			$param_modo = $this->validate_param('param_modo', 'get', validador::TIPO_ALPHANUM, array(
				'default' => ''
			));

			$checks = $this->validate_param('checks', 'get', validador::TIPO_TEXTO, array(
				'default' => ''
			));
			
			$aChecks = [];
			if (!empty($checks)) {
				$aChecks = explode(",", substr($checks, 0, -1));
			}

			list($datos_ha, $datos_creditos) = $this->datos_exportar($modo, $param_modo, $aChecks);

			$pdf = new generador_pdf($this->crear_ezpdf());
			$pdf->set_font($this->pdf_fuente);
			$pdf->set_options(array('showHeadings' => 1, 'protectRows' => 1, 'maxWidth' => 500, 'width' => 800, 'fontSize' => $this->pdf_fuente_texto, 'shadeCol' => array(0.8, 0.8, 0.8), 'cols' => array()));
			$pdf->set_text_options(array('left' => 5, 'top' => 20, 'maxWidth' => 800, 'width' => 500));
			$pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);
			$pdf->set_pdf_fuente_titulo($this->pdf_fuente_titulo);
			$pdf->set_pdf_fuente_subtitulo($this->pdf_fuente_subtitulo);
			$pdf->set_nombre_archivo('historia_academica');

			$alumno = guarani::persona()->get_nombre();
			$datos_carrera = guarani::persona()->get_info_propuesta_activa();

			$nombre_carrera = "";
			if (isset($datos_carrera)) {
				$nombre_carrera = $datos_carrera['nombre'];
			}

			$this->pdf_encabezado = array('HISTORIA ACADÉMICA', 'Propuesta: ' . $nombre_carrera . PHP_EOL . 'Alumno: ' . $alumno, 112);
			$logo = kernel::localizador()->encontrar_img('www/img', 'logo_comprobante_con_bajada_nuevo.jpg');
			$this->pdf_encabezado_img = array('img' => $logo['path'], 'width' => '103');

			$pdf->set_encabezado($this->pdf_encabezado[0], $this->pdf_encabezado[1], $this->pdf_encabezado[2]);
			$pdf->set_encabezado_img($this->pdf_encabezado_img["img"], $this->pdf_encabezado_img["width"]);

			$titulos = $this->get_columnas($modo);
			$pdf->agregar_tabla($datos_ha, $titulos, '');

			if($datos_creditos){
				$titulos_creditos = $this->get_columnas_creditos($modo);
				$pdf->dejar_espacio(-50);
				$pdf->agregar_tabla($datos_creditos, $titulos_creditos, '');
			}

			$archivo = $pdf->generar(false);
                        $this->descargar_archivo($archivo, 'historia_academica.pdf', true);
			
		} catch (ErrorKernelValidacion $e) {
			$pagelet_historia->data['error'] = kernel::traductor()->trans('error_parametros');
		} catch (\Exception $e) {
			$pagelet_historia->data['error'] = kernel::traductor()->trans('error_indefinido');
		}
	}

	function accion__exportar_xls() 
	{
		
		try {
			
			$pagelet_menu_filtros = $this->vista()->pagelet('menu_filtros');
			$pagelet_menu_filtros->data['filtros'] = [];
			$pagelet_menu_filtros->data['estados'] = ['cursadas' => 'A', 'examenes' => 'A', 'reconocimientos' => 'A'];
			
			$pagelet_historia = $this->vista()->pagelet('info_historia');
			$pagelet_historia->data = ['materias' => []];
			$pagelet_historia->data['tiene_filtros'] = '';
			$pagelet_historia->data['orden'] = 'anio';
			$pagelet_historia->data['param_modo'] = '';
			
			$modo = $this->validate_param('modo', 'get', validador::TIPO_ALPHA, array(
				'default' => 'anio'
			));

			$param_modo = $this->validate_param('param_modo', 'get', validador::TIPO_ALPHANUM, array(
				'default' => ''
			));

			$checks = $this->validate_param('checks', 'get', validador::TIPO_TEXTO, array(
				'default' => ''
			));
			
			$aChecks = [];
			if (!empty($checks)) {
				$aChecks = explode(",", substr($checks, 0, -1));
			}

			list($datos_ha, $datos_creditos) = $this->datos_exportar($modo, $param_modo, $aChecks);
			$xls = new generador_excel();
			$titulos = $this->get_columnas($modo);

			$propuesta_activa = kernel::persona()->get_info_propuesta_activa();
			$xls->set_nombre_archivo('historia_academica');

			$xls->agregar_texto('Propuesta: '.$propuesta_activa['nombre'], 1);
			$xls->agregar_tabla($datos_ha, $titulos, $propuesta_activa['nombre']);

			if($datos_creditos){
				$titulos_creditos = $this->get_columnas_creditos($modo);
				$xls->agregar_texto("", 1);
				$xls->agregar_tabla($datos_creditos, $titulos_creditos, '');
			}

			$xls->generar();
		
		} catch (ErrorKernelValidacion $e) {
			$pagelet_historia->data['error'] = kernel::traductor()->trans('error_parametros');
		} catch (\Exception $e) {
			$pagelet_historia->data['error'] = kernel::traductor()->trans('error_indefinido');
		}
	}

	protected function crear_ezpdf() {
		$instancia = new \Cezpdf($this->pdf_hoja, 'landscape');
		return $instancia;
	}

	function get_columnas($modo = "anio") {
		switch ($modo) {
			case "materia":
				return array(
                    'actividad' => ucfirst(kernel::traductor()->trans('historia_academica.actividad')),
                    'fecha_display' => ucfirst(kernel::traductor()->trans('historia_academica.fecha')),
                    'origen' => ucfirst(kernel::traductor()->trans('historia_academica.tipo')),
                    'nota' => ucfirst(kernel::traductor()->trans('historia_academica.nota')),
                    'resultado_descripcion' => ucfirst(kernel::traductor()->trans('historia_academica.resultado'))
                );
				break;
			case "anio":
				return array(
                    'fecha_display' => ucfirst(kernel::traductor()->trans('historia_academica.fecha')),
                    'actividad' => ucfirst(kernel::traductor()->trans('historia_academica.actividad')),
                    'origen' => ucfirst(kernel::traductor()->trans('historia_academica.tipo')),
                    'nota' => ucfirst(kernel::traductor()->trans('historia_academica.nota')),
                    'resultado_descripcion' => ucfirst(kernel::traductor()->trans('historia_academica.resultado'))
                );
				break;
		}
	}

	function datos_exportar($modo, $param_modo = "", $aChecks = array()) {
		$historia = array();
		$historia = $this->modelo()->get_actividad_academica_con_creditos($modo);
		$cant = \guarani_arreglos::count($historia);
		$historia_final = array();
		$creditos_final = array();
		$j = 0;

		for ($i = 0; $i < $cant; $i++) {
			if (in_array(trim($historia[$i]['origen']).trim($historia[$i]['resultado']), $aChecks)) {
				if (empty($param_modo) || (!empty($param_modo) && ($modo == "anio" && $historia[$i]['anio'] == $param_modo) || ($modo == "materia" && $historia[$i]['materia'] == $param_modo))) {

					//Si son Créditos
					if(trim($historia[$i]['origen']) == historia_academica::CREDITOS){
						$creditos_final[$j] = $historia[$i];
						$creditos_final[$j]['credito_nombre'] = $creditos_final[$j]['materia_nombre_red'];
						$creditos_final[$j]['creditos'] = floatval($creditos_final[$j]['credito']);

						switch ($creditos_final[$j]['tipo']) {
							case historia_academica::CREDITOS_GLOBALES:
								$creditos_final[$j]['tipo_reconocimiento'] = "-------";
								break;
							case historia_academica::CREDITOS_MODULO:
								$creditos_final[$j]['tipo_reconocimiento'] = ucfirst($creditos_final[$j]['tipo_reconocimiento']);
								break;
						}
					}
					else{//sino
						$historia_final[$j] = $historia[$i];
						$historia_final[$j]['actividad'] = $historia_final[$j]['materia_nombre'].' ('.$historia_final[$j]['materia_codigo'].')';
						switch (trim($historia_final[$j]['origen'])) {
							case "EnCurso":
								$historia_final[$j]['origen'] = "En curso";
								break;
							default :
								$historia_final[$j]['origen'] = trim($historia_final[$j]['origen']);
						}
						$historia_final[$j]['origen_class'] = str_replace(" ", "_", $historia_final[$j]['origen']);
					}

					$j++;
				}
			}
		}
		unset($j, $i);
		return array($historia_final, $creditos_final);
	}

	function get_columnas_creditos($modo = "anio") {

		$columnas = array();
		switch ($modo) {
			case "materia":
				$columnas['credito_nombre'] = kernel::traductor()->trans('historia_academica.credito');
				$columnas['fecha_display'] = kernel::traductor()->trans('historia_academica.fecha');
				break;
			case "anio":
				$columnas['fecha_display'] = kernel::traductor()->trans('historia_academica.fecha');
				$columnas['credito_nombre'] = kernel::traductor()->trans('historia_academica.credito');
				break;
		}

		$columnas['tipo_reconocimiento'] = kernel::traductor()->trans('historia_academica.tipo_reconocimiento');
		$columnas['acta_resolucion'] = kernel::traductor()->trans('nro_resolucion');
		$columnas['creditos'] = kernel::traductor()->trans('historia_academica.creditos');

		return $columnas;
	}

}