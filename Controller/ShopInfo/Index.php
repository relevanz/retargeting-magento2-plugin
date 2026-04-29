<?php declare(strict_types = 1);
namespace Relevanz\Tracking\Controller\ShopInfo;

use Relevanz\Tracking\Helper\Data as DataHelper;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action
{
    
    private $helper;

    public function __construct(DataHelper $helper, Context $context)
    {
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function execute() : ResultInterface
    {
        if (!$this->helper->isAuthed($this->getRequest()->getParam('auth', ''))) {
            $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $response->setHttpResponseCode(401);
            return $response;
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($this->helper->getShopInfo());
    }

}
