<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\g3\g3;
use siu\modelo\g3\interface_personas;
use siu\preinscripcion;
use siu\modelo\propuesta;

class personas extends _consultas_bd implements interface_personas
{
	protected static $instancia;

	static function instancia()
	{
		$clase = substr(strrchr(get_class(), "\\"), 1);
		if (!isset(self::$instancia)) {
			self::$instancia = kernel::localizador()->instanciar("modelo\\g3\\consultas_bd\\$clase");
		}
		return self::$instancia;
	}
	
	//--------------------------------------------------------------------------
	//---- Implementación interface_personas -----------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Determina si un documento pertenece a un alumno ya registrado en Guaraní
	 * 
	 * @param $pais_documento
	 * @param $tipo_documento
	 * @param $nro_documento
	 * @return boolean
	 */
	function es_alumno($pais_documento, $tipo_documento, $nro_documento)
	{
		$pais_documento = kernel::db()->quote($pais_documento);
		$tipo_documento = kernel::db()->quote($tipo_documento);
		$nro_documento = kernel::db()->quote($nro_documento);
		
		$param_pre_preinsc_alumnos_solo_cursos = g3::parametros()->get_valor('pre_preinsc_alumnos_solo_cursos');
		if ($param_pre_preinsc_alumnos_solo_cursos == 'A') {
			$filtro_cursos = '';
		} else {
			$filtro_cursos = " AND sga_propuestas.propuesta_tipo <> '" . propuesta::tipo_CURSOS . "'";
		}
		
		$sql = "SELECT 
					1 as es_alumno
				FROM 
					mdp_personas_documentos,
					sga_alumnos,
					sga_propuestas
				WHERE
					mdp_personas_documentos.pais_documento = $pais_documento
					AND mdp_personas_documentos.tipo_documento = $tipo_documento
					AND mdp_personas_documentos.nro_documento = $nro_documento
					AND mdp_personas_documentos.persona = sga_alumnos.persona
					AND sga_alumnos.propuesta = sga_propuestas.propuesta
					$filtro_cursos
				";
		
		$datos = $this->consultar_fila($sql);	
		return $datos['es_alumno'] ?? false;
	}
	
	/**
	 * Determina si un documento pertenece a un docente ya registrado en Guaraní
	 * 
	 * @param $pais_documento
	 * @param $tipo_documento
	 * @param $nro_documento
	 * @return boolean
	 */
	function es_docente($pais_documento, $tipo_documento, $nro_documento)
	{
		$pais_documento = kernel::db()->quote($pais_documento);
		$tipo_documento = kernel::db()->quote($tipo_documento);
		$nro_documento = kernel::db()->quote($nro_documento);
		
		$sql = "SELECT 
					1 as es_docente
				FROM 
					mdp_personas_documentos,
					sga_docentes,
					sga_propuestas
				WHERE
					mdp_personas_documentos.pais_documento = $pais_documento
					AND mdp_personas_documentos.tipo_documento = $tipo_documento
					AND mdp_personas_documentos.nro_documento = $nro_documento
					AND mdp_personas_documentos.persona = sga_docentes.persona
				";
		
		$datos = $this->consultar_fila($sql);	
		return $datos['es_docente'];
		
		
	}
	
	
	function existe_persona_en_base($pais_documento, $tipo_documento, $nro_documento)
	{//sea por alumno o docente controlo que exista la persona
		$pais_documento = kernel::db()->quote($pais_documento);
		$tipo_documento = kernel::db()->quote($tipo_documento);
		$nro_documento = kernel::db()->quote($nro_documento);

		$sql = "SELECT 
					mptu.tipo_usuario as tipo_usuario
				FROM 
					mdp_personas_documentos
				JOIN mdp_personas_tipo_usuario as mptu ON  mptu.persona = mdp_personas_documentos.persona
				JOIN acc_tipos_usuarios as atu ON mptu.tipo_usuario = atu.tipo_usuario
				WHERE
					mdp_personas_documentos.pais_documento = $pais_documento
					AND mdp_personas_documentos.tipo_documento = $tipo_documento
					AND mdp_personas_documentos.nro_documento = $nro_documento
				";
		$datos = $this->consultar($sql);
		$perfiles = [];
		foreach ($datos as $dato) {
			$perfiles[$dato['tipo_usuario']] = $dato['tipo_usuario'];
		}
		$perfil_valido = $this->perfiles_validos($perfiles);
		return $perfil_valido;
	}

	function perfiles_validos($perfiles)
	{
		//El metodo consiste en validar el parametro pre_preinsc_alumnos_solo_cursos y en el caso de ser 'P'
		//Debo comprobar si el usuario es solo tipo curso, para este caso podra prinscribirse a propuesta
		//caso contrario, si el usuario ademas del perfil curso tiene un perfil, docente, administrativo3w o gestion hago la vista gorda =) 
		$array_perfiles_validos = [preinscripcion::perfil_alumno, //3w
			preinscripcion::perfil_administrador_3w, //3w
			preinscripcion::perfil_docente, //3w
			preinscripcion::perfil_gerencial, //3w
			//preinscripcion::perfil_preinscripcion, //segun parametro
			preinscripcion::perfil_cursos, //segun parametro
			preinscripcion::perfil_gestion//3w
		];

		$persona_verificada = false;
		if (!empty($perfiles)) {
			if (g3::parametros()->get_valor('pre_preinsc_alumnos_solo_cursos') == 'P') {
				//saco el perfil curso por que el parametro esta en P entonces se tiene que preinscribir por preinscripcion
				if (($key = array_search(preinscripcion::perfil_cursos, $array_perfiles_validos)) !== false) {
					unset($array_perfiles_validos[$key]);
				}
				foreach ($array_perfiles_validos as $perfil_valido) {
					if (in_array($perfil_valido, $perfiles)) {
						//tiene un perfil valido debe ir por 3w
						$persona_verificada = true;
						break;
					}
				}
			} else {
				//'pre_preinsc_alumnos_solo_cursos') == 'A' tiene perfiles y debe inscribirse por 3w
				$persona_verificada = true;
			}
		}

		return $persona_verificada;
	}

	/**
	 * Prueba las funciones de la interfaz
	 * 
	 * @param $id = array('pais_documento' => x, 'tipo_documento' => y, 'nro_documento' => 'z')
	 * @return array
	 */
	function test($id)
	{
		// Se testean las funciones particualres
		try {
			$datos = array();
			$datos['es_alumno'] = $this->es_alumno($id['pais_documento'], $id['tipo_documento'], $id['nro_documento']);
			$salida = array(
				'resultado' => true,
				'datos' => $datos,
				'msj' => 'OK',
			);
		} catch (\Exception $ex) {
			$salida = array(
				'resultado' => false,
				'msj' => $ex->getMessage(),
			);
		}
		return $salida;
	}

}