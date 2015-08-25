<?php

namespace Saxulum\RestCrud\Request\Converter;

use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\XmlRoot("form")
 */
class XmlForm
{
    /**
     * @var string
     * @Serializer\XmlAttribute
     * @Serializer\Type("string")
     */
    protected $name;

    /**
     * @var string
     * @Serializer\XmlValue
     * @Serializer\Type("string")
     */
    protected $value;

    /**
     * @var XmlForm[]
     * @Serializer\XmlList(inline = true, entry = "form")
     * @Serializer\Type("array<Saxulum\RestCrud\Request\Converter\XmlForm>")
     */
    protected $forms;

    /**
     * @return array
     */
    public function toArray()
    {
        if($this->value) {
            if(is_numeric($this->value)) {
                if((int) $this->value == $this->value) {
                    $this->value = (int) $this->value;
                } else {
                    $this->value = (float) $this->value;
                }
            }

            return array($this->name => $this->value);
        }

        $forms = array();
        foreach($this->forms as $form) {
            $forms = array_replace($forms, $form->toArray());
        }

        return array($this->name => $forms);
    }
}
