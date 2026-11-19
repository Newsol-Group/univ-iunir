<?php

namespace siu\operaciones\fecha_examen;

use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_g3w2;
use SIU\Chulupi\util\validador;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\ErrorKernelValidacion;

class controlador extends controlador_g3w2
{
	protected $form_builder;
	protected $datos_filtro;

	function modelo()
	{
		return null;
	}

	function accion__index()
	{
		$form = $this->get_form();
		$mostrar_seleccione_turno = true;

		if (!empty($_GET['formulario_filtro'])) {
			$this->recuperar_filtro();
			$filtros = $form->get_datos();
			
			$ra = "";
			if ($filtros['ra'] != "")
				$ra = $this->decodificar_ra($filtros['ra']);
			$ubicacion = "";
			if($filtros['ubicacion'] !="")
				$ubicacion = $this->decodificar_ubicacion($filtros['ubicacion'], $ra);	
			$carrera = "";
			if ($filtros['carrera'] != "") {
				$carrera = $this->decodificar_carrera($filtros['carrera']);
			}
			$plan = "";
			if ($carrera && ($filtros['plan'] != ""))
				$plan = $this->decodificar_plan($filtros['plan'], $carrera);
			$materia = "";
			$materia_descr = "";
			if ($filtros['materia'] != "") {
				list($materia, $materia_descr) = $this->decodificar_materia($filtros['materia'], $carrera, $plan);
			}
			$this->datos_filtro["materia_descr"] = $materia_descr;
			$form->set_datos(array("materia_descr" => $materia_descr));
			$fecha_desde = ($filtros['fecha_desde'] != "") ? \DateTime::createFromFormat('d/m/Y', $filtros['fecha_desde'])->format('Y-m-d') : "";
			$fecha_hasta = ($filtros['fecha_hasta'] != "") ? \DateTime::createFromFormat('d/m/Y', $filtros['fecha_hasta'])->format('Y-m-d') : "";
			$tipo_inscripcion = $filtros['tipo_mesa'];

			$materias = array();

			$parametros = array(
				'ubicacion' => $ubicacion,
				'propuesta' => $carrera,
				'plan' => $plan,
				'elemento' => $materia,
				'fecha_desde' => $fecha_desde,
				'fecha_hasta' => $fecha_hasta,
				'instancia' => $tipo_inscripcion
			);

			$datos = catalogo::consultar('usuario_anonimo', 'mesas_examen_usuario_anonimo', $parametros);

			//Hago corte de control por materia
			foreach ($datos as $dato) {

				$materias[$dato['actividad']]['MATERIA_NOMBRE'] = $dato['actividad_nombre'];
				$materias[$dato['actividad']]['MATERIA'] = $dato['actividad_codigo'];

				$materias[$dato['actividad']]['examenes'][] = array(
					'SUSPENDIDA' => ($dato['llamado_mesa_estado'] == \mesa_de_examen::mesa_estado_suspendido) ? true : false,
					'MESA' => $dato['mesa_examen_nombre'],
					'FECHA' => $dato['fecha_examen'],
					'TIPO_MESA' => $dato['instancias_mesa'],
					'INSCR_INICIO' => $dato['f_inicio_insc'],
					'INSCR_FIN' => $dato['f_fin_insc'],
					'INFO_EXTRA' => array(
						'HORA_INICIO' => $dato['hora_inicio'],
						'HORA_FINALIZACION' => $dato['hora_finalizacion'],
						'EDIFICIO_AULA' => $dato['edificio_aula'],
						'FECHA_TOPE_BAJAS' => $dato['f_tope_baja_mesa'],
						'DOCENTES' => $dato['docentes'],
						'CARRERA_NOMBRE' => $dato['propuestas'],
						'OBSERVACIONES'=> $dato['observaciones'],
					)
				);
			}

			$this->vista()->pagelet('filtro')->data['cuadro'] = $materias;
			$mostrar_seleccione_turno = false;
		}

		$this->vista()->pagelet('filtro')->add_var_js('mostrar_seleccione_turno', $mostrar_seleccione_turno);
	}

	function decodificar_carrera($carrera_hash)
	{
		$datos = catalogo::consultar('usuario_anonimo', 'get_carreras');
		if (!empty($datos)) {
			foreach ($datos as $value) {
				if ($value['_ID_'] == $carrera_hash) {
					return $value['propuesta'];
				}
			}
		}
		return false;
	}

	function decodificar_ra($ra_hash)
	{
		$datos = catalogo::consultar('usuario_anonimo', 'get_responsables_academicas');
		
		if (!empty($datos)) {
			foreach ($datos as $value) {
				if ($value['_ID_'] == $ra_hash) {
					return $value['responsable_academica'];
				}
			}
		}
		return false;
	}

	function decodificar_ubicacion($ubicacion_hash, $ra = "")
	{
		$datos = catalogo::consultar('usuario_anonimo', 'get_ubicacion', ['ra' => $ra]);

		if (!empty($datos)) {
			foreach ($datos as $value) {
				if ($value['_ID_'] == $ubicacion_hash) {
					return $value['id'];
				}
			}
		}
		return false;
	}

	function decodificar_plan($plan_hash, $carrera)
	{
		$datos = catalogo::consultar('usuario_anonimo', 'get_planes_propuesta', array('propuesta' => $carrera));
		if (!empty($datos)) {
			foreach ($datos as $value) {
				if ($value['_ID_'] == $plan_hash) {
					return $value['plan'];
				}
			}
		}
		return false;
	}

	function decodificar_materia($materia_hash, $carrera, $plan)
	{
		$datos = catalogo::consultar('usuario_anonimo', 'lista_actividades_propuesta', array('propuesta' => $carrera, 'plan' => $plan, 'materia' => "", 'persona' => ''));
		if (!empty($datos)) {
			foreach ($datos as $value) {
				if ($value['_ID_'] == $materia_hash) {
					return array($value['elemento'], $value['nombre']);
				}
			}
		}
		return false;
	}

	/**
	 * Solo la vista 'vista' tiene el form. Falla en otro caso
	 */
	private function recuperar_filtro()
	{
		$form = $this->get_form();
		if (!$form->procesar('GET')) {
			throw new ErrorKernelValidacion("Error al validar el filtro");
		}

		//si no se llega aca, aplican los valores default
		$this->datos_filtro = $form->get_datos();
	}

	/**
	 * @return guarani_form
	 */
	function get_form()
	{
		return $this->get_form_builder()->get_formulario();
	}

	function get_vista_form()
	{
		return $this->get_form_builder()->get_vista();
	}

	/**
	 * @return builder_form_filtro
	 */
	function get_form_builder()
	{
		// se hace esta función para no construir cada vez el builder
		if (!isset($this->form_builder)) {
			$this->form_builder = kernel::localizador()->instanciar('operaciones\fecha_examen\filtro\builder_form_filtro');
		}
		return $this->form_builder;
	}

	function accion__buscar_planes()
	{

		$carrera_hash = $this->validate_param('carrera', 'get', validador::TIPO_ALPHANUM);
		$carrera = "";
		if ($carrera_hash != "")
			$carrera = $this->decodificar_carrera($carrera_hash);
		$datos = array();
		$datos['cod'] = 1;
		$datos['planes'] = catalogo::consultar('usuario_anonimo', 'get_planes_propuesta', array('propuesta' => $carrera));
		$this->render_raw_json($datos);
	}


	function accion__buscar_ubicacion()
	{
		$ra_hash = $this->validate_param('ra', 'get', validador::TIPO_ALPHANUM, array('default' => ''));
		$ra = '';
		if ($ra_hash != "") {
			$ra = $this->decodificar_ra($ra_hash);
		}
		$datos = [];

		$datos['cod'] = 1;
		$datos['ubicacion'] = catalogo::consultar('usuario_anonimo', 'get_ubicacion', ['ra' => $ra]);
		if (empty($datos['ubicacion'])) {
			$datos['ubicacion'] = array('' => array('nombre' => kernel::traductor()->trans('fecha_examen.no_hay_ubicacion'), '_ID_' => 'nohaycarreras', 'propuesta' => 'nohaycarreras'));
		}
		$this->render_raw_json($datos);
	}
	
	function accion__buscar_carreras()
	{
		$ra_hash = $this->validate_param('ra', 'get', validador::TIPO_ALPHANUM, array('default' => ''));
		$ubicacion_hash = $this->validate_param('ubicacion', 'get', validador::TIPO_ALPHANUM, array('default' => ''));
		$ra = '';
		$ubicacion = "";
		
		if ($ra_hash != "") {
			$ra = $this->decodificar_ra($ra_hash);
			$ubicacion = $this->decodificar_ubicacion($ubicacion_hash, $ra);
		}
		
		$datos = [];
		$datos['cod'] = 1;
		
		$parametros = array(
			'ra'=> $ra,
			'ubicacion'=> $ubicacion
		);

		$datos['carrera'] = catalogo::consultar('usuario_anonimo', 'get_carreras_filtro', $parametros);
		if (empty($datos['carrera'])) {
			$datos['carrera'] = array('' => array('nombre' => kernel::traductor()->trans('fecha_examen.no_hay_propuestas_ra_ubicacion'), '_ID_' => 'nohaycarreras', 'propuesta' => 'nohaycarreras'));
		}
		$this->render_raw_json($datos);
	}

	function accion__buscar_materias()
	{
		$term = $this->getAutocompleteTerm();

		$carrera_hash = $this->validate_param('carrera', 'get', validador::TIPO_ALPHANUM, array('default' => ''));
		$carrera = "";
		if ($carrera_hash != "") {
			$carrera = $this->decodificar_carrera($carrera_hash);	
		}

		$plan_hash = $this->validate_param('plan', 'get', validador::TIPO_ALPHANUM, array('default' => ''));
		$plan = "";
		if ($carrera && ($plan_hash != "")) {
			$plan = $this->decodificar_plan($plan_hash, $carrera);	
		}

		$paquete = array();
		if (!is_null($term)) {

			$datos = catalogo::consultar('usuario_anonimo', 'lista_actividades_propuesta', array('propuesta' => $carrera, 'plan' => $plan, 'materia' => $term, 'persona' => ''));
			foreach ($datos as $dato) {
				$paquete[] = array(
					'id' => $dato['_ID_'],
					'label' => $dato['nombre']
				);
			}
		}

		$this->render_raw_json($paquete);
	}

}