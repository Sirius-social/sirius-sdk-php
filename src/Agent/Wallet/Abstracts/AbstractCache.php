<?php


namespace Siruis\Agent\Wallet\Abstracts;


abstract class AbstractCache
{
    /**
     * Gets schema json data for specified schema id.
     * If data is present inside of cache, cached data is returned.
     * Otherwise data is fetched from the ledger and stored inside of cache for future use.
     *
     * EXPERIMENTAL
     *
     * @param string $pool_name Ledger.
     * @param string $submitter_did DID of the submitter stored in secured Wallet.
     * @param string $id identifier of schema.
     * @param CacheOptions $options
     * {
     *      noCache: (bool, optional, false by default) Skip usage of cache,
     *      noUpdate: (bool, optional, false by default) Use only cached data, do not try to update.
     *      noStore: (bool, optional, false by default) Skip storing fresh data if updated,
     *      minFresh: (int, optional, -1 by default) Return cached data if not older than this many seconds. -1 means do not check age.
     * }
     * @return array
     * {
     *      id: identifier of schema
     *      attrNames: array of attribute name strings
     *      name: Schema's name string
     *      version: Schema's version string
     *      ver: Version of the Schema json
     * }
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function get_schema(string $pool_name, string $submitter_did, string $id, CacheOptions $options): array;

    /**
     * Gets credential definition json data for specified credential definition id.
     * If data is present inside of cache, cached data is returned.
     * Otherwise data is fetched from the ledger and stored inside of cache for future use.
     *
     * EXPERIMENTAL
     *
     * @param string $pool_name Ledger.
     * @param string $submitter_did DID of the submitter stored in secured Wallet.
     * @param string $id identifier of credential definition.
     * @param CacheOptions $options
     * {
     *      noCache: (bool, optional, false by default) Skip usage of cache,
     *      noUpdate: (bool, optional, false by default) Use only cached data, do not try to update.
     *      noStore: (bool, optional, false by default) Skip storing fresh data if updated,
     *      minFresh: (int, optional, -1 by default) Return cached data if not older than this many seconds. -1 means do not check age.
     * }
     * @return array Credential Definition json.
     * {
     *      id: string - identifier of credential definition
     *      schemaId: string - identifier of stored in ledger schema
     *      type: string - type of the credential definition. CL is the only supported type now.
     *      tag: string - allows to distinct between credential definitions for the same issuer and schema
     *      value: Dictionary with Credential Definition's data: {
     *          primary: primary credential public key,
     *          Optional<revocation>: revocation credential public key
     *      },
     *      ver: Version of the Credential Definition json
     * }
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function get_cred_def(string $pool_name, string $submitter_did, string $id, CacheOptions $options): array;

    /**
     * Purge schema cache.
     *
     * EXPERIMENTAL
     *
     * @param PurgeOptions $options
     * {
     *      maxAge: (int, optional, -1 by default) Purge cached data if older than this many seconds. -1 means purge all.
     * }
     * @return mixed
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function purge_schema_cache(PurgeOptions $options);

    /**
     * Purge credential definition cache.
     *
     * EXPERIMENTAL
     *
     * @param PurgeOptions $options
     * {
     *      maxAge: (int, optional, -1 by default) Purge cached data if older than this many seconds. -1 means purge all.
     * }
     * @return mixed
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function purge_cred_def_cache(PurgeOptions $options);

}