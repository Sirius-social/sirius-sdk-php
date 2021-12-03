<?php


namespace Siruis\Agent\Pairwise;


use Generator;

abstract class AbstractPairwiseList
{
    abstract public function create(Pairwise $pairwise);

    abstract public function update(Pairwise $pairwise);

    abstract public function is_exists(string $their_did);

    abstract public function ensure_exists(Pairwise $pairwise);

    abstract public function load_for_did(string $their_did): ?Pairwise;

    abstract public function load_for_verkey(string $their_verkey): ?Pairwise;

    public function enumerate(): ?Generator
    {
        $cur = 0;
        $this->start_loading();
        try {
            while (true) {
                [$success, $collection] = $this->partial_load();
                if ($success) {
                    foreach ($collection as $p) {
                        yield [$cur, $p];
                        ++$cur;
                    }
                } else {
                    break;
                }
            }
        } finally {
            $this->stop_loading();
        }
    }

    abstract public function start_loading();

    abstract public function partial_load();

    abstract public function stop_loading();
}