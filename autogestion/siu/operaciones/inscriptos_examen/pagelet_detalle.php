<?php
namespace siu\operaciones\inscriptos_examen;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\operaciones\_comun\operaciones\reporte\generador_excel;
use siu\operaciones\_comun\operaciones\reporte\super_generador_pdf;
use siu\modelo\entidades\docente;

class pagelet_detalle extends pagelet
{
	function get_nombre()
	{
		return 'detalle';
	}

	function prepare()
	{
		$seleccion = $this->controlador->llamado_mesa_params;
		if (!$this->controlador->is_zona_activa()) {
			// No es necesario hacer la consulta, viene en el encabezado de la zona
			$this->data['encabezado'] = $this->controlador->modelo()->info__mesa_examen($seleccion);
		}
		$mesa = $this->controlador->modelo()->info__mesa_examen($seleccion);
		$alumnos = $this->controlador->modelo()->info__alumnos_examen($seleccion);
		foreach ($alumnos as $key => $dato) {
			$alumnos[$key]['URL'] = kernel::vinculador()->crear('inscriptos_examenes', 'ficha_alumno', array(
				'id_imagen' => $dato['id_imagen'],
				'alumno' => $dato['alumno'],
				'actividad' => $mesa['actividad']
			));
		}
		$this->data['alumnos'] = $alumnos;
		$parametros = array($this->controlador->hash);
		$this->data['url_generar_pdf'] = kernel::vinculador()->crear('inscriptos_examen', 'generar_detalle_pdf', $parametros);
		$this->data['url_generar_xls'] = kernel::vinculador()->crear('inscriptos_examen', 'generar_detalle_xls', $parametros);

		$this->data['zona_activa'] = $this->controlador->is_zona_activa();

                $this->data['visualiza_datos_contacto'] = docente::visualiza_datos_contacto_por_llamado_mesa($seleccion['llamado_mesa']);
	}

	function generar_pdf()
	{
		$datos = $this->datos_impresion();
		$pdf = new super_generador_pdf();
                $nombre_archivo = $this->get_nombre_archivo().'.pdf';
		$pdf->set_report($nombre_archivo);
		$pdf->set_header_title($this->get_nombre_archivo());
		$pdf->set_header_subtitle($this->get_encabezado());
		if (!empty($datos['alumnos'])) {
			$pdf->tabla($datos['alumnos'], $this->get_columnas_impresion());
		} else {
			$pdf->texto(ucfirst(kernel::traductor()->trans('inscriptos_examenes.detalle.vacio')));
		}
		$path_archivo = $pdf->generate(false);
                return [$path_archivo, $nombre_archivo];
	}

	function generar_xls()
	{
		$datos = $this->datos_impresion();
		$titulos = $this->get_columnas_impresion();
		$xls = new generador_excel();
		$xls->set_nombre_archivo($this->get_nombre_archivo());
		$xls->agregar_tabla($datos['alumnos'], $titulos, 'Inscriptos');
		$xls->generar();
	}
	
	//--------------------------------------------------------------------------
	//---- Auxiliares
	//--------------------------------------------------------------------------
	
	protected function datos_impresion()
	{
		$seleccion = $this->controlador->llamado_mesa_params;
		$datos['alumnos'] = $this->controlador->modelo()->info__alumnos_examen($seleccion);
		return $datos;
	}

	protected function get_encabezado()
	{
		$seleccion = $this->controlador->llamado_mesa_params;
		$encabezado = $this->controlador->modelo()->info__mesa_examen($seleccion);

		return ucfirst(kernel::traductor()->trans('nombre_actividad')) . ': ' . $encabezado['actividad_nombre'] . ' (' . $encabezado['actividad_codigo'] . ')' . PHP_EOL
				. ucfirst(kernel::traductor()->trans('fecha')) . ': ' . $encabezado['fecha_examen'] . PHP_EOL
				. ucfirst(kernel::traductor()->trans('ubicacion')) . ': ' . $encabezado['ubicacion_nombre'] . PHP_EOL
				. ucfirst(kernel::traductor()->trans('horario')) . ': ' . $encabezado['hora_inicio_finalizacion'] . PHP_EOL
				. ucfirst(kernel::traductor()->trans('docentes')) . ': ' . $encabezado['docentes'] . PHP_EOL
				. ucfirst(kernel::traductor()->trans('instancias')) . ': ' . $encabezado['instancias'] . PHP_EOL
				. ucfirst(kernel::traductor()->trans('turno')) . ': ' . $encabezado['turno_examen_nombre'] . PHP_EOL
				. ucfirst(kernel::traductor()->trans('llamado')) . ': ' . $encabezado['llamado_nombre'];
	}
	
	protected function get_nombre_archivo()
	{
		return 'Detalle de inscripción a examen';
	}

	protected function get_columnas_impresion()
	{
		$columnas = [
			'legajo' => kernel::traductor()->trans('inscriptos_examen.legajo'),
			'nombre_alumno' => kernel::traductor()->trans('inscriptos_examen.alumno'),
			'instancia_nombre' => kernel::traductor()->trans('inscriptos_examen.instancia'),
			'estado_inscripcion' => kernel::traductor()->trans('inscriptos_examen.estado'),
		];

                $llamado_mesa = $this->controlador->getLlamadoMesa();
                $visualiza_datos_contacto = docente::visualiza_datos_contacto_por_llamado_mesa($llamado_mesa);

                // Si el docente puede visualizar los datos de contacto de los alumnos (email y teléfono)
                if ($visualiza_datos_contacto) {
                    $columnas['email'] = kernel::traductor()->trans('inscriptos_examen.email');
                    $columnas['telefono'] = kernel::traductor()->trans('inscriptos_examen.telefono');
                }
                
                return $columnas;
	}

}