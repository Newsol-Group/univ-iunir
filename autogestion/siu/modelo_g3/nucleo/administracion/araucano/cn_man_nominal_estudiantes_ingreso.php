<?php
/**
 *
 * @package		Administración
 * @subpackage 	Araucano
 */
class cn_man_nominal_estudiantes_ingreso extends guarani_pers_cn
{
	function generar($anio_academico, $responsable_academica=null)
	{
		$dir_name = toba::proyecto()->get_path_temp().'/araucano/estudiantes_ingreso/'.$anio_academico.'/';
		toba_manejador_archivos::crear_arbol_directorios($dir_name);
		guarani_archivos::borrar_archivos_path($dir_name, array('zip'));

		// -------------------------------------------------------
		// GENERO ARCHIVOS DE TEXTO.
		// -------------------------------------------------------
		// Se comprimen los archivos de texto.
		$nombre_archivo = isset($responsable_academica) ? "araucano_$responsable_academica.zip" : "araucano.zip";
		$archivo = $dir_name.$nombre_archivo;
		$zip = new ZipArchive();
		$zip->open($archivo, ZIPARCHIVE::CREATE);

		$filename = $dir_name.'archivo_ingreso.txt';
		if (!file_exists($filename)) {
			$alumnos = toba::consulta_php('co_araucano')->get_nominal_estudiantes_ingreso($anio_academico, $responsable_academica);
			$handle = fopen($filename, 'w');
			foreach ($alumnos as $alumno) {
				fwrite($handle, implode('|', $alumno) . "\n");
			}
			fclose($handle);
		}
		$zip->addFile($filename, basename($filename));

		$zip->close();
		guarani_archivos::borrar_archivos_path($dir_name, array('txt'));
	}
}
?>
