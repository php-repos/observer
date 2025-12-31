<?php

use PhpRepos\Logger\API\Config;
use PhpRepos\Observer\API\SignalType;
use PhpRepos\Observer\Core\Exceptions\ObserverException;
use PhpRepos\Observer\API\Command;
use PhpRepos\Observer\API\Event;
use PhpRepos\Observer\API\Inquiry;
use PhpRepos\Observer\API\Plan;
use PhpRepos\Observer\API\Message;
use PhpRepos\Observer\API\Signal;
use PhpRepos\Observer\API\Bus;
use PhpRepos\Observer\API\Signals;
use PhpRepos\Observer\Core\Handlers as CoreHandlers;
use function PhpRepos\TestRunner\Assertions\assert_true;
use function PhpRepos\TestRunner\Assertions\assert_false;
use function PhpRepos\TestRunner\Runner\test;

class UserLoggedIn extends Event {}
class PasswordChangePlan extends Plan {}
class NotificationMessage extends Message {}
interface Loggable {}
interface Trackable {}
class CustomSignal extends Event implements Loggable, Trackable {
    public function __construct(string $id, string $title, $time, array $details) {
        parent::__construct($id, $title, $time, $details);
    }
}

test(
    title: 'subscribe throws ObserverException for handlers with no parameters',
    case: function () {
        try {
            Bus\subscribe(function () {});
            assert_false(true, 'Expected ObserverException for handler with no parameters, but none was thrown.');
        } catch (ObserverException $e) {
            assert_true(
                $e->getMessage() === 'Handler should care for at least one signal.',
                "Expected ObserverException message 'Handler should care for at least one signal.', but got '{$e->getMessage()}'."
            );
        }
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'subscribe throws ObserverException for handler parameters without type or SignalType attribute',
    case: function () {
        try {
            Bus\subscribe(function ($a) {});
            assert_false(true, 'Expected ObserverException for handler parameter without type or SignalType attribute, but none was thrown.');
        } catch (ObserverException $e) {
            assert_true(
                $e->getMessage() === 'Handler parameter must have a type or SignalType attribute.',
                "Expected ObserverException message 'Handler parameter must have a type or SignalType attribute.', but got '{$e->getMessage()}'."
            );
        }
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'handler with Signal type acts as a global handler for all signals',
    case: function () {
        $last_signal_title = null;
        $paired_signal_titles = [];
        Bus\subscribe(
            function (Signal $signal) use (&$last_signal_title) {
                $last_signal_title = $signal->title;
            },
            function (Signal $signal_1, Signal $signal_2) use (&$paired_signal_titles) {
                $paired_signal_titles[] = $signal_1->title;
                $paired_signal_titles[] = $signal_2->title;
            }
        );

        Bus\send(Signal::create('Any signal'));
        assert_true($last_signal_title === 'Any signal', "Global handler for Signal type did not execute for Signal with title 'Any signal', got title '$last_signal_title'.");

        Bus\send(Event::create('Any event'));
        assert_true($last_signal_title === 'Any event', "Global handler for Signal type did not execute for Event with title 'Any event', got title '$last_signal_title'.");

        Bus\send(Plan::create('Any plan'));
        assert_true($last_signal_title === 'Any plan', "Global handler for Signal type did not execute for Plan with title 'Any plan', got title '$last_signal_title'.");

        Bus\send(Inquiry::create('Any inquiry'));
        assert_true($last_signal_title === 'Any inquiry', "Global handler for Signal type did not execute for Inquiry with title 'Any inquiry', got title '$last_signal_title'.");

        Bus\send(Message::create('Any message'));
        assert_true($last_signal_title === 'Any message', "Global handler for Signal type did not execute for Message with title 'Any message', got title '$last_signal_title'.");

        Bus\send(Command::create('Any command'));
        assert_true($last_signal_title === 'Any command', "Global handler for Signal type did not execute for Command with title 'Any command', got title '$last_signal_title'.");

        Bus\send(UserLoggedIn::create('UserLoggedIn'));
        assert_true($last_signal_title === 'UserLoggedIn', "Global handler for Signal type did not execute for UserLoggedIn with title 'UserLoggedIn', got title '$last_signal_title'.");

        Bus\send(Event::create('Test Event'), Plan::create('Test Plan'));
        assert_true($last_signal_title === 'Test Plan', "Global handler for Signal type did not execute for last signal with title 'Test Plan', got title '$last_signal_title'.");
        $expected_titles = ['Test Event', 'Test Plan'];
        assert_true(
            $paired_signal_titles === $expected_titles,
            "Handler for two Signals did not capture titles correctly. Expected " . json_encode($expected_titles) . ", got " . json_encode($paired_signal_titles) . "."
        );
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'handler executes when single parameter type matches the signal',
    case: function () {
        $executed = false;
        Bus\subscribe(function (UserLoggedIn $event) use (&$executed) {
            $executed = true;
        });

        Bus\send(UserLoggedIn::create('UserLoggedIn'));
        assert_true($executed, 'Handler for UserLoggedIn did not execute when signal type matched.');

        $executed = false;
        Bus\send(PasswordChangePlan::create('PasswordChangePlan'));
        assert_false($executed, 'Handler for UserLoggedIn executed for mismatched PasswordChangePlan signal.');
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'handler executes for union type parameter when signal matches either type',
    case: function () {
        $executed = false;
        Bus\subscribe(function (UserLoggedIn|PasswordChangePlan $signal) use (&$executed) {
            $executed = true;
        });

        Bus\send(UserLoggedIn::create('UserLoggedIn'));
        assert_true($executed, 'Handler with union type (UserLoggedIn|PasswordChangePlan) did not execute for UserLoggedIn signal.');

        $executed = false;
        Bus\send(PasswordChangePlan::create('PasswordChangePlan'));
        assert_true($executed, 'Handler with union type (UserLoggedIn|PasswordChangePlan) did not execute for PasswordChangePlan signal.');

        $executed = false;
        Bus\send(NotificationMessage::create('NotificationMessage'));
        assert_false($executed, 'Handler with union type (UserLoggedIn|PasswordChangePlan) executed for unmatched NotificationMessage signal.');
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'handler executes for intersection type when signal implements all required interfaces',
    case: function () {
        $executed = false;
        Bus\subscribe(function (Loggable&Trackable $signal) use (&$executed) {
            $executed = true;
        });

        $custom_signal = CustomSignal::create('CustomSignal');
        Bus\send($custom_signal);
        assert_true($executed, 'Handler with intersection type (Loggable&Trackable) did not execute for CustomSignal implementing both interfaces.');

        $executed = false;
        Bus\send(UserLoggedIn::create('UserLoggedIn'));
        assert_false($executed, 'Handler with intersection type (Loggable&Trackable) executed for UserLoggedIn signal missing required interfaces.');
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'handler executes for multiple parameters when all signal types match',
    case: function () {
        $executed = false;
        Bus\subscribe(function (UserLoggedIn $event, PasswordChangePlan $plan) use (&$executed) {
            $executed = true;
        });

        Bus\send(UserLoggedIn::create('UserLoggedIn'), PasswordChangePlan::create('PasswordChangePlan'));
        assert_true($executed, 'Handler with multiple parameters (UserLoggedIn, PasswordChangePlan) did not execute when all signal types matched.');

        $executed = false;
        Bus\send(UserLoggedIn::create('UserLoggedIn'));
        assert_false($executed, 'Handler with multiple parameters executed with missing PasswordChangePlan signal.');
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'handler with nullable parameter receives null for non-matching signals',
    case: function () {
        $event_value = null;
        Bus\subscribe(function (?UserLoggedIn $event) use (&$event_value) {
            $event_value = $event;
        });

        $event_signal = UserLoggedIn::create('UserLoggedIn');
        Bus\send($event_signal);
        assert_true($event_value === $event_signal, 'Handler with nullable UserLoggedIn parameter did not receive the matching signal.');

        $event_value = null;
        Bus\send(PasswordChangePlan::create('PasswordChangePlan'));
        assert_true($event_value === null, 'Handler with nullable UserLoggedIn parameter did not receive null for non-matching PasswordChangePlan signal.');
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'handler uses default value for nullable parameter when signal is not sent',
    case: function () {
        $message_value = 'default';
        Bus\subscribe(function (UserLoggedIn $event, ?NotificationMessage $message = null) use (&$message_value) {
            $message_value = $message;
        });

        Bus\send(UserLoggedIn::create('UserLoggedIn'));
        assert_true($message_value === null, 'Handler did not use default value (null) for missing NotificationMessage signal.');

        $message_signal = NotificationMessage::create('NotificationMessage');
        Bus\send(UserLoggedIn::create('UserLoggedIn'), $message_signal);
        assert_true($message_value === $message_signal, 'Handler did not use the provided NotificationMessage signal.');
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'handler executes for parameter with SignalType attribute when signal matches',
    case: function () {
        $executed = false;
        Bus\subscribe(function (
            #[SignalType(UserLoggedIn::class)]
            $signal
        ) use (&$executed) {
            $executed = true;
        });

        Bus\send(UserLoggedIn::create('UserLoggedIn'));
        assert_true($executed, 'Handler with SignalType attribute for UserLoggedIn did not execute for matching signal.');

        $executed = false;
        Bus\send(PasswordChangePlan::create('PasswordChangePlan'));
        assert_false($executed, 'Handler with SignalType attribute for UserLoggedIn executed for non-matching PasswordChangePlan signal.');
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'subscribe throws ObserverException for parameter without type or SignalType attribute',
    case: function () {
        $exception_thrown = false;
        try {
            Bus\subscribe(function ($signal) {});
            assert_false(true, 'Expected ObserverException for parameter without type or SignalType attribute, but none was thrown.');
        } catch (ObserverException $e) {
            $exception_thrown = true;
        }
        assert_true($exception_thrown, 'Expected ObserverException for parameter without type or SignalType attribute, but none was thrown.');
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'signal-specific functions execute handlers correctly',
    case: function () {
        $executed_broadcast = false;
        $executed_propose = false;
        $executed_ask = false;
        $executed_share = false;
        $executed_order = false;

        Bus\subscribe(
            function (Event $event) use (&$executed_broadcast) {
                $executed_broadcast = true;
            },
            function (Plan $plan) use (&$executed_propose) {
                $executed_propose = true;
            },
            function (Inquiry $inquiry) use (&$executed_ask) {
                $executed_ask = true;
            },
            function (Message $message) use (&$executed_share) {
                $executed_share = true;
            },
            function (Command $command) use (&$executed_order) {
                $executed_order = true;
            }
        );

        Bus\broadcast(Event::create('Test Event'));
        assert_true($executed_broadcast, 'broadcast() did not execute handler for Event signal.');

        Bus\propose(Plan::create('Test Plan'));
        assert_true($executed_propose, 'propose() did not execute handler for Plan signal.');

        Bus\ask(Inquiry::create('Test Inquiry'));
        assert_true($executed_ask, 'ask() did not execute handler for Inquiry signal.');

        Bus\share(Message::create('Test Message'));
        assert_true($executed_share, 'share() did not execute handler for Message signal.');

        Bus\order(Command::create('Test Command'));
        assert_true($executed_order, 'order() did not execute handler for Command signal.');
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'send stops executing handlers when a handler throws an exception',
    case: function () {
        $executed_first = false;
        $executed_second = false;
        Bus\subscribe(
            function (UserLoggedIn $event) use (&$executed_first) {
                $executed_first = true;
                throw new \Exception('Handler failed.');
            },
            function (UserLoggedIn $event) use (&$executed_second) {
                $executed_second = true;
            }
        );

        try {
            Bus\send(UserLoggedIn::create('UserLoggedIn'));
            assert_false(true, 'Expected exception from handler, but none was thrown.');
        } catch (\Exception $e) {
            assert_true($executed_first, 'First handler did not execute before throwing an exception.');
            assert_false($executed_second, 'Second handler executed despite first handler throwing an exception.');
            assert_true($e->getMessage() === 'Handler failed.', "Expected exception message 'Handler failed.', but got '{$e->getMessage()}'.");
        }
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'handler with multiple parameters and union type executes when signals match',
    case: function () {
        $executed = false;
        Bus\subscribe(function (UserLoggedIn|PasswordChangePlan $signal_1, NotificationMessage $signal_2) use (&$executed) {
            $executed = true;
        });

        Bus\send(UserLoggedIn::create('UserLoggedIn'), NotificationMessage::create('NotificationMessage'));
        assert_true($executed, 'Handler with union type (UserLoggedIn|PasswordChangePlan) and NotificationMessage did not execute for UserLoggedIn signal.');

        $executed = false;
        Bus\send(PasswordChangePlan::create('PasswordChangePlan'), NotificationMessage::create('NotificationMessage'));
        assert_true($executed, 'Handler with union type (UserLoggedIn|PasswordChangePlan) and NotificationMessage did not execute for PasswordChangePlan signal.');

        $executed = false;
        Bus\send(Event::create('Event'), NotificationMessage::create('NotificationMessage'));
        assert_false($executed, 'Handler with union type (UserLoggedIn|PasswordChangePlan) executed for non-matching first signal (Event).');
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'handler with multiple parameters and intersection type executes when signals match',
    case: function () {
        $executed = false;
        Bus\subscribe(function (Loggable&Trackable $signal_1, UserLoggedIn $signal_2) use (&$executed) {
            $executed = true;
        });

        $custom_signal = CustomSignal::create('CustomSignal');
        Bus\send($custom_signal, UserLoggedIn::create('UserLoggedIn'));
        assert_true($executed, 'Handler with intersection type (Loggable&Trackable) and UserLoggedIn did not execute for matching signals.');

        $executed = false;
        Bus\send(Event::create('Event'), UserLoggedIn::create('UserLoggedIn'));
        assert_false($executed, 'Handler with intersection type (Loggable&Trackable) executed for non-matching first signal (Event).');
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'handler uses non-null default value for optional parameter when signal is missing',
    case: function () {
        $default_message = NotificationMessage::create('DefaultMessage');
        $message_value = null;
        Bus\subscribe(function (UserLoggedIn $event, ?NotificationMessage $message = null) use (&$message_value, $default_message) {
            $message_value = $message ?? $default_message;
        });

        Bus\send(UserLoggedIn::create('UserLoggedIn'));
        assert_true($message_value === $default_message, 'Handler did not use non-null default value for missing NotificationMessage signal.');

        $message_signal = NotificationMessage::create('NotificationMessage');
        Bus\send(UserLoggedIn::create('UserLoggedIn'), $message_signal);
        assert_true($message_value === $message_signal, 'Handler did not use the provided NotificationMessage signal.');
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'send collects Signal return values and ignores non-Signal returns',
    case: function () {
        $event_signal = Event::create('ReturnedEvent');
        Bus\subscribe(
            function (UserLoggedIn $event) use ($event_signal) {
                return $event_signal;
            },
            function (UserLoggedIn $event) {
                return 'string';
            }
        );

        $results = Bus\send(UserLoggedIn::create('UserLoggedIn'));
        assert_true(count($results) === 1, 'Expected send to collect exactly one Signal return value, but got ' . count($results) . '.');
        assert_true($results[0] === $event_signal, 'send did not collect the correct Signal return value.');
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'send returns empty array when no signals are provided',
    case: function () {
        $results = Bus\send();
        assert_true($results === [], 'Expected send to return an empty array when no signals are provided, but got ' . json_encode($results) . '.');
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'handler respects signal order for multiple parameters',
    case: function () {
        $executed = false;
        Bus\subscribe(function (UserLoggedIn $event, PasswordChangePlan $plan) use (&$executed) {
            $executed = true;
        });

        Bus\send(UserLoggedIn::create('UserLoggedIn'), PasswordChangePlan::create('PasswordChangePlan'));
        assert_true($executed, 'Handler did not execute when signals were sent in the correct order (UserLoggedIn, PasswordChangePlan).');

        $executed = false;
        Bus\send(PasswordChangePlan::create('PasswordChangePlan'), UserLoggedIn::create('UserLoggedIn'));
        assert_false($executed, 'Handler executed despite signals being sent in incorrect order (PasswordChangePlan, UserLoggedIn).');
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'handlers return Signal instances correctly',
    case: function () {
        Bus\subscribe(
            fn (Signal $signal) => Signal::create('signal returned'),
            fn (Signal $signal) => Event::create('event returned'),
            fn (Signal $signal) => Plan::create('plan returned'),
            fn (Signal $signal) => Inquiry::create('inquiry returned'),
            fn (Signal $signal) => Message::create('message returned'),
            fn (Signal $signal) => Command::create('command returned'),
            fn (Signal $signal) => $signal->jsonSerialize(),
        );

        $responses = Bus\send(Signal::create('something happened or will happen'));

        assert_true(count($responses) === 6, "Expected 6 Signal responses, but got " . count($responses) . ".");
        assert_true($responses[0] instanceof Signal, 'First response is not an instance of Signal.');
        assert_true($responses[1] instanceof Event, 'Second response is not an instance of Event.');
        assert_true($responses[2] instanceof Plan, 'Third response is not an instance of Plan.');
        assert_true($responses[3] instanceof Inquiry, 'Fourth response is not an instance of Inquiry.');
        assert_true($responses[4] instanceof Message, 'Fifth response is not an instance of Message.');
        assert_true($responses[5] instanceof Command, 'Sixth response is not an instance of Command.');

        assert_true($responses[0]->title === 'signal returned', "Expected title 'signal returned' for first response, but got '{$responses[0]->title}'.");
        assert_true($responses[1]->title === 'event returned', "Expected title 'event returned' for second response, but got '{$responses[1]->title}'.");
        assert_true($responses[2]->title === 'plan returned', "Expected title 'plan returned' for third response, but got '{$responses[2]->title}'.");
        assert_true($responses[3]->title === 'inquiry returned', "Expected title 'inquiry returned' for fourth response, but got '{$responses[3]->title}'.");
        assert_true($responses[4]->title === 'message returned', "Expected title 'message returned' for fifth response, but got '{$responses[4]->title}'.");
        assert_true($responses[5]->title === 'command returned', "Expected title 'command returned' for sixth response, but got '{$responses[5]->title}'.");
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'send does nothing when no signals are provided',
    case: function () {
        $executed = false;
        Bus\subscribe(function (Signal $signal) use (&$executed) {
            $executed = true;
            return $signal->jsonSerialize();
        });

        $response = Bus\send();

        assert_true($response === [], 'Expected empty response array when no signals are sent, but got ' . json_encode($response) . '.');
        assert_false($executed, 'Handler executed despite no signals being sent.');
    },
    before: function () {
        CoreHandlers\reset();
    }
);

test(
    title: 'send logs handler execution and handler found during dispatch',
    case: function () {
        $logs = [];
        $log_media = function ($log) use (&$logs) {
            $logs[] = $log;
        };

        Config\set_default_media($log_media);

        Bus\subscribe(function (UserLoggedIn $event) {});
        Bus\send(UserLoggedIn::create('UserLoggedIn'));

        $execution_logged = false;
        $handler_found_logged = false;

        foreach ($logs as $log) {
            if ($log->text === 'Handler Execution Planned') {
                $execution_logged = true;
                $log_data = json_decode(json_encode($log), true);
                assert_true($log_data['context']['signal_types'] === [UserLoggedIn::class], 'Handler Execution log has incorrect signal types; expected [UserLoggedIn].');
                assert_true($log_data['context']['id'] === '1-0', "Handler Execution log has incorrect ID; expected '1-0'.");
            }
            if ($log->text === 'Handler Found') {
                $handler_found_logged = true;
                $log_data = json_decode(json_encode($log), true);
                assert_true($log_data['context']['signal_types'] === [UserLoggedIn::class], 'Handler Found log has incorrect signal types; expected [UserLoggedIn].');
                assert_true($log_data['context']['id'] === '1-0', "Handler Found log has incorrect ID; expected '1-0'.");
            }
        }

        assert_true($execution_logged, 'Handler Execution was not logged during dispatch.');
        assert_true($handler_found_logged, 'Handler Found was not logged during dispatch.');

        $logs = [];
        Bus\send(PasswordChangePlan::create('PasswordChangePlan'));

        $no_handler_found_logged = false;
        foreach ($logs as $log) {
            if ($log->text === 'No Handler Found') {
                $no_handler_found_logged = true;
                $log_data = json_decode(json_encode($log), true);
                assert_true($log_data['context']['signal_types'] === [PasswordChangePlan::class], 'No Handler Found log has incorrect signal types; expected [PasswordChangePlan].');
            }
        }

        assert_true($no_handler_found_logged, 'No Handler Found was not logged when no handler was found.');
    },
    before: function () {
        CoreHandlers\reset();
    }
);
