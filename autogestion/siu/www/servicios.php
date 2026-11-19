<?php

namespace g3w3_meter;

use siu\modelo\datos\estrategias;
use siu\modelo\entidades\parametro;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use SIU\Chulupi\util\cache\AdminCache;
use siu\bootstrap;

require_once(realpath(dirname(__FILE__).'/../bootstrap.php'));
bootstrap::cargar();

// Ver: http://php.net/manual/es/timezones.america.php
date_default_timezone_set('America/Argentina/Buenos_Aires');
ini_set("default_charset", "ISO-8859-1");
// Ver: http://php.net/manual/es/function.mb-internal-encoding.php
mb_internal_encoding("ISO-8859-1");

if (!defined('TEST_STRESS__NUNCA_ACTIVAR_EN_PRODUCCION') || (TEST_STRESS__NUNCA_ACTIVAR_EN_PRODUCCION != 1)) die();

//--------------------------------------------------------------------
//--------------------------------------------------------------------
$cronometrar = false;	

	$accion = (isset($_GET['accion'])) ? $_GET['accion'] : 'no';
	switch ($accion) {
		case 'datos':
			if($cronometrar){
				$microtime = explode(' ', microtime());
				$marca = $microtime[1] . substr($microtime[0], 1);
			}
			$caso = (isset($_GET['caso']) && is_integer($_GET['caso'])) ? $_GET['caso'] : 1;
			$instancias_casos = (isset($_GET['instancias_caso']) && is_numeric($_GET['instancias_caso'])) ? $_GET['instancias_caso'] : 10;
			$e = new extractor_de_casos($instancias_casos);
			$e->extraer_datos();
			//$e->info();
			if($cronometrar){
				$microtime = explode(' ', microtime());
				$marca2 = $microtime[1] . substr($microtime[0], 1);	
				echo "--T: " . ($marca2 - $marca);
			}
//			$datos = $e->get_datos();
//			foreach($datos as $dato){
//				echo $dato['usuario'] . "," . $dato['carrera'] . "," . $dato['mat'] . "," . $dato['com'] . "\n";
//			}
			break;
		case 'restaurar':
			test_utils::borrar_inscripciones();
			break;
		case 'resultado':
			echo test_utils::contar_inscripciones();
			break;
		default:
			die('no se definio una ACCION: datos, restaurar, resultado');
	}


//--------------------------------------------------------------------
	
class extractor_de_casos 
{
	protected $limit;
	protected $dao;
	protected $data = array();
	protected $count = 0;
	protected $consultas_materia = 0;
	protected $consultas_comision = 0;
	protected $no_com =0;
	
	function __construct($limit)
	{
		header("Content-Type: text/plain");
		AdminCache::set_activo(false);
		//ob_start();
		$this->registros = array();
		$this->dao = new estrategias\inscripcion_cursada();		
		$this->limit = $limit;
	}

	function extraer_datos()
	{
		foreach($this->get_datos_alumnos() as $alumno) {
			$instancia_mandatoria = parametro::get_valor('cur_instancia_mandatoria', $alumno['propuesta']);
			$hay_instancia = $instancia_mandatoria != 'ninguna';
			$materias = $this->get_datos_materias( $alumno['propuesta'],
													$alumno['alumno'],
													$alumno['persona'],
													$alumno['plan'],
													$alumno['plan_version'],
													$alumno['ubicacion']);
			$claves_materias = array_keys($materias);
			shuffle( $claves_materias );
			foreach($claves_materias as $id_materia){
				//if(isset($this->materias_sin_comisiones[$id_materia])) continue; //Evitar consultas innecesarias;
				$comisiones = $this->get_datos_comisiones( $alumno['propuesta'],
															$alumno['alumno'],
															$id_materia,
															$alumno['plan'],
															$alumno['plan_version'],
															$alumno['ubicacion'],
															$alumno['inicial_apellido']);
				if(\guarani_arreglos::count($comisiones)==0) {
					$this->no_com++;
				}
				//print_r($comisiones);
				$claves_comisiones = array_keys($comisiones);
				shuffle( $claves_comisiones );				
				foreach($claves_comisiones as $id_comision){
					if ($hay_instancia){ // Controla que la comision contenga la instancia requerida por el parametro
						$sql = "SELECT instancia FROM sga_comisiones_instancias WHERE comision = $id_comision AND instancia = $instancia_mandatoria";
						$temp = kernel::db()->consultar_fila($sql);
						if (empty($temp)) continue;
					}
					if(!isset($comisiones[$id_comision]['subcomisiones'])) { 
						// Comision sin subcomisiones
						echo	$alumno['usuario'] .','. 
								$alumno['propuesta'] .','. 
								$materias[$id_materia]['__ID__'] .','. 
								//"___".$id_comision.'___,'. 
								$comisiones[$id_comision]['__ID__'] . ",0\n";
						break 2;
					} else {
						// hay que elegir una subcom por cada tipo de clase
						$tipo_clase = array_keys($comisiones[$id_comision]['subcomisiones']);
						if(empty($tipo_clase)) throw new \Exception('ERROR SUBCOMISIONES: subcomisiones sin tipo de clase.');
						$out =	$alumno['usuario'] .','. 
								$alumno['propuesta'] .','. 
								$materias[$id_materia]['__ID__'] .','. 
								//"___".$id_comision.'___,'. 
								$comisiones[$id_comision]['__ID__'] . "," . \guarani_arreglos::count($tipo_clase);
						foreach($tipo_clase as $tipo){
							$subcoms = array_keys($comisiones[$id_comision]['subcomisiones'][$tipo]);
							if(empty($subcoms)) throw new \Exception('ERROR SUBCOMISIONES: tipos de clase sin instancias internas.');
							shuffle($subcoms);
							foreach($subcoms as $subcom){
								$id_subcom = $comisiones[$id_comision]['subcomisiones'][$tipo][$subcom]['__ID__'];
								$out .= ",$tipo,$id_subcom";
								break;
							}
						}
						echo $out . "\n";
						break 2;
					}
				}
			}
			if($this->count>$this->limit) return $this->data;
			$this->count++;
		}
		return $this->data;
	}	
	
	protected function get_datos_alumnos()
	{
		$sql = "	SELECT per.usuario, a.propuesta, a.alumno, a.persona, p.plan, a.plan_version, a.ubicacion,
						upper(substring(per.apellido,1,1)) as inicial_apellido
					FROM sga_alumnos as a,
						 mdp_personas as per,
						 sga_planes_versiones as p
					WHERE a.plan_version = p.plan_version
					AND per.persona = a.persona
					AND usuario is not null
					LIMIT ". ($this->limit * 2); //Algunos casos se van a descartar
		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		//print_r($datos);
		return $datos;
	}
	
	
	protected function get_datos_materias($propuesta, $alumno, $persona, $plan, $plan_version, $ubicacion)
	{
		$materias =	$this->dao->info__lista_materias(	$propuesta, 
														$alumno,
														$persona,
														$plan,
														$plan_version,
														$ubicacion);	
		$this->consultas_materia++;
		return $materias;
	}

	protected function get_datos_comisiones($propuesta, $alumno, $actividad, $plan, $plan_version, $ubicacion, $inicial)
	{
		$comisiones =	$this->dao->info__lista_comisiones(	$alumno,
															$actividad,
															$propuesta,
															$plan,
															$plan_version,
															$ubicacion,
															$inicial);	
		$this->consultas_comision++;
		return $comisiones;
	}
	
	function info()
	{
		echo "materias: " . $this->consultas_materia . " - comision: " . $this->consultas_comision ."- nosubcom:" .$this->no_subcom ."- nocom:" .$this->no_com. "\n";
	}
	
}	

class test_utils
{
	static function borrar_inscripciones()
	{
		$sql = "DELETE FROM  sga_insc_cursada_instancias;";
		kernel::db()->ejecutar($sql);
		$sql = "DELETE FROM  sga_insc_subcomision;";
		kernel::db()->ejecutar($sql);
		$sql = "DELETE FROM  sga_insc_cursada;";
		kernel::db()->ejecutar($sql);
	}
	
	static function contar_inscripciones()
	{
		$sql = "SELECT count(*) as cantidad from sga_insc_cursada;";
		$datos = kernel::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}
}