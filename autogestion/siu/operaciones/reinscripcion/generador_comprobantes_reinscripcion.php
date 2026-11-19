<?php
namespace siu\operaciones\reinscripcion;
use siu\operaciones\_comun\comprobantes\generador_comprobantes;
use siu\errores\error_guarani;
use SIU\Chulupi\kernel;
use siu\guarani;
use \siu\modelo\datos\catalogo;

/**
 * Description of generador_comprobantes_reinscripcion
 *
 * @author bruno
 */
class generador_comprobantes_reinscripcion {
    const TIPO_GENERACION_ARCHIVO = 'archivo';
    const TIPO_GENERACION_DESCARGA  = 'descarga';
    const TIPO_GENERACION_STREAM = 'stream';

    protected $tipo_generacion;
    protected $nombre_archivo;
    protected $path_destino;
    protected $opciones;
    protected $generador;

    function __construct()
    {
        $this->generador =	new generador_comprobantes($this->get_ancho(), $this->get_alto(), $this->get_opciones());
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
        return 420;
    }

    protected function get_alto()
    {
        return 510;
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

    protected function get_nombre_archivo($datos)
    {
        return "reinscripcion_".$datos['nro_trans'].".png";
    }

    function generar_comprobante($datos)
    {
        $subtitulo = kernel::traductor()->trans('comprobante_reinscripcion');
        $this->generador->agregar_subtitulo($subtitulo, 'i');

        $alto_img = 127;
        $margen_inferior = 20;

        $tabla1 = array();

		$responsables = catalogo::consultar('unidad_academica', 'get_responsables_propuesta', array('propuesta' => kernel::persona()->get_id_propuesta_activa()));
		$responsables_nombres = array_column($responsables, 'nombre');
		
        $tabla1['responsables_academicas'] = $responsables_nombres;
        $tabla1['nombre_alumno'] = $datos['alumno_nombre'];
        $tabla1['identificacion'] = $datos['identificacion'];
        if (!empty($datos['legajo'])){
                $tabla1['legajo'] = $datos['legajo'];
        }

        $tabla1['nombre_propuesta'] = '('.$datos['propuesta'].') '.$datos['propuesta_nombre'];
        $tabla1['plan'] = '('.$datos['plan_codigo'].') '.$datos['plan_nombre'];

        $tabla1['modalidad_cursada'] = $datos['modalidad_cursada_nombre'];
        $tabla1['ubicacion'] = $datos['ubicacion_nombre'];
        $tabla1['anio_academico'] = $datos['anio_academico'];
        $tabla1['fecha_reinscripcion'] = $datos['fecha_reinscripcion'];
        $tabla1['nro_trans'] = $datos['nro_trans'];

        $alto_tabla = $this->generador->agregar_tabla($tabla1);
        $alto_img += $alto_tabla;
        $alto_img += $margen_inferior;//le agrego un margen inferior a la imagen

        $this->nombre_archivo = $this->get_nombre_archivo($datos);
        $this->generador->set_conf('alto', $alto_img);
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
                if (! isset($this->path_destino)) {
                        throw new error_guarani("para guardar el comprobante debe setear el path_destino con set_path_destino");
                }
                imagepng($imagen, $this->path_destino);
                break;
        }
    }
}