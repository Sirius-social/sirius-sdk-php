<?php


namespace Siruis\Agent\Pairwise;


abstract class AbstractPairwiseList
{
    public abstract function create(Pairwise $pairwise);

    public abstract function update(Pairwise $pairwise);

    public abstract function is_exists(string $their_did);

    public abstract function ensure_exists(Pairwise $pairwise);

    public abstract function load_for_did(string $their_did): ?Pairwise;

    public abstract function load_for_verkey(string $their_verkey): ?Pairwise;

    public function enumerate()
    {
        $cur = 0;
        $this->start_loading();
        try {
            while (true) {
                $array = $this->partial_load();
                $success = $array[0];
                $collection = $array[1];
                if ($success) {
                    foreach ($collection as $p) {
                        $cur += 1;
                    }
                } else {
                    break;
                }
            }
        } finally {
            $this->stop_loading();
        }
    }

    public abstract function start_loading();

    /**
     * @return bool|array
     */
    public abstract function partial_load();

    public abstract function stop_loading();
}