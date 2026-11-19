<?php
namespace siu\operaciones\censo\_forms;
use SIU\Chulupi\kernel;

class form_financiamiento extends form_comun
{
	
	protected $form = 'financiamiento';
	
	public function init()
    {
		
				
		$this->crear_elementos();
		
		/*
		$datos = $this->datos;
		
		$form_id = new \Zend_Form_Element_Hidden('form_id');
		$form_id->setValue($this->get_idhtml());
		
		//¿Como costea sus estudios?
		$label = kernel::traductor()->trans('censo.financiamiento.AUX2_ALU_COS_EST_AP_FA');
        $costeo_familiares = new \Zend_Form_Element_Checkbox('AUX2_ALU_COS_EST_AP_FA', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
		$costeo_familiares->setValue($datos['AUX2_ALU_COS_EST_AP_FA']);
		
		$label = kernel::traductor()->trans('censo.financiamiento.AUX2_ALU_COS_EST_PLSOC');
        $costeo_planes = new \Zend_Form_Element_Checkbox('AUX2_ALU_COS_EST_PLSOC', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
		$costeo_planes->setValue($datos['AUX2_ALU_COS_EST_PLSOC']);
		
		$label = kernel::traductor()->trans('censo.financiamiento.AUX2_ALU_COS_EST_TRAB');
        $costeo_trabajo = new \Zend_Form_Element_Checkbox('AUX2_ALU_COS_EST_TRAB', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
		$costeo_trabajo->setValue($datos['AUX2_ALU_COS_EST_TRAB']);
		
		$label = kernel::traductor()->trans('censo.financiamiento.AUX2_ALU_COS_EST_BECA');
        $costeo_beca = new \Zend_Form_Element_Checkbox('AUX2_ALU_COS_EST_BECA', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
		$costeo_beca->setValue($datos['AUX2_ALU_COS_EST_BECA']);		
		
		//Fuente de la beca
		$label = kernel::traductor()->trans('censo.financiamiento.AUX1_TIENE_BECA_UNIV');
        $beca_universidad = new \Zend_Form_Element_Checkbox('AUX1_TIENE_BECA_UNIV', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
		$beca_universidad->setValue($datos['AUX1_TIENE_BECA_UNIV']);
		
		$label = kernel::traductor()->trans('censo.financiamiento.AUX1_TIENE_BECA_INTER');
        $beca_internacional = new \Zend_Form_Element_Checkbox('AUX1_TIENE_BECA_INTER', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
        $beca_internacional->setValue($datos['AUX1_TIENE_BECA_INTER']);
		
		$label = kernel::traductor()->trans('censo.financiamiento.AUX1_TIENE_BECA_NACIO');
        $beca_nacional = new \Zend_Form_Element_Checkbox('AUX1_TIENE_BECA_NACIO', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
        $beca_nacional->setValue($datos['AUX1_TIENE_BECA_NACIO']);
		
		$label = kernel::traductor()->trans('censo.financiamiento.AUX2_ALU_BECA_PROV');
        $beca_provincial = new \Zend_Form_Element_Checkbox('AUX2_ALU_BECA_PROV', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
        $beca_provincial->setValue($datos['AUX2_ALU_BECA_PROV']);
		
		$label = kernel::traductor()->trans('censo.financiamiento.AUX2_ALU_BECA_MUNI');
        $beca_municipal = new \Zend_Form_Element_Checkbox('AUX2_ALU_BECA_MUNI', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
        $beca_municipal->setValue($datos['AUX2_ALU_BECA_MUNI']);
		
		$label = kernel::traductor()->trans('censo.financiamiento.AUX2_ALU_BECA_OTRA');
        $beca_otra = new \Zend_Form_Element_Checkbox('AUX2_ALU_BECA_OTRA', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
        $beca_otra->setValue($datos['AUX2_ALU_BECA_OTRA']);
		
		//Tipo de beca		
		$label = kernel::traductor()->trans('censo.financiamiento.AUX2_ALU_BECA_TIPO_SER');
        $tipo_beca_servicio = new \Zend_Form_Element_Checkbox('AUX2_ALU_BECA_TIPO_SER', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
        $tipo_beca_servicio->setValue($datos['AUX2_ALU_BECA_TIPO_SER']);
		
		$label = kernel::traductor()->trans('censo.financiamiento.AUX2_ALU_BECA_TIPO_INV');
        $tipo_beca_investigacion = new \Zend_Form_Element_Checkbox('AUX2_ALU_BECA_TIPO_INV', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
        $tipo_beca_investigacion->setValue($datos['AUX2_ALU_BECA_TIPO_INV']);
		
		$label = kernel::traductor()->trans('censo.financiamiento.AUX2_ALU_BECA_TIPO_ECO');
        $tipo_beca_economica = new \Zend_Form_Element_Checkbox('AUX2_ALU_BECA_TIPO_ECO', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
        $tipo_beca_economica->setValue($datos['AUX2_ALU_BECA_TIPO_ECO']);
		
		//Tipo de ayuda económica
		$label = kernel::traductor()->trans('censo.financiamiento.AUX2_ALU_BECA_ECO_TRAN');
        $tipo_ayuda_transporte = new \Zend_Form_Element_Checkbox('AUX2_ALU_BECA_ECO_TRAN', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
        $tipo_ayuda_transporte->setValue($datos['AUX2_ALU_BECA_ECO_TRAN']);
		
		$label = kernel::traductor()->trans('censo.financiamiento.AUX2_ALU_BECA_ECO_EFEC');
        $tipo_ayuda_efectivo = new \Zend_Form_Element_Checkbox('AUX2_ALU_BECA_ECO_EFEC', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
        $tipo_ayuda_efectivo->setValue($datos['AUX2_ALU_BECA_ECO_EFEC']);
		
		$label = kernel::traductor()->trans('censo.financiamiento.AUX2_ALU_BECA_ECO_FOTO');
        $tipo_ayuda_fotocopia = new \Zend_Form_Element_Checkbox('AUX2_ALU_BECA_ECO_FOTO', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
        $tipo_ayuda_fotocopia->setValue($datos['AUX2_ALU_BECA_ECO_FOTO']);
		
		$label = kernel::traductor()->trans('censo.financiamiento.AUX2_ALU_BECA_ECO_HABI');
        $tipo_ayuda_habitacional = new \Zend_Form_Element_Checkbox('AUX2_ALU_BECA_ECO_HABI', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
        $tipo_ayuda_habitacional->setValue($datos['AUX2_ALU_BECA_ECO_HABI']);
		
		$label = kernel::traductor()->trans('censo.financiamiento.AUX2_ALU_BECA_ECO_COME');
        $tipo_ayuda_comedor = new \Zend_Form_Element_Checkbox('AUX2_ALU_BECA_ECO_COME', array(
			'label' => $label,
			'CheckedValue' => 'S',
			'UncheckedValue' => 'N',
            'class' => 'editable'
		));
        $tipo_ayuda_comedor->setValue($datos['AUX2_ALU_BECA_ECO_COME']);
				
		$label = kernel::traductor()->trans('censo.financiamiento.AUX2_ALU_COS_EST_ESPEC');
        $otro_financiamiento = new \Zend_Form_Element_Text('AUX2_ALU_COS_EST_ESPEC', array('label' => $label));
        $otro_financiamiento->setValue($datos['AUX2_ALU_COS_EST_ESPEC']);
		
		$this->setAction(kernel::vinculador()->crear('censo', 'financiamiento'))
			->setAttrib('id', 'censo_financiamiento')
			->addElement($form_id)
			->addElement($costeo_familiares)
			->addElement($costeo_planes)
			->addElement($costeo_trabajo)
			->addElement($costeo_beca)
			
			->addElement($beca_universidad)
			->addElement($beca_internacional)
			->addElement($beca_nacional)
			->addElement($beca_provincial)
			->addElement($beca_municipal)			
			->addElement($beca_otra)
				
			->addElement($tipo_beca_servicio)
			->addElement($tipo_beca_investigacion)
			->addElement($tipo_beca_economica)
				
			->addElement($tipo_ayuda_transporte)
			->addElement($tipo_ayuda_efectivo)
			->addElement($tipo_ayuda_fotocopia)
			->addElement($tipo_ayuda_habitacional)
			->addElement($tipo_ayuda_comedor)				
				
			->addElement($otro_financiamiento);
		 * 
		 * 
		 */
    }
}
?>