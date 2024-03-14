#!/usr/bin/env php
<?php

use Random\RandomException;

require_once 'config.php';

const AVAILABLE_COMMANDS = ['validate', 'send', 'seed'];
const SEED_SIZE_HUNDREDS_ROWS = 5000;
const MAX_PARALLEL_PROCESSES_COUNT = 10000;


// CLI interface
if (empty($argv[1]) || !in_array($argv[1], AVAILABLE_COMMANDS, true)) {
    echo "Provide command: " . implode(', ', AVAILABLE_COMMANDS) ."\n";
    exit(1);
}

switch ($argv[1]) {
    case 'validate':
        validate();
        exit(0);
    case 'send':
        send();
        exit(0);
    case 'seed':
        seed();
        exit(0);
}

/**
 * Stub for email validation service
 * @throws RandomException
 */
function check_email(string $email): int
{
    sleep(random_int(1,60));
    return random_int(0,1);
}

/**
 * Stub for email sending service
 * @throws RandomException
 */
function send_email(string $from, string $to, string $text): void
{
    sleep(random_int(1,10));
}

/**
 * "Validate" command
 *
 * @return void
 * @throws RandomException
 */
function validate(): void
{
    // get list for validation
    $mysqli = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE);
    $list = mysqli_query($mysqli, "
        SELECT id, email 
        FROM users 
        WHERE email IS NOT NULL 
          AND validts IS NOT NULL 
          AND confirmed=1 
          AND checked=0
          ");

    process($list, static function (array $row): void
        {
            $result = check_email($row['email']);

            $mysqli = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE);
            mysqli_query($mysqli, "
            UPDATE users
            SET checked=1, valid={$result}
            WHERE id={$row['id']}
            ");

            mysqli_close($mysqli);
        }
    );

    echo "Validation finished\n";
}

/**
 * "Send" command
 *
 * @return void
 * @throws RandomException
 */
function send(): void
{
    // get list for sending
    $mysqli = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE);
    $list = mysqli_query($mysqli, "
        SELECT U.id, email, username, U.validts
        FROM users U
        LEFT JOIN mails M USING (id, validts)
        WHERE TIMESTAMPDIFF(DAY,now(),U.validts) IN (1,3) AND valid=1 AND M.validts IS NULL 
          ");

    process($list, static function (array $row): void
        {
            $message = "{$row['username']}, your subscription is expiring soon";
            send_email(FROM_EMAIL, $row['email'], $message);

            $mysqli = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE);
            mysqli_query($mysqli, "INSERT INTO mails VALUES ({$row['id']},'{$row['validts']}')");

            mysqli_close($mysqli);
        }
    );

    echo "Sending finished\n";
}

/**
 * Here we are forking processes
 *
 * @param mysqli_result $rows
 * @param callable $processor
 * @return void
 */
function process(mysqli_result $rows, callable $processor): void
{
    $tasks = [];
    while ($row = mysqli_fetch_assoc($rows)) {
        $pid = pcntl_fork();

        if ($pid === -1) {
            die('Unable to fork process.');
        } elseif ($pid == 0) {
            $processor($row);
            exit();
        } else {
            $tasks[] = $pid;
        }

        // Keep max number of processes
        while (count($tasks) >= MAX_PARALLEL_PROCESSES_COUNT) {
            pcntl_waitpid(array_shift($tasks), $status);
            // TODO: check for exceptions, log exception and/or stop processing
        }
    }

    // Wait until finishing all tasks
    foreach ($tasks as $task) {
        pcntl_waitpid($task, $status);
        // TODO: check for exceptions, log exception and/or stop processing
    }
}

/**
 * Initialise database with randomized test rows
 *
 * @return void
 * @throws RandomException
 */
function seed(): void
{
    // get list for validation
    $mysqli = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE);
    $data = mysqli_query($mysqli, "SELECT count(*) cnt  FROM users");
    $row = mysqli_fetch_assoc($data);
    if ($row['cnt']>0) {
        echo "Database not empty, seed skipped\n";
        return;
    }

    for ($i=1; $i<SEED_SIZE_HUNDREDS_ROWS; $i++) {
        $values = [];
        for ($r=1; $r<100; $r++) {
            $confirmed = (int)(random_int(1,100) <= 15);
            $time = date('Y-m-d h:i:s', time()+60*60*24*random_int(1,5));
            $values[] = "('user_{$i}_{$r}','user_{$i}_{$r}@example.com','{$time}',{$confirmed})";
        }
        $values = implode(',',$values);

        mysqli_query($mysqli, "INSERT INTO users (username, email, validts, confirmed) VALUES {$values}");
    }

    echo "Seed finished\n";
}
