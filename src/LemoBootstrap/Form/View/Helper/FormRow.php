<?php

namespace LemoBootstrap\Form\View\Helper;

use LemoBootstrap\Exception;
use LemoBootstrap\Form\View\Helper\FormElementHelpBlock;
use LemoBootstrap\Form\View\Helper\FormElementHelpInline;
use Zend\Form\ElementInterface;
use Zend\Form\View\Helper\FormRow as FormRowHelper;

class FormRow extends FormRowHelper
{
    const LABEL_APPEND = 'append';
    const LABEL_DEFAULT = null;
    const LABEL_PREPEND = 'prepend';

    /**
     * @var FormElementHelpBlock
     */
    protected $elementHelpBlock;

    /**
     * @var FormElementHelpInline
     */
    protected $elementHelpInline;

    /**
     * @var string
     */
    protected $labelPosition = self::LABEL_DEFAULT;

    /**
     * @var string
     */
    protected $status;

    /**
     * Utility form helper that renders a label (if it exists), an element and errors
     *
     * @param  ElementInterface $element
     * @return string
     */
    public function render(ElementInterface $element)
    {
        $helperEscapeHtml    = $this->getEscapeHtmlHelper();
        $helperLabel         = $this->getLabelHelper();
        $helperElement       = $this->getElementHelper();
        $helperElementErrors = $this->getElementErrorsHelper();
        $helperElementHelpBlock   = $this->getElementHelpBlockHelper();
        $helperElementHelpInline   = $this->getElementHelpInlineHelper();

        $label           = $element->getLabel();
        $inputErrorClass = $this->getInputErrorClass();
        $elementErrors   = $helperElementErrors->setAttributes(array('class' => 'errors'))->render($element);

        // Does this element have errors ?
        if (!empty($elementErrors) && !empty($inputErrorClass)) {
            $classAttributes = ($element->hasAttribute('class') ? $element->getAttribute('class') . ' ' : '');
            $classAttributes = $classAttributes . $inputErrorClass;

            $element->setAttribute('class', $classAttributes);
            $this->setStatus('error');
        }

        if ($this->renderErrors && !empty($elementErrors)) {
            $options = $element->getOptions();
            $options['help-block'] = $elementErrors;

            $element->setOptions($options);
        }

        $elementString     = $helperElement->render($element);
        $elementHelpInline = $helperElementHelpInline->render($element);
        $elementHelpBlock  = $helperElementHelpBlock->render($element);

        // Add element helps
        $elementString .= $elementHelpInline . $elementHelpBlock;

        $elementString = '<div class="controls">' . $elementString . '</div>';

        if (isset($label) && '' !== $label) {
            // Translate the label
            if (null !== ($translator = $this->getTranslator())) {
                $label = $translator->translate(
                    $label, $this->getTranslatorTextDomain()
                );
            }

            $label = $helperEscapeHtml($label);
            $labelAttributes = $element->getLabelAttributes();

            if (empty($labelAttributes)) {
                $labelAttributes = $this->labelAttributes;
            }
            if(!is_array($labelAttributes) || !array_key_exists('for', $labelAttributes)) {
                $labelAttributes['for'] = $this->getId($element);
            }

            if ($element->hasAttribute('id')) {
                $labelOpen = '';
                $labelClose = '';
                $label = $helperLabel($element);
            } else {
                $labelOpen  = $helperLabel->openTag($labelAttributes);
                $labelClose = $helperLabel->closeTag();
            }

            switch ($this->labelPosition) {
                case self::LABEL_PREPEND:
                    $elementString = $labelOpen . $label . $elementString . $labelClose;
                    break;
                case self::LABEL_APPEND:
                    $elementString = $labelOpen . $elementString . $label . $labelClose;
                    break;
                default:
                    $elementString = $labelOpen . $label . $labelClose . $elementString;
                    break;
            }
        }

        return sprintf(
            '<div class="control-group %s" id="control-group-%s">%s</div>',
            $this->getStatus(),
            $this->getId($element),
            $elementString
        );
    }

    /**
     * @param  string|null $status
     * @throws Exception\InvalidArgumentException
     * @return FormRow
     */
    public function setStatus($status)
    {
        if(null !== $status) {
            $status = strtolower($status);
            $statuses = array('error', 'info', 'success', 'warning');

            if(!in_array($status, $statuses)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Invalid status given. Status must be one of \'%s\'',
                    implode(', ', $statuses)
                ));
            }

            $this->status = $status;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Retrieve the FormElement helper
     *
     * @return FormElement
     */
    protected function getElementHelper()
    {
        if ($this->elementHelper) {
            return $this->elementHelper;
        }

        if (method_exists($this->view, 'plugin')) {
            $this->elementHelper = $this->view->plugin('form_element');
        }

        if (!$this->elementHelper instanceof FormElement) {
            $this->elementHelper = new FormElement();
        }

        return $this->elementHelper;
    }

    /**
     * Retrieve the FormElementHelpBlock helper
     *
     * @return FormElementHelpBlock
     */
    protected function getElementHelpBlockHelper()
    {
        if ($this->elementHelpBlock) {
            return $this->elementHelpBlock;
        }

        if (method_exists($this->view, 'plugin')) {
            $this->elementHelpBlock = $this->view->plugin('form_element_help_block');
        }

        if (!$this->elementHelpBlock instanceof FormElementHelpBlock) {
            $this->elementHelpBlock = new FormElementHelpBlock();
        }

        return $this->elementHelpBlock;
    }

    /**
     * Retrieve the FormElementHelpInline helper
     *
     * @return FormElementHelpInline
     */
    protected function getElementHelpInlineHelper()
    {
        if ($this->elementHelpInline) {
            return $this->elementHelpInline;
        }

        if (method_exists($this->view, 'plugin')) {
            $this->elementHelpInline = $this->view->plugin('form_element_help_inline');
        }

        if (!$this->elementHelpInline instanceof FormElementHelpInline) {
            $this->elementHelpInline = new FormElementHelpInline();
        }

        return $this->elementHelpInline;
    }

    /**
     * Retrieve the FormLabel helper
     *
     * @return FormLabel
     */
    protected function getLabelHelper()
    {
        if ($this->labelHelper) {
            return $this->labelHelper;
        }

        if (method_exists($this->view, 'plugin')) {
            $this->labelHelper = $this->view->plugin('form_label');
        }

        if (!$this->labelHelper instanceof FormLabel) {
            $this->labelHelper = new FormLabel();
        }

        if ($this->hasTranslator()) {
            $this->labelHelper->setTranslator(
                $this->getTranslator(),
                $this->getTranslatorTextDomain()
            );
        }

        return $this->labelHelper;
    }
}
