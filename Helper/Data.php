<?php declare(strict_types = 1);
/**
 * Created by:
 * User: Oleg G
 * Email: oleg.galch87@gmail.com
 * Date: 6/16/17
 * Time: 5:19 PM
 */
namespace Relevanz\Tracking\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Registry;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ObjectManager;
use Releva\Retargeting\Base\AbstractShopInfo;
use Magento\Backend\App\Area\FrontNameResolver;
use Releva\Retargeting\Base\Credentials;
use Releva\Retargeting\Base\RelevanzApi;
use Releva\Retargeting\Base\Exception\RelevanzException;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Relevanz\Tracking\Model\Products;

class Data extends AbstractHelper
{
    const XML_PATH_ENABLED = 'relevanz_tracking/settings/enabled';
    const XML_PATH_CLIENT_ID = 'relevanz_tracking/settings/client_id';
    const XML_PATH_API_KEY = 'relevanz_tracking/settings/api_key';
    const XML_PATH_ADDITIONAL_HTML = 'relevanz_tracking/settings/additional_html';
    
    private $registry;
    
    private $checkoutSession;
    
    private $customer;
    
    private $state;
    
    private $request;
    
    private $storeManager;
    
    private $messageManager;
    
    private $resourceConfig;

    public function __construct(
        Registry $registry,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        State $state,
        StoreManagerInterface $storeManager,
        Context $context,
        ManagerInterface $messageManager,
        ResourceConfig $resourceConfig
    ) {
        $this->registry = $registry;
        $this->checkoutSession = $checkoutSession;
        $this->customer = $customerSession->getCustomer();
        $this->resourceConfig = $resourceConfig;
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->state = $state;
        $this->request = $context->getRequest();
        parent::__construct($context);
    }
    
    public function getRegistry () : Registry
    {
        return $this->registry;
    }
    
    public function getCheckoutSession() : CheckoutSession
    {
        return $this->checkoutSession;
    }
    
    public function getCustomer() : \Magento\Customer\Model\Customer
    {
        return $this->customer;
    }
    public function getShopInfo() : array
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        $shopInfo = [
            'plugin-version' => file_exists(__DIR__.'/../composer.json') ? json_decode(file_get_contents(__DIR__.'/../composer.json'))->version : null,
            'shop' => [
                'system' => 'Magento',
                'version' => ObjectManager::getInstance()->get(ProductMetadataInterface::class)->getVersion(),
            ],
            'environment' => array_merge(
                AbstractShopInfo::getServerEnvironment(),
                ['db' => ObjectManager::getInstance()->get(ResourceConnection::class)->getConnection()->fetchRow('SELECT @@version AS `version`, @@version_comment AS `server`'),]
            ),
            'callbacks' => [
                'callback' => [
                    'url' => sprintf('%srelevanz/shopinfo', $baseUrl),
                    'parameters' => [],
                ],
                'export' => [
                    'url' => sprintf('%srelevanz/products', $baseUrl),
                    'parameters' => [
                        'format' => ['values' => ['csv', 'json', ], 'default' => 'csv', 'optional' => true, ],
                        'page' => ['type' => 'integer', 'default' => 0, 'optional' => true, 'info' => [
                            'items-per-page' => Products::$pageLimit,
                        ], ],
                        'limit' => ['type' => 'integer', 'optional' => true, 'info' => 'changes items-per-page' , ],
                    ],
                ],
            ]
        ];
        
        return $shopInfo;
    }
    
    private function getStoreId() : int
    {
        return 
            $this->state->getAreaCode() === FrontNameResolver::AREA_CODE
            ? (int) $this->request->getParam('store', 0)
            : (int) $this->storeManager->getStore()->getId()
        ;
    }
    
    private function getConfigValue ($key)
    {
        return $this->scopeConfig->getValue($key, ScopeInterface::SCOPE_STORES, $this->getStoreId());
    }
    
    public function isAuthed($auth = '') : bool
    {
        return 
            $this->state->getAreaCode() === FrontNameResolver::AREA_CODE // admin
            || (
                $this->isEnabled()
                && md5($this->getConfigValue(self::XML_PATH_API_KEY).':'.((string) $this->getClientId())) == $auth
            )
        ;
    }
    
    public function isEnabled () : bool
    {
        return (bool) $this->getConfigValue(self::XML_PATH_ENABLED);
    }
    
    public function getClientId() : string
    {
        return (string) $this->getConfigValue(self::XML_PATH_CLIENT_ID);
    }
    
    public function getApiKey() : string
    {
        return (string) $this->getConfigValue(self::XML_PATH_API_KEY);
    }
    
    public function verifyApiKeyAndDisplayErrors (string $apiKey) :? Credentials
    {
        try {
            $credentials = RelevanzApi::verifyApiKey($apiKey, [
                'callback-url' => $this->getShopInfo()['callbacks']['callback']['url'],
            ]);
            $this->resourceConfig->saveConfig(
                self::XML_PATH_CLIENT_ID,
                $credentials->getUserId(),
                $this->getStoreId() ? ScopeInterface::SCOPE_STORES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $this->getStoreId()
            );
            return $credentials;
        } catch (RelevanzException $exception) {
            $this->messageManager->addError(vsprintf($exception->getMessage(), $exception->getSprintfArgs()));
            return null;
        } catch (\Exception $exception) {
            $this->messageManager->addError(__($exception->getMessage()));
            return null;
        }
    }
    
    public function getAdditionalHtml() : string
    {
        return (string) $this->getConfigValue(self::XML_PATH_ADDITIONAL_HTML);
    }

}