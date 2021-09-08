<?php


namespace Siruis\Agent\AriesRFC\Mixins;


trait AttachesMixin
{
    public function getAttaches()
    {
        if (key_exists('~attach', $this->payload)) {
            return $this->payload['~attach'];
        } else {
            return [];
        }
    }

    public function addAttach(Attach $attach)
    {
        if (!key_exists('~attach', $this->payload)) {
            $this->payload['~attach'] = [];
        }
        array_push($this->payload['~attach'], $attach);
    }
}