<?php
namespace siu\operaciones\oferta_comisiones;

use siu\guarani;
use siu\extension_kernel\controlador_g3w2;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;

class controlador extends controlador_g3w2
{
	private $datos_consulta;
	private $filtros;

	function modelo()
	{
		return false;
	}

	function accion__index()
	{
	}

	public function get_datos()
	{
		if (!isset($this->datos_consulta))
		{
			$parametros = [];
			$parametros['plan_version'] = null;
			// Si es perfil alumno paso el 'plan_version' como parámetro
			if (guarani::persona()->es_perfil_alumno()) {
				$parametros['plan_version'] = guarani::persona()->get_id_plan_version_activo();
			}
			
			// Se pasa como parámetro por cuestiones de cache
			$parametros['perfil_activo'] = kernel::persona()->get_id_perfil_activo();
			
			$this->datos_consulta = catalogo::consultar('inscripcion_cursada', 'info_oferta_comisiones_vigentes', $parametros);
		}

		$this->datos_consulta = $this->ordenar($this->datos_consulta);
		return $this->datos_consulta;
	}

	public function get_filtros()
	{
		return $this->filtros;
	}

	private function ordenar($datos)
	{
		$resultado = array();
		$filtros = array();

		foreach($datos as $valor)
		{
			$docentes = array();
			if (!empty($valor['docentes_resp']))
			{
				foreach(explode(', ', $valor['docentes_resp']) as $docente)
				{
					$i = strpos($docente, '(');
					if ($i)
						$docentes[] = substr($docente, 0, $i - 1);
					else
						$docentes[] = $docente;
				}
			}
			$docentes = implode(', ', $docentes);

			$resultado[$valor['anio_perlec']]['anio_academico'] = $valor['anio_academico'];
			$resultado[$valor['anio_perlec']]['periodo_lectivo'] = $valor['periodo_lectivo'];
			$resultado[$valor['anio_perlec']]['periodo_lectivo_nombre'] = $valor['periodo_lectivo_nombre'];
			$resultado[$valor['anio_perlec']]['actividades'][$valor['actividad_orden']]['nombre'] = $valor['actividad_nombre'].' ('.$valor['actividad_codigo'].')';

			if (substr(trim($valor['periodo_lectivo_nombre']), -4) == $valor['anio_academico'])
				$resultado[$valor['anio_perlec']]['periodo_lectivo_nombre'] = substr(trim($valor['periodo_lectivo_nombre']), 0, -4);

			if (!isset($resultado[$valor['anio_perlec']]['actividades'][$valor['actividad_orden']]['comisiones'][$valor['comision']]))
			{
				$resultado[$valor['anio_perlec']]['actividades'][$valor['actividad_orden']]['comisiones'][$valor['comision']] = array(
					'comision_nombre' => $valor['comision_nombre'],
					'ubicacion' => $valor['ubicacion'],
					'ubicacion_nombre' => $valor['ubicacion_nombre'],
					'turno_nombre' => $valor['turno_nombre'],
					'instancias' => $valor['instancias'],
					'cupo' => $valor['cupo'],
					'cant_inscriptos' => $valor['cant_inscriptos'],
					'docentes' => $docentes,
					'docentes_resp' => $valor['docentes_resp'],
					'cantidad' => 0,
					'observaciones' => $valor['observaciones'],
				);
			}

			if (empty($valor['dia_semana']))
				$valor['dia_semana'] = "sin_definir";

			if (!empty($valor['subcomision']))
			{
				if (!isset($resultado[$valor['anio_perlec']]['actividades'][$valor['actividad_orden']]['comisiones'][$valor['comision']]['subcomisiones'][$valor['subcomision']])) {
					$resultado[$valor['anio_perlec']]['actividades'][$valor['actividad_orden']]['comisiones'][$valor['comision']]['subcomisiones'][$valor['subcomision']] = array(
						'cupo' => $valor['cupo'],
						'cant_inscriptos' => $valor['cant_inscriptos'],
						'docentes' => $docentes,
						'docentes_resp' => $valor['docentes_resp'],
						'subcomision_nombre' => $valor['subcomision_nombre'],
						'cantidad' => 0,
					);
				}

				$resultado[$valor['anio_perlec']]['actividades'][$valor['actividad_orden']]['comisiones'][$valor['comision']]['subcomisiones'][$valor['subcomision']]['cantidad']++;
				$resultado[$valor['anio_perlec']]['actividades'][$valor['actividad_orden']]['comisiones'][$valor['comision']]['subcomisiones'][$valor['subcomision']]['horarios'][] = array(
					'tipo_clase_nombre' => $valor['tipo_clase_nombre'],
					'dia_semana' => $valor['dia_semana'],
					'horario' => $valor['horario'],
					'aula' => $valor['espacio'],
				);
			}
			else
			{
				$resultado[$valor['anio_perlec']]['actividades'][$valor['actividad_orden']]['comisiones'][$valor['comision']]['cantidad']++;
				$resultado[$valor['anio_perlec']]['actividades'][$valor['actividad_orden']]['comisiones'][$valor['comision']]['horarios'][] = array(
					'tipo_clase_nombre' => $valor['tipo_clase_nombre'],
					'dia_semana' => $valor['dia_semana'],
					'horario' => $valor['horario'],
					'aula' => $valor['espacio'],
					'cantidad' => $valor['espacio'],
				);
			}

			if (!empty($valor['anio_perlec']))
				$filtros['anio_perlec'][$valor['anio_perlec']] = $valor['anio_academico'].' - '.$resultado[$valor['anio_perlec']]['periodo_lectivo_nombre'];

			if (!empty($valor['actividad_nombre']))
				$filtros['actividades'][$valor['actividad_orden']] = $valor['actividad_nombre'].' ('.$valor['actividad_codigo'].')';

			if (!empty($valor['ubicacion']))
				$filtros['sedes'][$valor['ubicacion']] = $valor['ubicacion_nombre'];
		}

		if (isset($filtros['actividades']) && is_array($filtros['actividades'])) asort($filtros['actividades']);
		if (isset($filtros['sedes']) && is_array($filtros['sedes'])) asort($filtros['sedes']);

		$this->filtros = $filtros;
		return $resultado;
	}

	public function accion__buscar_docentes()
	{
		// Mismo lógica que la función "prepare" de "src/siu/operaciones/oferta_comisiones/pagelet_reporte.php"
		$json = array();
		$docentes = catalogo::consultar('docente', 'get_docentes_activos', array('apellido' => $this->getAutocompleteTerm()));
		$json[] = array('id' => '', 'label' => 'Todos');
		$json[] = array('id' => 'Sin docentes', 'label' => 'Sin docentes');
		foreach($docentes as $docente)
		{
			$json[] = array(
				'id' => $docente['apellido'].' '.$docente['nombres'],
				'label' => $docente['apellido'].', '.$docente['nombres'].' ('.$docente['legajo'].')'
			);
		}
		$this->render_raw_json($json);
	}

	public function accion__buscar_actividades()
	{
		$json = array();
		$actividades = catalogo::consultar('unidad_academica', 'actividades', array(
			'term' => $this->getAutocompleteTerm(),
			'plan_version' => guarani::persona()->es_perfil_docente() ? null : guarani::persona()->get_id_plan_version_activo()
		));
		$json[] = array('id' => '', 'label' => 'Todas');
		foreach($actividades as $actividad)
		{
			$json[] = array(
				'id' => $actividad['nombre'].' ('.$actividad['codigo'].')',
				'label' => $actividad['nombre'].' ('.$actividad['codigo'].')'
			);
		}
		$this->render_raw_json($json);
	}
}