<?php
class Core_Form_Element_Password extends \Core\Form\Element\Xhtml
{
   /**
     * Use formPassword view helper by default
     * @var string
     */
    public $helper = 'formPassword';
    
    /**
     * Whether or not to render the password
     * @var bool
     */
    public $renderPassword = false;

    /**
     * Set flag indicating whether or not to render the password
     * @param  bool $flag
     * @return Zend_Form_Element_Password
     */
    public function setRenderPassword($flag)
    {
        $this->renderPassword = (bool) $flag;
        return $this;
    }

    /**
     * Get value of renderPassword flag
     *
     * @return bool
     */
    public function renderPassword()
    {
        return $this->renderPassword;
    }

    /**
     * Override isValid()
     *
     * Ensure that validation error messages mask password value.
     *
     * @param  string $value
     * @param  mixed $context
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        foreach ($this->getValidators() as $validator) {
            if ($validator instanceof Zend_Validate_Abstract) {
                $validator->setObscureValue(true);
            }
        }
        return parent::isValid($value, $context);
    }
}