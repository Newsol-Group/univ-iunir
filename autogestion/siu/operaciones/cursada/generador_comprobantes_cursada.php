<?php
namespace siu\operaciones\cursada;

use siu\operaciones\_comun\comprobantes\generador_comprobantes;
use siu\errores\error_guarani;
use SIU\Chulupi\kernel;
use siu\guarani;
use \siu\modelo\datos\catalogo;

class generador_comprobantes_cursada
{
	const TIPO_BAJA = 'baja';
	const TIPO_ALTA = 'alta';
	const TIPO_GENERACION_ARCHIVO = 'archivo';
	const TIPO_GENERACION_DESCARGA = 'descarga';
	const TIPO_GENERACION_STREAM = 'stream';

	protected $tipo_generacion;
	protected $nombre_archivo;
	protected $path_destino;
	protected $opciones;
	protected $generador;

	function __construct()
	{
		$this->generador = new generador_comprobantes($this->get_ancho(), $this->get_alto(), $this->get_opciones());
		$this->generador->agregar_imagen($this->get_logo(), 5, 5, guarani::get_nombre_universidad());
		$this->generador->agregar_linea(10, 80, 420, 80, '');
	}

	protected function get_opciones()
	{
		$font = guarani::dir_fonts() . '/Ubuntu-R.ttf';
		$font_bold = guarani::dir_fonts() . '/Ubuntu-B.ttf';
		return array(
			'tamano_fuente' => 10,
			'tamano_fuente_titulo' => 14,
			'tamano_fuente_subtitulo' => 12,
			'interlineado' => 10,
			'font' => $font,
			'fontBold' => $font_bold,
			'margen' => 10,
			'set_offset_cuerpo' => 110
		);
	}

	protected function get_ancho()
	{
		return 430;
	}

	protected function get_alto()
	{
		return 600;
	}

	protected function get_logo()
	{
		return kernel::localizador()->path_recurso('/www/img/logo_comprobante_sin_bajada_nuevo.png');
	}

	function set_tipo_generacion($tipo_generacion)
	{
		$this->tipo_generacion = $tipo_generacion;
	}

	function set_path_destino($path)
	{
		$this->path_destino = $path;
	}

	protected function get_nombre_archivo_alta($datos)
	{
		return "Comprobante alta - {$datos['actividad_codigo']}.png";
	}

	protected function get_nombre_archivo_baja($datos)
	{
		return "Comprobante baja - {$datos['actividad_codigo']}.png";
	}

	function generar_comp_alta($datos, $comisiones_por_prioridad = null)
	{
		$subtitulo = kernel::traductor()->trans('comprobante_inscripcion_cursada_alta');
		$this->generador->agregar_subtitulo($subtitulo, 'i');

		$alto_img = 127;
		$margen_inferior = 20;

		$tabla1 = [];
		$tabla2 = [];
		$tabla3 = [];

		$responsables = catalogo::consultar('unidad_academica', 'get_responsables_propuesta', array('propuesta' => kernel::persona()->get_id_propuesta_activa()));
		$responsables_nombres = array_column($responsables, 'nombre');
		
		$tabla1['responsables_academicas'] = $responsables_nombres;
		$tabla1['nombre_alumno'] = $datos['alumno_nombre'];
		$tabla1['identificacion'] = $datos['identificacion'];
		$tabla1['legajo'] = $datos['legajo'];
		$tabla1['nombre_propuesta'] = '(' . $datos['propuesta'] . ') ' . $datos['propuesta_nombre'];
		$tabla1['plan'] = '(' . $datos['plan_codigo'] . ') ' . $datos['plan_nombre'];
		$tabla1['nombre_actividad'] = '(' . $datos['actividad_codigo'] . ') ' . $datos['actividad_nombre'];
		$tabla1['fecha_inscripcion'] = $datos['fecha_inscripcion'];


		$alto_tabla = $this->generador->agregar_tabla($tabla1);
		$alto_img += $alto_tabla;

                $msj_espacio_fisico_a_determinar = kernel::traductor()->trans('espacio_fisico_a_determinar');


		if (\guarani_arreglos::count($comisiones_por_prioridad) > 1) {
			$this->generador->agregar_linea(10, $alto_img, 420, $alto_img, '');

			foreach ($comisiones_por_prioridad as $comision_por_prioridad) {

				$tabla2['prioridad'] = $comision_por_prioridad['prioridad'];
				$tabla2['nombre_comision'] = $comision_por_prioridad['comision_nombre'];
				$tabla2['periodo_lectivo'] = $datos['anio_academico'] . ' - ' . $comision_por_prioridad['periodo_lectivo_nombre'];
				$tabla2['catedra'] = $comision_por_prioridad['catedra_nombre'];
				$tabla2['turno_cursada'] = $comision_por_prioridad['turno_cursada_nombre'];
				$tabla2['ubicacion'] = $comision_por_prioridad['ubicacion_nombre'];
				$tabla2['espacio_fisico'] = empty($comision_por_prioridad['espacio_fisico'])? $msj_espacio_fisico_a_determinar : $comision_por_prioridad['espacio_fisico'];
				$tabla2['instancias'] = $comision_por_prioridad['instancias'];
				$tabla2['modalidades'] = $comision_por_prioridad['modalidades'];
				$tabla2['estado_inscripcion'] = $comision_por_prioridad['estado_inscripcion'];
				$tabla2['subcomisiones'] = $comision_por_prioridad['subcomisiones'];

				$alto_tabla = $this->generador->agregar_tabla($tabla2);
				$alto_img += $alto_tabla;
				$this->generador->agregar_linea(10, $alto_img, 420, $alto_img, '');
			}
		} else {

			$tabla2['nombre_comision'] = $datos['comision_nombre'];
			$tabla2['periodo_lectivo'] = $datos['anio_academico'] . ' - ' . $datos['periodo_lectivo_nombre'];
			$tabla2['catedra'] = $datos['catedra_nombre'];
			$tabla2['turno_cursada'] = $datos['turno_cursada_nombre'];
			$tabla2['ubicacion'] = $datos['ubicacion_nombre'];
			$tabla2['espacio_fisico'] = empty($datos['espacio_fisico'])? $msj_espacio_fisico_a_determinar : $datos['espacio_fisico'];
			$tabla2['instancias'] = $datos['instancias'];
			$tabla2['modalidades'] = $datos['modalidades'];
			$tabla2['estado_inscripcion'] = $datos['estado_inscripcion'];
			$tabla2['subcomisiones'] = $datos['subcomisiones'];

			$alto_tabla = $this->generador->agregar_tabla($tabla2);
			$alto_img += $alto_tabla;
		}

		$tabla3['nro_transaccion'] = $datos['nro_transaccion'];
		$tabla3['importante'] = kernel::traductor()->trans('comprobante_aviso');

		$alto_tabla = $this->generador->agregar_tabla($tabla3);
		$alto_img += $alto_tabla;
		$alto_img += $margen_inferior; //le agrego un margen inferior a la imagen

		$this->nombre_archivo = $this->get_nombre_archivo_alta($datos);
		$this->generador->set_conf('alto', $alto_img);
		$this->output($this->generador->generar());
	}

	function generar_comp_baja($datos)
	{
		
		$responsables = catalogo::consultar('unidad_academica', 'get_responsables_propuesta', array('propuesta' => kernel::persona()->get_id_propuesta_activa()));
		$responsables_nombres = array_column($responsables, 'nombre');
		$alto_img = 127;
		$margen_inferior = 20;
		
		$tabla = array(
			'responsables_academicas' => $responsables_nombres,
			'nombre_alumno' => $datos['alumno_nombre'],
			'identificacion' => $datos['identificacion'],
			'legajo' => $datos['legajo'],
			'nombre_propuesta' => '(' . $datos['propuesta'] . ') ' . $datos['propuesta_nombre_abreviado'],
			'plan' => '(' . $datos['plan_codigo'] . ') ' . $datos['plan_nombre'],
			'nombre_actividad' => '(' . $datos['actividad_codigo'] . ') ' . $datos['actividad_nombre_abreviado'],
			'fecha_inscripcion' => $datos['fecha_inscripcion'],
			'fecha_baja_inscripcion' => $datos['fecha_baja_inscripcion'],
			'nombre_comision' => $datos['comision_nombre'],
			'anio_academico' => $datos['anio_academico'],
			'periodo_lectivo' => $datos['periodo_lectivo_nombre'],
			'catedra' => $datos['catedra_nombre'],
			'turno_cursada' => $datos['turno_cursada_nombre'],
			'ubicacion' => $datos['ubicacion_nombre'],
			'modalidades' => $datos['modalidades'],
			'nro_transaccion' => $datos['nro_transaccion'],
			'importante' => kernel::traductor()->trans("comprobante_aviso")
		);

		$subtitulo = kernel::traductor()->trans("comprobante_inscripcion_cursada_baja");
		$this->generador->agregar_subtitulo($subtitulo, "i");
		$alto_tabla = $this->generador->agregar_tabla($tabla);
		$alto_img += $alto_tabla;
		$alto_img += $margen_inferior;
		$this->generador->set_conf('alto', $alto_img);
		$this->nombre_archivo = $this->get_nombre_archivo_baja($datos);
			$this->output($this->generador->generar());
		}

	protected function output($imagen)
	{
		switch ($this->tipo_generacion) {
			case self::TIPO_GENERACION_DESCARGA:
				ob_clean();
				header("Content-Type: image/png");
				header("Content-Disposition: attachment; filename=\"$this->nombre_archivo\"");
				imagepng($imagen);
				break;
			case self::TIPO_GENERACION_STREAM:
				ob_clean();
				header("Content-Type: image/png");
				imagepng($imagen);
				break;
			case self::TIPO_GENERACION_ARCHIVO:
				if (!isset($this->path_destino)) {
					throw new error_guarani("para guardar el comprobante debe setear el path_destino con set_path_destino");
				}
				imagepng($imagen, $this->path_destino);
				break;
		}
	}

}