<?php
namespace bunq\Http;

use bunq\Exception\BunqException;
use function GuzzleHttp\Psr7\parse_query;

/**
 */
class Pagination
{
    /**
     * Error constants.
     */
    const ERROR_NO_PREVIOUS_PAGE = 'Could not generate previous page URL params: there is no previous page.';

    /**
     * URL Param constants.
     */
    const PARAM_OLDER_ID = 'older_id';
    const PARAM_NEWER_ID = 'newer_id';
    const PARAM_FUTURE_ID = 'future_id';
    const PARAM_COUNT = 'count';

    /**
     * Field constants.
     */
    const FIELD_OLDER_URL = 'older_url';
    const FIELD_NEWER_URL = 'newer_url';
    const FIELD_FUTURE_URL = 'future_url';

    /**
     * @var int
     */
    protected $olderId;

    /**
     * @var int
     */
    protected $newerId;

    /**
     * @var int
     */
    protected $futureId;

    /**
     * @var int
     */
    protected $count;

    /**
     * @param string[] $paginationJson
     *
     * @return static
     */
    public static function restore(array $paginationJson)
    {
        $paginationBody = static::parsePaginationBody($paginationJson);

        $pagination = new static();
        $pagination->setOlderId($paginationBody[self::PARAM_OLDER_ID]);
        $pagination->setNewerId($paginationBody[self::PARAM_NEWER_ID]);
        $pagination->setFutureId($paginationBody[self::PARAM_FUTURE_ID]);
        $pagination->setCount($paginationBody[self::PARAM_COUNT]);

        return $pagination;
    }

    /**
     * @param string[] $paginationResponse
     *
     * @return string[]
     */
    private static function parsePaginationBody(array $paginationResponse)
    {
        $paginationBody = [
            self::PARAM_OLDER_ID => null,
            self::PARAM_NEWER_ID => null,
            self::PARAM_FUTURE_ID => null,
            self::PARAM_COUNT => null,
        ];
        static::updatePaginationBodyIdFieldFromResponseField(
            $paginationBody,
            self::PARAM_OLDER_ID,
            $paginationResponse,
            self::FIELD_OLDER_URL,
            self::PARAM_OLDER_ID
        );
        static::updatePaginationBodyIdFieldFromResponseField(
            $paginationBody,
            self::PARAM_NEWER_ID,
            $paginationResponse,
            self::FIELD_NEWER_URL,
            self::PARAM_NEWER_ID
        );
        static::updatePaginationBodyIdFieldFromResponseField(
            $paginationBody,
            self::PARAM_FUTURE_ID,
            $paginationResponse,
            self::FIELD_FUTURE_URL,
            self::PARAM_NEWER_ID
        );

        return $paginationBody;
    }

    /**
     * @param string[] &$paginationBody
     * @param string $idField
     * @param string[] $response
     * @param string $responseField
     * @param string $responseParam
     */
    private static function updatePaginationBodyIdFieldFromResponseField(
        array &$paginationBody,
        $idField,
        array $response,
        $responseField,
        $responseParam
    ) {
        $url = $response[$responseField];

        if (!is_null($url)) {
            $urlQuery = parse_url($url, PHP_URL_QUERY);
            $parameters = parse_query($urlQuery);
            $paginationBody[$idField] = $parameters[$responseParam];

            if (isset($parameters[self::PARAM_COUNT]) && !isset($paginationBody[self::PARAM_COUNT])) {
                $paginationBody[self::PARAM_COUNT] = $parameters[self::PARAM_COUNT];
            }
        }
    }

    /**
     * @param int $olderId
     */
    public function setOlderId($olderId)
    {
        $this->olderId = $olderId;
    }

    /**
     * @param int $newerId
     */
    public function setNewerId($newerId)
    {
        $this->newerId = $newerId;
    }

    /**
     * @param int $futureId
     */
    public function setFutureId($futureId)
    {
        $this->futureId = $futureId;
    }

    /**
     * @param int $count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    /**
     * @return string[]
     */
    public function getUrlParamsNextPage()
    {
        $params = [
            self::PARAM_NEWER_ID => $this->getNextId(),
        ];
        $this->addCountToParamsIfNeeded($params);

        return $params;
    }

    /**
     * @return int
     */
    private function getNextId()
    {
        if ($this->hasNextItemAssured()) {
            return $this->newerId;
        } else {
            return $this->futureId;
        }
    }

    /**
     * @return bool
     */
    public function hasNextItemAssured()
    {
        return !is_null($this->newerId);
    }

    /**
     * @param string[] &$params
     */
    private function addCountToParamsIfNeeded(array &$params)
    {
        if (!is_null($this->count)) {
            $params[self::PARAM_COUNT] = $this->count;
        }
    }

    /**
     * @return string[]
     *
     * @throws BunqException When there is no previous page.
     */
    public function getUrlParamsPreviousPage()
    {
        if (!$this->hasPreviousItem()) {
            throw new BunqException(self::ERROR_NO_PREVIOUS_PAGE);
        }

        $params = [
            self::PARAM_OLDER_ID => $this->olderId,
        ];
        $this->addCountToParamsIfNeeded($params);

        return $params;
    }

    /**
     * @return bool
     */
    public function hasPreviousItem()
    {
        return !is_null($this->olderId);
    }

    /**
     * @return string[]
     */
    public function getUrlParamsCountOnly()
    {
        $params = [];
        $this->addCountToParamsIfNeeded($params);

        return $params;
    }

    /**
     * @return int
     */
    public function getOlderId()
    {
        return $this->olderId;
    }

    /**
     * @return int
     */
    public function getNewerId()
    {
        return $this->newerId;
    }

    /**
     * @return int
     */
    public function getFutureId()
    {
        return $this->futureId;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }
}
