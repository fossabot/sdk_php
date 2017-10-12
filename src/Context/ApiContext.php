<?php
namespace bunq\Context;

use bunq\Exception\BunqException;
use bunq\Model\Core\Installation;
use bunq\Model\Core\SessionServer;
use bunq\Model\Core\Token;
use bunq\Model\Generated\Endpoint\DeviceServer;
use bunq\Model\Generated\Endpoint\Session;
use bunq\Security\KeyPair;
use bunq\Util\BunqEnumApiEnvironmentType;
use bunq\Util\FileUtil;
use GuzzleHttp\Psr7\Uri;

/**
 */
class ApiContext
{
    /**
     * Api environment urls.
     */
    const BASE_URL_PRODUCTION = 'https://api.bunq.com/v1/';
    const BASE_URL_SANDBOX = 'https://sandbox.public.api.bunq.com/v1/';

    /**
     * Error constants.
     */
    const ERROR_CONTEXT_FILE_NOT_FOUND = 'Could not find the API context file "%s".';
    const ERROR_COULD_NOT_SAVE_THE_API_CONTEXT = 'Could not save the API context to "%s".';
    const ERROR_CONTEXT_NOT_INSTALLED = 'Context not yet installed. Please finish installation first.';
    const ERROR_CONTEXT_HAS_NO_SESSION = 'Context doesn\'t have a session yet. Please finish installation first.';
    const ERROR_ENVIRONMENT_TYPE_UNEXPECTED = 'Unexpected environment type "%s".';

    /**
     * Api context fields.
     */
    const FIELD_API_CONTEXT = 'api_context';
    const FIELD_INSTALLATION_CONTEXT = 'installation_context';
    const FIELD_SESSION_CONTEXT = 'session_context';
    const FIELD_ENVIRONMENT_TYPE = 'environment_type';
    const FIELD_API_KEY = 'api_key';
    const FIELD_PROXY_URL = 'proxy_url';

    /**
     * Dummy ID to pass to Session endpoint.
     */
    const SESSION_ID_DUMMY = 0;

    /**
     * Minimum time to session expiry not requiring session reset.
     */
    const TIME_TO_SESSION_EXPIRY_MINIMUM_SECONDS = 30;

    /**
     * Api context file constants.
     */
    const FILENAME_CONFIG_DEFAULT = 'bunq.conf';

    /**
     * @var BunqEnumApiEnvironmentType
     */
    protected $environmentType;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var SessionContext|null
     */
    protected $sessionContext;

    /**
     * @var InstallationContext|null
     */
    protected $installationContext;

    /**
     * @var string
     */
    protected $proxyUrl;

    /**
     */
    private function __construct()
    {
    }

    /**
     * @param BunqEnumApiEnvironmentType $environmentType
     * @param string $apiKey
     * @param string $description
     * @param string[] $permittedIps
     * @param string|null $proxyUrl
     *
     * @return static
     */
    public static function create(
        BunqEnumApiEnvironmentType $environmentType,
        string $apiKey,
        string $description,
        array $permittedIps = [],
        string $proxyUrl = null
    ): ApiContext {
        $apiContext = new static();
        $apiContext->environmentType = $environmentType;
        $apiContext->apiKey = $apiKey;
        $apiContext->proxyUrl = $proxyUrl;
        $apiContext->initialize($description, $permittedIps);

        return $apiContext;
    }

    /**
     * @param string $description
     * @param string[] $permittedIps
     */
    private function initialize(string $description, array $permittedIps)
    {
        $this->initializeInstallationContext();
        $this->registerDevice($description, $permittedIps);
        $this->initializeSessionContext();
    }

    /**
     */
    private function initializeInstallationContext()
    {
        $keyPairClient = KeyPair::generate();
        $installation = Installation::create(
            $this,
            $keyPairClient->getPublicKey()->getKey()
        )->getValue();
        $this->installationContext = new InstallationContext(
            $installation->getToken(),
            $keyPairClient,
            $installation->getServerPublicKey()->getServerPublicKey()
        );
    }

    /**
     * @param string $description
     * @param string[] $permittedIps
     */
    private function registerDevice(string $description, array $permittedIps)
    {
        DeviceServer::create(
            $this,
            [
                DeviceServer::FIELD_DESCRIPTION => $description,
                DeviceServer::FIELD_PERMITTED_IPS => $permittedIps,
                DeviceServer::FIELD_SECRET => $this->apiKey,
            ]
        );
    }

    /**
     * Create a new session and its data in a SessionContext.
     */
    private function initializeSessionContext()
    {
        $sessionServer = SessionServer::create($this)->getValue();
        $this->sessionContext = SessionContext::create($sessionServer);
    }

    /**
     * @param string $fileName
     *
     * @return ApiContext
     */
    public static function restore(string $fileName = self::FILENAME_CONFIG_DEFAULT): ApiContext
    {
        $contextJsonString = self::getContextJsonString($fileName);

        return static::fromJson($contextJsonString);
    }

    /**
     * @param string $fileName
     *
     * @return string
     * @throws BunqException When the context couldn't be loaded from the given location.
     */
    private static function getContextJsonString(string $fileName): string
    {
        try {
            return FileUtil::getFileContents($fileName);
        } catch (BunqException $exception) {
            throw new BunqException(self::ERROR_CONTEXT_FILE_NOT_FOUND, [$fileName]);
        }
    }

    /**
     * @param string $jsonString
     *
     * @return ApiContext
     */
    public static function fromJson(string $jsonString): ApiContext
    {
        $apiContext = new static();
        $contextJson = \GuzzleHttp\json_decode($jsonString, true);
        $apiContext->environmentType = new BunqEnumApiEnvironmentType($contextJson[self::FIELD_ENVIRONMENT_TYPE]);
        $apiContext->apiKey = $contextJson[self::FIELD_API_KEY];
        $apiContext->proxyUrl = static::restoreProxyUrl($contextJson);
        $apiContext->installationContext = InstallationContext::restore($contextJson[self::FIELD_INSTALLATION_CONTEXT]);
        $apiContext->sessionContext = SessionContext::restore($contextJson[self::FIELD_SESSION_CONTEXT]);

        return $apiContext;
    }

    /**
     * @param mixed[] $contextJson
     *
     * @return string|null
     */
    private static function restoreProxyUrl(array $contextJson)
    {
        if (isset($contextJson[self::FIELD_PROXY_URL])) {
            return $contextJson[self::FIELD_PROXY_URL];
        } else {
            return null;
        }
    }

    /**
     * @return Uri
     */
    public function determineBaseUri(): Uri
    {
        return new Uri($this->determineBaseUriString());
    }

    /**
     * @return string
     * @throws BunqException when the environment type is not expected.
     */
    private function determineBaseUriString(): string
    {
        if ($this->environmentType->getChoiceString() === BunqEnumApiEnvironmentType::CHOICE_PRODUCTION) {
            return self::BASE_URL_PRODUCTION;
        } elseif ($this->environmentType->getChoiceString() === BunqEnumApiEnvironmentType::CHOICE_SANDBOX) {
            return self::BASE_URL_SANDBOX;
        } else {
            throw new BunqException(
                self::ERROR_ENVIRONMENT_TYPE_UNEXPECTED,
                [
                    $this->environmentType->getChoiceString()
                ]
            );
        }
    }

    /**
     * Closes the current session.
     */
    public function closeSession()
    {
        Session::delete($this, self::SESSION_ID_DUMMY);
        $this->dropSessionContext();
    }

    /**
     */
    private function dropSessionContext()
    {
        $this->sessionContext = null;
    }

    /**
     * Check if current time is too close to the saved session expiry time and reset session if
     * needed.
     */
    public function ensureSessionActive()
    {
        if (!is_null($this->sessionContext) && $this->isExpired()) {
            $this->resetSession();
        }
    }

    /**
     * Checks if the session has expired
     *
     * @return bool
     */
    public function isExpired()
    {
        $timeExpiry = $this->sessionContext->getExpiryTime()->getTimestamp();

        return is_null($this->sessionContext) || $timeExpiry - time() < self::TIME_TO_SESSION_EXPIRY_MINIMUM_SECONDS;
    }

    /**
     * Closes the current session and opens a new one.
     */
    public function resetSession()
    {
        $this->dropSessionContext();
        $this->initializeSessionContext();
    }

    /**
     * @return Token|null
     */
    public function getSessionToken()
    {
        if (!is_null($this->sessionContext)) {
            return $this->sessionContext->getSessionToken();
        } elseif (!is_null($this->installationContext)) {
            return $this->installationContext->getInstallationToken();
        } else {
            return null;
        }
    }

    /**
     * @param string $fileName
     *
     * @throws BunqException When the context couldn't be saved to the given location.
     */
    public function save(string $fileName = self::FILENAME_CONFIG_DEFAULT)
    {
        $saved = file_put_contents($fileName, $this->toJson());

        if ($saved === false) {
            throw new BunqException(self::ERROR_COULD_NOT_SAVE_THE_API_CONTEXT, [$fileName]);
        }
    }

    /**
     * @return string
     * @throws BunqException when the context is incomplete.
     */
    public function toJson(): string
    {
        if (is_null($this->getInstallationContext())) {
            throw new BunqException(self::ERROR_CONTEXT_NOT_INSTALLED);
        } elseif (is_null($this->getSessionContext())) {
            throw new BunqException(self::ERROR_CONTEXT_HAS_NO_SESSION);
        }

        $context = [
            self::FIELD_API_KEY => $this->getApiKey(),
            self::FIELD_ENVIRONMENT_TYPE => $this->getEnvironmentType()->getChoiceString(),
            self::FIELD_INSTALLATION_CONTEXT => $this->getInstallationContext(),
            self::FIELD_PROXY_URL => $this->getProxy(),
            self::FIELD_SESSION_CONTEXT => $this->getSessionContext(),
        ];

        return \GuzzleHttp\json_encode($context, JSON_PRETTY_PRINT);
    }

    /**
     * @return InstallationContext|null
     */
    public function getInstallationContext()
    {
        return $this->installationContext;
    }

    /**
     * @return SessionContext|null
     */
    public function getSessionContext()
    {
        return $this->sessionContext;
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @return BunqEnumApiEnvironmentType
     */
    public function getEnvironmentType(): BunqEnumApiEnvironmentType
    {
        return $this->environmentType;
    }

    /**
     * @return string|null
     */
    public function getProxy()
    {
        return $this->proxyUrl;
    }
}
