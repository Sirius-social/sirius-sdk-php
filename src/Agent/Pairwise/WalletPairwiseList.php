<?php


namespace Siruis\Agent\Pairwise;


use Siruis\Agent\Wallet\Abstracts\AbstractDID;
use Siruis\Agent\Wallet\Abstracts\AbstractPairwise;
use Siruis\Helpers\ArrayHelper;

class WalletPairwiseList extends AbstractPairwiseList
{
    /**
     * @var AbstractPairwise
     */
    protected $api_pairwise;

    /**
     * @var AbstractDID
     */
    protected $api_did;
    protected $is_loading = false;

    public function __construct(array $api)
    {
        $this->api_pairwise = $api[0];
        $this->api_did = $api[1];
        $this->is_loading = false;
    }

    public function create(Pairwise $pairwise)
    {
        $this->api_did->store_their_did($pairwise->their->did, $pairwise->their->verkey);
        $metadata = $pairwise->metadata ? $pairwise->metadata : [];
        $metadata = ArrayHelper::update($metadata, self::build_metadata($pairwise));
        $this->api_pairwise->create_pairwise(
            $pairwise->their->did,
            $pairwise->me->did,
            $metadata,
            self::build_tags($pairwise)
        );
    }

    public function update(Pairwise $pairwise)
    {
        $this->api_pairwise->set_pairwise_metadata(
            $pairwise->their->did,
            $pairwise->metadata,
            self::build_tags($pairwise)
        );
    }

    public function is_exists(string $their_did): bool
    {
        return $this->api_pairwise->is_pairwise_exists($their_did);
    }

    public function ensure_exists(Pairwise $pairwise)
    {
        if ($this->is_exists($pairwise->their->did)) {
            $this->update($pairwise);
        } else {
            $this->create($pairwise);
        }
    }

    public function load_for_did(string $their_did): ?Pairwise
    {
        if ($this->is_exists($their_did)) {
            $raw = $this->api_pairwise->get_pairwise($their_did);
            $metadata = $raw['metadata'];
            return self::restore_pairwise($metadata);
        } else {
            return null;
        }
    }

    public function load_for_verkey(string $their_verkey): ?Pairwise
    {
        $array = $this->api_pairwise->search(['their_verkey' => $their_verkey], 1);
        if ($array['collection']) {
            $metadata = $array['collection'][0]['metadata'];
            return self::restore_pairwise($metadata);
        } else {
            return null;
        }
    }

    public function start_loading()
    {
        $this->is_loading = true;
    }

    /**
     * @inheritDoc
     */
    public function partial_load()
    {
        if ($this->is_loading) {
            $items = $this->api_pairwise->list_pairwise();
            $pairwise = [];
            $this->is_loading = false;
            foreach ($items as $key => $item) {
                array_push($pairwise, self::restore_pairwise($item['metadata']));
            }
            return [
                'loaded' => true,
                'pairwise' => $pairwise
            ];
        } else {
            return [
                'loaded' => false,
                'pairwise' => []
            ];
        }
    }

    public function stop_loading()
    {
        $this->is_loading = false;
    }

    public static function build_tags(Pairwise $pairwise): array
    {
        return [
            'my_did' => $pairwise->me->did,
            'my_verkey' => $pairwise->me->verkey,
            'their_verkey' => $pairwise->their->verkey
        ];
    }

    public static function restore_pairwise(array $metadata): Pairwise
    {
        return new Pairwise(
            new Me(
                $metadata['me']['did'],
                $metadata['me']['verkey'],
                $metadata['me']['did_doc']
            ),
            new Their(
                $metadata['their']['did'],
                $metadata['their']['verkey'],
                $metadata['their']['label'],
                $metadata['their']['endpoint'],
                $metadata['their']['routing_keys'],
                $metadata['their']['did_doc']
            ),
            $metadata
        );
    }

    public static function build_metadata(Pairwise $pairwise): array
    {
        return [
            'me' => [
                'did' => $pairwise->me->did,
                'verkey' => $pairwise->me->verkey,
                'did_doc' => $pairwise->me->did_doc
            ],
            'their' => [
                'did' => $pairwise->their->did,
                'verkey' => $pairwise->their->verkey,
                'label' => $pairwise->their->label,
                'endpoint' => [
                    'address' => $pairwise->their->endpoint,
                    'routing_keys' => $pairwise->their->routing_keys
                ],
                'did_doc' => $pairwise->their->did_doc
            ]
        ];
    }
}