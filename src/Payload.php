<?php namespace Moosend;

use InvalidArgumentException;
use Exception;
use Moosend\Models;

/**
 * Class Payload
 * @package Moosend
 */
class Payload
{

    /**
     * @var Cookie
     */
    private $cookie;

    /**
     * @var
     */
    private $siteId;

    /**
     * @var
     */
    private $userId;

    public function __construct(Cookie $cookie, $siteId, $userId)
    {
        if (empty($siteId) || empty($userId)) {
            throw new Exception('$siteId or $userId cannot be empty');
        }

        $this->cookie = $cookie;
        $this->siteId = $siteId;
        $this->userId = $userId;
    }

    /**
     * Generates payload for identify events
     *
     * @param $email
     * @param string $name
     * @param array $properties
     * @return array
     * @throws InvalidArgumentException
     */
    public function getIdentify($email, $name = '', $properties = [])
    {
        if (!$email) {
            throw new InvalidArgumentException('E-mail cannot be empty or null');
        }

        //props that we will combine with default props
        $props = [
            PayloadProperties::EMAIL => $email
        ];

        if ($name) {
            $props[PayloadProperties::NAME] = $name;
        }

        if ($properties) {
            $props[PayloadProperties::PROPERTIES] = $properties;
        }

        return $this->getTrackPayload(ActionTypes::IDENTIFY, $props);
    }

    /**
     * Generates payload for page view events
     *
     * @param $url
     * @param array $properties
     * @return array
     * @throws Exception
     */
    public function getPageView($url, $properties = [])
    {
        if (empty($url)) {
            throw new Exception('url cannot be empty');
        }

        $props = [
            PayloadProperties::URL => $url
        ];

        if ($properties) {
            $props[PayloadProperties::PROPERTIES] = $properties;
        }

        return $this->getTrackPayload(ActionTypes::PAGE_VIEW, $props);
    }

    /**
     * Generates payload for add to order events
     *
     * @param Models\Product $product
     * @return array
     */
    public function getAddToOrder(Models\Product $product)
    {
        return $this->getTrackPayload(ActionTypes::ADD_TO_ORDER, [
            PayloadProperties::PROPERTIES =>
                [
                    [
                        PayloadProperties::PRODUCT => $product->toArray()
                    ]
                ]
        ]);
    }

    /**
     * Generates payload for order completed events
     *
     * @param Models\Order $order
     * @throws Exception
     * @return array
     */
    public function getOrderCompleted(Models\Order $order)
    {
        if (!$order->hasProducts()) {

            throw new \Exception('$order should have at least one product');
        }

        return $this->getTrackPayload(ActionTypes::ORDER_COMPLETED, [
            PayloadProperties::PROPERTIES => [
                [
                    PayloadProperties::ORDER_TOTAL_PRICE => $order->getOrderTotal(),
                    PayloadProperties::PRODUCTS => $order->toArray()
                ]
            ]
        ]);
    }

    /**
     * Generates payload for custom events
     *
     * @param $event
     * @param array $properties
     * @return array
     */
    public function getCustom($event, $properties = [])
    {

        $properties = empty($properties) ? [] : [PayloadProperties::PROPERTIES => $properties];

        return $this->getTrackPayload($event, $properties);
    }

    /**
     * Returns the site id
     *
     * @return string
     * */
    public function getSiteId()
    {

        return $this->siteId;
    }

    private function getTrackPayload($actionType, $props)
    {
        $email = $this->cookie->getCookie(CookieNames::USER_EMAIL);

        $mandatoryProps = [
            PayloadProperties::ACTION_TYPE => $actionType,
            PayloadProperties::CONTACT_ID => $this->userId,
            PayloadProperties::SITE_ID => $this->siteId,
            PayloadProperties::EMAIL => $email
        ];

        if ($this->hasCampaignId()) {
            $mandatoryProps[PayloadProperties::CAMPAIGN_ID] = $this->cookie->getCookie(CookieNames::CAMPAIGN_ID);
        }

        return array_merge($mandatoryProps, $props);
    }

    private function hasCampaignId()
    {
        return !!$this->cookie->getCookie(CookieNames::CAMPAIGN_ID);
    }
}
