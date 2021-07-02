<?php


namespace Siruis\Hub\Proxies;


use Siruis\Agent\Wallet\Abstracts\AbstractDID;
use Siruis\Errors\Exceptions\SiriusInitializationError;
use Siruis\Hub\Core\Hub;

class DIDProxy extends AbstractDID
{
    /**
     * @var AbstractDID
     */
    protected $service;

    /**
     * DIDProxy constructor.
     * @throws SiriusInitializationError
     */
    public function __construct()
    {
        $this->service = Hub::current_hub()->get_did();
    }

    /**
     * @inheritDoc
     */
    public function create_and_store_my_did(string $did = null, string $seed = null, bool $cid = null): array
    {
        return $this->service->create_and_store_my_did($did, $seed, $cid);
    }

    /**
     * @inheritDoc
     */
    public function store_their_did(string $did, string $verkey = null)
    {
        return $this->service->store_their_did($did, $verkey);
    }

    /**
     * @inheritDoc
     */
    public function set_did_metadata(string $did, array $metadata = null)
    {
        return $this->service->set_did_metadata($did, $metadata);
    }

    /**
     * @inheritDoc
     */
    public function list_my_dids_with_meta(): array
    {
        return $this->service->list_my_dids_with_meta();
    }

    /**
     * @inheritDoc
     */
    public function get_did_metadata($did): ?array
    {
        return $this->service->get_did_metadata($did);
    }

    /**
     * @inheritDoc
     */
    public function key_for_local_did(string $did): string
    {
        return $this->service->key_for_local_did($did);
    }

    /**
     * @inheritDoc
     */
    public function key_for_did(string $pool_name, string $did): string
    {
        return $this->service->key_for_did($pool_name, $did);
    }

    /**
     * @inheritDoc
     */
    public function create_key(string $seed = null): string
    {
        return $this->service->create_key($seed);
    }

    /**
     * @inheritDoc
     */
    public function replce_keys_start(string $did, string $seed = null): string
    {
        return $this->service->replce_keys_start($did, $seed);
    }

    /**
     * @inheritDoc
     */
    public function replace_keys_apply(string $did)
    {
        return $this->service->replace_keys_apply($did);
    }

    /**
     * @inheritDoc
     */
    public function set_key_metadata(string $verkey, array $metadata)
    {
        return $this->service->set_key_metadata($verkey, $metadata);
    }

    /**
     * @inheritDoc
     */
    public function get_key_metadata(string $verkey): array
    {
        return $this->service->get_key_metadata($verkey);
    }

    /**
     * @inheritDoc
     */
    public function set_endpoint_for_did(string $did, string $address, string $transport_key)
    {
        return $this->service->set_endpoint_for_did($did, $address, $transport_key);
    }

    /**
     * @inheritDoc
     */
    public function get_endpoint_for_did(string $pool_name, string $did)
    {
        return $this->service->get_endpoint_for_did($pool_name, $did);
    }

    /**
     * @inheritDoc
     */
    public function get_my_did_with_meta(string $did)
    {
        return $this->service->get_my_did_with_meta($did);
    }

    /**
     * @inheritDoc
     */
    public function abbreviate_verkey(string $did, string $full_verkey): string
    {
        return $this->service->abbreviate_verkey($did, $full_verkey);
    }

    /**
     * @inheritDoc
     */
    public function qualify_did(string $did, string $method): string
    {
        return $this->service->qualify_did($did, $method);
    }
}