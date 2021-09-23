<?php
include '../vendor/autoload.php';

use Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages\ConnRequest;
use Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages\Invitation;
use Siruis\Agent\AriesRFC\feature_0160_connection_protocol\StateMachines\Inviter;
use Siruis\Errors\IndyExceptions\AnoncredsMasterSecretDuplicateNameError;
use Siruis\Hub\Init;

const PRIMARY_DID = 'Y8dyeSo96aNnEJE2Lxs9TD';
const PRIMARY_VERKEY = 'Hy8mByVocXiZ5HoGsRUNtEuQwd1eHkDpeNBYih9iW5Xt';

function logger(array $payload)
{
    error_log('----------- LOGGER --------------');
    error_log(json_encode($payload));
    error_log('---------------------------------');
}

function init()
{
    \Siruis\Hub\Core\Hub::init(
        'https://demo.socialsirius.com',
        b'hMPfx0D1ptQa2fK8UPw7p9/Zf/UUEY9Ppk9oU92VO8IUHnc6oP5ov7f9PQ1NLIO5EHcqghOJvRoV7taA/vCd29iwSyHpn7ZINVBPkm0AYS0zRxJNaGwNcyh1YenU9b0M',
        new \Siruis\Encryption\P2PConnection(
            ['TG2hT5RVXVfVhyr2NdAasXQ8d8rW2b9AvHTzgiHTrA4', '28CDR387RmQNL8QrULCZt742NvFx2vHhjWtwozoMkeyeUbfR2c78DbE2DD1emvwJkk3Vn2pCJkN4x58UVA3HcW96'],
            'FvNvo4vkrzxGhyhUk9Vb6XctyvLzMbZC96yGgDGc72dh'
        )
    );
}

function run()
{
    // Check health
    $endpoints = Init::endpoints();
    $rke = [];
    foreach ($endpoints as $e)
    {
        if ($e->routingKeys == []) {
            array_push($rke, $e);
        }
    }
    $endpoint = $rke[0];
    $dkms = Init::ledger('indicio_test_network');
    $master_secret_id = 'secret_id'.uniqid();
    try {
        Init::AnonCreds()->prover_create_master_secret($master_secret_id);
    } catch (AnoncredsMasterSecretDuplicateNameError $error) {
        throw new $error;
    }

    $connection_key = Init::Crypto()->createKey();
    $invitation = new Invitation(
        [], 'Daulet-'.date('d.m.Y H:i:s', time()), [$connection_key], $endpoint->address
    );
    $invitation_url = 'http://socialsirius.com'.$invitation->getInvitationUrl();
    error_log('Invitation URL: '.$invitation_url);

    $listener = Init::subscribe();
    error_log('!!!!!!!! Inviter Listener is running !!!!!!!!!!!');

    $event = $listener->get_one();
    error_log('=========== EVENT =============');
    error_log(json_encode($event->getMessage()->payload));
    error_log('===============================');
    if ($event->getMessage() instanceof ConnRequest) {
        error_log('========== RUN 0160 =============');
        $request = $event->getMessage();
        list($my_did, $my_verkey) = Init::DID()->create_and_store_my_did();
        $rfc_0160 = new Inviter(
            new \Siruis\Agent\Pairwise\Me($my_did, $my_verkey),
            $connection_key,
            $endpoint
        );
        list($success, $p2p) = $rfc_0160->create_connection($request);
        assert($success);
        error_log('==================================');
    } else {
        error_log('========== Other messages =============');
        error_log(json_encode($event->getMessage()->payload));
        error_log('==================================');
    }
}

init();

try {
    run();
} catch (AnoncredsMasterSecretDuplicateNameError $e) {
}