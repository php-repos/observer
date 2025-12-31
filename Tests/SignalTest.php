<?php

use PhpRepos\Observer\API\Signal;
use function PhpRepos\TestRunner\Assertions\assert_false;
use function PhpRepos\TestRunner\Assertions\assert_true;
use function PhpRepos\TestRunner\Runner\test;

test(
    title: 'Signal constructs with correct properties',
    case: function () {
        $id = 'signal id';
        $title = 'something happened or will happen';
        $time = new DateTimeImmutable('now');
        $details = ['variables' => 'that are involved'];

        $signal = new Signal($id, $title, $time, $details);

        $expected = [
            'id' => $id,
            'title' => $title,
            'details' => $details,
            'time' => $time->format('Y-m-d\TH:i:s.uP'),
        ];

        assert_true($expected === $signal->jsonSerialize(), 'Signal JSON serialization does not match expected data.');
    }
);

test(
    title: 'Signal::create factory method generates a valid signal',
    case: function () {
        $title = 'something happened or will happen';
        $details = ['variables' => 'that are involved'];

        $signal = Signal::create($title, $details);

        assert_true(preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $signal->id) === 1, "Signal ID is not a valid UUID; got '{$signal->id}'.");
        assert_true($signal->title === $title, "Signal title does not match; expected '$title', got '{$signal->title}'.");
        assert_true($signal->details === $details, 'Signal details do not match; expected ' . json_encode($details) . ", got " . json_encode($signal->details) . ".");
        assert_true(abs(time() - $signal->time->getTimestamp()) < 2, 'Signal time does not match current time; expected roughly ' . time() . ", got {$signal->time->getTimestamp()}.");
        assert_true($signal->time->getTimezone()->getName() === 'UTC', "Signal timezone is not UTC; got '{$signal->time->getTimezone()->getName()}'.");
    }
);

test(
    title: 'Signal::create uses empty details when none are provided',
    case: function () {
        $title = 'something happened or will happen';

        $signal = Signal::create($title);

        assert_true($signal->details === [], 'Signal details are not empty when none are provided; got ' . json_encode($signal->details) . '.');
    }
);
