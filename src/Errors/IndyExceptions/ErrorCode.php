<?php


namespace Siruis\Errors\IndyExceptions;


use MyCLabs\Enum\Enum;

class ErrorCode extends Enum
{
    public const Success = 0;
    public const CommonInvalidParam1 = 100;
    public const CommonInvalidParam2 = 101;
    public const CommonInvalidParam3 = 102;
    public const CommonInvalidParam4 = 103;
    public const CommonInvalidParam5 = 104;
    public const CommonInvalidParam6 = 105;
    public const CommonInvalidParam7 = 106;
    public const CommonInvalidParam8 = 107;
    public const CommonInvalidParam9 = 108;
    public const CommonInvalidParam10 = 109;
    public const CommonInvalidParam11 = 110;
    public const CommonInvalidParam12 = 111;
    public const CommonInvalidState = 112;
    public const CommonInvalidStructure = 113;
    public const CommonIOError = 114;
    public const WalletInvalidHandle = 200;
    public const WalletUnknownTypeError = 201;
    public const WalletTypeAlreadyRegisteredError = 202;
    public const WalletAlreadyExistsError = 203;
    public const WalletNotFoundError = 204;
    public const WalletIncompatiblePoolError = 205;
    public const WalletAlreadyOpenedError = 206;
    public const WalletAccessFailed = 207;
    public const WalletInputError = 208;
    public const WalletDecodingError = 209;
    public const WalletStorageError = 210;
    public const WalletEncryptionError = 211;
    public const WalletItemNotFound = 212;
    public const WalletItemAlreadyExists = 213;
    public const WalletQueryError = 214;
    public const PoolLedgerNotCreatedError = 300;
    public const PoolLedgerInvalidPoolHandle = 301;
    public const PoolLedgerTerminated = 302;
    public const LedgerNoConsensusError = 303;
    public const LedgerInvalidTransaction = 304;
    public const LedgerSecurityError = 305;
    public const PoolLedgerConfigAlreadyExistsError = 306;
    public const PoolLedgerTimeout = 307;
    public const PoolIncompatibleProtocolVersion = 308;
    public const LedgerNotFound = 309;
    public const AnoncredsRevocationRegistryFullError = 400;
    public const AnoncredsInvalidUserRevocId = 401;
    public const AnoncredsMasterSecretDuplicateNameError = 404;
    public const AnoncredsProofRejected = 405;
    public const AnoncredsCredentialRevoked = 406;
    public const AnoncredsCredDefAlreadyExistsError = 407;
    public const UnknownCryptoTypeError = 500;
    public const DidAlreadyExistsError = 600;
    public const PaymentUnknownMethodError = 700;
    public const PaymentIncompatibleMethodsError = 701;
    public const PaymentInsufficientFundsError = 702;
    public const PaymentSourceDoesNotExistError = 703;
    public const PaymentOperationNotSupportedError = 704;
    public const PaymentExtraFundsError = 705;
    public const TransactionNotAllowedError = 706;
}