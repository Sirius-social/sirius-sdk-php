<?php


namespace Siruis\Hub\Proxies;


use Siruis\Agent\Wallet\Abstracts\AbstractCache;
use Siruis\Agent\Wallet\Abstracts\CacheOptions;
use Siruis\Agent\Wallet\Abstracts\PurgeOptions;
use Siruis\Errors\Exceptions\SiriusInitializationError;
use Siruis\Hub\Core\Hub;

class CacheProxy extends AbstractCache
{
    /**
     * @var AbstractCache
     */
    protected $service;

    /**
     * CacheProxy constructor.
     * @throws SiriusInitializationError
     */
    public function __construct()
    {
        $this->service = Hub::current_hub()->get_cache();
    }

    /**
     * @inheritDoc
     */
    public function get_schema(string $pool_name, string $submitter_did, string $id, CacheOptions $options): array
    {
        return $this->service->get_schema($pool_name, $submitter_did, $id, $options);
    }

    /**
     * @inheritDoc
     */
    public function get_cred_def(string $pool_name, string $submitter_did, string $id, CacheOptions $options): array
    {
        return $this->service->get_cred_def($pool_name, $submitter_did, $id, $options);
    }

    /**
     * @inheritDoc
     */
    public function purge_schema_cache(PurgeOptions $options)
    {
        return $this->service->purge_schema_cache($options);
    }

    /**
     * @inheritDoc
     */
    public function purge_cred_def_cache(PurgeOptions $options)
    {
        return $this->service->purge_cred_def_cache($options);
    }
}