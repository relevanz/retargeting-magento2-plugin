<?php declare(strict_types = 1);
/**
 * Created by:
 * User: Oleg G
 * Email: oleg.galch87@gmail.com
 * Date: 6/16/17
 * Time: 6:08 PM
 */
namespace Relevanz\Tracking\Block;

use Relevanz\Tracking\Helper\Data as Helper;
use Releva\Retargeting\Base\RelevanzApi;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;

abstract class AbstractTracking extends Template
{
    
    protected $helper;
    
    protected $url = RelevanzApi::RELEVANZ_TRACKER_URL;
    
    public function __construct(Helper $helper, TemplateContext $context, array $data = [])
    {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }
    
    public function isActive() : bool
    {
        return $this->helper->getClientId() && $this->helper->isEnabled();
    }
    
    public function getAdditionalHtml() : string
    {
        return $this->helper->getAdditionalHtml();
    }
    
    abstract protected function getParameters() : array;
    
    public function getScriptParameters () : array
    {
        $parameters = $this->getParameters();
        $parameters['cid'] = (string) $this->helper->getClientId();
        if($customerId = $this->helper->getCustomer()->getId()) {
            $parameters['custid'] = $customerId;
        }
        return [
            'src' => $this->url.'?'.http_build_query($parameters),
            'async' => 'true',
        ];
    }
}
