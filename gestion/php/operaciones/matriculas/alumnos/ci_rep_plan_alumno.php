<?php
class ci_rep_plan_alumno extends ci_reporte
{
    protected $s__datos_plan = null;
    protected $s__datos_alumno = null;

    public function conf__cuadro(toba_ei_cuadro $cuadro)
	{		
        if (isset($this->s__datos)) {
            $datos = $this->get_datos($this->s__datos);
            $cuadro->set_datos($datos);
        }
	}

	public function conf__cuadro_seleccionado(toba_ei_cuadro $cuadro)
	{		
        if (isset($this->s__datos_alumno)) {			
			$param = [];
			$param['alumno'] = $this->s__datos_alumno['alumno'];
			$param['plan_version'] = $this->s__datos_alumno['plan_version'];
            $this->s__datos_alumno['legajo'] = current(toba::consulta_php('co_alumnos')->get_datos_alumno($this->s__datos_alumno['alumno']))['legajo'];
			$param['legajo'] = isset($this->s__datos_alumno['legajo']) ? $this->s__datos_alumno['legajo'] : "---";
			$this->evento('pdf')->vinculo()->set_parametros($param);
        }
	}
		
    public function get_datos_plan()
    {
        return $this->s__datos_plan ?? null;
    }
    
    public function set_datos_plan($datos)
    {
        $this->s__datos_plan = $datos;
    }

    public function get_datos($filtro)
    {	
		$datos = guarani::co('co_alumnos')->get_listado($filtro);
		return $datos;
    }

    public function get_tabla_principal()
    {
        return 'sga_alumnos';
    }
    
    public function get_propuesta($alumno)
    {
        //Cuando la variable viene como array, es porque vuelve del post
        if (is_array($alumno)) {
            $propuesta = $alumno[1];
        } else {
            $aux = explode('||', $alumno);
            $propuesta = $aux[1];
        }
        
        $datos = current(toba::consulta_php('co_propuestas')->get_nombres_propuestas($propuesta));
        return $datos['nombre'];
    }
    
    public function get_plan($alumno)
    {        
        //Cuando la variable viene como array, es porque vuelve del post
        if (is_array($alumno)) {
            $plan = $alumno[2];
        } else {
            $aux = explode('||', $alumno);
            $plan = $aux[2];
        }
        
        $where = ' plan = '.$plan;
        $datos = current(toba::consulta_php('co_planes')->get_planes_nombres($where));
        return $datos['descr'];
    }
    
    //---- filtro -----------------------------------------------------------------------

    public function conf__filtro($filtro)
    {
        if (isset($this->s__datos)) {
            $filtro->set_datos($this->s__datos);
        }
    }

    public function evt__filtro__filtrar($datos)
    {
        $this->s__datos = $this->dep('filtro')->get_sql_where();
    }
	
	public function evt__cuadro__seleccion($seleccion)
    {
		// Obtengo el parámetro 'pro_ver_optativas_en_estructura_plan'
		$ver_optativas_en_estructura_plan = toba::consulta_php('co_parametros')->get_estructura_parametro('pro_ver_optativas_en_estructura_plan');
		// Si el parámetro 'pro_ver_optativas_en_estructura_plan' es 'No' trato las genéricas como materias comunes
		$tratar_generica_como_materia = false;
		if ($ver_optativas_en_estructura_plan == \comunes::no) {
			$tratar_generica_como_materia = true;
		}
		$params = [
			'ver_optativas_en_estructura_plan' => $ver_optativas_en_estructura_plan,
			'tratar_generica_como_materia' => $tratar_generica_como_materia,
		];
        try{
            $this->s__datos_alumno['alumno'] = $seleccion['alumno'];
            $this->s__datos_alumno['plan_version'] = toba::consulta_php('co_alumnos')->get_plan_version($seleccion['alumno']);
            $this->s__datos_plan = alumno::get_contenido_plan_version_con_historia_academica($this->s__datos_alumno['alumno'], $this->s__datos_alumno['plan_version'],$params);
            $this->set_pantalla('edicion');
        }catch (guarani_error $e)	{
			throw $e;
		}
    }
		
	public function evt__volver()
    {
		$this->set_pantalla('seleccion');		
    }

    public function evt__filtro__cancelar()
    {
        unset($this->s__datos);
        unset($this->s__datos_plan);
    }
    
    function extender_objeto_js() 
    {
        $this->js_correlativas();
        $this->js_cumplimiento_genericas();
        $this->js_optativas();
        $this->js_acordeon();
    }
        
    /*
     * Funciones necesarias para el botón de mostrar correlativas.
     */
    
    function ajax__ver_correlativas($parametros, toba_ajax_respuesta $respuesta)
	{
        $retorno = '';
		if ($parametros['elemento'] && !empty($this->s__datos_alumno)) {
			
            $plan_version = $this->s__datos_alumno['plan_version'];
            $correlativas = toba::consulta_php('co_alumnos')->get_correlativas($parametros['elemento'], $plan_version);
            $retorno = $this->pantalla()->imprimir_correlativas($correlativas);
        }
        $respuesta->set(array($retorno, $parametros['elemento']));
	}
    
   function js_correlativas()
    {
        $id_js = $this->get_objeto_js();
        echo "
			
			$('body').on('click', 'button.ver_correlatividades', function (event) {
                event.preventDefault();
                var boton = $(this);
				var elemento = boton.data('elemento');
                ver_correlativas(boton, elemento);
            });
			
            function ver_correlativas(boton, elemento) {
			
				var tr_correlatividades = boton.parents('tr.materia').next('tr.correlatividades');
				var tabla_correlatividades = tr_correlatividades.find('table');
				
				// Si la info de correlatividades esta oculta
                if (tabla_correlatividades.is(':hidden')) {
				
					// Si la info aún no esta cargada la cargo
                    if (!boton.data('completo')) {
						var parametros = {elemento};                
						$id_js.ajax('ver_correlativas', parametros, boton, mostrar_correlativas);
					} else {// Si la info ya esta cargada simplemente la muestro
                        tabla_correlatividades.toggle(0, cambiar_texto(boton));
                    }
                } else if (tabla_correlatividades.is(':visible')) {// Si la info de correlatividades esta visible
                    tabla_correlatividades.toggle(0, cambiar_texto(boton));
                }
                return false;
            }
            
            /**
			 *	Se presiono el boton luego verifico si ver u ocultar.
			 */
            mostrar_correlativas = function(respuesta) {

				//Boton que muestra u oculta las correlativas
				var boton = $(this);
				
				//El tr que va a mostrar las correlatias
				var tr_correlatividades = boton.parents('tr.materia').next('tr.correlatividades');
				var tabla_correlatividades = tr_correlatividades.find('table');
				
				// Marco como completo/cargado
				boton.data('completo', true);
				boton.attr('data-completo', true);
				
				// https://learn.jquery.com/using-jquery-core/faq/how-do-i-pull-a-native-dom-element-from-a-jquery-object/
				tabla_correlatividades[0].innerHTML = respuesta[0];
                tabla_correlatividades.toggle(0, cambiar_texto(boton));
			}            
			
			function cambiar_texto(boton) {
		
				var tr_correlatividades = boton.parents('tr.materia').next('tr.correlatividades');
				var tabla_correlatividades = tr_correlatividades.find('table');
				//Me fijo el display si esta mostrando oculto, y sino al reves.
				if (tabla_correlatividades.is(':hidden')) {
					boton.text('Ocultar');
				} else {
					boton.text('Ver');
				}
			}";
    }
    
    /*
     * Funciones necesarias para el botón de verificar cumplimiento de generica
     */
    
    function ajax__verificar_cumplimiento($parametros, toba_ajax_respuesta $respuesta)
	{       
        $mensaje = 'El mensaje';
        if ($parametros['verificar'] && !empty($this->s__datos_alumno)) {
			
            $regla = guarani::regla($parametros['regla']);
            $parametros_contexto['fecha'] = guarani_fecha::get_hoy();
            $parametros_contexto['alumno'] = $this->s__datos_alumno['alumno'];
            $parametros_contexto['plan_version'] = $this->s__datos_alumno['plan_version'];
			$parametros_contexto['elemento'] = $parametros['generica'];            
            $regla->set_parametros($parametros['parametros']);
            $regla->set_parametros_contexto($parametros_contexto);

            $cumple_regla = $regla->validar();
            $mensaje = 'Cumplida';
            if (!$cumple_regla) {
                $mensaje = $regla->get_mensaje_validacion_interfaz(comunes::interfaz_gestion);
            }
        }
        
		$respuesta->set(array($parametros['generica'], $mensaje, $parametros['elemento_comp']));
        /**/
	}
    
    function js_cumplimiento_genericas()
    {
        $id_js = $this->get_objeto_js();
        echo "
            
            function verificar_cumplimiento(generica, regla, parametros, elemento_comp) {
                var div_id = document.getElementById('cumplimiento_'+elemento_comp);
                var verificar = 0;
                if (div_id.style.display == 'none') {
                    verificar = 1;
                }
                
                var parametros = {verificar, generica, regla, parametros, elemento_comp};                
                $id_js.ajax('verificar_cumplimiento', parametros, this, mostrar_cumplimiento);	
				return false;
            }
            
            mostrar_cumplimiento = function(respuesta) {
                //El div que va a mostrar el cumplimiento
                var div_id = document.getElementById('cumplimiento_'+respuesta[2]);
                
                //Boton que muestra u oculta las correlativas
                var boton_id = document.getElementById('boton_cumplimiento_'+respuesta[2]);
                                
                //Me fijo el display si esta mostrando oculto, y sino al reves.
                if (div_id.style.display == 'none') {                    
                    //Muestro las correlativas
                    boton_id.innerText = 'Ocultar';
                    div_id.style.display = 'block';
                    div_id.innerHTML = respuesta[1];
                } else {                    
                    div_id.style.display = 'none';
                    boton_id.innerText = 'Verificar';
                }
            }
        ";
    }
    
	/*
     * Funciones necesarias para cargar optativas de materias genéricas.
     */
    
	
    function ajax__ver_optativas($parametros, toba_ajax_respuesta $respuesta)
	{
        $retorno = '';
        if ($parametros['elemento_generica']&& !empty($this->s__datos_alumno)) {
            $alumno = $this->s__datos_alumno['alumno'];
            $plan_version = $this->s__datos_alumno['plan_version'];
			$materia_generica = $parametros['elemento_generica'];			
			$optativas = alumno::get_optativas_plan_version_con_historia_academica($alumno, $plan_version, $materia_generica);
            $retorno = $this->pantalla()->imprimir_materia($optativas, $materia_generica, elemento::subtipo_materia_generica, true);
        }
        $respuesta->set(array($retorno, $parametros['elemento_generica'], $parametros['elemento_comp_generica']));
	}
    
    function js_optativas()
    {
        $id_js = $this->get_objeto_js();
        echo "
            function ver_optativas(elemento_generica, elemento_comp_generica) {
				
				// Obtengo el accordion de la materia genérica
				var accordion = $('div.accordian.materia_generica.elemento-comp-'+elemento_comp_generica);
				
				// Si aún no se cargaron las optativas
				if (!accordion.data('cargado')) {
					var parametros = {elemento_generica, elemento_comp_generica};
					$id_js.ajax('ver_optativas', parametros, this, mostrar_optativas);	
				}
				return false;
            }
            
            /**
			 *	Se presiono el boton luego verifico si ver u ocultar.
			 */
            mostrar_optativas = function(respuesta) {
			
				var elemento_generica = respuesta[1];
				var elemento_comp_generica = respuesta[2];
				// Obtengo el accordion de la materia genérica
				var accordion = $('div.accordian.materia_generica.elemento-comp-'+elemento_comp_generica);
				// Cargo el contenido de las optativas
				accordion.find('.js-optativas').html(respuesta[0]);
				// Muestro el contenido del accordion
				accordion.find('.js-contenido').show();
				// Quito la flecha (estilo para que parezca un accordion cerrado)
				accordion.find('.ui-accordion-header-icon').remove();
				// Marco el accordion como cargado
				accordion.data('cargado', true);
				accordion.attr('data-cargado', true);
				// Inicializo el accordion
				accordion.accordion({
					heightStyle: 'content',
					collapsible : true,
				});
				
			}";
    }
	
    /*
     * Funciones para acordeon
     */
    
    function js_acordeon()
    {
        echo "
            $('div.accordian:not(.materia_generica)').accordion({
                heightStyle: 'content',
                collapsible : true,
            }); ";
    }
}