<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\exception\BunqException;
use bunq\Http\ApiClient;
use bunq\Model\Core\AnchorObjectInterface;
use bunq\Model\Core\BunqModel;

/**
 * Using this call you can retrieve information of the user you are logged
 * in as. This includes your user id, which is referred to in endpoints.
 *
 * @generated
 */
class User extends BunqModel implements AnchorObjectInterface
{
    /**
     * Error constants.
     */
    const ERROR_NULL_FIELDS = 'All fields of an extended model or object are null.';

    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_READ = 'user/%s';
    const ENDPOINT_URL_LISTING = 'user';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'User';

    /**
     * @var UserLight
     */
    protected $userLight;

    /**
     * @var UserPerson
     */
    protected $userPerson;

    /**
     * @var UserCompany
     */
    protected $userCompany;

    /**
     * @var UserApiKey
     */
    protected $userApiKey;

    /**
     * Get a specific user.
     *
     * @param int $userId
     * @param string[] $customHeaders
     *
     * @return BunqResponseUser
     */
    public static function get(int $userId, array $customHeaders = []): BunqResponseUser
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId()]
            ),
            [],
            $customHeaders
        );

        return BunqResponseUser::castFromBunqResponse(
            static::fromJson($responseRaw)
        );
    }

    /**
     * Get a collection of all available users.
     *
     * This method is called "listing" because "list" is a restricted PHP word
     * and cannot be used as constants, class names, function or method names.
     *
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponseUserList
     */
    public static function listing(array $params = [], array $customHeaders = []): BunqResponseUserList
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_LISTING,
                []
            ),
            $params,
            $customHeaders
        );

        return BunqResponseUserList::castFromBunqResponse(
            static::fromJsonList($responseRaw)
        );
    }

    /**
     * @return UserLight
     */
    public function getUserLight()
    {
        return $this->userLight;
    }

    /**
     * @deprecated User should not be able to set values via setters, use
     * constructor.
     *
     * @param UserLight $userLight
     */
    public function setUserLight($userLight)
    {
        $this->userLight = $userLight;
    }

    /**
     * @return UserPerson
     */
    public function getUserPerson()
    {
        return $this->userPerson;
    }

    /**
     * @deprecated User should not be able to set values via setters, use
     * constructor.
     *
     * @param UserPerson $userPerson
     */
    public function setUserPerson($userPerson)
    {
        $this->userPerson = $userPerson;
    }

    /**
     * @return UserCompany
     */
    public function getUserCompany()
    {
        return $this->userCompany;
    }

    /**
     * @deprecated User should not be able to set values via setters, use
     * constructor.
     *
     * @param UserCompany $userCompany
     */
    public function setUserCompany($userCompany)
    {
        $this->userCompany = $userCompany;
    }

    /**
     * @return UserApiKey
     */
    public function getUserApiKey()
    {
        return $this->userApiKey;
    }

    /**
     * @deprecated User should not be able to set values via setters, use
     * constructor.
     *
     * @param UserApiKey $userApiKey
     */
    public function setUserApiKey($userApiKey)
    {
        $this->userApiKey = $userApiKey;
    }

    /**
     * @return BunqModel
     * @throws BunqException
     */
    public function getReferencedObject()
    {
        if (!is_null($this->userLight)) {
            return $this->userLight;
        }

        if (!is_null($this->userPerson)) {
            return $this->userPerson;
        }

        if (!is_null($this->userCompany)) {
            return $this->userCompany;
        }

        if (!is_null($this->userApiKey)) {
            return $this->userApiKey;
        }

        throw new BunqException(self::ERROR_NULL_FIELDS);
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->userLight)) {
            return false;
        }

        if (!is_null($this->userPerson)) {
            return false;
        }

        if (!is_null($this->userCompany)) {
            return false;
        }

        if (!is_null($this->userApiKey)) {
            return false;
        }

        return true;
    }
}
