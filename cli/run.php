<?php

/**
 * 1. child process - listen socket
 * 2. child process - listen console
 * 3. parent process - hang and wait both (control them)
 */

$params = getopt('', [
    'fromAddress:',
    'fromPort:',
    'toAddress:',
    'toPort:'
]);

$fromAddress = $params['fromAddress'] ?? '127.0.0.1';
$fromPort = $params['fromPort'] ?? 9999;
$toAddress = $params['toAddress'] ?? '127.0.0.1';
$toPort = $params['toPort'] ?? 9999;

// start socket server
$acceptor = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($acceptor, SOL_SOCKET, SO_REUSEADDR, 1);
if ($acceptor === false)
    die("Socket created failed: " . socket_strerror(socket_last_error()) . "\n");

if (!socket_bind($acceptor, $fromAddress, $fromPort))
    die("Socket created failed: " . socket_strerror(socket_last_error()) . "\n");

if(!socket_listen($acceptor, 1))
    die("Socket listen " . socket_strerror(socket_last_error()) . "\n");

$pid = pcntl_fork();
if ($pid == 0){

    // inside child process - start sock listener
    while(true){

        // accept connections
        $socket = socket_accept($acceptor);

        // show message
        $incomingMessage = socket_read($socket, 2048);
        echo "Your friend ({$toAddress}:{$toPort}): {$incomingMessage}" . PHP_EOL;

        socket_close($socket);
    }

} else {
    // inside parent
    $newPid = pcntl_fork();
    if($newPid == 0){
        // inside child process - listen console
        while($input = fgets(STDIN)){
            $input = trim($input);

            if($input == "x"){
                shell_exec("exec kill -9 {$pid}");
                exit(0);
            }

            // Send message
            $clientSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

            if($clientSocket === false){
                die("Socket create failed " . socket_strerror(socket_last_error()) . PHP_EOL);
            }

            $connect = @socket_connect($clientSocket, $toAddress, $toPort);
            if($connect === false){
                // die("Socket connect failed " . socket_strerror(socket_last_error()) . PHP_EOL);
                echo "Your friend ({$toAddress}:{$toPort}) is offline. Press x to exit or wait for him." . PHP_EOL;
                continue;
            }

            $bytes = socket_write($clientSocket, $input, strlen($input));

            $answer = "";
            while (($batch = socket_read($clientSocket, 1)) !== ""){
                $answer .= $batch;
            }

            socket_close($clientSocket);
        }
    }
}

// parent process is waiting for child process
// and stop if child stops
while (($cid = pcntl_waitpid(0, $status)) != -1) {
    $exitCode = pcntl_wexitstatus($status);
    echo "Child $cid exited with status $exitCode" . PHP_EOL;
}

socket_close($acceptor);