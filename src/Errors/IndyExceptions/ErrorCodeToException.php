<?php


namespace Siruis\Errors\IndyExceptions;


class ErrorCodeToException
{
    public static function parse($errorcode)
    {
        $array = [
            # Common Errors
            ErrorCode::CommonInvalidParam1 => CommonInvalidParam1::class,
            ErrorCode::CommonInvalidParam2 => CommonInvalidParam2::class,
            ErrorCode::CommonInvalidParam3 => CommonInvalidParam3::class,
            ErrorCode::CommonInvalidParam4 => CommonInvalidParam4::class,
            ErrorCode::CommonInvalidParam5 => CommonInvalidParam5::class,
            ErrorCode::CommonInvalidParam6 => CommonInvalidParam6::class,
            ErrorCode::CommonInvalidParam7 => CommonInvalidParam7::class,
            ErrorCode::CommonInvalidParam8 => CommonInvalidParam8::class,
            ErrorCode::CommonInvalidParam9 => CommonInvalidParam9::class,
            ErrorCode::CommonInvalidParam10 => CommonInvalidParam10::class,
            ErrorCode::CommonInvalidParam11 => CommonInvalidParam11::class,
            ErrorCode::CommonInvalidParam12 => CommonInvalidParam12::class,
            ErrorCode::CommonInvalidState => CommonInvalidState::class,
            ErrorCode::CommonInvalidStructure => CommonInvalidStructure::class,
            ErrorCode::CommonIOError => CommonIOError::class,
            # Wallet Errors
            ErrorCode::WalletInvalidHandle => WalletInvalidHandle::class,
            ErrorCode::WalletUnknownTypeError => WalletUnknownTypeError::class,
            ErrorCode::WalletTypeAlreadyRegisteredError => WalletTypeAlreadyRegisteredError::class,
            ErrorCode::WalletAlreadyExistsError => WalletAlreadyExistsError::class,
            ErrorCode::WalletNotFoundError => WalletNotFoundError::class,
            ErrorCode::WalletIncompatiblePoolError => WalletIncompatiblePoolError::class,
            ErrorCode::WalletAlreadyOpenedError => WalletAlreadyOpenedError::class,
            ErrorCode::WalletAccessFailed => WalletAccessFailed::class,
            ErrorCode::WalletInputError => WalletInputError::class,
            ErrorCode::WalletDecodingError => WalletDecodingError::class,
            ErrorCode::WalletStorageError => WalletStorageError::class,
            ErrorCode::WalletEncryptionError => WalletEncryptionError::class,
            ErrorCode::WalletItemNotFound => WalletItemNotFound::class,
            ErrorCode::WalletItemAlreadyExists => WalletItemAlreadyExists::class,
            ErrorCode::WalletQueryError => WalletQueryError::class,
            # Pool Errors
            ErrorCode::PoolLedgerNotCreatedError => PoolLedgerNotCreatedError::class,
            ErrorCode::PoolLedgerInvalidPoolHandle => PoolLedgerInvalidPoolHandle::class,
            ErrorCode::PoolLedgerTerminated => PoolLedgerTerminated::class,
            ErrorCode::LedgerNoConsensusError => LedgerNoConsensusError::class,
            ErrorCode::LedgerInvalidTransaction => LedgerInvalidTransaction::class,
            ErrorCode::LedgerSecurityError => LedgerSecurityError::class,
            ErrorCode::PoolLedgerConfigAlreadyExistsError => PoolLedgerConfigAlreadyExistsError::class,
            ErrorCode::PoolLedgerTimeout => PoolLedgerTimeout::class,
            ErrorCode::PoolIncompatibleProtocolVersion => PoolIncompatibleProtocolVersion::class,
            ErrorCode::LedgerNotFound => LedgerNotFound::class,
            # Anoncreds Errors
            ErrorCode::AnoncredsRevocationRegistryFullError => AnoncredsRevocationRegistryFullError::class,
            ErrorCode::AnoncredsInvalidUserRevocId => AnoncredsInvalidUserRevocId::class,
            ErrorCode::AnoncredsMasterSecretDuplicateNameError => AnoncredsMasterSecretDuplicateNameError::class,
            ErrorCode::AnoncredsProofRejected => AnoncredsProofRejected::class,
            ErrorCode::AnoncredsCredentialRevoked => AnoncredsCredentialRevoked::class,
            ErrorCode::AnoncredsCredDefAlreadyExistsError => AnoncredsCredDefAlreadyExistsError::class,
            # Crypto Errors
            ErrorCode::UnknownCryptoTypeError => UnknownCryptoTypeError::class,
            ErrorCode::DidAlreadyExistsError => DidAlreadyExistsError::class,
            ErrorCode::PaymentUnknownMethodError => PaymentUnknownMethodError::class,
            ErrorCode::PaymentIncompatibleMethodsError => PaymentIncompatibleMethodsError::class,
            ErrorCode::PaymentInsufficientFundsError => PaymentInsufficientFundsError::class,
            ErrorCode::PaymentSourceDoesNotExistError => PaymentSourceDoesNotExistError::class,
            ErrorCode::PaymentOperationNotSupportedError => PaymentOperationNotSupportedError::class,
            ErrorCode::PaymentExtraFundsError => PaymentExtraFundsError::class,
        ];
        return $array[$errorcode];
    }
}