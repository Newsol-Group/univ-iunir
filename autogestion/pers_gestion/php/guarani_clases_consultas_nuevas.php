<?php
/**
 * Esta clase fue y será generada automáticamente. NO EDITAR A MANO.
 * @ignore
 */
class guarani_clases_consultas_nuevas 
{
	static function existe_clase($nombre)
	{
		return isset(self::$clases[$nombre]);
	}

	static function consulta($nombre)
	{
		if (static::existe_clase($nombre)) {
			$clase = self::$clases[$nombre]['clase'];
			$path = self::$clases[$nombre]['path'];
			return array($clase, $path);
		} else { 
			throw new siu\errores\error_guarani("No existe la clase: $nombre pude ser que tenga que volver a generar la clase g3_clases_consulta con el comando generar_autoload");
		}
	}

	static $clases = array(
		'co_actas' => array('clase'=>'co_actas_nsg','path'=>'../../pers_gestion/php/nucleo/actas/co_actas_nsg.php'),
		'co_alumnos' => array('clase'=>'co_alumnos_nsg','path'=>'../../pers_gestion/php/nucleo/matriculas/alumnos/co_alumnos_nsg.php'),
		'co_araucano' => array('clase'=>'co_araucano_nsg','path'=>'../../pers_gestion/php/nucleo/administracion/araucano/co_araucano_nsg.php'),
		'co_asistencias' => array('clase'=>'co_asistencias_nsg','path'=>'../../pers_gestion/php/nucleo/asistencia/co_asistencias_nsg.php'),
		'co_certificados' => array('clase'=>'co_certificados_nsg','path'=>'../../pers_gestion/php/nucleo/propuestas/certificados/co_certificados_nsg.php'),
		'co_cobros' => array('clase'=>'co_cobros_nsg','path'=>'../../pers_gestion/php/nucleo/cobro/co_cobros_nsg.php'),
		'co_comisiones' => array('clase'=>'co_comisiones_nsg','path'=>'../../pers_gestion/php/nucleo/cursadas/comisiones/co_comisiones_nsg.php'),
		'co_constancias' => array('clase'=>'co_constancias_nsg','path'=>'../../pers_gestion/php/nucleo/constancias/co_constancias_nsg.php'),
		'co_datos_censales' => array('clase'=>'co_datos_censales_nsg','path'=>'../../pers_gestion/php/nucleo/matriculas/datos_censales/co_datos_censales_nsg.php'),
		'co_docentes' => array('clase'=>'co_docentes_nsg','path'=>'../../pers_gestion/php/nucleo/docentes/actualizaciones/docentes/co_docentes_nsg.php'),
		'co_facturacion' => array('clase'=>'co_facturacion_nsg','path'=>'../../pers_gestion/php/nucleo/facturacion/co_facturacion_nsg.php'),
		'co_inscripciones_cursadas' => array('clase'=>'co_inscripciones_cursadas_nsg','path'=>'../../pers_gestion/php/nucleo/cursadas/inscripciones/co_inscripciones_cursadas_nsg.php'),
		'co_inscripciones_examenes' => array('clase'=>'co_inscripciones_examenes_nsg','path'=>'../../pers_gestion/php/nucleo/examenes/inscripciones/co_inscripciones_examenes_nsg.php'),
		'co_inscripciones' => array('clase'=>'co_inscripciones_nsg','path'=>'../../pers_gestion/php/nucleo/matriculas/inscripciones/co_inscripciones_nsg.php'),
		'co_instituciones' => array('clase'=>'co_instituciones_nsg','path'=>'../../pers_gestion/php/nucleo/ug/instituciones/co_instituciones_nsg.php'),
		'co_mensajes' => array('clase'=>'co_mensajes_nsg','path'=>'../../pers_gestion/php/nucleo/comunicacion/enviar_mensajes/co_mensajes_nsg.php'),
		'co_mesas_de_examen' => array('clase'=>'co_mesas_de_examen_nsg','path'=>'../../pers_gestion/php/nucleo/examenes/mesas_de_examen/co_mesas_de_examen_nsg.php'),
		'co_mug' => array('clase'=>'co_mug_nsg','path'=>'../../pers_gestion/php/nucleo/_lib/co_mug_nsg.php'),
		'co_personas' => array('clase'=>'co_personas_nsg','path'=>'../../pers_gestion/php/nucleo/matriculas/personas/co_personas_nsg.php'),
		'co_tramites_certificacion' => array('clase'=>'co_tramites_certificacion_nsg','path'=>'../../pers_gestion/php/nucleo/egresados/actualizaciones/solicitar_certificaciones/co_tramites_certificacion_nsg.php'),
		'co_usuarios' => array('clase'=>'co_usuarios_pers','path'=>'../../pers_gestion/php/nucleo/_lib/usuarios/co_usuarios_pers.php'),
	);
}
?>