<?php

namespace App;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Connection implements MessageComponentInterface {

    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo "Congratulations! the server is now running\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
                , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');
	

		//*** Insert data
		$obj = json_decode($msg);

		$serverName = "localhost";
		$userName = "root";
		$userPassword = "";
		$dbName = "demo_phpsocketio";

		$conn = mysqli_connect($serverName,$userName,$userPassword,$dbName);
		$sql = "INSERT INTO mytable (name, email) 
			VALUES ('".$obj->name."','".$obj->email."')";
		$query = mysqli_query($conn,$sql);

		//*** Get data to json
		$myArray = array();
		$sql = "SELECT * FROM mytable ORDER BY id ASC ";
		$query = mysqli_query($conn,$sql);
		while($result=mysqli_fetch_array($query,MYSQLI_ASSOC))
		{
			$myArray[] = $result;
		}
		$json = json_encode($myArray);

		mysqli_close($conn);


        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                $client->send($json); //*** send json
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

}
?>