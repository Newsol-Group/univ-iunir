<?php
namespace siu\operaciones\censo\discapacidad;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\modelo\comunes;
use siu\modelo\util;
use siu\operaciones\_comun\preinscripcion_form;

class form_discapacidad extends preinscripcion_form
{
	
	const LARGO_TEXTAREA = 2500;
	const FILAS_TEXTAREA = 2;
	
	function get_id_html() 
	{
		return 'form_discapacidad';
	}
	
	function get_action() 
	{
		return kernel::vinculador()->crear('censo', 'guardar', ['discapacidad']);
	}
	
	protected function generar_definicion() 
	{
		
		$this->set_campo('condicion_discapacidad', [
			form::label			=> '',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::elemento		=> ['tipo' => 'radio'],
			form::multi_options => util::opciones()->get_condicion_discapacidad(),
			form::clase_css		=> 'js_condicion_discapacidad',
			form::checked_value		=> comunes::no,
		]);
				
		$this->set_campo('tiene_cud', [
			form::label				=> 'tiene_cud',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
		]);
		
		//---- Tipos de discapacidades ---------------------------------
		$this->set_campo('disc_auditiva', [
			form::label				=> 'disc_auditiva',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
		]);
		
		$this->set_campo('disc_visual', [
			form::label				=> 'disc_visual',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
		]);
				
		$this->set_campo('disc_motora', [
			form::label				=> 'disc_motora',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
		]);
		
		$this->set_campo('disc_cond_psicosocial', [
			form::label				=> 'disc_cond_psicosocial',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
		]);
				
		$this->set_campo('disc_otra', [
			form::label				=> 'disc_otra',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
		]);
		
		//---- Auditiva ---------------------------------
		$this->set_campo('aud_tipo', [
			form::label			=> 'aud_tipo',
			form::filtro		=> validador::TIPO_INT,
			form::obligatorio	=> false,
			form::elemento		=> ['tipo' => 'select'],
			form::multi_options => util::opciones()->get_dificultades_discapacidad(),
			form::clase_css		=> '',
			form::valor_default => comunes::NO_HAY_DIFICULTAD_VAL
		]);
		
		$this->set_campo('aud_lenguaje_senias', [
			form::label				=> 'aud_lenguaje_senias',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
			form::clase_css			=> 'js_auditiva_forma_habitual_comunicacion',
		]);
		
		$this->set_campo('aud_lenguaje_labial', [
			form::label				=> 'aud_lenguaje_labial',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
			form::clase_css			=> 'js_auditiva_forma_habitual_comunicacion',
		]);
		
		$this->set_campo('aud_comunicacion_otra', [
			form::label			=> 'aud_comunicacion_otra',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> ['tipo' => 'textarea'],
			form::rows			=> static::FILAS_TEXTAREA,
			form::largo			=> static::LARGO_TEXTAREA,
			form::clase_css		=> 'js_auditiva_forma_habitual_comunicacion',
		]);
		
		$this->set_campo('aud_apoyo_vida_diaria', [
			form::label			=> 'aud_apoyo_vida_diaria',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> ['tipo' => 'textarea'],
			form::rows			=> static::FILAS_TEXTAREA,
			form::largo			=> static::LARGO_TEXTAREA,
			form::clase_css		=> 'js_auditiva_apoyos_requeridos',
		]);
				
		$this->set_campo('aud_req_interprete_lengua_senias', [
			form::label				=> 'aud_req_interprete_lengua_senias',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
			form::clase_css			=> 'js_auditiva_apoyos_requeridos',
		]);
		
		$this->set_campo('aud_req_aro_magnetico', [
			form::label				=> 'aud_req_aro_magnetico',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
			form::clase_css			=> 'js_auditiva_apoyos_requeridos',
		]);
		
		$this->set_campo('aud_req_otros_apoyos', [
			form::label				=> 'aud_req_otros_apoyos',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
			form::clase_css			=> 'js_auditiva_apoyos_requeridos',
		]);
		
		$this->set_campo('aud_otros_apoyos', [
			form::label			=> 'aud_otros_apoyos',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::elemento		=> ['tipo' => 'textarea'],
			form::rows			=> static::FILAS_TEXTAREA,
			form::largo			=> static::LARGO_TEXTAREA,
			form::clase_css		=> 'js_auditiva_apoyos_requeridos',
		]);
		
		//---- Visual ---------------------------------
		$this->set_campo('vis_dificultad_para_ver', [
			form::label			=> 'vis_dificultad_para_ver',
			form::filtro		=> validador::TIPO_INT,
			form::obligatorio	=> false,
			form::elemento		=> ['tipo' => 'select'],
			form::multi_options => util::opciones()->get_dificultades_discapacidad(),
			form::clase_css		=> '',
			form::valor_default => comunes::NO_HAY_DIFICULTAD_VAL
		]);
		
		$this->set_campo('vis_apoyo_vida_diaria', [
			form::label			=> 'vis_apoyo_vida_diaria',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> ['tipo' => 'textarea'],
			form::rows			=> static::FILAS_TEXTAREA,
			form::largo			=> static::LARGO_TEXTAREA,
			form::clase_css		=> 'js_visual_apoyos_requeridos',
		]);
				
		$this->set_campo('vis_req_archivo_audio', [
			form::label				=> 'vis_req_archivo_audio',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
			form::clase_css			=> 'js_visual_apoyos_requeridos',
		]);
		
		$this->set_campo('vis_req_texto_digital', [
			form::label				=> 'vis_req_texto_digital',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
			form::clase_css			=> 'js_visual_apoyos_requeridos',
		]);
				
		$this->set_campo('vis_req_texto_braile', [
			form::label				=> 'vis_req_texto_braile',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
			form::clase_css			=> 'js_visual_apoyos_requeridos',
		]);
						
		$this->set_campo('vis_req_otros_apoyos', [
			form::label				=> 'vis_req_otros_apoyos',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
			form::clase_css			=> 'js_visual_apoyos_requeridos',
		]);
		
		$this->set_campo('vis_otros_apoyos', [
			form::label			=> 'vis_otros_apoyos',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::elemento		=> ['tipo' => 'textarea'],
			form::rows			=> static::FILAS_TEXTAREA,
			form::largo			=> static::LARGO_TEXTAREA,
			form::clase_css		=> 'js_visual_apoyos_requeridos',
		]);
		
		//---- Motora ---------------------------------
		$this->set_campo('mot_apoyo_vida_diaria', [
			form::label			=> 'mot_apoyo_vida_diaria',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> ['tipo' => 'textarea'],
			form::rows			=> static::FILAS_TEXTAREA,
			form::largo			=> static::LARGO_TEXTAREA,
			form::clase_css		=> 'js_motora_apoyos_requeridos',
		]);
				
		$this->set_campo('mot_req_apoyo_aula', [
			form::label				=> 'mot_req_apoyo_aula',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
			form::clase_css			=> 'js_motora_apoyos_requeridos',
		]);
		
		$this->set_campo('mot_apoyo_aula', [
			form::label			=> 'mot_apoyo_aula',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> ['tipo' => 'textarea'],
			form::rows			=> static::FILAS_TEXTAREA,
			form::largo			=> static::LARGO_TEXTAREA,
			form::clase_css		=> 'js_motora_apoyos_requeridos',
		]);
						
		$this->set_campo('mot_req_otros_apoyos', [
			form::label				=> 'mot_req_otros_apoyos',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
			form::clase_css			=> 'js_motora_apoyos_requeridos',
		]);
		
		$this->set_campo('mot_otros_apoyos', [
			form::label			=> 'mot_otros_apoyos',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::elemento		=> ['tipo' => 'textarea'],
			form::rows			=> static::FILAS_TEXTAREA,
			form::largo			=> static::LARGO_TEXTAREA,
			form::clase_css		=> 'js_motora_apoyos_requeridos',
		]);
		
		//---- Condición Psicosocial ---------------------------------
		$this->set_campo('psi_descripcion', [
			form::label			=> 'psi_descripcion',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> ['tipo' => 'textarea'],
			form::rows			=> static::FILAS_TEXTAREA,
			form::largo			=> static::LARGO_TEXTAREA
		]);
		
		$this->set_campo('psi_apoyo_vida_diaria', [
			form::label			=> 'psi_apoyo_vida_diaria',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> ['tipo' => 'textarea'],
			form::rows			=> static::FILAS_TEXTAREA,
			form::largo			=> static::LARGO_TEXTAREA,
			form::clase_css		=> 'js_psicosocial_apoyos_requeridos',
		]);
				
		$this->set_campo('psi_req_otros_apoyos', [
			form::label				=> 'psi_req_otros_apoyos',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
			form::clase_css			=> 'js_psicosocial_apoyos_requeridos',
		]);
		
		$this->set_campo('psi_otros_apoyos', [
			form::label			=> 'psi_otros_apoyos',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::elemento		=> ['tipo' => 'textarea'],
			form::rows			=> static::FILAS_TEXTAREA,
			form::largo			=> static::LARGO_TEXTAREA,
			form::clase_css		=> 'js_psicosocial_apoyos_requeridos',
		]);
		
		//---- Otra situación de discapacidad ---------------------------------
		$this->set_campo('otra_descripcion', [
			form::label			=> 'otra_descripcion',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> ['tipo' => 'textarea'],
			form::rows			=> static::FILAS_TEXTAREA,
			form::largo			=> static::LARGO_TEXTAREA
		]);
		
		$this->set_campo('otra_req_apoyo_vida_diaria', [
			form::label				=> 'otra_req_apoyo_vida_diaria',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> ['tipo' => 'checkbox'],
			form::largo				=> 1,
			form::checked_value		=> comunes::si,
			form::unchecked_value	=> comunes::no,
		]);
		
		$this->set_campo('otra_apoyo_vida_diaria', [
			form::label			=> 'otra_apoyo_vida_diaria',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::elemento		=> ['tipo' => 'textarea'],
			form::rows			=> static::FILAS_TEXTAREA,
			form::largo			=> static::LARGO_TEXTAREA
		]);
		
		//---- Alguna otra información que consideres importante comunicar ---------------------------------
		$this->set_campo('informacion_importante', [
			form::label			=> '',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> ['tipo' => 'textarea'],
			form::rows			=> static::FILAS_TEXTAREA,
			form::largo			=> static::LARGO_TEXTAREA
		]);
	}
	
	function validar()
	{
		$form_valido = true;
		
		$condicion_discapacidad = $this->get_dato('condicion_discapacidad');
		// Si no presenta ninguna condición que implique discapacidad entonces NO valido
		if ($condicion_discapacidad == comunes::no) {
			return true;
		}
		
		$dificultades = util::opciones()->get_dificultades_discapacidad();
		$dificultades = array_keys($dificultades);
		
		$campos = $this->get_campos();
		
		// Recorro cada campo de Discapacidad
		foreach ($campos as $campo) {
			
			$tipo = $this->get_elemento_html_tipo($campo);
			$valor = $this->get_dato($campo);
			
			// Si el campo es del tipo 'radio' o 'checkbox'
			if (in_array($tipo, ['radio', 'checkbox'])) {
				
				// Si el campo viene vacío no lo valido ya que puede estar deshabilitado/oculto
				if (empty($valor)) {
					$this->set_dato($campo, comunes::no);
					continue;
				}
				
				// Si el valor es distinto a 'S' o 'N'
				if (!in_array($valor, [comunes::si, comunes::no])) {
					$form_valido = false;
					break;
				}
				
			// Si el campo es del tipo 'textarea'
			} else if ($tipo == 'textarea') {
				
				// Si el campo viene vacío no lo valido ya que puede estar deshabilitado/oculto
				if (empty($valor)) {
					$this->set_dato($campo, '');
					continue;
				}
				
				if (strlen($valor) > static::LARGO_TEXTAREA) {
					$form_valido = false;
					break;
				}
				
			// Si el campo es del tipo 'select'
			} else if ($tipo == 'select') {
				
				// Si el campo viene vacío no lo valido ya que puede estar deshabilitado/oculto
				if (empty($valor)) {
					$this->set_dato($campo, comunes::NO_HAY_DIFICULTAD_VAL);
					continue;
				}
				
				// Si el valor es distinto a las posibles dificultades
				if (!in_array($valor, $dificultades)) {
					$form_valido = false;
					break;
				}
				
			}

		}
		$datos = $this->get_datos();
		// Si 'Declaro condición de discapacidad' es 'Sí'
                if ($datos['condicion_discapacidad'] == comunes::si) {

                    // Valido que al menos seleccione una "Discapacidad"
                    if ($datos['disc_auditiva'] == comunes::no &&
                        $datos['disc_visual'] == comunes::no &&
                        $datos['disc_motora'] == comunes::no &&
                        $datos['disc_cond_psicosocial'] == comunes::no &&
                        $datos['disc_otra'] == comunes::no) 
                    {
                        $form_valido = false;
                    }
                }

                $msg_vacio = kernel::traductor()->trans('form_validacion_error.vacio');
                
                // Si 'Auditiva - ¿Requerís otros apoyos?' es 'Sí' y 'Especificar cuáles' es vacío
                if (($datos['aud_req_otros_apoyos'] == comunes::si) && empty(trim($datos['aud_otros_apoyos']))) {
                    $this->agregar_error('aud_otros_apoyos', $msg_vacio);
                    $form_valido = false;
                }

                // Si 'Visual - ¿Requerís otros apoyos?' es 'Sí' y 'Especificar cuáles' es vacío
                if (($datos['vis_req_otros_apoyos'] == comunes::si) && empty(trim($datos['vis_otros_apoyos']))) {
                    $this->agregar_error('vis_otros_apoyos', $msg_vacio);
                    $form_valido = false;
                }

                // Si 'Motora - ¿Requerís otros apoyos?' es 'Sí' y 'Especificar cuáles' es vacío
                if (($datos['mot_req_otros_apoyos'] == comunes::si) && empty(trim($datos['mot_otros_apoyos']))) {
                    $this->agregar_error('mot_otros_apoyos', $msg_vacio);
                    $form_valido = false;
                }

                // Si 'Condición Psicosocial - ¿Requerís otros apoyos?' es 'Sí' y 'Especificar cuáles' es vacío
                if (($datos['psi_req_otros_apoyos'] == comunes::si) && empty(trim($datos['psi_otros_apoyos']))) {
                    $this->agregar_error('psi_otros_apoyos', $msg_vacio);
                    $form_valido = false;
                }

                // Si 'Otra situación de discapacidad - ¿Requerís otros apoyos?' es 'Sí' y 'Especificar cuáles' es vacío
                if (($datos['otra_req_apoyo_vida_diaria'] == comunes::si) && empty(trim($datos['otra_apoyo_vida_diaria']))) {
                    $this->agregar_error('otra_apoyo_vida_diaria', $msg_vacio);
                    $form_valido = false;
                }

		return $form_valido;
	}

}