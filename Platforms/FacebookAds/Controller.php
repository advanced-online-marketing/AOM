<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@googlemail.com>
 */

namespace Piwik\Plugins\AOM\Platforms\FacebookAds;

use Exception;
use League\OAuth2\Client\Provider\Facebook;
use Piwik\Common;
use Piwik\Option;
use Piwik\Piwik;
use Piwik\Plugins\AOM\AOM;
use Piwik\Plugins\AOM\SystemSettings;

class Controller extends \Piwik\Plugins\AOM\Platforms\Controller
{
    /**
     * @param int $websiteId
     * @param string $clientId
     * @param string $clientSecret
     * @param string $accountId
     * @return bool
     */
    public function addAccount($websiteId, $clientId, $clientSecret, $accountId)
    {
        Piwik::checkUserHasAdminAccess($idSites = [$websiteId]);

        $settings = new SystemSettings();
        $configuration = $settings->getConfiguration();

        $configuration[AOM::PLATFORM_FACEBOOK_ADS]['accounts'][uniqid('', true)] = [
            'websiteId'    => $websiteId,
            'clientId'     => $clientId,
            'clientSecret' => $clientSecret,
            'accountId'    => $accountId,
            'accessToken'  => null,
            'active'       => true,
        ];

        $settings->setConfiguration($configuration);

        return true;
    }

    /**
     * Redirects to Facebook to get a "code" param via Facebook redirect response.
     * This "code" is used in processAccessTokenCode() to obtain an access token.
     *
     * @throws \Exception
     */
    public function getAccessToken()
    {
        $settings = new SystemSettings();
        $configuration = $settings->getConfiguration();

        // Does the account exist?
        $id = Common::getRequestVar('id', false);
        if (!array_key_exists($id, $configuration[AOM::PLATFORM_FACEBOOK_ADS]['accounts'])) {
            throw new \Exception('Facebook Ads account "' . $id . '" does not exist.');
        }

        Piwik::checkUserHasAdminAccess(
            $idSites = [$configuration[AOM::PLATFORM_FACEBOOK_ADS]['accounts'][$id]['websiteId']]
        );

        $provider = $this->getFacebookAuthProvider($configuration, $id);
        // If we don't have an authorization code then get one
        $authUrl = $provider->getAuthorizationUrl(['scope' => ['ads_read']]);
        $_SESSION['oauth2state'] = $provider->getState();
        $_SESSION['aom_facebook_website_id'] = $id;

        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * Facebook redirects back to us with a "code" param which is used to get the access token.
     *
     * @throws \Exception
     */
    public function processAccessTokenCode()
    {
        $settings = new SystemSettings();
        $configuration = $settings->getConfiguration();

        if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);
            echo 'Invalid state.';
            exit;
        }

        $id = $_SESSION['aom_facebook_website_id'];

        if (!array_key_exists($id, $configuration[AOM::PLATFORM_FACEBOOK_ADS]['accounts'])) {
            throw new \Exception('Facebook Ads account "' . $id . '" does not exist.');
        }

        Piwik::checkUserHasAdminAccess(
            $idSites = [$configuration[AOM::PLATFORM_FACEBOOK_ADS]['accounts'][$id]['websiteId']]
        );

        $provider = $this->getFacebookAuthProvider($configuration, $id);

        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code'],
        ]);

        try {
            $token = $provider->getLongLivedAccessToken($token);
        } catch (Exception $e) {
            echo 'Failed to exchange the token: ' . $e->getMessage();
            exit();
        }

        $configuration[AOM::PLATFORM_FACEBOOK_ADS]['accounts'][$id]['accessToken'] = $token->getToken();
        $settings->setConfiguration($configuration);

        header('Location: ?module=AOM&action=settings');
        exit;
    }

    /**
     * @param array $configuration
     * @param string $id
     * @return Facebook
     */
    private function getFacebookAuthProvider($configuration, $id)
    {
        return new Facebook([
            'clientId'        => $configuration[AOM::PLATFORM_FACEBOOK_ADS]['accounts'][$id]['clientId'],
            'clientSecret'    => $configuration[AOM::PLATFORM_FACEBOOK_ADS]['accounts'][$id]['clientSecret'],
            'redirectUri'     => Option::get('piwikUrl') . '?module=AOM&action=platformAction&platform=FacebookAds&method=processAccessTokenCode',
            'graphApiVersion' => 'v9.0',
        ]);
    }
}
