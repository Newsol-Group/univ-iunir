<?php
namespace siu\operaciones\_comun\operaciones\reporte;

use SIU\Chulupi\kernel;
use siu\guarani;
use SIU\Chulupi\util\u;

class generador_csv
{
	private $datos;
	private $columnas;
	private $nombre_archivo;

	public function __construct()
	{
		$this->datos = null;
		$this->columnas = null;
		$this->nombre_archivo = 'file';
	}

	public function set_datos(array $datos, array $columnas)
	{
		$this->datos = array();
		foreach($datos as $dato)
		{
			$linea = array();
			foreach($columnas as $col => $val)
			{
				if (isset($dato[$col]))
					$linea[$col] = $dato[$col];
				else
					$linea[$col] = null;
			}
			$this->datos[] = $linea;
		}
		$this->columnas = $columnas;
	}

	public function set_nombre_archivo($nombre)
	{
		$this->nombre_archivo = $nombre;
	}

	public function generar()
	{
		$nombre = uniqid(rand(), true).'.tmp';
		$archivo = fopen($nombre, 'w');

		if (!empty($this->datos) && !empty($this->columnas))
		{
			fputcsv($archivo, $this->columnas);
			foreach($this->datos as $linea)
				fputcsv($archivo, $linea);
		}
		fclose($archivo);

		$contenido = file_get_contents($nombre);
		$longitud = filesize($nombre);
		unlink($nombre);

		header("Cache-Control: private", false);
		header("Content-type: text/csv");
		header("Content-Length: ".$longitud);
		header("Content-Disposition: attachment; filename=".$this->nombre_archivo.".csv");
		header("Content-Transfer-Encoding: binary");
		header("Pragma: no-cache");
		header("Expires: 0");
		echo $contenido;
	}
}
?>