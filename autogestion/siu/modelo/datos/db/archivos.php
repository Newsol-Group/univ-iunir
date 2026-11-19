<?php
namespace siu\modelo\datos\db;

use \SIU\Chulupi\kernel;

class archivos
{
	/**
	 * parametros: path, nombre, tamanio, extension, tmp_name
	 * cache: no
	 * filas: n
	 */
	function insertar_archivo($parametros)
	{
		$sql = "
			INSERT INTO rep_archivos (
				nombre_archivo,
				path_archivo,
				tamanio_en_kb,
				extension
			) VALUES (
				{$parametros['nombre']},
				{$parametros['path']},
				{$parametros['tamanio']},
				{$parametros['extension']}
			) RETURNING id_archivo;";
		$rs = kernel::db()->consultar_fila($sql);
		$upload = move_uploaded_file(str_replace('\\\\\\\\', '\\', substr($parametros['tmp_name'], 1, -1)), substr($parametros['path'], 1, -1).'/'.str_pad($rs['id_archivo'], 11, '0', STR_PAD_LEFT).'.rep');
		return $rs['id_archivo'];
	}

	/**
	 * parametros: id_archivo
	 * cache: no
	 * filas: n
	 */
	function eliminar_archivo($parametros)
	{
		return kernel::db()->ejecutar("DELETE FROM rep_archivos WHERE id_archivo = {$parametros['id_archivo']}");
	}
}