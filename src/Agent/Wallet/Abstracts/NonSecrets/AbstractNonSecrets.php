<?php


namespace Siruis\Agent\Wallet\Abstracts\NonSecrets;


abstract class AbstractNonSecrets
{
    /**
     * Create a new non-secret record in the wallet
     *
     * @param string $type_ allows to separate different record types collections
     * @param string $id_ the id of record
     * @param string $value the value of record
     * @param array|null $tags the record tags used for search and storing meta information as json:
     *  {
     *      "tagName1": <str>, // string tag (will be stored encrypted)
     *      "tagName2": <str>, // string tag (will be stored encrypted)
     *      "~tagName3": <str>, // string tag (will be stored un-encrypted)
     *      "~tagName4": <str>, // string tag (will be stored un-encrypted)
     *  }
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public static function add_wallet_record(string $type_, string $id_, string $value, array $tags = null);

    /**
     * Update a non-secret wallet record value
     *
     * @param string $type_ allows to separate different record types collections
     * @param string $id_ the id of record
     * @param string $value the value of record
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public static function update_wallet_record_value(string $type_, string $id_, string $value);

    /**
     * Add new tags to the wallet record
     *
     * @param string $type_ allows to separate different record types collections
     * @param string $id_ the id of record
     * @param array $tags ags_json: the record tags used for search and storing meta information as json:
     *  {
     *      "tagName1": <str>, // string tag (will be stored encrypted)
     *      "tagName2": <str>, // string tag (will be stored encrypted)
     *      "~tagName3": <str>, // string tag (will be stored un-encrypted)
     *      "~tagName4": <str>, // string tag (will be stored un-encrypted)
     *  }
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public static function update_wallet_record_tags(string $type_, string $id_, array $tags);

    /**
     * Add new tags to the wallet record
     *
     * @param string $type_ allows to separate different record types collections
     * @param string $id_ the id of record
     * @param array $tags tags_json: the record tags used for search and storing meta information as json:
     *  {
     *      "tagName1": <str>, // string tag (will be stored encrypted)
     *      "tagName2": <str>, // string tag (will be stored encrypted)
     *      "~tagName3": <str>, // string tag (will be stored un-encrypted)
     *      "~tagName4": <str>, // string tag (will be stored un-encrypted)
     *  }
     * @return mixed
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public static function add_wallet_record_tags(string $type_, string $id_, array $tags);

    /**
     * Delete an existing wallet record tags in the wallet
     *
     * @param string $type_ allows to separate different record types collections
     * @param string $id_ the id of record
     * @param array $tag_names the list of tag names to remove from the record as json array: ["tagName1", "tagName2", ...]
     * @return mixed
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public static function delete_wallet_record_tags(string $type_, string $id_, array $tag_names);

    /**
     * Delete an existing wallet record in the wallet
     *
     * @param string $type_ allows to separate different record types collections
     * @param string $id_ the id of record
     * @return mixed
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public static function delete_wallet_record(string $type_, string $id_);

    /**
     * Get an wallet record by id
     *
     * @param string $type_ allows to separate different record types collections
     * @param string $id_ the id of record
     * @param RetrieveRecordOptions $options
     *  {
     *      retrieveType: (optional, false by default) Retrieve record type,
     *      retrieveValue: (optional, true by default) Retrieve record value,
     *      retrieveTags: (optional, true by default) Retrieve record tags
     *  }
     * @return array|null wallet record json:
     *  {
     *      id: "Some id",
     *      type: "Some type", // present only if retrieveType set to true
     *      value: "Some value", // present only if retrieveValue set to true
     *      tags: <tags json>, // present only if retrieveTags set to true
     *  }
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public static function get_wallet_record(string $type_, string $id_, RetrieveRecordOptions $options): ?array;

    /**
     * Search for wallet records
     *
     * @param string $type_ allows to separate different record types collections
     * @param array $query MongoDB style query to wallet record tags:
     *  {
     *      "tagName": "tagValue",
     *      $or: {
     *          "tagName2": { $regex: 'pattern' },
     *          "tagName3": { $gte: '123' },
     *      },
     *  }
     * @param RetrieveRecordOptions $options
     *  {
     *      retrieveRecords: (optional, true by default) If false only "counts" will be calculated,
     *      retrieveTotalCount: (optional, false by default) Calculate total count,
     *      retrieveType: (optional, false by default) Retrieve record type,
     *      retrieveValue: (optional, true by default) Retrieve record value,
     *      retrieveTags: (optional, true by default) Retrieve record tags,
     *  }
     * @param int $limit max record count to retrieve
     * @return array wallet records json:
     *  {
     *      totalCount: <str>, // present only if retrieveTotalCount set to true
     *      records: [{ // present only if retrieveRecords set to true
     *          id: "Some id",
     *          type: "Some type", // present only if retrieveType set to true
     *          value: "Some value", // present only if retrieveValue set to true
     *          tags: <tags json>, // present only if retrieveTags set to true
     *      }],
     *  }
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public static function wallet_search(string $type_, array $query, RetrieveRecordOptions $options, int $limit = 1): array;

}