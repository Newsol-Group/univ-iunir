<?php
namespace siu\operaciones\censo_docente\_forms;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;
use siu\operaciones\_comun\util\forms;

class form_principales extends form_comun
{
	public function get_template() {
		return "principales";
	}
	
	public function get_idhtml() {
		return "principales";
	}
	
	public function init()
	{
		$datos = $this->datos;
//		echo "<pre>";
//		print_r($datos);
		
    	$empresas_celulares = catalogo::consultar('etiquetas', 'empresas_celulares');
		$empresas_celulares  = forms::rs_to_select($empresas_celulares,'-- SELECCIONE --');
		
		$paises = catalogo::consultar('etiquetas', 'paises');
		$paises  = forms::rs_to_select($paises,'-- SELECCIONE --');
		
		if (!empty($datos['CENS_CP_PER_LECT'])){			
			$localidad_texto = catalogo::consultar(	'etiquetas',
													'nombre_localidad', 
													array(
														'localidad'=>$datos['CENS_LOC_PER_LECT']
														)
												);
		}
		else {
			$localidad_texto = "";
		}
		
		$form_id = new \Zend_Form_Element_Hidden('form_id');
		$form_id->setValue($this->get_idhtml());

		$label = kernel::traductor()->trans('censo.principales.email');
        $email = new \Zend_Form_Element_Text('CENS_E_MAIL', array(
			'label' => $label,
			'allowEmpty' => false,
			'class' => 'require'
		));
        $email->addValidator(new \Zend_Validate_NotEmpty());
		$email->setValue($datos['CENS_E_MAIL']);
		
		$label = kernel::traductor()->trans('censo.principales.domicilio');
        $domicilio = new \Zend_Form_Element_Text('CENS_CALLE_PER_LECT', array('label' => $label));
		$domicilio->setValue($datos['CENS_CALLE_PER_LECT']);
		
		$label = kernel::traductor()->trans('censo.principales.domicilio_num');
        $domicilio_num = new \Zend_Form_Element_Text('CENS_NUMERO_PER_LECT', array('label' => $label));
		$domicilio_num->setValue($datos['CENS_NUMERO_PER_LECT']);
		
		$label = kernel::traductor()->trans('censo.principales.domicilio_piso');
        $domicilio_piso = new \Zend_Form_Element_Text('CENS_PISO_PER_LECT', array('label' => $label));
		$domicilio_piso->setValue($datos['CENS_PISO_PER_LECT']);
		
		$label = kernel::traductor()->trans('censo.principales.domicilio_dto');
        $domicilio_dto = new \Zend_Form_Element_Text('CENS_DPTO_PER_LECT', array('label' => $label));
		$domicilio_dto->setValue($datos['CENS_DPTO_PER_LECT']);
		
		$label = kernel::traductor()->trans('censo.principales.domicilio_unidad');
        $domicilio_unidad = new \Zend_Form_Element_Text('CENS_UNIDAD_PER_LECT', array('label' => $label));
		$domicilio_unidad->setValue($datos['CENS_UNIDAD_PER_LECT']);
		
		$label = kernel::traductor()->trans('censo.principales.domicilio_localidad');
        $domicilio_localidad_texto = new \Zend_Form_Element_Text('CENS_LOC_PER_LECT_TEXTO', array('label' => $label, 'readonly' => 'readonly'));
		$domicilio_localidad_texto->setValue($localidad_texto);
		
        $domicilio_localidad = new \Zend_Form_Element_Hidden('CENS_LOC_PER_LECT');
		$domicilio_localidad->setValue($datos['CENS_LOC_PER_LECT']);
		
		$label = kernel::traductor()->trans('censo.principales.domicilio_cp');
        $domicilio_cp = new \Zend_Form_Element_Text('CENS_CP_PER_LECT', array('label' => $label, 'readonly' => 'readonly'));
		$domicilio_cp->setValue($datos['CENS_CP_PER_LECT']);
		
		$label = kernel::traductor()->trans('censo.principales.cel_comp');
        $cel_comp = new \Zend_Form_Element_Select('AUX1_CELULAR_COMPANIA', array(
			'label' => $label,
			'multiOptions' => $empresas_celulares
		));		
        $cel_comp->addValidator(new \Zend_Validate_InArray(array_keys($empresas_celulares)));
		$cel_comp->setValue($datos['AUX1_CELULAR_COMPANIA']);
				
		$celular = new \Zend_Form_Element_Text('AUX1_CELULAR_NUMERO');
        $celular->setLabel('Teléfono celular')
                  ->setValue($datos['AUX1_CELULAR_NUMERO']);
		
		$telefono = new \Zend_Form_Element_Text('CENS_TE_PER_LECT',array(
			'label' => 'Teléfono de línea agregar código de area'
		));
        $telefono->setValue($datos['CENS_TE_PER_LECT']);
		
		$this->setAction(kernel::vinculador()->crear('censo_docente', 'principales'))
			->setAttrib('id', $this->get_idhtml())
			->addElement($form_id)
			->addElement($email)
			->addElement($domicilio)
			->addElement($domicilio_num)
			->addElement($domicilio_piso)
			->addElement($domicilio_dto)
			->addElement($domicilio_unidad)
			->addElement($domicilio_localidad)
			->addElement($domicilio_localidad_texto)				
			->addElement($domicilio_cp)			
			->addElement($telefono)
			->addElement($celular)
			->addElement($cel_comp);
		
		$label = kernel::traductor()->trans('censo.localidad.pais');
        $pais = new \Zend_Form_Element_Select('pais', array(
			'label' => $label, 
			'multiOptions' => $paises
		));
		$pais->setValue('');
		
		$label = kernel::traductor()->trans('censo.localidad.provincia');
        $provincia = new \Zend_Form_Element_Select('provincia', array(
			'label' => $label
		));
		$provincia->setValue('');
				
		$label = kernel::traductor()->trans('censo.localidad.localidad');
        $localidad = new \Zend_Form_Element_Text('localidad', array(
			'label' => $label
		));
        $localidad->setValue('');
		
		$label = kernel::traductor()->trans('censo.localidad.buscar');
		$buscar = new \Zend_Form_Element_Submit('buscar', array(
			'label' => $label
		));
		
		$label = kernel::traductor()->trans('censo.localidad.cancelar');
		$cancelar = new \Zend_Form_Element_Submit('cancelar', array(
			'label' => $label
		));
		
		$this->setAction(kernel::vinculador()->crear('censo_docente', 'localidades'))
			->setAttrib('id', 'censo_localidades')
			->addElement($pais)
			->addElement($provincia)
			->addElement($localidad)
			->addElement($cancelar)
			->addElement($buscar);
    }
}
?>