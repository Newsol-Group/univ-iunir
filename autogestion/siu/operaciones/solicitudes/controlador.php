<?php

namespace siu\operaciones\solicitudes;

use SIU\Chulupi\ErrorKernelValidacion;
use \SIU\Chulupi\kernel;
use \SIU\Chulupi\util\validador;
use \siu\modelo\datos\catalogo;
use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use siu\modelo_g3\constancia;


class controlador extends controlador_g3w2
{
	function get_clase_vista()
	{
		switch ($this->accion) {
			case 'nueva_solicitud':
				return 'vista_nueva_solicitud';
			default:
				return 'vista';
		}
	}

	function modelo()
	{
		return guarani::solicitud_constancias();
	}

	function accion__index()
	{
		
	}

	function accion__nueva_solicitud()
	{

                $operacion = kernel::ruteador()->get_id_operacion();

                // Si no es POST es que se carga el formulario pero no se envió
		if (!kernel::request()->isPost()) {

			$resultado = $this->modelo()->evt__controla_nueva_solicitud();
			$hay_error = $this->hay_error_controles($resultado);

			if ($hay_error) {
				kernel::sesion()->set('solicitud_mensajes', $resultado['mensajes']);
                                // Redirijo a "Listado de solicitudes"
				$this->nuevo_request($operacion, 'index');
			}

			$this->vista()->pagelet('pagelet')->data['resultado'] = $resultado;
			return false;
		}

		$resultado = ['ok' => true, 'mensajes' => []];
		$campo_error = "";

		try {

			$campo_error = "Constancia";
			$constancia = $this->validate_param('constancia', 'post', validador::TIPO_INT);
			$campo_error = "Copias";
			$copias = $this->validate_param('copias', 'post', validador::TIPO_INT);
			$campo_error = "Presentar a";
			$presentar = $this->validate_param('presentar', 'post', validador::TIPO_TEXTO);
			$campo_error = "Observaciones";
			$observaciones = $this->validate_param('observaciones', 'post', validador::TIPO_TEXTO);
			$formulario = $this->validate_param('formulario', 'post', validador::TIPO_TEXTO);
			$formulario_campo = $this->validate_param('formulario_campo', 'post', validador::TIPO_TEXTO);
			$formulario_valor = $this->validate_param('formulario_valor', 'post', validador::TIPO_TEXTO);

			$this->modelo()->setConstancia($constancia);
			$resultado_controles = $this->modelo()->evt__controla_cant_max_solicitudes_aa();
			$hay_error = $this->hay_error_controles($resultado_controles);

			
			//valido si tiene formulario dentro del array el formulario_campo y formulario_valor no pueden ser vacios
			$array_formularios = ['34000236', '37000562', '14000079', '14000020', '14000017'];
			if (in_array($formulario, $array_formularios) && !empty($formulario_campo) && ($formulario_valor == 'no_hay_datos' || empty($formulario_valor ))) {
				$hay_error = true;
				$resultado_controles['mensajes'][] = array('mtipo' => 'E', 'mcuerpo' => kernel::traductor()->trans('solicitud_constancias_faltantes_datos'));
			}

			if ($hay_error) {
				kernel::sesion()->set('solicitud_mensajes', $resultado_controles['mensajes']);
                                // Redirijo a "Listado de solicitudes"
				$this->nuevo_request($operacion, 'index');
			}

			$resultado = $this->modelo()->evt__nueva_solicitud($constancia, $copias, $presentar, $observaciones, $formulario, $formulario_campo, $formulario_valor);
			$resultado['mensajes'] = array_merge($resultado_controles['mensajes'], $resultado['mensajes']);
		} catch (ErrorKernelValidacion $e) {

			$resultado['ok'] = false;
			$resultado['mensajes'][] = array('mtipo' => 'E', 'mcuerpo' => kernel::traductor()->trans('constancias.error_en_campo_x', array(
					'%campo%' => $campo_error
			)));
		}

                kernel::renderer()->add_to_ajax_response('ok', $resultado['ok']);

                // Si salió todo OK
                if ($resultado['ok']) {
                    kernel::sesion()->set('nueva_solicitud_ok', kernel::traductor()->trans('nueva_solicitud_ok'));
                    // Redirijo a "Listado de solicitudes"
                    $this->nuevo_request($operacion, 'index');
                }

		$this->vista()->pagelet('pagelet')->data['resultado'] = $resultado;
	}

	protected function hay_error_controles($resultado)
	{
		$hay_error = false;

		if (!empty($resultado)) {
			foreach ($resultado['mensajes'] as $mensaje) {
				if ($mensaje['mtipo'] == 'E') {
					$hay_error = true;
					break;
				}
			}
		}

		return $hay_error;
	}

	function accion__get_cantidad_copias()
	{
		$const = kernel::request()->getParam('constancia');
		$this->render_raw_json(array(
			'cod' => 1,
			'copias' => catalogo::consultar('solicitudes', 'get_max_cant_copias', array(
				'constancia' => $const
			))
		));
	}

	function accion__descargar_comprobante()
	{
		try {

			$this->vista()->pagelet('pagelet')->add_var_js('hubo_error', false);

			try {
				$solicitud_hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
			} catch (ErrorKernelValidacion $e) {
				klog("Falló la validación del Hash del certificado.");
				throw new \Exception(kernel::traductor()->trans('constancias_certificados.no_se_pudo_descargar_certificado'));
			}

			klog2('hash solicitud', $solicitud_hash);

			$solicitud = $this->decodificar_solicitud($solicitud_hash);

			if ($solicitud) {//Existe la solicitud
				klog2('solicitud seleccionada', $solicitud);

				//Valido si el certificado se puede descargar
				$certificado_vencido = false;
				$fecha_actual = new \DateTime('today');

				if ($solicitud['fecha_fin_vigencia']) {
					$fecha_fin_vigencia = \DateTime::createFromFormat('!Y-m-d', $solicitud['fecha_fin_vigencia']);

					if ($fecha_fin_vigencia < $fecha_actual) {
						$certificado_vencido = true;
					}
				}

				//Si permite descarga y la interfaz es autogestion y el estado es online y no esta vencido muestro el boton para descargar certificado...
				$permite_descargar = $solicitud['permite_descargar'] && ($solicitud['interfaz'] == \comunes::interfaz_autogestion) && ($solicitud['estado'] == \constancias::estado_solicitud_online) && (!$certificado_vencido);

				if (!$permite_descargar) {//Sino permite descargar
					klog("No se puede descargar el certificado, falló la validación.");
					throw new \Exception(kernel::traductor()->trans('constancias_certificados.no_se_pudo_descargar_certificado'));
				}


				$this->descargar_archivo(constancia::descargar_comprobante($solicitud['solicitud']), 'comprobante.pdf', true);
			} else {//No existe la solicitud
				klog("No existe el certificado a descargar.");
				throw new \Exception(kernel::traductor()->trans('constancias_certificados.no_se_pudo_descargar_certificado'));
			}
		} catch (\Exception $e) {

			$this->vista()->pagelet('pagelet')->add_var_js('hubo_error', true);
			$this->vista()->pagelet('pagelet')->add_var_js('msj_error', $e->getMessage());
		}
	}

	/**
	 * Retorna la solicitud a partir del hash
	 */
	protected function decodificar_solicitud($hash)
	{
		$solicitudes = catalogo::consultar('solicitudes', 'get_solicitudes_activas', array('alumno' => kernel::persona()->get_id_alumno_activo()));

		foreach ($solicitudes as $solicitud) {

			if ($solicitud[catalogo::id] == $hash) {
				return $solicitud;
			}
		}

		return null;
	}

	function get_solicitudes()
	{
		$solicitudes = catalogo::consultar(
						'solicitudes',
						kernel::request()->getParam('historicas') == 'true' ? 'get_solicitudes_historicas' : 'get_solicitudes_activas',
						array('alumno' => kernel::persona()->get_id_alumno_activo())
		);

		$fecha_actual = new \DateTime('today');

		foreach ($solicitudes as $key => $col) {

			$solicitudes[$key]['fecha_solicitud'] = date('d/m/Y', strtotime($col['fecha_solicitud']));

			$certificado_vencido = false;

			if ($col['fecha_fin_vigencia']) {
				$fecha_fin_vigencia = \DateTime::createFromFormat('!Y-m-d', $col['fecha_fin_vigencia']);
				$solicitudes[$key]['fecha_fin_vigencia'] = $fecha_fin_vigencia->format('d/m/Y');

				if ($fecha_fin_vigencia < $fecha_actual) {
					$certificado_vencido = true;
				}
			}

			//Si permite descarga y la interfaz es autogestion y el estado es online y no esta vencido muestro el boton para descargar certificado...
			$permite_descargar = $col['permite_descargar'] && ($col['interfaz'] == \comunes::interfaz_autogestion) && ($col['estado'] == \constancias::estado_solicitud_online) && (!$certificado_vencido);
			$solicitudes[$key]['permite_descargar'] = $permite_descargar;

			if ($permite_descargar) {
				$operacion = kernel::ruteador()->get_id_operacion();
				$solicitudes[$key]['url_descarga'] = kernel::vinculador()->crear($operacion, 'descargar_comprobante', $col[catalogo::id]);
			}
		}

		return $solicitudes;
	}

	function get_constancias()
	{
		$constancias = [];
		$propuesta = kernel::persona()->get_id_propuesta_activa();

		$constancias_activas = catalogo::consultar('solicitudes', 'lista_constancias_activas', ['propuesta' => $propuesta]);
		foreach ($constancias_activas as $const) {
			$constancias[$const['constancia']]['id'] = $const['constancia'];
			$constancias[$const['constancia']]['nombre'] = $const['nombre'];
			$constancias[$const['constancia']]['formulario'] = $const['objeto'];
		}

		return $constancias;
	}

	function accion__get_formulario_filtro()
	{
		$datos = [];
		$hubo_error = false;
		if (kernel::request()->isPost()) {

			try {

				try {
					$form = $this->validate_param('form', 'post', validador::TIPO_INT);
				} catch (ErrorKernelValidacion $e) {
					throw new \Exception(kernel::traductor()->trans('solicitudes.parametro_invalido'));
				}

				//$datos['form'] = kernel::localizador()->instanciar("operaciones\solicitudes\\nueva_solicitud\builder_form_filtro_{$form}");
				$fomulario = kernel::localizador()->instanciar("operaciones\solicitudes\\nueva_solicitud\builder_form_filtro_{$form}");
				$datos['form'] = $fomulario->get_vista();
			} catch (\Exception $e2) {
				$hubo_error = true;
				kernel::renderer()->add_to_ajax_response('mensaje', $e2->getMessage());
			}

			kernel::renderer()->add_to_ajax_response('hubo_error', $hubo_error);

			if (!$hubo_error) {
				$this->render_template("nueva_solicitud\builder_form_filtro.twig", $datos);
			}
		}
	}

	function accion__get_actividad_anio()
	{
		$anio_academico = $this->validate_param('anio', 'post', validador::TIPO_INT);
		$turno_examen = $this->validate_param('turno_examen', 'post', validador::TIPO_INT, array('default' => NULL));

		$anio_academico = \toba::db()->quote($anio_academico);

		$where = "vw_mesas_examen.anio_academico = $anio_academico ";
		if (isset($turno_examen)) {
			$turno_examen = \toba::db()->quote($turno_examen);
			$where .= " AND vw_mesas_examen.turno_examen = " . $turno_examen;
		}
		$filtro = "	sga_elementos.elemento 
						IN (SELECT	vw_mesas_examen.mesa_examen_elemento
							FROM	vw_mesas_examen
							WHERE	$where)
						";
		if (isset($filtro_activ) && trim($filtro_activ) != '') {
			$filtro_activ = \toba::db()->quote($filtro_activ);
			$filtro .= " AND (sga_elementos.nombre ILIKE '%$filtro_activ%' OR sga_elementos.codigo ILIKE '%$filtro_activ%')";
		}
		// Obtengo la propuesta seleccionada por el alumno y listo las actividades de esa propuesta
		$propuesta_seleccionada = guarani::persona()->get_id_propuesta_activa();
		$data = \toba::consulta_php('co_elementos')->get_listado_actividades_propuesta($propuesta_seleccionada);
	
		foreach ($data as $key => $actividad) {
			$actividades[$key]['elemento'] = $actividad['elemento'];
			$actividades[$key]['actividad_descr'] = $actividad['actividad_descr'];
		}
		$this->render_raw_json(array(
			'cod' => 1,
			'actividades' => $actividades
		));
	}

	function accion__get_evaluacion_parcial()
	{
		$anio_academico = $this->validate_param('anio', 'post', validador::TIPO_INT);
		$periodo_lectivo = $this->validate_param('periodo_lectivo', 'post', validador::TIPO_INT, array('default' => NULL));
		$actividad = $this->validate_param('actividad', 'post', validador::TIPO_INT, array('default' => NULL));
		$comision = $this->validate_param('comision', 'post', validador::TIPO_INT, array('default' => NULL));
		$fecha_desde = $this->validate_param('fecha_desde', 'post', validador::TIPO_DATE, array('format' => 'd/m/Y', 'default' => NULL));
		$fecha_hasta = $this->validate_param('fecha_hasta', 'post', validador::TIPO_DATE, array('format' => 'd/m/Y', 'default' => NULL));
		$filtro = '';
		
		//seteo condicion de fecha debería listar evaluaciones cuya fecha sea menor o igual a la actual
		$filtro .=" sga_evaluaciones.fecha <= current_date ";
		
		// Filtros sobre las evaluacion. El año académico es obligatorio.
		if (isset($anio_academico)) {
			$filtro .= " AND vw_periodos_lectivos.anio_academico = " . $anio_academico;
		}
		if (isset($periodo_lectivo)) {
			$filtro .= " AND sga_comisiones.periodo_lectivo = " . $periodo_lectivo;
		}
		if (isset($actividad)) {
			$filtro .= " AND sga_comisiones.elemento = " . $actividad;
		}
		if (isset($comision)) {
			$filtro .= " AND sga_comisiones.comision = " . $comision;
		}
		if (isset($fecha_desde)) {
			$fecha_desde = convertir_fecha_formato_base($fecha_desde);
			$filtro .= " AND sga_evaluaciones.fecha >= '$fecha_desde'";
		}
		if (isset($fecha_hasta)) {
			$fecha_hasta = convertir_fecha_formato_base($fecha_hasta);
			$filtro .= " AND sga_evaluaciones.fecha <=  '$fecha_hasta'";
		}
		// Se obtiene el alumno para el cual se está realizando la solicitud de constancia.
		$alumno = kernel::persona()->get_id_alumno_activo();
		$evaluaciones = \toba::consulta_php('co_evaluaciones')->get_evaluaciones_comision_alumno($alumno, $filtro);
		$this->render_raw_json(array(
			'cod' => 1,
			'evaluaciones' => $evaluaciones
		));
	}

	function accion__get_llamado_examen()
	{
		$anio_academico = $this->validate_param('anio', 'post', validador::TIPO_INT); //obligatorio
		$turno_examen = $this->validate_param('turno_examen', 'post', validador::TIPO_INT, array('default' => NULL));
		$actividad = $this->validate_param('actividad', 'post', validador::TIPO_INT, array('default' => NULL));
		$fecha_desde = $this->validate_param('fecha_desde', 'post', validador::TIPO_DATE, array('format' => 'd/m/Y', 'default' => NULL));
		$fecha_hasta = $this->validate_param('fecha_hasta', 'post', validador::TIPO_DATE, array('format' => 'd/m/Y', 'default' => NULL));
//
		$filtro = '';
		// Filtros sobre las Mesas. El año académico es obligatorio.
		if (isset($anio_academico)) {
			$filtro .= "sga_periodos.anio_academico = " . $anio_academico;
		}
		if (isset($turno_examen)) {
			$filtro .= " AND sga_turnos_examen.turno_examen = " . $turno_examen;
		}
		if (isset($actividad)) {
			$filtro .= " AND sga_mesas_examen.elemento = " . $actividad;
		}
		if (isset($fecha_desde)) {
			$fecha_desde = convertir_fecha_formato_base($fecha_desde);
			$filtro .= " AND sga_llamados_mesa.fecha >=  '$fecha_desde'";
		}
		if (isset($fecha_hasta)) {
			$fecha_hasta = convertir_fecha_formato_base($fecha_hasta);
			$filtro .= " AND sga_llamados_mesa.fecha <=  '$fecha_hasta'";
		}

		/**
		 * FILTRO SOBRE EL ALUMNO PARA EL CUAL SE ESTÁ SOLICITANDO LA CONSTANCIA:
		 * Se listan los llamados con Acta de examen cerrada, y en cuya acta el alumno solicitante
		 * tiene una nota cargada, excluyendo los ausentes.
		 */
		$alumno = kernel::persona()->get_id_alumno_activo();
		$filtro_alumno = " AND sga_llamados_mesa.llamado_mesa IN (SELECT	vw_actas.llamado_mesa
																	  FROM	vw_actas
																	  WHERE	vw_actas.alumno = $alumno
																	  AND	vw_actas.resultado <> '" . \escalas_notas::resultado_ausente . "')";
		$filtro .= $filtro_alumno;

		$llamados_mesa = \toba::consulta_php('co_mesas_de_examen')->get_listado($filtro);
		//nombre_llamado y fecha
		$llamados_mesa_opciones = [];
		foreach ($llamados_mesa as $key => $llamado_mesa) {
			$llamados_mesa_opciones[$key]['llamado_mesa'] = $llamado_mesa['llamado_mesa'];
			$llamados_mesa_opciones[$key]['nombre_llamado'] = $llamado_mesa['nombre_llamado'];
			$llamados_mesa_opciones[$key]['fecha'] = $llamado_mesa['fecha'];
		}
		$this->render_raw_json(array(
			'cod' => 1,
			'llamado_examen' => $llamados_mesa_opciones
		));
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

	///Funcion utilizada para obtener los datos a cargar en el combo de actividades
	function accion__buscar_periodo_lectivo()
	{

		$anio_academico = $this->validate_param('anio_academico', 'post', validador::TIPO_ALPHANUM);
		$datos = array();
		if ($anio_academico) {
			$datos['periodos'] = catalogo::consultar('unidad_academica', 'periodos_lectivos_un_anio_academico', array('anio_academico' => $anio_academico));
		}
		klog2('accion__buscar_periodo_lectivo', $datos);
		$this->render_raw_json(array(
			'cod' => 1,
			'periodo_lectivo' => $datos
		));
	}

	function accion__buscar_actividad_comisiones()
	{
		$anio_academico = $this->validate_param('anio_academico', 'post', validador::TIPO_ALPHANUM);
		$datos = array();
		$actividades_con_comisiones = array();
		$filtro = '';
		
		// Propuesta actual seleccionada por el alumno
		$propuesta = guarani::persona()->get_id_propuesta_activa();
		
		// Recupero actividades que tienen comisiones creadas en el año academico seleccionado.
		if ($anio_academico) {
			$datos['actividad_con_comisiones_anio'] = \toba::consulta_php('co_elementos')->get_actividades_con_comision($filtro, $anio_academico, $propuesta);
		}
		//solo muestro una vez la actividad 
		foreach ($datos['actividad_con_comisiones_anio'] as $actividad) { 
			$actividades_con_comisiones[$actividad['elemento']]['nombre'] = ucfirst($actividad['nombre']);		
			$actividad_descr = 	(!empty($actividad['codigo']))?$actividad['codigo']:'Sin código';	
			$actividades_con_comisiones[$actividad['elemento']]['actividad_descr'] = "(".$actividad_descr.") ". $actividades_con_comisiones[$actividad['elemento']]['nombre'];
			$actividades_con_comisiones[$actividad['elemento']]['elemento'] = $actividad['elemento'];
			}
		sort($actividades_con_comisiones);
		$this->render_raw_json(array(
			'cod' => 1,
			'resultado' => $actividades_con_comisiones
		));
	}

	function accion__buscar_comisiones_de_actividad()
	{
		$actividad = $this->validate_param('actividad', 'post', validador::TIPO_ALPHANUM);
		$anio_academico = $this->validate_param('anio_academico', 'post', validador::TIPO_ALPHANUM);
		$datos = array();
		$filtro = '';
		if ($anio_academico) {
			$datos['comisiones'] = \toba::consulta_php('co_comisiones')->get_comisiones_actividad_anio_academico($anio_academico, $actividad, $filtro = '');
		}
		klog2('accion__buscar_comisiones_de_actividad', $datos);
		$this->render_raw_json(array(
			'cod' => 1,
			'resultado' => $datos['comisiones']
		));
	}

	function accion__seleccion_actividad()
	{
		$actividad = $this->validate_param('actividad', 'post', validador::TIPO_TEXTO, array('default' => NULL));
		$curso = $this->validate_param('curso', 'post', validador::TIPO_TEXTO, array('default' => NULL));
		$docentes = $this->validate_param('docentes', 'post', validador::TIPO_TEXTO, array('default' => NULL));
		$fecha_desde = $this->validate_param('fecha_desde', 'post', validador::TIPO_DATE, array('format' => 'd/m/Y', 'default' => NULL));
		$fecha_hasta = $this->validate_param('fecha_hasta', 'post', validador::TIPO_DATE, array('format' => 'd/m/Y', 'default' => NULL));
		$filtro = '';
		if (isset($actividad)) {
			$filtro .= " AND vw_comisiones.elemento_nombre ILIKE '%" . $actividad . "%'";
		}
		if (isset($curso)) {
			$filtro .= " AND vw_comisiones.comision_nombre  ILIKE '%" . $curso . "%'";
		}
		if (isset($docentes)) {
			$filtro .= " AND f_docentes_comision(vw_comisiones.comision, false) ILIKE '%" . $docentes . "%'";
		}
		if (isset($fecha_desde)) {
			$fecha_desde = convertir_fecha_formato_base($fecha_desde);
			$filtro .= " AND vw_comisiones.fecha_inicio_dictado >=  '$fecha_desde'";
		}
		if (isset($fecha_hasta)) {
			$fecha_hasta = convertir_fecha_formato_base($fecha_hasta);
			$filtro .= " AND vw_comisiones.fecha_fin_dictado <=  '$fecha_hasta'";
		}

		$alumno = kernel::persona()->get_id_alumno_activo();

		$cursos_realizados = \toba::consulta_php('co_inscripciones_cursadas')->get_cursos_inscripto_alumno($alumno, $filtro);
		$this->render_raw_json(array(
			'cod' => 1,
			'resultado' => $cursos_realizados
		));
	}
function accion__seleccion_actividad_clases()
	{
		$actividad = $this->validate_param('actividad', 'post', validador::TIPO_TEXTO, array('default' => NULL));
		$comision = $this->validate_param('comision', 'post', validador::TIPO_TEXTO, array('default' => NULL));
		$fecha_desde = $this->validate_param('fecha_desde', 'post', validador::TIPO_DATE, array('format' => 'd/m/Y', 'default' => NULL));
		$fecha_hasta = $this->validate_param('fecha_hasta', 'post', validador::TIPO_DATE, array('format' => 'd/m/Y', 'default' => NULL));
		$filtro = '';
		if (isset($actividad)) {
			$filtro .= " AND vw_comisiones.elemento = '" . $actividad . "'";
		}
		if (isset($comision)) {
			$filtro .= " AND vw_comisiones.comision = '" . $comision . "'";
		}
		if (isset($fecha_desde)) {
			$fecha_desde = convertir_fecha_formato_base($fecha_desde);
			$filtro .= " AND vw_comisiones.fecha_inicio_dictado >=  '$fecha_desde'";
		}
		if (isset($fecha_hasta)) {
			$fecha_hasta = convertir_fecha_formato_base($fecha_hasta);
			$filtro .= " AND vw_comisiones.fecha_fin_dictado <=  '$fecha_hasta'";
		}

		$alumno = kernel::persona()->get_id_alumno_activo();

		$clases_asistidas= \toba::consulta_php('co_clases')->get_clases_alumno_asistio($alumno, $filtro);
		$this->render_raw_json(array(
			'cod' => 1,
			'resultado' => $clases_asistidas
		));
	}
	function accion__seleccion_cursos_aprobados()
	{
		$actividad = $this->validate_param('actividad', 'post', validador::TIPO_TEXTO, array('default' => NULL));
		$curso = $this->validate_param('curso', 'post', validador::TIPO_TEXTO, array('default' => NULL));
		$docentes = $this->validate_param('docentes', 'post', validador::TIPO_TEXTO, array('default' => NULL));
		$fecha_desde = $this->validate_param('fecha_desde', 'post', validador::TIPO_DATE, array('format' => 'd/m/Y', 'default' => NULL));
		$fecha_hasta = $this->validate_param('fecha_hasta', 'post', validador::TIPO_DATE, array('format' => 'd/m/Y', 'default' => NULL));
		$filtro = '';
		if (isset($actividad)) {
			$filtro .= " AND vw_comisiones.elemento_nombre ILIKE '%" . $actividad . "%'";
		}
		if (isset($curso)) {
			$filtro .= " AND vw_comisiones.comision_nombre  ILIKE '%" . $curso . "%'";
		}
		if (isset($docentes)) {
			$filtro .= " AND f_docentes_comision(vw_comisiones.comision, false) ILIKE '%" . $docentes . "%'";
		}
		if (isset($fecha_desde)) {
			$fecha_desde = convertir_fecha_formato_base($fecha_desde);
			$filtro .= " AND vw_comisiones.fecha_inicio_dictado >=  '$fecha_desde'";
		}
		if (isset($fecha_hasta)) {
			$fecha_hasta = convertir_fecha_formato_base($fecha_hasta);
			$filtro .= " AND vw_comisiones.fecha_fin_dictado <= '$fecha_hasta'";
		}

		$alumno = kernel::persona()->get_id_alumno_activo();

		$cursos_realizados = \toba::consulta_php('co_inscripciones_cursadas')->get_cursos_inscripto_alumno($alumno, $filtro, true);
		$this->render_raw_json(array(
			'cod' => 1,
			'resultado' => $cursos_realizados
		));
	}

	function accion__inscripciones_comision_alumno()
	{
		$actividades = [];
		$periodo_lectivo = $this->validate_param('periodo_lectivo', 'post', validador::TIPO_INT);
		
		$where = ' AND vw_comisiones.periodo_lectivo = ' . $periodo_lectivo;
		$where .= ' AND sga_elementos.entidad_subtipo != ' . \g3entidad::subtipo_raiz;
		$alumno = kernel::persona()->get_id_alumno_activo();
		$inscripciones_comision = \toba::consulta_php('co_inscripciones_cursadas')->get_inscripciones_actividad_alumno($alumno, $where);
		foreach ($inscripciones_comision as $key => $inscripcion_comision) {
			$actividades[$key]['valor'] = $inscripcion_comision['valor'];
			$actividades[$key]['descr'] = $inscripcion_comision['descr'];
		}
		$this->render_raw_json(array(
			'cod' => 1,
			'actividad' => $actividades
		));
	}

	function accion__turno_examen()
	{
		$anio_academico = $this->validate_param('anio', 'post', validador::TIPO_INT);

		$data = \toba::consulta_php('co_turnos_de_examen')->get_turnos_anio_academico($anio_academico);
		foreach ($data as $turno) {
			$turnos[$turno['valor']] = $turno['descr'];
		}
		$this->render_raw_json(array(
			'cod' => 1,
			'turno' => $turnos
		));
	}

	function accion__seleccion_clase()
	{
		$anio_academico = $this->validate_param('anio', 'post', validador::TIPO_INT);
		$periodo_lectivo = $this->validate_param('periodo_lectivo', 'post', validador::TIPO_INT);
		$actividad = $this->validate_param('actividad', 'post', validador::TIPO_INT);
		$comision = $this->validate_param('comision', 'post', validador::TIPO_INT);
		$formato_fecha = '!d/m/Y';		
		$fecha_desde = $this->validate_param('fecha_desde', 'post', validador::TIPO_DATE, array('format' => $formato_fecha));
		$fecha_hasta = $this->validate_param('fecha_hasta', 'post', validador::TIPO_DATE, array('format' => $formato_fecha));
		$filtro = '';
		if (isset($anio_academico)) {
			$filtro .= " AND vw_comisiones.anio_academico = " . $anio_academico;
		}
		if (isset($periodo_lectivo)) {
			$filtro .= " AND vw_comisiones.periodo_lectivo  = " . $periodo_lectivo;
		}
		if (isset($comision)) {
			$filtro .= " AND vw_comisiones.comision = " . $comision;
		}
		if (isset($actividad)) {
			$filtro .= " AND vw_comisiones.elemento = " . quote($actividad);
		}
		if (isset($fecha_desde)) {
			$fecha_desde = convertir_fecha_formato_base($fecha_desde);
			$filtro .= " AND sga_clases.fecha >=  '$fecha_desde'";
		}
		if (isset($fecha_hasta)) {
			$fecha_hasta = convertir_fecha_formato_base($fecha_hasta);
			$filtro .= " AND sga_clases.fecha <=  '$fecha_hasta'";
		}

		$alumno = kernel::persona()->get_id_alumno_activo();

		$clases_asistio = \toba::consulta_php('co_clases')->get_clases_alumno_asistio($alumno, $filtro);
		$this->render_raw_json(array(
			'cod' => 1,
			'clases' => $clases_asistio
		));
	}

}