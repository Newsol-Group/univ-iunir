<?php
namespace siu\operaciones\admin_actas_examen_fc\acta;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\formularios\builder_formulario;
use siu\extension_kernel\formularios\fabrica_formularios;
use siu\extension_kernel\formularios\guarani_form;
use siu\extension_kernel\formularios\guarani_form_elemento;
use siu\guarani;
use siu\modelo\datos\catalogo;

class form_acta extends builder_formulario
{
	const MODO_NUEVA = 'nueva';
	const MODO_EDICION_EN_TURNO = 'edicion_t';
	const MODO_EDICION_FUERA_TERMINO = 'edicion_ft';

	//Referencias de comodidad del builder
	/**
	 * @var guarani_form
	 */
	protected $formulario;
	/**
	 * @var fabrica_formularios
	 */
	protected $fabrica;
	protected $id_acta = null;
	protected $hash_id_acta = null;
	protected $datos_acta = null;

	function get_id_html()
	{
		return 'form_acta';
	}

	function get_action()
	{
		if ($this->hash_id_acta) {
			return kernel::vinculador()->crear('admin_actas_examen_fc', 'modificar_datos_acta', array($this->hash_id_acta)); //editar
		} else {
			return kernel::vinculador()->crear('admin_actas_examen_fc', 'persistir_datos_acta', array());
		}
	}

	function set_id_acta($id_acta)
	{
		$this->id_acta = $id_acta;
	}

	function set_hash_id_acta($hash)
	{
		$this->hash_id_acta = $hash;
	}

	function get_modo_acta()
	{
		if (empty($this->id_acta)) {
			return self::MODO_NUEVA;
		}
		$this->datos_acta = catalogo::consultar('acta', 'get_datos_acta', array('id_acta' => $this->id_acta));
		if ($this->datos_acta['mesa_en_turno_examen'] == 'N') {
			return self::MODO_EDICION_FUERA_TERMINO;
		} else {
			return self::MODO_EDICION_EN_TURNO;
		}
	}

	static function get_anios_academicos($con_no_seleccion = true)
	{
		$datos = guarani::anios_academicos();
		if (\guarani_arreglos::count($datos) == 1) {
			$con_no_seleccion = false;
		}
		return guarani_form_elemento::armar_combo_opciones($datos, 'anio_academico', 'anio_academico', $con_no_seleccion);
	}

	static function get_escala_notas($con_no_seleccion = true)
	{
		$datos = catalogo::consultar('sistema', 'get_escalas_notas_activas', array());
		return guarani_form_elemento::armar_combo_opciones($datos, 'escala_nota', 'nombre', $con_no_seleccion);
	}

	static function get_roles($con_no_seleccion = true)
	{
		$datos = catalogo::consultar('docente', 'get_tribunales_roles', array());
		return guarani_form_elemento::armar_combo_opciones($datos, 'rol', 'nombre', $con_no_seleccion);
	}

	static function get_ubicaciones($con_no_seleccion = true)
	{
		$parametros = array('docente' => kernel::persona()->get_id_docente());
		$datos = catalogo::consultar('docente', 'get_ubicaciones_por_propuestas', $parametros);
		if (\guarani_arreglos::count($datos) == 0) {
			$datos = catalogo::consultar('sistema', 'ubicaciones', array());
		} elseif (\guarani_arreglos::count($datos) == 1) {
			$con_no_seleccion = false;
		}
		return guarani_form_elemento::armar_combo_opciones($datos, 'ubicacion', 'nombre', $con_no_seleccion);
	}

	static function get_instancias($con_no_seleccion = true)
	{
		$datos = catalogo::consultar('acta', 'get_instancias_examen', array('instancia' => null));
		return guarani_form_elemento::armar_combo_opciones($datos, 'instancia', 'instancia_nombre', $con_no_seleccion);
	}

	protected function add_actividad($modo = "")
	{
		// Si es modo edición se transforma en un campo fijo
		if ($modo == self::MODO_NUEVA) {
			              
                        // Etiqueta del autocompletar de la actividad
                        $actividad_descr = $this->fabrica->elemento(
                            'actividad_descr',
                            [
                                FormElementoConfig::group       => 'datos_acta',
                                FormElementoConfig::filtro      => validador::TIPO_TEXTO,
                                FormElementoConfig::label       => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.actividad')),
                                FormElementoConfig::obligatorio => true,
                                FormElementoConfig::elemento    => array('tipo' => 'text'),
                                FormElementoConfig::placeholder => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.ingrese_actividad')),
                                FormElementoConfig::clase_css   => 'span12'
                            ]
                        );

                        $this->formulario->add_elemento($actividad_descr);
                        $this->fabrica->elemento_decorar_boton_limpiar($actividad_descr, 'limpiar_actividad');

                        // Valor del autocompletar de la actividad
                        $actividad = $this->fabrica->elemento(
                            'actividad',
                            [
                                FormElementoConfig::group           => 'datos_acta',
                                FormElementoConfig::filtro          => validador::TIPO_INT,
                                FormElementoConfig::obligatorio     => true,
                                FormElementoConfig::elemento        => array('tipo' => 'hidden'),
                                FormElementoConfig::valor_default   => '',
                            ]
                        );
                        $this->formulario->add_elemento($actividad);

		} else {
			$actividad = $this->fabrica->elemento_fijo('actividad_nombre', array(
				FormElementoConfig::group => 'datos_acta',
				FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.actividad')),
			));
			$this->formulario->add_elemento($actividad);
		}
	}

	protected function add_nro_acta($modo = "")
	{
		if ($modo == self::MODO_NUEVA) {// || $modo == self::MODO_EDICION_FUERA_TERMINO) {
			$this->formulario->add_elemento($this->fabrica->elemento_texto('nro_acta', array(
						FormElementoConfig::group => 'datos_acta',
						FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.nro_acta')),
						FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
						FormElementoConfig::obligatorio => false,
						FormElementoConfig::largo => 30,
			)));
		} else {
			$this->formulario->add_elemento($this->fabrica->elemento_fijo('nro_acta', array(
						FormElementoConfig::group => 'datos_acta',
						FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.nro_acta')),
			)));
		}
	}

	protected function add_estado($modo = "")
	{
		$estado_acta = $this->fabrica->elemento_fijo('estado_acta', array(
			FormElementoConfig::group => 'datos_acta',
			FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.estado')),
		));

		if ($modo == self::MODO_NUEVA) {
			$estado_acta->set_valor('--');
		} else {

			switch ($this->datos_acta['estado']) {
				case \acta::estado_abierta:
					$estado_descr = kernel::traductor()->trans('estado_acta.abierta');
					break;
				case \acta::estado_anulada:
					$estado_descr = kernel::traductor()->trans('estado_acta.anulada');
					break;
				case \acta::estado_cerrada:
					$estado_descr = kernel::traductor()->trans('estado_acta.cerrada');
					break;
			}

			$estado_acta->set_valor($estado_descr);
		}

		$this->formulario->add_elemento($estado_acta);
	}

	protected function add_select_hora_inicio_fin()
	{
		$hora_inicio = $this->fabrica->elemento_timepicker('hora_inicio', array(
			FormElementoConfig::group => 'datos_acta',
			FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.inicio')),
			FormElementoConfig::obligatorio => true,
		));
		$hora_inicio->set_intervalo(15); // Por defecto es 15. Dejo esta línea a modo de ejemplo
		$this->formulario->add_elemento($hora_inicio);

		$this->formulario->add_elemento($this->fabrica->elemento_timepicker('hora_finalizacion', array(
					FormElementoConfig::group => 'datos_acta',
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.fin')),
					FormElementoConfig::obligatorio => false,
		)));
	}

	protected function add_ml_instancias()
	{
		$ml = $this->fabrica->elemento_ml('instancias');
		$ml->set_analisis_columnas(true);
		$ml->set_info_analisis_columnas(true);

		$ml->set_permite_alta_baja(false)
				->set_columna_todo_nada('activo')
				->set_columna_clave('instancia')
				// define la columna que tendrá el checkbox de selección todo o nada
				->add_columna($this->fabrica->elemento('instancia', array(
							FormElementoConfig::filtro => validador::TIPO_TEXTO,
							FormElementoConfig::obligatorio => false,
							FormElementoConfig::elemento => array('tipo' => 'hidden'),
							FormElementoConfig::largo => 150
				)))
				->add_columna($this->fabrica->elemento('activo', array(
							FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.activo')),
							FormElementoConfig::filtro => validador::TIPO_TEXTO,
							FormElementoConfig::obligatorio => false,
							FormElementoConfig::elemento => array('tipo' => 'checkbox'),
							FormElementoConfig::clase_css => 'instancias_activas',
							FormElementoConfig::largo => 1,
							FormElementoConfig::checked_value => 'S',
							FormElementoConfig::unchecked_value => 'N',
				)))
				->add_columna($this->fabrica->elemento_fijo('instancia_nombre', array(
							FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.instancia')),
				)))
				->add_columna($this->fabrica->elemento('escala_nota', array(
							FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.escala_notas')),
							FormElementoConfig::filtro => validador::TIPO_INT,
							FormElementoConfig::obligatorio => false,
							FormElementoConfig::elemento => array('tipo' => 'select'),
							FormElementoConfig::multi_options => static::get_escala_notas(true),
							FormElementoConfig::validar_select => false,
							FormElementoConfig::clase_css => 'select-escala_nota',
				)))
				// botón escala de notas
				->add_columna($this->fabrica->elemento('detalle_escala_nota', array(
							FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.detalle')),
							FormElementoConfig::filtro => validador::TIPO_INT,
							FormElementoConfig::obligatorio => false,
							FormElementoConfig::elemento => array('tipo' => 'button'),
							FormElementoConfig::clase_css => 'btn btn-small btn-detalle-escala js-detalle_escala',
		)));
		$this->formulario->add_elemento($ml);
	}

	protected function add_tribunal_examinador()
	{
		$bd = $this->fabrica->elemento_autocompletar('buscar_docentes', array(
			FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.buscar_docente')),
			FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
			FormElementoConfig::obligatorio => false,
			FormElementoConfig::placeholder => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.ingrese_nombre_docente')),
			FormElementoConfig::largo => 200,
				), function ($id) {
			return '';
		}, kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'buscar_docente')
		);

		$mje = ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.agregar'));

		$this->fabrica->elemento_decorar_boton_texto($bd, 'js-btn-agregar-docente', $mje);

		$this->formulario->add_elemento($bd);

		$mld = $this->fabrica->elemento_ml('docentes');
		$mld->set_analisis_columnas(true);
		$mld->set_permite_alta_baja(true)
				->set_columna_clave('docente')
				->add_columna($this->fabrica->elemento('docente', array(
							FormElementoConfig::filtro => validador::TIPO_TEXTO,
							FormElementoConfig::obligatorio => false,
							FormElementoConfig::elemento => array('tipo' => 'hidden'),
							FormElementoConfig::largo => 150
				)))
				->add_columna($this->fabrica->elemento_fijo('legajo', array(
							FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.legajo')),
				)))
				->add_columna($this->fabrica->elemento_fijo('apellido_nombre', array(
							FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.apellido_nombre')),
				)))
				->add_columna($this->fabrica->elemento_select('rol', static::get_roles(true), array(
							FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.rol')),
							FormElementoConfig::filtro => validador::TIPO_INT,
							FormElementoConfig::obligatorio => true,
							FormElementoConfig::validar_select => false,
							FormElementoConfig::clase_css => 'select-rol span12',
				)))
				->set_fn_completar_efs_fijos(function ($filas_post) {
					$rs = array();
					foreach ($filas_post as $fila) {
						$datos_docente = catalogo::consultar('docente', 'datos_basicos', array('docente' => $fila['docente']));
						$aux = $fila;
						$aux['legajo'] = $datos_docente['legajo'];
						$aux['apellido_nombre'] = $datos_docente['apellido_nombres'];
						$rs[] = $aux;
					}

					return $rs;
				});

		$this->formulario->add_elemento($mld);
	}

	protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica)
	{
		$modo = $this->get_modo_acta();
		$this->formulario = $form;
		$this->fabrica = $fabrica;

		if ($modo == self::MODO_NUEVA) {
			$this->formulario->add_accion($fabrica->accion_boton_submit('guardar', ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.seleccionar_alumnos'))));
		} else if($modo == self::MODO_EDICION_FUERA_TERMINO) {
			$this->formulario->add_accion($fabrica->accion_boton_submit('guardar', ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.editar_alumnos'))));
		} else if($modo == self::MODO_EDICION_EN_TURNO) {
			$this->formulario->add_accion($fabrica->accion_boton_submit('guardar', ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.guardar'))));
		}

		$this->add_nro_acta($modo);

		$this->add_actividad($modo);

		$this->add_estado($modo);

		if ($modo == self::MODO_NUEVA || $modo == self::MODO_EDICION_FUERA_TERMINO) {

			$this->formulario->add_elemento($this->fabrica->elemento_fecha('fecha', array(
						FormElementoConfig::group => 'datos_acta',
						FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.fecha')),
						FormElementoConfig::obligatorio => true
			)));

			$this->formulario->add_elemento($this->fabrica->elemento_select('anio_academico', static::get_anios_academicos(true), array(
						FormElementoConfig::group => 'datos_acta',
						FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.anio_academico')),
						FormElementoConfig::filtro => validador::TIPO_INT,
						FormElementoConfig::obligatorio => true,
						FormElementoConfig::clase_css => 'select-anio_academico span12',
			)));

			$this->add_select_hora_inicio_fin();

			$this->formulario->add_elemento($this->fabrica->elemento_select('ubicacion', static::get_ubicaciones(true), array(
						FormElementoConfig::group => 'datos_acta',
						FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.ubicacion')),
						FormElementoConfig::filtro => validador::TIPO_INT,
						FormElementoConfig::obligatorio => true,
						FormElementoConfig::clase_css => 'select-ubicacion span12',
			)));
		}

		$this->formulario->add_elemento($this->fabrica->elemento_textarea('observaciones', array(
					FormElementoConfig::group => 'datos_acta',
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.observaciones')),
					FormElementoConfig::largo => 150
		)));


		$this->add_ml_instancias();
		$this->add_tribunal_examinador();
	}

	protected function configurar_filas_datos_acta()
	{
		$modo = $this->get_modo_acta();

                $filas = [];

                // Si se trata de la edición de un acta existente
		if ($modo == self::MODO_EDICION_FUERA_TERMINO || $modo == self::MODO_EDICION_EN_TURNO) {
			$filas[] = array('actividad_nombre' => array('span' => 6), 'nro_acta' => array('span' => 3), 'estado_acta' => array('span' => 3));
		} else {// Si se trata de la creación de una nueva acta de examen
			$filas[] = array('actividad_descr' => array('span' => 6), 'nro_acta' => array('span' => 3), 'estado_acta' => array('span' => 3));
		}

		if ($modo == self::MODO_EDICION_FUERA_TERMINO || $modo == self::MODO_NUEVA) {
			$filas[] = array('anio_academico' => array('span' => 3),
				'ubicacion' => array('span' => 4)
			);
			$filas[] = array('fecha' => array('span' => 3),
				'hora_inicio' => array('span' => 2),
				'hora_finalizacion' => array('span' => 2)
			);
		}
		$filas[] = array('observaciones' => array());

                // Si se trata de la creación de una nueva acta de examen
                if ($modo == static::MODO_NUEVA) {
                    $filas[] = ['actividad' => []];
                }

		return $filas;
	}

	/**
	 * Por defecto los filtros de guarani se basan en el layout grilla. Se debe configurar en este metodo para poder
	 * instanciar el mismo
	 */
	function get_configuracion_layout_grilla()
	{
		//---- DATOS ACTA ------------------------------------------------------
		$layout = array(
			array(
				'grupo' => 'datos_acta',
				'titulo' => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.datos_acta')),
				'filas' => $this->configurar_filas_datos_acta(),
			),
		);

		$modo = static::get_modo_acta();

		if ($modo == self::MODO_EDICION_FUERA_TERMINO || $modo == self::MODO_NUEVA) {
			//---- INSTANCIAS --------------------------------------------------
			$layout[] = array(
				'grupo' => 'instancias',
				'titulo' => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.instancias')),
				'filas' => array(
					array('instancias' => array()
					),
				)
			);

			//---- DOCENTES ----------------------------------------------------
			$layout[] = array(
				'grupo' => 'docentes',
				'titulo' => ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.tribunal_examinador')),
				'filas' => array(
					array('buscar_docentes' => array('span' => 12),),
					array('docentes' => array('span' => 12)),
				)
			);
		}

		return $layout;
	}

}