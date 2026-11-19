<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Comisiones
 * 
 */
class ci_nav_seleccion_alumnos extends guarani_pers_ci
{
	protected $s__evaluacion;
	protected $s__comision;
	protected $s__fecha_inicio_periodo_comision;
	
	// Alumnos insertados en 'sga_eval_detalle_cursadas' durante la operación.
	protected $s__alumnos_agregados;
	
	// Código de acta ficticio, para filtrar los detalles a mostrar!
	protected $id_acta_ficticia = acta::id_acta_ficticia;

	function ini()
	{
		if (!isset($this->s__evaluacion)) {
			$this->s__evaluacion = $this->controlador()->get_evaluacion();
		}

		if (!isset($this->s__comision)) {
			$datos_comision = $this->controlador()->get_datos_comision();
			$this->s__comision = $datos_comision['comision'];
			$this->s__fecha_inicio_periodo_comision = $datos_comision['fecha_inicio_periodo'];
		}

		// Se setean los filtros que necesita el selector de alumnos.
		$this->dep('alumnos')->set_filtros($this->s__evaluacion);
		$this->set_alumnos_asociados();
	}

	function resetear_vars()
	{
		unset($this->s__evaluacion);
		unset($this->s__comision);

		unset($this->s__alumnos_agregados);

		$this->dep('alumnos')->evt__cancelar();
	}

	//-----------------------------------------------------------------------------------
	//---- alumnos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function set_alumnos_asociados()
	{
		$condiciones = array('agregado' => true);
		$this->dep('alumnos')->set_alumnos_asociados($this->cn()->get_detalles($condiciones));
	}

	function agregar_alumno_comision($alumno)
	{
		$id_detalle = $this->cn()->get_id_detalle($alumno);

		// Se indica que el alumno ha sido agregado
		$alumno['agregado'] = true;

		$this->cn()->modificar_detalle($id_detalle, $alumno);
		$this->set_alumnos_asociados();
	}

	function agregar_alumno($alumno)
	{
		$this->cn()->validar_alumno($alumno['alumno'], $this->s__comision);

		// Se completan los campos básicos del detalle.
		$alumno = $this->completar_datos_alumno($alumno);

		// Se validan los controles por instancia y se completan el resto de los campos del detalle...
		$alumno = $this->cn()->validar_alumno_instancias($alumno, $this->s__comision);

		$this->cn()->agregar_detalle($alumno);

		// Se indica que el detalle para el alumno se insertó durante la operación. Al desasociarlo se eliminará.
		$this->s__alumnos_agregados[$alumno['alumno']] = true;

		$this->set_alumnos_asociados();
	}

	function eliminar_alumno($alumno)
	{
		$detalle = $this->cn()->get_detalle($alumno);

		if (isset($this->s__alumnos_agregados[$detalle['alumno']])) { // Si es un alumno insertado durante la operación, se elimina el detalle.
			$this->cn()->eliminar_detalle($alumno);
			unset($this->s__alumnos_agregados[$detalle['alumno']]);
		} else { // Sino, se desasocia.
			$this->cn()->modificar_columna_detalle($alumno, 'agregado', false);
		}

		$this->set_alumnos_asociados();
	}

	function eliminar_alumno_disponible($alumno)
	{
		guarani::act('act_actas')->eliminar_eval_detalle_cursada($this->s__evaluacion, $alumno);
	}

	function completar_datos_alumno($alumno)
	{
		$alumno['agregado'] = true;

		$alumno['instancia_cursada'] = 0;
		$alumno['escala_nota_cursada'] = null;
		$alumno['acta_cursada_cerrada'] = 0;
		$alumno['nota_cursada'] = null;
		$alumno['resultado_cursada'] = null;

		$alumno['instancia_promocion'] = 0;
		$alumno['escala_nota_promocion'] = null;
		$alumno['acta_promocion_cerrada'] = 0;
		$alumno['nota_promocion'] = null;
		$alumno['resultado_promocion'] = null;

		$alumno['fecha_regular'] = null;
		$alumno['inscripto'] = comunes::no;

		// Se obtiene el plan-versión del alumno a la fecha de inicio del período lectivo de la comisión.
		$alumno['plan_version'] = toba::consulta_php('co_alumnos')->get_plan_version($alumno['alumno'], $this->s__fecha_inicio_periodo_comision);

		return $alumno;
	}

}
?>