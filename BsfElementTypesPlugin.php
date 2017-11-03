<?php

require('lib/simple_html_dom.php');
require('helpers/BsfElementTypesFunctions.php');

class BsfElementTypesPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
        'initialize',
        'after_save_item',
    );

    protected $_filters = array(
        'element_types_info',
    );

    public function hookInitialize()
    {
        $elementSets =  $this->_db->getTable('ElementSet')->findAll();
        foreach($elementSets as $elementSet){
            $elements =  $this->_db->getTable('Element')->findBySet($elementSet['name']);
            foreach($elements as $element){
                add_filter(
                    array('ElementForm', 'Item', $elementSet['name'], $element['name']),
                    array($this, 'itemValidationCallback')
                );
            }
        }
    }

    public function hookAfterSaveItem($args)
    {
        $elementTexts = $this->_db->getTable('ElementText')->findByRecord($args['record']);
        foreach($elementTexts as $et){
            $elementType = $this->_db->getTable('ElementType')->findByElementId($et['element_id']);
            if(isset($elementType)){
                $etOption = json_decode($elementType['element_type_options'], true);
                $format = $etOption['format'];
                if($elementType['element_type']=='date'){
                    $format = dateFormatToRegexPattern($format);
                }

                if(preg_match("/$format/", $et->text)<=0){
                    $element = $this->_db->getTable('Element')->find($et['element_id']);
                    $name = $element->name;
                    $value = $et->text;
                    Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage(
                        "Field $name with value $value doesn't match format : $format",
                        'error'
                    );
                }
            }
        }
    }

    public function filterElementTypesInfo($types) {
        $types['text'] = array(
            'label' => __('Text'),
            'hooks' => array(
                'OptionsForm' => array($this, 'textHookOptionsForm'),
            ),
        );
        return $types;
    }


    public function textHookOptionsForm($args) {
        $view = get_view();
        $options = $args['element_type']['element_type_options'];
        print $view->formLabel('format', __('Regular expression')) . ' ';
        print $view->formText(
            'format',
            (isset($options) && isset($options['format'])) ? $options['format'] : ''
        );

    }

    /**
     * @param $components : array('label' => [...],'inputs' => [...],'description' => [...],'comment' => [...],'add_input' => [...],)
     * @param $args : array('record' => [...],'element' => [...],'options' => [...],)
     * @return array modified $components
     */
    public function itemValidationCallback(array $components, $args){

        $elementType = $this->_db->getTable('ElementType')->findByElementId($args['element']->id);
        if(isset($elementType)){
            if($elementType['element_type']=='text'){
                $etOption = json_decode($elementType['element_type_options'], true);
                $format = $etOption['format'];

                $html = str_get_html($components['inputs']);
                $inputNode = $html->find('textarea', 0);
                @$inputNode->tag='input';
                $inputNode->attr['type']='text';
                @$inputNode->attr['value']=$inputNode->innertext;
                $inputNode->innertext = '';
                $inputNode->attr['pattern']=$format;
                @$components['inputs'] = $html->__toString();
            }
        }
        return $components;
    }
}
