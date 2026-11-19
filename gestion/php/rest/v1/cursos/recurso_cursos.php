<?php

namespace SIU\Guarani\php\rest\v1\cursos;

use SIUToba\rest\lib\rest_validador;
use SIUToba\rest\rest;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v1\modelos\rest_cursos;

class recurso_cursos implements modelable
{
	/**
	 * @var rest_cursos
	 */
	protected $modelo;
        
	function __construct()
	{
		$this->modelo = \guarani::rest(rest_cursos::class);
	}

	public static function _get_modelos()
	{
		
		//Datos de la Plataforma
		$plataforma = array(
			'id' => array(
				'type' => 'integer',
				'_validar' => array(rest_validador::OBLIGATORIO)),
			'nombre' => array(
				'type' => 'string',
				'_validar' => array(rest_validador::OBLIGATORIO)),
		);
		
		//Datos del Curso
		$curso = array(
			'id_curso' => array(
				'type' => 'integer',
				'_validar' => array(rest_validador::OBLIGATORIO)),
			'id_curso_externo' => array(
				'type' => 'integer',
				'_validar' => array(rest_validador::OBLIGATORIO)),
			'plataforma' => array(
				'_mapeo' => 'plataforma_arr',
				'$ref' => '#/definitions/Plataforma'),
			'comisiones' => array(
				'type' => 'array',
				'items' => array('type' => 'integer'))
		);

		// Datos del curso (para creación)
		$cursoPost = array(
			'plataforma' => array(
				'type' => 'integer',
				'_validar' => array(rest_validador::OBLIGATORIO, rest_validador::TIPO_INT)),
			'id_curso_externo' => array(
				'type' => 'integer',
				'_validar' => array(rest_validador::OBLIGATORIO, rest_validador::TIPO_INT))
		);
		
		// Datos del curso (para modificación)
		$cursoPut = array(
			'plataforma' => array(
				'type' => 'integer',
				'_validar' => array(rest_validador::TIPO_INT)),
			'id_curso_externo' => array(
				'type' => 'integer',
				'_validar' => array(rest_validador::TIPO_INT))
		);
		
		//Datos del alumno
		$inscripcion = array('estado'		=> array('type'		=> 'string'),
							 'descripcion'	=> array('type'		=> 'string'));
		
		$alumnoCurso = array(
			'legajo' => array('type' => 'string'),
			'documento_tipo' => array('type' => 'string'),
			'documento_nro' => array('type' => 'string'),
			'apellido' => array('type' => 'string'),
			'nombres' => array('type' => 'string'),
			'instancias' => array('type' => 'array',
			'items' => array('type' => 'string'),
			'_mapeo' => 'nombre_instancias'),
			'inscripcion' => array('_mapeo' => 'inscripcion_arr',
			'$ref' => '#/definitions/InscripcionCurso'),
		);

		return array(
			'Curso'				=> $curso,
			'CursoPost'			=> $cursoPost,
			'CursoPut'			=> $cursoPut,
			'InscripcionCurso'	=> $inscripcion,
			'AlumnoCurso'		=> $alumnoCurso,
			'Plataforma'		=> $plataforma,
		);
	}

	
	/**
     * POST /cursos
     *
     * Crea un nuevo curso
     *
     * @param_body $curso CursoPost [required] Campos de un curso.
     *
     * @summary Creación del curso
     *
     * @responses  201 El curso se creó con exito
     * @responses  400 Errores de validación
     * @responses  404 Plataforma no encontrada
     * @responses  500 Falló la creación del curso
     **/
    function post_list()
    {
        $curso = $this->modelo->post(rest::request()->get_body_json());
        rest::response()->post(array('id_curso' => $curso));
    }
	
	/**
	 * PUT /cursos/id_curso
	 * 
	 * Modifica un curso existente
	 *        
	 * @param_body $curso CursoPut [required] Campos de un curso.
	 * 
	 * @notes Los cambios se realizarán sobre el curso {id_curso}
	 * @summary Modificación del curso {id_curso}
	 * 
	 * @responses  204 El curso se modificó con éxito
	 * @responses  400 Errores de validación
     * @responses  404 Plataforma no encontrada
     * @responses  500 Falló la creación del curso
	 **/
	function put($id_curso)
	{
		$this->modelo->put($id_curso, rest::request()->get_body_json());		
		rest::response()->put();
	}

	/**
	 * DELETE /cursos/id_curso
	 *
	 * @notes Da de baja físicamente a un curso, junto a sus datos asociados
	 *
	 * @summary Baja del curso {id_curso}
	 * @responses 204 El curso ha sido eliminado
	 * @responses 404 El curso {id_curso} no existe
	 */
	function delete($id_curso)
	{
		$this->modelo->delete($id_curso);
		rest::response()->delete();
	}

 	/**
	 * GET /cursos
	 *
	 * @param_query $plataforma integer Id de Plataforma
	 * @param_query $limit integer (defecto: 20) Limitar a esta cantidad de registros
	 * @param_query $page integer Página
	 * @param_query $order string (defecto: +id_curso) +/-[id_curso,id_curso_externo] Ordena por el/los campos
	 *
     * @summary Cursos
	 * @responses 200 array {"$ref":"Curso"}
	 */
	function get_list()
	{
		$datos = $this->modelo->get_list();
		rest::response()->get_list($datos);
	}

	/**
	 * GET /cursos/id_curso
	 *
	 * @notes Retorna los datos del curso {id_curso} y los identificadores de las comisiones asociadas
	 *
	 * @summary Datos del curso {id_curso}
	 * @responses 200 {"$ref":"Curso"}
	 * @responses 404 El curso {id_curso} no existe
	 */
	function get($id_curso)
	{
		$datos = $this->modelo->get($id_curso);
		rest::response()->get($datos);
	}

	/**
	 * GET /cursos/id_curso/alumnos
	 *
	 * @notes Alumnos comprendidos por el curso {id_curso} de acuerdo a las comisiones que lo componen.
	 *
	 * @summary Alumnos del curso {id_curso}
	 * @responses 200 array {"$ref":"AlumnoCurso"}
	 * @responses 404 El curso {id_curso} no existe
	 */
	function get_alumnos_list($id_curso)
	{	
		$this->modelo->get($id_curso); //para ver si existe
		
		$datos = $this->modelo->get_alumnos($id_curso);
		rest::response()->get($datos);
	}

	/**
	 * PUT /cursos/id_curso/comisiones/id_comision
	 *
	 * @notes Asocia una comisión al curso. Es idempotente, lo que implica que si
	 * la comisión ya se encuentra asociada al curso, la operación igual se considera exitosa.
	 * La comisión debe estar activa.
	 * 
	 * @summary Asociación de la comisión {id_comision} al curso {id_curso}
	 * @responses  204 La comisión se encuentra asociada con el curso
	 * @responses  404 El curso {id_curso} no existe o
	 *				   La comisión {id_comision} no existe o no está activa. No se puede asociar
	 */
	function put_comisiones($id_curso, $id_comision)
	{
		$this->modelo->put_comision($id_curso, $id_comision);
		rest::response()->put();
	}

	/**
	 * DELETE /cursos/id_curso/comisiones/id_comision
	 *
	 * @notes Remueve la asociación de una comisión {id_comision} con el curso {id_curso}.<br>
	 * Es idempotente, esto implica que si la comisión no se encuentra asociada al curso, la operación igual se considera exitosa.
	 *
	 * @summary Desvinculación de la comisión {id_comision} del curso {id_curso}
	 * @responses 204 La comisión {id_comision} ya no se encuentra asociada con el curso
	 * @responses 404 La comisión {id_comision} no está asociada al curso.
	 *				  El curso {id_curso} no existe.	
	 *				  No es semánticamente un error (puede suceder si se envía el delete más de una vez).
	 */
	function delete_comisiones($id_curso, $id_comision)
	{
		$this->modelo->delete_comision($id_curso, $id_comision);
		rest::response()->delete();
	}
}