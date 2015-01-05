<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace TheliaSmarty\Template\Plugins;

use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Thelia\Model\OrderPostage;
use TheliaSmarty\Template\AbstractSmartyPlugin;
use TheliaSmarty\Template\SmartyPluginDescriptor;
use Thelia\Model\AddressQuery;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Country;
use Thelia\Model\CountryQuery;
use Thelia\Model\Customer;
use Thelia\Model\ModuleQuery;
use Thelia\Module\BaseModule;
use Thelia\Module\DeliveryModuleInterface;
use Thelia\Module\Exception\DeliveryException;

/**
 * Class CartPostage
 * @package Thelia\Core\Template\Smarty\Plugins
 */
class CartPostage extends AbstractSmartyPlugin
{
    /** @var \Thelia\Core\HttpFoundation\Request The Request */
    protected $request;

    /** @var ContainerInterface Service Container */
    protected $container = null;

    /** @var integer $countryId the id of country */
    protected $countryId = null;

    /** @var integer $deliveryId the id of the cheapest delivery  */
    protected $deliveryId = null;

    /** @var float $postage the postage amount with taxes */
    protected $postage = null;

    /** @var float $postageTax the postage tax amount */
    protected $postageTax = null;

    /** @var string $postageTaxRuleTitle the postage tax rule title */
    protected $postageTaxRuleTitle = null;

    /** @var boolean $isCustomizable indicate if customer can change the country */
    protected $isCustomizable = true;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->request = $container->get('request');
    }

    /**
     * Get postage amount for cart
     *
     * @param array                     $params   Block parameters
     * @param mixed                     $content  Block content
     * @param \Smarty_Internal_Template $template Template
     * @param bool                      $repeat   Control how many times
     *                                            the block is displayed
     *
     * @return mixed
     */
    public function postage($params, $content, $template, &$repeat)
    {
        if (! $repeat) {
            return (null !== $this->countryId) ? $content : "";
        }

        $customer = $this->request->getSession()->getCustomerUser();
        $country = $this->getDeliveryCountry($customer);

        if (null !== $country) {
            $this->countryId = $country->getId();
            // try to get the cheapest delivery for this country
            $this->getCheapestDelivery($country);
        }

        $template->assign('country_id', $this->countryId);
        $template->assign('delivery_id', $this->deliveryId);
        $template->assign('postage', $this->postage ?: 0.0);
        $template->assign('postage_tax', $this->postageTax ?: 0.0);
        $template->assign('postage_title', $this->postageTaxRuleTitle ?: 0.0);
        $template->assign('is_customizable', $this->isCustomizable);
    }

    /**
     * Retrieve the delivery country for a customer
     *
     * The rules :
     *  - the country of the delivery address of the customer related to the
     *      cart if it exists
     *  - the country saved in cookie if customer have changed
     *      the default country
     *  - the default country for the shop if it exists
     *
     *
     * @param  \Thelia\Model\Customer $customer
     * @return \Thelia\Model\Country
     */
    protected function getDeliveryCountry(Customer $customer = null)
    {
        // get country from customer addresses
        if (null !== $customer) {
            $address = AddressQuery::create()
                ->filterByCustomerId($customer->getId())
                ->filterByIsDefault(1)
                ->findOne()
            ;

            if (null !== $address) {
                $this->isCustomizable = false;

                return $address->getCountry();
            }
        }

        // get country from cookie
        $cookieName = ConfigQuery::read('front_cart_country_cookie_name', 'fcccn');
        if ($this->request->cookies->has($cookieName)) {
            $cookieVal = $this->request->cookies->getInt($cookieName, 0);
            if (0 !== $cookieVal) {
                $country = CountryQuery::create()->findPk($cookieVal);
                if (null !== $country) {
                    return $country;
                }
            }
        }

        // get default country for store.
        try {
            $country = Country::getDefaultCountry();

            return $country;
        } catch (\LogicException $e) {
            ;
        }

        return null;
    }

    /**
     * Retrieve the cheapest delivery for country
     *
     * @param  \Thelia\Model\Country   $country
     * @return DeliveryModuleInterface
     */
    protected function getCheapestDelivery(Country $country)
    {
        $deliveryModules = ModuleQuery::create()
            ->filterByActivate(1)
            ->filterByType(BaseModule::DELIVERY_MODULE_TYPE, Criteria::EQUAL)
            ->find();
        ;

        /** @var \Thelia\Model\Module $deliveryModule */
        foreach ($deliveryModules as $deliveryModule) {
            $moduleInstance = $deliveryModule->getDeliveryModuleInstance($this->container);

            try {
                // Check if module is valid, by calling isValidDelivery(),
                // or catching a DeliveryException.
                if ($moduleInstance->isValidDelivery($country)) {
                    $postage = OrderPostage::loadFromPostage(
                        $moduleInstance->getPostage($country)
                    );

                    if (null === $this->postage || $this->postage > $postage->getAmount()) {
                        $this->postage = $postage->getAmount();
                        $this->postageTax = $postage->getAmountTax();
                        $this->postageTaxRuleTitle = $postage->getTaxRuleTitle();
                        $this->deliveryId = $deliveryModule->getId();
                    }
                }
            } catch (DeliveryException $ex) {
                // Module is not available
            }
        }
    }

    /**
     * Defines the various smarty plugins handled by this class
     *
     * @return \TheliaSmarty\Template\SmartyPluginDescriptor[] smarty plugin descriptors
     */
    public function getPluginDescriptors()
    {
        return array(
            new SmartyPluginDescriptor('block', 'postage', $this, 'postage')
        );
    }
}
