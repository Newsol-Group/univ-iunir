<?php
/**
 *
 * @package		Administración
 * @subpackage 	Araucano
 */
class cn_man_generar_datos_araucano extends guarani_pers_cn
{
	function generar($anio_academico, $cuadros_4_y_5, $archivo_13, $responsable_academica=null)
	{
		$dir_name = toba::proyecto()->get_path_temp().'/araucano/'.$anio_academico.'/';
		toba_manejador_archivos::crear_arbol_directorios($dir_name);
		guarani_archivos::borrar_archivos_path($dir_name, array('zip'));

		// Se deshabilitan los trigger de Auditoría
		guarani::act('act_araucano')->deshabilitar_trigger_auditoria();

        // Genero tabla con las actividades de cada version de plan de estudios
		guarani::act('act_araucano')->get_actividades_x_certificado($responsable_academica);

        // Genero tabla con la historia academica de los alumnos
		guarani::act('act_araucano')->cargar_tabla_historia_academica($responsable_academica);
		guarani::act('act_araucano')->vacuum('int_arau_nominal_ha', true);

		// -----------------------------------------------------------------------
		// Cargo las tablas con info para luego generar los archivos
		// -----------------------------------------------------------------------
		// Relevamiento de alumnos - Archivo Nro. 0.
		guarani::act('act_araucano')->eliminar_nominal_alumnos($anio_academico, $responsable_academica);
		guarani::act('act_araucano')->generar_nominal_alumnos($anio_academico, $responsable_academica);
		guarani::act('act_araucano')->vacuum('int_arau_nominal_alumnos', true);
		
		// Relevamiento de alumnos por oferta académica - Archivo Nro. 1.
		guarani::act('act_araucano')->eliminar_nominal_alumnos_oferta($anio_academico, $responsable_academica);
		guarani::act('act_araucano')->generar_nominal_alumnos_oferta($anio_academico, $responsable_academica);
		guarani::act('act_araucano')->vacuum('int_arau_nominal_oferta', true);

		// Relevamiento de materias aprobadas, regularizadas y exámenes rendidos - Archivos Nro. 2 y 3.
		guarani::act('act_araucano')->eliminar_nominal_actividades($anio_academico, $responsable_academica);
		guarani::act('act_araucano')->generar_nominal_actividades($anio_academico, $cuadros_4_y_5, $archivo_13, $responsable_academica);
		guarani::act('act_araucano')->vacuum('int_arau_nominal_actividades', true);
		if ($archivo_13) {
			guarani::act('act_araucano')->vacuum('int_arau_nominal_movilidad', true);
		}	

		// Se vuelven a habilitar los trigger de Auditoría
		guarani::act('act_araucano')->habilitar_trigger_auditoria();

		// -------------------------------------------------------
		// GENERO ARCHIVOS DE TEXTO.
		// -------------------------------------------------------
		// Se comprimen los archivos de texto.
		$nombre_archivo = isset($responsable_academica) ? "araucano_$responsable_academica.zip" : "araucano.zip";
		$archivo = $dir_name.$nombre_archivo;
		$zip = new ZipArchive();
		$zip->open($archivo, ZIPARCHIVE::CREATE);

		// Archivo 0 - Alumnos
		$filename = $dir_name.'archivo_0.txt';
		if (!file_exists($filename)) {
			$alumnos = toba::consulta_php('co_araucano')->get_nominal_alumnos($anio_academico, $responsable_academica);
			$handle = fopen($filename, 'w');
			foreach ($alumnos as $alumno) {
				fwrite($handle, implode('|', $alumno) . "\n");
			}
			fclose($handle);
		}
		$zip->addFile($filename, basename($filename));

		if ($archivo_13) {
			// Archivo 13 - Movilidad
			$filename = $dir_name.'archivo_13.txt';
			if (!file_exists($filename)) {
				$alumnos = toba::consulta_php('co_araucano')->get_nominal_alumnos_movilidad($anio_academico, $responsable_academica);
				$handle = fopen($filename, 'w');
				foreach ($alumnos as $alumno) {
					fwrite($handle, implode('|', $alumno) . "\n");
				}
				fclose($handle);
			}
			$zip->addFile($filename, basename($filename));
		}

		// Archivo 1 - Oferta
		$filename = $dir_name.'archivo_1.txt';
		if (!file_exists($filename)) {
			$alumnos_oferta = toba::consulta_php('co_araucano')->get_nominal_alumnos_oferta($anio_academico, $responsable_academica);
			$handle = fopen($filename, 'w');
			foreach ($alumnos_oferta as $alumno_oferta) {
				fwrite($handle, implode('|', $alumno_oferta) . "\n");
			}
			fclose($handle);
		}
		$zip->addFile($filename, basename($filename));

		// Archivo 2 - Actividades
		$filename = $dir_name.'archivo_2.txt';
		if (!file_exists($filename)) {
			$fecha_desde = $cuadros_4_y_5 ? "01/04/$anio_academico" : null;
			$actividades = toba::consulta_php('co_araucano')->get_nominal_actividades($anio_academico, $fecha_desde, null, $responsable_academica);
			$handle = fopen($filename, 'w');
			foreach ($actividades as $actividad) {
				fwrite($handle, implode('|', $actividad) . "\n");
			}
			fclose($handle);
		}
		$zip->addFile($filename, basename($filename));

		// Archivo 3 - Regularizadas
		$filename = $dir_name.'archivo_3.txt';
		if (!file_exists($filename)) {
			$actividades = toba::consulta_php('co_araucano')->get_nominal_actividades_regularizadas($anio_academico, $cuadros_4_y_5, $responsable_academica);
			$handle = fopen($filename, 'w');
			foreach ($actividades as $actividad) {
				fwrite($handle, implode('|', $actividad) . "\n");
			}
			fclose($handle);
		}
		$zip->addFile($filename, basename($filename));

		if ($cuadros_4_y_5) {
			// Archivo 4 - Cuadros 4 y 5
			$filename = $dir_name.'archivo_4.txt';
			if (!file_exists($filename)) {
				$handle = fopen($filename, 'w');
				/* Se cicla por año académico para partir la información y no generar tanto volumen de datos en memoria.
				El ciclo se inicia con el año académico anterior al seleccionado (porque el mismo ya está informado en el archivo 2).
				Se cicla 13 años hacia atrás. */
				for ($anio = $anio_academico-1; $anio >= $anio_academico-13; $anio--) {
					$fecha_desde = "01/04/$anio";
					$fecha_hasta = '31/03/'.($anio + 1);
					$actividades = toba::consulta_php('co_araucano')->get_nominal_actividades($anio_academico, $fecha_desde, $fecha_hasta, $responsable_academica);
					foreach ($actividades as $actividad) {
						fwrite($handle, implode('|', $actividad) . "\n");
					}
				}
				// Se agrega todo lo anterior a los 13 años de historia ya incluidos en el archivo.
				$fecha_hasta = '31/03/'.($anio_academico-13);
				$actividades = toba::consulta_php('co_araucano')->get_nominal_actividades($anio_academico, null, $fecha_hasta, $responsable_academica);
				foreach ($actividades as $actividad) {
					fwrite($handle, implode('|', $actividad) . "\n");
				}
				fclose($handle);
			}
			$zip->addFile($filename, basename($filename));
		}

		$zip->close();
		guarani_archivos::borrar_archivos_path($dir_name, array('txt'));
	}
}
?>
