<?php declare(strict_types = 1);

namespace Relevanz\Tracking\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class ReadonlyField extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setReadonly(true, true);
        return parent::_getElementHtml($element);
    }
}
