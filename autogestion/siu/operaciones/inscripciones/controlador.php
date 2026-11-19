<?php
namespace siu\operaciones\inscripciones;

use SIU\Chulupi\kernel;
use siu\guarani;
use siu\extension_kernel\controlador_g3w2;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\util\validador;
use siu\operaciones\inscripciones\pagelet_cursadas;
use siu\operaciones\inscripciones\pagelet_examenes;
use SIU\Chulupi\ErrorKernelValidacion;

class controlador extends controlador_g3w2
{
	/**
	 * @return \siu\modelo\transacciones\inscripciones
	 */
	function modelo()
	{
		return guarani::inscripciones();
	}
	
	// Si trae "Inscripciones históricas" o no, por defecto no.
	function get_alcance_cursadas() 
	{
		return kernel::sesion()->get_con_default('alcance_cursadas', 'A');
	}
	
	// Si trae "Inscripciones históricas" o no, por defecto no.
	function get_alcance_examenes() 
	{
		return kernel::sesion()->get_con_default('alcance_examenes', 'S');
	}
	
	function accion__index()
	{
		$alcance_exa = $this->validate_param('examenes_historicos', 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
		$alcance_cur = $this->validate_param('cursadas_historicas', 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
		kernel::sesion()->set('alcance_examenes', empty($alcance_exa) ? 'S' : $alcance_exa);
		kernel::sesion()->set('alcance_cursadas', $alcance_cur);
	}

	function accion__examenes()
	{
		$alcance_exa = $this->validate_param('examenes_historicos', 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
		kernel::sesion()->set('alcance_examenes', $alcance_exa);
		
		$pagelet = $this->vista()->pagelet('examenes');
		kernel::renderer()->add($pagelet);
	}
	
	function accion__cursadas()
	{
		$alcance_cur = $this->validate_param('cursadas_historicas', 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
		kernel::sesion()->set('alcance_cursadas', $alcance_cur);
		
		$pagelet = $this->vista()->pagelet('cursadas');
		kernel::renderer()->add($pagelet);
	}
	
	function accion__ver_subcomisiones()
	{
		$hash_inscripcion = $this->validate_param('link', 'post', validador::TIPO_ALPHANUM);
		
		$alcance = $this->get_alcance_cursadas();
		$datos_inscripcion = $this->modelo()->info__inscripcion_cursada_from_hash($hash_inscripcion, $alcance);
		$inscripcion = $datos_inscripcion['inscripcion'];
				
		$parametros = array('inscripcion' => $inscripcion);
		$resultado['subcomisiones'] = catalogo::consultar('alumno', 'inscripciones_subcomisiones', $parametros);
		$this->render_template('subcomisiones.twig', $resultado);
	}

	function accion__comprobante()
	{
		$tipo = $this->validate_param('tipo', 'get', validador::TIPO_ALPHANUM);
		$estado = $this->validate_param('estado', 'get', validador::TIPO_ALPHANUM);
		$hash_inscripcion = $this->validate_param('inscripcion', 'get', validador::TIPO_ALPHANUM);

		switch($tipo)
		{
			// Si se trata de cursada
			case 'C':
				$alcance = $this->get_alcance_cursadas();
				$datos_inscripcion = $this->modelo()->info__inscripcion_cursada_from_hash($hash_inscripcion, $alcance);
				$inscripcion = $datos_inscripcion['inscripcion'];
				$datos = catalogo::consultar('inscripcion_cursada', $estado == 'B' ? 'info_comprobante_baja' : 'info_comprobante_inscripcion', array('inscripcion' => $inscripcion));
				$generador = kernel::localizador()->instanciar('operaciones\cursada\generador_comprobantes_cursada');
				break;
			// Si se trata de examen
			case 'E':
				$alcance = $this->get_alcance_examenes();
				$datos_inscripcion = $this->modelo()->info__inscripcion_examen_from_hash($hash_inscripcion, $alcance);
				$inscripcion = $datos_inscripcion['inscripcion'];
				$datos = catalogo::consultar('inscripcion_examen', $estado == 'B' ? 'info_comprobante_baja' : 'info_comprobante_inscripcion', array('inscripcion' => $inscripcion));
				$generador = kernel::localizador()->instanciar('operaciones\examen\generador_comprobantes_examen');
				break;
			// Si se trata de curso
			case 'CU':
				$alcance = $this->get_alcance_cursadas();
				$datos_inscripcion = $this->modelo()->info__inscripcion_cursada_from_hash($hash_inscripcion, $alcance);
				$inscripcion = $datos_inscripcion['inscripcion'];
				$datos = $estado == 'B' ? guarani::cursos()->info__comprobante_baja($inscripcion) : guarani::cursos()->info__comprobante($inscripcion);
				$generador = kernel::localizador()->instanciar('operaciones\inscripcion_cursos\generador_comprobantes_cursos');
				break;
			default;
				throw new \siu\errores\error_guarani("Tipo de inscripción desconocido: $tipo.");
		}
		$generador->set_tipo_generacion($generador::TIPO_GENERACION_STREAM);
		//$generador->set_tipo_generacion($generador::TIPO_GENERACION_DESCARGA);
		if ($estado == 'B') {
			$generador->generar_comp_baja($datos);
		} else {
			$generador->generar_comp_alta($datos);
		}
		$this->finalizar_request();
	}
	
	function accion__curso_moodle()
	{
		$tipo = $this->validate_param('0', 'get', validador::TIPO_TEXTO);
		$hash_inscripcion = $this->validate_param('1', 'get', validador::TIPO_ALPHANUM);
		
		if (!in_array($tipo, [pagelet_cursadas::TIPO, pagelet_examenes::TIPO])) {
			kernel::log()->add_error(new ErrorKernelValidacion("El tipo debe estar entre los valores '".pagelet_cursadas::TIPO."' o '".pagelet_examenes::TIPO."'."));
			throw new ErrorKernelValidacion('Ha ocurrido un error en los parámetros.');
		}
		
		// Si se trata de Cursadas
		if ($tipo === pagelet_cursadas::TIPO) {
			$alcance = $this->get_alcance_cursadas();
			$datos_inscripcion = $this->modelo()->info__inscripcion_cursada_from_hash($hash_inscripcion, $alcance);
			$comision = $datos_inscripcion['comision'];
			$parametros['comision'] = $comision;
			$curso_virtual = catalogo::consultar('moodle', 'get_curso_virtual_by_comision', $parametros);
		// Si se trata de Exámenes
		} else if ($tipo === pagelet_examenes::TIPO) {
			$alcance = $this->get_alcance_examenes();
			$datos_inscripcion = $this->modelo()->info__inscripcion_examen_from_hash($hash_inscripcion, $alcance);
			$llamado_mesa = $datos_inscripcion['llamado_mesa'];
			$parametros['llamado_mesa'] = $llamado_mesa;
			$curso_virtual = catalogo::consultar('moodle', 'get_curso_virtual_by_llamado_mesa', $parametros);
		}
		
		$persona = kernel::persona()->get_id();
		$url_curso = \moodle::generarUrlCurso($curso_virtual['plataforma_host'], $curso_virtual['plataforma_url'], $curso_virtual['plataforma_token'], $persona, $curso_virtual['id_curso_externo']);
		
		header("Location: $url_curso");
		exit(); 
	}
	
}