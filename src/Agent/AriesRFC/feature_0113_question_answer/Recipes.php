<?php


namespace Siruis\Agent\AriesRFC\feature_0113_question_answer;


use Siruis\Agent\AriesRFC\feature_0113_question_answer\Messages\Answer;
use Siruis\Agent\AriesRFC\feature_0113_question_answer\Messages\Question;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Errors\Exceptions\SiriusTimeoutIO;
use Siruis\Hub\Coprotocols\CoProtocolThreadedP2P;
use Siruis\Hub\Init;

class Recipes
{
    public static function ask_and_wait_answer(Question $query, Pairwise $to)
    {
        $iso_string = $query->getExpiresTime();
        if ($iso_string) {
            try {
                $expires_at = new \DateTime($iso_string);
                $delta = $expires_at->diff(new \DateTime('now'));
                $ttl = round($delta->f);
            } catch (\Exception $err) {
                $ttl = null;
            }
        } else {
            $ttl = null;
        }

        $co = new CoProtocolThreadedP2P(
            $query->getId(), $to, $ttl
        );
        $co->send($query);
        try {
            while (true) {
                list($msg, $sender_verkey, $recipient_verkey) = $co->get_one();
                if ($sender_verkey == $to->their->verkey && $msg instanceof Answer) {
                    return [true, $msg];
                }
            }
        } catch (SiriusTimeoutIO $exception) {
            return [false, null];
        }
    }

    public static function make_answer(string $response, Question $question, Pairwise $to)
    {
        $answer = new Answer([], $response, $question->getId());
        $answer->setOutTime();
        Init::send_to($answer, $to);
    }
}