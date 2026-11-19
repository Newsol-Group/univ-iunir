<?php
namespace siu\operaciones\resumen_cursada;

use SIU\Chulupi\kernel;
use siu\guarani;
use siu\extension_kernel\controlador_g3w2;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\operaciones\reporte\generador_pdf;
use siu\operaciones\_comun\operaciones\reporte\generador_excel;
use siu\modelo\entidades\alumno_foto;


class controlador extends controlador_g3w2 
{

    protected $datos_filtro = array('anio_academico' => "", 'periodo' => "");
    protected $pdf_hoja = "A4";
    protected $pdf_encabezado = array("", "");
    protected $pdf_encabezado_img = array("img" => "", "width" => "");
    protected $pdf_fuente_texto = 8;
    protected $pdf_fuente_titulo = 10;
    protected $pdf_fuente_subtitulo = 12;
    protected $tiene_encabezado = true;
    protected $encabezado_reporte = "";
    protected $datos_encabezado = "";

    /**
     * @return null
     */
    function modelo() {
        return null;
    }

    function get_clase_vista() {
        switch ($this->accion) {
            case 'comision':
                return 'vista_comision';
            default:
                return 'vista';
        }
    }

    function accion__index() {
        if (!empty($_GET['formulario_filtro'])) {
            $this->datos_filtro = $_GET['formulario_filtro'];
        }
    }
    
    function accion__comision() {
       
	        $comision_hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
	        $anio_academico_hash = $this->validate_param(1, 'get', validador::TIPO_ALPHANUM);
	        $periodo_hash = $this->validate_param(2, 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
	        $encabezado = $this->get_comision($anio_academico_hash, $periodo_hash, $comision_hash);

	        $parciales = array();
	        $tabla = array();
	        $resultados_acta_cursada = array();
	        $resultados_acta_promocion = array();
	        
	        if (!empty($encabezado['comision'])) {
	        
	            $parciales = $this->get_parciales($encabezado);
	            $parciales_alumnos = $this->get_parciales_alumnos($encabezado);
	            $alumnos_inscriptos_comision = $this->get_alumnos_de_parciales_insc_acta($encabezado);
	            $resultados_acta_cursada = $this->get_resultados_acta_cursada($encabezado);
	            $resultados_acta_promocion = $this->get_resultados_acta_promocion($encabezado);
				
				if(!empty($alumnos_inscriptos_comision))
				{
					$tabla = $this->generar_resumen_cursada($parciales, $parciales_alumnos, $alumnos_inscriptos_comision, $resultados_acta_cursada, $resultados_acta_promocion);					
				}

	            if(!empty($tabla)){
	                $url_pdf = kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'exportar_pdf', array($comision_hash, $anio_academico_hash, $encabezado['periodo_lectivo'],'T'));
	                $url_xls = kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'exportar_excel', array($comision_hash, $anio_academico_hash, $encabezado['periodo_lectivo'],'T'));
	                $this->vista()->pagelet("comision")->data['url_pdf'] = $url_pdf;
	                $this->vista()->pagelet("comision")->data['url_xls'] = $url_xls;
	            }
	            
	            //Logs de la info a mostrar en la tabla del resume de cursada
				 klog2('Evaluaciones de la comision:', $parciales);
				 klog2('Actas de regularidad de la comision:', $resultados_acta_cursada);
				 klog2('Actas de promoción de la comision:', $resultados_acta_promocion);
	             klog2('Alumnos, evaluaciones y actas:', $tabla);
	        }
	        
	        $this->vista()->pagelet("comision")->data['encabezado'] = $encabezado;
	        $this->vista()->pagelet("comision")->data['parciales'] = $parciales;
			$this->vista()->pagelet("comision")->data['tabla'] = $tabla;
	        $this->vista()->pagelet("comision")->data['tiene_actas'] = !empty($resultados_acta_cursada);
	        $this->vista()->pagelet("comision")->data['tiene_actas_prom'] = !empty($resultados_acta_promocion);
	        $this->vista()->pagelet("comision")->data['tiene_evaluaciones'] = !empty($parciales);
			
	        $this->vista()->pagelet("comision")->add_var_js('tiene_actas', !empty($resultados_acta_cursada));
	        $this->vista()->pagelet("comision")->add_var_js('tiene_actas_prom', !empty($resultados_acta_promocion));
	        $this->vista()->pagelet("comision")->add_var_js('tiene_evaluaciones', !empty($parciales));
	        $this->vista()->pagelet("comision")->add_var_js('tiene_datos', !empty($tabla));

			
    }

    protected function get_parciales($encabezado){
        return catalogo::consultar('comisiones', 'get_evaluaciones', array('comision' => $encabezado['comision'], 'visible_al_alumno' => "N"));
    }
    
    protected function get_parciales_alumnos($encabezado){
        return catalogo::consultar('comisiones', 'get_datos_evaluaciones_alumno', array('comision' => $encabezado['comision'], 'visible_al_alumno' => "N"));
    }
    
    protected function get_resultados_acta_cursada($encabezado){
        $alumnos_acta = catalogo::consultar('comisiones', 'get_resultados_acta_cursada', array('comision' => $encabezado['comision'], 'estado_acta' => 'T'));
		$salida = array();
		if (!empty($alumnos_acta)){
            foreach($alumnos_acta as $key => $detalle){
                $salida['filas'][$key]['legajo']    = $detalle['legajo'];
                $salida['filas'][$key]['alumno']    = $detalle['alumno'];
                $salida['filas'][$key]['cond_reg']  = $detalle['cond_regularidad_desc'];
                $salida['filas'][$key]['resultado'] = $detalle['resultado_descripcion'];  // kernel::traductor()->trans('actas.resultado.'.$detalle[27]);
                $salida['filas'][$key]['nota']      = $detalle['nota_descripcion'];
                $salida['filas'][$key]['estado']    = $detalle['estado_acta'];
                $salida['filas'][$key]['acta']      = $detalle['acta'];
            }

            return $salida;
        }
        return $salida;
    }
    
    
    protected function get_resultados_acta_promocion($encabezado){
        $alumnos_acta = catalogo::consultar('comisiones', 'get_resultados_acta_promocion', array('comision' => $encabezado['comision'], 'estado_acta' => 'T'));
		$salida = array();
		if (!empty($alumnos_acta)){
            foreach($alumnos_acta as $key => $detalle){
                $salida['filas'][$key]['legajo']    = $detalle['legajo'];
                $salida['filas'][$key]['alumno']    = $detalle['alumno'];
                //$salida['filas'][$key]['cond_reg']  = $detalle['cond_regularidad_desc'];
                $salida['filas'][$key]['resultado'] = $detalle['resultado_descripcion'];  // kernel::traductor()->trans('actas.resultado.'.$detalle[27]);
                $salida['filas'][$key]['nota']      = $detalle['nota_descripcion'];
                $salida['filas'][$key]['estado']    = $detalle['estado_acta'];
                $salida['filas'][$key]['acta']      = $detalle['acta'];
            }

            return $salida;
        }
        return $salida;
    }
    
    protected function get_alumnos_de_parciales_insc_acta($encabezado){
        return catalogo::consultar('comisiones', 'get_alumnos_de_parciales_insc_acta', array('comision' => $encabezado['comision']));
    }

    //Genera el Join entre evaluacion y las evaluaciones de los alumnos, de no haber eval para el alumno completa con campos vacios
    protected function generar_resumen_cursada($parciales, $parciales_alu, $alumnos, $acta_cursada, $acta_promocion) {
        $tabla = array();
		if(!empty($alumnos)){
			foreach ($alumnos as $key => $alumno) {
				$tabla[$alumno['alumno']]['alumno'] = $alumno['apellido_nombres_elegido'];
				$tabla[$alumno['alumno']]['documento_principal'] = $alumno['documento_principal'];
				$tabla[$alumno['alumno']]['nombre_abreviado']= $alumno['nombre_abreviado'];
				$tabla[$alumno['alumno']]['url_imagen'] = empty($alumno['id_imagen']) ? kernel::vinculador()->vinculo_recurso('img/iconos/mm_grande.png') : alumno_foto::url_imagen($alumno['id_imagen'], alumno_foto::TAMANIO_GRANDE);
			}
				// Carga los parciales si encuentra al alumno.
				foreach ($parciales_alu as $key => $parcial_alu) {
					if (array_key_exists($parcial_alu['alumno'], $tabla)) {
						$tabla[$parcial_alu['alumno']]['parciales'][$parcial_alu['fecha_evaluacion']]['nota'] = $parcial_alu['nota'];
					}
				}

				//Comienzo a procesar la tabla para completar las evaluaciones que estan vacias en cada alumno, esto evita romper la tabla al momento de mostrar las notas
				foreach ($tabla as $key_legajo => $parciales_alu) {

					//Diferencia entra comparacion de array con las evaluaciones de la comision vs las evaluaciones del alumno
					$array_column = array_map(function($element){return $element['fecha_evaluacion'];}, $parciales);
					$aux_diff = array_diff($array_column, (isset($parciales_alu['parciales']) ? array_keys($parciales_alu['parciales']) : array()));

					while (!empty($aux_diff)) {
						$i = array_shift($aux_diff);
						$tabla[$key_legajo]['parciales'][$i]['nota'] = '';
					}

					//Ordeno los parciales del alumno por fecha de parcial
					if(!empty($tabla[$key_legajo]['parciales']))
					{
						ksort($tabla[$key_legajo]['parciales'], SORT_NATURAL);				
					}

				}

			// Acta cursada
			if (!empty($acta_cursada)) {
				foreach ($acta_cursada['filas'] as $key => $acta) {
					$tabla[$acta['alumno']]['acta']['acta'] = $acta['acta'];					
					$tabla[$acta['alumno']]['acta']['cond_reg'] = $acta['cond_reg'];
					$tabla[$acta['alumno']]['acta']['nota'] = $acta['nota'];
					$tabla[$acta['alumno']]['acta']['resultado'] = $acta['resultado'];
					$tabla[$acta['alumno']]['acta']['estado'] = $acta['estado'];
				}

				// Agrego con campos vacios en los casos donde no se haya cargado ningun acta al alumno
				foreach ($tabla as $key_legajo => $alumno) {
					if (!isset($alumno['acta'])) {
						$tabla[$key_legajo]['acta']['acta'] = '';
						$tabla[$key_legajo]['acta']['cond_reg'] = '';
						$tabla[$key_legajo]['acta']['nota'] = '';
						$tabla[$key_legajo]['acta']['resultado'] = '';
					}
				}
			}	
			// Acta promocion
			if (!empty($acta_promocion)) {
				foreach ($acta_promocion['filas'] as $key => $acta) {
					//$tabla[$acta['alumno']]['acta_prom']['cond_reg'] = $acta['cond_reg'];
					$tabla[$acta['alumno']]['acta_prom']['acta'] = $acta['acta'];
					$tabla[$acta['alumno']]['acta_prom']['nota'] = $acta['nota'];
					$tabla[$acta['alumno']]['acta_prom']['resultado'] = $acta['resultado'];
					$tabla[$acta['alumno']]['acta_prom']['estado'] = $acta['estado'];
				}

				// Agrego con campos vacios en los casos donde no se haya cargado ningun acta al alumno
				foreach ($tabla as $key_legajo => $alumno) {
					if (!isset($alumno['acta_prom'])) {
						//$tabla[$key_legajo]['acta_prom']['cond_reg'] = '';
						$tabla[$key_legajo]['acta_prom']['acta'] = '';
						$tabla[$key_legajo]['acta_prom']['nota'] = '';
						$tabla[$key_legajo]['acta_prom']['resultado'] = '';
					}
				}
			}
		} 
        klog2('El resumen de la cursada procesada fue: ', $tabla);
        return $tabla;
    }

    function accion__exportar_pdf() {
        
        $comision_hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
        $anio_academico_hash = $this->validate_param(1, 'get', validador::TIPO_ALPHANUM);
        $periodo_hash = $this->validate_param(2, 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
        $filtro = $this->validate_param(3, 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));

        $encabezado = $this->get_comision($anio_academico_hash, $periodo_hash, $comision_hash);

        $parciales = array();
        $tabla = array();
        $resultados_acta_cursada = array();

        if (!empty($encabezado['comision'])) {
            $parciales = $this->get_parciales($encabezado);
            $parciales_alumnos = $this->get_parciales_alumnos($encabezado);
            $alumnos_inscriptos_comision = $this->get_alumnos_de_parciales_insc_acta($encabezado);
            $resultados_acta_cursada = $this->get_resultados_acta_cursada($encabezado);
            $resultados_acta_promocion = $this->get_resultados_acta_promocion($encabezado);
            $tabla = $this->generar_resumen_cursada($parciales, $parciales_alumnos, $alumnos_inscriptos_comision, $resultados_acta_cursada, $resultados_acta_promocion);
            $universidad = guarani::get_nombre_universidad() . "\n" ;

            $pdf = new generador_pdf($this->crear_ezpdf());
            $pdf->set_font($this->pdf_fuente);
            $pdf->set_text_options(array('left' => 5, 'top' => 20, 'maxWidth' => 800, 'width' => 800));
            $pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);
            $pdf->set_pdf_fuente_titulo($this->pdf_fuente_titulo);
            $pdf->set_pdf_fuente_subtitulo($this->pdf_fuente_subtitulo);
            $pdf->set_tiene_encabezado($this->tiene_encabezado);

            $datos_encabezado = array(
                'cant_columnas' => 2,
                'datos' => array(
                    0 => '<b>' . ucfirst(kernel::traductor()->trans("resumen_cursada.comision.encabezado.materia")) . ':</b> ' . $encabezado['actividad_nombre'],
                    1 => '<b>' . ucfirst(kernel::traductor()->trans("resumen_cursada.comision.encabezado.comision")) . ':</b> ' . $encabezado['comision_nombre'],
                    2 => '<b>' . ucfirst(kernel::traductor()->trans("resumen_cursada.comision.encabezado.anio_acad")) . ':</b> ' . $encabezado['anio_academico'],
                    3 => '<b>' . ucfirst(kernel::traductor()->trans("resumen_cursada.comision.encabezado.periodo")) . ':</b> ' . $encabezado['periodo_lectivo_nombre']
                )
            );

            $pdf->set_encabezado_tabla($datos_encabezado);

            $this->pdf_encabezado = array($universidad, "<u><b>" . kernel::traductor()->trans("tit_resumen_cursada") . "</b></u>", 110);
            $pdf->set_encabezado($this->pdf_encabezado[0], $this->pdf_encabezado[1], $this->pdf_encabezado[2]);

            $logo = kernel::localizador()->encontrar_img('www/img', 'logo_comprobante_con_bajada_nuevo.jpg');
            $pdf->set_encabezado_img($logo['path'], '103');
            
            $titulos_options = array();
            //if (!empty($parciales)) {
                list($titulos, $titulos_options) = $this->generar_titulos_reporte($parciales, $resultados_acta_cursada, $resultados_acta_promocion, true, $filtro);
                $data_tabla = $this->generar_tabla_reporte($tabla, $resultados_acta_cursada, $resultados_acta_promocion, $filtro);
            //}

            $pdf->set_options(array('showHeadings' => 1, 'protectRows' => 1, 'maxWidth' => 800, 'width' => 800, 'fontSize' => $this->pdf_fuente_texto, 'shadeCol' => array(0.8, 0.8, 0.8), 'cols' => $titulos_options));
			if(!empty($data_tabla))
			{
				$pdf->agregar_tabla($data_tabla, $titulos, '');				
			}else
			{
				$pdf->agregar_texto('No hay información para mostrar');
			}

            $archivo = $pdf->generar(false);
            $this->descargar_archivo($archivo, 'resultado_cursadas.pdf', true);
        }
    }

    function accion__exportar_excel() {
        
        $comision_hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
        $anio_academico_hash = $this->validate_param(1, 'get', validador::TIPO_ALPHANUM);
        $periodo_hash = $this->validate_param(2, 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
        $filtro = $this->validate_param(3, 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
        
        $encabezado = $this->get_comision($anio_academico_hash, $periodo_hash, $comision_hash);
        $parciales = $this->get_parciales($encabezado);
        $parciales_alumnos = $this->get_parciales_alumnos($encabezado);
        $alumnos_inscriptos_comision = $this->get_alumnos_de_parciales_insc_acta($encabezado);
        $resultados_acta_cursada = $this->get_resultados_acta_cursada($encabezado);
        $resultados_acta_promocion = $this->get_resultados_acta_promocion($encabezado);
        $tabla = $this->generar_resumen_cursada($parciales, $parciales_alumnos, $alumnos_inscriptos_comision, $resultados_acta_cursada, $resultados_acta_promocion);

        $xls = new generador_excel();

        $xls->agregar_texto_sin_enter(guarani::get_nombre_universidad()."\n", 1);

        $estilo_titulo = array(
            'font' => array('bold' => true),
        );
        $xls->agregar_texto_estilos(kernel::traductor()->trans("tit_resumen_cursada"), 1, $estilo_titulo);

        $xls->agregar_texto_sin_enter(ucfirst(kernel::traductor()->trans("resumen_cursada.comision.encabezado.materia")) . ': ' . $encabezado['actividad_nombre'], 1);
        $xls->agregar_texto_sin_enter(ucfirst(kernel::traductor()->trans("resumen_cursada.comision.encabezado.comision")) . ': ' . $encabezado['comision_nombre'], 1);
        $xls->agregar_texto_sin_enter(ucfirst(kernel::traductor()->trans("resumen_cursada.comision.encabezado.anio_acad")) . ': ' . $encabezado['anio_academico'], 1);
        $xls->agregar_texto_sin_enter(ucfirst(kernel::traductor()->trans("resumen_cursada.comision.encabezado.periodo")) . ': ' . $encabezado['periodo_lectivo_nombre'], 1);

        list($titulos, ) = $this->generar_titulos_reporte($parciales, $resultados_acta_cursada, $resultados_acta_promocion,false ,$filtro);
        $data_tabla = $this->generar_tabla_reporte($tabla, $resultados_acta_cursada, $resultados_acta_promocion, $filtro);

		if(!empty($data_tabla))
		{
            $xls->agregar_texto(' ', 1);
			$xls->agregar_tabla($data_tabla, $titulos);
		}else { 
	        $xls->agregar_texto(' ', 1);
	        $xls->agregar_texto('No hay información para mostrar', 1);
		}	
			
        $xls->set_nombre_archivo('Resumen Cursada');
        $xls->generar();
    }
    
    protected function generar_titulos_reporte($parciales, $resultados_acta_cursada, $resultados_acta_promocion,$tipo_pdf = false, $filtro){
        
		$titulos = array();
		$titulos_options = array();
		
//		if((!empty($parciales)&& ($filtro == 'T' || $filtro == 'E')) 
//				|| ((!empty($resultados_acta_cursada) || !empty($resultados_acta_promocion)) && ($filtro == 'T' || $filtro == 'A') ))

			$titulos = array('alumno' => strtoupper(kernel::traductor()->trans("resumen_cursada.comision.encabezado.alumno")));

//			if ($tipo_pdf && ($filtro == 'T' && (!empty($parciales) || !empty($resultados_acta_cursada) || !empty($resultados_acta_promocion)))){
//
//				$titulos_options['alumno'] = array('width'=>110);
//			}			
//		}
		 
		if(($filtro == 'T' || $filtro == 'E') && !empty($parciales))
		{
			foreach ($parciales as $parcial) {
				$titulos[$parcial['fecha_evaluacion']] = $parcial['evaluacion_nombre']."\n".$parcial['sfecha_evaluacion'];
				if ($tipo_pdf && $filtro == 'T'){
					$titulos_options[$parcial['evaluacion']] = array('width'=>80);
				}    
			}   
		}
		
		if(($filtro == 'T' || $filtro == 'A') && (!empty($resultados_acta_cursada) || !empty($resultados_acta_promocion))) 
		{
			if (!empty($resultados_acta_cursada)) {
				$titulos ['acta'] = strtoupper(kernel::traductor()->trans("resumen_cursada.comision.encabezado.acta_cursadas"));
				if ($tipo_pdf && ($filtro == 'T' && (!empty($parciales) || !empty($resultados_acta_promocion)) )){
					$titulos_options['acta'] = array('width'=>90);
				}   
			}
			if (!empty($resultados_acta_promocion)) {
				$titulos ['acta_prom'] = strtoupper(kernel::traductor()->trans("resumen_cursada.comision.encabezado.acta_promocion"));
				if ($tipo_pdf && ($filtro == 'T' && (!empty($parciales) || !empty($resultados_acta_cursada)))){
					$titulos_options['acta_prom'] = array('width'=>90);
				}   
			}			
		}
		
		$titulos['propuesta'] = strtoupper(kernel::traductor()->trans("nombre_propuesta"));
        klog2('Titulos de tabla reporte:', $titulos);
        return array($titulos, $titulos_options);
    }
    
    protected function generar_tabla_reporte($tabla, $resultados_acta_cursada, $resultados_acta_promocion, $filtro) {
        $data_tabla = array();
        foreach ($tabla as $alumno) {
          
//		if((!empty($alumno['parciales'])&& ($filtro == 'T' || $filtro == 'E')) 
//				|| ((!empty($resultados_acta_cursada) || !empty($resultados_acta_promocion)) && ($filtro == 'T' || $filtro == 'A') ))
//		{
            $data_alumno = array(
                'alumno' => $alumno['alumno']."\n".$alumno['documento_principal']             
					);			
//		}

		if(($filtro == 'T' || $filtro == 'E') && (!empty($alumno['parciales'])))
		{			
            foreach ($alumno['parciales'] as $key => $parciales){
                $data_alumno[$key] = $parciales['nota'];
            }

		}

		if(($filtro == 'T' || $filtro == 'A') && (!empty($resultados_acta_cursada) ||!empty($resultados_acta_promocion))) 
		{
            if (!empty($resultados_acta_cursada)) {
				$data_alumno['acta'] = null;
				if($alumno['acta']['acta']){
	                $data_alumno['acta'] = "Acta N° ".$alumno['acta']['acta'];
					if($alumno['acta']['cond_reg']){
						$data_alumno['acta']= $data_alumno['acta']."\n".$alumno['acta']['cond_reg'];
					}
					if($alumno['acta']['nota']){
						$data_alumno['acta']= $data_alumno['acta']."\n".$alumno['acta']['nota'];
					}
					if($alumno['acta']['resultado']){
						$data_alumno['acta']= $data_alumno['acta']."\n".$alumno['acta']['resultado'];
					}
				}
            }
            
            if (!empty($resultados_acta_promocion)) {
				$data_alumno['acta_prom']= null;
				if($alumno['acta_prom']['acta']){
	                $data_alumno['acta_prom'] = "Acta N° ".$alumno['acta_prom']['acta'];
					if($alumno['acta_prom']['cond_reg']){
						$data_alumno['acta_prom']= $data_alumno['acta_prom']."\n".$alumno['acta_prom']['cond_reg'];
					}
					if($alumno['acta_prom']['nota']){
						$data_alumno['acta_prom']= $data_alumno['acta_prom']."\n".$alumno['acta_prom']['nota'];
					}
					if($alumno['acta_prom']['resultado']){
						$data_alumno['acta_prom']= $data_alumno['acta_prom']."\n".$alumno['acta_prom']['resultado'];
					}					
				}
            }
		}	
		
			$data_alumno['propuesta'] = $alumno['nombre_abreviado'];		
			if(!empty($data_alumno))
				$data_tabla[] = $data_alumno;
        }
        klog2('Contenido tabla reporte:', $data_tabla);
        return $data_tabla;
    }

    protected function crear_ezpdf() {
        $instancia = new \Cezpdf($this->pdf_hoja, 'landscape');
        return $instancia;
    }

    function accion__buscar_periodos() {
		$anio_academico_hash = $this->validate_param('anio_academico', 'get', validador::TIPO_ALPHANUM);
		$anio_academico = $this->decodificar_anio_academico($anio_academico_hash);
		$datos = array();
		if (!is_null($anio_academico)){
			$datos = catalogo::consultar('unidad_academica', 'periodos_lectivos_un_anio_academico_docente', array('anio_academico' => $anio_academico,'docente' => kernel::persona()->get_id_docente()));
		}
		$this->render_raw_json($datos);
    }

    function get_comision($anio_academico_hash = null, $periodo_hash = null, $comision_hash = null) {
        $comision_actual = null;
        $comisiones = $this->get_comisiones($anio_academico_hash, $periodo_hash);
        foreach ($comisiones as $comision) {
            if ($comision['comision_id'] == $comision_hash) {
                $comision_actual = $comision;
            }
        }
        return $comision_actual;
    }

    function get_comisiones($anio_academico_hash, $periodo) {
        //$periodo = null;
        $anio_academico = null;
        $operacion = kernel::ruteador()->get_id_operacion();
		
        if (!empty($anio_academico_hash)) {
            $anio_academico = $this->decodificar_anio_academico($anio_academico_hash);
		}
		
		if (!empty($anio_academico)) {

			$parametros = array(
				'anio_academico' => $anio_academico,
				'docente' => kernel::persona()->get_id_docente(),
				'periodo_lectivo' => $periodo,
				'actividad' => null,
				'ubicacion' => null
			);
			$comisiones = catalogo::consultar('comisiones', 'lista_comisiones', $parametros);
			foreach ($comisiones as $key => $comision) {
				$comisiones[$key]['LINK'] = kernel::vinculador()->crear($operacion, 'comision', array($comision['comision_id'], $anio_academico_hash, $periodo));
			}
			return $comisiones;
		}
        
        return false;
    }

    function get_periodo() {
        return $this->datos_filtro['periodo'];
    }

    function get_anio_academico() {
        return $this->datos_filtro['anio_academico'];
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
        if (!isset($this->form_builder)) {
            $this->form_builder = kernel::localizador()->instanciar('operaciones\resumen_cursada\filtro\builder_form_filtro');
        }

        return $this->form_builder;
    }

    function decodificar_anio_academico($anio_hash) {
        $datos = catalogo::consultar('unidad_academica', 'anios_academicos');
        if (!empty($datos)) {
            foreach ($datos as $value) {
                if ($value['__ID__'] == $anio_hash) {
                    return $value['anio_academico'];
                }
            }
        }
        return false;
    }

}