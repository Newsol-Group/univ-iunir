<?php
namespace siu\modelo\transacciones;

use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;

class cursos_virtuales extends transaccion
{

	const PLATAFORMA_SELECCIONADA_SESION = '__plataforma_seleccionada';
	
	protected $operacion = '800SIUCUR';
	
	protected $comision;
	
	// Moodle
	protected $plataforma;
	protected $plataformas;
	protected $datos_plataforma;
	protected $datos_comision;
	protected $datos_curso_virtual;
	protected $moodle_obj;
	protected $categorias_cursos_moodle;
	protected $cursos_moodle_de_docentes;
	protected $info_curso_virtual;
	protected $funciones_faltantes_moodle;
	
	protected $personas_para_crear;
	
	// Alumnos
	protected $alumnos_comision;
	protected $alumnos_eliminados_comision;
	protected $alumnos_curso_virtual_guarani;
	protected $alumnos_curso_virtual_moodle;
	protected $alumnos_para_matricular;
	protected $alumnos_para_desmatricular;
	
	// Docentes
	protected $docentes_comision;
	protected $roles_docentes;
	protected $docentes_curso_virtual_guarani;
	protected $docentes_curso_virtual_moodle;
	protected $docentes_para_matricular;
	protected $docentes_para_desmatricular;
	
	function get_plataforma_seleccionada()
	{
		$plataforma_seleccionada = null;
		if (kernel::sesion()->esta_seteada(static::PLATAFORMA_SELECCIONADA_SESION)) {
			$plataforma_seleccionada = kernel::sesion()->get(static::PLATAFORMA_SELECCIONADA_SESION);
		}
		return $plataforma_seleccionada;
	}
	
	function set_plataforma_seleccionada($plataforma_seleccionada)
	{
		kernel::sesion()->set(static::PLATAFORMA_SELECCIONADA_SESION, $plataforma_seleccionada);
	}
	
	function unset_plataforma_seleccionada()
	{
		kernel::sesion()->borrar(static::PLATAFORMA_SELECCIONADA_SESION);
	}
	
	function get_plataforma()
	{
		return $this->plataforma;
	}
	
	function set_plataforma($plataforma)
	{
		return $this->plataforma = $plataforma;
	}
	
	function get_comision()
	{
		return $this->comision;
	}
	
	function set_comision($comision)
	{
		return $this->comision = $comision;
	}
	
	function cargar_contexto_comision($comision)
	{
		$this->comision = $comision;
		$this->plataforma = $this->get_plataforma_seleccionada();
		$this->get_datos_plataforma();
		$this->get_datos_curso_virtual();
	}
	
	/**
	 * @return \moodle Objeto de la clase moodle.
	 */
	function get_moodle_obj()
	{
		
		if (!isset($this->moodle_obj)) {
			$datos_plataforma = $this->get_datos_plataforma();
			$this->moodle_obj = new \moodle($datos_plataforma['host'], $datos_plataforma['url'], $datos_plataforma['token']);
		}
		
		return $this->moodle_obj;
	}
	
	function limpiar_moodle_obj()
	{	
		$this->moodle_obj = null;
	}
	
	function get_funciones_faltantes_moodle()
    {	
        if (!isset($this->funciones_faltantes_moodle)) {
            $this->funciones_faltantes_moodle = $this->get_moodle_obj()->verificarFunciones();
        }
		
        return $this->funciones_faltantes_moodle;
    }
	
	function limpiar_funciones_faltantes_moodle()
    {	
        unset($this->funciones_faltantes_moodle);
    }
	
	function get_categorias_cursos_moodle()
    {	
        if (!isset($this->categorias_cursos_moodle)) {
            $this->categorias_cursos_moodle = $this->get_moodle_obj()->getCategoriasCursos();
        }
		
        return $this->categorias_cursos_moodle;
    }
	
	function limpiar_categorias_cursos_moodle()
    {	
        $this->categorias_cursos_moodle = true;
    }
	
	function get_cursos_moodle_de_docentes()
	{	
		
		if (!isset($this->cursos_moodle_de_docentes)) {
			
            // Obtengo los docentes de la comisión
			$docentes_comision = $this->get_docentes_comision();

			// Obtengo los docentes ya creados en Moodle
			$docentes_moodle = array_filter($docentes_comision, function($docente) {
				return !is_null($docente['usuario_moodle']);
			});

			$docentes_moodle = array_column($docentes_moodle, 'usuario_moodle');
			
			$this->cursos_moodle_de_docentes = $this->get_moodle_obj()->getCursosByUsuarios($docentes_moodle);
			
        }
		
        return $this->cursos_moodle_de_docentes;
	}
	
	function limpiar_cursos_moodle_de_docentes()
	{	
		$this->cursos_moodle_de_docentes = null;
	}
	
	function get_plataformas()
    {	
        if (!isset($this->plataformas)) {
            $this->plataformas = catalogo::consultar('moodle', 'get_plataformas_by_comision', array('comision' => $this->comision));
        }
		
        return $this->plataformas;
    }
	
	function limpiar_plataformas()
    {	
		$this->plataformas = null;
        catalogo::limpiar_cache('moodle', 'get_plataformas_by_comision', array('comision' => $this->comision));
    }
	
	function get_datos_plataforma()
    {	
        if (!isset($this->datos_plataforma)) {
            $this->datos_plataforma = catalogo::consultar('moodle', 'get_plataforma_educacion_virtual', array('plataforma' => $this->plataforma));
        }
		
        return $this->datos_plataforma;
    }
	
	function limpiar_datos_plataforma()
    {	
		$this->datos_plataforma = null;
        catalogo::limpiar_cache('moodle', 'get_plataforma_educacion_virtual', array('plataforma' => $this->plataforma));
    }
	
	function set_datos_plataforma($datos_plataforma)
    {	
		$this->set_plataforma_seleccionada($datos_plataforma['plataforma']);
		$this->datos_plataforma = $datos_plataforma;
    }
	
	function get_datos_curso_virtual()
    {
        if (!isset($this->datos_curso_virtual)) {
            $this->datos_curso_virtual = catalogo::consultar('moodle', 'get_curso_virtual_by_comision', array('comision' => $this->comision));
        }
		
        return $this->datos_curso_virtual;
    }
	
	function limpiar_datos_curso_virtual()
    {
        $this->datos_curso_virtual = null;
		catalogo::limpiar_cache('moodle', 'get_curso_virtual_by_comision', array('comision' => $this->comision));
    }
	
	function get_datos_comision()
	{
		if (!isset($this->datos_comision)) {
			$this->datos_comision = catalogo::consultar('moodle', 'get_datos_comision', array('comision' => $this->comision));
		}

		return $this->datos_comision;
	}

	function limpiar_datos_comision()
	{
		$this->datos_comision = null;
		catalogo::limpiar_cache('moodle', 'get_datos_comision', array('comision' => $this->comision));
	}

	function get_info_curso_virtual()
	{
		if (!isset($this->info_curso_virtual)) {
			$datos_curso_virtual = $this->get_datos_curso_virtual();
            $this->info_curso_virtual = $this->get_moodle_obj()->getInfoCurso($datos_curso_virtual['id_curso_externo']);
        }
		
        return $this->info_curso_virtual;
	}
	
	function limpiar_info_curso_virtual()
    {
        $this->info_curso_virtual = null;
    }
	
	function eliminar_curso_guarani($curso)
	{
		catalogo::consultar('moodle', 'eliminar_curso', array('curso' => $curso));
		$this->limpiar_datos_curso_virtual();
		$this->limpiar_info_curso_virtual();
	}
	
	//*************************************************************************
	//********************************Cursos***********************************
	//*************************************************************************
	
	function crear_curso_moodle($fullname, $shortname, $categoryid, $summary, $visible, $startdate = null, $enddate = null)
	{
		$datos_plataforma = $this->get_datos_plataforma();
		
		return $this->get_moodle_obj()->crearAsociarCursoComision(
				$datos_plataforma['plataforma'],
				$this->comision, 
				$fullname, 
				$shortname, 
				$categoryid, 
				$summary, 
				$visible, 
				$startdate, 
				$enddate,
				true
			);
	}
	
	function importar_contenido_curso($curso_desde)
	{
		$datos_curso_virtual = $this->get_datos_curso_virtual();
		return $this->get_moodle_obj()->importarContenidoCurso($curso_desde, $datos_curso_virtual['id_curso_externo'], true);
	}
	
	//*************************************************************************
	//********************************Fin Cursos*******************************
	//*************************************************************************
	
	
	//*************************************************************************
	//********************************Docentes*********************************
	//*************************************************************************
	
	function get_docentes_comision()
    {
        if (!isset($this->docentes_comision)) {
            $this->docentes_comision = catalogo::consultar('moodle', 'get_docentes_comision_moodle', array('comision' => $this->comision, 'plataforma' => $this->plataforma));
        }
		
        return $this->docentes_comision;
    }
	
	function limpiar_docentes_comision()
    {
		$this->docentes_comision = null;
        catalogo::limpiar_cache('moodle', 'get_docentes_comision_moodle', array('comision' => $this->comision, 'plataforma' => $this->plataforma));
    }
	
	function get_roles_docentes_by_plataforma()
    {
        if (!isset($this->roles_docentes)) {
			$datos_plataforma = $this->get_datos_plataforma();
            $this->roles_docentes = catalogo::consultar('moodle', 'get_roles_docentes_by_plataforma', array('plataforma' => $datos_plataforma['plataforma']));
        }
		
        return $this->roles_docentes;
    }
	
	function limpiar_roles_docentes_by_plataforma()
    {
		$this->roles_docentes = null;
		$datos_plataforma = $this->get_datos_plataforma();
        catalogo::limpiar_cache('moodle', 'get_roles_docentes_by_plataforma', array('plataforma' => $datos_plataforma['plataforma']));
    }
	
	function get_docentes_curso_virtual_guarani()
    {
        if (!isset($this->docentes_curso_virtual_guarani)) {
			$datos_curso_virtual = $this->get_datos_curso_virtual();
            $this->docentes_curso_virtual_guarani = catalogo::consultar('moodle', 'get_docentes_curso_moodle', array('curso' => $datos_curso_virtual['curso']));
        }
		
        return $this->docentes_curso_virtual_guarani;
    }
	
	function limpiar_docentes_curso_virtual_guarani()
    {
		$this->docentes_curso_virtual_guarani = null;
		$datos_curso_virtual = $this->get_datos_curso_virtual();
        catalogo::limpiar_cache('moodle', 'get_docentes_curso_moodle', array('curso' => $datos_curso_virtual['curso']));
    }
	
	function get_docentes_curso_virtual_moodle()
	{
		if (!isset($this->docentes_curso_virtual_moodle)) {
			$datos_curso_virtual = $this->get_datos_curso_virtual();
			$curso_externo = $datos_curso_virtual['id_curso_externo'];
			$roles_docentes_by_plataforma = $this->get_roles_docentes_by_plataforma();
			$roles_docentes = array_unique(array_column($roles_docentes_by_plataforma, 'rol_externo'));

			$this->docentes_curso_virtual_moodle = $this->get_moodle_obj()->getDocentesByCurso($curso_externo, $roles_docentes);
		}
		
		return $this->docentes_curso_virtual_moodle;
	}
	
	function limpiar_docentes_curso_virtual_moodle()
    {
		$this->docentes_curso_virtual_moodle = null;
    }
	
	function limpiar_docentes()
	{
		$this->limpiar_docentes_comision();
		$this->limpiar_docentes_curso_virtual_guarani();
		$this->limpiar_docentes_curso_virtual_moodle();
	}
	
	function coinciden_docentes_moodle($es_desvinculacion = false)
	{
		
		if ($es_desvinculacion) {
			$docentes_comision = [];
		} else {
			$docentes_comision = $this->get_docentes_comision();
		}
		
		$docentes_curso_virtual_guarani = $this->get_docentes_curso_virtual_guarani();
		$docentes_curso_virtual_moodle = $this->get_docentes_curso_virtual_moodle();
		
		list(	$coinciden_docentes_moodle,
				$this->personas_para_crear,
				$this->docentes_para_matricular,
				$this->docentes_para_desmatricular) = \moodle::coincidenDocentesMoodle(
							$docentes_comision,
							$docentes_curso_virtual_guarani,
							$docentes_curso_virtual_moodle);

		return $coinciden_docentes_moodle;
		
	}
		
	function actualizar_docentes_moodle($es_desvinculacion = false)
	{
		
		$exito_actualizacion_msg = kernel::traductor()->trans('moodle.exito_actualizacion_docentes_moodle');
		
		try {
			
			// Si no hay que actualizar en Moodle
			if ($this->coinciden_docentes_moodle($es_desvinculacion)) {
				
				return [true, [$exito_actualizacion_msg]];

			} else { // sino
			
				$datos_plataforma = $this->get_datos_plataforma();
				$datos_curso_virtual = $this->get_datos_curso_virtual();
				
				// Si hay usuarios para crear en Moodle
				if (!empty($this->personas_para_crear)) {

					$personas_ids = array_column($this->personas_para_crear, 'persona');
					$emails_personas = \toba::consulta_php('co_personas')->get_emails_personas($personas_ids, false);

					$usuarios = [];
					$personas_usuarios = [];
					$personas_sin_email = [];

					foreach ($this->personas_para_crear as $key => $persona) {
						
						// Si la persona no tiene email asignado no lo matriculo en Moodle
						if (empty($emails_personas[$persona['persona']]['email'])) {
							$personas_sin_email[] = "{$persona['apellido']} {$persona['nombres']}";
							continue;
						}
						
						$usuarios[$key]['username'] = $persona['usuario'];
						$usuarios[$key]['firstname'] = $persona['nombres'];
						$usuarios[$key]['lastname'] = $persona['apellido'];
						$usuarios[$key]['email'] = $emails_personas[$persona['persona']]['email'];
						$usuarios[$key]['idnumber'] = $persona['persona'];

						$personas_usuarios[$persona['usuario']] = $persona;
					}

					// Si hay personas sin email asignado
					if (!empty($personas_sin_email)) {
						$personas_falta_email_msg = kernel::traductor()->trans('moodle.personas_falta_email_matriculacion_moodle', ['%rol%' => 'docente/s', '%personas%' => implode(', ', $personas_sin_email)]);
						return [false, [$personas_falta_email_msg]];
					}
					$lote_usuarios = array_chunk($usuarios,1000, true);
                                        
                                        foreach ($lote_usuarios as $key => $usuarios) { 
                                       
                                            $usuarios_moodle = $this->get_moodle_obj()->crearUsuarios($usuarios, true);

                                            // Si hay usuarios a guardar en la DB
                                            if (!empty($usuarios_moodle)) {

                                                    $usuarios_pv = [];
                                                    foreach ($usuarios_moodle as $key => $usuario_moodle) {

                                                            // Agrego los usuarios para matricular y luego los guardo en la DB
                                                            $usuarios_pv[] = [$datos_plataforma['plataforma'], $personas_usuarios[$usuario_moodle['username']]['persona'], $usuario_moodle['id']];
                                                            $personas_usuarios[$usuario_moodle['username']]['usuario_moodle'] = $usuario_moodle['id'];
                                                            $this->docentes_para_matricular[] = $personas_usuarios[$usuario_moodle['username']];

                                                    }

                                                    \guarani::act('act_plataformas_virtuales')->insertar_usuarios_pv_masivo($usuarios_pv);

                                            }
                                        
                                        }
					
				}
				
				// Si hay usuarios para matricular en Moodle
				if (!empty($this->docentes_para_matricular)) {

					$matriculaciones = [];

					foreach ($this->docentes_para_matricular as $key => $docente_para_matricular) {
						$matriculaciones[$key]['roleid'] = $docente_para_matricular['rol_moodle'];
						$matriculaciones[$key]['userid'] = $docente_para_matricular['usuario_moodle'];
						$matriculaciones[$key]['courseid'] = $datos_curso_virtual['id_curso_externo'];
					}
                                        $lote_matriculaciones = array_chunk($matriculaciones,1000, true);
                                        foreach ($lote_matriculaciones as $key => $matriculaciones) {
                                            $this->get_moodle_obj()->matricularUsuarios($matriculaciones, true);
                                        }
				}
				
				// Si hay usuarios para desmatricular en Moodle
				if (!empty($this->docentes_para_desmatricular)) {

					$desmatriculaciones = [];

					foreach ($this->docentes_para_desmatricular as $key => $docente_para_desmatricular) {
						$desmatriculaciones[$key]['userid'] = $docente_para_desmatricular;
						$desmatriculaciones[$key]['courseid'] = $datos_curso_virtual['id_curso_externo'];
					}
                                        $lote_desmatriculaciones = array_chunk($desmatriculaciones,1000, true);
                                        foreach ($lote_desmatriculaciones as $key => $desmatriculaciones) { 
                                            $this->get_moodle_obj()->desmatricularUsuarios($desmatriculaciones, true);
					}
				}

			}
			
			return [true, [$exito_actualizacion_msg]];
		
		} catch (\Exception $e) {
			
			kernel::log()->add_error($e);
			$fallo_actualizacion_msg = kernel::traductor()->trans('moodle.fallo_actualizacion_docentes_moodle', ['%moodle_msj%' => $e->getMessage()]);
			return [false, [$fallo_actualizacion_msg]];
			
		}
		
	}
	
	//*************************************************************************
	//*****************************FIN DOCENTES********************************
	//*************************************************************************
	
	
	//*************************************************************************
	//********************************ALUMNOS**********************************
	//*************************************************************************
	
	function get_alumnos_comision($es_desvinculacion = false)
	{
		if (!isset($this->alumnos_comision)) {
			// Si es "Desvinculación" también debo pasar la plataforma
			$plataforma = ($es_desvinculacion) ? $this->plataforma : null;
			$parametros = [
				'comision' => $this->comision,
				'plataforma' => $plataforma
			];
			$this->alumnos_comision = catalogo::consultar('moodle', 'get_alumnos_inscriptos_a_comision_moodle', $parametros);
		}

		return $this->alumnos_comision;
	}

	function limpiar_alumnos_comision($es_desvinculacion = false)
	{
		// Si es "Desvinculación" también debo pasar la plataforma
		if ($es_desvinculacion) {
			catalogo::limpiar_cache('moodle', 'get_alumnos_inscriptos_a_comision_moodle', [
				'comision' => $this->comision,
				'plataforma' => $this->plataforma
				]
			);
		}

		catalogo::limpiar_cache('moodle', 'get_alumnos_inscriptos_a_comision_moodle', [
			'comision' => $this->comision,
			'plataforma' => null
			]
		);
		// Vacio el array
		$this->alumnos_comision = null;
	}

	function get_alumnos_eliminados_comision($es_desvinculacion = false)
	{
		if (!isset($this->alumnos_eliminados_comision)) {
			// Si es "Desvinculación" también debo pasar la plataforma
			$plataforma = ($es_desvinculacion) ? $this->plataforma : null;
			$parametros = [
				'comision' => $this->comision,
				'plataforma' => $plataforma
			];
			$this->alumnos_eliminados_comision = catalogo::consultar('moodle', 'get_alumnos_eliminados_a_comision_moodle', $parametros);
		}

		return $this->alumnos_eliminados_comision;
	}

	function limpiar_alumnos_eliminados_comision($es_desvinculacion = false)
	{
		// Si es "Desvinculación" también debo pasar la plataforma
		if ($es_desvinculacion) {
			catalogo::limpiar_cache('moodle', 'get_alumnos_eliminados_a_comision_moodle', [
				'comision' => $this->comision,
				'plataforma' => $this->plataforma
				]
			);
		}

		catalogo::limpiar_cache('moodle', 'get_alumnos_eliminados_a_comision_moodle', [
			'comision' => $this->comision,
			'plataforma' => null
			]
		);
		// Vacio el array
		$this->alumnos_eliminados_comision = null;
	}

	function get_alumnos_curso_virtual_guarani()
    {
        if (!isset($this->alumnos_curso_virtual_guarani)) {
			$datos_curso_virtual = $this->get_datos_curso_virtual();
            $this->alumnos_curso_virtual_guarani = catalogo::consultar('moodle', 'get_alumnos_inscriptos_a_curso_moodle', array('curso' => $datos_curso_virtual['curso']));
        }
		
        return $this->alumnos_curso_virtual_guarani;
    }
	
	function limpiar_alumnos_curso_virtual_guarani()
    {	
		$this->alumnos_curso_virtual_guarani = null;
		$datos_curso_virtual = $this->get_datos_curso_virtual();
		catalogo::limpiar_cache('moodle', 'get_alumnos_inscriptos_a_curso_moodle', array('curso' => $datos_curso_virtual['curso']));
    }
	
	function get_alumnos_curso_virtual_moodle()
	{
		if (!isset($this->alumnos_curso_virtual_moodle)) {
			
			$datos_curso_virtual	= $this->get_datos_curso_virtual();
			$datos_plataforma		= $this->get_datos_plataforma();
			
			$curso_externo			= $datos_curso_virtual['id_curso_externo'];
			$rol_alumno				= $datos_plataforma['rol_alumno'];
			$rol_curso				= $datos_plataforma['rol_alumno_curso'];

			$this->alumnos_curso_virtual_moodle = $this->get_moodle_obj()->getAlumnosByCurso($curso_externo, $rol_alumno, $rol_curso);
		
		}
		
		return $this->alumnos_curso_virtual_moodle;
	}
	
	function limpiar_alumnos_curso_virtual_moodle()
    {
		$this->alumnos_curso_virtual_moodle = null;
    }
	
	function limpiar_alumnos($es_desvinculacion = false)
    {
		$this->limpiar_alumnos_comision($es_desvinculacion);
		$this->limpiar_alumnos_eliminados_comision($es_desvinculacion);
		$this->limpiar_alumnos_curso_virtual_guarani();
		$this->limpiar_alumnos_curso_virtual_moodle();
    }
	
	function coinciden_alumnos_moodle($es_desvinculacion = false)
	{
		
		$alumnos_comision = $this->get_alumnos_comision($es_desvinculacion);
		$alumnos_eliminados = $this->get_alumnos_eliminados_comision($es_desvinculacion);
		$alumnos_curso_virtual_guarani = $this->get_alumnos_curso_virtual_guarani();
		$alumnos_curso_virtual_moodle = $this->get_alumnos_curso_virtual_moodle();
		
		list(	$coinciden_alumnos_moodle,
				$this->personas_para_crear,
				$this->alumnos_para_matricular,
				$this->alumnos_para_desmatricular) = \moodle::coincidenAlumnosMoodle(
							$alumnos_comision,
							$alumnos_eliminados,
							$alumnos_curso_virtual_guarani,
							$alumnos_curso_virtual_moodle,
							$es_desvinculacion);

		return $coinciden_alumnos_moodle;
		
	}

	function actualizar_alumnos_moodle($es_desvinculacion = false)
	{
		
		$exito_actualizacion_msg = kernel::traductor()->trans('moodle.exito_actualizacion_alumnos_moodle');
		
		try {
			
			// Si no hay que actualizar en Moodle
			if ($this->coinciden_alumnos_moodle($es_desvinculacion)) {
				
				return [true, [$exito_actualizacion_msg]];

			} else { // sino
			
				$datos_plataforma = $this->get_datos_plataforma();
				$datos_curso_virtual = $this->get_datos_curso_virtual();
				
				// Si hay usuarios para crear en Moodle
				if (!empty($this->personas_para_crear)) {

					$personas_ids = array_column($this->personas_para_crear, 'persona');
					$emails_personas = \toba::consulta_php('co_personas')->get_emails_personas($personas_ids, false);

					$usuarios = [];
					$personas_usuarios = [];
					$personas_sin_email = [];

					foreach ($this->personas_para_crear as $key => $persona) {
						
						// Si la persona no tiene email asignado no lo matriculo en Moodle
						if (empty($emails_personas[$persona['persona']]['email'])) {
							$personas_sin_email[] = "{$persona['apellido']} {$persona['nombres']}";
							continue;
						}
						
						$usuarios[$key]['username'] = $persona['usuario'];
						$usuarios[$key]['firstname'] = $persona['nombres'];
						$usuarios[$key]['lastname'] = $persona['apellido'];
						$usuarios[$key]['email'] = $emails_personas[$persona['persona']]['email'];
						$usuarios[$key]['idnumber'] = $persona['persona'];

						$personas_usuarios[$persona['usuario']] = $persona;
					}

					// Si hay personas sin email asignado
					if (!empty($personas_sin_email)) {
						$personas_falta_email_msg = kernel::traductor()->trans('moodle.personas_falta_email_matriculacion_moodle', ['%rol%' => 'alumno/s', '%personas%' => implode(', ', $personas_sin_email)]);
						return [false, [$personas_falta_email_msg]];
					}
					$lote_usuarios = array_chunk($usuarios,1000, true);
                                        
                                        foreach ($lote_usuarios as $key => $usuarios) { 
                                       
                                            $usuarios_moodle = $this->get_moodle_obj()->crearUsuarios($usuarios, true);

                                            // Si hay usuarios a guardar en la DB
                                            if (!empty($usuarios_moodle)) {

                                                    $usuarios_pv = [];
                                                    foreach ($usuarios_moodle as $key => $usuario_moodle) {

                                                            // Agrego los usuarios para matricular y luego los guardo en la DB
                                                            $usuarios_pv[] = [$datos_plataforma['plataforma'], $personas_usuarios[$usuario_moodle['username']]['persona'], $usuario_moodle['id']];
                                                            $personas_usuarios[$usuario_moodle['username']]['usuario_moodle'] = $usuario_moodle['id'];
                                                            $this->alumnos_para_matricular[] = $personas_usuarios[$usuario_moodle['username']];

                                                    }

                                                    \guarani::act('act_plataformas_virtuales')->insertar_usuarios_pv_masivo($usuarios_pv);

                                            }
                                        }
				}

				// Si hay usuarios para matricular en Moodle
				if (!empty($this->alumnos_para_matricular)) {

					$matriculaciones = [];

					foreach ($this->alumnos_para_matricular as $key => $alumno_para_matricular) {
						
						// Si se trata de una propuesta tipo curso
						if ($alumno_para_matricular['propuesta_tipo'] == \propuesta::tipo_curso) {
							$role_moodle = $datos_plataforma['rol_alumno_curso'];
						} else { // sino
							$role_moodle = $datos_plataforma['rol_alumno'];
						}
				
						$matriculaciones[$key]['roleid'] = $role_moodle;
						$matriculaciones[$key]['userid'] = $alumno_para_matricular['usuario_moodle'];
						$matriculaciones[$key]['courseid'] = $datos_curso_virtual['id_curso_externo'];
					}
                                        $lote_matriculaciones = array_chunk($matriculaciones,1000, true);
                                        foreach ($lote_matriculaciones as $key => $matriculaciones) {
                                            $this->get_moodle_obj()->matricularUsuarios($matriculaciones, true);
                                        }
				}

				// Si hay usuarios para desmatricular en Moodle
				if (!empty($this->alumnos_para_desmatricular)) {

					$desmatriculaciones = [];

					foreach ($this->alumnos_para_desmatricular as $key => $alumno_para_desmatricular) {
						$desmatriculaciones[$key]['userid'] = $alumno_para_desmatricular;
						$desmatriculaciones[$key]['courseid'] = $datos_curso_virtual['id_curso_externo'];
					}
                                        $lote_desmatriculaciones = array_chunk($desmatriculaciones,1000, true);
                                        foreach ($lote_desmatriculaciones as $key => $desmatriculaciones) { 
                                            $this->get_moodle_obj()->desmatricularUsuarios($desmatriculaciones, true);
                                        }
				}

			}
			
			return [true, [$exito_actualizacion_msg]];
		
		} catch (\Exception $e) {
			
			kernel::log()->add_error($e);
			$fallo_actualizacion_msg = kernel::traductor()->trans('moodle.fallo_actualizacion_alumnos_moodle', ['%moodle_msj%' => $e->getMessage()]);
			return [false, [$fallo_actualizacion_msg]];
			
		}
		
	}
	
	//*************************************************************************
	//*****************************FIN ALUMNOS*********************************
	//*************************************************************************

	//*************************************************************************
	//*****************************VINCULAR************************************
	//*************************************************************************
	
	function vincular_curso_virtual($curso)
	{
		
		$vincular_curso_ok = false;
		
		try {
			
			kernel::db()->abrir_transaccion();
			
			$datos_plataforma = $this->get_datos_plataforma();
			
			$curso_externo = catalogo::consultar('moodle', 'get_datos_curso_externo', array('curso' => $curso, 'plataforma' => $datos_plataforma['plataforma']));
			
			// Si el curso externo no se vinculï¿½ con Guaranï¿½
			if (empty($curso_externo)) {
				// Guardo el curso en la DB
				$curso_id = \guarani::act('act_plataformas_virtuales')->insertar_curso_pv($datos_plataforma['plataforma'], $curso);
			} else { // sino
				$curso_id = $curso_externo['curso'];
			}
			
				
			// Vinculo el curso con la comisiï¿½n
			\guarani::act('act_plataformas_virtuales')->insertar_curso_comision_pv($curso_id, $this->comision);
			
			kernel::db()->cerrar_transaccion();
			
			$vincular_curso_ok = true;
			
		} catch (\Exception $e) {
			
			kernel::db()->abortar_transaccion();
			
			kernel::log()->add_error($e);
			
			$vincular_curso_ok = false;
			
		}
		
		return $vincular_curso_ok;
	}
	
	//*************************************************************************
	//*****************************FIN VINCULAR********************************
	//*************************************************************************
        
	//validar si la comision tiene actas cerradas
        function validar_tiene_actas_cerradas ($comision)
        {
            return $actas_cerradas = \toba::consulta_php('co_comisiones')->tiene_acta_cursada_promocion_cerrada($comision);
            
        }

        //*************************************************************************
	//***************************** DESVINCULAR *******************************
	//*************************************************************************

        function eliminar_grupo_curso_moodle()
	{

            $eliminacion_grupo_ok = true;

            $datos_curso_virtual = $this->get_datos_curso_virtual();

            $grupo = $datos_curso_virtual['grupo'] ?? \comunes::no;
            // Si tiene un grupo asociado lo elimino
            if ($grupo != \comunes::no) {

                try {
                    $this->get_moodle_obj()->borrarGrupos([$grupo], true);
                } catch (\Exception $e) {
                    $eliminacion_grupo_ok = false;
                }
            
            }

            return $eliminacion_grupo_ok;
        }
}
