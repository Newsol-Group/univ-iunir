<?php
namespace siu\operaciones\inscripcion_examen;

use siu\operaciones\_comun\comprobantes\generador_comprobantes;
use siu\errores\error_guarani;
use SIU\Chulupi\kernel;
use siu\guarani;
use \siu\modelo\datos\catalogo;

class generador_comprobantes_examen
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
		return 860;
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

	function generar_comp_alta($datos)
	{
		$alto_img = 357;
        $margen_inferior = 20;
		
		// Se crean y formatean fechas de inicio y fin para generar evento QR
		$fecha_inicio_examen = \DateTime::createFromFormat("d/m/Y H:i", $datos['fecha_mesa_examen'] . ' ' . $datos['hora_inicio']);
		$fecha_inicio_examen_qr_format = $fecha_inicio_examen->format("Ymd\THi00");

		$fecha_fin_examen = \DateTime::createFromFormat("d/m/Y H:i", $datos['fecha_mesa_examen'] . ' ' . $datos['hora_finalizacion']);
		$fecha_fin_examen_qr_format = ($fecha_fin_examen ? $fecha_fin_examen->format("Ymd\THi00") : $fecha_inicio_examen_qr_format);

		$titulo = kernel::traductor()->trans("aviso_comprobante_qr") . '(' . $datos['actividad_codigo'] . ') ' . $datos['actividad_nombre_abreviado'];
		$ubicacion = $datos['espacio_descripcion'];

		$qr = new \siu\extension_kernel\qrcode($titulo, $fecha_inicio_examen_qr_format, $fecha_fin_examen_qr_format, null, $ubicacion);
		$calendar_event_info = $qr->get_calendar_event_info();

		$responsables = catalogo::consultar('unidad_academica', 'get_responsables_propuesta', array('propuesta' => kernel::sesion()->get('__propuesta')));
		$responsables_nombres = array_column($responsables, 'nombre');
		
		$tabla = array();
		$tabla['responsables_academicas'] = $responsables_nombres;
		$tabla['nombre_alumno'] = $datos['alumno_nombre'];
		$tabla['identificacion'] = $datos['identificacion'];
		$tabla['legajo'] = $datos['legajo'];
		$tabla['nombre_propuesta'] = '(' . $datos['propuesta'] . ') ' . $datos['propuesta_nombre'];
		$tabla['nombre_actividad'] = '(' . $datos['actividad_codigo'] . ') ' . $datos['actividad_nombre_abreviado'];
		$tabla['fecha_inscripcion'] = $datos['fecha_inscripcion'];
		$tabla['anio_academico'] = $datos['anio_academico'];
		$tabla['turno_examen'] = $datos['turno_examen_nombre'];
		$tabla['llamado'] = $datos['llamado_nombre'];
		$tabla['mesa_examen'] = $datos['mesa_examen_nombre'];
		$tabla['fecha_mesa_examen'] = $datos['fecha_mesa_examen'];
		$tabla['hora_inicio'] = $datos['hora_inicio'];
		$tabla['hora_fin'] = ($datos['hora_finalizacion'] ? $datos['hora_finalizacion'] : kernel::traductor()->trans('insc_examen.hora_sin_definir'));
		$tabla['catedra'] = $datos['catedra_nombre'];
		$tabla['instancia'] = $datos['instancia_nombre'];
		$tabla['ubicacion'] = $datos['ubicacion_nombre'];
		$tabla['lugar'] = $datos['espacio_nombre'];
		$tabla['estado_inscripcion'] = $datos['estado_inscripcion'];
		$tabla['nro_transaccion'] = $datos['nro_transaccion'];
		$tabla['importante'] = kernel::traductor()->trans('comprobante_aviso_qr');

		$subtitulo = kernel::traductor()->trans('comprobante_inscripcion_examen_alta');

		$this->generador->agregar_subtitulo($subtitulo, 'i');
		$alto_tabla = $this->generador->agregar_tabla($tabla);
		$this->generador->agregar_QR($calendar_event_info, 120, $alto_img - 240);
		$alto_img += $alto_tabla;
		$alto_img += $margen_inferior;//le agrego un margen inferior a la imagen
		$this->generador->set_conf('alto', $alto_img);
		$this->nombre_archivo = $this->get_nombre_archivo_alta($datos);
		$this->output($this->generador->generar());
	}

	function generar_comp_baja($datos)
	{
		$alto_img = 127;
		$margen_inferior = 20;
		
		$responsables = catalogo::consultar('unidad_academica', 'get_responsables_propuesta', array('propuesta' => kernel::sesion()->get('__propuesta')));
		$responsables_nombres = array_column($responsables, 'nombre');
		
		$tabla = array(
			'responsables_academicas' => $responsables_nombres,
			'nombre_alumno' => $datos['alumno_nombre'],
			'identificacion' => $datos['identificacion'],
			'legajo' => $datos['legajo'],
			'nombre_propuesta' => '(' . $datos['propuesta'] . ') ' . $datos['propuesta_nombre_abreviado'],
			'nombre_actividad' => '(' . $datos['actividad_codigo'] . ') ' . $datos['actividad_nombre_abreviado'],
			'fecha_inscripcion' => $datos['fecha_inscripcion'],
			'fecha_baja_inscripcion' => $datos['fecha_baja_inscripcion'],
			'anio_academico' => $datos['anio_academico'],
			'turno_examen' => $datos['turno_examen_nombre'],
			'llamado' => $datos['llamado_nombre'],
			'mesa_examen' => $datos['mesa_examen_nombre'],
			'fecha_mesa_examen' => $datos['fecha_mesa_examen'],
			'hora_inicio' => $datos['hora_inicio'],
			'catedra' => $datos['catedra_nombre'],
			'ubicacion' => $datos['ubicacion_nombre'],
			'lugar' => $datos['espacio_nombre'],
			'nro_transaccion_inscripcion' => $datos['nro_transaccion_alta'],
			'nro_transaccion' => $datos['nro_transaccion_baja'],
			'importante' => kernel::traductor()->trans("comprobante_aviso")
		);

		$subtitulo = kernel::traductor()->trans("comprobante_inscripcion_examen_baja");
		$this->generador->agregar_subtitulo($subtitulo, "i");
		$this->generador->agregar_tabla($tabla);
		$alto_img += $alto_tabla;
		$alto_img += $margen_inferior;//le agrego un margen inferior a la imagen
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