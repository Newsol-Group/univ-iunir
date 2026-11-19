<?php
namespace siu\operaciones\admin_actas_examen_fc\alumnos;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\formularios\builder_formulario;
use siu\extension_kernel\formularios\elementos\guarani_elemento_vista;
use siu\extension_kernel\formularios\fabrica_formularios;
use siu\extension_kernel\formularios\guarani_form;
use siu\extension_kernel\formularios\guarani_form_elemento;
use siu\modelo\datos\catalogo;

class form_alumnos extends builder_formulario
{
	protected $instancias_disponibles;
	protected $hash;
	protected $id_acta;
	protected $datos_originales;

	function get_id_html()
	{
		return 'form_alumnos';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('admin_actas_examen_fc', 'guardar_detalle', $this->hash);
	}

	function set_id_acta($id_acta)
	{
		$this->id_acta = $id_acta;
	}

	function set_hash($hash)
	{
		$this->hash = $hash;
	}

	function get_instancias_disponibles($con_no_seleccion = true)
	{
		$datos = array();

		if (!empty($this->datos_originales['instancias'])) {
			foreach ($this->datos_originales['instancias'] as $instancia) {
				if (!empty($instancia['activo']) && $instancia['activo'] == 'S') {
					$datos[$instancia['instancia']]['instancia'] = $instancia['instancia'];
					$datos[$instancia['instancia']]['instancia_nombre'] = $instancia['instancia_nombre'];
				}
			}
		}
		return guarani_form_elemento::armar_combo_opciones($datos, 'instancia', 'instancia_nombre', $con_no_seleccion);
	}

	function set_datos_originales($datos)
	{
		$this->datos_originales = $datos;
	}

	protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica)
	{
		$form->add_accion($fabrica->accion_boton_submit('crear', ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.guardar'))));
		
		// Alumnos

		$ba = $fabrica->elemento_autocompletar('buscar_alumnos', array(
			FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.buscar_alumnos')),
			FormElementoConfig::filtro => validador::TIPO_INT,
			FormElementoConfig::obligatorio => false,
			FormElementoConfig::placeholder => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.ingrese_nombre_alumno')),
			FormElementoConfig::largo => 200,
				), function($id) {
			return $id;
		}, kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'buscar_alumnos', array('hash' => $this->hash))
		);

		$form->add_elemento($ba);

		$form->add_elemento($fabrica->elemento('instancia_alumno', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.instancia')),
					FormElementoConfig::filtro => validador::TIPO_INT,
					FormElementoConfig::elemento => array('tipo' => 'select'),
					FormElementoConfig::multi_options => static::get_instancias_disponibles(false),
					FormElementoConfig::validar_select => false,
					FormElementoConfig::clase_css => 'select-instancia_alumno',
					FormElementoConfig::obligatorio => false
		)));

		$mla = $fabrica->elemento_ml('alumnos');

		$mla->set_analisis_columnas(true);
		$mla->set_permite_alta_baja(true)
				->set_columna_clave('alumno')
				->add_columna($fabrica->elemento('persona', array(
							FormElementoConfig::filtro => validador::TIPO_TEXTO,
							FormElementoConfig::obligatorio => false,
							FormElementoConfig::elemento => array('tipo' => 'hidden'),
							FormElementoConfig::clase_css => 'hidden-persona',
							FormElementoConfig::largo => 150
				)))
				->add_columna($fabrica->elemento('alumno', array(
							FormElementoConfig::filtro => validador::TIPO_TEXTO,
							FormElementoConfig::obligatorio => false,
							FormElementoConfig::elemento => array('tipo' => 'hidden'),
							FormElementoConfig::largo => 150
				)))
				->add_columna($fabrica->elemento_fijo('legajo', array(
							FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.legajo')),
				)))
				->add_columna($fabrica->elemento_fijo('apellido_nombre', array(
							FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.apellido_nombre')),
				)))
				->add_columna($fabrica->elemento_fijo('identificacion', array(
							FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.identificacion')),
				)))
				->add_columna($fabrica->elemento_fijo('propuesta', array(
							FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.propuesta')),
				)))
				->add_columna($fabrica->elemento('plan_version', array(
					FormElementoConfig::filtro => validador::TIPO_INT,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'hidden'),
					FormElementoConfig::largo => 150
				)))
				->add_columna($fabrica->elemento('instancia', array(
							FormElementoConfig::filtro => validador::TIPO_TEXTO,
							FormElementoConfig::obligatorio => false,
							FormElementoConfig::elemento => array('tipo' => 'hidden'),
							FormElementoConfig::largo => 150
				)))
				->add_columna($fabrica->elemento_fijo('instancia_nombre', array(
							FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.instancia')),
				)))
				->set_fn_completar_efs_fijos(function($filas_post) {
					$rs = array();
					foreach ($filas_post as $fila) {
						$datos_alumno = catalogo::consultar('alumno', 'get_datos', array('alumno' => $fila['alumno'])); // Esta consulta esta cacheada
						$datos_instancia = catalogo::consultar('acta', 'get_instancias_examen', array('instancia' => $fila['instancia'])); // Esta consulta esta cacheada
						$aux = $fila;
						$aux['legajo'] = $datos_alumno['legajo'];
						$aux['apellido_nombre'] = $datos_alumno['apellido_nombres'];
						$aux['identificacion'] = $datos_alumno['tipo_nro_documento'];
						$aux['propuesta'] = $datos_alumno['propuesta_nombre_abreviado'];
						$aux['instancia_nombre'] = $datos_instancia['instancia_nombre'];
						$rs[] = $aux;
					}
					return $rs;
				});

		$mla->get_vista_helper()->set_mensaje_vacio(ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.msg_tabla_vacia')));

		$form->add_elemento($mla);
	}

	/**
	 * Por defecto los filtros de guarani se basan en el layout grilla. Se debe configurar en este metodo para poder
	 * instanciar el mismo
	 */
	function get_configuracion_layout_grilla()
	{

		$btn_agregar = new guarani_elemento_vista(
				function () {
			return "<a class='btn' id='js-btn-agregar-alumno'>Agregar</a>";
		});

		$fila1 = array('buscar_alumnos' => array('span' => 6),
			'instancia_alumno' => array('span' => 3),
			'btn_agregar' => array('plano' => $btn_agregar, 'span' => 2)
		);
	
		return array(
			array(
				'grupo' => 'datos_acta',
				'titulo' => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.alumnos')),
				'filas' => array(
					$fila1,
					array('alumnos' => array('span' => 12, 'offset' => 0)),
				)
			),
		);
	}

}
?>