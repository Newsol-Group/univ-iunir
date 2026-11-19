<?php
/**
 *
 * @package		Administración
 * @subpackage 	Becas
 */
class cn_man_generar_datos_becas extends guarani_pers_cn
{
	function generar($becas, $anio_academico, $periodo, $id_proceso, $responsable_academica=null, $numero_documento=null)
	{
		$becas_dir = ($becas == beca::becas_manuel_belgrano) ? beca::becas_manuel_belgrano_dir :  beca::becas_progresar_dir;
		$dir_name = toba::proyecto()->get_path_temp()."/becas/$becas_dir/$anio_academico/";
		toba_manejador_archivos::crear_arbol_directorios($dir_name);
		guarani_archivos::borrar_archivos_path($dir_name, array('zip'));

		$textos = guarani::act('act_becas')->procesar_alumnos_becas($becas, $anio_academico, $periodo, $id_proceso, $responsable_academica, $numero_documento);
		$cantidad_textos = guarani_arreglos::count($textos);
		$i = 0;

		$nombre_archivo = isset($responsable_academica) ? "becas_$responsable_academica.zip" : "becas.zip";
		$archivo = $dir_name.$nombre_archivo;
		$zip = new ZipArchive();
		$zip->open($archivo, ZIPARCHIVE::CREATE);

		// Archivo alumnos
		$filename = $dir_name.'alumnos.txt';
		if (!file_exists($filename)) {
			$handle = fopen($filename, 'w');
			while ($i < $cantidad_textos) {
				if ($textos[$i]['f_int_alumnos_becas'] == '#SECCION1# **************** Inicio archivo alumnos.txt *****************') {
					$i++;
				} elseif ($textos[$i]['f_int_alumnos_becas'] == '#SECCION1# **************** Fin archivo alumnos.txt *****************') {
					$i++;
					break;
				} else {
					fwrite($handle, $textos[$i]['f_int_alumnos_becas'] . "\n");
					$i++;
				}
			}
			fclose($handle);
		}
		$zip->addFile($filename, basename($filename));

		// Archivo planes de estudio
		$filename = $dir_name.'planes_estudio.txt';
		if (!file_exists($filename)) {
			$handle = fopen($filename, 'w');
			while ($i < $cantidad_textos) {
				if ($textos[$i]['f_int_alumnos_becas'] == '#SECCION2# **************** Inicio archivo planes_estudio.txt *****************') {
					$i++;
				} elseif ($textos[$i]['f_int_alumnos_becas'] == '#FINSECCION2# **************** Fin archivo planes_estudio.txt ************') {
					$i++;
					break;
				} else {
					fwrite($handle, $textos[$i]['f_int_alumnos_becas'] . "\n");
					$i++;
				}
			}
			fclose($handle);
		}
		$zip->addFile($filename, basename($filename));

		// Archivo materias
		$filename = $dir_name.'materias.txt';
		if (!file_exists($filename)) {
			$handle = fopen($filename, 'w');
			while ($i < $cantidad_textos) {
				if ($textos[$i]['f_int_alumnos_becas'] == '#SECCION3# **************** Inicio archivo materias.txt *****************') {
					$i++;
				} elseif ($textos[$i]['f_int_alumnos_becas'] == '#FINSECCION3# **************** Fin archivo materias.txt ************') {
					$i++;
					break;
				} else {
					fwrite($handle, $textos[$i]['f_int_alumnos_becas'] . "\n");
					$i++;
				}
			}
			fclose($handle);
		}
		$zip->addFile($filename, basename($filename));

		// Archivo alumnos con error
		$filename = $dir_name.'alumnos_con_error.txt';
		if (!file_exists($filename)) {
			$handle = fopen($filename, 'w');
			while ($i < $cantidad_textos) {
				if ($textos[$i]['f_int_alumnos_becas'] == '#SECCION4# **************** Inicio archivo de Errores alumnos_con_error.txt ************') {
					$i++;
				} elseif ($textos[$i]['f_int_alumnos_becas'] == '#FINSECCION4# **************** Fin archivo de Errores alumnos_con_error.txt ************') {
					break;
				} else {
					fwrite($handle, $textos[$i]['f_int_alumnos_becas'] . "\n");
					$i++;
				}
			}
			fclose($handle);
		}
		$zip->addFile($filename, basename($filename));

		$zip->close();
		guarani_archivos::borrar_archivos_path($dir_name, array('txt'));
	}

	function procesar_archivo_alumnos($archivo_alumnos, $id_proceso)
	{
		$errores = array();
		$handle = fopen($archivo_alumnos, 'r');
		if ($handle) {
			guarani::act('act_becas')->eliminar_alumnos_becas();
			$i_fila = 0;
			while (!feof($handle)) {
				$fila = fgets($handle);
				$i_fila++;
				if (!$fila || trim($fila) == '') {
					continue;
				}
				$columnas = explode('|', trim($fila));
				try {
					toba::db()->abrir_transaccion();
					$this->validar($columnas);
					guarani::act('act_becas')->insertar_alumno_becas($columnas, $id_proceso);
					toba::db()->cerrar_transaccion();
				} catch (toba_error $e) {
					$error['fila'] = $i_fila;
					$error['observaciones'] = $e->get_mensaje();
					$errores[] = $error;
					toba::db()->abortar_transaccion();
				}
			}
		}
		fclose($handle);
		return $errores;
	}

	function validar($columnas)
	{
		if (count($columnas) < 10) {
			throw guarani::error(guarani_mensajes::get('800ADM_becas_faltan_columnas'));
		}
		// No nulos: 0 - Código Institución, 2 - Código Facultad, 4 - Código del Título Araucano, 6 - Tipo de Documento, 7 - Número de Documento.
		if ($columnas[0] == '') {
			throw guarani::error(guarani_mensajes::get('800ADM_becas_codigo_institucion_vacio'));
		}
		if ($columnas[2] == '') {
			throw guarani::error(guarani_mensajes::get('800ADM_becas_codigo_facultad_vacio'));
		}
		if ($columnas[4] === '') {
			throw guarani::error(guarani_mensajes::get('800ADM_becas_codigo_titulo_vacio'));
		}
		if ($columnas[6] == '') {
			throw guarani::error(guarani_mensajes::get('800ADM_becas_tipo_documento_vacio'));
		}
		if ($columnas[7] == '') {
			throw guarani::error(guarani_mensajes::get('800ADM_becas_numero_documento_vacio'));
		}
	}
}