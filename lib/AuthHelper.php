<?php
/**
 * Created by PhpStorm.
 * @author Tareq Mahmood <tareqtms@yahoo.com>
 * Created at: 8/27/16 10:58 AM UTC+06:00
 */

namespace PHPShopify;


use PHPShopify\Exception\SdkException;
use function urlencode;
use function var_dump;

class AuthHelper
{
    /**
     * Get the url of the current page
     *
     * @return string
     */
    public static function getCurrentUrl()
    {
        if (isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
        ) {
            $protocol = 'https';
        } else {
            $protocol = 'http';
        }

        return "$protocol://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }

    /**
     * Verify if the request is made from shopify using hmac hash value
     *
     * @throws SdkException if SharedSecret is not provided or hmac is not found in the url parameters
     *
     * @return bool
     */
    //TODO Rewrite
    // Using Slim Middleware to auth every request
    public static function verifyShopifyRequest()
    {
        return true;
    }

    /**
     * Redirect the user to the authorization page to allow the app access to the shop
     *
     * @see https://help.shopify.com/api/guides/authentication/oauth#scopes For allowed scopes
     *
     * @param string|string[] $scopes Scopes required by app
     * @param string $redirectUrl
     *
     * @throws SdkException if required configuration is not provided in $config
     *
     * @return void
     */
    public static function createAuthRequest($scopes, $redirectUrl = null)
    {
        $config = ShopifySDK::$config;

        if (!isset($config['ShopUrl']) || !isset($config['ApiKey'])) {
            throw new SdkException("ShopUrl and ApiKey are required for authentication request. Please check SDK configuration!");
        }

        if (!$redirectUrl) {
            if (!isset($config['SharedSecret'])) {
                throw new SdkException("SharedSecret is required for getting access token. Please check SDK configuration!");
            }

            //If redirect url is the same as this url, then need to check for access token when redirected back from shopify
            if (isset($_GET['code'])) {
                return self::getAccessToken($config);
            } else {
                $redirectUrl = self::getCurrentUrl();
            }
        }

        if (is_array($scopes)) {
            $scopes = join(',', $scopes);
        }
        $state = urlencode($config['State']);
        $authUrl = $config['AdminUrl'] . 'oauth/authorize?client_id=' . $config['ApiKey'] . '&redirect_uri=' . $redirectUrl . "&scope=$scopes" . "&state=$state";

        return $authUrl;
    }

    /**
     * Get Access token for the API
     * Call this when being redirected from shopify page ( to the $redirectUrl) after authentication
     *
     * @throws SdkException if SharedSecret or ApiKey is missing in SDK configuration or request is not valid
     *
     * @return string
     */
    public static function getAccessToken()
    {
        $config = ShopifySDK::$config;
        if (!isset($config['SharedSecret']) || !isset($config['ApiKey'])) {
            throw new SdkException("SharedSecret and ApiKey are required for getting access token. Please check SDK configuration!");
        }

        if (self::verifyShopifyRequest()) {
            $data = array(
                'client_id' => $config['ApiKey'],
                'client_secret' => $config['SharedSecret'],
                'code' => $_GET['code'],
            );

            $response = HttpRequestJson::post($config['AdminUrl'] . 'oauth/access_token', $data);
            return $response;
//            return isset($response['access_token']) ? $response['access_token'] : null;
        } else {
            throw new SdkException("This request is not initiated from a valid shopify shop!");
        }
    }
}