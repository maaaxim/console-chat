<?php
/**
 * Created by PhpStorm.
 * User: maxim
 * Date: 8/18/18
 * Time: 9:10 AM
 */

namespace Maaaxim\ConsoleChat;

class User
{
    /**
     * @var resource
     */
    protected $server;

    /**
     * @var string
     */
    protected $fromPort;

    /**
     * @var string
     */
    protected $toPort;

    /**
     * @var string
     */
    protected $fromAddress;

    /**
     * @var string
     */
    protected $toAddress;

    /**
     * User constructor.
     *
     * @param $fromPort
     * @param $toPort
     * @param $fromAddress
     * @param $toAddress
     */
    public function __construct($fromPort, $toPort, $fromAddress, $toAddress)
    {
        $this->fromPort = $fromPort;
        $this->toPort = $toPort;
        $this->fromAddress = $fromAddress;
        $this->toAddress = $toAddress;

        // start socket server
        $acceptor = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($acceptor, SOL_SOCKET, SO_REUSEADDR, 1);
        if ($acceptor === false)
            die("Socket created failed: " . socket_strerror(socket_last_error()) . "\n");

        if (!socket_bind($acceptor, $fromAddress, $fromPort))
            die("Socket created failed: " . socket_strerror(socket_last_error()) . "\n");

        if(!socket_listen($acceptor, 1))
            die("Socket listen " . socket_strerror(socket_last_error()) . "\n");

        $this->server = $acceptor;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        // parent process is waiting for child process
        // and stop if child stops
        while (($cid = pcntl_waitpid(0, $status)) != -1) {
            $exitCode = pcntl_wexitstatus($status);
            echo "Child $cid exited with status $exitCode" . PHP_EOL;
        }

        // also close socket
        socket_close($this->server);
    }

    /**
     * Init
     */
    public function init()
    {
        $pid = pcntl_fork();
        if ($pid == 0){
            while(true)
                $this->listenSocket();
        } else {
            // inside parent
            $newPid = pcntl_fork();
            if($newPid == 0){
                $this->listenConsole($pid);

            }
        }
    }

    /**
     * inside child process - start sock listener
     */
    protected function listenSocket(): void
    {
        // accept connections
        $socket = socket_accept($this->server);

        // show message
        $incomingMessage = socket_read($socket, 2048);
        echo "Your friend ({$this->toAddress}:{$this->toPort}): {$incomingMessage}" . PHP_EOL;

        socket_close($socket);
    }

    /**
     * inside child process - listen console
     *
     * @param $pid
     */
    protected function listenConsole($pid): void
    {
        while ($input = fgets(STDIN)) {
            $input = trim($input);

            if ($input == "x") {
                shell_exec("exec kill -9 {$pid}");
                exit(0);
            }

            // Send message
            $clientSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

            if ($clientSocket === false) {
                die("Socket create failed " . socket_strerror(socket_last_error()) . PHP_EOL);
            }

            $connect = @socket_connect($clientSocket, $this->toAddress, $this->toPort);
            if ($connect === false) {
                echo "Your friend ({$this->toAddress}:{$this->toPort}) is offline. Press x to exit or wait for him." . PHP_EOL;
                continue;
            }

            socket_write($clientSocket, $input, strlen($input));

            $answer = "";
            while (($batch = socket_read($clientSocket, 1)) !== "") {
                $answer .= $batch;
            }

            socket_close($clientSocket);
        }
    }
}