<?php
namespace siu\operaciones\reporte_actas;

use \SIU\Chulupi\kernel;
use \siu\modelo\datos\catalogo;

class pagelet_promociones
	extends pagelet_acta
{
	function get_nombre()
	{
		return 'promociones';
	}

	function filtrar()
	{
		$this->data['actas'] = $this->datos_listado();
		return kernel::load_template('promociones/listado.twig')->render(array('this' => $this));
	}

	function prepare_listado()
	{
		$docente = array('docente' => kernel::persona()->get_id_docente());
		$this->data['per_lec'] = kernel::request()->getParam('per_lec');
		$this->data['periodos_lectivos'] = catalogo::consultar('unidad_academica', 'periodos_lectivos_todos', $docente);
		$this->data['actas'] = $this->datos_listado();
		$this->add_var_js('url_detalle', kernel::vinculador()->crear('reporte_actas', 'detalle'));
		$this->add_var_js('url_generar_pdf', kernel::vinculador()->crear('reporte_actas', 'generar_pdf_listado'));
		$this->add_var_js('url_generar_xls', kernel::vinculador()->crear('reporte_actas', 'generar_xls_listado'));
		$this->add_var_js('url_filtrar_actas', kernel::vinculador()->crear('reporte_actas', 'filtrar_actas'));
		$this->add_var_js('url_filtrar_actividades', kernel::vinculador()->crear('reporte_actas', 'filtrar_actividades'));
		$actividad = kernel::request()->getParam('actividad');
		if (!empty($actividad))
		{
			$actividad = $this->controlador->get_datos_actividad($actividad);
			$this->add_var_js('id_actividad', $actividad ? $actividad['elemento'] : '');
			$this->add_var_js('nombre_actividad', $actividad ? $actividad['nombre'].' ('.$actividad['codigo'].')' : '');
		}
		$this->add_var_js('acta', kernel::request()->getParam('acta'));
	}

	function prepare_detalle()
	{
		$this->data = $this->datos_acta();
		$parametros['origen'] = 'P';
		$parametros['actividad'] = kernel::request()->getParam('actividad');
		$parametros['per_lec'] = kernel::request()->getParam('per_lec');
		$parametros['acta'] = kernel::request()->getParam('acta');
		$this->data['url_volver'] = kernel::vinculador()->crear('reporte_actas', 'index', $parametros);
		$this->data['si'] = \comunes::si;
		$this->data['acta_estado_invalidado'] = \acta::detalle_estado_invalidado;
		unset($parametros['acta']);
		$parametros['url_acta'] = kernel::request()->getParam('url_acta');
		$this->add_var_js('url_generar_pdf', kernel::vinculador()->crear('reporte_actas', 'generar_pdf_detalle', $parametros));
		$this->add_var_js('url_generar_xls', kernel::vinculador()->crear('reporte_actas', 'generar_xls_detalle', $parametros));
		$this->add_var_js('ocultar_encabezado', ucfirst(kernel::traductor()->trans('reporte_actas.ocultar_encabezado')));
		$this->add_var_js('mostrar_encabezado', ucfirst(kernel::traductor()->trans('reporte_actas.mostrar_encabezado')));
	}

	protected function datos_listado()
	{
		$actividad = kernel::request()->getParam('actividad');
		$per_lec = kernel::request()->getParam('per_lec');

		$parametros = array();
		$parametros['docente'] = kernel::persona()->get_id_docente();
		$parametros['origen_acta'] = 'P';
		if (!empty($actividad))
		{
			$datos_actividad = $this->controlador->get_datos_actividad($actividad);
			$parametros['actividad_codigo'] = $datos_actividad['codigo'];
		}
		else
			$parametros['actividad_codigo'] = null;
		if (!empty($per_lec))
		{
			$datos_periodo = $this->controlador->get_datos_periodo($per_lec);
			$parametros['anio_academico'] = $datos_periodo['anio_academico'];
			$parametros['periodo_lectivo'] = $datos_periodo['periodo_lectivo'];
		}
		else
		{
			$parametros['anio_academico'] = null;
			$parametros['periodo_lectivo'] = null;
		}
		$actas = catalogo::consultar('docente', 'lista_actas_cursadas_promociones', $parametros);
		foreach($actas as $key => $acta)
		{
			$actas[$key]['comision_nombre'] = $acta['comision_nombre'];
			if (!empty($acta['fecha_cierre']))
				$actas[$key]['estado_acta'] = $acta['estado_acta'].' ('.$acta['fecha_cierre'].')';
		}
		return $actas;
	}

	protected function get_nombre_archivo_listado()
	{
		return 'Listado de acta de promociones';
	}

	protected function get_columnas_impresion_listado()
	{
		return array(
			'id_acta' => ucfirst(kernel::traductor()->trans('reporte_actas.acta')),
			'anio_academico' => ucfirst(kernel::traductor()->trans('reporte_actas.anio_academico')),
			'periodo_lectivo_nombre' => ucfirst(kernel::traductor()->trans('reporte_actas.periodo_lectivo')),
			'actividad_nombre_completo' => ucfirst(kernel::traductor()->trans('reporte_actas.actividad')),
			'comision_nombre' => ucfirst(kernel::traductor()->trans('reporte_actas.comision')),
			'ubicacion_nombre' => ucfirst(kernel::traductor()->trans('reporte_actas.sede')),
			'estado_acta' => ucfirst(kernel::traductor()->trans('reporte_actas.estado')),
		);
	}

	protected function datos_acta()
	{
		$url_acta = kernel::request()->getParam('url_acta');
		foreach($this->datos_listado() as $dato)
		{
			if ($dato['url_acta'] == $url_acta)
			{
				$resultado = array();
				$alumnos = catalogo::consultar('docente', 'detalle_acta_cursada_promocion', array('id_acta' => $dato['id_acta']));
				$encabezado = catalogo::consultar('docente', 'get_datos_cabecera_acta', array('id_acta' => $dato['id_acta'], 'origen' => $dato['origen']));
				if (!empty($encabezado['fecha_cierre']))
					$encabezado['estado_acta'] = $encabezado['estado_acta'].' ('.$encabezado['fecha_cierre'].')';
				$resultado['datos'] = $dato;
				$resultado['encabezado'] = $encabezado;

				// Corte de alumnos por folio
				$corte_alumno = array();
				foreach ($alumnos as $alumno)
				{
					$alumno['nota_desc'] = !empty($alumno['nota_desc']) ? $alumno['nota'].' ('.$alumno['nota_desc'].')' : null;
					$alumno['nombre_completo'] = $alumno['apellido'].", ".$alumno['nombres'];
					//$alumno['nota_completa'] = $alumno['nota']." (".$alumno['nota_desc'].")";
					if (isset($corte_alumno[$alumno['folio']]))
						$corte_alumno[$alumno['folio']][\guarani_arreglos::count($corte_alumno[$alumno['folio']])] = $alumno;
					else
						$corte_alumno[$alumno['folio']][0] = $alumno;
				}
				$resultado['alumnos'] = $corte_alumno;
				$resultado['encabezado']['folios'] = $alumno['folio'];
				return $resultado;
			}
		}
		return null;
	}

	protected function get_encabezado_detalle($nro_acta)
	{
		return ucfirst(kernel::traductor()->trans('reporte_actas.acta')).': '.$nro_acta;
	}

	protected function get_nombre_archivo_detalle()
	{
		return 'Detalle de acta de promoción';
	}

	protected function get_columnas_impresion_detalle()
	{
		return array(
			'legajo' => ucfirst(kernel::traductor()->trans('reporte_actas.legajo')),
			'nombre_completo' => ucfirst(kernel::traductor()->trans('reporte_actas.alumno')),
			'fecha' => ucfirst(kernel::traductor()->trans('reporte_actas.fecha')),
			//'nota_completa' => ucfirst(kernel::traductor()->trans('reporte_actas.nota')),
			'nota_desc' => ucfirst(kernel::traductor()->trans('reporte_actas.nota')),
			'concepto_desc' => ucfirst(kernel::traductor()->trans('reporte_actas.condicion')),
			'resultado_desc' => ucfirst(kernel::traductor()->trans('reporte_actas.resultado')),
		);
	}

    protected function prepare_encabezado_pdf($pdf, $datos_acta){
        $pdf->agregar_texto(ucfirst(kernel::traductor()->trans('reporte_actas.nro_acta')).": ".$datos_acta['encabezado']['nro_acta']);

        if($datos_acta['encabezado']['tipo_acta'] == "Rectificativa"){
            $pdf->agregar_texto(ucfirst(kernel::traductor()->trans('reporte_actas.tipo_acta')).": ".$datos_acta['encabezado']['tipo_acta']." (".kernel::traductor()->trans('reporte_actas.rectifica_a', array('%1%' => $datos_acta['encabezado']['id_acta'])).")");
        }
        else{
            $pdf->agregar_texto(ucfirst(kernel::traductor()->trans('reporte_actas.tipo_acta')).": ".$datos_acta['encabezado']['tipo_acta']);
        }

        $pdf->agregar_texto(ucfirst(kernel::traductor()->trans('reporte_actas.estado')).": ".$datos_acta['encabezado']['estado_acta']);
        $pdf->agregar_texto(ucfirst(kernel::traductor()->trans('reporte_actas.libro')).": ".$datos_acta['encabezado']['libro']);
        $pdf->agregar_texto(ucfirst(kernel::traductor()->trans('reporte_actas.anio_academico')).": ".$datos_acta['encabezado']['anio_academico']);
        $pdf->agregar_texto(ucfirst(kernel::traductor()->trans('reporte_actas.periodo_lectivo')).": ".$datos_acta['encabezado']['periodo_lectivo_nombre']);
        $pdf->agregar_texto(ucfirst(kernel::traductor()->trans('reporte_actas.actividad')).": ".$datos_acta['encabezado']['actividad_nombre']." (".$datos_acta['encabezado']['actividad_codigo'].")");
        $pdf->agregar_texto(ucfirst(kernel::traductor()->trans('reporte_actas.comision')).": ".$datos_acta['encabezado']['comision_nombre']." (".$datos_acta['encabezado']['comision'].")");
        $pdf->agregar_texto(ucfirst(kernel::traductor()->trans('reporte_actas.ubicacion')).": ".$datos_acta['encabezado']['ubicacion_nombre']);
        $pdf->agregar_texto(ucfirst(kernel::traductor()->trans('reporte_actas.observaciones')).": ".$datos_acta['encabezado']['observaciones']);
        $pdf->agregar_texto();
    }

    protected function prepare_encabezado_xls($datos_acta){
        $encabezado_xls = array();
        $encabezado_xls[] = array(ucfirst(kernel::traductor()->trans('reporte_actas.nro_acta')).": ", $datos_acta['encabezado']['nro_acta']);

        if($datos_acta['encabezado']['tipo_acta'] == "Rectificativa"){
            $encabezado_xls[] = array(ucfirst(kernel::traductor()->trans('reporte_actas.tipo_acta')).": ", $datos_acta['encabezado']['tipo_acta']." (".kernel::traductor()->trans('reporte_actas.rectifica_a', array('%1%' => $datos_acta['encabezado']['id_acta'])).")");
        }
        else{
            $encabezado_xls[] = array(ucfirst(kernel::traductor()->trans('reporte_actas.tipo_acta')).": ", $datos_acta['encabezado']['tipo_acta']);
        }

        $encabezado_xls[] = array(ucfirst(kernel::traductor()->trans('reporte_actas.estado')).": ", $datos_acta['encabezado']['estado_acta']);
        $encabezado_xls[] = array(ucfirst(kernel::traductor()->trans('reporte_actas.libro')).": ", $datos_acta['encabezado']['libro']);
        $encabezado_xls[] = array(ucfirst(kernel::traductor()->trans('reporte_actas.anio_academico')).": ", $datos_acta['encabezado']['anio_academico']);
        $encabezado_xls[] = array(ucfirst(kernel::traductor()->trans('reporte_actas.periodo_lectivo')).": ", $datos_acta['encabezado']['periodo_lectivo_nombre']);
        $encabezado_xls[] = array(ucfirst(kernel::traductor()->trans('reporte_actas.actividad')).": ", $datos_acta['encabezado']['actividad_nombre']." (".$datos_acta['encabezado']['actividad_codigo'].")");
        $encabezado_xls[] = array(ucfirst(kernel::traductor()->trans('reporte_actas.comision')).": ", $datos_acta['encabezado']['comision_nombre']." (".$datos_acta['encabezado']['comision'].")");
        $encabezado_xls[] = array(ucfirst(kernel::traductor()->trans('reporte_actas.ubicacion')).": ", $datos_acta['encabezado']['ubicacion_nombre']);
        $encabezado_xls[] = array(ucfirst(kernel::traductor()->trans('reporte_actas.observaciones')).": ", $datos_acta['encabezado']['observaciones']);
        return $encabezado_xls;
    }
}