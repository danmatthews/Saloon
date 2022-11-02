<?php declare(strict_types=1);

namespace Sammyjo20\Saloon\Traits\OAuth2;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Sammyjo20\Saloon\Helpers\URLHelper;
use Sammyjo20\Saloon\Helpers\StateHelper;
use Sammyjo20\Saloon\Helpers\OAuth2\OAuthConfig;
use Sammyjo20\Saloon\Http\OAuth2\GetUserRequest;
use Sammyjo20\Saloon\Contracts\OAuthAuthenticator;
use Sammyjo20\Saloon\Http\Responses\SaloonResponse;
use Sammyjo20\Saloon\Exceptions\InvalidStateException;
use Sammyjo20\Saloon\Http\OAuth2\GetAccessTokenRequest;
use Sammyjo20\Saloon\Http\Auth\AccessTokenAuthenticator;
use Sammyjo20\Saloon\Http\OAuth2\GetRefreshTokenRequest;

trait AuthorizationCodeGrant
{
    /**
     * The OAuth2 Config
     *
     * @var OAuthConfig
     */
    protected OAuthConfig $oauthConfig;

    /**
     * The state generated by the getAuthorizationUrl method.
     *
     * @var string|null
     */
    protected ?string $state = null;

    /**
     * Manage the OAuth2 config
     *
     * @return OAuthConfig
     */
    public function oauthConfig(): OAuthConfig
    {
        return $this->oauthConfig ??= $this->defaultOauthConfig();
    }

    /**
     * Define the default Oauth 2 Config.
     *
     * @return OAuthConfig
     */
    protected function defaultOauthConfig(): OAuthConfig
    {
        return OAuthConfig::make();
    }

    /**
     * Get the Authorization URL.
     *
     * @param array $scopes
     * @param string $scopeSeparator
     * @param string|null $state
     * @return string
     * @throws \Sammyjo20\Saloon\Exceptions\OAuthConfigValidationException
     */
    public function getAuthorizationUrl(array $scopes = [], string $state = null, string $scopeSeparator = ' '): string
    {
        $config = $this->oauthConfig();

        $config->validate();

        $clientId = $config->getClientId();
        $redirectUri = $config->getRedirectUri();
        $defaultScopes = $config->getDefaultScopes();

        $this->state = $state ?? StateHelper::createRandomState();

        $queryParameters = [
            'response_type' => 'code',
            'scope' => implode($scopeSeparator, array_merge($defaultScopes, $scopes)),
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'state' => $this->state,
        ];

        $query = http_build_query($queryParameters, '', '&', PHP_QUERY_RFC3986);
        $query = trim($query, '?&');

        $url = URLHelper::join($this->defineBaseUrl(), $config->getAuthorizeEndpoint());

        $glue = str_contains($url, '?') ? '&' : '?';

        return $url . $glue . $query;
    }

    /**
     * Get the access token.
     *
     * @param string $code
     * @param string|null $state
     * @param string|null $expectedState
     * @param bool $returnResponse
     * @return OAuthAuthenticator|SaloonResponse
     * @throws InvalidStateException
     * @throws \JsonException
     * @throws \ReflectionException
     * @throws \Sammyjo20\Saloon\Exceptions\OAuthConfigValidationException
     * @throws \Sammyjo20\Saloon\Exceptions\SaloonException
     * @throws \Sammyjo20\Saloon\Exceptions\SaloonRequestException
     */
    public function getAccessToken(string $code, string $state = null, string $expectedState = null, bool $returnResponse = false): OAuthAuthenticator|SaloonResponse
    {
        $this->oauthConfig()->validate();

        if (! empty($state) && ! empty($expectedState) && $state !== $expectedState) {
            throw new InvalidStateException;
        }

        $response = $this->send(new GetAccessTokenRequest($code, $this->oauthConfig()));

        if ($returnResponse === true) {
            return $response;
        }

        $response->throw();

        return $this->createOAuthAuthenticatorFromResponse($response);
    }

    /**
     * Refresh the access token.
     *
     * @param OAuthAuthenticator|string $refreshToken
     * @param bool $returnResponse
     * @return OAuthAuthenticator|SaloonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \ReflectionException
     * @throws \Sammyjo20\Saloon\Exceptions\OAuthConfigValidationException
     * @throws \Sammyjo20\Saloon\Exceptions\SaloonException
     * @throws \Sammyjo20\Saloon\Exceptions\SaloonRequestException
     */
    public function refreshAccessToken(OAuthAuthenticator|string $refreshToken, bool $returnResponse = false): OAuthAuthenticator|SaloonResponse
    {
        $this->oauthConfig()->validate();

        if ($refreshToken instanceof OAuthAuthenticator) {
            $refreshToken = $refreshToken->getRefreshToken();
        }

        $response = $this->send(new GetRefreshTokenRequest($this->oauthConfig(), $refreshToken));

        if ($returnResponse === true) {
            return $response;
        }

        $response->throw();

        return $this->createOAuthAuthenticatorFromResponse($response, $refreshToken);
    }

    /**
     * Create the OAuthAuthenticator from a response.
     *
     * @param SaloonResponse $response
     * @param string|null $fallbackRefreshToken
     * @return OAuthAuthenticator
     * @throws \JsonException
     */
    protected function createOAuthAuthenticatorFromResponse(SaloonResponse $response, string $fallbackRefreshToken = null): OAuthAuthenticator
    {
        $responseData = $response->object();

        $accessToken = $responseData->access_token;
        $refreshToken = $responseData->refresh_token ?? $fallbackRefreshToken;
        $expiresAt = CarbonImmutable::now()->addSeconds($responseData->expires_in);

        return $this->createOAuthAuthenticator($accessToken, $refreshToken, $expiresAt);
    }

    /**
     * Create the authenticator.
     *
     * @param string $accessToken
     * @param string $refreshToken
     * @param CarbonInterface $expiresAt
     * @return OAuthAuthenticator
     */
    protected function createOAuthAuthenticator(string $accessToken, string $refreshToken, CarbonInterface $expiresAt): OAuthAuthenticator
    {
        return new AccessTokenAuthenticator($accessToken, $refreshToken, $expiresAt);
    }

    /**
     * Get the authenticated user.s
     *
     * @param OAuthAuthenticator $oauthAuthenticator
     * @return SaloonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \ReflectionException
     * @throws \Sammyjo20\Saloon\Exceptions\SaloonException
     */
    public function getUser(OAuthAuthenticator $oauthAuthenticator): SaloonResponse
    {
        return $this->send(
            GetUserRequest::make($this->oauthConfig())->withAuth($oauthAuthenticator)
        );
    }

    /**
     * Get the state that was generated in the getAuthorizationUrl() method.
     *
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }
}
