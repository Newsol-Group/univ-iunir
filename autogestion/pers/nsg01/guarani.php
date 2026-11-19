<?php
namespace nsg01;

use siu\extension_kernel\formularios\fabrica_formularios;
use siu\modelo\entidades\parametro;
use SIU\Chulupi\util\bcrypt;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;
use siu\modelo\controles\validador_puntos_de_control;
use siu\errores\error_guarani;

use siu\modelo\entidades\persona;
use siu\modelo\entidades\unidad_academica;
use nsg01\modelo\transacciones\datos_facturacion;

class guarani extends \siu\guarani
{
	static protected $datos_facturacion;
	static protected $reporte_asistencia;
	static protected $datos_censales_docentes;

	/**
	 * 
	 * @return modelo\transacciones\datos_facturacion
	 */
	static function datos_facturacion()
	{
		if (!self::$datos_facturacion) {

			$parametros['persona'] = kernel::persona()->get_id();
			$es_perfil_cursos = kernel::persona()->es_perfil_cursos();

			if (!$es_perfil_cursos) {
				$alumno = kernel::persona()->get_id_alumno_activo();
				$parametros['alumno'] = array('alumno' => $alumno);
			}
			else {
				$propuestas = catalogo::consultar('cursos', 'get_propuestas_academica', array('persona' => $parametros['persona']));

				foreach($propuestas as &$propuesta){
					$propuesta['cursos']= catalogo::consultar('cursos', 'get_cursos_academica', array('persona' => $parametros['persona'], 'propuesta' => $propuesta['propuesta']));
				}
				$parametros['propuesta'] = $propuesta['propuesta'];

				$alumno = catalogo::consultar('alumno', 'get_alumno', array('persona' => $parametros['persona'], 'propuesta' => $parametros['propuesta']));
				$parametros['alumno'] = $alumno;
			}
		
//			self::$datos_facturacion = catalogo::consultar('datos_facturacion', 'get_datos_facturacion_alumno', $parametros['alumno']);
//			$alumno = guarani::persona()->get_id_alumno_activo();
			self::$datos_facturacion = kernel::localizador()->instanciar("modelo\\transacciones\\datos_facturacion", $alumno);
		}
		return self::$datos_facturacion;
	}
	
	/**
	 * @return \siu\modelo\transacciones\reporte_asistencia
	 */
	static function reporte_asistencia()
	{
		if (!self::$reporte_asistencia) {
			$persona = guarani::persona()->get_id();
			self::$reporte_asistencia = kernel::localizador()->instanciar("modelo\\transacciones\\reporte_asistencia", $persona);
		}
		return self::$reporte_asistencia;
	}

}
?>