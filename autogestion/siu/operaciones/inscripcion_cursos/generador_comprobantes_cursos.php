<?php
namespace siu\operaciones\inscripcion_cursos;

use siu\operaciones\_comun\comprobantes\generador_comprobantes;
use siu\errores\error_guarani;
use SIU\Chulupi\kernel;
use siu\guarani;
use \siu\modelo\datos\catalogo;

/**
 * Description of generador_comprobantes_cursada
 *
 * @author andres
 */
class generador_comprobantes_cursos {
    const TIPO_BAJA = 'baja';
    const TIPO_ALTA = 'alta';

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
        return 430;
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
        $subtitulo = kernel::traductor()->trans('inscripcion_cursos.comprobante_inscripcion_curso_alta');
        $this->generador->agregar_subtitulo($subtitulo, 'i');

        $alto_img = 127;
        $margen_inferior = 20;

        //Obtengo la propuesta seleccionada de sesion
        $propuesta_seleccionada = null;
		$responsables = array();
		$responsables_nombres = array();
        if(kernel::sesion()->esta_seteada('__propuesta')) $propuesta_seleccionada = kernel::sesion()->get('__propuesta');
		kernel::log()->add_debug('propuesta_seleccionada', $propuesta_seleccionada);
        if($propuesta_seleccionada){
            $responsables = catalogo::consultar('unidad_academica', 'get_responsables_propuesta', array('propuesta' => $propuesta_seleccionada));
			$responsables_nombres = array_column($responsables, 'nombre');
        }

        $msj_espacio_fisico_a_determinar = kernel::traductor()->trans('espacio_fisico_a_determinar');

        $tabla = array();
		$tabla['responsables_academicas'] = $responsables_nombres;
        $tabla['nombre_alumno'] = $datos['alumno_nombre'];
        $tabla['identificacion'] = $datos['identificacion'];

        $tabla['nombre_curso'] = $datos['actividad_nombre'].' - '.$datos['comision_nombre'];

        $tabla['fecha_inscripcion'] = $datos['fecha_inscripcion'];
        $tabla['fecha_inicio_clases'] = $datos['fecha_inicio_dictado'];
        $tabla['nombre_horario'] = $datos['horario'];
        $tabla['ubicacion'] = $datos['ubicacion_nombre'];
        $tabla['espacio_fisico'] = empty($datos['espacio_fisico'])? $msj_espacio_fisico_a_determinar : $datos['espacio_fisico'];
        $tabla['modalidades'] = $datos['modalidades'];

        $tabla['estado_inscripcion'] = strtoupper($datos['estado_inscripcion']);

        $tabla['nro_transaccion'] = $datos['nro_transaccion'];
        $tabla['importante'] = kernel::traductor()->trans('comprobante_aviso');

        $alto_tabla = $this->generador->agregar_tabla($tabla);
        $alto_img += $alto_tabla;
        $alto_img += $margen_inferior;//le agrego un margen inferior a la imagen

        $this->nombre_archivo = $this->get_nombre_archivo_alta($datos);
        $this->generador->set_conf('alto', $alto_img);
        $this->output($this->generador->generar());
    }

    function generar_comp_baja($datos)
    {
		
		$alto_img = 127;
        $margen_inferior = 20;
		
		//Obtengo la propuesta seleccionada de sesion
        $propuesta_seleccionada = null;
		$responsables = array();
		$responsables_nombres = array();
        if(kernel::sesion()->esta_seteada('__propuesta')) $propuesta_seleccionada = kernel::sesion()->get('__propuesta');
		kernel::log()->add_debug('propuesta_seleccionada', $propuesta_seleccionada);
        if($propuesta_seleccionada){
            $responsables = catalogo::consultar('unidad_academica', 'get_responsables_propuesta', array('propuesta' => $propuesta_seleccionada));
			$responsables_nombres = array_column($responsables, 'nombre');
        }
		
        $tabla = array(
			'responsables_academicas' => $responsables_nombres,
            'nombre_alumno' => $datos['alumno_nombre'],
            'identificacion' => $datos['identificacion'],
            'nombre_curso'  => $datos['actividad_nombre'].' - '.$datos['comision_nombre'],
            'fecha_inscripcion' => $datos['fecha_inscripcion'],
            'fecha_baja_inscripcion' => $datos['fecha_baja_inscripcion'],
            'nro_transaccion' => $datos['nro_transaccion'],
            'importante' => kernel::traductor()->trans("comprobante_aviso")
        );

        $subtitulo = kernel::traductor()->trans("inscripcion_cursos.comprobante_inscripcion_curso_baja");
        $this->generador->agregar_subtitulo($subtitulo, "i");
        $alto_tabla = $this->generador->agregar_tabla($tabla);
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
                if (! isset($this->path_destino)) {
                    throw new error_guarani("para guardar el comprobante debe setear el path_destino con set_path_destino");
                }
                imagepng($imagen, $this->path_destino);
                break;
        }
    }
}