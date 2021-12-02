<?php


namespace Siruis\Agent\Wallet\Abstracts;


abstract class AbstractPairwise
{
    /**
     * Check if pairwise is exists.
     *
     * @param string $their_did encoded Did.
     * @return bool true - if pairwise is exists, false - otherwise
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function is_pairwise_exists(string $their_did): bool;

    /**
     * Creates pairwise.
     *
     * @param string $their_did encrypting DID
     * @param string $my_did encrypting DID
     * @param array|null $metadata (Optional) extra information for pairwise
     * @param array|null $tags tags for searching operations
     * @return mixed Error code
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function create_pairwise(string $their_did,
                                             string $my_did,
                                             array $metadata = null,
                                             array $tags = null);

    /**
     * Get list of saved pairwise.
     *
     * @return array list of saved pairwise
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function list_pairwise(): array;

    /**
     * Gets pairwise information for specific their_did.
     *
     * @param string $their_did encoded Did
     * @return array|null did info associated with their did
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function get_pairwise(string $their_did): ?array;

    /**
     * Save some data in the Wallet for pairwise associated with Did.
     *
     * @param string $their_did encoded DID
     * @param array|null $metadata some extra information for pairwise
     * @param array|null $tags  tags for searching operation
     * @return mixed
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function set_pairwise_metadata(string $their_did, array $metadata = null, array $tags = null);

    /**
     * Search Pairwises
     *
     * @param array $tags tags based query
     * @param int|null $limit max items count
     * @return mixed Results, TotalCount
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function search(array $tags, int $limit = null);
}