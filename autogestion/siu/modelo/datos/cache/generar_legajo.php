<?php
namespace siu\modelo\datos\cache;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;
use siu\modelo\controles\controles;

use SIU\Chulupi\util\db\db;
use SIU\Chulupi\util\ManejadorArchivos;
use SIU\Chulupi\util\ClaseDatos;

/**
 * Genera el cacha correspondiente a un legajo
 */
class generar_legajo
{
	protected $carrera;
	protected $legajo;
	protected $nro_inscripcion;
	protected $plan;
	protected $version;
	protected $dir;
	protected $base_clases;

	function __construct($carrera, $legajo, $nro_inscripcion, $plan, $version) 
	{
		//if($legajo=='01-24715193-00') throw new error_guarani('Error generando legajo: ' . $legajo);
		$this->carrera = $carrera;
		$this->legajo = $legajo;
		$this->nro_inscripcion = $nro_inscripcion;
		$this->plan = $plan;
		$this->version = $version;
		$ni = ManejadorArchivos::nombre_valido($this->nro_inscripcion);
		$this->dir = kernel::proyecto()->get_dir_cache() . '/legajos/datos/' . $ni ;
		if(is_dir($this->dir)){
			ManejadorArchivos::borrar_directorio($this->dir);
		}
		$this->base_clases = ManejadorArchivos::nombre_clase_valido('legajo__' . $this->legajo);
	}
	
	function procesar()
	{
		$vista_cursadas = new generar_legajo_vistas_cursada(	$this->carrera,
																$this->legajo,
																$this->nro_inscripcion,
																$this->plan,
																$this->version,
																$this->dir,
																$this->base_clases );
		$vista_cursadas->procesar();
	}
	
}
?>