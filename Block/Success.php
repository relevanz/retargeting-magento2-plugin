<?php declare(strict_types = 1);
/**
 * Created by:
 * User: Oleg G
 * Email: oleg.galch87@gmail.com
 * Date: 6/16/17
 * Time: 6:05 PM
 */

/**
 * tracking url example: https://d.hyj.mobi/conv?cid=CLIENT_ID&orderId=ORDER_ID&amount=ORDER_TOTAL&products=PRODUCT_ID1,PRODUCT_ID2
 */

namespace Relevanz\Tracking\Block;

use Relevanz\Tracking\Block\AbstractTracking;
use Releva\Retargeting\Base\RelevanzApi;

class Success extends AbstractTracking
{

    protected $url = RelevanzApi::RELEVANZ_CONV_URL;

    protected function getParameters() : array
    {
        $parameters = [];
        if($order = $this->helper->getCheckoutSession()->getLastRealOrder()) {
            /* @var $order \Magento\Sales\Model\Order */
            $itemsIds = [];
            foreach ($order->getAllVisibleItems() as $product) {
                $itemsIds[] = $product->getProductId();
            }
            $parameters['orderId'] = (string) $order->getIncrementId();
            $parameters['amount'] = number_format((float) $order->getSubtotal(), 2, '.', '');
            $parameters['products'] = implode(',', $itemsIds);
        }
        return $parameters;
    }

}