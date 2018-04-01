<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@googlemail.com>
 */
namespace Piwik\Plugins\AOM\Platforms\AdWords;

use Google\Auth\CredentialsLoader;
use Google\Auth\OAuth2;
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
     * @param string $clientCustomerId
     * @param string $developerToken
     * @return bool
     */
    public function addAccount($websiteId, $clientId, $clientSecret, $clientCustomerId, $developerToken)
    {
        Piwik::checkUserHasAdminAccess($idSites = [$websiteId]);

        $settings = new SystemSettings();
        $configuration = $settings->getConfiguration();

        $configuration[AOM::PLATFORM_AD_WORDS]['accounts'][uniqid('', true)] = [
            'websiteId' => $websiteId,
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'clientCustomerId' => $clientCustomerId,
            'developerToken' => $developerToken,
            'refreshToken' => null,
            'active' => true,
        ];

        $settings->setConfiguration($configuration);

        return true;
    }

    /**
     * Redirects to AdWords to get a "code" param via AdWords redirect response.
     * This "code" is used in processAccessTokenCode() to obtain both access and refresh token.
     *
     * TODO: Do we really get both tokens?!
     *
     * This code is based on https://github.com/googleads/googleads-php-lib/wiki/Using-OAuth-2.0?hl=de and
     * https://github.com/googleads/googleads-php-lib/blob/master/examples/AdWords/Auth/GetRefreshToken.php?hl=de.
     *
     * @throws \Exception
     */
    public function getRefreshToken()
    {
        $settings = new SystemSettings();
        $configuration = $settings->getConfiguration();

        // Does the account exist?
        $id = Common::getRequestVar('id', false);
        if (!array_key_exists($id, $configuration[AOM::PLATFORM_AD_WORDS]['accounts'])) {
            throw new \Exception('AdWords account "' . $id . '" does not exist.');
        }

        Piwik::checkUserHasAdminAccess(
            $idSites = [$configuration[AOM::PLATFORM_AD_WORDS]['accounts'][$id]['websiteId']]
        );

        $account = $configuration[AOM::PLATFORM_AD_WORDS]['accounts'][$id];

        // Get the authorization URL for the OAuth2 token.
        // We must pass our internal AdWords account ID in the "state" param to get it back in Google's redirect.
        // The name of this param must be "state" according to Google OAuth.
        $oauth2 = new OAuth2([
            'authorizationUri' => 'https://accounts.google.com/o/oauth2/auth',
            'redirectUri' => $this->getRedirectURI(),
            'tokenCredentialUri' => CredentialsLoader::TOKEN_CREDENTIAL_URI,
            'clientId' => $account['clientId'],
            'clientSecret' => $account['clientSecret'],
            'scope' => ['https://www.googleapis.com/auth/adwords'],
            'state' => $id,
        ]);

        $oauth2->updateToken([]);

        $url = $oauth2->buildFullAuthorizationUri(['prompt' => 'consent',]);

        header('Location: ' . $url);
        exit;
    }

    /**
     * AdWords redirects back to us with a "code" param which is used to get both access and refresh token.
     *
     * TODO: Do we really get both tokens?!
     *
     * @throws \Exception
     */
    public function processOAuthRedirect()
    {
        $settings = new SystemSettings();
        $configuration = $settings->getConfiguration();

        // Is there a "state"-param holding an existing AdWords account?
        // The name of this param must be "state" according to Google OAuth.
        $id = Common::getRequestVar('state', false);
        if (!array_key_exists($id, $configuration[AOM::PLATFORM_AD_WORDS]['accounts'])) {
            throw new \Exception('AdWords account "' . $id . '" does not exist.');
        }

        Piwik::checkUserHasAdminAccess(
            $idSites = [$configuration[AOM::PLATFORM_AD_WORDS]['accounts'][$id]['websiteId']]
        );

        // Is there a "code"-param in the URI?
        $code = Common::getRequestVar('code', false);
        if (!$code) {
            throw new \Exception('No code in URI.');
        }

        $account = $configuration[AOM::PLATFORM_AD_WORDS]['accounts'][$id];

        $oauth2 = new OAuth2([
            'authorizationUri' => 'https://accounts.google.com/o/oauth2/auth',
            'redirectUri' => $this->getRedirectURI(),
            'tokenCredentialUri' => CredentialsLoader::TOKEN_CREDENTIAL_URI,
            'clientId' => $account['clientId'],
            'clientSecret' => $account['clientSecret'],
            'scope' => ['https://www.googleapis.com/auth/adwords'],
            'state' => $id,
        ]);

        $oauth2->setCode($code);
        $response = $oauth2->fetchAuthToken();

        if (!array_key_exists('refresh_token', $response)) {
            throw new \Exception('No refresh token in response.');
        }

        // The access token expires but the refresh token doesn't, and should be stored for later use.
        $configuration[AOM::PLATFORM_AD_WORDS]['accounts'][$id]['refreshToken'] = $response['refresh_token'];
        $settings->setConfiguration($configuration);

        header('Location: ?module=AOM&action=settings');
        exit;
    }

    /**
     * The redirect URL back to Piwik. Ensure it's one that has been configured in the Google API console.
     *
     * @return string
     */
    private function getRedirectURI()
    {
        return rtrim(Option::get('piwikUrl'), '/')
        . '?module=AOM&action=platformAction&platform=AdWords&method=processOAuthRedirect';
    }
}
